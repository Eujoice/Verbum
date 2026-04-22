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

    // 1. HISTÓRICO E INATIVAR EMPRÉSTIMO
    if (!empty($idEmprestimo)) {
        $urlEmp = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/emprestimos/{$idEmprestimo}";
        curl_setopt($ch, CURLOPT_URL, $urlEmp);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $dadosEmp = json_decode(curl_exec($ch), true);

        if (isset($dadosEmp['fields'])) {
            // Salva Histórico
            curl_setopt($ch, CURLOPT_URL, "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/historico");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => [
                'usuario_id' => $dadosEmp['fields']['usuario_id'],
                'obra_id' => $dadosEmp['fields']['obra_id'],
                'data_retirada' => $dadosEmp['fields']['data_emprestimo'],
                'data_devolucao_real' => ['stringValue' => date('Y-m-d')]
            ]]));
            curl_exec($ch);

            // Inativa Empréstimo
            curl_setopt($ch, CURLOPT_URL, $urlEmp . "?updateMask.fieldPaths=status");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => ['status' => ['stringValue' => 'inativo']]]));
            curl_exec($ch);
        }
    }

    // 2. LÓGICA DE FILA
    $resReservas = json_decode(file_get_contents("https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/reservas"), true);
    $primeiroFila = null;
    $dataAntiga = null;

    if (isset($resReservas['documents'])) {
        foreach ($resReservas['documents'] as $doc) {
            $f = $doc['fields'];
            if (($f['obra_id']['stringValue'] ?? '') == $idObra && ($f['tipo']['stringValue'] ?? '') == 'Fila') {
                $d = $f['data_reserva']['stringValue'];
                if ($dataAntiga === null || $d < $dataAntiga) {
                    $dataAntiga = $d;
                    $primeiroFila = $doc['name'];
                }
            }
        }
    }

    $statusFinal = 'Disponível';
    if ($primeiroFila) {
        $statusFinal = 'Reservado';
        // Promove o primeiro da fila
        curl_setopt($ch, CURLOPT_URL, "https://firestore.googleapis.com/v1/{$primeiroFila}?updateMask.fieldPaths=tipo");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => ['tipo' => ['stringValue' => 'Direta']]]));
        curl_exec($ch);
    }

    // 3. ATUALIZA OBRA
    $urlObra = "https://firestore.googleapis.com/v1/projects/{$projetoID}/databases/(default)/documents/obras/{$idObra}?updateMask.fieldPaths=status&updateMask.fieldPaths=emprestado_por&updateMask.fieldPaths=id_emprestimo_atual";
    curl_setopt($ch, CURLOPT_URL, $urlObra);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => [
        'status' => ['stringValue' => $statusFinal],
        'emprestado_por' => ['stringValue' => ''],
        'id_emprestimo_atual' => ['stringValue' => '']
    ]]));
    curl_exec($ch);

    curl_close($ch);
    echo "Sucesso!";
}