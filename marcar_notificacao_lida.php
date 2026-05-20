<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logado']) || empty($_POST['id'])) {
    echo json_encode(['sucesso' => false]);
    exit();
}

$projeto_id = "verbum-bd";
$id = $_POST['id'];

$url = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes/$id?updateMask.fieldPaths=lida";

$body = json_encode([
    'fields' => [
        'lida' => ['booleanValue' => true]
    ]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
curl_close($ch);

echo json_encode(['sucesso' => true]);
?>