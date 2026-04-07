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

document.getElementById('buscaUsuario').addEventListener('input', function() {
    let q = this.value;
    if (q.length < 3) return;

    fetch(`buscar_usuarios.php?q=${q}`)
        .then(res => res.json())
        .then(dados => {
            let lista = document.getElementById('listaSugestoes');
            lista.innerHTML = '';
            dados.forEach(user => {
                let div = document.createElement('div');
                div.innerText = `${user.nome} (${user.matricula})`;
                div.onclick = () => {
                    document.getElementById('buscaUsuario').value = user.nome;
                    document.getElementById('usuario_id').value = user.matricula;
                    lista.innerHTML = '';
                };
                lista.appendChild(div);
            });
        });
});

// Envio do Formulário via AJAX
document.getElementById('formEmprestimo').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('processar_emprestimo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(msg => {
        alert(msg);
        if(msg.includes("Sucesso")) location.reload();
    });
};

const btnExp = document.getElementById('btn-exp');
const menuLateral = document.querySelector('.menu-lateral');

// Cria o overlay dinamicamente se não existir
let overlay = document.querySelector('.overlay-menu');
if (!overlay) {
    overlay = document.createElement('div');
    overlay.classList.add('overlay-menu');
    document.body.appendChild(overlay);
}

function abrirMenu() {
    menuLateral.classList.add('expandir');
    overlay.classList.add('ativo');
}

function fecharMenu() {
    menuLateral.classList.remove('expandir');
    overlay.classList.remove('ativo');
}

btnExp.addEventListener('click', () => {
    if (menuLateral.classList.contains('expandir')) {
        fecharMenu();
    } else {
        abrirMenu();
    }
});

// Fechar ao clicar fora (no overlay)
overlay.addEventListener('click', fecharMenu);