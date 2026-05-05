<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logado'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado.']);
    exit();
}

$projeto_id = "verbum-bd";
$acao       = $_POST['acao']     ?? '';
$livro_id   = trim($_POST['livro_id'] ?? '');
$nota       = floatval($_POST['nota']  ?? 0);
$matricula  = $_SESSION['usuario_matricula'];

// ── Funções auxiliares (mesmo padrão de processar_reserva.php) ───────────────

function fsGet($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $r = curl_exec($ch);
    curl_close($ch);
    return json_decode($r, true);
}

function fsPatch($url, $fields, $fieldPaths) {
    $mask    = implode('&', array_map(fn($f) => "updateMask.fieldPaths=$f", $fieldPaths));
    $fullUrl = $url . '?' . $mask;
    $ch = curl_init($fullUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => json_encode(['fields' => $fields]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $r    = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['http_code' => $info['http_code'], 'body' => json_decode($r, true)];
}

// ── Validações ────────────────────────────────────────────────────────────────

try {
    if ($acao !== 'avaliar') {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
        exit();
    }

    if (empty($livro_id)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID do livro inválido.']);
        exit();
    }

    if ($nota < 0.5 || $nota > 5.0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nota deve ser entre 0.5 e 5.0.']);
        exit();
    }

    $base = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents";

    // 1. Buscar dados atuais da obra
    $obraUrl = "$base/obras/$livro_id";
    $obraDoc = fsGet($obraUrl);

    if (!$obraDoc || isset($obraDoc['error'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Obra não encontrada.']);
        exit();
    }

    $f               = $obraDoc['fields'] ?? [];
    $mediaAtual      = floatval($f['avaliacao_media']['doubleValue'] ?? $f['avaliacao_media']['integerValue'] ?? 0);
    $totalAvaliacoes = intval($f['total_avaliacoes']['integerValue'] ?? 0);

    // 2. Verificar se o usuário já avaliou
    $avalUrl = "$base/obras/$livro_id/avaliacoes/$matricula";
    $avalDoc = fsGet($avalUrl);

    $jaAvaliou    = !isset($avalDoc['error']) && isset($avalDoc['fields']);
    $notaAnterior = 0;
    if ($jaAvaliou) {
        $notaAnterior = floatval(
            $avalDoc['fields']['nota']['doubleValue'] ??
            $avalDoc['fields']['nota']['integerValue'] ?? 0
        );
    }

    // 3. Calcular nova média
    if ($jaAvaliou && $totalAvaliacoes > 0) {
        $novaMedia = (($mediaAtual * $totalAvaliacoes) - $notaAnterior + $nota) / $totalAvaliacoes;
    } else {
        $totalAvaliacoes++;
        $novaMedia = (($mediaAtual * ($totalAvaliacoes - 1)) + $nota) / $totalAvaliacoes;
    }
    $novaMedia = round($novaMedia, 2);

    // 4. Salvar avaliação individual em obras/{id}/avaliacoes/{matricula}
    $resAval = fsPatch($avalUrl,
        [
            'nota'      => ['doubleValue'  => $nota],
            'matricula' => ['stringValue'  => $matricula],
            'data'      => ['stringValue'  => date('Y-m-d H:i:s')],
        ],
        ['nota', 'matricula', 'data']
    );

    if ($resAval['http_code'] !== 200) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar avaliação individual.']);
        exit();
    }

    // 5. Atualizar avaliacao_media e total_avaliacoes na obra
    $resObra = fsPatch($obraUrl,
        [
            'avaliacao_media'  => ['doubleValue'  => $novaMedia],
            'total_avaliacoes' => ['integerValue' => (string) $totalAvaliacoes],
        ],
        ['avaliacao_media', 'total_avaliacoes']
    );

    if ($resObra['http_code'] !== 200) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Avaliação salva, mas erro ao atualizar média.']);
        exit();
    }

    echo json_encode([
        'sucesso'          => true,
        'mensagem'         => 'Avaliação registrada com sucesso!',
        'nova_media'       => $novaMedia,
        'total_avaliacoes' => $totalAvaliacoes,
    ]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
?>