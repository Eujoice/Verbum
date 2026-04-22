<?php
session_start();
require 'config.php';

// Verifica se o usuário está logado na sessão ou em processo de recuperação
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_matricula'])) {
    $matricula = $_SESSION['usuario_matricula'];
    $novaSenha = $_POST['nova_senha'];
    $confirmar = $_POST['confirmar_senha'];

    // 1. Validação de Força da Senha (Regex)
    // Exige: 8+ caracteres, 1 maiúscula, 1 número e 1 caractere especial
    $regex = "/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*(),.?\":{}|<>]).{8,}$/";
    
    if (!preg_match($regex, $novaSenha)) {
        header("Location: trocar_senha.php?erro=senha_fraca");
        exit();
    }

    // 2. Validação de Confirmação
    if ($novaSenha !== $confirmar) {
        header("Location: trocar_senha.php?erro=confirmacao");
        exit();
    }

    // 3. Preparação para o Firestore
    // Adicionamos os campos de token no updateMask para limpá-los após a troca bem-sucedida
    $url = $baseUrl . $matricula . "?updateMask.fieldPaths=senha" .
           "&updateMask.fieldPaths=primeiro_acesso" .
           "&updateMask.fieldPaths=token_recuperacao" .
           "&updateMask.fieldPaths=token_expiracao"; 

    $dados = [
        'fields' => [
            'senha' => ['stringValue' => password_hash($novaSenha, PASSWORD_DEFAULT)],
            'primeiro_acesso' => ['booleanValue' => false],
            // Limpa os tokens para que o link de recuperação não funcione mais
            'token_recuperacao' => ['stringValue' => ""],
            'token_expiracao' => ['stringValue' => ""]
        ]
    ];

    // 4. Execução do PATCH via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Importante para ambientes locais
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $resposta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 5. Tratamento do Resultado
    if ($httpCode >= 200 && $httpCode < 300) {
        // Limpa flag temporária de recuperação se existir
        unset($_SESSION['autorizado_troca']);
        
        // Redireciona conforme o sucesso
        header("Location: acervo.php?sucesso=senha_atualizada");
    } else {
        header("Location: trocar_senha.php?erro=servidor");
    }
    exit();
} else {
    // Se tentar acessar o script sem sessão válida
    header("Location: index.php");
    exit();
}