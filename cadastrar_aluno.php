<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = trim($_POST['matricula']);
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['rua'] . ", " . $_POST['numero'] . " - " . $_POST['bairro'];
    $localidade = $_POST['cidade'] . "/" . ($_POST['uf'] ?? 'ES');

    $url = $baseUrl . $matricula; 
    
    $dados = [
        'fields' => [
            'matricula' => ['stringValue' => $matricula],
            'nome' => ['stringValue' => $nome],
            'cpf' => ['stringValue' => $cpf],
            'email' => ['stringValue' => $email],
            'telefone' => ['stringValue' => $telefone],
            'endereco' => ['stringValue' => $endereco],
            'localidade' => ['stringValue' => $localidade],
            'tipo' => ['stringValue' => 'aluno'],
            'primeiro_acesso' => ['booleanValue' => true], 
            'senha' => ['stringValue' => password_hash("12345678", PASSWORD_DEFAULT)]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $resposta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        header("Location: cadastro.php?sucesso=1");
    } else {
        header("Location: cadastro.php?erro=1");
    }
    exit();
}