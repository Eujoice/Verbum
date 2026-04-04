<?php
$projetoID = "verbum-bd"; 

$baseUrl = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/usuarios/";

function buscarUsuario($matricula) {
    global $baseUrl;
    $url = $baseUrl . trim($matricula);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $resposta = curl_exec($ch);
    curl_close($ch);
    return json_decode($resposta, true);
}

function processarTransacaoEmprestimo($matricula, $idObra, $dataIni, $dataFim) {
    global $projetoID;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    // 1. Cria o registro na coleção 'emprestimos' primeiro para obter o ID
    $urlHist = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/emprestimos";
    $payloadHist = json_encode([
        'fields' => [
            'usuario_id' => ['stringValue' => $matricula],
            'obra_id' => ['stringValue' => $idObra],
            'data_emprestimo' => ['stringValue' => $dataIni],
            'data_devolucao_prevista' => ['stringValue' => $dataFim],
            'status' => ['stringValue' => 'ativo']
        ]
    ]);

    curl_setopt($ch, CURLOPT_URL, $urlHist);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadHist);
    $resEmprestimo = json_decode(curl_exec($ch), true);

    // Extrai o ID gerado 
    $pathParts = explode('/', $resEmprestimo['name']);
    $idGeradoEmprestimo = end($pathParts);

    // 2. Atualizar a Obra com o Status E o ID do empréstimo vinculado
    $urlObra = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/obras/{$idObra}?updateMask.fieldPaths=status&updateMask.fieldPaths=emprestado_por&updateMask.fieldPaths=data_emprestimo&updateMask.fieldPaths=data_devolucao_prevista&updateMask.fieldPaths=id_emprestimo_atual";
    
    $payloadObra = json_encode([
        'fields' => [
            'status' => ['stringValue' => 'Emprestado'],
            'emprestado_por' => ['stringValue' => $matricula],
            'data_emprestimo' => ['stringValue' => $dataIni],
            'data_devolucao_prevista' => ['stringValue' => $dataFim],
            'id_emprestimo_atual' => ['stringValue' => $idGeradoEmprestimo]
        ]
    ]);

    curl_setopt($ch, CURLOPT_URL, $urlObra);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadObra);
    $resObra = curl_exec($ch);
    
    curl_close($ch);
    return $resObra;
}
?>