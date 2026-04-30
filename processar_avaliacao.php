<?php
session_start();
header('Content-Type: application/json');

// ── Autenticação ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['acao']) || $_POST['acao'] !== 'avaliar') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Requisição inválida.']);
    exit();
}

// ── Parâmetros ───────────────────────────────────────────────────────────────
$livro_id  = isset($_POST['livro_id']) ? trim($_POST['livro_id']) : '';
$nota      = isset($_POST['nota'])     ? floatval($_POST['nota']) : 0;
$matricula = $_SESSION['usuario_matricula'];

// Validações
if (empty($livro_id)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do livro inválido.']);
    exit();
}
if ($nota < 0.5 || $nota > 5.0 || fmod(round($nota * 2), 1) !== 0.0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nota inválida.']);
    exit();
}

// ── Configuração Firebase ─────────────────────────────────────────────────────
// Altere PROJECT_ID para o ID do seu projeto Firebase (ex: "verbum-bd-xxxxx")
define('PROJECT_ID', 'SEU_PROJECT_ID_AQUI');
define('FIRESTORE_BASE', 'https://firestore.googleapis.com/v1/projects/' . PROJECT_ID . '/databases/(default)/documents');

// Caminho do arquivo de credenciais da service account (JSON baixado do Firebase)
// Coloque o arquivo na raiz do projeto ou ajuste o caminho
define('CREDENTIALS_FILE', __DIR__ . '/firebase_credentials.json');

// ── Gerar token de acesso via Service Account ─────────────────────────────────
function getFirebaseToken() {
    $creds = json_decode(file_get_contents(CREDENTIALS_FILE), true);

    $now = time();
    $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'iss'   => $creds['client_email'],
        'sub'   => $creds['client_email'],
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/datastore',
    ]));

    $header  = str_replace(['+', '/', '='], ['-', '_', ''], $header);
    $payload = str_replace(['+', '/', '='], ['-', '_', ''], $payload);

    $signing_input = "$header.$payload";
    openssl_sign($signing_input, $signature, $creds['private_key'], 'SHA256');
    $signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $jwt = "$signing_input.$signature";

    // Troca o JWT pelo access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
    ]);
    $res   = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $res['access_token'] ?? null;
}

// ── Helper: requisição Firestore ──────────────────────────────────────────────
function firestoreRequest($method, $url, $token, $body = null) {
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// ── Lógica principal ──────────────────────────────────────────────────────────
$token = getFirebaseToken();
if (!$token) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de autenticação com Firebase.']);
    exit();
}

// 1. Buscar o documento atual da obra para calcular nova média
$obraUrl  = FIRESTORE_BASE . '/obras/' . $livro_id;
$obraDoc  = firestoreRequest('GET', $obraUrl, $token);

if (isset($obraDoc['error'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Obra não encontrada.']);
    exit();
}

// Lê avaliacao_media e total de avaliações atuais
$fields         = $obraDoc['fields'] ?? [];
$mediaAtual     = floatval($fields['avaliacao_media']['doubleValue'] ?? $fields['avaliacao_media']['integerValue'] ?? 0);
$totalAvaliacoes = intval($fields['total_avaliacoes']['integerValue'] ?? 0);

// 2. Verificar se o usuário já avaliou (subcoleção avaliacoes/{matricula})
$avaliacaoUrl = FIRESTORE_BASE . '/obras/' . $livro_id . '/avaliacoes/' . $matricula;
$avaliacaoDoc = firestoreRequest('GET', $avaliacaoUrl, $token);

$jaAvaliou    = !isset($avaliacaoDoc['error']);
$notaAnterior = 0;
if ($jaAvaliou) {
    $notaAnterior = floatval(
        $avaliacaoDoc['fields']['nota']['doubleValue'] ??
        $avaliacaoDoc['fields']['nota']['integerValue'] ?? 0
    );
}

// 3. Recalcular média
if ($jaAvaliou && $totalAvaliacoes > 0) {
    // Substitui a nota antiga pela nova
    $novaMedia = (($mediaAtual * $totalAvaliacoes) - $notaAnterior + $nota) / $totalAvaliacoes;
} else {
    // Nova avaliação
    $totalAvaliacoes++;
    $novaMedia = (($mediaAtual * ($totalAvaliacoes - 1)) + $nota) / $totalAvaliacoes;
}
$novaMedia = round($novaMedia, 2);

// 4. Salvar avaliação individual em obras/{livro_id}/avaliacoes/{matricula}
$avaliacaoBody = [
    'fields' => [
        'nota'       => ['doubleValue' => $nota],
        'matricula'  => ['stringValue' => $matricula],
        'data'       => ['stringValue' => date('Y-m-d H:i:s')],
    ]
];
firestoreRequest('PATCH', $avaliacaoUrl, $token, $avaliacaoBody);

// 5. Atualizar avaliacao_media e total_avaliacoes na obra
$updateUrl  = $obraUrl . '?updateMask.fieldPaths=avaliacao_media&updateMask.fieldPaths=total_avaliacoes';
$updateBody = [
    'fields' => [
        'avaliacao_media'  => ['doubleValue'  => $novaMedia],
        'total_avaliacoes' => ['integerValue' => (string) $totalAvaliacoes],
    ]
];
$updateResult = firestoreRequest('PATCH', $updateUrl, $token, $updateBody);

if (isset($updateResult['error'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar avaliação.']);
    exit();
}

echo json_encode([
    'sucesso'    => true,
    'mensagem'   => 'Avaliação registrada com sucesso!',
    'nova_media' => $novaMedia,
]);