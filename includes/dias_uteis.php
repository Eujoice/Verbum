<?php
/**
 * Helpers de dias úteis — Verbum
 * Inclua onde precisar: require_once '../includes/dias_uteis.php';
 */

/**
 * Adiciona $n dias úteis (seg–sex) a partir de uma data.
 * @param string $dataInicio  Data no formato 'Y-m-d'
 * @param int    $n           Quantidade de dias úteis a adicionar
 * @return string             Data resultante no formato 'Y-m-d'
 */
function adicionarDiasUteis(string $dataInicio, int $n): string {
    $data = new DateTime($dataInicio);
    $adicionados = 0;
    while ($adicionados < $n) {
        $data->modify('+1 day');
        $dow = (int)$data->format('N'); // 1=seg … 7=dom
        if ($dow <= 5) $adicionados++; // seg–sex
    }
    return $data->format('Y-m-d');
}

/**
 * Conta dias úteis em atraso a partir da data prevista até hoje.
 * Retorna 0 se ainda dentro do prazo.
 * @param string $dataPrevista  'Y-m-d'
 * @return int
 */
function contarDiasUteisAtraso(string $dataPrevista): int {
    $hoje     = new DateTime();
    $hoje->setTime(0, 0, 0);
    $prevista = new DateTime($dataPrevista);
    $prevista->setTime(0, 0, 0);

    if ($hoje <= $prevista) return 0;

    $dias   = 0;
    $cursor = clone $prevista;
    $cursor->modify('+1 day'); // primeiro dia de atraso é o dia seguinte ao prazo

    while ($cursor <= $hoje) {
        $dow = (int)$cursor->format('N');
        if ($dow <= 5) $dias++;
        $cursor->modify('+1 day');
    }
    return $dias;
}
