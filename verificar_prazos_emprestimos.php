<?php
// Remove o limite de tempo de execução do PHP para CLI ou processos longos
set_time_limit(0);
date_default_timezone_set('America/Sao_Paulo'); 

require 'config.php';
$projeto_id = "verbum-bd";

// Funções Auxiliares do Firestore Adaptadas para a API REST
function firestoreListar($url) {
    $json = @file_get_contents($url);
    if (!$json) return [];
    $data = json_decode($json, true);
    return $data['documents'] ?? [];
}

function firestorePost($url, $fields) {
    // API REST do Firestore exige o root 'fields'
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

// Função para checar se a exata mensagem já foi enviada hoje (Evita SPAM no dropdown)
function jaFoiNotificadoHoje($projeto_id, $matricula, $mensagem) {
    $url = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/notificacoes?pageSize=100";
    $json = @file_get_contents($url);
    if (!$json) return false;
    
    $dados = json_decode($json, true);
    $documentos = $dados['documents'] ?? [];
    $hojeStr = date('Y-m-d');

    foreach ($documentos as $doc) {
        $fields = $doc['fields'] ?? [];
        $m = $fields['matricula']['stringValue'] ?? '';
        $msg = $fields['mensagem']['stringValue'] ?? '';
        $data = $fields['data']['stringValue'] ?? ''; // Ex: 2026-05-20 14:30:00

        if ($m === $matricula && $msg === $mensagem && strpos($data, $hojeStr) === 0) {
            return true; // Já foi enviado hoje!
        }
    }
    return false;
}

// LOOP DE VERIFICAÇÃO AUTOMÁTICA
while (true) {
    echo "A verificar prazos de empréstimos no Firestore... (" . date('H:i:s') . ")\n";

    $urlEmprestimos = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents/emprestimos";
    $emprestimos = firestoreListar($urlEmprestimos);
    
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
        
        $intervalo = $hoje->diff($data_prevista);
        $diferenca_dias = (int)$intervalo->format('%r%a'); 

        $enviar_mensagem = "";

        if ($diferenca_dias === 2) {
            $enviar_mensagem = "Atenção: Faltam 2 dias para o prazo de devolução do livro \"$titulo_obra\". Não se esqueça de devolvê-lo no balcão!";
        } elseif ($diferenca_dias === 1) {
            $enviar_mensagem = "Lembrete importante: O prazo de devolução do livro \"$titulo_obra\" vence AMANHÃ. Evite suspensões!";
        } elseif ($diferenca_dias === 0) {
            $enviar_mensagem = "Atenção: O prazo de devolução do livro \"$titulo_obra\" vence HOJE!";
        } elseif ($diferenca_dias < 0) {
            $dias_atraso = abs($diferenca_dias);
            $enviar_mensagem = "Aviso de Atraso: O livro \"$titulo_obra\" está atrasado há $dias_atraso dia(s). Por favor, compareça à biblioteca.";
        }

        if (!empty($enviar_mensagem)) {
            // Verifica a trava anti-duplicação antes de postar
            if (!jaFoiNotificadoHoje($projeto_id, $matricula, $enviar_mensagem)) {
                $dadosNotificacao = [
                    'matricula' => ['stringValue' => $matricula],
                    'mensagem'  => ['stringValue' => $enviar_mensagem],
                    'data'      => ['stringValue' => date('Y-m-d H:i:s')],
                    'lida'      => ['booleanValue' => false]
                ];
                
                firestorePost($urlNotificacoes, $dadosNotificacao);
                echo "Notificação de prazo enviada para a matrícula $matricula.\n";
            } else {
                echo "Matrícula $matricula já recebeu este aviso hoje. Pulado.\n";
            }
        }
    }

    echo "Verificação concluída. Próxima verificação em 12 horas...\n";
    if (ob_get_level() > 0) ob_flush();
    flush(); 

    // Dorme por 12 horas
    sleep(43200); 
}
?>