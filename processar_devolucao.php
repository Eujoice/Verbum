<?php
require 'config.php';

$acao = $_POST['acao'] ?? '';
$idObra = $_POST['livro_id'] ?? '';
$idEmprestimo = $_POST['emprestimo_id'] ?? ''; 
$projetoID = "verbum-bd"; 

if ($acao == 'devolver' && !empty($idObra)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    if (!empty($idEmprestimo)) {
        // 1. BUSCA DADOS DO EMPRÉSTIMO ATUAL
        $urlEmp = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/emprestimos/{$idEmprestimo}";
        curl_setopt($ch, CURLOPT_URL, $urlEmp);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $res = curl_exec($ch);
        $dadosEmp = json_decode($res, true);

        if (isset($dadosEmp['fields'])) {
            // 2. MOVE PARA A COLEÇÃO 'HISTORICO'
            $urlHist = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/historico";
            $payloadHist = json_encode([
                'fields' => [
                    'usuario_id' => $dadosEmp['fields']['usuario_id'],
                    'obra_id' => $dadosEmp['fields']['obra_id'],
                    'data_retirada' => $dadosEmp['fields']['data_emprestimo'],
                    'data_devolucao_real' => ['stringValue' => date('Y-m-d')]
                ]
            ]);
            curl_setopt($ch, CURLOPT_URL, $urlHist);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadHist);
            curl_exec($ch);

            // 3. INATIVA O REGISTRO EM 'EMPRESTIMOS'
            $urlPatchEmp = $urlEmp . "?updateMask.fieldPaths=status";
            $payloadPatch = json_encode(['fields' => ['status' => ['stringValue' => 'inativo']]]);
            curl_setopt($ch, CURLOPT_URL, $urlPatchEmp);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadPatch);
            curl_exec($ch);
        }
    }

    // 4. ATUALIZA A OBRA PARA 'DISPONÍVEL' E LIMPAR VÍNCULOS
    $urlObra = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/obras/{$idObra}?updateMask.fieldPaths=status&updateMask.fieldPaths=emprestado_por&updateMask.fieldPaths=data_emprestimo&updateMask.fieldPaths=data_devolucao_prevista&updateMask.fieldPaths=id_emprestimo_atual";
    
    $payloadObra = json_encode(['fields' => [
        'status' => ['stringValue' => 'Disponível'],
        'emprestado_por' => ['stringValue' => ''],
        'data_emprestimo' => ['stringValue' => ''],
        'data_devolucao_prevista' => ['stringValue' => ''],
        'id_emprestimo_atual' => ['stringValue' => '']
    ]]);

    curl_setopt($ch, CURLOPT_URL, $urlObra);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadObra);
    curl_exec($ch);
    curl_close($ch);

    echo "Devolução realizada com sucesso!";
}
?>