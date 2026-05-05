<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logado'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$livro_id  = trim($_GET['livro_id'] ?? '');
$matricula = $_SESSION['usuario_matricula'];
$projeto_id = "verbum-bd";

if (empty($livro_id)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit();
}

$base = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents";

function fsGetAval($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false]);
    $r = curl_exec($ch);
    curl_close($ch);
    return json_decode($r, true);
}

// Nota do usuário
$notaUsuario = null;
$avalDoc = fsGetAval("$base/obras/$livro_id/avaliacoes/$matricula");
if (!isset($avalDoc['error']) && isset($avalDoc['fields']['nota'])) {
    $notaUsuario = floatval(
        $avalDoc['fields']['nota']['doubleValue'] ??
        $avalDoc['fields']['nota']['integerValue'] ?? 0
    );
}

// Média geral e total
$media = 0;
$total = 0;
$obraDoc = fsGetAval("$base/obras/$livro_id");
if (!isset($obraDoc['error']) && isset($obraDoc['fields'])) {
    $f     = $obraDoc['fields'];
    $media = floatval($f['avaliacao_media']['doubleValue'] ?? $f['avaliacao_media']['integerValue'] ?? 0);
    $total = intval($f['total_avaliacoes']['integerValue'] ?? 0);
}

echo json_encode([
    'sucesso'          => true,
    'nota_usuario'     => $notaUsuario,
    'media'            => $media,
    'total_avaliacoes' => $total,
]);
?>