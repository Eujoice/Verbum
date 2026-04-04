let armarioSelecionado = null;
let dadosArmarios = []; // Armazena info completa do Firestore

async function carregarArmarios() {
    try {
        const response = await fetch('buscar_armarios.php');
        dadosArmarios = await response.json(); // Espera um array de objetos {id, ocupado, usuario}
        renderGrid();
    } catch (e) { console.error("Erro ao carregar:", e); }
}

function renderGrid() {
    const grid = document.getElementById('gridArmarios');
    const statsDiv = document.getElementById('statsArmarios');
    grid.innerHTML = '';

    let total = 70;
    let ocupadosCont = 0;

    for (let i = 1; i <= total; i++) {
        const numStr = String(i).padStart(2, '0');
        const info = dadosArmarios.find(a => a.id === numStr) || { ocupado: false };
        
        const cell = document.createElement('div');
        const isSel = armarioSelecionado === numStr;
        
        cell.className = `armario-cell ${info.ocupado ? 'ocupado' : (isSel ? 'selecionado' : 'disponivel')}`;
        cell.innerHTML = `
            <span class="armario-num">${numStr}</span>
            <span class="armario-sub">${info.ocupado ? 'ocupado' : (isSel ? 'selecionado' : 'livre')}</span>
        `;

        cell.onclick = () => {
            if (info.ocupado) {
                abrirModal(numStr, info.usuario);
        } else {
            armarioSelecionado = isSel ? null : numStr;
            document.getElementById('inputArmario').value = armarioSelecionado || '';
            renderGrid();
        }
        };
        if (info.ocupado) ocupadosCont++;
        grid.appendChild(cell);
    }

    statsDiv.innerHTML = `
        <div class="stat-box s-disp"><div class="num">${total - ocupadosCont}</div><div class="desc">disponíveis</div></div>
        <div class="stat-box s-ocup"><div class="num">${ocupadosCont}</div><div class="desc">ocupados</div></div>
        <div class="stat-box s-tot"><div class="num">${total}</div><div class="desc">total</div></div>
    `;
}

function abrirModal(numero, usuario) {
    const modal = document.getElementById('modalConfirm');
    const msg = document.getElementById('modalMsg');
    const btn = document.getElementById('btnConfirmarAcao');
    
    msg.innerHTML = `O armário <strong>${numero}</strong> está ocupado por <strong>${usuario || 'N/A'}</strong>.<br>Deseja realizar a devolução?`;
    modal.style.display = 'flex';

    btn.onclick = () => {
        document.getElementById('devolverNum').value = numero;
        document.getElementById('formDevolucao').submit();
    };
}

function fecharModal() {
    document.getElementById('modalConfirm').style.display = 'none';
}

// Função para exibir o Toast de sucesso (chame isso se quiser após o reload)
function mostrarToast(texto) {
    const toast = document.getElementById('toast');
    toast.innerText = texto;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 3000);
}

// Fechar modal se clicar fora dele
window.onclick = (event) => {
    const modal = document.getElementById('modalConfirm');
    if (event.target == modal) fecharModal();
};

// Lógica de Busca de Usuário
const inputBusca = document.getElementById('pesquisaUsuario');
const sugestoes = document.getElementById('sugestoes');

inputBusca.addEventListener('input', async (e) => {
    const termo = e.target.value;
    if (termo.length < 2) { sugestoes.innerHTML = ''; return; }

    const res = await fetch(`buscar_usuarios.php?q=${termo}`);
    const lista = await res.json();

    sugestoes.innerHTML = lista.map(u => `
        <div class="sugestao-item" onclick="selecionarUser('${u.nome}', '${u.matricula}')">
            <strong>${u.nome}</strong><br><small>${u.matricula}</small>
        </div>
    `).join('');
});

function selecionarUser(nome, matricula) {
    inputBusca.value = nome;
    document.getElementById('matriculaSelecionada').value = matricula;
    sugestoes.innerHTML = '';
}

document.addEventListener('DOMContentLoaded', carregarArmarios);


document.addEventListener('DOMContentLoaded', () => {
    carregarArmarios();
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sucesso')) {
        const tipo = urlParams.get('sucesso');
        if (tipo == '1') mostrarToast("Armário locado com sucesso!");
        if (tipo == '2') mostrarToast("Armário devolvido com sucesso!");
        
        // Limpa a URL para não mostrar o toast de novo ao atualizar
        window.history.replaceState({}, document.title, "armario.php");
    }
});