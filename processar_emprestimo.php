<?php
session_start();
require 'config.php';
date_default_timezone_set('America/Sao_Paulo'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['usuario_id'] ?? '';
    $idObra = $_POST['livro_id'] ?? '';
    $dataIni = $_POST['data_ini'] ?? '';
    $dataFim = $_POST['data_fim'] ?? '';
    $projeto_id = "verbum-bd"; 

    if (empty($matricula) || empty($idObra) || empty($dataIni) || empty($dataFim)) {
        echo "Erro: Preencha todos os campos (Usuário, Livro e Datas).";
        exit;
    }

    // 1. Executa a transação padrão do sistema para criar o empréstimo
    $resultado = processarTransacaoEmprestimo($matricula, $idObra, $dataIni, $dataFim);
    
    if ($resultado) {
        $resArray = json_decode($resultado, true);
        
        if (isset($resArray['error'])) {
            echo "Erro API: " . $resArray['error']['message'];
        } else {
            
            /* ══════════════════════════════════════════════
               DISPARO DA NOTIFICAÇÃO (SEGURO)
            ══════════════════════════════════════════════ */
            
            // Tenta buscar o título do livro de forma isolada
            $tituloObra = 'Livro'; 
            $urlObra = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/obras/$idObra";
            $chObra = curl_init($urlObra);
            curl_setopt($chObra, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chObra, CURLOPT_TIMEOUT, 3); // Não deixa o sistema esperando mais que 3 segundos
            $jsonObra = curl_exec($chObra);
            curl_close($chObra);

            if ($jsonObra) {
                $dadosObra = json_decode($jsonObra, true);
                if (isset($dadosObra['fields']['titulo']['stringValue'])) {
                    $tituloObra = $dadosObra['fields']['titulo']['stringValue'];
                }
            }

            // Formata a data de devolução para DD/MM/AAAA
            $partesData = explode('-', $dataFim);
            $dataFormatada = (count($partesData) === 3) ? "{$partesData[2]}/{$partesData[1]}/{$partesData[0]}" : $dataFim;

            // Monta o texto que o aluno verá no Dropdown
            $mensagem = "Empréstimo realizado com sucesso! O livro \"$tituloObra\" deve ser devolvido até o dia $dataFormatada.";

            // Payload exigido pela API REST do Firestore
            $dadosNotificacao = [
                'fields' => [
                    'matricula' => ['stringValue' => $matricula],
                    'mensagem'  => ['stringValue' => $mensagem],
                    'data'      => ['stringValue' => date('Y-m-d H:i:s')],
                    'lida'      => ['booleanValue' => false]
                ]
            ];

            // Envia os dados para a coleção de notificações usando cURL puro
            $urlNotificacoes = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes";
            $chNotif = curl_init($urlNotificacoes);
            curl_setopt($chNotif, CURLOPT_POST, true);
            curl_setopt($chNotif, CURLOPT_POSTFIELDS, json_encode($dadosNotificacao));
            curl_setopt($chNotif, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chNotif, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_exec($chNotif);
            curl_close($chNotif);

            // Retorna a resposta esperada pelo seu front-end JavaScript
            echo "Sucesso";
        }
    } else {
        echo "Erro de conexão com o banco.";
    }
}
?>