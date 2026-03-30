<?php
session_start();
require 'config.php';

// Se não houver matrícula na sessão, redireciona para o login
if (!isset($_SESSION['usuario_matricula'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pega a matrícula da SESSÃO (quem está logado)
    $matricula = trim($_SESSION['usuario_matricula']);
    
    // Pega os novos dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];

    // URL igual ao seu exemplo de sucesso
    $url = $baseUrl . $matricula; 
    
    // Montamos o JSON exatamente como no seu exemplo
    $dados = [
        'fields' => [
            'nome' => ['stringValue' => $nome],
            'email' => ['stringValue' => $email],
            'telefone' => ['stringValue' => $telefone],
            'endereco' => ['stringValue' => $endereco],
            // IMPORTANTE: Mantemos os campos fixos para não dar erro no Firestore
            'tipo' => ['stringValue' => $_SESSION['usuario_tipo'] ?? 'aluno'],
            'matricula' => ['stringValue' => $matricula]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $resposta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        // ATUALIZA A SESSÃO para refletir a mudança na tela imediatamente
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_email'] = $email;
        $_SESSION['usuario_telefone'] = $telefone;
        $_SESSION['usuario_endereco'] = $endereco;
        $_SESSION['sucesso'] = true;
        
        header("Location: dpessoais.php");
        exit();
    } else {
        echo "Erro ao atualizar. Código HTTP: " . $httpCode;
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados Pessoais</title>
    <link rel="stylesheet" href="/Verbum/style.css">
</head>

<body class="body-dp">

<?php if (isset($_SESSION['sucesso'])): ?>
<script>
    alert("Alterações salvas com sucesso!");
</script>
<?php unset($_SESSION['sucesso']); endif; ?>

<div class="container-dp">

    <div class="foto3">
        <img src="imgs/icon_perfil.png" width="180">
    </div>

    <div class="dados-entrada-dp">
        <a id="nomeUsuario"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></a>  
        <p id="emailUsuario"><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></p>
    </div>

    <form method="POST" class="formulario">

        <div class="campo">
            <label class="label-dp">Nome:</label>
            <input type="text" name="nome" class="formu" value="<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>">
        </div>

        <div class="campo">
            <label class="label-dp">Email:</label>
            <input type="text" name="email" class="formu" value="<?php echo htmlspecialchars($_SESSION['usuario_email']); ?>">
        </div>

        <div class="campo">
            <label class="label-dp">Telefone:</label>
            <input type="text" name="telefone" class="formu" value="<?php echo htmlspecialchars($_SESSION['usuario_telefone'] ?? ''); ?>">
        </div>

        <div class="campo">
            <label class="label-dp">Endereço:</label>
            <input type="text" name="endereco" class="formu" value="<?php echo htmlspecialchars($_SESSION['usuario_endereco'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn">Salvar</button>

    </form>

</div>

</body>
</html>