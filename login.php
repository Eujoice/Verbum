<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = $_POST['matricula'];
    $senhaDigitada = $_POST['senha'];

    $dadosFirebase = buscarUsuario($matricula);

    if (isset($dadosFirebase['fields'])) {
        $f = $dadosFirebase['fields']; // Atalho para facilitar
        $senhaNoBanco = $f['senha']['stringValue'];
        
        if (password_verify($senhaDigitada, $senhaNoBanco)){
            $_SESSION['logado'] = true;
            $_SESSION['usuario_matricula'] = $matricula; 
            $_SESSION['usuario_tipo'] = $f['tipo']['stringValue'] ?? 'aluno'; 
            
            // --- Puxando os dados que o ADM cadastrou ---
            $_SESSION['usuario_nome'] = $f['nome']['stringValue'] ?? '';
            $_SESSION['usuario_email'] = $f['email']['stringValue'] ?? '';
            $_SESSION['usuario_telefone'] = $f['telefone']['stringValue'] ?? '';
            $_SESSION['usuario_endereco'] = $f['endereco']['stringValue'] ?? '';
            // ---------------------------------------------------------

            // --- Verificar Primeiro Acesso ---
            $primeiroAcesso = $f['primeiro_acesso']['booleanValue'] ?? false;

            if ($primeiroAcesso === true) {
                header("Location: trocar_senha.php");
                exit();
            }
            // ----------------------------------------------

            if ($_SESSION['usuario_tipo'] === 'administrador') {
                header("Location: consulta.php");
            } else {
                header("Location: acervo.php");
            }
            exit();

            if ($_SESSION['usuario_tipo'] === 'administrador') {
                header("Location: consulta.php");
            } else {
                header("Location: acervo.php");
            }
            exit();
        } else {
            header("Location: index.php?erro=senha");
            exit();
        }
    } else {
        header("Location: index.php?erro=usuario");
        exit();
    }
}
?>