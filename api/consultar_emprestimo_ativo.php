<?php
/**
 * consultar_emprestimo_ativo.php
 * Verifica se o usuário da sessão tem um empréstimo ATIVO para o livro informado.
 */
session_start();
require '../includes/config.php';
header('Content-Type: application/json');

$livroId   = trim($_GET['livro_id'] ?? '');
$matricula = $_SESSION['usuario_matricula'] ?? '';
$projetoID = 'verbum-bd';

if (empty($livroId) || empty($matricula)) {
    echo json_encode(['temEmprestimo' => false]);
    exit;
}

// runQuery com filtro composto: usuario + obra + ativo — 1 leitura mínima
$base  = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";
$query = json_encode([
    'structuredQuery' => [
        'from' => [['collectionId' => 'emprestimos']],
        'where' => [
            'compositeFilter' => [
                'op' => 'AND',
                'filters' => [
                    ['fieldFilter' => ['field' => ['fieldPath' => 'usuario_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $matricula]]],
                    ['fieldFilter' => ['field' => ['fieldPath' => 'obra_id'],    'op' => 'EQUAL', 'value' => ['stringValue' => $livroId]]],
                    ['fieldFilter' => ['field' => ['fieldPath' => 'status'],     'op' => 'EQUAL', 'value' => ['stringValue' => 'ativo']]],
                ]
            ]
        ],
        'limit' => 1
    ]
]);

$ch = curl_init("$base:runQuery");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = json_decode(curl_exec($ch), true);
curl_close($ch);

$doc = $res[0]['document'] ?? null;
if (!$doc) {
    echo json_encode(['temEmprestimo' => false]);
    exit;
}

$f = $doc['fields'] ?? [];
$pathParts    = explode('/', $doc['name']);
$emprestimoId = end($pathParts);
$dataFimStr   = $f['data_devolucao_prevista']['stringValue'] ?? '';
$dataIniStr   = $f['data_emprestimo']['stringValue']         ?? '';

$diasRestantes = null;
if (!empty($dataFimStr)) {
    $hoje    = new DateTime(); $hoje->setTime(0,0,0);
    $dataFim = new DateTime($dataFimStr); $dataFim->setTime(0,0,0);
    $diasRestantes = (int)$hoje->diff($dataFim)->format('%r%a');
}

echo json_encode([
    'temEmprestimo'  => true,
    'emprestimo_id'  => $emprestimoId,
    'data_ini'       => $dataIniStr,
    'data_fim'       => $dataFimStr,
    'dias_restantes' => $diasRestantes,
]);
