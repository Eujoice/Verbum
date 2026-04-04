<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $num = $_POST['numero_armario'];
    $matricula = $_POST['matricula_usuario'];
    
    $url = "https://firestore.googleapis.com/v1/projects/verbum-bd/databases/(default)/documents/armarios/{$num}?updateMask.fieldPaths=ocupado&updateMask.fieldPaths=usuario_matricula";

    $dados = [
        'fields' => [
            'ocupado' => ['booleanValue' => true],
            'usuario_matricula' => ['stringValue' => $matricula]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code == 200) {
        header("Location: armario.php?sucesso=1");
        exit();
    } else {
        echo "Erro ao processar.";
    }
}