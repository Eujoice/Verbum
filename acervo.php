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
    <title>Verbum | Acervo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Milonga&family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styleacervo.css"
    
    >
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
            <div class="sb-name" id="nomeUsuario"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></div>
            <div class="sb-mat" id="matriculaUsuario">Matrícula: <?php echo htmlspecialchars($_SESSION['usuario_matricula']); ?></div>
            <div class="sb-divider"></div>
        </div>
        <div class="sb-nav">
            <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador'): ?>
            <a class="nav-item nav-admin" href="consulta.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
                Acessar Painel
            </a>
            <?php endif; ?>
            <a class="nav-item" href="titulos-pendentes.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14l4-4h12c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg></div>
                Títulos Pendentes
            </a>
            <a class="nav-item" href="historico.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg></div>
                Histórico de Empréstimos
            </a>

            <!-- ↓ Agora abre o overlay em vez de navegar para outra página -->
            <a class="nav-item" href="javascript:void(0)" onclick="abrirDpOverlay(); fecharMenu();">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
                Dados Pessoais
            </a>

            <a class="nav-item" href="favoritos.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></div>
                Favoritos e Avaliações
            </a>
            <a class="nav-item nav-sair" href="logout.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg></div>
                Sair
            </a>
        </div>
    </nav>

    <div class="container-acervo">
        <header class="header">
            <div class="header-left">
                    <div class="logo"><a href="acervo.php">Verbum</a></div>
                    <img class="logo-vb" src="imgs/ig_aviao.png" alt="Logo">
                </div>
            <div class="busca">
                <svg class="icone-lupa" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" stroke="#9aaa98" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                <input type="text" id="pesquisa" placeholder="O que você quer ler?">            </div>
            <div class="icones">
                <button class="hambtn" id="hambtn" onclick="toggleMenu()">
                    <div class="bar"></div>
                    <div class="bar"></div>
                    <div class="bar"></div>
                </button>
            </div>
        </header>

        <nav class="menu-tabs">
            <a class="tab ativo" href="acervo.php">Acervo</a>
            <a class="tab" href="genero.php">Gênero</a>
            <a class="tab" href="historico.php">Histórico</a>
            <a class="tab" href="titulos-pendentes.php">Títulos pendentes</a>
        </nav>

        <section class="banner-section">
            <div class="carousel" id="carousel">
                <div class="slides" id="slides">
                    <div class="slide slide-1">
                        <img class="slide-img" src="imgs/banner-percy.png" alt="Percy Jackson e os Olimpianos">
                    </div>
                    <div class="slide slide-2">
                        <img class="slide-img" src="imgs/crime.jpg" alt="Crime e Castigo">
                    </div>
                    <div class="slide slide-3">
                        <img class="slide-img" src="imgs/met.png" alt="A Metamorfose">
                    </div>
                    <div class="slide slide-4">
                        <img class="slide-img" src="imgs/bibliotb.jpg" alt="Biblioteca da meia noite">
                    </div>
                    <div class="slide slide-5">
                        <img class="slide-img" src="imgs/vds.png" alt="Vidas Secas">
                    </div>
                </div>
                <button class="carr-prev" onclick="mudarSlide(-1)">
                    <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button class="carr-next" onclick="mudarSlide(1)">
                    <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <div class="dots" id="dots">
                <button class="dot active" onclick="irParaSlide(0)"></button>
                <button class="dot" onclick="irParaSlide(1)"></button>
                <button class="dot" onclick="irParaSlide(2)"></button>
                <button class="dot" onclick="irParaSlide(3)"></button>
                <button class="dot" onclick="irParaSlide(4)"></button>
            </div>
        </section>

        <section class="populares">
            <div class="sec-header">
                <h2>Populares</h2>
                <a href="populares_lista.php" class="ver-todos">Ver todos →</a>
            </div>
            <div class="lista-livros" id="lista-populares"></div>
        </section>

        <section class="classicos">
            <div class="sec-header">
                <h2>Clássicos</h2>
                <a href="classicos_lista.php" class="ver-todos">Ver todos →</a>
            </div>
            <div class="lista-livros" id="lista-classicos"></div>
        </section>
    </div>

    <!-- ↓ Overlay de Dados Pessoais incluído aqui -->
    <?php include 'dp_modal.php'; ?>
    <script src="busca_detalhes.js"></script>
    <script src="script-acervo.js"></script>
    <script type="module" src="acervo_logic.js"></script>
</body>
</html>