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
    <title>Verbum | Títulos Pendentes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Milonga&family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styleacervo.css">
    <link rel="stylesheet" href="style-pendentes.css">
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
                <div class="nav-ic"><svg viewBox="0 0 24 24" width="15" height="15" fill="#6b4800"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div>
                Acessar Painel
            </a>
            <?php endif; ?>
            <a class="nav-item nav-ativo" href="titulos-pendentes.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24" width="15" height="15" fill="#6C9467"><path d="M19 3H5c-1.1 0-2 .9-2 2v14l4-4h12c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg></div>
                Títulos Pendentes
            </a>
            <a class="nav-item" href="historico.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24" width="15" height="15" fill="#6C9467"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg></div>
                Histórico de Empréstimos
            </a>
            <a class="nav-item" href="javascript:void(0)" onclick="abrirDpOverlay(); fecharMenu();">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
                Dados Pessoais
            </a>
            <a class="nav-item" href="favoritos.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24" width="15" height="15" fill="#6C9467"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></div>
                Favoritos e Avaliações
            </a>
            <a class="nav-item nav-sair" href="logout.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24" width="15" height="15" fill="#fff"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg></div>
                Sair
            </a>
        </div>
    </nav>

    <div class="container-acervo">

 <header class="header" style="box-sizing: border-box; width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; overflow: visible;">
    
    <div class="header-left" style="display: flex; align-items: center; flex: 1; justify-content: flex-start; flex-shrink: 0;">
        <div class="logo"><a href="acervo.php">Verbum</a></div>
        <img class="logo-vb" src="imgs/ig_aviao.png" alt="Logo">
    </div>

    <div class="busca" style="flex: 2; max-width: 500px; display: flex; align-items: center; justify-content: center; margin: 0 15px;">
        <svg class="icone-lupa" viewBox="0 0 24 24" style="flex-shrink: 0;">
            <path d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" stroke="#9aaa98" stroke-width="2" fill="none" stroke-linecap="round"/>
        </svg>
        <input type="text" id="pesquisa" placeholder="O que você quer ler?" style="width: 100%;">
    </div>

    <div class="icones" style="display: flex; align-items: center; gap: 20px; flex: 1; justify-content: flex-end; flex-shrink: 0;">
        
        <div class="notificacao-container" id="notificacaoContainer" style="position: relative; display: flex; align-items: center; color: #ffffff;">
            <button class="notificacao-btn" id="notificacaoBtn" onclick="toggleDropdownNotificacoes(event)" style="background: none; border: none; cursor: pointer; color: #ffffff; padding: 4px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notificacao-badge" id="notificacaoBadge" style="display: none; position: absolute; top: -2px; right: -2px; background-color: #ff4d4d; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold; line-height: 1;">0</span>
            </button>
            
            <div class="notificacao-dropdown" id="notificacaoDropdown" style="display: none; position: absolute; right: 0; top: 45px; background: white; border: 1px solid #ccc; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); border-radius: 8px; width: 300px; z-index: 1000; color: #333;">
                <div class="notificacao-header" style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;">
                    <h3 style="margin: 0; font-size: 16px;">Notificações</h3>
                </div>
                <div class="notificacao-lista" id="notificacaoLista" style="max-height: 250px; overflow-y: auto; padding: 10px;">
                    <div class="notificacao-vazia" style="text-align: center; color: #888; padding: 15px 0;">Nenhuma notificação encontrada.</div>
                </div>
            </div>
        </div>

        <div class="user-profile" style="display: flex; align-items: center; gap: 10px; color: #ffffff;">
            <span class="user-name" style="font-size: 15px; font-weight: 500; white-space: nowrap; color: #ffffff; display: inline-block; margin-right: 2px;">
                <?php 
                    $nome_completo = trim($_SESSION['usuario_nome']);
                    $partes_nome = explode(' ', $nome_completo);
                    $primeiro_nome = $partes_nome[0];
                    echo "Olá, " . htmlspecialchars($primeiro_nome) . "!";
                ?>
            </span>
            <div class="user-avatar" style="width: 34px; height: 34px; min-width: 34px; min-height: 34px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.2); flex-shrink: 0;">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="#ffffff">
                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                </svg>
            </div>
        </div>

        <button class="hambtn" id="hambtn" style="flex-shrink: 0;">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </button>
    </div>
</header>

        <nav class="menu-tabs">
            <a class="tab" href="acervo.php">Acervo</a>
            <a class="tab" href="genero.php">Gênero</a>
            <a class="tab" href="historico.php">Histórico</a>
            <a class="tab ativo" href="titulos-pendentes.php">Títulos pendentes</a>
        </nav>

        <section class="pend-body">

            <!-- Cabeçalho + slots -->
            <div class="pend-top">
                <div class="pend-titulo">
                    <h1>Títulos Pendentes</h1>
                    <p>Você pode ter até 3 livros emprestados ao mesmo tempo</p>
                </div>
                <div class="slots-wrap" id="slots-wrap">
                    <div class="slot-loading"></div>
                    <div class="slot-loading"></div>
                    <div class="slot-loading"></div>
                </div>
            </div>

            <!-- Seção: Empréstimos ativos -->
            <div class="pend-secao">
                <div class="secao-header">
                    <span class="secao-titulo">Em mãos</span>
                    <span class="secao-badge badge-ativo" id="count-ativos">—</span>
                </div>
                <div class="pend-lista" id="lista-ativos">
                    <div class="emp-skeleton"></div>
                    <div class="emp-skeleton"></div>
                </div>
            </div>

            <!-- Seção: Atrasados -->
            <div class="pend-secao" id="secao-atrasados" style="display:none">
                <div class="secao-header">
                    <span class="secao-titulo secao-titulo-atrasado">Atrasados</span>
                    <span class="secao-badge badge-atrasado" id="count-atrasados">—</span>
                </div>
                <div class="pend-lista" id="lista-atrasados"></div>
            </div>

        </section>
    </div>

    <script>
        var MATRICULA_USUARIO = '<?php echo htmlspecialchars($_SESSION["usuario_matricula"]); ?>';
    </script>
        <?php include 'dp_modal.php'; ?>

    <script src="busca_detalhes.js"></script>
    <script src="script-acervo.js"></script>
    <script type="module" src="pendentes_logic.js"></script>
</body>
</html>