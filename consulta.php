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
            <a href="perfil.php"><img src="imgs/usuario.png" class="icone-usuario"></a>
        </div>
    </header>

    <nav class="menu">
        <a href="emprestimo.php">Empréstimo e devolução</a>
        <a href="armario.php">Armários</a>
        <a href="cadastro.php">Cadastro de usuário</a>
        <a class="ativo" href="consulta.php">Consultar exemplares</a>
    </nav>

    <div class="secao-busca">
        <label for="pesquisa">Pesquisar Exemplar</label>
        <div class="input-busca-container">
            <input type="text" id="pesquisa" placeholder="Digite aqui o nome do livro...">
            <button class="btn-lupa"><img src="imgs/lupa.png" alt="Buscar"></button>
        </div>
    </div>

    <div class="lista-resultados">
        
        <div class="card-exemplar">
            <div class="info-exemplar">
                <h3>Dom Quixote</h3>
                <p class="autor">Miguel de Cervantes Saavedra</p>
                <div class="avaliacao-stars">
                    <span class="estrelas">Estrelas(?)</span>
                    <span class="nota-texto">5,0 (Ótimo)</span>
                </div>
                
                <ul class="detalhes-lista">
                    <li><strong>Tipo do Material:</strong> Livros</li>
                    <li><strong>Edição:</strong> 1. ed.</li>
                    <li><strong>Ano de Publicação:</strong> 2019</li>
                </ul>
                
                <p class="localizacao">Localização: <a href="#"><img src=" "> Consultar</a></p>
            </div>
            <div class="capa-exemplar">
                <img src="imgs/dom-quixote.jpg" alt="Capa do Livro">
            </div>
        </div>

        <div class="card-exemplar">
            <div class="info-exemplar">
                <h3>A Biblioteca da Meia-Noite</h3>
                <p class="autor">Matt Haig</p>
                <div class="avaliacao-stars">
                    <span class="estrelas">Estrelas(?)</span>
                    <span class="nota-texto">4,0 (Muito bom)</span>
                </div>
                
                <ul class="detalhes-lista">
                    <li><strong>Tipo do Material:</strong> Livros</li>
                    <li><strong>Edição:</strong> 1. ed.</li>
                    <li><strong>Ano de Publicação:</strong> 2021</li>
                </ul>
                
                <p class="localizacao">Localização: <a href="#"><img src=""> Consultar</a></p>
            </div>
            <div class="capa-exemplar">
                <img src="imgs/biblioteca-meia-noite.jpg" alt="Capa do Livro">
            </div>
        </div>

    </div>
</div>

</body>
</html>