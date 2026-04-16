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
            <div class="linha">
            <label for="pesquisa">Pesquisar exemplar</label>
            <div class="busca">
                <svg class="icone-lupa" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" stroke="#9aaa98" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                <input type="text" id="pesquisa" placeholder="Digite o nome do livro, autor...">
            </div>
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