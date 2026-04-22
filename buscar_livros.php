<?php
require 'config.php';
$termo = isset($_GET['q']) ? mb_strtolower(trim($_GET['q'])) : '';
if (empty($termo)) { echo json_encode([]); exit; }

// 1. Buscar Obras
$urlObras = "https://firestore.googleapis.com/v1/projects/verbum-bd/databases/(default)/documents/obras";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlObras);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resObras = curl_exec($ch);

// 2. Buscar Empréstimos (para vincular o ID do documento de empréstimo)
$urlEmprestimos = "https://firestore.googleapis.com/v1/projects/verbum-bd/databases/(default)/documents/emprestimos";
curl_setopt($ch, CURLOPT_URL, $urlEmprestimos);
$resEmprestimos = curl_exec($ch);
curl_close($ch);

$dadosObras = json_decode($resObras, true);
$dadosEmprestimos = json_decode($resEmprestimos, true);
$resultados = [];

// Cria um mapa de empréstimos ativos [obra_id => emprestimo_doc_id]
$mapaEmprestimos = [];
if (isset($dadosEmprestimos['documents'])) {
    foreach ($dadosEmprestimos['documents'] as $emp) {
        $fields = $emp['fields'];
        $statusEmp = $fields['status']['stringValue'] ?? 'ativo';
        if ($statusEmp === 'ativo') {
            $idObra = $fields['obra_id']['stringValue'] ?? '';
            $pathParts = explode('/', $emp['name']);
            $docId = end($pathParts); // Pega o ID real do documento no Firestore
            $mapaEmprestimos[$idObra] = $docId;
        }
    }
}

if (isset($dadosObras['documents'])) {
    foreach ($dadosObras['documents'] as $doc) {
        $f = $doc['fields'];
        $id = $f['id']['stringValue'] ?? '';
        $titulo = $f['titulo']['stringValue'] ?? '';
        $status = $f['status']['stringValue'] ?? 'Indisponível';
        $emprestado_por = $f['emprestado_por']['stringValue'] ?? '';
        $data_prevista = $f['data_devolucao_prevista']['stringValue'] ?? '';
        $capa = $f['capa']['stringValue'] ?? '';
        $avaliacao = $f['avaliacao']['stringValue'] ?? '';

        if (strpos(mb_strtolower($id), $termo) !== false || strpos(mb_strtolower($titulo), $termo) !== false) {
            $resultados[] = [
                'id' => $id,
                'titulo' => $titulo,
                'status' => $status,
                'emprestado_por' => $emprestado_por,
                'data_prevista' => $data_prevista,
                'capa' => $capa,
                'avaliacao' => $avaliacao,
                'id_emprestimo_atual' => $mapaEmprestimos[$id] ?? '' 
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($resultados);
?>