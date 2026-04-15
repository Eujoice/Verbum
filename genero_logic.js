import { db } from "./firebase-config.js";
import { collection, getDocs, query, where } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

/* ── Ícones SVG por gênero (paths do Material / custom) ── */
const ICONS = {
    rocket:    '<svg viewBox="0 0 24 24"><path d="M9.37 5.51A7.35 7.35 0 0 0 9.1 7.5c0 4.08 3.32 7.4 7.4 7.4.68 0 1.35-.09 1.99-.27A7.014 7.014 0 0 1 12 19c-3.86 0-7-3.14-7-7 0-2.93 1.81-5.45 4.37-6.49zM12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.14-9.8c-.44-.06-.9-.1-1.36-.1z"/></svg>',
    sword:     '<svg viewBox="0 0 24 24"><path d="M6.92 5H5L3 7l3.5 3.5L5 12l1.5 1.5 3-3 .5.5-3 3L8.5 15.5l3-3 .5.5-3 3L10.5 17.5l1.5-1.5L15.5 19.5l2-2v-1.92L6.92 5zM19 3l-6.5 6.5 2 2L21 5l-2-2z"/></svg>',
    heart:     '<svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
    ghost:     '<svg viewBox="0 0 24 24"><path d="M12 2a9 9 0 0 0-9 9v11l3-3 3 3 3-3 3 3 3-3v-11a9 9 0 0 0-9-9zm-3.5 10a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm7 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg>',
    scroll:    '<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>',
    star:      '<svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    columns:   '<svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>',
    search:    '<svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
    person:    '<svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
    comic:     '<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>',
    ship:      '<svg viewBox="0 0 24 24"><path d="M20 21c-1.39 0-2.78-.47-4-1.32-2.44 1.71-5.56 1.71-8 0C6.78 20.53 5.39 21 4 21H2v2h2c1.38 0 2.74-.35 4-.99 2.52 1.29 5.48 1.29 8 0 1.26.65 2.62.99 4 .99h2v-2h-2zM3.95 19H4c1.6 0 3.02-.88 4-2 .98 1.12 2.4 2 4 2s3.02-.88 4-2c.98 1.12 2.4 2 4 2h.05l1.89-6.68c.08-.26.06-.54-.06-.79s-.34-.45-.6-.55L19 10.62V6c0-1.1-.9-2-2-2h-3V1H10v3H7c-1.1 0-2 .9-2 2v4.62l-1.29.51c-.26.1-.48.3-.6.55s-.14.53-.06.79L3.95 19zM9 4h6v1H9V4zm8 7.87.66.26-1.72 6.04c-.51-.3-.99-.65-1.44-1.03L17 11.87zM7 6h10v4.62l-5 1.98-5-1.98V6zM6.34 12.13l-1.72-6.04.66-.26 1.5 5.27c-.45.38-.93.73-1.44 1.03z"/></svg>',
    atom:      '<svg viewBox="0 0 24 24"><path d="M18.5 8.5c.28 0 .5.22.5.5s-.22.5-.5.5-.5-.22-.5-.5.22-.5.5-.5M12 11c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm3.55 12.45C14.68 16.28 13.38 17 12 17s-2.68-.72-3.55-2.55C6.28 13.25 6 12.56 6 12s.28-1.25 2.45-2.45C9.32 7.72 10.62 7 12 7s2.68.72 3.55 2.55C17.72 10.75 18 11.44 18 12s-.28 1.25-2.45 2.45z"/></svg>',
    plant:     '<svg viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21H5.71c.51-1.2 1.06-2.38 1.78-3.47C9.01 16.33 10.87 17 13 17c4.42 0 8-3.58 8-8v-1h-4z"/></svg>',
    city:      '<svg viewBox="0 0 24 24"><path d="M15 11V5l-3-3-3 3v2H3v14h18V11h-6zm-8 8H5v-2h2v2zm0-4H5v-2h2v2zm0-4H5v-2h2v2zm6 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm6 12h-2v-2h2v2zm0-4h-2v-2h2v2z"/></svg>',
    brazil:    '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
    castle:    '<svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2h-2V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-1.5c0-2.33 4.67-3.5 7-3.5s7 1.17 7 3.5V19z"/></svg>',
    brain:     '<svg viewBox="0 0 24 24"><path d="M21 12.22C21 6.73 16.74 3 12 3c-4.69 0-9 3.65-9 9.28-.6.34-1 .98-1 1.72v2c0 1.1.9 2 2 2h1v-6.1c0-3.87 3.13-7 7-7s7 3.13 7 7V19h-8v2h8c1.1 0 2-.9 2-2v-1.22c.59-.31 1-.92 1-1.64v-2.3c0-.7-.41-1.31-1-1.62z"/></svg>',
    books:     '<svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z"/></svg>',
};

/* Padrões SVG geométricos (linhas, grades, diagonais) */
const PATTERNS = {
    lines: `<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
        <line x1="0" y1="20" x2="80" y2="20" stroke="white" stroke-width="1.5"/>
        <line x1="0" y1="40" x2="80" y2="40" stroke="white" stroke-width="1.5"/>
        <line x1="0" y1="60" x2="80" y2="60" stroke="white" stroke-width="1.5"/>
        <line x1="0" y1="80" x2="80" y2="80" stroke="white" stroke-width="1.5"/>
    </svg>`,
    dots: `<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
        <circle cx="10" cy="10" r="3" fill="white"/><circle cx="30" cy="10" r="3" fill="white"/>
        <circle cx="50" cy="10" r="3" fill="white"/><circle cx="70" cy="10" r="3" fill="white"/>
        <circle cx="20" cy="30" r="3" fill="white"/><circle cx="40" cy="30" r="3" fill="white"/>
        <circle cx="60" cy="30" r="3" fill="white"/><circle cx="80" cy="30" r="3" fill="white"/>
        <circle cx="10" cy="50" r="3" fill="white"/><circle cx="30" cy="50" r="3" fill="white"/>
        <circle cx="50" cy="50" r="3" fill="white"/><circle cx="70" cy="50" r="3" fill="white"/>
        <circle cx="20" cy="70" r="3" fill="white"/><circle cx="40" cy="70" r="3" fill="white"/>
        <circle cx="60" cy="70" r="3" fill="white"/><circle cx="80" cy="70" r="3" fill="white"/>
    </svg>`,
    diagonal: `<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
        <line x1="0" y1="0" x2="80" y2="80" stroke="white" stroke-width="1.5"/>
        <line x1="20" y1="0" x2="80" y2="60" stroke="white" stroke-width="1.5"/>
        <line x1="40" y1="0" x2="80" y2="40" stroke="white" stroke-width="1.5"/>
        <line x1="0" y1="20" x2="60" y2="80" stroke="white" stroke-width="1.5"/>
        <line x1="0" y1="40" x2="40" y2="80" stroke="white" stroke-width="1.5"/>
    </svg>`,
    cross: `<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
        <line x1="0" y1="20" x2="80" y2="20" stroke="white" stroke-width="1"/>
        <line x1="0" y1="40" x2="80" y2="40" stroke="white" stroke-width="1"/>
        <line x1="0" y1="60" x2="80" y2="60" stroke="white" stroke-width="1"/>
        <line x1="20" y1="0" x2="20" y2="80" stroke="white" stroke-width="1"/>
        <line x1="40" y1="0" x2="40" y2="80" stroke="white" stroke-width="1"/>
        <line x1="60" y1="0" x2="60" y2="80" stroke="white" stroke-width="1"/>
    </svg>`,
    arcs: `<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
        <circle cx="0" cy="0" r="30" stroke="white" stroke-width="1.5" fill="none"/>
        <circle cx="0" cy="0" r="50" stroke="white" stroke-width="1.5" fill="none"/>
        <circle cx="0" cy="0" r="70" stroke="white" stroke-width="1.5" fill="none"/>
    </svg>`,
};

const CONFIG_GENEROS = {
    'Ficção':               { cor: '#1a2a5e', icon: 'rocket',  pattern: 'arcs',     desc: 'Universos futuristas, tecnologia e imaginação além dos limites.' },
    'Fantasia':             { cor: '#2a1a5e', icon: 'sword',   pattern: 'diagonal', desc: 'Mundos mágicos, criaturas e aventuras épicas.' },
    'Romance':              { cor: '#5e1a2a', icon: 'heart',   pattern: 'dots',     desc: 'Histórias de amor, paixão e relacionamentos humanos.' },
    'Terror':               { cor: '#1a1a1a', icon: 'ghost',   pattern: 'lines',    desc: 'Suspense, mistério e histórias que arrepiam a espinha.' },
    'Clássico':             { cor: '#3d2800', icon: 'scroll',  pattern: 'diagonal', desc: 'Obras imortais que moldaram a literatura mundial.' },
    'Autoajuda':            { cor: '#1a4a2a', icon: 'star',    pattern: 'dots',     desc: 'Desenvolvimento pessoal, motivação e bem-estar.' },
    'História':             { cor: '#2a1a00', icon: 'columns', pattern: 'cross',    desc: 'Fatos, civilizações e personagens que mudaram o mundo.' },
    'Policial':             { cor: '#001a2a', icon: 'search',  pattern: 'arcs',     desc: 'Crimes, investigações e reviravoltas surpreendentes.' },
    'Biografia':            { cor: '#1a1a3a', icon: 'person',  pattern: 'lines',    desc: 'Vidas extraordinárias que inspiram e ensinam.' },
    'HQ':                   { cor: '#3a0a1a', icon: 'comic',   pattern: 'dots',     desc: 'Quadrinhos, graphic novels e mangás de todos os estilos.' },
    'Ficção Clássica':      { cor: '#2a1a00', icon: 'ship',    pattern: 'diagonal', desc: 'A ficção que resistiu ao tempo e se tornou patrimônio da humanidade.' },
    'Ficção Científica':    { cor: '#002a3a', icon: 'atom',    pattern: 'arcs',     desc: 'Ciência, futuro e os limites da imaginação humana.' },
    'Naturalismo':          { cor: '#1a3a1a', icon: 'plant',   pattern: 'lines',    desc: 'Realismo cru e a influência do meio sobre o ser humano.' },
    'Distopia':             { cor: '#2a0a00', icon: 'city',    pattern: 'cross',    desc: 'Sociedades opressivas e futuros onde a liberdade foi perdida.' },
    'Literatura Brasileira':{ cor: '#3a2000', icon: 'brazil',  pattern: 'diagonal', desc: 'A riqueza e diversidade da literatura produzida no Brasil.' },
    'Romance Gótico':       { cor: '#1a001a', icon: 'castle',  pattern: 'arcs',     desc: 'Atmosfera sombria, paixões intensas e o sobrenatural.' },
    'Suspense Psicológico': { cor: '#001a1a', icon: 'brain',   pattern: 'cross',    desc: 'Tensão mental, reviravoltas e narrativas que perturbam.' },
    'Outros':               { cor: '#2a2a2a', icon: 'books',   pattern: 'dots',     desc: 'Obras que escapam das categorias tradicionais.' },
};

var filtroAtual = 'titulo';
var livrosBrutos = [];

const urlParams = new URLSearchParams(window.location.search);
const generoParam = urlParams.get('id');

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

        Object.keys(contagem).forEach(function (nomeGenero) {
            var cfg = CONFIG_GENEROS[nomeGenero] || { cor: '#3d6139', icon: 'books', pattern: 'dots', desc: '' };
            var qtd = contagem[nomeGenero];
            var iconSvg = ICONS[cfg.icon] || ICONS['books'];
            var patternSvg = PATTERNS[cfg.pattern] || PATTERNS['dots'];

            var card = document.createElement('a');
            card.className = 'genero-card';
            card.href = 'genero.php?id=' + encodeURIComponent(nomeGenero);
            card.style.background = cfg.cor;
            card.innerHTML =
                '<div class="genero-card-pattern">' + patternSvg + '</div>' +
                '<div class="genero-icon-wrap">' + iconSvg + '</div>' +
                '<div class="genero-nome">' + nomeGenero + '</div>' +
                '<div class="genero-qtd">' + qtd + ' título' + (qtd !== 1 ? 's' : '') + ' disponível' + (qtd !== 1 ? 'eis' : '') + '</div>';
            grid.appendChild(card);
        });

    } catch (e) {
        console.error('Erro ao carregar gêneros:', e);
    }
}

async function carregarLivros(generoId) {
    var cfg = CONFIG_GENEROS[generoId] || { cor: '#3d6139', icon: 'books', desc: '' };
    var iconSvg = ICONS[cfg.icon] || ICONS['books'];

    // Badge com ícone SVG — sem repetir o título
    var badgeIconEl = document.getElementById('badge-genero');
    if (badgeIconEl) {
        // Substitui o badge por ícone + desc no novo layout
        var livrosHeader = document.querySelector('.livros-header');
        if (livrosHeader) {
            livrosHeader.innerHTML =
                '<div class="genero-badge-icon" style="background:' + cfg.cor + '">' + iconSvg + '</div>' +
                '<div class="livros-header-text">' +
                '<p class="genero-desc">' + cfg.desc + '</p>' +
                '</div>';
        }
    }

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