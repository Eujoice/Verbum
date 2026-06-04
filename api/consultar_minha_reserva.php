<?php
session_start();
require '../includes/config.php';
header('Content-Type: application/json');

$livro_id  = $_GET['livro_id'] ?? '';
$matricula = $_SESSION['usuario_matricula'] ?? '';
$projeto_id = "verbum-bd";

if (!$livro_id || !$matricula) {
    echo json_encode(['jaReservado' => false]);
    exit();
}

$base = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents";

// runQuery: filtra reservas SOMENTE deste livro — não baixa toda a coleção
$query = json_encode([
    'structuredQuery' => [
        'from' => [['collectionId' => 'reservas']],
        'where' => [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'obra_id'],
                'op'    => 'EQUAL',
                'value' => ['stringValue' => $livro_id],
            ]
        ],
        'select' => ['fields' => [
            ['fieldPath' => 'matricula'],
            ['fieldPath' => 'tipo'],
            ['fieldPath' => 'data_reserva'],
        ]],
        'orderBy' => [['field' => ['fieldPath' => 'data_reserva'], 'direction' => 'ASCENDING']],
    ]
]);

$ch = curl_init("$base:runQuery");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = json_decode(curl_exec($ch), true) ?? [];
curl_close($ch);

$filaOrdenada = [];

foreach ($res as $item) {
    if (!isset($item['document'])) continue;
    $f     = $item['document']['fields'] ?? [];
    $mat   = $f['matricula']['stringValue']   ?? '';
    $tipo  = $f['tipo']['stringValue']        ?? '';
    $data  = $f['data_reserva']['stringValue'] ?? '';

    // Reserva direta deste usuário: responde imediatamente
    if ($mat === $matricula && $tipo === 'Direta') {
        echo json_encode(['jaReservado' => true, 'tipo' => 'Direta', 'posicao' => 0]);
        exit();
    }

    if ($tipo === 'Fila') {
        $filaOrdenada[] = ['matricula' => $mat, 'data' => $data];
    }
}

// Encontra posição na fila (já ordenada pelo Firestore via orderBy)
foreach ($filaOrdenada as $index => $reserva) {
    if ($reserva['matricula'] === $matricula) {
        echo json_encode(['jaReservado' => true, 'tipo' => 'Fila', 'posicao' => $index + 1]);
        exit();
    }
}

echo json_encode(['jaReservado' => false, 'tipo' => 'Fila', 'posicao' => 0]);
