<?php
session_start();
require 'config.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-trocar_senha.css">
    <title>Recuperar Senha - Verbum</title>
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
            <h2 style="color: white; text-align: center;">Esqueceu sua Senha?</h2>
            <p style="margin-bottom: 30px; margin-top: -17px; text-align: center; color: white;">Informe os dados cadastrados.</p>

            <form action="processar_pedido_recuperacao.php" method="POST" id="formRecuperar">
                <label style="margin-left: 30px" class="matr-login">Matrícula</label>
                <input style="margin-left: 30px" type="text" class="input-login" name="matricula" required style="margin-bottom: 18px;">

                <label style="margin-left: 30px" class="senha-login">E-mail Cadastrado</label>
                <input style="margin-left: 30px" type="email" class="input-login" name="email" required>
                
                <p class="link-rec">Um link de verificação será enviado ao e-mail cadastrado.</p>
                
                <div class="div-log-inf">
                    <button class="btn-enviar" type="submit">Enviar Link</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="script_recuperacao.js"></script>
</body>
</html>