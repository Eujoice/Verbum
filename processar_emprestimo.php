<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['usuario_id'] ?? '';
    $idObra = $_POST['livro_id'] ?? '';
    $dataIni = $_POST['data_ini'] ?? '';
    $dataFim = $_POST['data_fim'] ?? '';

    if (empty($matricula) || empty($idObra) || empty($dataIni) || empty($dataFim)) {
        echo "Erro: Preencha todos os campos (Usuário, Livro e Datas).";
        exit;
    }

    // A função processarTransacaoEmprestimo deve ser a responsável por:
    // 1. Criar o documento em 'emprestimos' com status 'ativo'
    // 2. Atualizar a 'obra' com status 'Emprestado' e o ID deste empréstimo
    $resultado = processarTransacaoEmprestimo($matricula, $idObra, $dataIni, $dataFim);
    
    if ($resultado) {
        $resArray = json_decode($resultado, true);
        if (isset($resArray['error'])) {
            echo "Erro API: " . $resArray['error']['message'];
        } else {
            echo "Sucesso";
        }
    } else {
        echo "Erro de conexão com o banco.";
    }
}
?>