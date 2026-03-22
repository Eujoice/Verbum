<!--Tela de Acervo-->

<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verbum | Acervo</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="body-acervo">

<div class="container-acervo">

<header class="header">
    <div class="logo">
        <a href="acervo.php" style="text-decoration: none; color: inherit;">Verbum</a>
    </div>

    <div class="busca">
        <img src="imgs/lupa.png" class="icone-lupa">
        <input type="text" placeholder="O que você quer ler?">
    </div>

    <div class="icones">
        <span class="favoritos">
            Favoritos
            <img src="imgs/Heart.png" class="icone-coracao">
        </span>
        <a href="perfil.php">
            <img id="iconeUsuario" src="imgs/usuario.png" class="icone-usuario">
        </a>
    </div>
</header>

<nav class="menu">
    <a class="ativo" href="#">Acervo</a>
    <a href="#">Recomendações</a>
    <a href="#">Gênero</a>
    <a href="#">Histórico</a>
    <a href="#">Títulos pendentes</a>
</nav>

<section class="banner">
    <img src="imgs/banner-percy.jpg" alt="Percy Jackson">
</section>

<section class="populares">
 <div class="populares-container">
    <h2>Populares</h2>

    <div class="lista-livros" id="lista-livros-firebase">

    </div>
 </div>
</section>

</div>

<script src="script.js"></script>
<script type="module" src="acervo_logic.js"></script>
</body>
</html>
