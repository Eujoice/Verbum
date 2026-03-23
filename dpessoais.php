<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $matricula = $_SESSION['usuario_matricula'];

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $endereco = $_POST['endereco'];

    $url = $baseUrl . $matricula;

    $dados = [
        'fields' => [
            'nome' => ['stringValue' => $nome],
            'email' => ['stringValue' => $email],
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

    curl_exec($ch);
    curl_close($ch);

    $_SESSION['usuario_nome'] = $nome;
    $_SESSION['usuario_email'] = $email;

    $_SESSION['sucesso'] = true;

    header("Location: dpessoais.php");
    exit();
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
        <a id="nomeUsuario"><?php echo $_SESSION['usuario_nome']; ?></a>  
        <p id="emailUsuario"><?php echo $_SESSION['usuario_email'] ?? ''; ?></p>
    </div>

    <form method="POST" class="formulario">

        <div class="campo">
            <label class="label-dp">Nome:</label>
            <input type="text" name="nome" class="formu" value="<?php echo $_SESSION['usuario_nome']; ?>">
        </div>

        <div class="campo">
            <label class="label-dp">Email:</label>
            <input type="text" name="email" class="formu" value="<?php echo $_SESSION['usuario_email'] ?? ''; ?>">
        </div>

        <div class="campo">
            <label class="label-dp">Telefone:</label>
            <input type="text" name="telefone" class="formu">
        </div>

        <div class="campo">
            <label class="label-dp">Endereço:</label>
            <input type="text" name="endereco" class="formu">
        </div>

        <button type="submit" class="btn">Salvar</button>

    </form>

</div>

</body>
</html>

