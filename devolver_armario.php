<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $num = $_POST['numero_armario'];
    
    $url = "https://firestore.googleapis.com/v1/projects/verbum-bd/databases/(default)/documents/armarios/{$num}?updateMask.fieldPaths=ocupado&updateMask.fieldPaths=usuario_matricula";

    $dados = [
        'fields' => [
            'ocupado' => ['booleanValue' => false],
            'usuario_matricula' => ['stringValue' => '']
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_exec($ch);
    curl_close($ch);

    header("Location: armario.php?sucesso=2");
    exit();
}