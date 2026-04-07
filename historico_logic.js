import { db } from "./firebase-config.js";
import {
    collection, getDocs, getDoc, doc, query, where
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

var todosEmprestimos = [];

document.addEventListener('DOMContentLoaded', async function () {
    await carregarHistorico();

    document.querySelectorAll('.filtro-pill').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filtro-pill').forEach(function (b) { b.classList.remove('ativo'); });
            this.classList.add('ativo');
            aplicarFiltro(this.dataset.filtro);
        });
    });
});

async function carregarHistorico() {
    const lista = document.getElementById('hist-lista');

    try {
        // Busca todos os empréstimos do usuário
        const q = query(
            collection(db, 'emprestimos'),
            where('usuario_id', '==', window.MATRICULA_USUARIO)
        );
        const snap = await getDocs(q);

        if (snap.empty) {
            lista.innerHTML =
                '<div class="hist-vazio">' +
                '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>' +
                '<p>Você ainda não tem empréstimos registrados.</p>' +
                '</div>';
            atualizarStats([], [], []);
            return;
        }

        // Para cada empréstimo, busca os dados da obra
        const promessas = snap.docs.map(async function (empDoc) {
            const emp = Object.assign({ id: empDoc.id }, empDoc.data());

            try {
                const obraSnap = await getDoc(doc(db, 'obras', emp.obra_id));
                if (obraSnap.exists()) {
                    emp.obra = obraSnap.data();
                } else {
                    emp.obra = { titulo: 'Obra não encontrada', autor: '', capa: '' };
                }
            } catch (e) {
                emp.obra = { titulo: emp.obra_id, autor: '', capa: '' };
            }

            // Calcula se está atrasado
            emp.statusReal = calcularStatus(emp);
            return emp;
        });

        todosEmprestimos = await Promise.all(promessas);

        // Ordena: atrasados primeiro, depois ativos, depois devolvidos
        todosEmprestimos.sort(function (a, b) {
            var ordem = { atrasado: 0, ativo: 1, inativo: 2 };
            return (ordem[a.statusReal] ?? 3) - (ordem[b.statusReal] ?? 3);
        });

        renderizarLista(todosEmprestimos);
        atualizarStats(todosEmprestimos);

    } catch (e) {
        console.error('Erro ao carregar histórico:', e);
        lista.innerHTML = '<div class="hist-vazio"><p>Erro ao carregar histórico.</p></div>';
    }
}

function calcularStatus(emp) {
    if (emp.status === 'inativo') return 'inativo';

    // Verifica se está atrasado comparando datas
    if (emp.data_devolucao_prevista) {
        var hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        var partes = emp.data_devolucao_prevista.split('-');
        var prevista = new Date(partes[0], partes[1] - 1, partes[2]);
        if (hoje > prevista) return 'atrasado';
    }

    return 'ativo';
}

function atualizarStats(lista) {
    var devolvidos = lista.filter(function (e) { return e.statusReal === 'inativo'; }).length;
    var ativos     = lista.filter(function (e) { return e.statusReal === 'ativo'; }).length;
    var atrasados  = lista.filter(function (e) { return e.statusReal === 'atrasado'; }).length;

    document.getElementById('stat-total').textContent      = lista.length;
    document.getElementById('stat-devolvidos').textContent = devolvidos;
    document.getElementById('stat-ativos').textContent     = ativos;
    document.getElementById('stat-atrasados').textContent  = atrasados;
}

function aplicarFiltro(tipo) {
    var filtrado = tipo === 'todos'
        ? todosEmprestimos
        : todosEmprestimos.filter(function (e) { return e.statusReal === tipo; });
    renderizarLista(filtrado);
}

function renderizarLista(lista) {
    var container = document.getElementById('hist-lista');

    if (lista.length === 0) {
        container.innerHTML =
            '<div class="hist-vazio">' +
            '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>' +
            '<p>Nenhum empréstimo encontrado para este filtro.</p>' +
            '</div>';
        return;
    }

    container.innerHTML = lista.map(function (emp) {
        var obra       = emp.obra || {};
        var statusReal = emp.statusReal;

        var badgeClass = statusReal === 'inativo'  ? 'badge-devolvido' :
                         statusReal === 'atrasado' ? 'badge-atrasado'  : 'badge-ativo';
        var badgeTexto = statusReal === 'inativo'  ? 'Devolvido' :
                         statusReal === 'atrasado' ? 'Atrasado'  : 'Ativo';

        var labelDevolucao = statusReal === 'inativo' ? 'Devolvido em' : 'Devolução prevista';
        var dataDevolucao  = statusReal === 'inativo'
            ? (emp.data_devolucao_efetiva || emp.data_devolucao_prevista || '—')
            : (emp.data_devolucao_prevista || '—');

        var classeDataDev = statusReal === 'atrasado' ? ' atrasado' : '';

        var capaHtml = obra.capa
            ? '<img class="emp-capa" src="' + obra.capa + '" alt="' + (obra.titulo || '') + '">'
            : '<div class="emp-capa-placeholder" style="background:' + corAleatoria(obra.titulo) + '">' + (obra.titulo || '').substring(0, 20) + '</div>';

        return '<a class="emp-card" href="detalheslivro.php?id=' + emp.obra_id + '">' +
            capaHtml +
            '<div class="emp-info">' +
                '<div class="emp-titulo">' + (obra.titulo || 'Sem título') + '</div>' +
                '<div class="emp-autor">' + (obra.autor || '') + '</div>' +
                '<div class="emp-datas">' +
                    '<div class="emp-data-item">' +
                        '<span class="emp-data-label">Empréstimo</span>' +
                        '<span class="emp-data-val">' + formatarData(emp.data_emprestimo) + '</span>' +
                    '</div>' +
                    '<div class="emp-data-item">' +
                        '<span class="emp-data-label">' + labelDevolucao + '</span>' +
                        '<span class="emp-data-val' + classeDataDev + '">' + formatarData(dataDevolucao) + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="emp-status"><span class="badge ' + badgeClass + '">' + badgeTexto + '</span></div>' +
            '</a>';
    }).join('');
}

function formatarData(data) {
    if (!data || data === '—') return '—';
    // Converte "2026-04-09" para "09/04/2026"
    var partes = data.split('-');
    if (partes.length === 3) return partes[2] + '/' + partes[1] + '/' + partes[0];
    return data;
}

function corAleatoria(seed) {
    var cores = ['#8b1a1a','#1a5c3a','#2c2c2c','#5a1a6e','#1a3a5c','#3d2a00','#1a4a3a','#4a1a00'];
    var idx = (seed || '').split('').reduce(function (acc, c) { return acc + c.charCodeAt(0); }, 0) % cores.length;
    return cores[idx];
}