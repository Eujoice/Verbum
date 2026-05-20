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
                avaliacao: f.avaliacao_media?.doubleValue || 0           
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
    if (ex.avaliacao > 0) {
        const nota = parseFloat(ex.avaliacao);
        let estrelas = '';
        
        for (let i = 1; i <= 5; i++) {
            if (nota >= i) {
                estrelas += '★';
            } else if (nota >= i - 0.5) {
                estrelas += '⯨';
            } else {
                estrelas += '☆';
            }
        }

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

let filtroEmpStatus = 'todos'; // 'todos' | 'atrasado' | 'hoje' | 'ok'

function calcularStatusEmprestimo(devolucao) {
    let classeStatus = 'ok';
    let textoStatus = 'Em dia';
    let diasAtraso = 0;

    if (devolucao && devolucao !== '—') {
        try {
            const dataPrevista = new Date(devolucao + 'T00:00:00');
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            const diff = Math.floor((hoje - dataPrevista) / 86400000);

            if (diff > 0) {
                classeStatus = 'atrasado';
                textoStatus = `Atrasado ${diff} ${diff === 1 ? 'dia' : 'dias'}`;
                diasAtraso = diff;
            } else if (diff === 0) {
                classeStatus = 'hoje';
                textoStatus = 'Entrega Hoje';
            } else if (diff >= -3) {
                classeStatus = 'proximo';
                textoStatus = `Vence em ${Math.abs(diff)} ${Math.abs(diff) === 1 ? 'dia' : 'dias'}`;
            }
        } catch (e) { console.error(e); }
    }
    return { classeStatus, textoStatus, diasAtraso };
}

function htmlEmpStatsBar(filtrado) {
    const atrasados = filtrado.filter(e => calcularStatusEmprestimo(e.devolucao).classeStatus === 'atrasado').length;
    const hoje = filtrado.filter(e => calcularStatusEmprestimo(e.devolucao).classeStatus === 'hoje').length;
    const emDia = filtrado.filter(e => calcularStatusEmprestimo(e.devolucao).classeStatus === 'ok').length;
    const proximos = filtrado.filter(e => calcularStatusEmprestimo(e.devolucao).classeStatus === 'proximo').length;

    return `
    <div class="emp-stats-bar">
        <button class="emp-stat-pill ${filtroEmpStatus === 'todos' ? 'ativo' : ''}" onclick="setFiltroEmp('todos')">
            <span class="emp-stat-num">${filtrado.length}</span>
            <span class="emp-stat-label">Total</span>
        </button>
        <button class="emp-stat-pill emp-stat-pill--atrasado ${filtroEmpStatus === 'atrasado' ? 'ativo' : ''}" onclick="setFiltroEmp('atrasado')">
            <span class="emp-stat-num">${atrasados}</span>
            <span class="emp-stat-label">Atrasados</span>
        </button>
        <button class="emp-stat-pill emp-stat-pill--hoje ${filtroEmpStatus === 'hoje' ? 'ativo' : ''}" onclick="setFiltroEmp('hoje')">
            <span class="emp-stat-num">${hoje}</span>
            <span class="emp-stat-label">Vencem Hoje</span>
        </button>
        <button class="emp-stat-pill emp-stat-pill--proximo ${filtroEmpStatus === 'proximo' ? 'ativo' : ''}" onclick="setFiltroEmp('proximo')">
            <span class="emp-stat-num">${proximos}</span>
            <span class="emp-stat-label">Vence em breve</span>
        </button>
        <button class="emp-stat-pill emp-stat-pill--ok ${filtroEmpStatus === 'ok' ? 'ativo' : ''}" onclick="setFiltroEmp('ok')">
            <span class="emp-stat-num">${emDia}</span>
            <span class="emp-stat-label">Em dia</span>
        </button>
    </div>`;
}

function setFiltroEmp(status) {
    filtroEmpStatus = status;
    paginaEmprestimos = 1;
    renderEmprestimos();
}

function htmlCardEmprestimo(emp) {
    const obra = todosExemplares.find(ex => ex.id === emp.livro);
    const tituloLivro = obra ? obra.titulo : emp.livro;
    const autorLivro = obra ? obra.autor : '—';
    const genero = obra ? obra.genero : '—';
    const iniciais = emp.aluno.split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase();

    const { classeStatus, textoStatus, diasAtraso } = calcularStatusEmprestimo(emp.devolucao);

    const alertaAtraso = diasAtraso > 0 ? `
        <div class="emp-alerta-atraso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Atraso de ${diasAtraso} ${diasAtraso === 1 ? 'dia' : 'dias'} — multa pode ser aplicada
        </div>` : '';

    return `
    <div class="emp-card emp-card--${classeStatus}">
        <div class="emp-card-body">
            <div class="emp-avatar-novo emp-avatar-novo--${classeStatus}">${iniciais}</div>
            <div class="emp-info-nova">
                <span class="emp-nome-novo">${emp.aluno}</span>
                <span class="emp-mat-novo">Matrícula: ${emp.matricula}</span>
                <div class="emp-livro-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <div>
                        <strong>${tituloLivro}</strong>
                        <span class="emp-livro-sub">${autorLivro} · ${genero} · #${emp.livro}</span>
                    </div>
                </div>
                ${alertaAtraso}
            </div>
            <div class="emp-meta-nova">
                <span class="emp-badge-status emp-badge-status--${classeStatus}">${textoStatus}</span>
                <div class="emp-datas-nova">
                    <div class="emp-data-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Retirada</span><strong>${formatarDataBR(emp.retirada)}</strong>
                    </div>
                    <div class="emp-data-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Devolução</span><strong>${formatarDataBR(emp.devolucao)}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="emp-card-footer">
            <div class="emp-footer-id">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3H8a2 2 0 0 0-2 2v2h12V5a2 2 0 0 0-2-2z"/></svg>
                Empréstimo <strong>#${emp.id.substring(0, 8)}…</strong>
            </div>
            <div class="emp-acoes">
                <button class="emp-btn emp-btn--outline" onclick="verDetalhesEmprestimo('${emp.id}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Detalhes
                </button>
                <button class="emp-btn emp-btn--amber" onclick="notificarAlunoEmprestimo('${emp.matricula}', '${tituloLivro.replace(/'/g,"\\\'")}', '${emp.id}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
                    Contatar aluno
                </button>
                <button class="emp-btn emp-btn--verde" onclick="devolverLivro('${emp.livro}', '${emp.id}')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Devolver
                </button>
            </div>
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

async function notificarAlunoEmprestimo(matricula, tituloLivro, idEmprestimo) {
    abrirModalContato(matricula, tituloLivro, idEmprestimo);
}

function abrirModalContato(matricula, tituloLivro, idEmprestimo) {
    // Remove modal antigo se existir
    document.getElementById('modal-contato')?.remove();

    const modal = document.createElement('div');
    modal.id = 'modal-contato';
    modal.className = 'emp-modal-overlay';
    modal.innerHTML = `
    <div class="emp-modal">
        <div class="emp-modal-header">
            <h3>Contatar Aluno</h3>
            <button class="emp-modal-close" onclick="fecharModalContato()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="emp-modal-body">
            <div class="emp-modal-info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Matrícula: <strong>${matricula}</strong></span>
            </div>
            <div class="emp-modal-info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <span>Livro: <strong>${tituloLivro}</strong></span>
            </div>
            <label class="emp-modal-label">Mensagem para o aluno</label>
            <textarea id="modal-msg" class="emp-modal-textarea" rows="4">Olá! Identificamos que o livro "${tituloLivro}" está com devolução pendente em nosso sistema. Por favor, compareça à biblioteca para regularizar a situação. Obrigado.</textarea>
            <div class="emp-modal-tipos">
                <span class="emp-modal-label">Tipo de aviso</span>
                <div class="emp-modal-tipo-grid">
                    <label class="emp-modal-tipo"><input type="radio" name="tipo-aviso" value="devolucao" checked> Cobrança de devolução</label>
                    <label class="emp-modal-tipo"><input type="radio" name="tipo-aviso" value="atraso"> Aviso de atraso</label>
                    <label class="emp-modal-tipo"><input type="radio" name="tipo-aviso" value="renovacao"> Oferta de renovação</label>
                </div>
            </div>
        </div>
        <div class="emp-modal-footer">
            <button class="emp-btn emp-btn--outline" onclick="fecharModalContato()">Cancelar</button>
            <button class="emp-btn emp-btn--amber" onclick="enviarNotificacaoEmp('${matricula}', '${tituloLivro.replace(/'/g,"\\'")}', '${idEmprestimo}')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
                Enviar notificação
            </button>
        </div>
    </div>`;

    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) fecharModalContato(); });
}

function fecharModalContato() {
    document.getElementById('modal-contato')?.remove();
}

async function enviarNotificacaoEmp(matricula, tituloLivro, idEmprestimo) {
    const msg = document.getElementById('modal-msg')?.value || '';
    const tipo = document.querySelector('input[name="tipo-aviso"]:checked')?.value || 'devolucao';

    const formData = new FormData();
    formData.append('matricula', matricula);
    formData.append('mensagem', msg);          // ← a mensagem exata do textarea
    formData.append('tipo_aviso', tipo);

    try {
        const response = await fetch('processar_notificacao_manual.php', { method: 'POST', body: formData });
        const resultado = await response.json();
        fecharModalContato();
        if (resultado.sucesso) {
            mostrarToast('Notificação enviada com sucesso!', 'ok');
        } else {
            mostrarToast('Erro ao enviar: ' + resultado.mensagem, 'erro');
        }
    } catch (e) {
        fecharModalContato();
        mostrarToast('Erro de conexão ao tentar enviar.', 'erro');
    }
}

function verDetalhesEmprestimo(idEmprestimo) {
    const emp = todosEmprestimos.find(e => e.id === idEmprestimo);
    if (!emp) return;

    const obra = todosExemplares.find(ex => ex.id === emp.livro);
    const tituloLivro = obra ? obra.titulo : emp.livro;
    const autorLivro = obra ? obra.autor : '—';
    const { classeStatus, textoStatus, diasAtraso } = calcularStatusEmprestimo(emp.devolucao);

    document.getElementById('modal-contato')?.remove();

    const modal = document.createElement('div');
    modal.id = 'modal-contato';
    modal.className = 'emp-modal-overlay';
    modal.innerHTML = `
    <div class="emp-modal">
        <div class="emp-modal-header">
            <h3>Detalhes do Empréstimo</h3>
            <button class="emp-modal-close" onclick="fecharModalContato()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="emp-modal-body">
            <div class="emp-det-section">
                <span class="emp-det-label">Aluno</span>
                <div class="emp-det-value-big">${emp.aluno}</div>
                <div class="emp-det-sub">Matrícula: ${emp.matricula}</div>
            </div>
            <div class="emp-det-divider"></div>
            <div class="emp-det-section">
                <span class="emp-det-label">Exemplar</span>
                <div class="emp-det-value-big">${tituloLivro}</div>
                <div class="emp-det-sub">${autorLivro} · ID #${emp.livro}</div>
            </div>
            <div class="emp-det-divider"></div>
            <div class="emp-det-grid">
                <div class="emp-det-item">
                    <span class="emp-det-label">Data de retirada</span>
                    <strong>${formatarDataBR(emp.retirada)}</strong>
                </div>
                <div class="emp-det-item">
                    <span class="emp-det-label">Devolução prevista</span>
                    <strong>${formatarDataBR(emp.devolucao)}</strong>
                </div>
                <div class="emp-det-item">
                    <span class="emp-det-label">Situação</span>
                    <span class="emp-badge-status emp-badge-status--${classeStatus}">${textoStatus}</span>
                </div>
                <div class="emp-det-item">
                    <span class="emp-det-label">ID do Empréstimo</span>
                    <span style="font-size:12px; color:#8a8a8a; word-break:break-all;">${emp.id}</span>
                </div>
            </div>
            ${diasAtraso > 0 ? `<div class="emp-alerta-atraso" style="margin-top:12px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Atraso de ${diasAtraso} ${diasAtraso === 1 ? 'dia' : 'dias'}
            </div>` : ''}
        </div>
        <div class="emp-modal-footer">
            <button class="emp-btn emp-btn--outline" onclick="fecharModalContato()">Fechar</button>
            <button class="emp-btn emp-btn--amber" onclick="fecharModalContato(); notificarAlunoEmprestimo('${emp.matricula}', '${tituloLivro.replace(/'/g,"\\'")}', '${emp.id}')">
                Contatar aluno
            </button>
            <button class="emp-btn emp-btn--verde" onclick="fecharModalContato(); devolverLivro('${emp.livro}', '${emp.id}')">
                Devolver
            </button>
        </div>
    </div>`;

    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) fecharModalContato(); });
}

function mostrarToast(msg, tipo) {
    document.getElementById('emp-toast')?.remove();
    const toast = document.createElement('div');
    toast.id = 'emp-toast';
    toast.className = `emp-toast emp-toast--${tipo}`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('visivel'), 10);
    setTimeout(() => { toast.classList.remove('visivel'); setTimeout(() => toast.remove(), 300); }, 3500);
}

/* ══════════════════════════════════════════════
   CARDS DE RESERVA — NOVO FLUXO COM SELETOR
══════════════════════════════════════════════ */

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

function htmlPainelReservas(diretas, fila) {
    const iniD = (paginaReservadosDireta - 1) * POR_PAGINA;
    const pagD = diretas.slice(iniD, iniD + POR_PAGINA);

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

    const countElement = document.getElementById('count-reservados');
    if (countElement) countElement.textContent = filtrado.length;

    const listaReservados = document.getElementById('lista-reservados');
    if (!listaReservados) return;

    listaReservados.innerHTML = htmlPainelReservas(diretas, fila);

    renderPaginacao('paginacao-reserva-direta', diretas.length, paginaReservadosDireta, p => {
        paginaReservadosDireta = p;
        renderReservados();
    });

    renderPaginacao('paginacao-reserva-fila', fila.length, paginaReservadosFila, p => {
        paginaReservadosFila = p;
        renderReservados();
    });
}

/* ── Card: Reserva Direta ── */
function htmlCardReservaDireta(res) {
    const iniciais = res.aluno.split(' ').filter(n => n).slice(0, 2).map(p => p[0]).join('').toUpperCase();
    const dias = diasDesde(res.dataReserva);
    const prazoLabel = dias !== null ? `Aguardando retirada há ${dias} ${dias === 1 ? 'dia' : 'dias'}` : '';

    const expiracaoDias = 7; 
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

    // Corrigido aqui: trocado 'r.id', 'r.matricula' e 'r.livro' por 'res.id', 'res.matricula' e 'res.livro'
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
                <button class="res-btn res-btn--ambar" onclick="notificarAluno('${res.matricula}', '${res.livro}', '${res.id}')">
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
                <button class="res-btn res-btn--ambar" onclick="notificarAluno('${res.matricula}', '${res.livro}', '${res.id}')">
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

function mostrarSubtela(tipo) {
    subAbaReserva = tipo; 
    document.getElementById('resSeletor').style.display = 'none';
    if (tipo === 'direta') {
        document.getElementById('resSubtelaDireta').style.display = 'block';
    } else {
        document.getElementById('resSubtelaFila').style.display = 'block';
    }
}

function voltarSeletor() {
    subAbaReserva = 'seletor'; 
    document.getElementById('resSubtelaDireta').style.display = 'none';
    document.getElementById('resSubtelaFila').style.display = 'none';
    document.getElementById('resSeletor').style.display = 'block';
}

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

function diffDias(dataIni, dataFim) {
    try {
        const a = new Date(dataIni + 'T00:00:00');
        const b = new Date(dataFim + 'T00:00:00');
        return Math.round((b - a) / 86400000);
    } catch { return null; }
}

function garantirToolbarHistorico() {
    if (document.getElementById('hist-toolbar')) return; 

    const container = document.getElementById('hist-toolbar-container');
    if (!container) return;

    container.innerHTML = `
    <div class="hist-toolbar" id="hist-toolbar">
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

        <div class="hist-toolbar-grupo" id="hist-datas-custom" style="display:none;">
            <input type="date" class="hist-input-data" id="hist-data-ini"
                   onchange="aplicarFiltroData()" title="Data inicial">
            <span class="hist-sep">→</span>
            <input type="date" class="hist-input-data" id="hist-data-fim"
                   onchange="aplicarFiltroData()" title="Data final">
        </div>

        <button class="hist-btn-limpar" onclick="limparFiltrosHistorico()">Limpar filtros</button>

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

function aplicarPeriodoRapido(valor) {
    const custom = document.getElementById('hist-datas-custom');
    if (valor === 'custom') {
        if (custom) custom.style.display = 'flex';
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

function aplicarFiltroData() {
    filtroHistDataInicio = document.getElementById('hist-data-ini')?.value || '';
    filtroHistDataFim    = document.getElementById('hist-data-fim')?.value || '';
    paginaHistorico = 1;
    renderHistorico();
}

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

function htmlCardHistorico(h) {
    const obra = todosExemplares.find(ex => ex.id === h.livro);
    const titulo = obra ? obra.titulo : (h.livro || 'Título desconhecido');
    const iniciais = h.aluno.split(' ').filter(n => n).slice(0, 2).map(p => p[0]).join('').toUpperCase();

    const durDias = (h.dataRetirada && h.dataDevolucao && h.dataDevolucao !== '—')
        ? diffDias(h.dataRetirada, h.dataDevolucao)
        : null;
    const durTexto = durDias !== null ? `${durDias} ${durDias === 1 ? 'dia' : 'dias'}` : '—';

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
            <div class="hist-avatar">${iniciais}</div>
            <div class="hist-info">
                <span class="hist-nome">${h.aluno}</span>
                <div class="hist-livro-row">
                    <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <span>${titulo}</span>
                </div>
                <span class="hist-id-exemplar">Exemplar #${h.livro}</span>
            </div>
            <div class="hist-meta">
                <span class="hist-badge-concluido">✓ Concluído</span>
                <div class="hist-duracao">
                    Duração<br><strong>${durTexto}</strong>
                </div>
                ${prazoBadge}
            </div>
        </div>
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

function htmlEstatisticasHistorico(lista) {
    const total = lista.length;

    const contagemLivros = {};
    lista.forEach(h => {
        const obra = todosExemplares.find(ex => ex.id === h.livro);
        const t = obra ? obra.titulo : h.livro;
        contagemLivros[t] = (contagemLivros[t] || 0) + 1;
    });
    const topLivro = Object.entries(contagemLivros).sort((a, b) => b[1] - a[1])[0];
    const topLivroTexto = topLivro ? `${topLivro[0].substring(0, 22)}${topLivro[0].length > 22 ? '…' : ''}` : '—';

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

function renderHistorico() {
    garantirToolbarHistorico();

    const filtrado = filtrarHistorico();

    const countElement = document.getElementById('count-historico');
    if (countElement) countElement.textContent = filtrado.length;

    const statsContainer = document.getElementById('hist-stats-container');
    if (statsContainer) statsContainer.innerHTML = htmlEstatisticasHistorico(filtrado);

    const inicio = (paginaHistorico - 1) * POR_PAGINA;
    const pagina = filtrado.slice(inicio, inicio + POR_PAGINA);
    const lista = document.getElementById('lista-historico');
    if (!lista) return;

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

    if (abaAtiva === 'reservados') {
        const seletor = document.getElementById('resSeletor');
        const telaDireta = document.getElementById('resSubtelaDireta');
        const telaFila = document.getElementById('resSubtelaFila');

        if(seletor) seletor.style.display = 'none';
        if(telaDireta) telaDireta.style.display = 'none';
        if(telaFila) telaFila.style.display = 'none';

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

    const countElement = document.getElementById('count-acervo');
    if (countElement) countElement.textContent = filtrado.length;
    
    const inicio = (paginaAcervo - 1) * POR_PAGINA;
    const pagina = filtrado.slice(inicio, inicio + POR_PAGINA);
    const lista = document.getElementById('lista-acervo');
    if (!lista) return;

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

    // Aplica filtro de status
    const filtradoComStatus = filtroEmpStatus === 'todos'
        ? filtrado
        : filtrado.filter(e => calcularStatusEmprestimo(e.devolucao).classeStatus === filtroEmpStatus);

    const countElement = document.getElementById('count-emprestimos');
    if (countElement) countElement.textContent = filtrado.length;
    
    const inicio = (paginaEmprestimos - 1) * POR_PAGINA;
    const pagina = filtradoComStatus.slice(inicio, inicio + POR_PAGINA);
    const lista = document.getElementById('lista-emprestimos');
    if (!lista) return;

    // Injeta stats bar no container acima da lista
    const statsContainer = document.getElementById('emp-stats-container');
    if (statsContainer) statsContainer.innerHTML = htmlEmpStatsBar(filtrado);

    lista.innerHTML = pagina.length === 0
        ? '<p class="sem-resultado">Nenhum empréstimo encontrado.</p>'
        : pagina.map(htmlCardEmprestimo).join('');

    renderPaginacao('paginacao-emprestimos', filtradoComStatus.length, paginaEmprestimos, p => {
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
    abaAtiva = id; 
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('ativo'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('ativo'));
    const panel = document.getElementById('panel-' + id);
    if (panel) panel.classList.add('ativo');
    if (btn) btn.classList.add('ativo');
    renderizar(); 
}

function toggleMenu() {
    const menu = document.getElementById('menuLateral');
    const overlay = document.getElementById('overlayMenu');
    const btn = document.getElementById('hambtn');
    if (menu) menu.classList.toggle('aberto');
    if (overlay) overlay.classList.toggle('ativo');
    if (btn) btn.classList.toggle('aberto');
}

function fecharMenu() {
    const menu = document.getElementById('menuLateral');
    const overlay = document.getElementById('overlayMenu');
    const btn = document.getElementById('hambtn');
    if (menu) menu.classList.remove('aberto');
    if (overlay) overlay.classList.remove('ativo');
    if (btn) btn.classList.remove('aberto');
}

/* ── Sincronização Automática ── */
const INTERVALO_ATUALIZACAO = 15000; 

setInterval(async () => {
    try {
        await carregarDados(); 
        console.log("Sincronizado com sucesso às " + new Date().toLocaleTimeString());
    } catch (e) {
        console.error("Erro na sincronização:", e);
    }
}, INTERVALO_ATUALIZACAO);

async function atualizarDadosSilenciosamente() {
    try {
        await carregarDados(); 
        console.log("Dados updated silenciamente às " + new Date().toLocaleTimeString());
    } catch (e) {
        console.error("Erro na atualização automática:", e);
    }
}

/* ── Função Única de Notificação ── */
async function notificarAluno(matricula, tituloLivro, idReserva) {
    if (!confirm(`Deseja enviar uma notificação de aviso para o aluno sobre a retirada do livro "${tituloLivro}"?`)) return;

    const formData = new FormData();
    formData.append('acao', 'enviar_notificacao_manual');
    formData.append('matricula', matricula);
    formData.append('titulo_livro', tituloLivro);
    formData.append('reserva_id', idReserva);

    try {
        const response = await fetch('processar_reserva.php', {
            method: 'POST',
            body: formData
        });
        
        const resultado = await response.json();
        
        if (resultado.sucesso) {
            alert("Notificação enviada com sucesso para o aluno!");
        } else {
            alert("Erro ao enviar: " + resultado.mensagem);
        }
    } catch (e) {
        console.error("Erro ao notificar aluno:", e);
        alert("Erro de conexão ao tentar enviar a notificação.");
    }
}