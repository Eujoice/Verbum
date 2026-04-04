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
                    <input type="text" id="buscaLivro" placeholder="Ex: L01" autocomplete="off" style="width: 300px;">
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
</body>
</html>
<script src="emprestimo.js"></script>
</body>
</html>