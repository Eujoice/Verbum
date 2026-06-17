<?php
require '../includes/config.php';

$acao         = $_POST['acao']         ?? '';
$idObra       = $_POST['livro_id']     ?? '';
$idEmprestimo = $_POST['emprestimo_id'] ?? '';
$projetoID    = "verbum-bd";
$base         = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";

if ($acao !== 'devolver' || empty($idObra)) exit;

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// 1. Lê o empréstimo pelo ID direto (1 leitura, não a coleção)
if (!empty($idEmprestimo)) {
    $urlEmp = "$base/emprestimos/$idEmprestimo";
    curl_setopt($ch, CURLOPT_URL, $urlEmp);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $dadosEmp = json_decode(curl_exec($ch), true);

    if (isset($dadosEmp['fields'])) {
        $obraId    = $dadosEmp['fields']['obra_id']['stringValue']    ?? '';
        $matriculaAluno = $dadosEmp['fields']['usuario_id']['stringValue'] ?? '';

        // Busca título da obra (não está no documento de empréstimo)
        $tituloObra = $obraId;
        curl_setopt($ch, CURLOPT_URL, "$base/obras/$obraId");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $dadosObra = json_decode(curl_exec($ch), true);
        if (isset($dadosObra['fields']['titulo']['stringValue'])) {
            $tituloObra = $dadosObra['fields']['titulo']['stringValue'];
        }

        // Busca nome do usuário (não está no documento de empréstimo)
        $nomeUsuario = $matriculaAluno;
        curl_setopt($ch, CURLOPT_URL, "$base/usuarios/$matriculaAluno");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $dadosUsuario = json_decode(curl_exec($ch), true);
        if (isset($dadosUsuario['fields']['nome']['stringValue'])) {
            $nomeUsuario = $dadosUsuario['fields']['nome']['stringValue'];
        }

        // Salva histórico com todos os campos preenchidos
        curl_setopt($ch, CURLOPT_URL, "$base/historico");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => [
            'usuario_id'          => $dadosEmp['fields']['usuario_id'],
            'obra_id'             => $dadosEmp['fields']['obra_id'],
            'titulo_obra'         => ['stringValue' => $tituloObra],
            'nome_usuario'        => ['stringValue' => $nomeUsuario],
            'data_retirada'       => $dadosEmp['fields']['data_emprestimo'],
            'data_devolucao_real' => ['stringValue' => date('Y-m-d')],
        ]]));
        curl_exec($ch);

        // Inativa o empréstimo
        curl_setopt($ch, CURLOPT_URL, "$urlEmp?updateMask.fieldPaths=status");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => ['status' => ['stringValue' => 'inativo']]]));
        curl_exec($ch);

        // Notificação ao aluno
        if (!empty($matriculaAluno)) {
            curl_setopt($ch, CURLOPT_URL, "$base/notificacoes");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => [
                'matricula' => ['stringValue' => $matriculaAluno],
                'mensagem'  => ['stringValue' => "Devolução confirmada! \"$tituloObra\" devolvido em " . date('d/m/Y') . "."],
                'data'      => ['stringValue' => date('Y-m-d H:i:s')],
                'lida'      => ['booleanValue' => false],
            ]]));
            curl_exec($ch);
        }
    }
}

// 2. Busca primeiro da fila via runQuery (filtra por obra_id + tipo=Fila)
$queryFila = json_encode([
    'structuredQuery' => [
        'from' => [['collectionId' => 'reservas']],
        'where' => [
            'compositeFilter' => [
                'op' => 'AND',
                'filters' => [
                    ['fieldFilter' => ['field' => ['fieldPath' => 'obra_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $idObra]]],
                    ['fieldFilter' => ['field' => ['fieldPath' => 'tipo'],    'op' => 'EQUAL', 'value' => ['stringValue' => 'Fila']]],
                ]
            ]
        ],
        'orderBy' => [['field' => ['fieldPath' => 'data_reserva'], 'direction' => 'ASCENDING']],
        'limit'   => 1,
    ]
]);

curl_setopt($ch, CURLOPT_URL, "$base:runQuery");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $queryFila);
$resFila = json_decode(curl_exec($ch), true) ?? [];

$statusFinal  = 'Disponível';
$primeiroFila = $resFila[0]['document'] ?? null;

if ($primeiroFila) {
    $statusFinal  = 'Reservado';
    $proximoPath  = $primeiroFila['name'];
    curl_setopt($ch, CURLOPT_URL, "https://firestore.googleapis.com/v1/{$proximoPath}?updateMask.fieldPaths=tipo");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => ['tipo' => ['stringValue' => 'Direta']]]));
    curl_exec($ch);
}

// 3. Atualiza a obra
curl_setopt($ch, CURLOPT_URL, "$base/obras/{$idObra}?updateMask.fieldPaths=status&updateMask.fieldPaths=emprestado_por&updateMask.fieldPaths=id_emprestimo_atual");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => [
    'status'              => ['stringValue' => $statusFinal],
    'emprestado_por'      => ['stringValue' => ''],
    'id_emprestimo_atual' => ['stringValue' => ''],
]]));
curl_exec($ch);

curl_close($ch);
echo "Sucesso!";