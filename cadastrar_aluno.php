<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = trim($_POST['matricula']);
    $nome = $_POST['nome'];
    $senha = $_POST['senha'];

    $url = $baseUrl . $matricula;
    
    $dados = [
        'fields' => [
            'nome' => ['stringValue' => $nome],
            'matricula' => ['stringValue' => $matricula],
            'senha' => ['stringValue' => password_hash($senha, PASSWORD_DEFAULT)], 
            'tipo' => ['stringValue' => 'aluno']
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
        echo "<script>alert('Aluno cadastrado com sucesso!'); window.location.href='perfil.php';</script>";
    } else {
        echo "<h3>Erro ao cadastrar no Firebase</h3>";
        echo "Código HTTP: " . $httpCode . "<br>";
        echo "Resposta do Google: " . $resposta;
        exit;
    }
}
?>