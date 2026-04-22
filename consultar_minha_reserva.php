<?php
session_start();
require 'config.php'; 

header('Content-Type: application/json');

$livro_id = $_GET['livro_id'] ?? '';
$matricula = $_SESSION['usuario_matricula'] ?? '';
$projeto_id = "verbum-bd";

if (!$livro_id || !$matricula) {
    echo json_encode(['jaReservado' => false]);
    exit();
}

// 1. Buscar todas as reservas para este livro específico
$url = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/reservas";
$json = @file_get_contents($url);
$dados = json_decode($json, true);
$documentos = $dados['documents'] ?? [];

$filaDoLivro = [];
$minhaReserva = null;

// 2. Filtrar apenas as reservas deste livro que estão na Fila
foreach ($documentos as $doc) {
    $f = $doc['fields'];
    $idDoLivroNoDoc = $f['obra_id']['stringValue'] ?? '';
    $tipoDoc = $f['tipo']['stringValue'] ?? '';

    if ($idDoLivroNoDoc === $livro_id && $tipoDoc === 'Fila') {
        $filaDoLivro[] = [
            'matricula' => $f['matricula']['stringValue'] ?? '',
            'data' => $f['data_reserva']['stringValue'] ?? '9999-12-31' // Fallback para o fim da fila
        ];
    }
    
    // Verifica se o usuário tem uma reserva "Direta" (que não entra na conta da fila)
    if ($idDoLivroNoDoc === $livro_id && $f['matricula']['stringValue'] === $matricula && $tipoDoc === 'Direta') {
        echo json_encode(['jaReservado' => true, 'tipo' => 'Direta', 'posicao' => 0]);
        exit();
    }
}

// 3. Ordenar a fila por data (quem reservou primeiro fica no topo)
usort($filaDoLivro, function($a, $b) {
    return strcmp($a['data'], $b['data']);
});

// 4. Encontrar a posição da matrícula do usuário na fila ordenada
$posicaoReal = 0;
$encontradoNaFila = false;

foreach ($filaDoLivro as $index => $reserva) {
    if ($reserva['matricula'] === $matricula) {
        $posicaoReal = $index + 1; // +1 porque array começa em 0
        $encontradoNaFila = true;
        break;
    }
}

echo json_encode([
    'jaReservado' => $encontradoNaFila,
    'tipo' => 'Fila',
    'posicao' => $posicaoReal
]);