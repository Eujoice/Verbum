<?php
session_start();
require '../includes/config.php';

$tokenURL = $_GET['token'] ?? '';
$matricula = $_GET['mat'] ?? '';

if (empty($tokenURL) || empty($matricula)) {
    header("Location: ../pages/esqueceu_senha.php?erro=link_invalido");
    exit();
}

$dados = buscarUsuario($matricula);
$tokenNoBanco = $dados['fields']['token_recuperacao']['stringValue'] ?? '';
$expiracao    = $dados['fields']['token_expiracao']['stringValue'] ?? '';

if ($tokenURL === $tokenNoBanco && strtotime($expiracao) > time()) {
    $_SESSION['usuario_matricula']    = $matricula;
    $_SESSION['autorizado_pelo_token'] = true;
    header("Location: ../pages/trocar_senha.php");
} else {
    header("Location: ../pages/esqueceu_senha.php?erro=link_invalido");
}
exit();