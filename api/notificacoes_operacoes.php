<?php
/**
 * notificacoes_operacoes.php
 * REMOVIDO: ação 'listar_aluno' que baixava a coleção inteira e filtrava no PHP.
 * Use listar_notificacoes.php (já usa runQuery) para listar notificações.
 * Mantido: ação 'marcar_lida'.
 */
session_start();
require '../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logado'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$projeto_id = "verbum-bd";
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    if ($acao === 'marcar_lida') {
        $id = $_POST['id'] ?? '';
        if (empty($id)) throw new Exception("ID inválido.");

        $url  = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes/$id?updateMask.fieldPaths=lida";
        $body = json_encode(['fields' => ['lida' => ['booleanValue' => true]]]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);

        echo json_encode(['sucesso' => true]);
        exit();
    }

    // Ação 'listar_aluno' foi migrada para listar_notificacoes.php
    if ($acao === 'listar_aluno') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Use listar_notificacoes.php para listar notificações.']);
        exit();
    }

    echo json_encode(['sucesso' => false, 'mensagem' => "Ação '$acao' não reconhecida."]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
