<!--Tela de Perfil-->

<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Perfil</title>
</head>
<body class="body-perfil">  
<img src="imgs/estrela.png" class="star star1">
<img src="imgs/estrela.png" class="star star2">
<img src="imgs/estrela.png" class="star star3">
<img src="imgs/estrela.png" class="star star4">
<img src="imgs/estrela.png" class="star star5">
<img src="imgs/estrela.png" class="star star6">
<img src="imgs/estrela.png" class="star star7">
<img src="imgs/estrela.png" class="star star8">

    <div class="container-perfil">

        <div class="foto">
            <img src="imgs/icon_perfil.png" alt="Foto de Perfil" width="180" height="140">
        </div>

        <div class="dados-entrada-perfil">
            <p id="nomeUsuario"><?php echo $_SESSION['usuario_nome']; ?></p>
            <p id="matriculaUsuario">Matrícula: <?php echo $_SESSION['usuario_matricula']; ?></p>
        </div>

        <div class="botoes">
            <a class="btn-titulos">Títulos Pendentes</a>
            <a class="btn-historico">Histórico de Empréstimos</a>
            <a class="btn-dados" href="dpessoais.html">Dados Pessoais</a>
            <a class="btn-favoritos">Favoritos e Avaliações</a>
            <a href="logout.php" class="btn-sair" style="text-decoration: none; text-align: center;">Sair</a>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>