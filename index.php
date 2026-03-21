<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = $_POST['matricula'];
    $senhaDigitada = $_POST['senha'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Login</title>
</head>
<body class="body-login">
<img src="imgs/estrela.png" class="star star1">
<img src="imgs/estrela.png" class="star star2">
<img src="imgs/estrela.png" class="star star3">
<img src="imgs/estrela.png" class="star star4">
<img src="imgs/estrela.png" class="star star5">
<img src="imgs/estrela.png" class="star star6">
<img src="imgs/estrela.png" class="star star7">
<img src="imgs/estrela.png" class="star star8">

        <div class="div-central">
        <div class="div-titulo">
            <h1 class="titulo-login" id="titulo">Verbum</h1>

        </div>
        <div class="div-entradas">
    
            <?php if (isset($_GET['erro'])): ?>
                <div class="mensagem-erro">
                    <?php 
                        if ($_GET['erro'] == 'senha') echo "Senha incorreta! Tente novamente.";
                        if ($_GET['erro'] == 'usuario') echo "Usuário não encontrado!";
                    ?>
                </div>
            <?php endif; ?>


            <form action="login.php" method="post">
                <label id="label-login" class="matr-login" for="matricula">Matrícula</label>
                <input type="text" class="input-login" id="matricula" name="matricula"><br>

                <label id="label-login" class="senha-login" for="senha">Senha</label>
                <input type="password" class="input-login" id="senha" name="senha" minlength="8"><br>
                
                <div class="div-log-inf">
                    <button class="btn-login" type="submit" id="btn-login">Entrar</button><br>

                    <a class="a-log-sup" id="a-login" href="">Esqueceu a senha ou o usuário?</a>
            </form>
        </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="script.js"></script>
</body>
</html>