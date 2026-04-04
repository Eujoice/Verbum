<!-- Tela ADM - Consulta -->

<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verbum | Consultar exemplares</title>
    <link rel="stylesheet" href="consulta.css">
</head>
<body class="body-acervo">

<div class="container-acervo">

    <div class="topo">
    <header class="header">
        <div class="header-left">
            <div class="logo"><a href="acervo.php">Verbum</a></div>
            <img class="logo-vb" src="imgs/ig_aviao.png" alt="Logo">
        </div>
        <span class="badge-admin">Painel Administrativo</span>
        <div class="icones">
            <span class="favoritos">Favoritos <img src="imgs/Heart.png" class="icone-coracao"></span>
            <a href="perfil.php"><img src="imgs/usuario.png" class="icone-usuario"></a>
        </div>
    </header>

    <nav class="menu">
        <a href="emprestimo.php">Empréstimo e devolução</a>
        <a href="armario.php">Armários</a>
        <a href="cadastro.php">Cadastro de usuário</a>
        <a class="ativo" href="consulta.php">Consultar exemplares</a>
    </nav>
    </div>

    <div class="consulta-content">

        <!-- Busca -->
        <div class="secao-busca">
            <label for="pesquisa">Pesquisar exemplar</label>
            <div class="input-busca-container">
                <input type="text" id="pesquisa" placeholder="Digite o nome do livro, autor ou ISBN...">
                <button class="btn-lupa">
                    <img src="imgs/lupa.png" alt="Buscar">
                </button>
            </div>
        </div>

        <!-- Abas -->
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab ativo" onclick="trocarTab('acervo', this)">
                    Acervo
                    <span class="tab-count" id="count-acervo">—</span>
                </button>
                <button class="tab" onclick="trocarTab('emprestimos', this)">
                    Em empréstimo
                    <span class="tab-count" id="count-emprestimos">—</span>
                </button>
                <button class="tab" onclick="trocarTab('reservados', this)">
                    Reservados
                    <span class="tab-count" id="count-reservados">—</span>
                </button>
                <button class="tab" onclick="trocarTab('historico', this)">
                    Histórico de Empréstimos 
                    <span class="tab-count" id="count-historico">0</span>
                </button>
                </div>
        </div>

        <div id="panel-acervo" class="panel ativo">
            <div class="lista-resultados" id="lista-acervo"></div>
            <div class="paginacao" id="paginacao-acervo"></div>
        </div>

        <div id="panel-emprestimos" class="panel">
            <div class="lista-resultados" id="lista-emprestimos"></div>
            <div class="paginacao" id="paginacao-emprestimos"></div>
        </div>

        <div id="panel-reservados" class="panel">
            <div class="lista-resultados" id="lista-reservados"></div>
            <div class="paginacao" id="paginacao-reservados"></div>
        </div>

        <div id="panel-historico" class="panel">
            <div class="lista-resultados" id="lista-historico"></div>
            <div class="paginacao" id="paginacao-historico"></div>
        </div>
</div>

<script src="consulta.js"></script>

</body>
</html>