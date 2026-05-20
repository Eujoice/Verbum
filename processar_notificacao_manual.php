<?php
session_start();
require 'config.php';
date_default_timezone_set('America/Sao_Paulo');

header('Content-Type: application/json');

// Apenas administradores podem enviar notificações manuais
if (!isset($_SESSION['logado']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit();
}

$matricula  = trim($_POST['matricula']  ?? '');
$mensagem   = trim($_POST['mensagem']   ?? '');  // ← texto exato digitado pelo admin
$tipo_aviso = trim($_POST['tipo_aviso'] ?? 'devolucao');

if (empty($matricula) || empty($mensagem)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Matrícula e mensagem são obrigatórios.']);
    exit();
}

$projeto_id = "verbum-bd";

$dadosNotificacao = [
    'fields' => [
        'matricula' => ['stringValue' => $matricula],
        'mensagem'  => ['stringValue' => $mensagem],   // ← grava o que o admin digitou
        'tipo'      => ['stringValue' => $tipo_aviso],
        'data'      => ['stringValue' => date('Y-m-d H:i:s')],
        'lida'      => ['booleanValue' => false]
    ]
];

$url = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dadosNotificacao));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo json_encode(['sucesso' => true]);
} else {
    $erro = json_decode($resposta, true);
    $msg  = $erro['error']['message'] ?? 'Erro desconhecido ao gravar no banco.';
    echo json_encode(['sucesso' => false, 'mensagem' => $msg]);
}
?>