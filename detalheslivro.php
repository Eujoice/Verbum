<!--Tela de Detalhes de Livro-->
<?php
ini_set('display_errors', 0);
error_reporting(0);
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) { 
    header("Location: index.php");
    exit();
}

// ── Busca avaliação do usuário server-side ────────────────────────────────────
$_avaliacao_usuario = null;
$_avaliacao_media   = 0.0;
$_total_avaliacoes  = 0;

function _fsGetSimples($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $r = curl_exec($ch);
    curl_close($ch);
    return json_decode($r, true);
}

$_livro_id_url = isset($_GET['id']) ? trim($_GET['id']) : '';
if (!empty($_livro_id_url)) {
    $projeto_id = 'verbum-bd';
    $base = "https://firestore.googleapis.com/v1/projects/$projeto_id/databases/(default)/documents";
    $matricula = $_SESSION['usuario_matricula'];

    // Nota do usuário neste livro
    $avalDoc = _fsGetSimples("$base/obras/$_livro_id_url/avaliacoes/$matricula");
    if (!isset($avalDoc['error']) && isset($avalDoc['fields']['nota'])) {
        $_avaliacao_usuario = floatval(
            $avalDoc['fields']['nota']['doubleValue'] ??
            $avalDoc['fields']['nota']['integerValue'] ?? 0
        );
    }

    // Média geral e total de avaliações da obra
    $obraDoc = _fsGetSimples("$base/obras/$_livro_id_url");
    if (!isset($obraDoc['error']) && isset($obraDoc['fields'])) {
        $f = $obraDoc['fields'];
        $_avaliacao_media  = floatval($f['avaliacao_media']['doubleValue'] ?? $f['avaliacao_media']['integerValue'] ?? 0);
        $_total_avaliacoes = intval($f['total_avaliacoes']['integerValue'] ?? 0);
    }
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
                    Favoritos
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

                    <!-- Widget de avaliação por estrelas — abaixo do resumo -->
                    <div class="avaliar-inline">
                        <span class="avaliar-inline-titulo">Avalie esta obra</span>
                        <div class="avaliar-inline-corpo">
                            <div class="rating-input" id="rating-input">
                                <span class="star-group">
                                    <span class="star-half" data-val="0.5">★</span>
                                    <span class="star-full" data-val="1">★</span>
                                </span>
                                <span class="star-group">
                                    <span class="star-half" data-val="1.5">★</span>
                                    <span class="star-full" data-val="2">★</span>
                                </span>
                                <span class="star-group">
                                    <span class="star-half" data-val="2.5">★</span>
                                    <span class="star-full" data-val="3">★</span>
                                </span>
                                <span class="star-group">
                                    <span class="star-half" data-val="3.5">★</span>
                                    <span class="star-full" data-val="4">★</span>
                                </span>
                                <span class="star-group">
                                    <span class="star-half" data-val="4.5">★</span>
                                    <span class="star-full" data-val="5">★</span>
                                </span>
                            </div>
                            <input type="hidden" id="hidden-nota" value="">
                            <button id="btnConfirmar" class="btn-confirmar-avaliacao" onclick="enviarAvaliacao()" disabled>
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Confirmar
                            </button>
                        </div>
                    </div>

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
                        <span class="detalhe-key">Idioma original</span>
                        <span class="detalhe-val" id="det-idioma">—</span>
                    </div>
                    <div class="detalhe-row">
                        <span class="detalhe-key">Tradução</span>
                        <span class="detalhe-val" id="det-traducao">—</span>
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
    <script type="module" src="busca_detalhes.js"></script>
    <script type="module" src="acervo_logic.js"></script>
    <script src="script-acervo.js"></script>

    <!-- ─── Toast global (acessível por todos os scripts) ─────────────────── -->
    <script>
        function showToast(msg) {
            const t = document.getElementById('toastDl');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }
    </script>

    <!-- ─── Avaliação por estrelas ─────────────────────────────────────── -->
    <script>
        // IIFE: interação com as estrelas (hover + click + meia estrela)
        (function () {
            const container = document.getElementById('rating-input');
            if (!container) return;

            const allSpans    = container.querySelectorAll('[data-val]');
            const hiddenInput = document.getElementById('hidden-nota');
            const btnConfirmar = document.getElementById('btnConfirmar');
            let notaSelecionada = 0;

            function pintar(valor, modo) {
                allSpans.forEach(s => {
                    const v = parseFloat(s.dataset.val);
                    if (v <= valor) s.classList.add(modo);
                    else            s.classList.remove(modo);
                });
            }

            allSpans.forEach(span => {
                span.addEventListener('mouseenter', () => {
                    allSpans.forEach(s => s.classList.remove('hover'));
                    pintar(parseFloat(span.dataset.val), 'hover');
                });
                span.addEventListener('mouseleave', () => {
                    allSpans.forEach(s => s.classList.remove('hover'));
                    if (notaSelecionada > 0) pintar(notaSelecionada, 'on');
                });
                span.addEventListener('click', () => {
                    notaSelecionada = parseFloat(span.dataset.val);
                    allSpans.forEach(s => s.classList.remove('on', 'hover'));
                    pintar(notaSelecionada, 'on');
                    hiddenInput.value = notaSelecionada;
                    btnConfirmar.disabled = false;
                    btnConfirmar.style.opacity = '1';
                });
            });

            // Expõe pintar para uso externo (pré-preenche nota já salva)
            window._pintarEstrelas = function(valor) {
                notaSelecionada = valor;
                allSpans.forEach(s => s.classList.remove('on', 'hover'));
                pintar(valor, 'on');
                hiddenInput.value = valor;
                btnConfirmar.disabled = false;
                btnConfirmar.style.opacity = '1';
                btnConfirmar.innerText = 'Atualizar Avaliação';
            };
        })();

        // Envia a avaliação ao servidor
        async function enviarAvaliacao() {
            const nota = parseFloat(document.getElementById('hidden-nota').value);
            const btn  = document.getElementById('btnConfirmar');
            const urlParams = new URLSearchParams(window.location.search);
            const livroId   = urlParams.get('id');

            if (!nota || nota <= 0 || nota > 5) return showToast('Selecione uma nota antes de confirmar.');
            if (!livroId) return showToast('Erro: ID do livro não encontrado.');

            btn.disabled  = true;
            btn.innerText = 'Enviando...';

            const formData = new FormData();
            formData.append('acao', 'avaliar');   // ← campo obrigatório pelo PHP
            formData.append('livro_id', livroId);
            formData.append('nota', nota);

            try {
                const response = await fetch('processar_avaliacao.php', { method: 'POST', body: formData });
                const resultado = await response.json();

                if (resultado.sucesso) {
                    showToast('★ Avaliação enviada com sucesso!');
                    btn.innerText = 'Atualizar Avaliação';
                    btn.style.backgroundColor = '#27ae60';
                    btn.disabled = false;
                    if (resultado.nova_media !== undefined) {
                        const detAval = document.getElementById('det-avaliacao');
                        if (detAval) {
                            const m     = resultado.nova_media;
                            const total = resultado.total_avaliacoes;
                            const estrelas = '★'.repeat(Math.floor(m)) + (m % 1 >= 0.5 ? '½' : '');
                            detAval.textContent = m.toFixed(1) + ' ' + estrelas + ' (' + total + (total > 1 ? ' avaliações' : ' avaliação') + ')';
                        }
                    }
                } else {
                    showToast('✕ ' + resultado.mensagem);
                    btn.disabled  = false;
                    btn.innerText = 'Confirmar Avaliação';
                }
            } catch (e) {
                console.error('Erro ao enviar avaliação:', e);
                showToast('Erro: ' + e.message);
                btn.disabled  = false;
                btn.innerText = 'Confirmar Avaliação';
            }
        }

        // Carrega avaliação já existente ao abrir a página (via PHP server-side)
        function carregarAvaliacaoUsuario() {
            const notaUsuario     = <?php echo json_encode($_avaliacao_usuario ?? null); ?>;
            const media           = <?php echo json_encode((float)($_avaliacao_media ?? 0)); ?>;
            const totalAvaliacoes = <?php echo json_encode((int)($_total_avaliacoes ?? 0)); ?>;

            // Pré-preenche estrelas se o usuário já avaliou
            if (notaUsuario !== null && notaUsuario > 0) {
                const aguardar = setInterval(() => {
                    if (typeof window._pintarEstrelas === 'function') {
                        clearInterval(aguardar);
                        window._pintarEstrelas(notaUsuario);
                    }
                }, 100);
            }

            // Atualiza "Avaliação dos leitores" no card de detalhes
            const detAval = document.getElementById('det-avaliacao');
            if (detAval) {
                if (totalAvaliacoes > 0 && media > 0) {
                    const estrelas = '★'.repeat(Math.floor(media)) + (media % 1 >= 0.5 ? '½' : '');
                    detAval.textContent = media.toFixed(1) + ' ' + estrelas + ' (' + totalAvaliacoes + (totalAvaliacoes > 1 ? ' avaliações' : ' avaliação') + ')';
                } else {
                    detAval.textContent = 'Sem avaliações ainda';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', carregarAvaliacaoUsuario);
    </script>

    <script type="module">
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

        // Monitorar o status do livro e a existência de reserva do usuário
        const observarStatusEReserva = setInterval(async () => {
            const statusEl = document.getElementById('status');
            const btnReservar = document.querySelector('.btn-reservar');
            const urlParams = new URLSearchParams(window.location.search);
            const livroId = urlParams.get('id');

            if (statusEl && statusEl.innerText.trim() !== '' && livroId) {
                const status = statusEl.innerText.toLowerCase().trim();
                const matricula = "<?php echo $_SESSION['usuario_matricula']; ?>";

                try {
                    const resp = await fetch(`consultar_minha_reserva.php?livro_id=${livroId}`);
                    const dados = await resp.json();

                    if (dados.jaReservado) {
                        btnReservar.disabled = true;
                        if (dados.tipo === 'Direta') {
                            btnReservar.innerHTML = 'Reservado';
                            btnReservar.style.backgroundColor = '#27ae60';
                        } else {
                            btnReservar.innerHTML = `Na fila (Posição: ${dados.posicao}º)`;
                            btnReservar.style.backgroundColor = '#f39c12';
                        }
                        clearInterval(observarStatusEReserva);
                        return;
                    }

                    if (status === 'emprestado') {
                        btnReservar.innerHTML = 'Entrar na fila de espera';
                        btnReservar.style.backgroundColor = '#f39c12';
                    } else if (status === 'disponivel') {
                        btnReservar.innerHTML = 'Reservar exemplar';
                        btnReservar.style.backgroundColor = '';
                    } else if (status === 'reservado') {
                        btnReservar.innerHTML = 'Entrar na fila de espera';
                        btnReservar.style.backgroundColor = '#f39c12';
                    }

                } catch (e) {
                    console.error("Erro ao validar reserva existente", e);
                }
                
                clearInterval(observarStatusEReserva);
            }
        }, 500);

        // ─── Reservar ────────────────────────────────────────────────
        async function reservarLivro() {
            const urlParams = new URLSearchParams(window.location.search);
            const livroId = urlParams.get('id');
            const btnReservar = document.querySelector('.btn-reservar');
            
            if (!livroId) return showToast("Erro: ID do livro não encontrado.");

            btnReservar.disabled = true;
            btnReservar.innerText = "Processando...";

            const formData = new FormData();
            formData.append('acao', 'reservar');
            formData.append('livro_id', livroId);

            try {
                const response = await fetch('processar_reserva.php', {
                    method: 'POST',
                    body: formData
                });
                
                const resultado = await response.json();
                
                if (resultado.sucesso) {
                    showToast('✓ ' + resultado.mensagem);

                    if (resultado.tipo === 'Direta') {
                        btnReservar.innerText = 'Reservado';
                        btnReservar.style.backgroundColor = '#27ae60';
                    } else {
                        btnReservar.innerText = `Sua posição na fila: ${resultado.posicao}º`;
                        btnReservar.style.backgroundColor = '#f39c12';
                    }
                    
                    setTimeout(() => location.reload(), 3000);
                } else {
                    showToast('✕ ' + resultado.mensagem);
                    btnReservar.disabled = false;
                    btnReservar.innerText = "Reservar exemplar";
                }
            } catch (e) {
                console.error("Erro na reserva:", e);
                showToast("Erro ao conectar com o servidor.");
                btnReservar.disabled = false;
            }
        }

        // ─── Favorito ────────────────────────────────────────────────
        import('https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js').then(({ collection, query, where, getDocs, addDoc, deleteDoc, doc, serverTimestamp }) => {
            import('./firebase-config.js').then(({ db }) => {

                const MATRICULA = "<?php echo htmlspecialchars($_SESSION['usuario_matricula']); ?>";
                const urlP = new URLSearchParams(window.location.search);
                const LIVRO_ID = urlP.get('id');
                const btn = document.getElementById('btnFavorito');

                let favDocId = null;

                async function verificarFavorito() {
                    if (!LIVRO_ID) return;
                    const q = query(
                        collection(db, 'favoritos'),
                        where('usuario_id', '==', MATRICULA),
                        where('obra_id', '==', LIVRO_ID)
                    );
                    const snap = await getDocs(q);
                    if (!snap.empty) {
                        favDocId = snap.docs[0].id;
                        btn.textContent = '♥';
                        btn.classList.add('ativo');
                    }
                }

                window.toggleFavorito = async function () {
                    if (!LIVRO_ID) return;
                    btn.disabled = true;

                    try {
                        if (favDocId) {
                            await deleteDoc(doc(db, 'favoritos', favDocId));
                            favDocId = null;
                            btn.textContent = '♡';
                            btn.classList.remove('ativo');
                            showToast('Removido dos favoritos.');
                        } else {
                            const novoDoc = await addDoc(collection(db, 'favoritos'), {
                                usuario_id: MATRICULA,
                                obra_id: LIVRO_ID,
                                salvo_em: serverTimestamp()
                            });
                            favDocId = novoDoc.id;
                            btn.textContent = '♥';
                            btn.classList.add('ativo');
                            showToast('♥ Adicionado aos favoritos!');
                        }
                    } catch (e) {
                        console.error('Erro ao atualizar favorito:', e);
                        showToast('Erro ao salvar favorito.');
                    } finally {
                        btn.disabled = false;
                    }
                };

                verificarFavorito();
            });
        });

        // ─── Você também pode gostar ─────────────────────────────────
        window.renderizarSimilares = function(outrosLivros, idAtual) {
            const grid = document.getElementById('similares-grid');
            if (!grid) return;

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