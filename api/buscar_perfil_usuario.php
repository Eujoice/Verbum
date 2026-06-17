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
    // Zeramos o horário em ambas as datas para evitar comparações incorretas
    $hoje     = new DateTime(); $hoje->setTime(0, 0, 0);
    $prevista = DateTime::createFromFormat('Y-m-d', $dataFim);
    $diasUteis = 0;

    if ($prevista) {
        $prevista->setTime(0, 0, 0); // garante comparação só por data
        if ($hoje > $prevista) {
            // Conta dias úteis do dia seguinte à prevista até hoje (inclusive)
            $cursor = clone $prevista;
            $cursor->modify('+1 day');
            while ($cursor <= $hoje) {
                if ((int)$cursor->format('N') <= 5) $diasUteis++; // 1=seg … 5=sex
                $cursor->modify('+1 day');
            }
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

// 3. Histórico deste usuário — busca na coleção 'historico'
// (evita NOT_EQUAL + orderBy em campos diferentes, que exige índice composto no Firestore)
$historico = [];

$resHist = fsQuery($base, [
    'structuredQuery' => [
        'from' => [['collectionId' => 'historico']],
        'where' => [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'usuario_id'],
                'op'    => 'EQUAL',
                'value' => ['stringValue' => $matricula]
            ]
        ],
        'limit' => 20
    ]
]);

foreach ($resHist as $item) {
    if (!isset($item['document'])) continue;
    $f = $item['document']['fields'] ?? [];
    $pathParts = explode('/', $item['document']['name']);

    // Busca o título da obra se não estiver direto no registro de histórico
    $obraId = $f['obra_id']['stringValue'] ?? '';
    $titulo = $f['titulo_obra']['stringValue'] ?? '';
    if (empty($titulo) && !empty($obraId)) {
        $obraDoc = fsGet("$base/obras/$obraId");
        $titulo = $obraDoc['fields']['titulo']['stringValue'] ?? $obraId;
    }

    $historico[] = [
        'id'                  => end($pathParts),
        'obra_id'             => $obraId,
        'titulo'              => $titulo,
        'data_ini'            => $f['data_retirada']['stringValue']       ?? '',
        'data_fim'            => '',
        'data_devolucao_real' => $f['data_devolucao_real']['stringValue'] ?? '',
        'status'              => 'inativo',
    ];
}

// Ordena histórico do mais recente para o mais antigo
usort($historico, fn($a, $b) => strcmp($b['data_devolucao_real'], $a['data_devolucao_real']));

echo json_encode([
    'usuario'           => $usuario,
    'ativos'            => $ativos,
    'historico'         => $historico,
    'total_multa'       => $totalMulta,
    'total_emprestimos' => count($ativos) + count($historico),
]);