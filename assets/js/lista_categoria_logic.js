import { db } from "./firebase-config.js";
import {
    collection,
    getDocs,
    query,
    limit
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

// Faixa de IDs por categoria (offset, quantidade)
const FAIXAS = {
    populares:      { offset: 0,  qtd: 12, desc: "Os livros mais emprestados e avaliados do acervo" },
    classicos:      { offset: 6,  qtd: 12, desc: "Obras fundamentais da literatura universal" },
    internacionais: { offset: 12, qtd: 12, desc: "Grandes obras da literatura ao redor do mundo" },
    contos:         { offset: 18, qtd: 12, desc: "Histórias curtas de grandes autores" },
};

let todoLivros = [];
let filtroAtual = "titulo";

async function iniciar() {
    const cat    = (CATEGORIA_ID || '').toLowerCase();
    const config = FAIXAS[cat] || { offset: 0, qtd: 12, desc: '' };

    const desc   = document.getElementById('desc-categoria');
    const titulo = document.getElementById('titulo-categoria');
    if (desc  && config.desc)  desc.textContent  = config.desc;
    if (titulo && CATEGORIA_NOME) titulo.textContent = CATEGORIA_NOME;

    await carregarLivros(config);
    configurarFiltros();
}

async function carregarLivros(config) {
    try {
        const snap = await getDocs(query(collection(db, "obras"), limit(100)));

        const todos = [];
        snap.forEach(d => todos.push({ id: d.id, ...d.data() }));

        // Ordena por número no ID: L1, L2, ..., L10, L11
        todos.sort((a, b) => {
            const na = parseInt((a.id || '').replace(/\D/g, '')) || 0;
            const nb = parseInt((b.id || '').replace(/\D/g, '')) || 0;
            return na - nb;
        });

        // Fatia com wraparound
        const fatia = [];
        const total = todos.length;
        if (total > 0) {
            for (let i = 0; i < config.qtd; i++) {
                fatia.push(todos[(config.offset + i) % total]);
            }
        }

        todoLivros = fatia.filter(Boolean);
        aplicarFiltro();

    } catch (error) {
        console.error("Erro ao carregar categoria:", error);
        const grid = document.getElementById('livros-grid');
        if (grid) grid.innerHTML = '<p style="color:#888;padding:20px;">Erro ao carregar livros.</p>';
    }
}

function renderizarLivros(livros) {
    const grid  = document.getElementById('livros-grid');
    const count = document.getElementById('livros-count');

    if (count) {
        count.textContent = `${livros.length} título${livros.length !== 1 ? 's' : ''} encontrado${livros.length !== 1 ? 's' : ''}`;
    }

    if (!grid) return;

    if (!livros.length) {
        grid.innerHTML = `<div style="text-align:center;padding:60px 20px;color:#888;"><p>Nenhum livro encontrado.</p></div>`;
        return;
    }

    // Mesma estrutura exata do acervo: .livro > a > img + .titulo + .autor
    grid.innerHTML = livros.map(livro => `
        <div class="livro">
            <a href="/verbum/pages/detalheslivro.php?id=${livro.id}" style="text-decoration:none;color:inherit;display:block;">
                <img src="${livro.capa || ''}" alt="${livro.titulo || ''}"
                     onerror="this.src='../assets/imgs/capa-placeholder.png'">
                <p class="titulo">${livro.titulo || ''}</p>
                <p class="autor">${livro.autor || ''}</p>
            </a>
        </div>
    `).join('');
}

function configurarFiltros() {
    document.querySelectorAll('.filtro-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filtro-pill').forEach(b => b.classList.remove('ativo'));
            btn.classList.add('ativo');
            filtroAtual = btn.dataset.filtro;
            aplicarFiltro();
        });
    });
}

function aplicarFiltro() {
    const ordenados = [...todoLivros];
    if (filtroAtual === 'titulo') {
        ordenados.sort((a, b) => (a.titulo || '').localeCompare(b.titulo || '', 'pt-BR'));
    } else if (filtroAtual === 'avaliacao') {
        ordenados.sort((a, b) => (parseFloat(b.avaliacao_media) || 0) - (parseFloat(a.avaliacao_media) || 0));
    }
    renderizarLivros(ordenados);
}

iniciar();