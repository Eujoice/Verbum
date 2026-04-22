<?php
session_start();
require 'config.php';

$tokenURL = $_GET['token'] ?? '';
$matricula = $_GET['mat'] ?? '';

$dados = buscarUsuario($matricula);
$tokenNoBanco = $dados['fields']['token_recuperacao']['stringValue'] ?? '';
$expiracao = $dados['fields']['token_expiracao']['stringValue'] ?? '';

// VALIDAR O TOKEN
if ($tokenURL !== '' && $tokenURL === $tokenNoBanco && strtotime($expiracao) > time()) {
    // Se o token está certo, criamos a sessão que o processar_troca.php exige
    $_SESSION['usuario_matricula'] = $matricula;
    $_SESSION['autorizado_pelo_token'] = true; 

    //  manda para a página de trocar a senha (reutilizando)
    header("Location: trocar_senha.php");
    exit();
} else {
    echo "<h1>Link inválido ou expirado!</h1>";
}