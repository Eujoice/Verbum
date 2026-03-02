// Botão Entrar (Tela de Login)
document.getElementById("btn-login").addEventListener("click", function() {
    window.location.href = "acervo.html";
});

// DADOS USUÁRIO FICTICIO
let nome = "João Bento";
let email = "joao@email.com";

document.getElementById("nomeUsuario").textContent = nome;
document.getElementById("emailUsuario").textContent = email;

// Botão Sair (Tela de Perfil)-> LOGOFF
document.getElementById("btnSair").addEventListener("click", function() {
    window.location.href = "index.html";
});