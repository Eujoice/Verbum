<?php
/**
 * processar_renovacao.php
 * Renova um empréstimo ativo:
 *   - Estende data_devolucao_prevista na coleção 'emprestimos' e em 'obras'
 *   - Registra o campo 'renovado_em' para rastreabilidade
 *   - Envia notificação ao aluno
 *
 * POST params:
 *   emprestimo_id  — ID do documento em /emprestimos
 *   livro_id       — ID do documento em /obras  (campo 'id' da obra, ex: L01)
 *   nova_data_fim  — Nova data de devolução (YYYY-MM-DD)
 */

session_start();
require '../includes/config.php';
date_default_timezone_set('America/Sao_Paulo');

header('Content-Type: application/json');

$projetoID    = 'verbum-bd';
$baseFirestore = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";

/* ── Valida entrada ──────────────────────────────────────────────────────── */
$idEmprestimo = trim($_POST['emprestimo_id'] ?? '');
$idObra       = trim($_POST['livro_id']      ?? '');
$novaDataFim  = trim($_POST['nova_data_fim'] ?? '');

if (empty($idEmprestimo) || empty($idObra) || empty($novaDataFim)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Parâmetros obrigatórios ausentes.']);
    exit;
}

// Validação básica de data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $novaDataFim)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Formato de data inválido.']);
    exit;
}

// A nova data não pode ser anterior a hoje
$hoje = new DateTime();
$hoje->setTime(0, 0, 0);
$novaData = new DateTime($novaDataFim);
$novaData->setTime(0, 0, 0);

if ($novaData <= $hoje) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'A nova data de devolução deve ser posterior a hoje.']);
    exit;
}

/* ── cURL reutilizável ───────────────────────────────────────────────────── */
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);

/* ── 1. Busca o documento atual do empréstimo ────────────────────────────── */
$urlEmp = "{$baseFirestore}/emprestimos/{$idEmprestimo}";
curl_setopt($ch, CURLOPT_URL, $urlEmp);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$dadosEmp = json_decode(curl_exec($ch), true);

if (!isset($dadosEmp['fields'])) {
    curl_close($ch);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Empréstimo não encontrado no banco de dados.']);
    exit;
}

$statusEmp = $dadosEmp['fields']['status']['stringValue'] ?? '';
if ($statusEmp !== 'ativo') {
    curl_close($ch);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas empréstimos ativos podem ser renovados.']);
    exit;
}

$matricula  = $dadosEmp['fields']['usuario_id']['stringValue'] ?? '';
$tituloObra = $dadosEmp['fields']['titulo_obra']['stringValue'] ?? 'Livro';

/* ── 2. Atualiza a data no documento de empréstimo ───────────────────────── */
$urlEmpPatch = "{$urlEmp}?updateMask.fieldPaths=data_devolucao_prevista&updateMask.fieldPaths=renovado_em";
$payloadEmp  = json_encode([
    'fields' => [
        'data_devolucao_prevista' => ['stringValue' => $novaDataFim],
        'renovado_em'             => ['stringValue' => date('Y-m-d H:i:s')],
    ]
]);

curl_setopt($ch, CURLOPT_URL, $urlEmpPatch);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadEmp);
$resEmp = json_decode(curl_exec($ch), true);

if (isset($resEmp['error'])) {
    curl_close($ch);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar empréstimo: ' . $resEmp['error']['message']]);
    exit;
}

/* ── 3. Atualiza a data também no documento da obra ─────────────────────── */
// O campo obra_id no empréstimo armazena o ID lógico (ex: "L01"), igual ao campo 'id' em /obras
// mas o documento em /obras usa esse mesmo id como document ID
$urlObraPatch = "{$baseFirestore}/obras/{$idObra}?updateMask.fieldPaths=data_devolucao_prevista";
$payloadObra  = json_encode([
    'fields' => [
        'data_devolucao_prevista' => ['stringValue' => $novaDataFim],
    ]
]);

curl_setopt($ch, CURLOPT_URL, $urlObraPatch);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadObra);
curl_exec($ch); // ignora falha não-crítica aqui

/* ── 4. Notificação ao aluno ─────────────────────────────────────────────── */
if (!empty($matricula)) {
    $partesData      = explode('-', $novaDataFim);
    $dataFormatada   = (count($partesData) === 3)
        ? "{$partesData[2]}/{$partesData[1]}/{$partesData[0]}"
        : $novaDataFim;

    $mensagemNotif = "Renovação confirmada! O prazo do livro \"{$tituloObra}\" foi estendido até {$dataFormatada}.";

    $dadosNotif = json_encode([
        'fields' => [
            'matricula' => ['stringValue' => $matricula],
            'mensagem'  => ['stringValue' => $mensagemNotif],
            'data'      => ['stringValue' => date('Y-m-d H:i:s')],
            'lida'      => ['booleanValue' => false],
        ]
    ]);

    curl_setopt($ch, CURLOPT_URL, "{$baseFirestore}/notificacoes");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosNotif);
    curl_exec($ch);
}

curl_close($ch);

echo json_encode([
    'sucesso'      => true,
    'mensagem'     => 'Empréstimo renovado com sucesso!',
    'nova_data_fim' => $novaDataFim,
]);
