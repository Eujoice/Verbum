<!--Tela de Detalhes de Livro-->
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="detalheslivro.css">
</head>
<body class="body-det-livro">
    <div class="container-dl-cheio">

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
                <a class="nav-item" href="dpessoais.php">
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
                    <input type="text" id="pesquisa" name="pesquisa" placeholder="O que você quer ler?">
                </div>
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
                <a class="tab" href="#">Títulos pendentes</a>
            </nav>

            <!-- Breadcrumb -->
            <div class="breadcrumb-dl">
                <a href="acervo.php">Acervo</a>
                <span>›</span>
                <span id="breadcrumb-titulo">Carregando...</span>
            </div>

            <!-- Bloco principal: capa + info -->
            <div class="div-livro">

                <!-- Coluna da capa -->
                <div class="capa-wrapper">
                    <img class="capa-livro-det" id="capa-livro-det" src="" alt="">
                </div>

                <!-- Coluna de informações -->
                <div class="info-livro" id="info-livro">
                    <span class="tag-genero" id="tag-genero">Literatura</span> 
                    <h2 class="ttl-livro" id="ttl-livro"></h2>
                    <p class="autor-livro" id="autor-livro"></p>

                    <p class="resenha" id="resenha">
                        <span id="pontos">...</span>
                        <span id="mais" style="display:none"></span>
                        <button onclick="leiaMais()" id="btnLerMais">Leia mais</button>
                    </p>

                    <div id="div-detalhes">
    
                        <div class="item">
                            <span class="ttl">PUBLICAÇÃO</span>
                            <span id="publicacao"></span>
                        </div>

                        <div class="item">
                            <span class="ttl">EXEMPLARES</span>
                            <span id="exemplares"></span>
                        </div>

                        <div class="item">
                            <span class="ttl">EDITORA</span>
                            <span id="editora"></span>
                        </div>

                        <div class="item">
                            <span class="ttl">PÁGINAS</span>
                            <span id="paginas"></span>
                        </div>

                        <div class="item">
                            <span class="ttl">ISBN</span>
                            <span id="isbn"></span>
                        </div>

                        <div class="item">
                            <span class="ttl">STATUS</span>
                            <span id="status"></span>
                        </div>

                    </div>

                    <div class="div-reserva">
                        <button class="btn-reservar" onclick="reservarLivro()">
                            Reservar exemplar
                        </button>
                        <button class="btn-favorito" id="btnFavorito" onclick="toggleFavorito()" title="Salvar nos favoritos">♡</button>
                    </div>
                </div>
            </div>

            <!-- Cards inferiores: sinopse completa + detalhes do acervo -->
            <div class="secao-inferior">
                <div class="card-info">
                    <p class="card-info-titulo">Sinopse completa</p>
                    <p class="card-sinopse-texto" id="sinopse-completa">Carregando sinopse...</p>
                </div>
                <div class="card-info">
                    <p class="card-info-titulo">Informações do acervo</p>
                    <div class="detalhe-row">
                        <span class="detalhe-key">Coleção</span>
                        <span class="detalhe-val" id="det-colecao">—</span>
                    </div>
                    <div class="detalhe-row">
                        <span class="detalhe-key">Localização</span>
                        <span class="detalhe-val" id="det-localizacao">—</span>
                    </div>
                    <div class="detalhe-row">
                        <span class="detalhe-key">Exemplares totais</span>
                        <span class="detalhe-val" id="det-exemplares-total">—</span>
                    </div>
                    <div class="detalhe-row">
                        <span class="detalhe-key">Avaliação dos leitores</span>
                        <span class="detalhe-val"  id="det-avaliacao">—</span>
                    </div>
                    <div class="detalhe-row">
                        <span class="detalhe-key">Adicionado ao acervo</span>
                        <span class="detalhe-val" id="det-adicionado">—</span>
                    </div>
                    <div class="detalhe-row">
                        <span class="detalhe-key"> Idioma original</span>
                        <span class="detalhe-val" id="det-idioma">—</span>
                    </div>
                     <div class="detalhe-row">
                        <span class="detalhe-key">Prazo de empréstimo</span>
                        <span class="detalhe-val">7 dias úteis</span>
                    </div>
                </div>
            </div>

            <!-- Você também pode gostar -->
            <div class="secao-similares">
                <h3 class="secao-titulo">Você também pode gostar</h3>
                <div class="similares-grid" id="similares-grid">
                    <!-- Skeletons enquanto carrega -->
                    <div class="similar-skeleton"><div class="sk-capa"></div><div class="sk-linha"></div><div class="sk-linha curta"></div></div>
                    <div class="similar-skeleton"><div class="sk-capa"></div><div class="sk-linha"></div><div class="sk-linha curta"></div></div>
                    <div class="similar-skeleton"><div class="sk-capa"></div><div class="sk-linha"></div><div class="sk-linha curta"></div></div>
                    <div class="similar-skeleton"><div class="sk-capa"></div><div class="sk-linha"></div><div class="sk-linha curta"></div></div>
                </div>
            </div>

        </div><!-- /container-acervo -->
    </div><!-- /container-dl-cheio -->

    <!-- Toast de feedback -->
    <div class="toast-dl" id="toastDl"></div>

    <script src="script.js"></script>
    <script typw="module" src="busca_detalhes.js"></script>
    <script type="module" src="acervo_logic.js"></script>
    <script src="script-acervo.js"></script>

    <script>
        // ─── Breadcrumb ───────────────────────────────────────────────

        function formatarGenero(texto) {
            return texto.toLowerCase().replace(/^\w/, c => c.toUpperCase());
        }   

        const observarInfos = setInterval(() => {
    const ttl = document.getElementById('ttl-livro');
    const genero = document.getElementById('tag-genero');

    if (
        ttl && ttl.innerText.trim() !== '' &&
        genero && genero.innerText.trim() !== ''
    ) {
        const titulo = ttl.innerText;
        const generoTexto = formatarGenero(genero.innerText);

        document.getElementById('breadcrumb-titulo').innerHTML = `
            <a href="genero.php?g=${encodeURIComponent(generoTexto)}">
                ${generoTexto}
            </a>
            <span>›</span>
            <span>${titulo}</span>
        `;

        clearInterval(observarInfos);
    }
}, 300);

        // ─── Toast ───────────────────────────────────────────────────
        function showToast(msg) {
            const t = document.getElementById('toastDl');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        // ─── Reservar ────────────────────────────────────────────────
        function reservarLivro() {
            showToast('✓ Reserva realizada! Retire em até 2 dias úteis.');
        }

        // ─── Favorito ────────────────────────────────────────────────
        let favoritoAtivo = false;
        function toggleFavorito() {
            favoritoAtivo = !favoritoAtivo;
            const btn = document.getElementById('btnFavorito');
            btn.textContent = favoritoAtivo ? '♥' : '♡';
            btn.classList.toggle('ativo', favoritoAtivo);
            showToast(favoritoAtivo ? '♥ Adicionado aos favoritos!' : 'Removido dos favoritos.');
        }

        // ─── Você também pode gostar ─────────────────────────────────
        // Aguarda o acervo_logic.js terminar de expor os dados e chama
        // a função de similares. 
        //
        // Estratégia: busca até 8 livros do acervo e exibe 5 aleatórios,
        // excluindo o livro atual.

        window.renderizarSimilares = function(outrosLivros, idAtual) {
            const grid = document.getElementById('similares-grid');
            if (!grid) return;

            // Sorteia 5 entre os similares encontrados
            const selecionados = outrosLivros
                .sort(() => Math.random() - 0.5)
                .slice(0, 5);

    

            if (selecionados.length === 0) {
                grid.innerHTML = '<p style="color:var(--texto-muted);font-size:14px;">Nenhuma sugestão disponível no momento.</p>';
                return;
            }

            grid.innerHTML = selecionados.map(livro => {

                const status = (livro.status || "")
                    .toLowerCase()
                    .trim()
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "");

                return `
                    <a class="similar-card" href="detalheslivro.php?id=${livro.id}">
                        <div class="similar-capa-wrap">
                            <img src="${livro.capa}" alt="${livro.titulo}" loading="lazy">
                        </div>
                        <div class="similar-info">
                            <p class="similar-titulo">${livro.titulo}</p>
                            <p class="similar-autor">${livro.autor}</p>
                            <span class="similar-status ${status === 'disponivel' ? 'disponivel' : 'emprestado'}">
                                <span class="similar-dot"></span>
                                ${status === 'disponivel' ? 'Disponível' : 'Emprestado'}
                            </span>
                        </div>
                    </a>
                `;
            }).join('');
        };
    </script>
</body>
</html>