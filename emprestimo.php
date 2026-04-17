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
    <link rel="stylesheet" href="emprestimo.css">
    <style>
        /* CSS para garantir que as sugestões flutuem sobre tudo */
        .wrapper-busca {
            position: relative; /* Referência para o box de sugestões */
            flex: 1;
            display: flex;
            align-items: center;
        }

        .sugestoes-box {
            position: absolute;
            top: 100%; /* Gruda no fundo do input */
            left: 0;
            width: 100%;
            background: white;
            border: 1px solid #2d5a27;
            border-top: none;
            border-radius: 0 0 8px 8px;
            z-index: 99999 !important; /* Valor altíssimo para sobrepor tudo */
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            max-height: 200px;
            overflow-y: auto;
            display: none; /* Escondido por padrão */
        }

        .sugestao-item {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            color: #333;
            font-size: 14px;
            background: white;
        }

        .sugestao-item:hover {
            background-color: #f0f7f0;
        }

        /* Remove qualquer interferência visual de inputs ocultos */
        input[type="hidden"] {
            position: absolute;
            display: none !important;
        }

        .linha { position: relative; overflow: visible !important; }
    </style>
</head>
<body class="body-acervo">
<div class="container-acervo">
    <div class="overlay-menu" id="overlayMenu" onclick="fecharMenu()"></div>
    <nav class="menu-lateral" id="menuLateral">
        <div class="sb-profile">
            <div class="sb-avatar">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
            <div class="sb-name"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></div>
            <div class="sb-mat">Matrícula: <?php echo htmlspecialchars($_SESSION['usuario_matricula']); ?></div>
        </div>
        <div class="sb-nav">
            <a class="nav-item nav-admin" href="acervo.php">
                <div class="nav-ic">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                Acessar Acervo
            </a>
            <a class="nav-item" href="dpessoais.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
                Dados Pessoais
            </a>
            <a class="nav-item" href="https://www.gov.br/tesouronacional/pt-br/gru-e-pag-tesouro/pagtesouro">
                    <div class="nav-ic">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                </div>
                Pague Tesouro
            </a>
             <a class="nav-item" href="https://suporte.ifes.edu.br/">
                    <div class="nav-ic">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                </div>
                Sistema de Chamados
            </a>
            
            <a class="nav-item nav-sair" href="logout.php">
                <div class="nav-ic"><svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg></div>
                Sair
            </a>
        </div>
    </nav>

    <div class="container-acervo">
        <div class="topo">
            <header class="header">
                <div class="header-left">
                    <div class="logo"><a href="acervo.php">Verbum</a></div>
                    <img class="logo-vb" src="imgs/ig_aviao.png" alt="Logo">
                </div>
                
                <span class="badge-admin">Painel Administrativo</span>
                
                <div class="icones">
                    <button class="hambtn" id="hambtn" onclick="toggleMenu()">
                        <div class="bar"></div>
                        <div class="bar"></div>
                        <div class="bar"></div>
                    </button>
                </div>
            </header>

        <nav class="menu">
            <a class="ativo" href="emprestimo.php">Empréstimo e devolução</a>
            <a href="armario.php">Armários</a>
            <a href="cadastro.php">Cadastro de usuário</a>
            <a href="consulta.php">Consultar exemplares</a>
        </nav>
    </div>

    <div class="form-container">
        <form id="formEmprestimo" onsubmit="return false;">
            <input type="hidden" id="usuario_id">

            <div class="linha radio-group">
                <label>Emprestado por</label>
                <label><input type="radio" name="tipo" checked> Usuário da instituição</label>
                <label><input type="radio" name="tipo"> Visitante</label>
            </div>

            <div class="linha">
                <label>Pesquisar usuário</label>
                <div class="wrapper-busca">
                    <input type="text" id="buscaUsuario" placeholder="Digite o nome do usuário..." autocomplete="off" style="width: 100%;">
                    <div id="listaSugestoes" class="sugestoes-box"></div>
                </div>
            </div>

            <div class="linha">
                <label>Senha do usuário</label>
                <input type="password" style="width: 150px;">
                
                <label style="margin-left: 20px;">Código ou Título do livro</label>
                <div class="wrapper-busca" style="flex: 0 0 150px;">
                    <input type="text" id="buscaLivro" placeholder="Ex: L01 ou Crime e Castigo" autocomplete="off" style="width: 300px;">
                    <div id="listaSugestoesLivro" class="sugestoes-box"></div>
                </div>
            </div>

            <hr class="divisor">

            <div class="linha">
                <label>Data de empréstimo</label>
                <input type="date" id="data_ini" value="<?php echo date('Y-m-d'); ?>">
                <label style="margin-left: 20px;">Data de devolução prevista</label>
                <input type="date" id="data_fim">
            </div>

            <div class="linha">
                <label>Valor da multa</label>
                <input type="text" id="valorMulta" class="input-readonly" placeholder="R$ 0,00" readonly>
                <label style="margin-left: 20px;">Dias de atraso</label>
                <input type="text" id="diasAtraso" class="input-readonly" placeholder="0" readonly>
            </div>

            <div class="area-botoes">
                <button type="button" id="btnEmprestar" class="btn-acao verde-claro">Emprestar</button>
                <button type="button" id="btnRenovar" class="btn-acao verde-claro">⟲ Renovar</button>
                <button type="button" id="btnDevolver" class="btn-acao verde-escuro">Pagar multa / Devolver</button>
            </div>
        </form>
    </div>
</div>
<div id="modalConfirm" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-icon"><img src="imgs/exclamacao.png" alt="exclamacao" style="width:50px;"></div>
            <h3 id="modalTitulo" style="color:#2d5a30; margin-top:10px;">Atenção</h3>
            <p id="modalMsg">Mensagem do sistema aqui.</p>
            <div class="modal-buttons">
                <button onclick="fecharModal()" class="btn-cancelar">Fechar</button>
                <button id="btnConfirmarAcao" class="btn-confirmar" style="display:none;">Confirmar</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast">Operação realizada com sucesso!</div>

    <script src="emprestimo.js"></script>
    <script src="script-acervo.js"></script>
</body>
</html>
