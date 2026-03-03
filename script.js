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