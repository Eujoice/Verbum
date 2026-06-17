document.addEventListener('DOMContentLoaded', () => {
    let idEmprestimoAtual = '';
    let dataPrevistaAtual = '';

    /* ─── Busca Autocomplete ─── */
    const setupSearch = (inputId, listId, url, isUser) => {
        const input = document.getElementById(inputId);
        const list  = document.getElementById(listId);

        input.addEventListener('input', async () => {
            const query = input.value.trim();
            if (query.length < 2) { list.innerHTML = ''; list.style.display = 'none'; return; }

            try {
                const res  = await fetch(`${url}?q=${encodeURIComponent(query)}`);
                const data = await res.json();

                list.innerHTML = '';
                if (data.length > 0) {
                    list.style.display = 'block';
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'sugestao-item';

                        if (isUser) {
                            div.innerHTML = `
                                <div class="sug-user-row">
                                    <div class="sug-user-avatar">${item.nome.charAt(0).toUpperCase()}</div>
                                    <div class="sug-user-info">
                                        <span class="sug-user-nome">${item.nome}</span>
                                        <span class="sug-user-mat">${item.matricula}</span>
                                    </div>
                                    <div class="sug-user-ver">Ver perfil</div>
                                </div>`;
                            div.onclick = (e) => {
                                e.stopPropagation();
                                input.value = item.nome;
                                document.getElementById('usuario_id').value = item.matricula;
                                list.style.display = 'none';
                                // Abre o popup de perfil do usuário
                                abrirPerfilUsuario(item.matricula, item.nome);
                            };
                        } else {
                            const statusClass = item.status === 'Disponível' ? 'status-disp' : 'status-emp';
                            div.innerHTML = `<strong>${item.id}</strong> — ${item.titulo} <span class="sug-status ${statusClass}">${item.status}</span>`;
                            div.onclick = (e) => {
                                e.stopPropagation();
                                input.value = item.id;
                                if (item.status === 'Emprestado') {
                                    idEmprestimoAtual = item.id_emprestimo_atual || '';
                                    dataPrevistaAtual = item.data_prevista || '';
                                    abrirModal("Atenção", `Este livro está com: <strong>${item.emprestado_por}</strong>`, false);
                                    document.getElementById('buscaUsuario').value = item.emprestado_por || '';
                                    document.getElementById('usuario_id').value   = item.matricula_usuario || '';
                                    if (item.data_prevista) calcularMulta(item.data_prevista);
                                } else {
                                    idEmprestimoAtual = '';
                                    dataPrevistaAtual = '';
                                    document.getElementById('diasAtraso').value = 0;
                                    document.getElementById('valorMulta').value  = 'R$ 0,00';
                                }
                                list.style.display = 'none';
                            };
                        }
                        list.appendChild(div);
                    });
                } else {
                    list.style.display = 'none';
                }
            } catch (e) { console.error(e); }
        });
    };

    setupSearch('buscaUsuario', 'listaSugestoes',      '/verbum/api/buscar_usuarios.php', true);
    setupSearch('buscaLivro',   'listaSugestoesLivro', '/verbum/api/buscar_livros.php',   false);

    /* ─── Empréstimo ─── */
    document.getElementById('btnEmprestar').onclick = async function() {
        const user    = document.getElementById('usuario_id').value;
        const livro   = document.getElementById('buscaLivro').value;
        const dataIni = document.getElementById('data_ini').value;
        const dataFim = document.getElementById('data_fim').value;

        if (!user || !livro || !dataIni || !dataFim) {
            return abrirModal("Erro", "Por favor, preencha todos os campos.", false);
        }

        const fd = new FormData();
        fd.append('usuario_id', user); fd.append('livro_id', livro);
        fd.append('data_ini', dataIni); fd.append('data_fim', dataFim);

        const res = await fetch('/verbum/api/processar_emprestimo.php', { method: 'POST', body: fd });
        const txt = await res.text();
        if (txt.trim() === 'Sucesso') {
            mostrarToast('Empréstimo realizado com sucesso!');
            setTimeout(() => location.reload(), 2000);
        } else {
            abrirModal("Erro no Processamento", txt, false);
        }
    };

    /* ─── Renovação ─── */
    document.getElementById('btnRenovar').onclick = function() {
        const idObra = document.getElementById('buscaLivro').value;
        // Tenta do closure; se vazio, tenta do dataset (seleção via popup)
        const empId = idEmprestimoAtual || document.getElementById('buscaLivro').dataset.emprestimoId || '';

        if (!idObra)  return abrirModal("Aviso", "Selecione um livro para renovar.", false);
        if (!empId)   return abrirModal("Aviso", "Este exemplar não possui um empréstimo ativo registrado.", false);

        const novaData          = calcularNovaDataRenovacao(14, dataPrevistaAtual);
        const novaDataFormatada = formatarDataBR(novaData);

        abrirModal("Confirmar Renovação",
            `Deseja renovar o exemplar <strong>#${idObra}</strong>?<br>
             Nova data de devolução: <strong>${novaDataFormatada}</strong>`,
            true,
            async () => {
                const fd = new FormData();
                fd.append('emprestimo_id', empId);
                fd.append('livro_id', idObra);
                fd.append('nova_data_fim', novaData);

                try {
                    const res    = await fetch('/verbum/api/processar_renovacao.php', { method: 'POST', body: fd });
                    const result = await res.json();
                    fecharModal();
                    if (result.sucesso) {
                        mostrarToast('✓ ' + result.mensagem);
                        document.getElementById('data_fim').value = result.nova_data_fim;
                        dataPrevistaAtual = result.nova_data_fim;
                        calcularMulta(result.nova_data_fim);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        abrirModal("Erro na Renovação", result.mensagem || 'Falha ao renovar.', false);
                    }
                } catch (err) {
                    abrirModal("Erro", "Falha ao conectar com o servidor.", false);
                }
            }
        );
    };

    /* ─── Devolução ─── */
    document.getElementById('btnDevolver').onclick = function() {
        const idObra = document.getElementById('buscaLivro').value;
        // Tenta do closure; se vazio, tenta do dataset (seleção via popup)
        const empId = idEmprestimoAtual || document.getElementById('buscaLivro').dataset.emprestimoId || '';

        if (!idObra)  return abrirModal("Aviso", "Selecione um livro para devolver.", false);
        if (!empId)   return abrirModal("Aviso", "Este exemplar não possui um empréstimo ativo registrado.", false);

        abrirModal("Confirmar Devolução", `Deseja confirmar a devolução do exemplar <strong>#${idObra}</strong>?`, true, async () => {
            const fd = new FormData();
            fd.append('acao', 'devolver');
            fd.append('livro_id', idObra);
            fd.append('emprestimo_id', empId);

            try {
                const res    = await fetch('/verbum/api/processar_devolucao.php', { method: 'POST', body: fd });
                const result = await res.text();
                fecharModal();
                mostrarToast(result);
                setTimeout(() => location.reload(), 2000);
            } catch (err) {
                abrirModal("Erro", "Falha ao conectar com o servidor.", false);
            }
        });
    };

    // Fecha sugestões ao clicar fora
    document.addEventListener('click', () => {
        document.querySelectorAll('.sugestoes-box').forEach(b => b.style.display = 'none');
    });

    // Atualiza idEmprestimoAtual e dataPrevistaAtual quando o popup selecionar um livro
    document.addEventListener('_atualizarEmprestimoAtual', (e) => {
        idEmprestimoAtual = e.detail.emprestimoId;
        dataPrevistaAtual = e.detail.dataFim;
    });

    // Preenche data_fim automaticamente com 14 dias úteis ao mudar data_ini
    document.getElementById('data_ini').addEventListener('change', function() {
        const ini = this.value;
        if (ini) {
            document.getElementById('data_fim').value = calcularDataFimUteis(ini, 14);
        }
    });
    // Preenche data_fim na carga inicial se data_ini já tiver valor
    const dataIniInicial = document.getElementById('data_ini').value;
    if (dataIniInicial && !document.getElementById('data_fim').value) {
        document.getElementById('data_fim').value = calcularDataFimUteis(dataIniInicial, 14);
    }
});

/* ═══════════════════════════════════════════
   POPUP DE PERFIL DO USUÁRIO
   ═══════════════════════════════════════════ */
async function abrirPerfilUsuario(matricula, nomeHint) {
    const overlay = document.getElementById('popupPerfilOverlay');
    overlay.style.display = 'flex';

    // Estado de carregamento
    document.getElementById('popupPerfilCorpo').innerHTML = `
        <div class="perfil-loading">
            <div class="perfil-loading-spinner"></div>
            <p>Carregando dados de <strong>${nomeHint}</strong>…</p>
        </div>`;

    try {
        const res  = await fetch(`/verbum/api/buscar_perfil_usuario.php?matricula=${encodeURIComponent(matricula)}`);
        const data = await res.json();

        if (data.erro) {
            document.getElementById('popupPerfilCorpo').innerHTML =
                `<p style="color:#c00;padding:20px;">Erro: ${data.erro}</p>`;
            return;
        }

        renderizarPerfil(data);
    } catch (e) {
        document.getElementById('popupPerfilCorpo').innerHTML =
            `<p style="color:#c00;padding:20px;">Falha ao conectar com o servidor.</p>`;
    }
}

function renderizarPerfil(data) {
    const { usuario, ativos, historico, total_multa, total_emprestimos } = data;

    const iniciais = usuario.nome.split(' ').slice(0,2).map(p => p[0]).join('').toUpperCase();
    const temMulta  = total_multa > 0;

    // Monta HTML dos livros ativos
    const ativosHtml = ativos.length === 0
        ? `<div class="perfil-vazio"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg><p>Nenhum livro emprestado</p></div>`
        : ativos.map(e => {
            const atraso = e.dias_atraso > 0;
            return `
            <div class="perfil-livro-card ${atraso ? 'livro-atrasado' : ''}">
                <div class="perfil-livro-badge">${e.obra_id}</div>
                <div class="perfil-livro-info">
                    <div class="perfil-livro-titulo">${e.titulo}</div>
                    <div class="perfil-livro-meta">
                        Devolver até: <strong>${formatarDataBR(e.data_fim)}</strong>
                        ${atraso ? `<span class="tag-atraso">⚠ ${e.dias_atraso} dia(s) útil(eis) de atraso · R$ ${e.multa.toFixed(2).replace('.',',')}</span>` : ''}
                    </div>
                </div>
                <button class="btn-selecionar-livro" onclick="selecionarLivroDoPopup('${e.obra_id}', '${e.id}', '${e.data_fim}')">Selecionar</button>
            </div>`;
        }).join('');

    // Monta HTML do histórico
    const historicoHtml = historico.length === 0
        ? `<div class="perfil-vazio"><p>Sem histórico de empréstimos</p></div>`
        : historico.map(e => {
            const foiDevolvido = !!e.data_devolucao_real;
            const statusHtml = foiDevolvido
                ? `<span class="perfil-hist-status status-devolvido">\u2713 Devolvido em ${formatarDataBR(e.data_devolucao_real)}</span>`
                : `<span class="perfil-hist-status status-pendente">\u26a0 Devolução pendente</span>`;
            const btnAcao = !foiDevolvido
                ? `<button class="btn-selecionar-livro btn-selecionar-livro--hist" onclick="selecionarLivroDoPopup('${e.obra_id}', '${e.id}', '${e.data_fim}')">Pagar multa / Devolver</button>`
                : '';
            return `
            <div class="perfil-hist-row ${!foiDevolvido ? 'perfil-hist-row--pendente' : ''}">
                <span class="perfil-hist-id">${e.obra_id}</span>
                <span class="perfil-hist-titulo">${e.titulo}</span>
                <span class="perfil-hist-data">Retirada: ${formatarDataBR(e.data_ini)}</span>
                ${statusHtml}
                ${btnAcao}
            </div>`;
        }).join('');

    document.getElementById('popupPerfilCorpo').innerHTML = `
        <div class="perfil-header-usr">
            <div class="perfil-avatar-grande">${iniciais}</div>
            <div class="perfil-info-usuario">
                <h2 class="perfil-nome-usr">${usuario.nome}</h2>
                <div class="perfil-chips">
                    <span class="chip chip-mat">${usuario.matricula}</span>
                    ${usuario.curso ? `<span class="chip chip-curso">🎓 ${usuario.curso}</span>` : ''}
                    ${usuario.email ? `<span class="chip chip-email">✉ ${usuario.email}</span>` : ''}
                </div>
            </div>
        </div>

        <div class="perfil-stats">
            <div class="stat-card">
                <div class="stat-num">${ativos.length}</div>
                <div class="stat-label">Emprestados agora</div>
            </div>
            <div class="stat-card">
                <div class="stat-num">${total_emprestimos}</div>
                <div class="stat-label">Total histórico</div>
            </div>
            <div class="stat-card ${temMulta ? 'stat-multa' : ''}">
                <div class="stat-num">R$ ${total_multa.toFixed(2).replace('.',',')}</div>
                <div class="stat-label">Multa pendente</div>
            </div>
        </div>

        <div class="perfil-secao">
            <div class="perfil-secao-titulo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                Livros em mãos
            </div>
            <div class="perfil-livros-lista">${ativosHtml}</div>
        </div>

        <div class="perfil-secao">
            <div class="perfil-secao-titulo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0z"/></svg>
                Histórico de empréstimos
            </div>
            <div class="perfil-historico">${historicoHtml}</div>
        </div>

        <div class="perfil-acoes-footer">
            <button class="btn-perfil-usar" onclick="usarUsuarioDoPopup()">Usar este usuário</button>
            <button class="btn-perfil-fechar" onclick="fecharPerfilUsuario()">Fechar</button>
        </div>`;
}

function fecharPerfilUsuario() {
    document.getElementById('popupPerfilOverlay').style.display = 'none';
}

function usarUsuarioDoPopup() {
    // O usuário já foi preenchido no campo ao clicar na sugestão
    fecharPerfilUsuario();
    mostrarToast('Usuário selecionado!');
}

function selecionarLivroDoPopup(obraId, emprestimoId, dataFim) {
    document.getElementById('buscaLivro').value   = obraId;
    // Atualiza as variáveis globais de controle
    // Usamos um evento personalizado para comunicar com o escopo do DOMContentLoaded
    document.dispatchEvent(new CustomEvent('selecionarEmprestimo', {
        detail: { obraId, emprestimoId, dataFim }
    }));
    fecharPerfilUsuario();
    calcularMulta(dataFim);
    mostrarToast(`Livro ${obraId} selecionado para devolução ou renovação.`);
}

// Listener para capturar seleção do popup no escopo do DOMContentLoaded
document.addEventListener('selecionarEmprestimo', (e) => {
    // Precisa acessar variáveis do closure — redefine via data attributes
    document.getElementById('buscaLivro').dataset.emprestimoId = e.detail.emprestimoId;
    document.getElementById('buscaLivro').dataset.dataFim      = e.detail.dataFim;
    // Dispara evento de atualização para o closure do DOMContentLoaded
    document.dispatchEvent(new CustomEvent('_atualizarEmprestimoAtual', { detail: e.detail }));
});

/* ═══════════════════════════════════════════
   CÁLCULO DE MULTA (DIAS ÚTEIS CORRETOS)
   ═══════════════════════════════════════════ */

/**
 * Converte string "YYYY-MM-DD" para Date LOCAL (sem deslocamento UTC).
 * new Date("YYYY-MM-DD") interpreta como UTC meia-noite, o que no Brasil
 * (UTC-3) vira o dia anterior — causando +1 dia de atraso falso.
 */
function parseDateLocal(str) {
    const [ano, mes, dia] = str.split('-').map(Number);
    return new Date(ano, mes - 1, dia); // mês é 0-based
}

/**
 * Conta dias úteis (seg–sex) entre duas datas.
 * O dia seguinte ao prazo é o primeiro dia de atraso.
 */
function contarDiasUteisAtraso(dataPrevista) {
    const hoje    = new Date();
    const prevista = parseDateLocal(dataPrevista);

    hoje.setHours(0, 0, 0, 0);
    prevista.setHours(0, 0, 0, 0);

    if (hoje <= prevista) return 0; // Ainda dentro do prazo

    let dias   = 0;
    const cursor = new Date(prevista);
    cursor.setDate(cursor.getDate() + 1); // começa no dia seguinte ao prazo

    while (cursor <= hoje) {
        const dow = cursor.getDay();
        if (dow !== 0 && dow !== 6) dias++; // 0=dom, 6=sáb
        cursor.setDate(cursor.getDate() + 1);
    }
    return dias;
}

function calcularMulta(dataPrevista) {
    const dias = contarDiasUteisAtraso(dataPrevista);

    document.getElementById('diasAtraso').value = dias;
    document.getElementById('valorMulta').value =
        dias > 0 ? `R$ ${dias.toFixed(2).replace('.', ',')}` : 'R$ 0,00';
}

/**
 * Adiciona N dias úteis (seg–sex) a partir de uma data de início.
 * @param {string} dataInicio  'YYYY-MM-DD'
 * @param {number} n           dias úteis a adicionar
 * @returns {string}           'YYYY-MM-DD'
 */
function calcularDataFimUteis(dataInicio, n = 14) {
    const [ano, mes, dia] = dataInicio.split('-').map(Number);
    const data = new Date(ano, mes - 1, dia);
    let adicionados = 0;
    while (adicionados < n) {
        data.setDate(data.getDate() + 1);
        const dow = data.getDay(); // 0=dom, 6=sáb
        if (dow !== 0 && dow !== 6) adicionados++;
    }
    const a = data.getFullYear();
    const m = String(data.getMonth() + 1).padStart(2, '0');
    const d = String(data.getDate()).padStart(2, '0');
    return `${a}-${m}-${d}`;
}

/**
 * Adiciona N dias úteis a partir de HOJE (não inclui hoje).
 * Retorna string YYYY-MM-DD.
 */
/**
 * Calcula nova data de renovação adicionando N dias úteis.
 * Parte da dataPrevista se ainda no prazo; caso contrário parte de hoje.
 * Garante que a nova data seja sempre >= hoje + 1 dia útil.
 */
function calcularNovaDataRenovacao(n = 14, dataPrevista = null) {
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    // Base: data prevista atual (se ainda no prazo) ou hoje (se já atrasado)
    let base = hoje;
    if (dataPrevista) {
        const [a, m, d] = dataPrevista.split('-').map(Number);
        const prev = new Date(a, m - 1, d);
        prev.setHours(0, 0, 0, 0);
        if (prev > hoje) base = prev;
    }

    const data = new Date(base);
    let adicionados = 0;
    while (adicionados < n) {
        data.setDate(data.getDate() + 1);
        const dow = data.getDay();
        if (dow !== 0 && dow !== 6) adicionados++;
    }
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

function formatarDataBR(dataISO) {
    if (!dataISO) return '—';
    const [ano, mes, dia] = dataISO.split('-');
    return `${dia}/${mes}/${ano}`;
}

/* ─── Modal e Toast ─── */
function abrirModal(titulo, mensagem, mostrarConfirmar = false, acaoConfirmar = null) {
    const modal = document.getElementById('modalConfirm');
    document.getElementById('modalTitulo').innerText  = titulo;
    document.getElementById('modalMsg').innerHTML     = mensagem;
    const btnConfirmar = document.getElementById('btnConfirmarAcao');
    if (mostrarConfirmar) {
        btnConfirmar.style.display = 'block';
        btnConfirmar.onclick       = acaoConfirmar;
    } else {
        btnConfirmar.style.display = 'none';
    }
    modal.style.display = 'flex';
}

function fecharModal() { document.getElementById('modalConfirm').style.display = 'none'; }

function mostrarToast(texto) {
    const toast = document.getElementById('toast');
    toast.innerText    = texto;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 3000);
}