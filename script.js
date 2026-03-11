// Botão Entrar (Tela de Login)

let btnlogin = document.getElementById("btn-login");

if (btnlogin) {
    btnlogin.addEventListener("click", function() {
        window.location.href = "acervo.html";
    });
}

// DADOS USUÁRIO FICTICIO
let nome = "João Bento";
let email = "joao@email.com";

let nomeUsuario = document.getElementById("nomeUsuario");
let emailUsuario = document.getElementById("emailUsuario");

if (nomeUsuario && emailUsuario) {
    nomeUsuario.textContent = nome;
    emailUsuario.textContent = email;
}

// Botão Sair (Tela de Perfil)-> LOGOFF

let btnSair = document.getElementById("btnSair");

if (btnSair) {

    btnSair.addEventListener("click", function() {
        window.location.href = "index.html";
    });
}

let imgUsuario = document.getElementById("iconeUsuario");

if (imgUsuario) {

    imgUsuario.addEventListener("click", function() {
        window.location.href ="perfil.html";
    });
}

// Função LEIA MAIS para a tela detalheslivro
function leiaMais() {
    var pontos = document.getElementById("pontos");
    var maisTxt = document.getElementById("mais");
    var btnLerMais = document.getElementById("btnLerMais");

    if (pontos.style.display === "none") {
        pontos.style.display = "inline";
        maisTxt.style.display = "none";
        btnLerMais.innerHTML = "Leia mais";
    } else {
        pontos.style.display = "none";
        maisTxt.style.display = "inline";
        btnLerMais.innerHTML = "Leia menos";
    }
}

// Livro clicável: Acervo --> Detalhes
let livroAcervo = document.getElementById("meiaNoiteAcervo");

if (livroAcervo) {
    livroAcervo.addEventListener("click", function() {
        window.location.href = "detalheslivro.html";
    }) 
}