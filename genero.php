<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit();
}
$genero_id   = isset($_GET['id'])   ? htmlspecialchars($_GET['id'])   : '';
$genero_nome = isset($_GET['nome']) ? htmlspecialchars($_GET['nome']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verbum | Gêneros</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Milonga&family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styleacervo.css">
    <link rel="stylesheet" href="style-genero.css">
</head>
<body class="body-acervo">

    <div class="overlay-menu" id="overlayMenu" onclick="fecharMenu()"></div>
    <nav class="menu-lateral" id="menuLateral">
    <div class="sb-profile">
        <div class="sb-avatar">        
        <div class="sb-avatar-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                
            </div>
        </div>
        <div class="sb-name"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></div>
        <div class="sb-mat">Matrícula: <?php echo htmlspecialchars($_SESSION['usuario_matricula']); ?></div>
        <div class="sb-divider"></div>
    </div>
    <div class="sb-nav">
        <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador'): ?>
        <a class="nav-item nav-admin" href="consulta.php">
            <div class="nav-ic">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="#6b4800">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            Acessar Painel
        </a>
        <?php endif; ?>
        <a class="nav-item" href="titulos-pendentes.php">
            <div class="nav-ic">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="#6C9467">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14l4-4h12c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                </svg>
            </div>
            Títulos Pendentes
        </a>
        <a class="nav-item" href="historico.php">
            <div class="nav-ic">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="#6C9467">
                    <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                </svg>
            </div>
            Histórico de Empréstimos
        </a>
        <a class="nav-item" href="dpessoais.php">
            <div class="nav-ic">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="#6C9467">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
            Dados Pessoais
        </a>
        <a class="nav-item" href="favoritos.php">
            <div class="nav-ic">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="#6C9467">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
            </div>
            Favoritos e Avaliações
        </a>
        <a class="nav-item nav-sair" href="logout.php">
            <div class="nav-ic">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="#fff">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
            </div>
            Sair
        </a>
    </div>
</nav>

    <div class="container-acervo">
        <header class="header">
            <div class="logo"><a href="acervo.php">Verbum</a></div>
            <div class="busca">
                <svg class="icone-lupa" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" stroke="#9aaa98" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                <input type="text" placeholder="O que você quer ler?">
            </div>
            <div class="icones">
                <span class="favoritos">
                    <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    Favoritos
                </span>
                <button class="hambtn" id="hambtn" onclick="toggleMenu()">
                    <div class="bar"></div><div class="bar"></div><div class="bar"></div>
                </button>
            </div>
        </header>

        <nav class="menu-tabs">
            <a class="tab" href="acervo.php">Acervo</a>
            <a class="tab ativo" href="genero.php">Gênero</a>
            <a class="tab" href="historico.php">Histórico</a>
            <a class="tab" href="#">Títulos pendentes</a>
        </nav>

        <!-- TELA 1: GRID DE GÊNEROS -->
        <section class="pg-generos" id="tela-generos" <?php if($genero_id) echo 'style="display:none"'; ?>>
            <div class="pg-header">
                <h1>Explorar por Gênero</h1>
                <p class="pg-sub">Escolha um gênero para ver todos os títulos disponíveis</p>
            </div>
            <div class="generos-grid" id="generos-grid">
                <div class="genero-skeleton"></div>
                <div class="genero-skeleton"></div>
                <div class="genero-skeleton"></div>
                <div class="genero-skeleton"></div>
            </div>
        </section>

        <!-- TELA 2: LIVROS DO GÊNERO -->
        <section class="pg-livros" id="tela-livros" <?php if(!$genero_id) echo 'style="display:none"'; ?>>
            <button class="btn-voltar" onclick="voltarGeneros()">
                <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6" stroke="#6C9467" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Voltar aos gêneros
            </button>
            <div class="livros-header">
                <div class="genero-badge" id="badge-genero"></div>
                <div>
                    <h2 id="titulo-genero"><?php echo $genero_nome; ?></h2>
                    <p class="genero-desc" id="desc-genero"></p>
                </div>
            </div>
            <div class="filtros-bar">
                <span class="filtros-label">Ordenar por:</span>
                <button class="filtro-pill ativo" data-filtro="titulo">A–Z</button>
                <button class="filtro-pill" data-filtro="avaliacao">Melhor avaliados</button>
                <button class="filtro-pill" data-filtro="recente">Mais recentes</button>
            </div>
            <p class="livros-count" id="livros-count"></p>
            <div class="livros-grid" id="livros-grid">
                <div class="livro-skeleton"></div>
                <div class="livro-skeleton"></div>
                <div class="livro-skeleton"></div>
                <div class="livro-skeleton"></div>
            </div>
        </section>
    </div>

    <script>
        var GENERO_INICIAL = '<?php echo $genero_id; ?>';
        var NOME_INICIAL   = '<?php echo $genero_nome; ?>';
    </script>
    <script src="script-acervo.js"></script>
    <script type="module" src="genero_logic.js"></script>
</body>
</html>