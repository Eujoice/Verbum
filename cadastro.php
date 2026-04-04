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
    <title>Verbum | Cadastro de usuário</title>
    <link rel="stylesheet" href="cadastro.css">
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
            <a href="armario.php">Armários</a>
            <a class="ativo" href="cadastro.php">Cadastro de usuário</a>
            <a href="consulta.php">Consultar exemplares</a>
        </nav>
    </div>

    <div class="form-container">
        <div class="form-card">
            <form action="cadastrar_aluno.php" method="POST" class="form-grid-verbum">
                <p class="section-title span-3">Informações Pessoais</p>
                <div class="campo-box">
                    <label>Matrícula</label>
                    <input type="text" name="matricula" required>
                </div>
                <div class="campo-box">
                    <label>CPF</label>
                    <input type="text" name="cpf" required placeholder="000.000.000-00">
                </div>
                <div class="campo-box">
                    <label>Data de Nascimento</label>
                    <input type="date" name="data_nasc">
                </div>
                <div class="campo-box span-2">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="campo-box">
                    <label>Gênero</label>
                    <select name="genero">
                        <option value="">Selecione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Feminino">Feminino</option>
                        <option value="Feminino">Outro</option>
                        <option value="Feminino">Desejo não informar</option>
                    </select>
                </div>
                <div class="campo-box span-2">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="campo-box">
                    <label>Telefone</label>
                    <input type="text" name="telefone">
                </div>

                <hr class="divisor span-3">
                <p class="section-title span-3">Endereço e Localização</p>
                <div class="campo-box span-2"><label>Rua</label><input type="text" name="rua"></div>
                <div class="campo-box"><label>Número</label><input type="text" name="numero"></div>
                <div class="campo-box"><label>Cidade</label><input type="text" name="cidade"></div>
                <div class="campo-box"><label>Bairro</label><input type="text" name="bairro"></div>
                <div class="campo-box"><label>CEP</label><input type="text" name="cep"></div>

                <div class="area-botoes-cadastro span-3">
                    <button type="submit" id="btnFinalizar" class="btn-acao verde-claro">Finalizar Cadastro</button>
                    <button type="reset" class="btn-acao verde-escuro">Limpar Campos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalAlert" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-icon" id="modalIcon">⚠️</div>
        <h3 id="modalTitle">Aviso</h3>
        <p id="modalMsg"></p>
        <button type="button" onclick="fecharModal()" class="btn-confirmar">Ok</button>
    </div>
</div>

<script src="validacao_cadastro.js"></script>
<script src="animacao_logo.js"></script>

<?php if (isset($_GET['sucesso'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        mostrarPopup("Usuário cadastrado com sucesso!", "+1 Leitor(a)!", "imgs/aluna.png");
    });
</script>
<?php endif; ?>

</body>
</html>