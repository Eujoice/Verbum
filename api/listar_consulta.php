<?php
require '../includes/config.php';
header('Content-Type: application/json');

// $projetoID vem do config.php
$base = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";

function buscarColecao($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return $data['documents'] ?? [];
}

function runQuery($base, $query) {
    $ch = curl_init("$base:runQuery");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $res  = json_decode(curl_exec($ch), true) ?? [];
    curl_close($ch);
    // runQuery retorna array de {document: {...}}, mapeia para formato de coleção
    return array_filter(array_map(fn($i) => $i['document'] ?? null, $res));
}

// Obras: coleção completa (usada para exibição no acervo)
$obras = buscarColecao("$base/obras");

// Empréstimos: somente ativos — histórico fica na coleção 'historico'
$emprestimosAtivos = runQuery($base, [
    'structuredQuery' => [
        'from' => [['collectionId' => 'emprestimos']],
        'where' => ['fieldFilter' => [
            'field' => ['fieldPath' => 'status'],
            'op'    => 'EQUAL',
            'value' => ['stringValue' => 'ativo'],
        ]],
    ]
]);

// Reservas: somente ativas
$reservas = runQuery($base, [
    'structuredQuery' => [
        'from' => [['collectionId' => 'reservas']],
    ]
]);

// Usuários: projeta somente nome + matrícula (a página não precisa de senha/CPF etc)
$usuariosRaw = runQuery($base, [
    'structuredQuery' => [
        'from' => [['collectionId' => 'usuarios']],
        'select' => ['fields' => [
            ['fieldPath' => 'nome'],
            ['fieldPath' => 'matricula'],
            ['fieldPath' => 'email'],
            ['fieldPath' => 'curso'],
        ]],
    ]
]);

// Histórico: coleção separada
$historico = buscarColecao("$base/historico");

echo json_encode([
    'obras'       => array_values($obras),
    'emprestimos' => array_values($emprestimosAtivos),
    'reservas'    => array_values($reservas),
    'usuarios'    => array_values($usuariosRaw),
    'historico'   => array_values($historico),
]);
