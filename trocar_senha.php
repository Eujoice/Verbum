<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verbum - Primeiro Acesso</title>
    <link rel="stylesheet" href="style-trocar_senha.css">
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
            <h2 style="color: white; text-align: center;">Boas-vindas ao Verbum!</h2>
            <p style="margin-bottom: 30px; margin-top: -17px; text-align: center; color: white;">Por favor, crie uma senha.</p>

            <form id="formTrocaSenha" action="processar_troca.php" method="POST">
                
                <label class="matr-login" style="margin-left: 30px">Nova senha</label>
                <input style="margin-left: 30px;" type="password" class="input-login" name="nova_senha" id="nova_senha" required>

                <ul id="requisitos-senha" style="list-style: none; padding: 0; margin-top: 10px; font-size: 0.85em;">
                    <li style="margin-left: 30px" id="req-comprimento">✖ Mínimo de 8 caracteres</li>
                    <li style="margin-left: 30px" id="req-maiuscula">✖ Pelo menos uma letra maiúscula</li>
                    <li style="margin-left: 30px" id="req-numero">✖ Pelo menos um número</li>
                    <li style="margin-left: 30px" id="req-especial">✖ Pelo menos um caractere especial (@, #, $, etc.)</li>
                </ul>

                <label style="margin-left: 30px; margin-top: 10px" class="senha-login">Confirmar nova senha</label>
                <input style="margin-left: 30px" type="password" class="input-login" name="confirmar_senha" id="confirmar_senha" required>

                <div class="div-log-inf">
                    <button class="btn-login" id="btnAtualizar" type="submit">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="trocar_senha_logic.js"></script>
    <script src="script.js"></script>
</body>
</html>