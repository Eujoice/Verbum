<?php
session_start();
require 'config.php';

if (!isset($_SESSION['usuario_matricula'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = trim($_SESSION['usuario_matricula']);
    
    $nome     = $_POST['nome'];
    $email    = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];

    $queryParams = http_build_query([
        'updateMask.fieldPaths' => ['nome', 'email', 'telefone', 'endereco']
    ]);
    $queryParams = preg_replace('/%5B\d+%5D/', '', $queryParams);
    $url = $baseUrl . $matricula . "?" . $queryParams;

    $dados = [
        'fields' => [
            'nome'     => ['stringValue' => $nome],
            'email'    => ['stringValue' => $email],
            'telefone' => ['stringValue' => $telefone],
            'endereco' => ['stringValue' => $endereco]
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
        $_SESSION['usuario_nome']     = $nome;
        $_SESSION['usuario_email']    = $email;
        $_SESSION['usuario_telefone'] = $telefone;
        $_SESSION['usuario_endereco'] = $endereco;
        $_SESSION['sucesso']          = true;

        header("Location: dpessoais.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dados Pessoais — Verbum</title>
    <link rel="stylesheet" href="styledp.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=ABeeZee&display=swap" rel="stylesheet">
</head>
<body class="body-dp">



<!-- ─── Card de perfil ─── -->
<div class="container-dp">

    <div class="header-perfil">
        <div class="header-banner"></div>

        <div class="foto3">
            <img src="imgs/icon_perfil.png" alt="Foto de perfil">
        </div>

        <div class="dados-entrada-dp">
            <a id="nomeUsuario"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></a>
            <p id="emailUsuario"><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></p>
        </div>
    </div>

    <div class="divider"></div>

    <form method="POST" class="formulario">

        <div class="campo">
            <label class="label-dp">Nome</label>
            <input type="text" name="nome" class="formu"
                   value="<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>"
                   placeholder="Seu nome completo">
        </div>

        <div class="campo">
            <label class="label-dp">E-mail</label>
            <input type="email" name="email" class="formu"
                   value="<?php echo htmlspecialchars($_SESSION['usuario_email']); ?>"
                   placeholder="seu@email.com">
        </div>

        <div class="campo">
            <label class="label-dp">Telefone</label>
            <input type="tel" name="telefone" class="formu"
                   value="<?php echo htmlspecialchars($_SESSION['usuario_telefone'] ?? ''); ?>"
                   placeholder="(27) 9 0000-0000">
        </div>

        <div class="campo">
            <label class="label-dp">Endereço</label>
            <input type="text" name="endereco" class="formu"
                   value="<?php echo htmlspecialchars($_SESSION['usuario_endereco'] ?? ''); ?>"
                   placeholder="Rua, número, bairro">
        </div>

        <div class="btn-wrapper">
            <button type="submit" class="btn">Salvar alterações</button>
        </div>

    </form>
</div>

<!-- ─── Modal popup de sucesso ─── -->
<div id="modalSucesso" class="modal-overlay">
    <div class="modal-content">
        <img src="imgs/aluna.png" class="modal-img" alt="Sucesso">
        <h3>Alterações salvas!</h3>
        <p>Seus dados foram atualizados com sucesso.</p>
        <button type="button" onclick="fecharModal()" class="btn-confirmar">Ok</button>
    </div>
</div>

<script>
    function fecharModal() {
        document.getElementById('modalSucesso').classList.remove('ativo');
    }

    <?php if (isset($_SESSION['sucesso'])): ?>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('modalSucesso').classList.add('ativo');
    });
    <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>
</script>

</body>
</html>