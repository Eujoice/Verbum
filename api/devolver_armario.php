<?php
require '../includes/config.php';
require_once '../includes/dias_uteis.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $num       = $_POST['numero_armario'];
    $projetoID = "verbum-bd";
    $base      = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents";

    // 1. Busca dados atuais do armário para calcular multa
    $ch = curl_init("$base/armarios/$num");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $dadosArmario = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $matricula    = $dadosArmario['fields']['usuario_matricula']['stringValue'] ?? '';
    $dataOcupacao = $dadosArmario['fields']['data_ocupacao']['stringValue']     ?? '';

    // 2. Calcula multa: 1 dia útil de prazo (o próprio dia de ocupação + 1 dia útil)
    $multaArmario = 0.00;
    if (!empty($dataOcupacao)) {
        $prazoDevol  = adicionarDiasUteis($dataOcupacao, 1); // deve devolver em 1 dia útil
        $diasAtraso  = contarDiasUteisAtraso($prazoDevol);
        $multaArmario = $diasAtraso * 5.00; // R$ 5,00 por dia útil de atraso
    }

    // 3. Libera o armário
    $urlPatch = "$base/armarios/$num"
              . "?updateMask.fieldPaths=ocupado"
              . "&updateMask.fieldPaths=usuario_matricula"
              . "&updateMask.fieldPaths=data_ocupacao"
              . "&updateMask.fieldPaths=multa_pendente";

    $dados = [
        'fields' => [
            'ocupado'           => ['booleanValue' => false],
            'usuario_matricula' => ['stringValue'  => ''],
            'data_ocupacao'     => ['stringValue'  => ''],
            'multa_pendente'    => ['doubleValue'  => 0.0],
        ]
    ];

    $ch = curl_init($urlPatch);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_exec($ch);
    curl_close($ch);

    // 4. Se houver multa, registra na coleção de multas_armario
    if ($multaArmario > 0 && !empty($matricula)) {
        $dadosMulta = [
            'fields' => [
                'matricula'      => ['stringValue' => $matricula],
                'armario'        => ['stringValue' => $num],
                'data_ocupacao'  => ['stringValue' => $dataOcupacao],
                'data_devolucao' => ['stringValue' => date('Y-m-d')],
                'valor'          => ['doubleValue' => $multaArmario],
                'status'         => ['stringValue' => 'pendente'],
            ]
        ];
        $ch = curl_init("$base/multas_armario");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dadosMulta));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);

        $valorFormatado = number_format($multaArmario, 2, ',', '.');
        header("Location: ../pages/armario.php?sucesso=2&multa=$valorFormatado&matricula=" . urlencode($matricula));
    } else {
        header("Location: ../pages/armario.php?sucesso=2");
    }
    exit();
}
