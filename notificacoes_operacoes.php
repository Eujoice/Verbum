<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logado'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$projeto_id = "verbum-bd";
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    // 1. LISTAR NOTIFICAÇÕES (Usado pelo Aluno)
    if ($acao === 'listar_aluno') {
        $matricula = $_SESSION['usuario_matricula'] ?? '';
        $url = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes";
        
        $json = @file_get_contents($url);
        $dados = $json ? json_decode($json, true) : [];
        $documentos = $dados['documents'] ?? [];

        $filtradas = [];
        foreach ($documentos as $doc) {
            $f = $doc['fields'] ?? [];
            $mat = $f['matricula']['stringValue'] ?? '';
            $lida = $f['lida']['booleanValue'] ?? false;

            if ($mat === $matricula) {
                $filtradas[] = [
                    'id' => basename($doc['name']),
                    'mensagem' => $f['mensagem']['stringValue'] ?? '',
                    'data' => $f['data']['stringValue'] ?? '',
                    'lida' => $lida
                ];
            }
        }
        // Ordena pela data mais recente
        usort($filtradas, fn($a, $b) => strcmp($b['data'], $a['data']));
        echo json_encode($filtradas);
        exit();
    }

    // 2. MARCAR COMO LIDA (Usado pelo Aluno ao abrir o sininho ou clicar)
    if ($acao === 'marcar_lida') {
        $id = $_POST['id'] ?? '';
        if (empty($id)) throw new Exception("ID inválido.");

        $url = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes/$id?updateMask.fieldPaths=lida";
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

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
?>