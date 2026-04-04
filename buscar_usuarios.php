<?php
require 'config.php';
$termo = isset($_GET['q']) ? mb_strtolower(trim($_GET['q'])) : '';

if (empty($termo)) {
    echo json_encode([]);
    exit;
}

$projetoID = "verbum-bd";
$url = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/usuarios";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resposta = curl_exec($ch);
curl_close($ch);

$dados = json_decode($resposta, true);
$resultados = [];

if (isset($dados['documents'])) {
    foreach ($dados['documents'] as $doc) {
        $fields = $doc['fields'];
        $nome = $fields['nome']['stringValue'] ?? '';
        $matricula = $fields['matricula']['stringValue'] ?? '';

        // Filtra se o termo bate com nome ou matrícula
        if (strpos(mb_strtolower($nome), $termo) !== false || strpos($matricula, $termo) !== false) {
            $resultados[] = [
                'nome' => $nome,
                'matricula' => $matricula
            ];
        }
    }
}

echo json_encode(array_slice($resultados, 0, 5)); // Retorna as 5 primeiras sugestões