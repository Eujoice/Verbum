<?php
require '../includes/config.php';
header('Content-Type: application/json');

$termo = isset($_GET['q']) ? mb_strtolower(trim($_GET['q'])) : '';
if (empty($termo)) { echo json_encode([]); exit; }

$projetoID = "verbum-bd";

// runQuery: filtra no banco, não baixa a coleção inteira
$url = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents:runQuery";

$query = json_encode([
    'structuredQuery' => [
        'from' => [['collectionId' => 'usuarios']],
        'select' => ['fields' => [
            ['fieldPath' => 'nome'],
            ['fieldPath' => 'matricula'],
        ]],
        'limit' => 10
    ]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = curl_exec($ch);
curl_close($ch);

$items    = json_decode($res, true) ?? [];
$resultados = [];

foreach ($items as $item) {
    if (!isset($item['document'])) continue;
    $f         = $item['document']['fields'] ?? [];
    $nome      = $f['nome']['stringValue']      ?? '';
    $matricula = $f['matricula']['stringValue'] ?? '';

    if (
        strpos(mb_strtolower($nome), $termo) !== false ||
        strpos($matricula, $termo) !== false
    ) {
        $resultados[] = ['nome' => $nome, 'matricula' => $matricula];
        if (count($resultados) >= 5) break;
    }
}

echo json_encode($resultados);
