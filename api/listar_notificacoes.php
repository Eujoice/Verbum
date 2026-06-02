<?php
session_start();
require '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logado']) || !isset($_SESSION['usuario_matricula'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autorizado.']);
    exit();
}

$projeto_id = "verbum-bd";
$matricula_aluno = $_SESSION['usuario_matricula'];

/* ══════════════════════════════════════════════
   FLUXO 1: MARCAR COMO LIDA (POST)
══════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID da notificação não informada.']);
        exit();
    }

    $urlPatch = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes/$id?updateMask.fieldPaths=lida";
    
    $body = json_encode([
        'fields' => [
            'lida' => ['booleanValue' => true]
        ]
    ]);

    $ch = curl_init($urlPatch);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    $info   = curl_getinfo($ch);
    curl_close($ch);

    if ($info['http_code'] === 200) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Notificação marcada como lida.']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar no banco de dados.']);
    }
    exit();
}

/* ══════════════════════════════════════════════
   FLUXO 2: LISTAR NOTIFICAÇÕES (GET)
   CORREÇÃO: Usa a Firestore runQuery API para filtrar
   por matrícula DIRETAMENTE no banco — em vez de baixar
   todos os documentos e filtrar no PHP.
   Isso reduz as leituras de N-total para N-do-aluno.
══════════════════════════════════════════════ */
$urlQuery = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents:runQuery";

$queryBody = json_encode([
    'structuredQuery' => [
        'from' => [['collectionId' => 'notificacoes']],
        'where' => [
            'fieldFilter' => [
                'field'  => ['fieldPath' => 'matricula'],
                'op'     => 'EQUAL',
                'value'  => ['stringValue' => $matricula_aluno]
            ]
        ],
        'orderBy' => [
            ['field' => ['fieldPath' => 'data'], 'direction' => 'DESCENDING']
        ],
        'limit' => 50
    ]
]);

$ch = curl_init($urlQuery);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $queryBody);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$json = curl_exec($ch);
curl_close($ch);

if (!$json) {
    echo json_encode(['sucesso' => true, 'total_nao_lidas' => 0, 'notificacoes' => []]);
    exit();
}

$resultados = json_decode($json, true);
$notificacoes_filtradas = [];
$total_nao_lidas = 0;

foreach ($resultados as $item) {
    if (!isset($item['document'])) continue;

    $doc    = $item['document'];
    $fields = $doc['fields'] ?? [];
    $id     = basename($doc['name']);

    $mensagem = $fields['mensagem']['stringValue'] ?? '';
    $data     = $fields['data']['stringValue'] ?? '';
    $lida     = $fields['lida']['booleanValue'] ?? false;

    if (!$lida) {
        $total_nao_lidas++;
    }

    $notificacoes_filtradas[] = [
        'id'       => $id,
        'mensagem' => $mensagem,
        'data'     => $data,
        'lida'     => $lida
    ];
}

echo json_encode([
    'sucesso'         => true,
    'total_nao_lidas' => $total_nao_lidas,
    'notificacoes'    => $notificacoes_filtradas
]);
?>
