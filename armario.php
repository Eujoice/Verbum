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
    <title>Verbum | Armários</title>
    <link rel="stylesheet" href="armario.css">
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
                <a class="ativo" href="armario.php">Armários</a>
                <a href="cadastro.php">Cadastro de usuário</a>
                <a href="consulta.php">Consultar exemplares</a>
            </nav>
    </div>

    <div class="form-container">
        <div class="form-card">
            <form id="formArmario" method="POST" action="processar_armario.php">
                <div class="form-duas-colunas">
                    <div class="coluna-campos">
                        <p class="section-title">Identificação do usuário</p>
                        
                        <div class="linha">
                            <label>Pesquisar usuário</label>
                            <div style="position: relative; width: 100%;">
                                <input type="text" id="pesquisaUsuario" placeholder="Nome ou matrícula..." autocomplete="off" required>
                                <div id="sugestoes" class="sugestoes-box"></div>
                            </div>
                            <input type="hidden" id="matriculaSelecionada" name="matricula_usuario">
                        </div>

                        <div class="linha">
                            <label>Senha do usuário</label>
                            <input type="password" name="senha" style="max-width: 200px;">
                        </div>

                        <hr class="divisor">

                        <p class="section-title">Dados do empréstimo</p>

                        <div class="linha">
                            <label>Armário selecionado</label>
                            <input type="text" id="inputArmario" name="numero_armario" placeholder="Selecione ao lado..." readonly required>
                        </div>

                        <div class="linha">
                            <label>Data de empréstimo</label>
                            <input type="date" name="data_emprestimo" value="<?php echo date('Y-m-d'); ?>" style="max-width: 180px;">
                        </div>

                        <div class="area-botoes">
                            <button type="submit" class="btn-acao verde-claro">Emprestar</button>
                            <button type="button" class="btn-acao verde-escuro">Pagar multa</button>
                        </div>
                    </div>

                    <div class="coluna-grid">
                        <div class="grid-header">
                            <span class="grid-titulo">Mapa de Armários</span>
                            <div class="legenda">
                                <div class="leg-item"><div class="dot disponivel"></div> Disponível</div>
                                <div class="leg-item"><div class="dot ocupado"></div> Ocupado</div>
                            </div>
                        </div>
                        <div class="grid-wrap">
                            <div id="gridArmarios"></div>
                        </div>
                        <div id="statsArmarios"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="formDevolucao" method="POST" action="devolver_armario.php" style="display:none;">
    <input type="hidden" name="numero_armario" id="devolverNum">
</form>

<div id="modalConfirm" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-icon"><img src="imgs/exclamacao.png" alt="exclamacao"></div>
        <h3 class="aviso-armario" style="color:rgb(226,87,76);">Armário Ocupado!</h3>
        <p id="modalMsg">Deseja realizar a devolução deste armário?</p>
        <div class="modal-buttons">
            <button onclick="fecharModal()" class="btn-cancelar">Cancelar</button>
            <button id="btnConfirmarAcao" class="btn-confirmar">Confirmar</button>
        </div>
    </div>
</div>

<div id="toast" class="toast">Armário locado com sucesso!</div>

<script src="armario.js"></script>
</body>
</html>