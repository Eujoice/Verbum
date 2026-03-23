<!-- Tela ADM - Armarios --> 
 
<?php
session_start();
// verifica se está logado e se é administrador
if (!isset($_SESSION['logado']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: index.php"); // manda de volta se não for adm
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
    <span class="badge-admin">Painel Administrativo</span>
    <div class="icones">
        <span class="favoritos">Favoritos <img src="imgs/Heart.png" class="icone-coracao"></span>        
        <a href="perfil.php">
            <img id="iconeUsuario" src="imgs/usuario.png" class="icone-usuario">
        </a>
    </div>
</header>

<nav class="menu">
    <a href="emprestimo.php">Empréstimo e devolução</a>
        <a class="ativo" href="armario.php">Armários</a>
        <a href="cadastro.php">Cadastro de usuário</a>
        <a href="consulta.php">Consultar exemplares</a>
</nav>

    <div class="form-container">
        <form>
            <div class="linha radio-group">
                <label>Emprestado por:</label>
                <label><input type="radio" name="tipo" checked> Usuário da instituição</label>
                <label><input type="radio" name="tipo"> Visitante</label>
            </div>

            <div class="linha">
                <label>Pesquisar usuário:</label>
                <input type="text" style="flex: 1;" placeholder="Digite o nome do usuário..."> 
            </div>

            <div class="linha">
                <label>Senha do usuário:</label>
                <input type="password">
            </div>

            <hr class="divisor">

            <div class="linha">
                <label">Número do armário:</label>
                <input type="text"> 
            </div>

            <div class="linha"> 
                <label>Data de empréstimo:</label>
                <input type="date">
            </div>

            <div class="linha">
                <label>Valor da multa:</label>
                <input type="text" class="input-readonly" placeholder="R$ 0,00" readonly>
            </div>

            <div class="area-botoes">
                <button type="button" class="btn-acao verde-claro">Emprestar</button>
                <button type="button" class="btn-acao verde-escuro">Pagar multa</button>
            </div>
        </form>
    </div>
    <script src="script.js"></script>
</body>
</html>