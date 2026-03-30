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
    <title>Verbum | Empréstimos</title>
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
        <a class="ativo" href="emprestimo.php">Empréstimo e devolução</a>
        <a href="armario.php">Armários</a>
        <a href="cadastro.php">Cadastro de usuário</a>
        <a href="consulta.php">Consultar exemplares</a>
    </nav>

    <div class="form-container">
        <form id="formEmprestimo">
            <div class="linha radio-group">
                <label>Emprestado por:</label>
                <label><input type="radio" name="tipo" value="Instituição" checked> Usuário da instituição</label>
                <label><input type="radio" name="tipo" value="Visitante"> Visitante</label>
            </div>

            <div class="linha">
                <label>Pesquisar usuário:</label>
                <input type="text" id="nomeUsuario" style="flex: 1;" placeholder="Digite o nome do usuário..."> 
            </div>

            <div class="linha">
                <label>Senha do usuário:</label>
                <input type="password" id="senhaUsuario">
                <label>Código do livro:</label>
                <input type="text" id="codigoLivro" style="width: 150px;">
            </div>

            <hr class="divisor">

            <div class="linha">
                <label>Data de empréstimo:</label>
                <input type="date" id="dataEmp">
                <label>Data de devolução prevista:</label>
                <input type="date" id="dataPrev">
            </div>

            <div class="linha">
                <label>Data de devolução</label>
                <input type="date" id="dataDevolucaoEfetiva">
                <label>Valor da multa:</label>
                <input type="text" class="input-readonly" placeholder="R$ 0,00" readonly>
                <label>Dias de atraso:</label>
                <input type="text" class="input-readonly" placeholder="0" readonly>
            </div>

            <div class="area-botoes">
                <button type="button" id="btnEmprestar" class="btn-acao verde-claro">Emprestar</button>
                <button type="button" class="btn-acao verde-claro">⟲ Renovar</button>
                <button type="button" class="btn-acao verde-escuro">Pagar multa</button>
            </div>
        </form>
    </div>


</div>

</body>
</html>