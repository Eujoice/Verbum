<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logado']))  {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$projeto_id = "verbum-bd";
$acao       = $_POST['acao']       ?? '';
$reserva_id = $_POST['reserva_id'] ?? '';

/* ══════════════════════════════════════════════
   FUNÇÕES AUXILIARES DE FIRESTORE
══════════════════════════════════════════════ */

// Busca um documento pelo caminho completo
function firestoreGet($url) {
    $json = @file_get_contents($url);
    if (!$json) return null;
    return json_decode($json, true);
}

// Atualiza campos específicos de um documento (PATCH)
function firestorePatch($url, $fields, $fieldPaths) {
    $maskParams = implode('&', array_map(fn($f) => "updateMask.fieldPaths=$f", $fieldPaths));
    $fullUrl    = $url . '?' . $maskParams;

    $body = json_encode(['fields' => $fields]);

    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    $info   = curl_getinfo($ch);
    curl_close($ch);

    return ['http_code' => $info['http_code'], 'body' => json_decode($result, true)];
}

// Cria um novo documento em uma coleção (POST)
function firestorePost($url, $fields) {
    $body = json_encode(['fields' => $fields]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    $info   = curl_getinfo($ch);
    curl_close($ch);

    return ['http_code' => $info['http_code'], 'body' => json_decode($result, true)];
}

// Deleta um documento pelo caminho completo
function firestoreDelete($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $info   = curl_getinfo($ch);
    curl_close($ch);

    return $info['http_code'];
}

// Busca todos os documentos de uma coleção
function firestoreListar($url) {
    $json = @file_get_contents($url);
    if (!$json) return [];
    $data = json_decode($json, true);
    return $data['documents'] ?? [];
}

/* ══════════════════════════════════════════════
   ROTEADOR DE AÇÕES
══════════════════════════════════════════════ */
try {
    switch ($acao) {

        /* ──────────────────────────────────────
           AÇÃO 1: FAZER RESERVA (fluxo existente)
           Chamado pelo botão "Reservar" na página do livro
        ────────────────────────────────────── */
        case 'reservar':
            $livro_id          = $_POST['livro_id'] ?? '';
            $usuario_matricula = $_SESSION['usuario_matricula'];
            $usuario_nome      = $_SESSION['usuario_nome'];

            if (empty($livro_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID do livro inválido.']);
                exit();
            }

            $urlObra   = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/obras/$livro_id";
            $obra_data = firestoreGet($urlObra);

            if (!$obra_data) {
                throw new Exception("Erro ao localizar livro no acervo.");
            }

            $fields       = $obra_data['fields'] ?? [];
            $status_atual = $fields['status']['stringValue'] ?? 'Disponivel';
            $titulo_obra  = $fields['titulo']['stringValue'] ?? 'Livro';

            // Define se vai direto ou para a fila
            $tipo_reserva = ($status_atual === 'Emprestado') ? 'Fila' : 'Direta';

            $dadosReserva = [
                'matricula'    => ['stringValue' => $usuario_matricula],
                'nome_usuario' => ['stringValue' => $usuario_nome],
                'titulo_obra'  => ['stringValue' => $titulo_obra],
                'obra_id'      => ['stringValue' => $livro_id],
                'data_reserva' => ['stringValue' => date('Y-m-d H:i:s')],
                'tipo'         => ['stringValue' => $tipo_reserva]
            ];

            $urlReservas = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas";
            $resultado   = firestorePost($urlReservas, $dadosReserva);

            if ($resultado['http_code'] !== 200) {
                throw new Exception("Erro ao registrar reserva no banco.");
            }

            $posicao = 0;
            if ($tipo_reserva === 'Direta') {
                // Atualiza status da obra para Reservado
                firestorePatch($urlObra, ['status' => ['stringValue' => 'Reservado']], ['status']);
                $msg = "Reserva confirmada! Retire no balcão em até 48h.";
            } else {
                // Lógica de contagem de fila
                $urlTodas = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas";
                $todas = firestoreListar($urlTodas);
                $fila = array_filter($todas, function($doc) use ($livro_id) {
                    $f = $doc['fields'] ?? [];
                    return ($f['obra_id']['stringValue'] ?? '') === $livro_id && 
                           ($f['tipo']['stringValue'] ?? '') === 'Fila';
                });
                $posicao = count($fila);
                $msg = "Você entrou na fila de espera.";
            }

            echo json_encode([
                'sucesso' => true, 
                'mensagem' => $msg, 
                'tipo' => $tipo_reserva, 
                'posicao' => $posicao
            ]);
            break;

            if ($resultado['http_code'] !== 200) {
                throw new Exception("Erro ao registrar reserva no banco.");
            }

            if ($tipo_reserva === 'Direta') {
                firestorePatch($urlObra, ['status' => ['stringValue' => 'Reservado']], ['status']);
                $msg = "Reserva confirmada! Retire no balcão em até 48h.";
            } else {
                $msg = "Você entrou na fila de espera para este livro.";
            }

            echo json_encode(['sucesso' => true, 'mensagem' => $msg]);
            break;


        /* ──────────────────────────────────────
           AÇÃO 2: CONFIRMAR RETIRADA
           Bibliotecário converte a reserva direta em empréstimo
        ────────────────────────────────────── */
        case 'confirmar_retirada':
            $obra_id = $_POST['obra_id'] ?? '';

            if (empty($reserva_id) || empty($obra_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Dados insuficientes para confirmar retirada.']);
                exit();
            }

            // 1. Busca dados da reserva
            $urlReserva  = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas/$reserva_id";
            $reserva_doc = firestoreGet($urlReserva);

            if (!$reserva_doc) {
                throw new Exception("Reserva não encontrada.");
            }

            $rf             = $reserva_doc['fields'] ?? [];
            $matricula_aluno = $rf['matricula']['stringValue']    ?? '';
            $nome_aluno      = $rf['nome_usuario']['stringValue'] ?? '';
            $titulo_obra     = $rf['titulo_obra']['stringValue']  ?? '';

            // 2. Cria o empréstimo na coleção 'emprestimos'
            $dataEmprestimo  = date('Y-m-d');
            $dataDevolucao   = date('Y-m-d', strtotime('+14 days')); // prazo padrão de 14 dias

            $dadosEmprestimo = [
                'usuario_id'              => ['stringValue' => $matricula_aluno],
                'nome_usuario'            => ['stringValue' => $nome_aluno],
                'obra_id'                 => ['stringValue' => $obra_id],
                'titulo_obra'             => ['stringValue' => $titulo_obra],
                'data_emprestimo'         => ['stringValue' => $dataEmprestimo],
                'data_devolucao_prevista' => ['stringValue' => $dataDevolucao],
                'status'                  => ['stringValue' => 'ativo'],
                'registrado_por'          => ['stringValue' => $_SESSION['usuario_nome']]
            ];

            $urlEmprestimos = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/emprestimos";
            $resultEmp      = firestorePost($urlEmprestimos, $dadosEmprestimo);

            if ($resultEmp['http_code'] !== 200) {
                throw new Exception("Erro ao criar o empréstimo.");
            }

            // 3. Atualiza o status da obra para 'Emprestado'
            $urlObra = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/obras/$obra_id";
            firestorePatch($urlObra, ['status' => ['stringValue' => 'Emprestado']], ['status']);

            // 4. Remove a reserva (já foi convertida em empréstimo)
            firestoreDelete($urlReserva);

            echo json_encode([
                'sucesso'   => true,
                'mensagem'  => "Retirada confirmada! Empréstimo criado para $nome_aluno. Devolução prevista: " . date('d/m/Y', strtotime($dataDevolucao)) . "."
            ]);
            break;


        /* ──────────────────────────────────────
           AÇÃO 3: CANCELAR RESERVA DIRETA
           Bibliotecário cancela uma reserva direta e libera o livro
        ────────────────────────────────────── */
        case 'cancelar':
            if (empty($reserva_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID da reserva não informado.']);
                exit();
            }

            // 1. Busca a reserva para pegar o obra_id
            $urlReserva  = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas/$reserva_id";
            $reserva_doc = firestoreGet($urlReserva);

            if (!$reserva_doc) {
                throw new Exception("Reserva não encontrada.");
            }

            $rf      = $reserva_doc['fields'] ?? [];
            $obra_id = $rf['obra_id']['stringValue'] ?? '';

            // 2. Deleta a reserva
            $httpCode = firestoreDelete($urlReserva);

            if ($httpCode !== 200) {
                throw new Exception("Erro ao cancelar a reserva.");
            }

            // 3. Verifica se há alguém na fila para este livro
            $urlTodasReservas = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas";
            $todasReservas    = firestoreListar($urlTodasReservas);

            $filaDoLivro = array_filter($todasReservas, function($doc) use ($obra_id) {
                $f = $doc['fields'] ?? [];
                return ($f['obra_id']['stringValue'] ?? '') === $obra_id
                    && ($f['tipo']['stringValue'] ?? '')   === 'Fila';
            });

            if (!empty($filaDoLivro)) {
                // Há fila: o próximo da fila vira reserva direta
                usort($filaDoLivro, function($a, $b) {
                    $da = $a['fields']['data_reserva']['stringValue'] ?? '';
                    $db = $b['fields']['data_reserva']['stringValue'] ?? '';
                    return strcmp($da, $db);
                });

                $proximo     = reset($filaDoLivro);
                $proximoPath = $proximo['name'];
                $proximoId   = basename($proximoPath);

                // Atualiza o tipo da reserva do próximo para 'Direta'
                $urlProximo = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas/$proximoId";
                firestorePatch($urlProximo, ['tipo' => ['stringValue' => 'Direta']], ['tipo']);

                // Mantém o status da obra como 'Reservado'
                $urlObra = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/obras/$obra_id";
                firestorePatch($urlObra, ['status' => ['stringValue' => 'Reservado']], ['status']);

                $msg = "Reserva cancelada. O próximo da fila foi notificado e agora tem prioridade de retirada.";
            } else {
                // Sem fila: livro volta para disponível
                if (!empty($obra_id)) {
                    $urlObra = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/obras/$obra_id";
                    firestorePatch($urlObra, ['status' => ['stringValue' => 'Disponivel']], ['status']);
                }
                $msg = "Reserva cancelada. O exemplar está disponível novamente no acervo.";
            }

            echo json_encode(['sucesso' => true, 'mensagem' => $msg]);
            break;


        /* ──────────────────────────────────────
           AÇÃO 4: REMOVER DA FILA
           Bibliotecário remove um aluno da fila de espera
        ────────────────────────────────────── */
        case 'remover_fila':
            if (empty($reserva_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID da reserva não informado.']);
                exit();
            }

            $urlReserva = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas/$reserva_id";
            $httpCode   = firestoreDelete($urlReserva);

            if ($httpCode !== 200) {
                throw new Exception("Erro ao remover da fila.");
            }

            // A fila se reordena automaticamente no frontend pelo campo data_reserva
            echo json_encode(['sucesso' => true, 'mensagem' => 'Aluno removido da fila de espera com sucesso.']);
            break;


        /* ──────────────────────────────────────
           AÇÃO 5: ADICIONAR OBSERVAÇÃO
           Bibliotecário adiciona uma nota a uma reserva
        ────────────────────────────────────── */
        case 'observacao':
            $observacao = trim($_POST['observacao'] ?? '');

            if (empty($reserva_id)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID da reserva não informado.']);
                exit();
            }

            if (empty($observacao)) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'O texto da observação não pode estar vazio.']);
                exit();
            }

            $urlReserva = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas/$reserva_id";

            $novosCampos = [
                'observacao'           => ['stringValue' => $observacao],
                'observacao_por'       => ['stringValue' => $_SESSION['usuario_nome']],
                'observacao_data'      => ['stringValue' => date('Y-m-d H:i:s')]
            ];

            $result = firestorePatch($urlReserva, $novosCampos, ['observacao', 'observacao_por', 'observacao_data']);

            if ($result['http_code'] !== 200) {
                throw new Exception("Erro ao salvar a observação.");
            }

            echo json_encode(['sucesso' => true, 'mensagem' => 'Observação salva com sucesso.']);
            break;


        /* ──────────────────────────────────────
           AÇÃO DESCONHECIDA
        ────────────────────────────────────── */
        default:
            echo json_encode(['sucesso' => false, 'mensagem' => "Ação '$acao' não reconhecida."]);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
?>