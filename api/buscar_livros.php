<?php
require '../includes/config.php';
header('Content-Type: application/json');

$termo = isset($_GET['q']) ? mb_strtolower(trim($_GET['q'])) : '';
if (empty($termo)) { echo json_encode([]); exit; }

$projetoID = "verbum-bd";
$base = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";

// Reutiliza uma única conexão cURL para as 2 leituras
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// 1. Obras — projeta apenas os campos necessários
curl_setopt($ch, CURLOPT_URL, "$base/obras?mask.fieldPaths=id&mask.fieldPaths=titulo&mask.fieldPaths=status&mask.fieldPaths=emprestado_por&mask.fieldPaths=data_devolucao_prevista&mask.fieldPaths=capa&mask.fieldPaths=avaliacao");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$dadosObras = json_decode(curl_exec($ch), true);

// 2. Somente empréstimos ATIVOS — evita baixar o histórico inteiro
$queryEmp = json_encode([
    'structuredQuery' => [
        'from' => [['collectionId' => 'emprestimos']],
        'where' => [
            'fieldFilter' => [
                'field' => ['fieldPath' => 'status'],
                'op'    => 'EQUAL',
                'value' => ['stringValue' => 'ativo'],
            ]
        ],
        'select' => ['fields' => [
            ['fieldPath' => 'obra_id'],
            ['fieldPath' => 'usuario_id'],
            ['fieldPath' => 'data_devolucao_prevista'],
        ]],
    ]
]);

curl_setopt($ch, CURLOPT_URL, "$base:runQuery");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $queryEmp);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resEmp = json_decode(curl_exec($ch), true);
curl_close($ch);

// Mapa: obra_id => { doc_id, matricula_usuario, data_prevista }
$mapaEmprestimos = [];
foreach ($resEmp as $item) {
    if (!isset($item['document'])) continue;
    $f     = $item['document']['fields'] ?? [];
    $idObra = $f['obra_id']['stringValue'] ?? '';
    $pathParts = explode('/', $item['document']['name']);
    $mapaEmprestimos[$idObra] = [
        'doc_id'            => end($pathParts),
        'matricula_usuario' => $f['usuario_id']['stringValue']             ?? '',
        'data_prevista'     => $f['data_devolucao_prevista']['stringValue'] ?? '',
    ];
}

$resultados = [];
foreach ($dadosObras['documents'] ?? [] as $doc) {
    $f      = $doc['fields'];
    $id     = $f['id']['stringValue']     ?? '';
    $titulo = $f['titulo']['stringValue'] ?? '';

    if (
        strpos(mb_strtolower($id), $termo) === false &&
        strpos(mb_strtolower($titulo), $termo) === false
    ) continue;

    $emp = $mapaEmprestimos[$id] ?? null;
    $resultados[] = [
        'id'                  => $id,
        'titulo'              => $titulo,
        'status'              => $f['status']['stringValue']              ?? 'Indisponível',
        'emprestado_por'      => $f['emprestado_por']['stringValue']      ?? '',
        'data_prevista'       => $emp['data_prevista'] ?? ($f['data_devolucao_prevista']['stringValue'] ?? ''),
        'capa'                => $f['capa']['stringValue']                ?? '',
        'avaliacao'           => $f['avaliacao']['stringValue']           ?? '',
        'id_emprestimo_atual' => $emp['doc_id']            ?? '',
        'matricula_usuario'   => $emp['matricula_usuario'] ?? '',
    ];
}

echo json_encode($resultados);
