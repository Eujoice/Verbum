import { db } from "./firebase-config.js";
import {
    collection, getDocs, getDoc, doc, query, where
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

const MAX_LIVROS = 3;

document.addEventListener('DOMContentLoaded', async function () {
    await carregarPendentes();
});

async function carregarPendentes() {
    try {
        // Busca só os empréstimos ativos do usuário
        const q = query(
            collection(db, 'emprestimos'),
            where('usuario_id', '==', window.MATRICULA_USUARIO),
            where('status', '==', 'ativo')
        );
        const snap = await getDocs(q);

        if (snap.empty) {
            renderizarSlots(0, 0);
            mostrarVazio('lista-ativos');
            document.getElementById('count-ativos').textContent = '0';
            return;
        }

        // Busca dados das obras em paralelo
        const promessas = snap.docs.map(async function (empDoc) {
            const emp = Object.assign({ id: empDoc.id }, empDoc.data());
            try {
                const obraSnap = await getDoc(doc(db, 'obras', emp.obra_id));
                emp.obra = obraSnap.exists() ? obraSnap.data() : { titulo: 'Obra não encontrada', autor: '', capa: '' };
            } catch (e) {
                emp.obra = { titulo: emp.obra_id, autor: '', capa: '' };
            }
            emp.statusReal = calcularStatus(emp);
            emp.diasRestantes = calcularDiasRestantes(emp.data_devolucao_prevista);
            return emp;
        });

        const todos = await Promise.all(promessas);

        // Separa atrasados dos ativos normais
        const atrasados = todos.filter(function (e) { return e.statusReal === 'atrasado'; });
        const ativos    = todos.filter(function (e) { return e.statusReal === 'ativo'; });

        // Ordena ativos: mais urgente primeiro
        ativos.sort(function (a, b) { return a.diasRestantes - b.diasRestantes; });

        renderizarSlots(todos.length, atrasados.length);
        renderizarLista('lista-ativos', ativos, false);
        document.getElementById('count-ativos').textContent = ativos.length;

        if (atrasados.length > 0) {
            document.getElementById('secao-atrasados').style.display = 'block';
            document.getElementById('count-atrasados').textContent = atrasados.length;
            renderizarLista('lista-atrasados', atrasados, true);
        }

    } catch (e) {
        console.error('Erro ao carregar pendentes:', e);
        mostrarVazio('lista-ativos');
    }
}

function calcularStatus(emp) {
    if (!emp.data_devolucao_prevista) return 'ativo';
    var hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    var partes = emp.data_devolucao_prevista.split('-');
    var prevista = new Date(partes[0], partes[1] - 1, partes[2]);
    return hoje > prevista ? 'atrasado' : 'ativo';
}

function calcularDiasRestantes(dataPrevista) {
    if (!dataPrevista) return 999;
    var hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    var partes = dataPrevista.split('-');
    var prevista = new Date(partes[0], partes[1] - 1, partes[2]);
    return Math.round((prevista - hoje) / (1000 * 60 * 60 * 24));
}

function renderizarSlots(total, atrasados) {
    var wrap = document.getElementById('slots-wrap');
    var html = '';
    for (var i = 0; i < MAX_LIVROS; i++) {
        if (i < total) {
            var eAtrasado = i < atrasados;
            var classeSlot = eAtrasado ? 'slot atrasado-slot' : 'slot ocupado';
            var corIcon = eAtrasado ? '#d94f4f' : '#6C9467';
            html += '<div class="' + classeSlot + '">' +
                '<svg viewBox="0 0 24 24" fill="' + corIcon + '"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 14H8v-2h8v2zm0-4H8v-2h8v2zm-2-4H8V6h6v2z"/></svg>' +
                '</div>';
        } else {
            html += '<div class="slot">' +
                '<svg class="slot-vazio-ic" viewBox="0 0 24 24" fill="#9aaa98"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>' +
                '</div>';
        }
    }
    wrap.innerHTML = html;
}

function renderizarLista(containerId, lista, isAtrasado) {
    var container = document.getElementById(containerId);

    if (lista.length === 0) {
        container.innerHTML =
            '<div class="pend-vazio">' +
            '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>' +
            '<p>Nenhum empréstimo ativo no momento.</p>' +
            '</div>';
        return;
    }

    container.innerHTML = lista.map(function (emp) {
        var obra = emp.obra || {};

        var capaHtml = obra.capa
            ? '<img class="emp-capa" src="' + obra.capa + '" alt="' + (obra.titulo || '') + '">'
            : '<div class="emp-capa-placeholder" style="background:' + corAleatoria(obra.titulo) + '">' + (obra.titulo || '').substring(0, 20) + '</div>';

        var ladoDireito;
        if (isAtrasado) {
            var diasAtraso = Math.abs(emp.diasRestantes);
            ladoDireito =
                '<div class="atraso-wrap">' +
                    '<div class="atraso-dias">-' + diasAtraso + '</div>' +
                    '<div class="atraso-label">' + (diasAtraso === 1 ? 'dia' : 'dias') + ' atrasado</div>' +
                '</div>';
        } else {
            var dias = emp.diasRestantes;
            var classDias = dias <= 2 ? 'prazo-num urgente' : 'prazo-num';
            var labelDias = dias === 1 ? 'dia restante' : 'dias restantes';
            if (dias === 0) { classDias = 'prazo-num urgente'; labelDias = 'vence hoje'; }
            ladoDireito =
                '<div class="prazo-wrap">' +
                    '<div class="' + classDias + '">' + Math.max(0, dias) + '</div>' +
                    '<div class="prazo-label">' + labelDias + '</div>' +
                '</div>';
        }

        var classeCard = isAtrasado ? 'emp-card card-atrasado' : 'emp-card';
        var classeDataVal = isAtrasado ? 'emp-data-val atrasado' : 'emp-data-val';

        return '<a class="' + classeCard + '" href="detalheslivro.php?id=' + emp.obra_id + '">' +
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
                        '<span class="emp-data-label">Devolução prevista</span>' +
                        '<span class="' + classeDataVal + '">' + formatarData(emp.data_devolucao_prevista) + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            ladoDireito +
            '</a>';
    }).join('');
}

function mostrarVazio(containerId) {
    document.getElementById(containerId).innerHTML =
        '<div class="pend-vazio">' +
        '<svg viewBox="0 0 24 24"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 14H8v-2h8v2zm0-4H8v-2h8v2zm-2-4H8V6h6v2z"/></svg>' +
        '<p>Nenhum livro em mãos no momento. Que tal explorar o acervo?</p>' +
        '</div>';
}

function formatarData(data) {
    if (!data || data === '—') return '—';
    var partes = data.split('-');
    if (partes.length === 3) return partes[2] + '/' + partes[1] + '/' + partes[0];
    return data;
}

function corAleatoria(seed) {
    var cores = ['#8b1a1a','#1a5c3a','#2c2c2c','#5a1a6e','#1a3a5c','#3d2a00','#1a4a3a','#4a1a00'];
    var idx = (seed || '').split('').reduce(function (acc, c) { return acc + c.charCodeAt(0); }, 0) % cores.length;
    return cores[idx];
}