const POR_PAGINA = 10;

let todosExemplares = [];
let todosEmprestimos = [];
let todosReservados = [];
let todosHistorico = []; 
let paginaAcervo = 1;
let paginaEmprestimos = 1;
let paginaReservados = 1;
let termoBusca = '';

/* ── Inicialização ── */
document.addEventListener('DOMContentLoaded', () => {
    carregarDados();

    const inputBusca = document.getElementById('pesquisa');
    if (inputBusca) {
        inputBusca.addEventListener('input', e => {
            termoBusca = e.target.value.toLowerCase().trim();
            paginaAcervo = 1;
            renderizar();
        });
    }
});

function formatarDataBR(data) {
    if (!data || data === '—') return '—';

    try {
        const d = new Date(data);

        return d.toLocaleDateString('pt-BR'); 
    } catch {
        return data; 
    }
}

async function carregarDados() {
    try {
        const response = await fetch('listar_consulta.php');
        const data = await response.json();

        // 1. Cria o mapa de usuários (Necessário para as outras listas)
        const usuariosMap = {};
        if (data.usuarios) {
            data.usuarios.forEach(doc => {
                const f = doc.fields;
                const path = doc.name.split('/');
                const id = path[path.length - 1];
                usuariosMap[id] = f.nome?.stringValue || 'Usuário sem nome';
            });
        }

        // 2. Processa as listas que dependem do usuariosMap
        todosHistorico = (data.historico || []).map(doc => {
            const f = doc.fields || {};
            const m = f.usuario_id?.stringValue || '—';
            return {
                aluno: usuariosMap[m] || m,
                livro: f.obra_id?.stringValue || '—',
                dataRetirada: f.data_retirada?.stringValue || '—',
                dataDevolucao: f.data_devolucao_real?.stringValue || '—'
            };
        });

        todosExemplares = (data.obras || []).map(doc => {
            const f = doc.fields || {};
            const pathParts = doc.name.split('/');
            const docId = pathParts[pathParts.length - 1];

            return {
                id: docId,
                titulo: f.titulo?.stringValue || 'Sem título',
                autor: f.autor?.stringValue || 'Autor desconhecido',
                genero: f.genero?.stringValue || '—',
                ano: f.ano_publicacao?.integerValue || f.ano_publicacao?.stringValue || '—',
                capa: f.capa?.stringValue || '',
                status: f.status?.stringValue || 'Disponível',
                isbn: f.isbn?.stringValue || '—',
                nota: 5.0 
            };
        });

        todosEmprestimos = (data.emprestimos || []).map(doc => {
            const f = doc.fields || {};
            const pathParts = doc.name.split('/');
            const docId = pathParts[pathParts.length - 1];

            return {
                id: docId,
                aluno: usuariosMap[f.usuario_id?.stringValue] || f.usuario_id?.stringValue || '—',
                matricula: f.usuario_id?.stringValue || '—',
                livro: f.obra_id?.stringValue || '—',
                retirada: f.data_emprestimo?.stringValue || '—',
                devolucao: f.data_devolucao_prevista?.stringValue || '—',
                status: f.status?.stringValue || 'ativo' 
            };
        });

        todosReservados = (data.reservas || []).map(doc => {
            const f = doc.fields || {};
            return {
                aluno: f.nome_usuario?.stringValue || '—',
                matricula: f.matricula?.stringValue || '—',
                livro: f.titulo_obra?.stringValue || '—',
                dataReserva: f.data_reserva?.stringValue || '—'
            };
        });

    } catch (e) {
        console.error("Erro ao carregar dados:", e);
    }
    renderizar();
}

function renderizar() {
    renderAcervo();
    renderEmprestimos();
    renderReservados();
    renderHistorico(); 
}

function htmlCardExemplar(ex) {
    const estrelas = '★'.repeat(5); 
    const classeStatus = ex.status === 'Emprestado' ? 'status-emprestado' : 'status-disponivel';

    const capaHtml = ex.capa 
        ? `<img src="${ex.capa}" alt="Capa">`
        : `<div class="capa-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="#4a7c4e" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>`;

    return `
    <a href="detalheslivro.php?id=${ex.id}" class="card-link-wrapper">
        <div class="card-exemplar">
            <div class="info-exemplar">
                <h3>${ex.titulo}</h3>
                <p class="autor">${ex.autor}</p>
                <div class="avaliacao-stars">
                    <span class="estrelas">${estrelas}</span>
                    <span class="nota-texto">5.0 - Muito bom</span>
                </div>
                <ul class="detalhes-lista">
                    <li><strong>Exemplar:</strong> #${ex.id}</li> <li><strong>Gênero:</strong> ${ex.genero}</li>
                    <li><strong>ISBN:</strong> ${ex.isbn}</li>
                </ul>
                <p class="localizacao">Status: <span class="badge-status-simples ${classeStatus}">${ex.status}</span></p>
            </div>
            <div class="capa-exemplar">${capaHtml}</div>
        </div>
    </a>`;
}

function renderAcervo() {
    const filtrado = todosExemplares.filter(ex => 
        ex.titulo.toLowerCase().includes(termoBusca) || 
        ex.autor.toLowerCase().includes(termoBusca) ||
        ex.id.toLowerCase().includes(termoBusca) 
    );
    
    document.getElementById('count-acervo').textContent = filtrado.length;
    const inicio = (paginaAcervo - 1) * POR_PAGINA;
    const pagina = filtrado.slice(inicio, inicio + POR_PAGINA);
    const lista = document.getElementById('lista-acervo');
    
    lista.innerHTML = pagina.length === 0 
        ? '<p class="sem-resultado">Nenhum exemplar encontrado.</p>' 
        : pagina.map(htmlCardExemplar).join('');
    
    renderPaginacao('paginacao-acervo', filtrado.length, paginaAcervo, p => {
        paginaAcervo = p;
        renderAcervo();
        document.getElementById('panel-acervo').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

/* ── Funções de Paginacao ── */
function htmlCardEmprestimo(emp) {
    // Busca o título do livro no acervo para exibir no card
    const obra = todosExemplares.find(ex => ex.id === emp.livro);
    const tituloLivro = obra ? obra.titulo : "Título não encontrado";

    const iniciais = emp.aluno.split(' ').slice(0,2).map(p => p[0]).join('').toUpperCase();
    
    let classeStatus = 'ok';
    let textoStatus = 'Em dia';

    if (emp.devolucao && emp.devolucao !== '—') {
        try {
            const dataPrevista = new Date(emp.devolucao + 'T00:00:00');
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);

            if (hoje.getTime() > dataPrevista.getTime()) {
                classeStatus = 'atrasado';
                textoStatus = 'Atrasado';
            } else if (hoje.getTime() === dataPrevista.getTime()) {
                classeStatus = 'hoje';
                textoStatus = 'Entrega Hoje';
            }
        } catch (e) { console.error(e); }
    }

    return `
    <div class="card-emprestimo">
        <div class="emp-avatar">${iniciais}</div>
        <div class="emp-info">
            <h3 class="emp-nome-titulo">${emp.aluno}</h3>
            <p class="emp-matricula-sub">Matrícula: ${emp.matricula}</p>
            <div class="emp-detalhes-livro">
                <p><strong>Livro:</strong> ${tituloLivro}</p>
                <p><strong>ID Exemplar:</strong> #${emp.livro}</p>
            </div>
        </div>
        <div class="emp-datas">
            <p class="emp-data">Retirada:  <strong>${formatarDataBR(emp.retirada)}</strong></p>
            <p class="emp-data">Devolução prevista:  <strong>${formatarDataBR(emp.devolucao)}</strong></p>
            <span class="badge-status ${classeStatus}">${textoStatus}</span>
            <button class="pag-btn" style="margin-top:10px; width:100%; border-color:#2d5a30; color:#2d5a30;" 
                    onclick="devolverLivro('${emp.livro}', '${emp.id}')">
                Devolver
            </button>
        </div>
    </div>`;
}

// Função adicionada para devolução
async function devolverLivro(idObra, idEmprestimo) {
    if(!confirm("Deseja confirmar a devolução deste livro?")) return;

    const formData = new FormData();
    formData.append('acao', 'devolver');
    formData.append('livro_id', idObra);
    formData.append('emprestimo_id', idEmprestimo);

    try {
        const response = await fetch('processar_devolucao.php', {
            method: 'POST',
            body: formData
        });
        const resultado = await response.text();
        alert(resultado);
        carregarDados(); // Recarrega as listas 
    } catch (e) {
        console.error("Erro na devolução:", e);
        alert("Erro ao processar devolução.");
    }
}

// EXEMPLO de card para reserva (mesmo modelo dos outros)
function htmlCardReserva(res) {
    const iniciais = res.aluno.split(' ').slice(0,2).map(p => p[0]).join('').toUpperCase();
    return `
    <div class="card-emprestimo">
        <div class="emp-avatar">${iniciais}</div>
        <div class="emp-info">
            <p class="emp-nome">${res.aluno}</p>
            <p class="emp-matricula">Matrícula: ${res.matricula}</p>
            <p class="emp-livro">${res.livro}</p>
        </div>
        <div class="emp-datas">
            <p class="emp-data">Reserva:  <strong>${formatarDataBR(res.dataReserva)}</strong></p>
            <span class="badge-status proximo">Reservado</span>
        </div>
    </div>`;
}

function htmlCardHistorico(h) {
    const obra = todosExemplares.find(ex => ex.id === h.livro);
    const titulo = obra ? obra.titulo : "Título não encontrado";
    
    return `
    <div class="card-emprestimo historico-item">
        <div class="emp-info">
            <h3 class="emp-nome-titulo">${h.aluno}</h3>
            <p><strong>Livro:</strong> ${titulo} (#${h.livro})</p>
            <p class="emp-data">Retirado em:  ${formatarDataBR(h.dataRetirada)}</p>
            <p class="emp-data">Devolvido em:  ${formatarDataBR(h.dataDevolucao)}</p>
        </div>
        <span class="badge-status ok">Concluído</span>
    </div>`;
}

function renderHistorico() {
    const lista = document.getElementById('lista-historico');
    const filtrado = todosHistorico.filter(h => h.aluno.toLowerCase().includes(termoBusca) || h.livro.toLowerCase().includes(termoBusca));
    document.getElementById('count-historico').textContent = filtrado.length;
    lista.innerHTML = filtrado.map(htmlCardHistorico).join('');
}

function renderEmprestimos() {
    // filtra pelo status do empréstimo ser 'ativo' (ou não ser 'inativo')
    const filtrado = todosEmprestimos.filter(emp => {
        // Verifica se o status do registro de empréstimo não é inativo
        const emprestimoAtivo = emp.status !== 'inativo';
        
        const matchesBusca = emp.aluno.toLowerCase().includes(termoBusca) || 
                             emp.livro.toLowerCase().includes(termoBusca);
        
        return matchesBusca && emprestimoAtivo;
    });

    document.getElementById('count-emprestimos').textContent = filtrado.length;
    const lista = document.getElementById('lista-emprestimos');
    
    lista.innerHTML = filtrado.length === 0 
        ? '<p class="sem-resultado">Nenhum empréstimo ativo encontrado.</p>' 
        : filtrado.map(htmlCardEmprestimo).join('');
}

function renderReservados() {
    const filtrado = todosReservados.filter(r => r.aluno.toLowerCase().includes(termoBusca) || r.livro.toLowerCase().includes(termoBusca));
    document.getElementById('count-reservados').textContent = filtrado.length;
    const lista = document.getElementById('lista-reservados');
    lista.innerHTML = filtrado.map(htmlCardReserva).join('');
}

function renderPaginacao(containerId, total, paginaAtual, callback) {
    const container = document.getElementById(containerId);
    const totalPaginas = Math.ceil(total / POR_PAGINA);
    if (totalPaginas <= 1) { container.innerHTML = ''; return; }

    const info = `<span class="pag-info">${((paginaAtual-1)*POR_PAGINA)+1}–${Math.min(paginaAtual*POR_PAGINA, total)} de ${total}</span>`;

    let botoes = '';
    const delta = 2;
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaAtual - delta && i <= paginaAtual + delta)) {
            botoes += `<button class="pag-btn ${i === paginaAtual ? 'ativo' : ''}" onclick="executarCallback('${containerId}', ${i})">${i}</button>`;
        } else if (i === paginaAtual - delta - 1 || i === paginaAtual + delta + 1) {
            botoes += `<span class="pag-ellipsis">…</span>`;
        }
    }

    const prev = paginaAtual > 1 ? `<button class="pag-btn pag-nav" onclick="executarCallback('${containerId}', ${paginaAtual-1})">‹</button>` : '';
    const next = paginaAtual < totalPaginas ? `<button class="pag-btn pag-nav" onclick="executarCallback('${containerId}', ${paginaAtual+1})">›</button>` : '';

    container.innerHTML = `<div class=\"paginacao-inner\">${info}${prev}${botoes}${next}</div>`;

    // Armazena o callback para ser acessado pelo clique
    window["cb_" + containerId] = callback;
}

// Função auxiliar para disparar o callback correto
window.executarCallback = (id, p) => {
    if (window["cb_" + id]) window["cb_" + id](p);
};

/* ── Helpers de Design ── */
function trocarTab(id, btn) {
    tabAtiva = id;
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('ativo'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('ativo'));
    document.getElementById('panel-' + id).classList.add('ativo');
    btn.classList.add('ativo');
}