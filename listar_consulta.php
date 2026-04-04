<?php
require 'config.php';
header('Content-Type: application/json');

$baseFirestore = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";

function buscarColecao($colecao) {
    global $baseFirestore;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseFirestore . "/" . $colecao);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resposta = curl_exec($ch);
    curl_close($ch);
    return json_decode($resposta, true);
}

$dadosObras = buscarColecao("obras");
$dadosEmprestimos = buscarColecao("emprestimos");
$dadosUsuarios = buscarColecao("usuarios"); 
$dadosHistorico = buscarColecao("historico");

echo json_encode([
    'obras' => $dadosObras['documents'] ?? [],
    'emprestimos' => $dadosEmprestimos['documents'] ?? [],
    'reservas' => $dadosReservas['documents'] ?? [],
    'usuarios' => $dadosUsuarios['documents'] ?? [],
    'historico' => $dadosHistorico['documents'] ?? [] 
]);
?>