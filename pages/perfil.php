<!--Tela de Perfil-->

<?php
session_start();

// FORÇA O NAVEGADOR A NÃO GUARDAR ESTA PÁGINA NO CACHE
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// verifica se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../includes/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Perfil</title>
    <link rel="stylesheet" href="../assets/css/footer.css">
</head>
<body class="body-perfil">  
<img src="../assets/imgs/estrela.png" class="star star1">
<img src="../assets/imgs/estrela.png" class="star star2">
<img src="../assets/imgs/estrela.png" class="star star3">
<img src="../assets/imgs/estrela.png" class="star star4">
<img src="../assets/imgs/estrela.png" class="star star5">
<img src="../assets/imgs/estrela.png" class="star star6">
<img src="../assets/imgs/estrela.png" class="star star7">
<img src="../assets/imgs/estrela.png" class="star star8">

    <div class="container-perfil">

        <div class="foto">
            <img src="../assets/imgs/icon_perfil.png" alt="Foto de Perfil" width="180" height="140">
        </div>

        <div class="dados-entrada-perfil">
            <p id="nomeUsuario"><?php echo $_SESSION['usuario_nome']; ?></p>
            <p id="matriculaUsuario">Matrícula: <?php echo $_SESSION['usuario_matricula']; ?></p>
        </div>

        <div class="botoes">
            <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador'): ?>
                <a href="consulta.php" class="btn-admin-atalho">Acessar Painel Administrativo</a>
            <?php endif; ?>

            <a class="btn-titulos">Títulos Pendentes</a>
            <a class="btn-historico">Histórico de Empréstimos</a>
            <a class="btn-dados" href="dpessoais.php">Dados Pessoais</a>
            <a class="btn-favoritos">Favoritos e Avaliações</a>
            <a href="../includes/logout.php" class="btn-sair" style="text-decoration: none; text-align: center;">Sair</a>
        </div>
    </div>
    <script src="../assets/js/script.js"></script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>