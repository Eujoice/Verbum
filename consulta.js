const POR_PAGINA = 10;

let todosExemplares = [];
let todosEmprestimos = [];
let todosReservados = [];
let todosHistorico = [];
let paginaAcervo = 1;
let paginaEmprestimos = 1;
let paginaReservados = 1;
let paginaReservadosDireta = 1; 
let paginaReservadosFila = 1;   
let paginaHistorico = 1;        
let termoBusca = '';
let abaAtiva = 'acervo'; // Define 'acervo' como padrão
let subAbaReserva = 'seletor'; // Pode ser 'seletor', 'direta' ou 'fila'
let filtroHistDataInicio = '';
let filtroHistDataFim    = '';


/* ── Inicialização ── */
document.addEventListener('DOMContentLoaded', () => {
    carregarDados();

    const inputBusca = document.getElementById('pesquisa');
    if (inputBusca) {
        inputBusca.addEventListener('input', e => {
            termoBusca = e.target.value.toLowerCase().trim();
            // Reseta todas as páginas para 1
            paginaAcervo = 1;
            paginaEmprestimos = 1;
            paginaReservadosDireta = 1;
            paginaReservadosFila = 1;
            paginaHistorico = 1;
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

function obterPosicaoNaFila(obraId, dataReserva, tipo) {
    if (tipo !== 'Fila') return null;

    const filaDoLivro = todosReservados.filter(r =>
        r.obra_id === obraId && r.tipo === 'Fila'
    );

    const index = filaDoLivro.findIndex(r =>
        r.dataReserva === dataReserva
    );

    return index + 1;
}

async function carregarDados() {
    try {
        const response = await fetch('listar_consulta.php');
        const data = await response.json();

        const usuariosMap = {};
        if (data.usuarios) {
            data.usuarios.forEach(doc => {
                const f = doc.fields;
                const path = doc.name.split('/');
                const id = path[path.length - 1];
                usuariosMap[id] = f.nome?.stringValue || 'Usuário sem nome';
            });
        }

        todosHistorico = (data.historico || []).map(doc => {
            const f = doc.fields || {};
            const m = f.usuario_id?.stringValue || '—';
            return {
                aluno: usuariosMap[m] || m,
                livro: f.obra_id?.stringValue || '—',
                dataRetirada: f.data_retirada?.stringValue || '—',
                dataDevolucao: f.data_devolucao_real?.stringValue || '—'
            };
        }).sort((a, b) => new Date(b.dataRetirada) - new Date(a.dataRetirada));

        todosExemplares = (data.obras || []).map(doc => {
            const f = doc.fields || {};
            const pathParts = doc.name.split('/');
            const docId = pathParts[pathParts.length - 1];
            return {
                id: docId,
                titulo: f.titulo?.stringValue || 'Sem título',
                autor: f.autor?.stringValue || 'Autor desconhecido',
                genero: f.genero?.stringValue || '—',
                capa: f.capa?.stringValue || '',
                status: f.status?.stringValue || 'Disponível',
                isbn: f.isbn?.stringValue || '—',
                avaliacao: f.avaliacao?.doubleValue || f.avaliacao?.stringValue || null
            };
        });

        todosEmprestimos = (data.emprestimos || []).map(doc => {
            const f = doc.fields || {};
            return {
                id: doc.name.split('/').pop(),
                aluno: usuariosMap[f.usuario_id?.stringValue] || f.usuario_id?.stringValue || '—',
                matricula: f.usuario_id?.stringValue || '—',
                livro: f.obra_id?.stringValue || '—',
                retirada: f.data_emprestimo?.stringValue || '—',
                devolucao: f.data_devolucao_prevista?.stringValue || '—',
                status: f.status?.stringValue || 'ativo'
            };
        }).sort((a, b) => new Date(b.retirada) - new Date(a.retirada));

        todosReservados = (data.reservas || [])
            .map(doc => {
                const f = doc.fields || {};
                return {
                    id: doc.name.split('/').pop(),
                    aluno: f.nome_usuario?.stringValue || '—',
                    matricula: f.matricula?.stringValue || '—',
                    livro: f.titulo_obra?.stringValue || '—',
                    obra_id: f.obra_id?.stringValue || '',
                    dataReserva: f.data_reserva?.stringValue || '',
                    tipo: f.tipo?.stringValue || 'Direta'
                };
            })
            .sort((a, b) => new Date(a.dataReserva) - new Date(b.dataReserva));

    } catch (e) {
        console.error("Erro ao carregar dados:", e);
    }
    renderizar();
}

/* ══════════════════════════════════════════════
   CARDS DE ACERVO
══════════════════════════════════════════════ */
function htmlCardExemplar(ex) {
    const classeStatus = ex.status === 'Emprestado' ? 'status-emprestado' : 'status-disponivel';

    let avaliacaoHtml = '';
    if (ex.avaliacao) {
        const nota = parseFloat(ex.avaliacao);
        let estrelas = '';
        const inteiras = Math.floor(nota);
        const decimal = nota - inteiras;
        let meia = 0;

        if (decimal >= 0.75) {
            estrelas += '★'.repeat(inteiras + 1);
        } else {
            estrelas += '★'.repeat(inteiras);
            if (decimal >= 0.25) {
                estrelas += '⯨';
                meia = 1;
            }
        }
        const ocupadas = inteiras + meia + (decimal >= 0.75 ? 1 : 0);
        const vazias = Math.max(0, 5 - ocupadas);
        estrelas += '☆'.repeat(vazias);

        avaliacaoHtml = `<span class="estrelas">${estrelas}</span> <span class="nota-texto">${nota.toFixed(1)}</span>`;
    } else {
        avaliacaoHtml = `<span class="nota-texto">Sem avaliações</span>`;
    }

    const capaHtml = ex.capa
        ? `<img src="${ex.capa}" alt="Capa">`
        : `<div class="capa-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="#4a7c4e" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>`;

    return `
    <a href="detalheslivro.php?id=${ex.id}" class="card-link-wrapper">
        <div class="card-exemplar">
            <div class="info-exemplar">
                <h3>${ex.titulo}</h3>
                <p class="autor">${ex.autor}</p>
                <div class="avaliacao-stars">${avaliacaoHtml}</div>
                <ul class="detalhes-lista">
                    <li><strong>Exemplar:</strong> #${ex.id}</li>
                    <li><strong>Gênero:</strong> ${ex.genero}</li>
                    <li><strong>ISBN:</strong> ${ex.isbn}</li>
                </ul>
                <p class="localizacao">Status: <span class="badge-status-simples ${classeStatus}">${ex.status}</span></p>
            </div>
            <div class="capa-exemplar">${capaHtml}</div>
        </div>
    </a>`;
}

/* ══════════════════════════════════════════════
   CARDS DE EMPRÉSTIMO
══════════════════════════════════════════════ */
function htmlCardEmprestimo(emp) {
    const obra = todosExemplares.find(ex => ex.id === emp.livro);
    const tituloLivro = obra ? obra.titulo : "Título não encontrado";
    const iniciais = emp.aluno.split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase();

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
            <p class="emp-data">Retirada: <strong>${formatarDataBR(emp.retirada)}</strong></p>
            <p class="emp-data">Devolução prevista: <strong>${formatarDataBR(emp.devolucao)}</strong></p>
            <span class="badge-status ${classeStatus}">${textoStatus}</span>
            <button class="pag-btn" style="margin-top:10px; width:100%; border-color:#2d5a30; color:#2d5a30;"
                    onclick="devolverLivro('${emp.livro}', '${emp.id}')">
                Devolver
            </button>
        </div>
    </div>`;
}

async function devolverLivro(idObra, idEmprestimo) {
    if (!confirm("Deseja confirmar a devolução deste livro?")) return;

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
        carregarDados();
    } catch (e) {
        console.error("Erro na devolução:", e);
        alert("Erro ao processar devolução.");
    }
}

/* ══════════════════════════════════════════════
   CARDS DE RESERVA — NOVO FLUXO COM SELETOR
══════════════════════════════════════════════ */

/* ── Calcula dias desde a reserva ── */
function diasDesde(dataStr) {
    if (!dataStr) return null;
    try {
        const d = new Date(dataStr + 'T00:00:00');
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        return Math.floor((hoje - d) / 86400000);
    } catch {
        return null;
    }
}

/* ── Monta o HTML completo do painel (seletor + sub-telas) ── */
function htmlPainelReservas(diretas, fila) {
    // Paginação para Reservas Diretas
    const iniD = (paginaReservadosDireta - 1) * POR_PAGINA;
    const pagD = diretas.slice(iniD, iniD + POR_PAGINA);

    // Paginação para Fila
    const iniF = (paginaReservadosFila - 1) * POR_PAGINA;
    const pagF = fila.slice(iniF, iniF + POR_PAGINA);

    return `
    <div id="resSeletor" style="display:none;">
        <div class="res-seletor-grid">
            <button class="res-sel-card res-sel-card--verde" onclick="mostrarSubtela('direta')">
                <span class="res-sel-count res-sel-count--verde">${diretas.length}</span>
                <div class="res-sel-icon res-sel-icon--verde">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#3B6D11" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </div>
                <p class="res-sel-label">Prontos para retirada</p>
                <p class="res-sel-desc">Reservas aguardando retirada</p>
            </button>
            <button class="res-sel-card res-sel-card--ambar" onclick="mostrarSubtela('fila')">
                <span class="res-sel-count res-sel-count--ambar">${fila.length}</span>
                <div class="res-sel-icon res-sel-icon--ambar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#BA7517" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <p class="res-sel-label">Lista de espera</p>
                <p class="res-sel-desc">Aguardando a devolução do exemplar</p>
            </button>
        </div>
    </div>

    <div class="res-subtela" id="resSubtelaDireta" style="display:none;">
        <button class="res-voltar-btn" onclick="voltarSeletor()">← Voltar</button>
        <div class="res-subtela-header">
            <span class="res-subtela-titulo">Prontos para retirada</span>
            <span class="res-badge res-badge--verde">${diretas.length}</span>
        </div>
        <div class="lista-resultados">
            ${pagD.length === 0 ? '<p class="sem-resultado">Nenhuma reserva pronta para retirada.</p>' : pagD.map(r => htmlCardReservaDireta(r)).join('')}
        </div>
        <div id="paginacao-reserva-direta" class="paginacao"></div>
    </div>

    <div class="res-subtela" id="resSubtelaFila" style="display:none;">
        <button class="res-voltar-btn" onclick="voltarSeletor()">← Voltar</button>
        <div class="res-subtela-header">
            <span class="res-subtela-titulo">Lista de Espera</span>
            <span class="res-badge res-badge--ambar">${fila.length}</span>
        </div>
        <div class="lista-resultados">
            ${pagF.length === 0 ? '<p class="sem-resultado">Ninguém na fila de espera.</p>' : pagF.map(r => htmlCardReservaFila(r)).join('')}
        </div>
        <div id="paginacao-reserva-fila" class="paginacao"></div>
    </div>`;
}

function renderReservados() {
    const filtrado = todosReservados.filter(r =>
        r.aluno.toLowerCase().includes(termoBusca) ||
        r.livro.toLowerCase().includes(termoBusca)
    );
    const diretas = filtrado.filter(r => r.tipo !== 'Fila');
    const fila = filtrado.filter(r => r.tipo === 'Fila');

    document.getElementById('count-reservados').textContent = filtrado.length;

    // Injeta o HTML primeiro — os containers de paginação precisam existir no DOM
    document.getElementById('lista-reservados').innerHTML = htmlPainelReservas(diretas, fila);

    // Só então renderiza a paginação dentro dos containers recém-criados
    renderPaginacao('paginacao-reserva-direta', diretas.length, paginaReservadosDireta, p => {
        paginaReservadosDireta = p;
        renderReservados();
    });

    renderPaginacao('paginacao-reserva-fila', fila.length, paginaReservadosFila, p => {
        paginaReservadosFila = p;
        renderReservados();
    });
}

/* ── Card: Reserva Direta (pronto para retirada) ── */
function htmlCardReservaDireta(res) {
    const iniciais = res.aluno.split(' ').filter(n => n).slice(0, 2).map(p => p[0]).join('').toUpperCase();
    const dias = diasDesde(res.dataReserva);
    const prazoLabel = dias !== null ? `Aguardando retirada há ${dias} ${dias === 1 ? 'dia' : 'dias'}` : '';

    const expiracaoDias = 7; // prazo padrão em dias para retirada
    const diasRestantes = dias !== null ? expiracaoDias - dias : null;
    let prazoAlerta = '';
    if (diasRestantes !== null && diasRestantes <= 2 && diasRestantes >= 0) {
        prazoAlerta = `
        <div class="res-prazo-row">
            <div class="res-prazo-dot res-prazo-dot--vermelho"></div>
            <span class="res-prazo-texto--vermelho">Expira em ${diasRestantes} ${diasRestantes === 1 ? 'dia' : 'dias'}</span>
        </div>`;
    } else if (diasRestantes !== null && diasRestantes < 0) {
        prazoAlerta = `
        <div class="res-prazo-row">
            <div class="res-prazo-dot res-prazo-dot--vermelho"></div>
            <span class="res-prazo-texto--vermelho">Prazo expirado</span>
        </div>`;
    }

    return `
    <div class="res-card res-card--verde">
        <div class="res-card-body">
            <div class="res-avatar res-avatar--verde">${iniciais}</div>
            <div class="res-info">
                <span class="res-nome">${res.aluno}</span>
                <span class="res-matricula">Matrícula: ${res.matricula}</span>
                <div class="res-livro-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <span>${res.livro}</span>
                </div>
            </div>
            <div class="res-meta">
                <span class="res-badge-status res-badge-status--verde">Pronto para retirada</span>
                <div class="res-data-info">Solicitado em<br><strong>${formatarDataBR(res.dataReserva)}</strong></div>
                ${prazoAlerta}
            </div>
        </div>
        <div class="res-card-footer">
            <div class="res-footer-info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                ${prazoLabel}
            </div>
            <div class="res-acoes">
                <button class="res-btn" onclick="adicionarObservacao('${res.id}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Observação
                </button>
                <button class="res-btn res-btn--ambar" onclick="notificarAluno('${res.id}', '${res.matricula}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
                    Notificar aluno
                </button>
                <button class="res-btn res-btn--verde" onclick="confirmarRetirada('${res.obra_id}', '${res.id}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirmar retirada
                </button>
                <button class="res-btn res-btn--vermelho" onclick="cancelarReserva('${res.id}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Cancelar
                </button>
            </div>
        </div>
    </div>`;
}

/* ── Card: Fila de espera ── */
function htmlCardReservaFila(res) {
    const iniciais = res.aluno.split(' ').filter(n => n).slice(0, 2).map(p => p[0]).join('').toUpperCase();
    const posicao = obterPosicaoNaFila(res.obra_id, res.dataReserva, res.tipo);
    const ePrimeiro = posicao === 1;
    const ordinal = posicao === 1 ? '1ª' : posicao === 2 ? '2ª' : `${posicao}ª`;
    const msgFila = ePrimeiro
        ? 'Será notificado quando o exemplar for devolvido'
        : `Aguarda liberação do ${posicao - 1}º na fila`;

    return `
    <div class="res-card res-card--ambar">
        <div class="res-card-body">
            <div class="res-avatar res-avatar--ambar">${iniciais}</div>
            <div class="res-info">
                <span class="res-nome">${res.aluno}</span>
                <span class="res-matricula">Matrícula: ${res.matricula}</span>
                <div class="res-livro-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <span>${res.livro}</span>
                </div>
            </div>
            <div class="res-meta">
                <span class="res-badge-status res-badge-status--ambar">${ordinal} na fila de espera</span>
                <div class="res-data-info">Solicitado em<br><strong>${formatarDataBR(res.dataReserva)}</strong></div>
            </div>
        </div>
        <div class="res-card-footer">
            <div class="res-footer-info">
                <span class="res-pos-circulo">${posicao}</span>
                ${msgFila}
            </div>
            <div class="res-acoes">
                <button class="res-btn" onclick="adicionarObservacao('${res.id}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Observação
                </button>
                ${ePrimeiro ? `
                <button class="res-btn res-btn--ambar" onclick="notificarAluno('${res.id}', '${res.matricula}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
                    Contatar aluno
                </button>` : ''}
                <button class="res-btn res-btn--vermelho" onclick="removerDaFila('${res.id}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Remover da fila
                </button>
            </div>
        </div>
    </div>`;
}

/* ── Controle de navegação entre seletor e sub-telas ── */
function mostrarSubtela(tipo) {
    subAbaReserva = tipo; // Salva se é 'direta' ou 'fila'
    document.getElementById('resSeletor').style.display = 'none';
    if (tipo === 'direta') {
        document.getElementById('resSubtelaDireta').style.display = 'block';
    } else {
        document.getElementById('resSubtelaFila').style.display = 'block';
    }
}

function voltarSeletor() {
    subAbaReserva = 'seletor'; // Volta ao estado inicial
    document.getElementById('resSubtelaDireta').style.display = 'none';
    document.getElementById('resSubtelaFila').style.display = 'none';
    document.getElementById('resSeletor').style.display = 'block';
}

/* ── Helper: POST para processar_reserva.php e trata JSON ── */
async function postReserva(formData, confirmacaoMsg) {
    if (confirmacaoMsg && !confirm(confirmacaoMsg)) return false;
    try {
        const response = await fetch('processar_reserva.php', { method: 'POST', body: formData });
        const resultado = await response.json();
        alert(resultado.mensagem);
        if (resultado.sucesso) carregarDados();
        return resultado.sucesso;
    } catch (e) {
        console.error("Erro na requisição:", e);
        alert("Erro de comunicação com o servidor.");
        return false;
    }
}

/* ── Ações dos cards de reserva ── */
async function confirmarRetirada(obraId, reservaId) {
    const fd = new FormData();
    fd.append('acao', 'confirmar_retirada');
    fd.append('obra_id', obraId);
    fd.append('reserva_id', reservaId);
    await postReserva(fd, "Confirmar a retirada deste exemplar? Um empréstimo será criado.");
}

async function cancelarReserva(reservaId) {
    const fd = new FormData();
    fd.append('acao', 'cancelar');
    fd.append('reserva_id', reservaId);
    await postReserva(fd, "Deseja cancelar esta reserva?");
}

async function removerDaFila(reservaId) {
    const fd = new FormData();
    fd.append('acao', 'remover_fila');
    fd.append('reserva_id', reservaId);
    await postReserva(fd, "Deseja remover este aluno da fila de espera?");
}

function notificarAluno(reservaId, matricula) {
    // Conecte ao seu sistema de notificações conforme necessário
    alert(`Notificação enviada para a matrícula ${matricula}.`);
}

async function adicionarObservacao(reservaId) {
    const obs = prompt("Digite a observação para esta reserva:");
    if (!obs || !obs.trim()) return;

    const fd = new FormData();
    fd.append('acao', 'observacao');
    fd.append('reserva_id', reservaId);
    fd.append('observacao', obs.trim());
    await postReserva(fd, null);
}

/* ══════════════════════════════════════════════
   HISTÓRICO
══════════════════════════════════════════════ */


// ── Estado de filtros do histórico ── (declarado no topo do arquivo)

// ── Utilitário: diferença em dias entre duas datas ──
function diffDias(dataIni, dataFim) {
    try {
        const a = new Date(dataIni + 'T00:00:00');
        const b = new Date(dataFim + 'T00:00:00');
        return Math.round((b - a) / 86400000);
    } catch { return null; }
}

// ── Injeta a toolbar na primeira chamada ──────
function garantirToolbarHistorico() {
    if (document.getElementById('hist-toolbar')) return; // já existe

    const container = document.getElementById('hist-toolbar-container');
    if (!container) return;

    container.innerHTML = `
    <div class="hist-toolbar" id="hist-toolbar">
        <!-- Seletor rápido de período -->
        <div class="hist-toolbar-grupo">
            <span class="hist-toolbar-label">Período:</span>
            <select class="hist-select-periodo" id="hist-periodo" onchange="aplicarPeriodoRapido(this.value)">
                <option value="">Todos</option>
                <option value="7">Últimos 7 dias</option>
                <option value="30">Últimos 30 dias</option>
                <option value="90">Últimos 3 meses</option>
                <option value="365">Este ano</option>
                <option value="custom">Personalizado…</option>
            </select>
        </div>

        <!-- Datas personalizadas (oculto até selecionar "Personalizado") -->
        <div class="hist-toolbar-grupo" id="hist-datas-custom" style="display:none;">
            <input type="date" class="hist-input-data" id="hist-data-ini"
                   onchange="aplicarFiltroData()" title="Data inicial">
            <span class="hist-sep">→</span>
            <input type="date" class="hist-input-data" id="hist-data-fim"
                   onchange="aplicarFiltroData()" title="Data final">
        </div>

        <!-- Limpar filtros -->
        <button class="hist-btn-limpar" onclick="limparFiltrosHistorico()">Limpar filtros</button>

        <!-- Exportar CSV -->
        <button class="hist-btn-exportar" onclick="exportarHistoricoCSV()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Exportar CSV
        </button>
    </div>`;
}

// ── Aplica período rápido ────────────────────
function aplicarPeriodoRapido(valor) {
    const custom = document.getElementById('hist-datas-custom');
    if (valor === 'custom') {
        custom.style.display = 'flex';
        filtroHistDataInicio = document.getElementById('hist-data-ini')?.value || '';
        filtroHistDataFim    = document.getElementById('hist-data-fim')?.value || '';
    } else {
        if (custom) custom.style.display = 'none';
        if (valor === '') {
            filtroHistDataInicio = '';
            filtroHistDataFim    = '';
        } else {
            const dias = parseInt(valor);
            const hoje = new Date();
            const inicio = new Date(hoje);
            inicio.setDate(hoje.getDate() - dias);
            filtroHistDataFim    = hoje.toISOString().split('T')[0];
            filtroHistDataInicio = inicio.toISOString().split('T')[0];
        }
    }
    paginaHistorico = 1;
    renderHistorico();
}

// ── Aplica datas personalizadas ──────────────
function aplicarFiltroData() {
    filtroHistDataInicio = document.getElementById('hist-data-ini')?.value || '';
    filtroHistDataFim    = document.getElementById('hist-data-fim')?.value || '';
    paginaHistorico = 1;
    renderHistorico();
}

// ── Limpa todos os filtros do histórico ──────
function limparFiltrosHistorico() {
    filtroHistDataInicio = '';
    filtroHistDataFim    = '';
    paginaHistorico = 1;
    const sel = document.getElementById('hist-periodo');
    if (sel) sel.value = '';
    const custom = document.getElementById('hist-datas-custom');
    if (custom) custom.style.display = 'none';
    const ini = document.getElementById('hist-data-ini');
    const fim = document.getElementById('hist-data-fim');
    if (ini) ini.value = '';
    if (fim) fim.value = '';
    renderHistorico();
}

// ── Filtra o array de histórico ──────────────
function filtrarHistorico() {
    return todosHistorico.filter(h => {
        const matchBusca = h.aluno.toLowerCase().includes(termoBusca) ||
                           h.livro.toLowerCase().includes(termoBusca);
        if (!matchBusca) return false;

        if (filtroHistDataInicio) {
            if (!h.dataRetirada || h.dataRetirada < filtroHistDataInicio) return false;
        }
        if (filtroHistDataFim) {
            if (!h.dataRetirada || h.dataRetirada > filtroHistDataFim) return false;
        }
        return true;
    });
}

// ── Card de histórico (novo design) ──────────
function htmlCardHistorico(h) {
    const obra = todosExemplares.find(ex => ex.id === h.livro);
    const titulo = obra ? obra.titulo : (h.livro || 'Título desconhecido');
    const iniciais = h.aluno.split(' ').filter(n => n).slice(0, 2).map(p => p[0]).join('').toUpperCase();

    // Calcula duração do empréstimo
    const durDias = (h.dataRetirada && h.dataDevolucao && h.dataDevolucao !== '—')
        ? diffDias(h.dataRetirada, h.dataDevolucao)
        : null;
    const durTexto = durDias !== null ? `${durDias} ${durDias === 1 ? 'dia' : 'dias'}` : '—';

    // Verifica se foi devolvido com atraso (prazo padrão: 14 dias)
    const PRAZO_PADRAO = 14;
    let prazoBadge = '';
    if (durDias !== null) {
        if (durDias > PRAZO_PADRAO) {
            const excesso = durDias - PRAZO_PADRAO;
            prazoBadge = `<span class="hist-prazo-badge hist-prazo-badge--atrasado">Atrasado ${excesso}d</span>`;
        } else {
            prazoBadge = `<span class="hist-prazo-badge hist-prazo-badge--ok">No prazo</span>`;
        }
    }

    return `
    <div class="hist-card">
        <div class="hist-card-body">

            <!-- Avatar com iniciais -->
            <div class="hist-avatar">${iniciais}</div>

            <!-- Informações principais -->
            <div class="hist-info">
                <span class="hist-nome">${h.aluno}</span>
                <div class="hist-livro-row">
                    <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <span>${titulo}</span>
                </div>
                <span class="hist-id-exemplar">Exemplar #${h.livro}</span>
            </div>

            <!-- Meta: badge + duração -->
            <div class="hist-meta">
                <span class="hist-badge-concluido">✓ Concluído</span>
                <div class="hist-duracao">
                    Duração<br><strong>${durTexto}</strong>
                </div>
                ${prazoBadge}
            </div>

        </div>

        <!-- Rodapé com datas -->
        <div class="hist-card-footer">
            <div class="hist-datas">
                <div class="hist-data-item">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Retirado em: <strong>${formatarDataBR(h.dataRetirada)}</strong>
                </div>
                <span class="hist-data-seta">→</span>
                <div class="hist-data-item">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Devolvido em: <strong>${formatarDataBR(h.dataDevolucao)}</strong>
                </div>
            </div>
        </div>
    </div>`;
}

// ── Estatísticas do histórico filtrado ───────
function htmlEstatisticasHistorico(lista) {
    const total = lista.length;

    // Livro mais emprestado
    const contagemLivros = {};
    lista.forEach(h => {
        const obra = todosExemplares.find(ex => ex.id === h.livro);
        const t = obra ? obra.titulo : h.livro;
        contagemLivros[t] = (contagemLivros[t] || 0) + 1;
    });
    const topLivro = Object.entries(contagemLivros).sort((a, b) => b[1] - a[1])[0];
    const topLivroTexto = topLivro ? `${topLivro[0].substring(0, 22)}${topLivro[0].length > 22 ? '…' : ''}` : '—';

    // Duração média
    const duracoes = lista
        .filter(h => h.dataRetirada && h.dataDevolucao && h.dataDevolucao !== '—')
        .map(h => diffDias(h.dataRetirada, h.dataDevolucao))
        .filter(d => d !== null && d >= 0);
    const mediaDias = duracoes.length > 0
        ? Math.round(duracoes.reduce((s, d) => s + d, 0) / duracoes.length)
        : null;
    const mediaTexto = mediaDias !== null ? `${mediaDias} dias` : '—';

    return `
    <div class="hist-stats">
        <div class="hist-stat-card">
            <div class="hist-stat-icon hist-stat-icon--verde">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
            <div class="hist-stat-body">
                <div class="hist-stat-valor">${total}</div>
                <div class="hist-stat-label">Empréstimos no período</div>
            </div>
        </div>
        <div class="hist-stat-card">
            <div class="hist-stat-icon hist-stat-icon--azul">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <div class="hist-stat-body">
                <div class="hist-stat-valor" style="font-size:14px; padding-top:4px;">${topLivroTexto}</div>
                <div class="hist-stat-label">Livro mais emprestado</div>
            </div>
        </div>
        <div class="hist-stat-card">
            <div class="hist-stat-icon hist-stat-icon--ambar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="hist-stat-body">
                <div class="hist-stat-valor">${mediaTexto}</div>
                <div class="hist-stat-label">Duração média</div>
            </div>
        </div>
    </div>`;
}

// ── Exportar CSV ──────────────────────────────
function exportarHistoricoCSV() {
    const lista = filtrarHistorico();
    const cabecalho = ['Aluno', 'ID Exemplar', 'Título', 'Data Retirada', 'Data Devolução', 'Dias'];
    const linhas = lista.map(h => {
        const obra = todosExemplares.find(ex => ex.id === h.livro);
        const titulo = obra ? obra.titulo : h.livro;
        const dur = (h.dataRetirada && h.dataDevolucao && h.dataDevolucao !== '—')
            ? diffDias(h.dataRetirada, h.dataDevolucao) : '';
        return [
            `"${h.aluno}"`,
            h.livro,
            `"${titulo}"`,
            formatarDataBR(h.dataRetirada),
            formatarDataBR(h.dataDevolucao),
            dur
        ].join(';');
    });

    const csv = [cabecalho.join(';'), ...linhas].join('\n');
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `historico_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

// ── renderHistorico — substitui a função existente ──
function renderHistorico() {
    garantirToolbarHistorico();

    const filtrado = filtrarHistorico();

    document.getElementById('count-historico').textContent = filtrado.length;

    // Estatísticas
    const statsContainer = document.getElementById('hist-stats-container');
    if (statsContainer) statsContainer.innerHTML = htmlEstatisticasHistorico(filtrado);

    // Paginação
    const inicio = (paginaHistorico - 1) * POR_PAGINA;
    const pagina = filtrado.slice(inicio, inicio + POR_PAGINA);
    const lista = document.getElementById('lista-historico');

    if (pagina.length === 0) {
        lista.innerHTML = `
        <div class="hist-vazio">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1" ry="1"/></svg>
            <p><strong>Nenhum registro encontrado</strong></p>
            <p>Tente ajustar os filtros de data ou a busca.</p>
        </div>`;
    } else {
        lista.innerHTML = pagina.map(htmlCardHistorico).join('');
    }

    renderPaginacao('paginacao-historico', filtrado.length, paginaHistorico, p => {
        paginaHistorico = p;
        renderHistorico();
        document.getElementById('panel-historico').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

/* ══════════════════════════════════════════════
   RENDER PRINCIPAL
══════════════════════════════════════════════ */
function renderizar() {
    renderAcervo();
    renderEmprestimos();
    renderReservados();
    renderHistorico();

    // Lógica de abas principais
    document.querySelectorAll('.panel').forEach(p => {
        p.classList.remove('ativo');
        p.style.display = 'none';
    });
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('ativo'));

    const painelAtivo = document.getElementById('panel-' + abaAtiva);
    const btnAtivo = document.querySelector(`.tab[onclick*="'${abaAtiva}'"]`);

    if (painelAtivo) {
        painelAtivo.classList.add('ativo');
        painelAtivo.style.display = 'block';
    }
    if (btnAtivo) btnAtivo.classList.add('ativo');

    // --- NOVA LÓGICA: Manter sub-tela de Reservas ---
    if (abaAtiva === 'reservados') {
        const seletor = document.getElementById('resSeletor');
        const telaDireta = document.getElementById('resSubtelaDireta');
        const telaFila = document.getElementById('resSubtelaFila');

        // Resetamos todas primeiro
        if(seletor) seletor.style.display = 'none';
        if(telaDireta) telaDireta.style.display = 'none';
        if(telaFila) telaFila.style.display = 'none';

        // Mostramos apenas a que estava ativa
        if (subAbaReserva === 'direta' && telaDireta) {
            telaDireta.style.display = 'block';
        } else if (subAbaReserva === 'fila' && telaFila) {
            telaFila.style.display = 'block';
        } else if (seletor) {
            seletor.style.display = 'block';
        }
    }
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

function renderEmprestimos() {
    const filtrado = todosEmprestimos.filter(emp => {
        const emprestimoAtivo = emp.status !== 'inativo';
        const matchesBusca = emp.aluno.toLowerCase().includes(termoBusca) ||
                             emp.livro.toLowerCase().includes(termoBusca);
        return matchesBusca && emprestimoAtivo;
    });

    document.getElementById('count-emprestimos').textContent = filtrado.length;
    
    // Lógica de Paginação: Cálculo do índice inicial e final
    const inicio = (paginaEmprestimos - 1) * POR_PAGINA;
    const pagina = filtrado.slice(inicio, inicio + POR_PAGINA);
    const lista = document.getElementById('lista-emprestimos');

    lista.innerHTML = pagina.length === 0
        ? '<p class="sem-resultado">Nenhum empréstimo ativo encontrado.</p>'
        : pagina.map(htmlCardEmprestimo).join('');

    // Renderização dos botões (Certifique-se que o ID 'paginacao-emprestimos' existe no HTML)
    renderPaginacao('paginacao-emprestimos', filtrado.length, paginaEmprestimos, p => {
        paginaEmprestimos = p;
        renderEmprestimos();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}


function renderPaginacao(containerId, total, paginaAtual, callback) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const totalPaginas = Math.ceil(total / POR_PAGINA);
    if (totalPaginas <= 1) { container.innerHTML = ''; return; }

    const info = `<span class="pag-info">${((paginaAtual - 1) * POR_PAGINA) + 1}–${Math.min(paginaAtual * POR_PAGINA, total)} de ${total}</span>`;

    let botoes = '';
    const delta = 2;
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaAtual - delta && i <= paginaAtual + delta)) {
            botoes += `<button class="pag-btn ${i === paginaAtual ? 'ativo' : ''}" onclick="executarCallback('${containerId}', ${i})">${i}</button>`;
        } else if (i === paginaAtual - delta - 1 || i === paginaAtual + delta + 1) {
            botoes += `<span class="pag-ellipsis">…</span>`;
        }
    }

    const prev = paginaAtual > 1 ? `<button class="pag-btn pag-nav" onclick="executarCallback('${containerId}', ${paginaAtual - 1})">‹</button>` : '';
    const next = paginaAtual < totalPaginas ? `<button class="pag-btn pag-nav" onclick="executarCallback('${containerId}', ${paginaAtual + 1})">›</button>` : '';

    container.innerHTML = `<div class="paginacao-inner">${info}${prev}${botoes}${next}</div>`;
    window["cb_" + containerId] = callback;
}

window.executarCallback = (id, p) => {
    if (window["cb_" + id]) window["cb_" + id](p);
};

/* ══════════════════════════════════════════════
   MENU LATERAL
══════════════════════════════════════════════ */
function trocarTab(id, btn) {
    abaAtiva = id; // Salva o ID da aba (ex: 'reservados')
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('ativo'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('ativo'));
    document.getElementById('panel-' + id).classList.add('ativo');
    btn.classList.add('ativo');
    renderizar(); // Garante que a renderização ocorra na aba certa
}

function toggleMenu() {
    const menu = document.getElementById('menuLateral');
    const overlay = document.getElementById('overlayMenu');
    const btn = document.getElementById('hambtn');
    menu.classList.toggle('aberto');
    overlay.classList.toggle('ativo');
    btn.classList.toggle('aberto');
}

function fecharMenu() {
    const menu = document.getElementById('menuLateral');
    const overlay = document.getElementById('overlayMenu');
    const btn = document.getElementById('hambtn');
    menu.classList.remove('aberto');
    overlay.classList.remove('ativo');
    btn.classList.remove('aberto');
}

// --- Lógica de Atualização Automática ---
const INTERVALO_ATUALIZACAO = 15000; 

setInterval(async () => {
    try {
        // carregarDados() já chama renderizar() internamente ao final
        await carregarDados(); 
        console.log("Sincronizado com sucesso às " + new Date().toLocaleTimeString());
    } catch (e) {
        console.error("Erro na sincronização:", e);
    }
}, INTERVALO_ATUALIZACAO);

async function atualizarDadosSilenciosamente() {
    try {
        // Reutilizamos a lógica de busca que você já tem
        // Note: Se sua função carregarDados() limpa a tela antes de renderizar,
        // a lista pode "piscar". Se isso acontecer, você pode ajustar para
        // renderizar apenas se houver mudanças.
        await carregarDados(); 
        
        console.log("Dados atualizados automaticamente às " + new Date().toLocaleTimeString());
    } catch (e) {
        console.error("Erro na atualização automática:", e);
    }
}