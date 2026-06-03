<?php
/**
 * verificar_multa.php
 * Verifica se o usuário logado possui multa pendente por atraso.
 *
 * Lógica: busca empréstimos ATIVOS com data_devolucao_prevista < hoje
 *         e calcula R$ 1,00 por dia de atraso por empréstimo.
 *
 * Resposta JSON:
 *   { temMulta: false }
 *   { temMulta: true, total: 12.00, detalhes: [ { emprestimo_id, titulo, obra_id, dias_atraso, valor, data_prevista } ] }
 */

session_start();
require '../includes/config.php';

header('Content-Type: application/json');

$matricula = $_SESSION['usuario_matricula'] ?? '';

if (empty($matricula)) {
    echo json_encode(['temMulta' => false]);
    exit;
}

$projeto_id = 'verbum-bd';
$url        = "https://firestore.googleapis.com/v1/projects/{$projeto_id}/databases/(default)/documents/emprestimos";
$json       = @file_get_contents($url);

if (!$json) {
    echo json_encode(['temMulta' => false]);
    exit;
}

$dados      = json_decode($json, true);
$documentos = $dados['documents'] ?? [];

$hoje = new DateTime();
$hoje->setTime(0, 0, 0);

$total    = 0.0;
$detalhes = [];

foreach ($documentos as $doc) {
    $f = $doc['fields'] ?? [];

    $statusDoc    = $f['status']['stringValue']    ?? '';
    $matriculaDoc = $f['usuario_id']['stringValue'] ?? '';

    // Somente empréstimos ATIVOS do usuário logado
    if ($statusDoc !== 'ativo' || $matriculaDoc !== $matricula) {
        continue;
    }

    $dataFimStr = $f['data_devolucao_prevista']['stringValue'] ?? '';
    if (empty($dataFimStr)) continue;

    $dataFim = new DateTime($dataFimStr);
    $dataFim->setTime(0, 0, 0);

    $diff     = $hoje->diff($dataFim);
    $diffDias = (int)$diff->format('%r%a'); // negativo = atrasado

    if ($diffDias < 0) {
        $diasAtraso = abs($diffDias);
        $valorMulta = $diasAtraso * 1.00; // R$ 1,00 / dia
        $total     += $valorMulta;

        $pathParts    = explode('/', $doc['name']);
        $emprestimoId = end($pathParts);

        $detalhes[] = [
            'emprestimo_id' => $emprestimoId,
            'titulo'        => $f['titulo_obra']['stringValue'] ?? 'Livro',
            'obra_id'       => $f['obra_id']['stringValue']    ?? '',
            'dias_atraso'   => $diasAtraso,
            'valor'         => $valorMulta,
            'data_prevista' => $dataFimStr,
        ];
    }
}

if (empty($detalhes)) {
    echo json_encode(['temMulta' => false]);
} else {
    echo json_encode([
        'temMulta' => true,
        'total'    => round($total, 2),
        'detalhes' => $detalhes,
    ]);
}
?>
