<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logado']) || !isset($_SESSION['usuario_matricula'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autorizado.']);
    exit();
}

$projeto_id = "verbum-bd";
$matricula_aluno = $_SESSION['usuario_matricula'];

/* ══════════════════════════════════════════════
   FLUXO 1: MARCAR COMO LIDA (POST)
══════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID da notificação não informada.']);
        exit();
    }

    $urlPatch = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes/$id?updateMask.fieldPaths=lida";
    
    $body = json_encode([
        'fields' => [
            'lida' => ['booleanValue' => true]
        ]
    ]);

    $ch = curl_init($urlPatch);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    $info   = curl_getinfo($ch);
    curl_close($ch);

    if ($info['http_code'] === 200) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Notificação marcada como lida.']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar no banco de dados.']);
    }
    exit();
}

/* ══════════════════════════════════════════════
   FLUXO 2: LISTAR NOTIFICAÇÕES (GET - Seguro e Direto)
══════════════════════════════════════════════ */
// Buscamos a coleção pura adicionando um limite alto para cobrir o histórico do aluno
$url = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes?pageSize=100";

$json = @file_get_contents($url);
if (!$json) {
    echo json_encode(['sucesso' => true, 'total_nao_lidas' => 0, 'notificacoes' => []]);
    exit();
}

$dados = json_decode($json, true);
$documentos = $dados['documents'] ?? [];

$notificacoes_filtradas = [];
$total_nao_lidas = 0;

foreach ($documentos as $doc) {
    $fields = $doc['fields'] ?? [];
    $matricula_doc = $fields['matricula']['stringValue'] ?? '';

    // Filtramos aqui no backend do PHP de forma garantida
    if (trim($matricula_doc) === trim($matricula_aluno)) {
        $id = basename($doc['name']);
        $mensagem = $fields['mensagem']['stringValue'] ?? '';
        $data = $fields['data']['stringValue'] ?? '';
        $lida = $fields['lida']['booleanValue'] ?? false;

        if (!$lida) {
            $total_nao_lidas++;
        }

        $notificacoes_filtradas[] = [
            'id' => $id,
            'mensagem' => $mensagem,
            'data' => $data,
            'lida' => $lida
        ];
    }
}

// Ordena de forma estável: o próprio PHP compara as strings de data (Mais recentes primeiro)
usort($notificacoes_filtradas, function($a, $b) {
    return strcmp($b['data'], $a['data']);
});

echo json_encode([
    'sucesso' => true,
    'total_nao_lidas' => $total_nao_lidas,
    'notificacoes' => $notificacoes_filtradas
]);
?>