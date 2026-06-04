<?php
require '../includes/config.php';
header('Content-Type: application/json');

$matricula = isset($_GET['matricula']) ? trim($_GET['matricula']) : '';
if (empty($matricula)) { echo json_encode(['erro' => 'Matrícula não informada']); exit; }

$projetoID = "verbum-bd";
$base = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";

function fsQuery($base, $query) {
    $ch = curl_init("$base:runQuery");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

function fsGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

// 1. Buscar SOMENTE o usuário pela matrícula (1 documento, não a coleção inteira)
$userDoc = fsGet("$base/usuarios/$matricula");
if (isset($userDoc['error']) || !isset($userDoc['fields'])) {
    echo json_encode(['erro' => 'Usuário não encontrado']);
    exit;
}
$uf = $userDoc['fields'];
$usuario = [
    'nome'      => $uf['nome']['stringValue']      ?? '',
    'matricula' => $uf['matricula']['stringValue'] ?? $matricula,
    'email'     => $uf['email']['stringValue']     ?? '',
    'curso'     => $uf['curso']['stringValue']     ?? '',
    'tipo'      => $uf['tipo']['stringValue']      ?? 'aluno',
];

// 2. Empréstimos ATIVOS deste usuário — query filtrada
$ativos = [];
$totalMulta = 0;

$resAtivos = fsQuery($base, [
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
        ]
    ]
]);

foreach ($resAtivos as $item) {
    if (!isset($item['document'])) continue;
    $f = $item['document']['fields'] ?? [];
    $pathParts    = explode('/', $item['document']['name']);
    $emprestimoId = end($pathParts);
    $obraId   = $f['obra_id']['stringValue']                 ?? '';
    $dataFim  = $f['data_devolucao_prevista']['stringValue'] ?? '';
    $titulo   = $f['titulo_obra']['stringValue']             ?? $obraId;

    // Calcular dias úteis de atraso
    $hoje    = new DateTime(); $hoje->setTime(0,0,0);
    $prevista = DateTime::createFromFormat('Y-m-d', $dataFim);
    $diasUteis = 0;

    if ($prevista && $hoje > $prevista) {
        $cursor = clone $prevista;
        $cursor->modify('+1 day');
        while ($cursor <= $hoje) {
            if ((int)$cursor->format('N') < 6) $diasUteis++;
            $cursor->modify('+1 day');
        }
    }

    $totalMulta += $diasUteis;
    $ativos[] = [
        'id'        => $emprestimoId,
        'obra_id'   => $obraId,
        'titulo'    => $titulo,
        'data_ini'  => $f['data_emprestimo']['stringValue'] ?? '',
        'data_fim'  => $dataFim,
        'status'    => 'ativo',
        'dias_atraso' => $diasUteis,
        'multa'       => $diasUteis,
    ];
}

// 3. Histórico deste usuário — query filtrada (sem status ativo)
$historico = [];

$resHist = fsQuery($base, [
    'structuredQuery' => [
        'from' => [['collectionId' => 'emprestimos']],
        'where' => [
            'compositeFilter' => [
                'op' => 'AND',
                'filters' => [
                    ['fieldFilter' => ['field' => ['fieldPath' => 'usuario_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $matricula]]],
                    ['fieldFilter' => ['field' => ['fieldPath' => 'status'],     'op' => 'NOT_EQUAL', 'value' => ['stringValue' => 'ativo']]],
                ]
            ]
        ],
        'orderBy' => [['field' => ['fieldPath' => 'data_emprestimo'], 'direction' => 'DESCENDING']],
        'limit' => 20
    ]
]);

foreach ($resHist as $item) {
    if (!isset($item['document'])) continue;
    $f = $item['document']['fields'] ?? [];
    $pathParts = explode('/', $item['document']['name']);
    $historico[] = [
        'id'                  => end($pathParts),
        'obra_id'             => $f['obra_id']['stringValue']                 ?? '',
        'titulo'              => $f['titulo_obra']['stringValue']              ?? '',
        'data_ini'            => $f['data_emprestimo']['stringValue']          ?? '',
        'data_fim'            => $f['data_devolucao_prevista']['stringValue']  ?? '',
        'data_devolucao_real' => $f['data_devolucao_real']['stringValue']      ?? '',
        'status'              => $f['status']['stringValue']                   ?? '',
    ];
}

echo json_encode([
    'usuario'           => $usuario,
    'ativos'            => $ativos,
    'historico'         => $historico,
    'total_multa'       => $totalMulta,
    'total_emprestimos' => count($ativos) + count($historico),
]);
