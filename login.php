<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = $_POST['matricula'];
    $senhaDigitada = $_POST['senha'];

    $dadosFirebase = buscarUsuario($matricula);

    if (isset($dadosFirebase['fields'])) {
        $senhaNoBanco = $dadosFirebase['fields']['senha']['stringValue'];
        $nomeNoBanco = $dadosFirebase['fields']['nome']['stringValue'];
        
        $tipoUsuario = isset($dadosFirebase['fields']['tipo']['stringValue']) ? $dadosFirebase['fields']['tipo']['stringValue'] : 'aluno';

        if (password_verify($senhaDigitada, $senhaNoBanco)){
            $_SESSION['logado'] = true;
            $_SESSION['usuario_nome'] = $nomeNoBanco;
            $_SESSION['usuario_matricula'] = $matricula; 
            $_SESSION['usuario_tipo'] = $tipoUsuario; 

            // Tipo
            // -> aluno: acervo
            // -> administrador -> consulta.php
            // Se não existir o campo no banco, ele assume aluno por padrão
            if ($tipoUsuario === 'administrador') {
                header("Location: consulta.php"); // pag inical de adm
            } else {
                header("Location: acervo.php"); // Pag inical de aluno
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