<?php
$projetoID = "verbum-bd"; 

$baseUrl = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/usuarios/";

function buscarUsuario($matricula) {
    global $baseUrl;
    // Remove espaços extras da matrícula
    $url = $baseUrl . trim($matricula);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $resposta = curl_exec($ch);
    curl_close($ch);

    return json_decode($resposta, true);
}
?>