import { db } from "./firebase-config.js";
import {
    collection,
    getDocs,
    query,
    limit
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

// --- TELA DE ACERVO ---
async function carregarAcervo() {
    const listaPopulares      = document.getElementById('lista-populares');
    const listaClassicos      = document.getElementById('lista-classicos');
    const listaInternacionais = document.getElementById('lista-internacionais');
    const listaContos         = document.getElementById('lista-contos');

    if (!listaPopulares && !listaClassicos && !listaInternacionais && !listaContos) return;

    try {
        const q = query(collection(db, "obras"), limit(100));
        const snap = await getDocs(q);

        const todos = [];
        snap.forEach((doc) => todos.push({ id: doc.id, ...doc.data() }));

        // Ordena por ID para pegar faixas consistentes
        todos.sort((a, b) => a.id.localeCompare(b.id, undefined, { numeric: true }));

        // Pega 6 livros a partir de um offset, com wraparound
        const fatia = (offset, qtd = 6) => {
            const result = [];
            for (let i = 0; i < qtd; i++) {
                result.push(todos[(offset + i) % todos.length]);
            }
            return result.filter(Boolean);
        };

        const gerarCardHTML = (livro) => `
            <div class="livro">
                <a href="/verbum/pages/detalheslivro.php?id=${livro.id}" style="text-decoration:none;color:inherit;display:block;">
                    <img src="${livro.capa || ''}" alt="${livro.titulo || ''}"
                         style="width:148px;height:210px;border-radius:12px;object-fit:cover;box-shadow:0 8px 22px rgba(0,0,0,0.2);display:block;"
                         onerror="this.src='../assets/imgs/capa-placeholder.png'">
                    <p class="titulo">${livro.titulo || ''}</p>
                    <p class="autor">${livro.autor || ''}</p>
                </a>
            </div>
        `;

        if (listaPopulares)      listaPopulares.innerHTML      = fatia(0).map(gerarCardHTML).join('');
        if (listaClassicos)      listaClassicos.innerHTML      = fatia(6).map(gerarCardHTML).join('');
        if (listaInternacionais) listaInternacionais.innerHTML = fatia(12).map(gerarCardHTML).join('');
        if (listaContos)         listaContos.innerHTML         = fatia(18).map(gerarCardHTML).join('');

    } catch (error) {
        console.error("Erro ao carregar acervo:", error);
    }
}

// --- TELA DE DETALHES ---
const urlParams = new URLSearchParams(window.location.search);
const idLivro = urlParams.get('id');

async function carregarDadosLivro() {
    if (!idLivro || !document.getElementById('ttl-livro')) return;

    try {
        const { doc, getDoc } = await import("https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js");
        const docRef  = doc(db, "obras", idLivro);
        const docSnap = await getDoc(docRef);

        if (!docSnap.exists()) { console.error("Livro não encontrado!"); return; }

        const dados = docSnap.data();

        document.getElementById('ttl-livro').innerText   = dados.titulo  || "Sem título";
        document.getElementById('autor-livro').innerText = dados.autor   || "Autor desconhecido";
        document.getElementById('capa-livro-det').src    = dados.capa    || "";
        document.getElementById('tag-genero').innerText  = dados.genero  || "Literatura";

        document.getElementById('publicacao').innerText  = dados.ano_publicacao || "---";
        document.getElementById('editora').innerText     = dados.editora        || "---";
        document.getElementById('isbn').innerText        = dados.isbn           || "---";
        document.getElementById('exemplares').innerText  = dados.exemplares     || "---";
        document.getElementById('paginas').innerText     = dados.paginas        || "---";
        document.getElementById('status').innerText      = dados.status         || "---";

        const resumo    = dados.resumo || "Resumo não disponível.";
        const resenhaEl = document.getElementById('resenha');
        const limite    = 200;
        if (resumo.length > limite) {
            resenhaEl.innerHTML = `
                ${resumo.substring(0, limite)}<span id="pontos">...</span>
                <span id="mais" style="display:none">${resumo.substring(limite)}</span>
                <button onclick="leiaMais()" id="btnLerMais">Leia mais</button>`;
        } else {
            resenhaEl.innerText = resumo;
        }

        const sinopseEl = document.getElementById('sinopse-completa');
        if (sinopseEl) sinopseEl.innerText = dados.sinopse || "Sinopse completa não disponível.";

        if (document.getElementById('det-colecao'))
            document.getElementById('det-colecao').innerText = dados.colecao || "Nenhuma";
        if (document.getElementById('det-localizacao'))
            document.getElementById('det-localizacao').innerText = dados.localizacao || "---";
        if (document.getElementById('det-exemplares-total'))
            document.getElementById('det-exemplares-total').innerText = dados.exemplares_totais || "0";
        if (document.getElementById('det-avaliacao')) {
            const el    = document.getElementById('det-avaliacao');
            const media = parseFloat(dados.avaliacao_media) || 0;
            const total = parseInt(dados.total_avaliacoes)  || 0;
            if (media > 0 && total > 0) {
                const inteiras = Math.floor(media);
                const decimal  = media - inteiras;
                let estrelas   = '★'.repeat(inteiras);
                if      (decimal >= 0.25 && decimal < 0.75) estrelas += '½';
                else if (decimal >= 0.75)                   estrelas += '★';
                estrelas += '☆'.repeat(Math.max(0, 5 - Math.round(media)));
                el.innerHTML = `<span class="estrelas">${estrelas}</span> — ${media.toFixed(1)} (${total} ${total > 1 ? 'avaliações' : 'avaliação'})`;
            } else {
                el.innerText = "Sem avaliações";
            }
        }
        if (document.getElementById('det-adicionado'))
            document.getElementById('det-adicionado').innerText = dados.data_adicao || "---";
        if (document.getElementById('det-idioma'))
            document.getElementById('det-idioma').innerText = dados.idioma_original || "Português";
        if (document.getElementById('det-traducao'))
            document.getElementById('det-traducao').innerText = dados.traducao || "---";

        buscarRecomendacoes(dados.genero, dados.colecao, idLivro);

    } catch (error) {
        console.error("Erro ao carregar dados do livro:", error);
    }
}

async function buscarRecomendacoes(genero, colecao, idAtual) {
    try {
        const { query: q2, getDocs: gd, collection: col, limit: lim, where } =
            await import("https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js");

        const snap = await gd(q2(col(db, "obras"), lim(20)));
        let similares = [];

        snap.forEach((d) => {
            const data = d.data();
            if (d.id !== idAtual && (data.genero === genero || data.colecao === colecao)) {
                similares.push({ id: d.id, ...data });
            }
        });

        if (similares.length < 4) {
            snap.forEach((d) => {
                if (d.id !== idAtual && !similares.find(s => s.id === d.id)) {
                    similares.push({ id: d.id, ...d.data() });
                }
            });
        }

        if (typeof window.renderizarSimilares === 'function') {
            window.renderizarSimilares(similares, idAtual);
        }
    } catch (error) {
        console.error("Erro ao buscar recomendações:", error);
    }
}

carregarAcervo();
carregarDadosLivro();