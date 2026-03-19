<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = $_POST['matricula'];
    $senhaDigitada = $_POST['senha'];

    $dadosFirebase = buscarUsuario($matricula);

    // Se o usuário existir, o Firebase retorna os campos. Se não, retorna um erro.
    if (isset($dadosFirebase['fields'])) {
        // O Firestore retorna os dados num formato específico: ['fields']['nome']['stringValue']
        $senhaNoBanco = $dadosFirebase['fields']['senha']['stringValue'];
        $nomeNoBanco = $dadosFirebase['fields']['nome']['stringValue'];

        // Verificação por password_verify por causa do hash (criptografia)
        if (password_verify($senhaDigitada, $senhaNoBanco)){
            $_SESSION['logado'] = true;
            $_SESSION['usuario_nome'] = $nomeNoBanco;
            $_SESSION['usuario_matricula'] = $matricula; 
            
            header("Location: acervo.php");
            exit();
        } else {
            echo "<script>alert('Senha incorreta!'); window.location.href='index.html';</script>";
        }
    } else {
        echo "<script>alert('Usuário não encontrado!'); window.location.href='index.html';</script>";
    }
}
?>