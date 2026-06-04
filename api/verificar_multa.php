<?php
/**
 * verificar_multa.php
 * Conta dias ÚTEIS de atraso (seg–sex), R$ 1,00/dia útil.
 */
session_start();
require '../includes/config.php';
header('Content-Type: application/json');

$matricula = $_SESSION['usuario_matricula'] ?? '';
if (empty($matricula)) { echo json_encode(['temMulta' => false]); exit; }

$projetoID = 'verbum-bd';
$base      = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";

// runQuery: somente empréstimos ATIVOS deste usuário
$query = json_encode([
    'structuredQuery' => [
        'from' => [['collectionId' => 'emprestimos']],
        'where' => [
            'compositeFilter' => [
                'op' => 'AND',
                'filters' => [
                    ['fieldFilter' => ['field' => ['fieldPath' => 'usuario_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $matricula]]],
                    ['fieldFilter' => ['field' => ['fieldPath' => 'status'],     'op' => 'EQUAL', 'value' => ['stringValue' => 'ativo']]],
                ]
            ]
        ],
        'select' => ['fields' => [
            ['fieldPath' => 'obra_id'],
            ['fieldPath' => 'titulo_obra'],
            ['fieldPath' => 'data_devolucao_prevista'],
        ]],
    ]
]);

$ch = curl_init("$base:runQuery");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = json_decode(curl_exec($ch), true) ?? [];
curl_close($ch);

$hoje = new DateTime(); $hoje->setTime(0,0,0);
$total    = 0.0;
$detalhes = [];

foreach ($res as $item) {
    if (!isset($item['document'])) continue;
    $f          = $item['document']['fields'] ?? [];
    $dataFimStr = $f['data_devolucao_prevista']['stringValue'] ?? '';
    if (empty($dataFimStr)) continue;

    $dataFim = DateTime::createFromFormat('Y-m-d', $dataFimStr);
    if (!$dataFim) continue;
    $dataFim->setTime(0,0,0);

    if ($hoje <= $dataFim) continue; // sem atraso

    // Conta dias úteis
    $diasUteis = 0;
    $cursor = clone $dataFim;
    $cursor->modify('+1 day');
    while ($cursor <= $hoje) {
        if ((int)$cursor->format('N') < 6) $diasUteis++;
        $cursor->modify('+1 day');
    }

    if ($diasUteis === 0) continue;

    $valorMulta = $diasUteis * 1.00;
    $total += $valorMulta;

    $pathParts    = explode('/', $item['document']['name']);
    $emprestimoId = end($pathParts);

    $detalhes[] = [
        'emprestimo_id' => $emprestimoId,
        'titulo'        => $f['titulo_obra']['stringValue'] ?? 'Livro',
        'obra_id'       => $f['obra_id']['stringValue']     ?? '',
        'dias_atraso'   => $diasUteis,
        'valor'         => $valorMulta,
        'data_prevista' => $dataFimStr,
    ];
}

if (empty($detalhes)) {
    echo json_encode(['temMulta' => false]);
} else {
    echo json_encode(['temMulta' => true, 'total' => round($total, 2), 'detalhes' => $detalhes]);
}
