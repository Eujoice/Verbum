<!-- Tela ADM - Cadastro --> 

<?php
session_start();
// verifica se está logado e se é administrador
if (!isset($_SESSION['logado']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    header("Location: index.php"); // manda de volta se não for admin
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verbum | Cadastro de usuário</title>
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
        <a class="ativo" href="cadastro.php">Cadastro de usuário</a>
        <a href="consulta.php">Consultar exemplares</a>
    </nav>

    <div class="form-container">
        <form action="cadastrar_aluno.php" method="POST" class="form-grid-verbum form-cadastro">
            
            <div class="campo-box">
                <label>Matrícula:</label>
                <input type="text" name="matricula">
            </div>
            <div class="campo-box">
                <label>CPF:</label>
                <input type="text" name="cpf" required>
            </div>
            <div class="campo-box">
                <label>Data de Nascimento:</label>
                <input type="date" name="data_nasc">
            </div>

            <div class="campo-box span-2">
                <label>Nome Completo:</label>
                <input type="text" name="nome" required>
            </div>
            <div class="campo-box">
                <label>Gênero:</label>
                <select name="genero">
                    <option value="" disabled selected>Selecione uma opção</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Feminino">Feminino</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>

            <div class="campo-box span-2">
                <label>Email:</label>
                <input type="email" name="email">
            </div>
            <div class="campo-box">
                <label>Telefone:</label>
                <input type="text" name="telefone">
            </div>

            <div class="campo-box span-2">
                <label>Rua:</label>
                <input type="text" name="rua">
            </div>
            <div class="campo-box">
                <label>Número:</label>
                <input type="text" name="numero">
            </div>

            <div class="campo-box span-2">
                <label>Cidade:</label>
                <input type="text" name="cidade">
            </div>
            <div class="campo-box">
                <label>CEP:</label>
                <input type="text" name="cep">
            </div>

            <div class="campo-box span-2">
                <label>Bairro:</label>
                <input type="text" name="bairro">
            </div>
            <div class="campo-box">
                <label>UF:</label>
                <select name="uf">
                    <option value="">Selecione</option>
                    <option value="SP">SP</option>
                    <option value="RJ">RJ</option>
                    <option value="ES">ES</option>
                    <option value="MG">MG</option>
                </select>
            </div>

            <div class="area-botoes-central">
                <button type="submit" class="btn-acao verde-claro">Cadastrar</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>