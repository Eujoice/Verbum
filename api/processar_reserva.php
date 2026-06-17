<?php
session_start();
require '../includes/config.php';
require_once '../includes/dias_uteis.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logado'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$projeto_id = "verbum-bd";
$base       = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents";
$acao       = $_POST['acao']       ?? '';
$reserva_id = $_POST['reserva_id'] ?? '';

/* ── Helpers ─────────────────────────────────────────────────────────────── */

function fsGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $r = curl_exec($ch); curl_close($ch);
    return json_decode($r, true);
}

function fsPatch($url, $fields, $fieldPaths) {
    $mask    = implode('&', array_map(fn($f) => "updateMask.fieldPaths=$f", $fieldPaths));
    $ch = curl_init("$url?$mask");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => json_encode(['fields' => $fields]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $r = curl_exec($ch); $info = curl_getinfo($ch); curl_close($ch);
    return ['http_code' => $info['http_code'], 'body' => json_decode($r, true)];
}

function fsPost($url, $fields) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['fields' => $fields]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $r = curl_exec($ch); $info = curl_getinfo($ch); curl_close($ch);
    return ['http_code' => $info['http_code'], 'body' => json_decode($r, true)];
}

function fsDelete($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_RETURNTRANSFER => true]);
    curl_exec($ch); $info = curl_getinfo($ch); curl_close($ch);
    return $info['http_code'];
}

/**
 * Busca reservas de FILA para um livro específico via runQuery.
 * Retorna array já ordenado por data_reserva ASC.
 */
function buscarFilaDoLivro($base, $obra_id) {
    $query = json_encode([
        'structuredQuery' => [
            'from' => [['collectionId' => 'reservas']],
            'where' => [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => [
                        ['fieldFilter' => ['field' => ['fieldPath' => 'obra_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $obra_id]]],
                        ['fieldFilter' => ['field' => ['fieldPath' => 'tipo'],    'op' => 'EQUAL', 'value' => ['stringValue' => 'Fila']]],
                    ]
                ]
            ],
            'orderBy' => [['field' => ['fieldPath' => 'data_reserva'], 'direction' => 'ASCENDING']],
            'limit' => 50,
        ]
    ]);

    $ch = curl_init("$base:runQuery");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $query,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $res = json_decode(curl_exec($ch), true) ?? [];
    curl_close($ch);

    $fila = [];
    foreach ($res as $item) {
        if (!isset($item['document'])) continue;
        $fila[] = $item['document'];
    }
    return $fila;
}

/* ── Roteador ────────────────────────────────────────────────────────────── */
try {
    switch ($acao) {

        /* ── RESERVAR ──────────────────────────────────────────────────── */
        case 'reservar':
            $livro_id          = $_POST['livro_id'] ?? '';
            $usuario_matricula = $_SESSION['usuario_matricula'];
            $usuario_nome      = $_SESSION['usuario_nome'];

            if (empty($livro_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID do livro inválido.']); exit();
            }

            // Verifica multa: busca somente empréstimos ativos deste usuário
            $queryMulta = json_encode([
                'structuredQuery' => [
                    'from' => [['collectionId' => 'emprestimos']],
                    'where' => [
                        'compositeFilter' => [
                            'op' => 'AND',
                            'filters' => [
                                ['fieldFilter' => ['field' => ['fieldPath' => 'usuario_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $usuario_matricula]]],
                                ['fieldFilter' => ['field' => ['fieldPath' => 'status'],     'op' => 'EQUAL', 'value' => ['stringValue' => 'ativo']]],
                            ]
                        ]
                    ],
                    'select' => ['fields' => [['fieldPath' => 'data_devolucao_prevista']]],
                ]
            ]);
            $ch = curl_init("$base:runQuery");
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $queryMulta,
                CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
            $docsEmp = json_decode(curl_exec($ch), true) ?? [];
            curl_close($ch);

            foreach ($docsEmp as $eItem) {
                if (!isset($eItem['document'])) continue;
                $ef    = $eItem['document']['fields'] ?? [];
                $dfStr = $ef['data_devolucao_prevista']['stringValue'] ?? '';
                if (empty($dfStr)) continue;
                $diasAtraso = contarDiasUteisAtraso($dfStr);
                if ($diasAtraso > 0) {
                    $valor = number_format($diasAtraso * 1.00, 2, ',', '.');
                    echo json_encode(['sucesso' => false, 'temMulta' => true,
                        'mensagem' => "Você possui multa pendente de R$ $valor. Quite antes de reservar."]);
                    exit();
                }
            }

            // Busca a obra pelo ID direto
            $urlObra   = "$base/obras/$livro_id";
            $obra_data = fsGet($urlObra);
            if (!$obra_data || isset($obra_data['error'])) throw new Exception("Erro ao localizar livro.");

            $fields       = $obra_data['fields'] ?? [];
            $status_atual = $fields['status']['stringValue'] ?? 'Disponivel';
            $titulo_obra  = $fields['titulo']['stringValue'] ?? 'Livro';
            $tipo_reserva = ($status_atual === 'Emprestado') ? 'Fila' : 'Direta';

            $dadosReserva = [
                'matricula'    => ['stringValue' => $usuario_matricula],
                'nome_usuario' => ['stringValue' => $usuario_nome],
                'titulo_obra'  => ['stringValue' => $titulo_obra],
                'obra_id'      => ['stringValue' => $livro_id],
                'data_reserva' => ['stringValue' => date('Y-m-d H:i:s')],
                'tipo'         => ['stringValue' => $tipo_reserva],
            ];

            $resultado = fsPost("$base/reservas", $dadosReserva);
            if ($resultado['http_code'] !== 200) throw new Exception("Erro ao registrar reserva.");

            if ($tipo_reserva === 'Direta') {
                fsPatch($urlObra, ['status' => ['stringValue' => 'Reservado']], ['status']);
                $msg = "Reserva confirmada! Retire no balcão em até 48h.";
                $posicao = 0;
            } else {
                // Conta posição na fila via query filtrada
                $fila    = buscarFilaDoLivro($base, $livro_id);
                $posicao = count($fila);
                $msg     = "Você entrou na fila de espera.";
            }

            echo json_encode(['sucesso' => true, 'mensagem' => $msg, 'tipo' => $tipo_reserva, 'posicao' => $posicao]);
            break;


        /* ── CONFIRMAR RETIRADA ────────────────────────────────────────── */
        case 'confirmar_retirada':
            $obra_id = $_POST['obra_id'] ?? '';
            if (empty($reserva_id) || empty($obra_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Dados insuficientes.']); exit();
            }

            $urlReserva  = "$base/reservas/$reserva_id";
            $reserva_doc = fsGet($urlReserva);
            if (!$reserva_doc) throw new Exception("Reserva não encontrada.");

            $rf             = $reserva_doc['fields'] ?? [];
            $matricula_aluno = $rf['matricula']['stringValue']    ?? '';
            $nome_aluno      = $rf['nome_usuario']['stringValue'] ?? '';
            $titulo_obra     = $rf['titulo_obra']['stringValue']  ?? '';

            $dataEmprestimo = date('Y-m-d');
            $dataDevolucao  = adicionarDiasUteis($dataEmprestimo, 14);

            $dadosEmprestimo = [
                'usuario_id'              => ['stringValue' => $matricula_aluno],
                'nome_usuario'            => ['stringValue' => $nome_aluno],
                'obra_id'                 => ['stringValue' => $obra_id],
                'titulo_obra'             => ['stringValue' => $titulo_obra],
                'data_emprestimo'         => ['stringValue' => $dataEmprestimo],
                'data_devolucao_prevista' => ['stringValue' => $dataDevolucao],
                'status'                  => ['stringValue' => 'ativo'],
                'registrado_por'          => ['stringValue' => $_SESSION['usuario_nome']],
            ];

            $resultEmp = fsPost("$base/emprestimos", $dadosEmprestimo);
            if ($resultEmp['http_code'] !== 200) throw new Exception("Erro ao criar empréstimo.");

            fsPatch("$base/obras/$obra_id", ['status' => ['stringValue' => 'Emprestado']], ['status']);
            fsDelete($urlReserva);

            $dataFormatada = date('d/m/Y', strtotime($dataDevolucao));
            fsPost("$base/notificacoes", [
                'matricula' => ['stringValue' => $matricula_aluno],
                'mensagem'  => ['stringValue' => "Empréstimo realizado! \"$titulo_obra\" deve ser devolvido até $dataFormatada."],
                'data'      => ['stringValue' => date('Y-m-d H:i:s')],
                'lida'      => ['booleanValue' => false],
            ]);

            echo json_encode(['sucesso' => true,
                'mensagem' => "Retirada confirmada para $nome_aluno. Devolução: $dataFormatada."]);
            break;


        /* ── CANCELAR ──────────────────────────────────────────────────── */
        case 'cancelar':
            if (empty($reserva_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID da reserva não informado.']); exit();
            }

            $urlReserva  = "$base/reservas/$reserva_id";
            $reserva_doc = fsGet($urlReserva);
            if (!$reserva_doc) throw new Exception("Reserva não encontrada.");

            $obra_id  = $reserva_doc['fields']['obra_id']['stringValue'] ?? '';
            $httpCode = fsDelete($urlReserva);
            if ($httpCode !== 200) throw new Exception("Erro ao cancelar reserva.");

            // Busca próximo da fila via query filtrada
            $fila = buscarFilaDoLivro($base, $obra_id);

            if (!empty($fila)) {
                $proximo   = $fila[0];
                $proximoId = basename($proximo['name']);
                fsPatch("$base/reservas/$proximoId", ['tipo' => ['stringValue' => 'Direta']], ['tipo']);
                fsPatch("$base/obras/$obra_id",      ['status' => ['stringValue' => 'Reservado']], ['status']);
                $msg = "Reserva cancelada. O próximo da fila foi promovido.";
            } else {
                if (!empty($obra_id)) {
                    fsPatch("$base/obras/$obra_id", ['status' => ['stringValue' => 'Disponivel']], ['status']);
                }
                $msg = "Reserva cancelada. Exemplar disponível novamente.";
            }

            echo json_encode(['sucesso' => true, 'mensagem' => $msg]);
            break;


        /* ── REMOVER DA FILA ───────────────────────────────────────────── */
        case 'remover_fila':
            if (empty($reserva_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID da reserva não informado.']); exit();
            }
            $httpCode = fsDelete("$base/reservas/$reserva_id");
            if ($httpCode !== 200) throw new Exception("Erro ao remover da fila.");
            echo json_encode(['sucesso' => true, 'mensagem' => 'Aluno removido da fila.']);
            break;


        /* ── OBSERVAÇÃO ────────────────────────────────────────────────── */
        case 'observacao':
            $observacao = trim($_POST['observacao'] ?? '');
            if (empty($reserva_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID da reserva não informado.']); exit();
            }
            if (empty($observacao)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Observação não pode estar vazia.']); exit();
            }
            $res = fsPatch("$base/reservas/$reserva_id", [
                'observacao'      => ['stringValue' => $observacao],
                'observacao_por'  => ['stringValue' => $_SESSION['usuario_nome']],
                'observacao_data' => ['stringValue' => date('Y-m-d H:i:s')],
            ], ['observacao', 'observacao_por', 'observacao_data']);
            if ($res['http_code'] !== 200) throw new Exception("Erro ao salvar observação.");
            echo json_encode(['sucesso' => true, 'mensagem' => 'Observação salva.']);
            break;


        /* ── NOTIFICAÇÃO MANUAL ────────────────────────────────────────── */
        case 'enviar_notificacao_manual':
            $matricula    = $_POST['matricula']    ?? '';
            $titulo_livro = $_POST['titulo_livro'] ?? 'Livro';
            if (empty($matricula)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Matrícula não informada.']); exit();
            }
            $resultado = fsPost("$base/notificacoes", [
                'matricula' => ['stringValue' => $matricula],
                'mensagem'  => ['stringValue' => "Lembrete: \"$titulo_livro\" aguarda sua retirada no balcão!"],
                'data'      => ['stringValue' => date('Y-m-d H:i:s')],
                'lida'      => ['booleanValue' => false],
            ]);
            if ($resultado['http_code'] !== 200) throw new Exception("Erro ao enviar notificação.");
            echo json_encode(['sucesso' => true, 'mensagem' => 'Notificação enviada!']);
            break;


        default:
            echo json_encode(['sucesso' => false, 'mensagem' => "Ação '$acao' não reconhecida."]);
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
