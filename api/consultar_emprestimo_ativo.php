<?php
/**
 * consultar_emprestimo_ativo.php
 * Verifica se o usuário da sessão tem um empréstimo ATIVO para o livro informado.
 *
 * GET params:
 *   livro_id — ID lógico da obra (ex: "L01")
 *
 * Resposta JSON:
 *   { temEmprestimo: false }
 *   { temEmprestimo: true, emprestimo_id: "...", data_ini: "YYYY-MM-DD", data_fim: "YYYY-MM-DD", dias_restantes: N }
 */

session_start();
require '../includes/config.php';

header('Content-Type: application/json');

$livroId   = trim($_GET['livro_id'] ?? '');
$matricula = $_SESSION['usuario_matricula'] ?? '';
$projetoID = 'verbum-bd';

if (empty($livroId) || empty($matricula)) {
    echo json_encode(['temEmprestimo' => false]);
    exit;
}

// Busca todos os empréstimos ativos
$url  = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/emprestimos";
$json = @file_get_contents($url);

if (!$json) {
    echo json_encode(['temEmprestimo' => false]);
    exit;
}

$dados      = json_decode($json, true);
$documentos = $dados['documents'] ?? [];

$hoje = new DateTime();
$hoje->setTime(0, 0, 0);

foreach ($documentos as $doc) {
    $f = $doc['fields'] ?? [];

    $statusDoc    = $f['status']['stringValue']    ?? '';
    $matriculaDoc = $f['usuario_id']['stringValue'] ?? '';
    $obraDoc      = $f['obra_id']['stringValue']    ?? '';

    // Filtra pelo usuário logado, pelo livro e por empréstimo ativo
    if ($statusDoc !== 'ativo' || $matriculaDoc !== $matricula || $obraDoc !== $livroId) {
        continue;
    }

    // Encontrou o empréstimo — extrai datas
    $dataFimStr = $f['data_devolucao_prevista']['stringValue'] ?? '';
    $dataIniStr = $f['data_emprestimo']['stringValue']         ?? '';

    $pathParts    = explode('/', $doc['name']);
    $emprestimoId = end($pathParts);

    $diasRestantes = null;
    if (!empty($dataFimStr)) {
        $dataFim = new DateTime($dataFimStr);
        $dataFim->setTime(0, 0, 0);
        $intervalo     = $hoje->diff($dataFim);
        $diasRestantes = (int)$intervalo->format('%r%a'); // negativo = atrasado
    }

    echo json_encode([
        'temEmprestimo' => true,
        'emprestimo_id' => $emprestimoId,
        'data_ini'      => $dataIniStr,
        'data_fim'      => $dataFimStr,
        'dias_restantes' => $diasRestantes,
    ]);
    exit;
}

echo json_encode(['temEmprestimo' => false]);
