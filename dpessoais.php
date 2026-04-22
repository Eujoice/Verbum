<?php
session_start();
require 'config.php';

if (!isset($_SESSION['usuario_matricula'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_dp'])) {
    $matricula = trim($_SESSION['usuario_matricula']);

    $nome     = $_POST['nome'];
    $email    = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];

    $queryParams = http_build_query([
        'updateMask.fieldPaths' => ['nome', 'email', 'telefone', 'endereco']
    ]);
    $queryParams = preg_replace('/%5B\d+%5D/', '', $queryParams);
    $url = $baseUrl . $matricula . "?" . $queryParams;

    $dados = [
        'fields' => [
            'nome'     => ['stringValue' => $nome],
            'email'    => ['stringValue' => $email],
            'telefone' => ['stringValue' => $telefone],
            'endereco' => ['stringValue' => $endereco]
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
        $_SESSION['usuario_nome']     = $nome;
        $_SESSION['usuario_email']    = $email;
        $_SESSION['usuario_telefone'] = $telefone;
        $_SESSION['usuario_endereco'] = $endereco;
        $_SESSION['sucesso_dp']       = true;
    }

    // Redireciona de volta para a página de origem
    $redirect = $_POST['pagina_origem'] ?? 'acervo.php';
    header("Location: " . $redirect);
    exit();
}
?>