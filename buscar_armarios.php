<?php
require 'config.php';
$url = "https://firestore.googleapis.com/v1/projects/verbum-bd/databases/(default)/documents/armarios";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

$dados = json_decode($res, true);
$lista = [];

if (isset($dados['documents'])) {
    foreach ($dados['documents'] as $doc) {
        $fields = $doc['fields'];
        $lista[] = [
            'id' => basename($doc['name']),
            'ocupado' => $fields['ocupado']['booleanValue'] ?? false,
            'usuario' => $fields['usuario_matricula']['stringValue'] ?? ''
        ];
    }
}
header('Content-Type: application/json');
echo json_encode($lista);