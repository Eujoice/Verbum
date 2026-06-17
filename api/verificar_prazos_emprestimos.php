<?php
// Remove o limite de tempo de execução do PHP para CLI ou processos longos
set_time_limit(0);
date_default_timezone_set('America/Sao_Paulo'); 

require '../includes/config.php';
require_once '../includes/dias_uteis.php';
$projeto_id = "verbum-bd";

function firestoreListar($url) {
    $json = @file_get_contents($url);
    if (!$json) return [];
    $data = json_decode($json, true);
    return $data['documents'] ?? [];
}

function firestorePost($url, $fields) {
    $body = json_encode(['fields' => $fields]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/*
 * CORREÇÃO: Em vez de consultar o Firestore para cada empréstimo
 * (N leituras da coleção inteira de notificações), carregamos
 * TODAS as notificações de hoje UMA única vez por ciclo e
 * construímos um Set em memória para checagem instantânea.
 * Redução: de N*100 leituras para 100 leituras por ciclo.
 */
function carregarNotificacoesHoje($projeto_id) {
    $url = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes?pageSize=500";
    $json = @file_get_contents($url);
    if (!$json) return [];

    $dados = json_decode($json, true);
    $documentos = $dados['documents'] ?? [];
    $hojeStr = date('Y-m-d');
    $cache = []; // chave => true

    foreach ($documentos as $doc) {
        $fields = $doc['fields'] ?? [];
        $m    = $fields['matricula']['stringValue'] ?? '';
        $msg  = $fields['mensagem']['stringValue'] ?? '';
        $data = $fields['data']['stringValue'] ?? '';

        if (strpos($data, $hojeStr) === 0) {
            // Chave única: matrícula + hash da mensagem
            $cache[$m . '|' . md5($msg)] = true;
        }
    }
    return $cache;
}

// LOOP DE VERIFICAÇÃO AUTOMÁTICA
while (true) {
    echo "A verificar prazos de empréstimos no Firestore... (" . date('H:i:s') . ")\n";

    // 1 leitura: todos os empréstimos
    $urlEmprestimos = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/emprestimos";
    $emprestimos = firestoreListar($urlEmprestimos);
    
    // 1 leitura: todas as notificações de hoje (cache em memória)
    $notificacoesHojeCache = carregarNotificacoesHoje($projeto_id);

    $hoje = new DateTime();
    $hoje->setTime(0, 0, 0);
    $urlNotificacoes = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes";

    foreach ($emprestimos as $emp) {
        $fields = $emp['fields'] ?? [];
        $status = $fields['status']['stringValue'] ?? '';
        
        if ($status !== 'ativo') continue;

        $matricula         = $fields['usuario_id']['stringValue'] ?? '';
        $titulo_obra       = $fields['titulo_obra']['stringValue'] ?? 'Livro'; 
        $data_prevista_str = $fields['data_devolucao_prevista']['stringValue'] ?? '';

        if (empty($matricula) || empty($data_prevista_str)) continue;

        $data_prevista = new DateTime($data_prevista_str);
        $data_prevista->setTime(0, 0, 0);

        // Calcula em dias ÚTEIS para prazo restante e atraso
        $diferenca_dias = 0;
        if ($hoje <= $data_prevista) {
            // Dias úteis restantes (positivo)
            $cursor = clone $hoje;
            $cursor->modify('+1 day');
            while ($cursor <= $data_prevista) {
                if ((int)$cursor->format('N') <= 5) $diferenca_dias++;
                $cursor->modify('+1 day');
            }
        } else {
            // Dias úteis de atraso (negativo)
            $diferenca_dias = -contarDiasUteisAtraso($data_prevista_str);
        }

        $enviar_mensagem = "";

        if ($diferenca_dias === 2) {
            $enviar_mensagem = "Atenção: Faltam 2 dias úteis para o prazo de devolução do livro \"$titulo_obra\". Não se esqueça de devolvê-lo no balcão!";
        } elseif ($diferenca_dias === 1) {
            $enviar_mensagem = "Lembrete importante: O prazo de devolução do livro \"$titulo_obra\" vence no próximo dia útil. Evite suspensões!";
        } elseif ($diferenca_dias === 0) {
            $enviar_mensagem = "Atenção: O prazo de devolução do livro \"$titulo_obra\" vence HOJE!";
        } elseif ($diferenca_dias < 0) {
            $dias_atraso = abs($diferenca_dias);
            $enviar_mensagem = "Aviso de Atraso: O livro \"$titulo_obra\" está atrasado há $dias_atraso dia(s) útil(eis). Por favor, compareça à biblioteca.";
        }

        if (!empty($enviar_mensagem)) {
            $chaveCache = $matricula . '|' . md5($enviar_mensagem);

            // Checagem em memória — zero leituras extras no Firestore
            if (!isset($notificacoesHojeCache[$chaveCache])) {
                $dadosNotificacao = [
                    'matricula' => ['stringValue' => $matricula],
                    'mensagem'  => ['stringValue' => $enviar_mensagem],
                    'data'      => ['stringValue' => date('Y-m-d H:i:s')],
                    'lida'      => ['booleanValue' => false]
                ];
                
                firestorePost($urlNotificacoes, $dadosNotificacao);
                // Atualiza o cache local para evitar duplicata no mesmo ciclo
                $notificacoesHojeCache[$chaveCache] = true;
                echo "Notificação de prazo enviada para a matrícula $matricula.\n";
            } else {
                echo "Matrícula $matricula já recebeu este aviso hoje. Pulado.\n";
            }
        }
    }

    echo "Verificação concluída. Próxima verificação em 12 horas...\n";
    if (ob_get_level() > 0) ob_flush();
    flush(); 

    sleep(43200); // 12 horas
}
?>
