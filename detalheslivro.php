<!--Tela de Detalhes de Livro-->
<?php
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) { 
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Biblioteca da Meia-Noite</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="body-det-livro">
    <div class="container-dl-cheio">
        <header class="header">
        <div class="logo">
            <a href="acervo.php" style="text-decoration: none; color: inherit;">Verbum</a>
        </div>
        <div class="busca">
            <img src="imgs/lupa.png" class="icone-lupa">
            <input type="text" placeholder="O que você quer ler?">
        </div>
        <div class="icones">
            <span class="favoritos">
                Favoritos
                <img src="imgs/Heart.png" class="icone-coracao">
            </span>
            <img id="iconeUsuario" src="imgs/usuario.png" class="icone-usuario">
        </div>
        </header>
            <br>
        <div class="div-livro">
            <img class="capa-livro-det" id="capa-livro-det" src="imgs/capa-biblioteca-meia-noite.png">
            <div class="info-livro" id="info-livro">
                <h2 class="ttl-livro" id="ttl-livro"></h2>
                <p class="autor-livro" id="autor-livro">Matt Haig</p>
                <p class="resenha" id="resenha">
                    Aos 35 anos, Nora Seed é uma mulher cheia de talentos e poucas conquistas. Arrependida 
                    das escolhas que fez no passado, ela vive se perguntando o que poderia ter acontecido 
                    caso tivesse vivido de maneira diferente.<span id="pontos">...</span>
                    <span id="mais"> Após ser demitida e seu gato ser atropelado, Nora vê pouco sentido em 
                    sua existência e decide colocar um ponto final em tudo. Porém, quando se vê na Biblioteca 
                    da Meia-Noite, Nora ganha uma oportunidade única de viver todas as vidas que poderia ter 
                    vivido.</span><button onclick="leiaMais()" id="btnLerMais">Leia mais</button>

                </p>
                <div id="div-detalhes">
                    <div class="ttl-detalhes">
                        <p>PUBLICAÇÃO</p>
                        <P>EDITORA</P>
                        <P>ISBN</P>
                    </div>
                    <div class="info-detalhes">
                        <P id="publicacao">Rio de Janeiro : Bertrand Brasil, 2021.</P>
                        <p id="editora">1.ed.</p>
                        <p id="isbn">9786558380542</p>
                    </div>
                </div>
                <br>
                <div class="div-reserva">
                    <span class="disponibilidade">Status: disponível</span>
                    <button class="btn-reservar">Reservar exemplar</button>
                </div>
            </div>
        </div>
        <!--<p class="avaliar">Avaliar</p>
        <form class="form-avaliar">
            <input type="text" id="input-avaliar" class="inp-avaliar" name="avaliacao" placeholder="Escreva aqui sua avaliação">
            <p class="nota-user">Nota do usuário com stars</p> nota do usuário com estrelinhas e numero<br><br>
            <input type="submit" id="inp-submit-avaliacao" class="inp-submit-avaliacao" name="btn-submit-avaliacao">
        </form>-->
    </div>
    <script src="script.js"></script>
    <script type="module" src="acervo_logic.js"></script>
</body>
</html>
