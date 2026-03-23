// Botão Entrar (Tela de Login) (removido)


// DADOS USUÁRIO FICTICIO (removido)


// Botão Sair (Tela de Perfil)-> LOGOFF (removido)

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