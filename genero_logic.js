import { db } from "./firebase-config.js";
import { collection, getDocs, query, where } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

const CONFIG_GENEROS = {
    'Ficção':               { cor: '#1a2a5e', deco: '#3a5abf', icon: '🚀', desc: 'Universos futuristas, tecnologia e imaginação além dos limites.' },
    'Fantasia':             { cor: '#2a1a5e', deco: '#6a3abf', icon: '⚔️', desc: 'Mundos mágicos, criaturas e aventuras épicas.' },
    'Romance':              { cor: '#5e1a2a', deco: '#bf3a5a', icon: '💛', desc: 'Histórias de amor, paixão e relacionamentos humanos.' },
    'Terror':               { cor: '#1a1a1a', deco: '#555555', icon: '🕯',  desc: 'Suspense, mistério e histórias que arrepiam a espinha.' },
    'Clássico':             { cor: '#3d2800', deco: '#a06a00', icon: '📜', desc: 'Obras imortais que moldaram a literatura mundial.' },
    'Autoajuda':            { cor: '#1a4a2a', deco: '#3aaf6a', icon: '✨', desc: 'Desenvolvimento pessoal, motivação e bem-estar.' },
    'História':             { cor: '#2a1a00', deco: '#8a5a00', icon: '🏛',  desc: 'Fatos, civilizações e personagens que mudaram o mundo.' },
    'Policial':             { cor: '#001a2a', deco: '#006a8a', icon: '🔍', desc: 'Crimes, investigações e reviravoltas surpreendentes.' },
    'Biografia':            { cor: '#1a1a3a', deco: '#4a4a9a', icon: '👤', desc: 'Vidas extraordinárias que inspiram e ensinam.' },
    'HQ':                   { cor: '#3a0a1a', deco: '#9a1a4a', icon: '💥', desc: 'Quadrinhos, graphic novels e mangás de todos os estilos.' },
    'Ficção Clássica':      { cor: '#2a1a00', deco: '#7a4a00', icon: '🏺', desc: 'A ficção que resistiu ao tempo e se tornou patrimônio da humanidade.' },
    'Ficção Científica':    { cor: '#002a3a', deco: '#006a9a', icon: '🛸', desc: 'Ciência, futuro e os limites da imaginação humana.' },
    'Naturalismo':          { cor: '#1a3a1a', deco: '#3a8a3a', icon: '🌿', desc: 'Realismo cru e a influência do meio sobre o ser humano.' },
    'Distopia':             { cor: '#2a0a00', deco: '#8a2a00', icon: '🏚', desc: 'Sociedades opressivas e futuros onde a liberdade foi perdida.' },
    'Literatura Brasileira':{ cor: '#3a2000', deco: '#9a5a00', icon: '🇧🇷', desc: 'A riqueza e diversidade da literatura produzida no Brasil.' },
    'Romance Gótico':       { cor: '#1a001a', deco: '#5a005a', icon: '🦇', desc: 'Atmosfera sombria, paixões intensas e o sobrenatural.' },
    'Suspense Psicológico': { cor: '#001a1a', deco: '#005a5a', icon: '🧠', desc: 'Tensão mental, reviravoltas e narrativas que perturbam.' },
    'Outros':               { cor: '#2a2a2a', deco: '#5a5a5a', icon: '📚', desc: 'Obras que escapam das categorias tradicionais.' },
};

var filtroAtual = 'titulo';
var livrosBrutos = [];

// Pega parâmetros da URL (igual ao acervo_logic.js)
const urlParams = new URLSearchParams(window.location.search);
const generoParam = urlParams.get('id'); // ex: ?id=Ficção

document.addEventListener('DOMContentLoaded', async function () {
    if (generoParam) {
        await carregarLivros(generoParam);
    } else {
        await carregarGeneros();
    }

    document.querySelectorAll('.filtro-pill').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filtro-pill').forEach(function (b) { b.classList.remove('ativo'); });
            this.classList.add('ativo');
            filtroAtual = this.dataset.filtro;
            renderizarLivros(livrosBrutos);
        });
    });
});

async function carregarGeneros() {
    try {
        const snap = await getDocs(collection(db, 'obras'));

        var contagem = {};
        snap.forEach(function (doc) {
            var g = doc.data().genero || 'Outros';
            contagem[g] = (contagem[g] || 0) + 1;
        });

        var grid = document.getElementById('generos-grid');
        grid.innerHTML = '';

        // Mostra gêneros que existem no banco
        Object.keys(contagem).forEach(function (nomeGenero) {
            var cfg = CONFIG_GENEROS[nomeGenero] || { cor: '#3d6139', deco: '#5a8a55', icon: '📚', desc: '' };
            var qtd = contagem[nomeGenero];

            var card = document.createElement('a');
            card.className = 'genero-card';
            card.href = 'genero.php?id=' + encodeURIComponent(nomeGenero);
            card.style.background = cfg.cor;
            card.innerHTML =
                '<div class="genero-deco" style="background:' + cfg.deco + '"></div>' +
                '<div class="genero-icon">' + cfg.icon + '</div>' +
                '<div class="genero-nome">' + nomeGenero + '</div>' +
                '<div class="genero-qtd">' + qtd + ' título' + (qtd !== 1 ? 's' : '') + ' disponível' + (qtd !== 1 ? 'eis' : '') + '</div>';
            grid.appendChild(card);
        });

    } catch (e) {
        console.error('Erro ao carregar gêneros:', e);
    }
}

async function carregarLivros(generoId) {
    var cfg = CONFIG_GENEROS[generoId] || { cor: '#3d6139', icon: '📚', desc: '' };

    // Preenche o cabeçalho da página de livros
    document.getElementById('badge-genero').textContent = (cfg.icon || '') + ' ' + generoId;
    document.getElementById('badge-genero').style.background = cfg.cor;
    document.getElementById('titulo-genero').textContent = generoId;
    document.getElementById('desc-genero').textContent = cfg.desc;

    try {
        const q = query(collection(db, 'obras'), where('genero', '==', generoId));
        const snap = await getDocs(q);

        livrosBrutos = snap.docs.map(function (d) {
            return Object.assign({ id: d.id }, d.data());
        });

        renderizarLivros(livrosBrutos);
    } catch (e) {
        console.error('Erro ao carregar livros:', e);
    }
}

function renderizarLivros(livros) {
    var lista = livros.slice();

    if (filtroAtual === 'titulo') {
        lista.sort(function (a, b) { return (a.titulo || '').localeCompare(b.titulo || ''); });
    } else if (filtroAtual === 'avaliacao') {
        lista.sort(function (a, b) { return (b.avaliacao || 0) - (a.avaliacao || 0); });
    } else if (filtroAtual === 'recente') {
        lista.sort(function (a, b) { return (b.ano_publicacao || 0) - (a.ano_publicacao || 0); });
    }

    var count = document.getElementById('livros-count');
    count.textContent = lista.length + ' título' + (lista.length !== 1 ? 's' : '') + ' encontrado' + (lista.length !== 1 ? 's' : '');

    var grid = document.getElementById('livros-grid');

    if (lista.length === 0) {
        grid.innerHTML =
            '<div class="estado-vazio">' +
            '<svg viewBox="0 0 24 24" width="48" height="48" fill="#cddacc"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>' +
            '<p>Nenhum título encontrado neste gênero.</p>' +
            '</div>';
        return;
    }

    grid.innerHTML = lista.map(function (livro) {
        var estrelas = gerarEstrelas(livro.avaliacao || 0);
        var capaHtml = livro.capa
            ? '<img src="' + livro.capa + '" alt="' + (livro.titulo || '') + '" loading="lazy" style="width:100%;aspect-ratio:2/3;border-radius:12px;object-fit:cover;box-shadow:0 6px 18px rgba(0,0,0,.18);">'
            : '<div class="livro-capa-placeholder" style="background:' + corAleatoria(livro.titulo) + '">' + (livro.titulo || '') + '</div>';

        return '<div class="livro-card" onclick="window.location=\'detalheslivro.php?id=' + livro.id + '\'">' +
            capaHtml +
            '<div class="livro-titulo">' + (livro.titulo || 'Sem título') + '</div>' +
            '<div class="livro-autor">' + (livro.autor || '') + '</div>' +
            '<div class="livro-stars">' + estrelas + '</div>' +
            '</div>';
    }).join('');
}

function gerarEstrelas(nota) {
    var cheia = Math.round(nota);
    var vazia = 5 - cheia;
    return '★'.repeat(Math.max(0, cheia)) + '☆'.repeat(Math.max(0, vazia));
}

function corAleatoria(seed) {
    var cores = ['#8b1a1a','#1a5c3a','#2c2c2c','#5a1a6e','#1a3a5c','#3d2a00','#1a4a3a','#4a1a00'];
    var idx = (seed || '').split('').reduce(function (acc, c) { return acc + c.charCodeAt(0); }, 0) % cores.length;
    return cores[idx];
}

window.voltarGeneros = function () {
    window.location.href = 'genero.php';
};