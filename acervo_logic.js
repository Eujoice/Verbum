import { db } from "./firebase-config.js"; 
import { 
    collection, 
    getDocs, 
    doc, 
    getDoc, 
    query, 
    limit,
    where
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

// --- CONFIGURAÇÃO INICIAL ---
const urlParams = new URLSearchParams(window.location.search);
const idLivro = urlParams.get('id');

// --- HELPER: busca livros por lista de IDs ---
async function buscarPorIds(ids) {
    const livros = [];
    await Promise.all(ids.map(async (id) => {
        try {
            const snap = await getDoc(doc(db, "obras", id));
            if (snap.exists()) livros.push({ id: snap.id, ...snap.data() });
        } catch (e) {}
    }));
    // Mantém a ordem da lista de IDs
    return ids.map(id => livros.find(l => l.id === id)).filter(Boolean);
}

// --- HELPER: gera HTML do card ---
const gerarCardHTML = (livro) => `
    <div class="livro">
        <a href="detalheslivro.php?id=${livro.id}" style="text-decoration: none; color: inherit; display: block;">
            <img src="${livro.capa}" alt="${livro.titulo}">
            <p class="titulo">${livro.titulo}</p>
            <p class="autor">${livro.autor}</p>
        </a>
    </div>
`;

// --- TELA DE ACERVO (Geral) ---
async function carregarAcervo() {
    const listaPopulares      = document.getElementById('lista-populares');
    const listaClassicos      = document.getElementById('lista-classicos');
    const listaInternacionais = document.getElementById('lista-internacionais');
    const listaFiccao         = document.getElementById('lista-ficcao');

    if (!listaPopulares && !listaClassicos && !listaInternacionais && !listaFiccao) return;

    try {
        // Populares: L01–L06
        if (listaPopulares) {
            const livros = await buscarPorIds(['L01','L02','L03','L04','L05','L06']);
            listaPopulares.innerHTML = livros.map(gerarCardHTML).join('');
        }

        // Clássicos: L07–L12
        if (listaClassicos) {
            const livros = await buscarPorIds(['L07','L08','L09','L10','L11','L12']);
            listaClassicos.innerHTML = livros.map(gerarCardHTML).join('');
        }

        // Internacionais: L13–L18
        if (listaInternacionais) {
            const livros = await buscarPorIds(['L13','L14','L15','L16','L17','L18']);
            listaInternacionais.innerHTML = livros.map(gerarCardHTML).join('');
        }

        // Ficção: L19–L24
        if (listaFiccao) {
            const livros = await buscarPorIds(['L19','L20','L21','L22','L23','L24']);
            listaFiccao.innerHTML = livros.map(gerarCardHTML).join('');
        }

    } catch (error) {
        console.error("Erro ao carregar acervo:", error);
    }
}

// --- TELA DE DETALHES (Livro Principal) ---
async function carregarDadosLivro() {
    if (!idLivro || !document.getElementById('ttl-livro')) return;

    try {
        const docRef = doc(db, "obras", idLivro);
        const docSnap = await getDoc(docRef);

        if (docSnap.exists()) {
            const dados = docSnap.data();

            // 1. Identificação
            document.getElementById('ttl-livro').innerText = dados.titulo || "Sem título";
            document.getElementById('autor-livro').innerText = dados.autor || "Autor desconhecido";
            document.getElementById('capa-livro-det').src = dados.capa || "";
            document.getElementById('tag-genero').innerText = dados.genero || "Literatura";

            // 2. Tabela de Detalhes Rápidos
            document.getElementById('publicacao').innerText = dados.ano_publicacao || "---";
            document.getElementById('editora').innerText = dados.editora || "---";
            document.getElementById('isbn').innerText = dados.isbn || "---";
            document.getElementById('exemplares').innerText = dados.exemplares || "---";
            document.getElementById('paginas').innerText = dados.paginas || "---";
            document.getElementById('status').innerText = dados.status || "---";

            // 3. Resumo ao lado da capa
            const resumo = dados.resumo || "Resumo não disponível.";
            const resenhaEl = document.getElementById('resenha');
            const limite = 200;
            if (resumo.length > limite) {
                resenhaEl.innerHTML = `
                    ${resumo.substring(0, limite)}<span id="pontos">...</span>
                    <span id="mais" style="display: none">${resumo.substring(limite)}</span>
                    <button onclick="leiaMais()" id="btnLerMais">Leia mais</button>`;
            } else {
                resenhaEl.innerText = resumo;
            }

            // 4. Sinopse Completa (Card Inferior)
            const sinopseCompleta = dados.sinopse || "Sinopse completa não disponível.";
            document.getElementById('sinopse-completa').innerText = sinopseCompleta;

            // 5. Card de Informações do Acervo
            if (document.getElementById('det-colecao'))
                document.getElementById('det-colecao').innerText = dados.colecao || "Nenhuma";
            if (document.getElementById('det-localizacao'))
                document.getElementById('det-localizacao').innerText = dados.localizacao || "---";
            if (document.getElementById('det-exemplares-total'))
                document.getElementById('det-exemplares-total').innerText = dados.exemplares_totais || "0";
            if (document.getElementById('det-avaliacao')) {
                const el = document.getElementById('det-avaliacao');
                const media = parseFloat(dados.avaliacao_media) || 0;
                const total = parseInt(dados.total_avaliacoes) || 0;
                if (media > 0 && total > 0) {
                    const inteiras = Math.floor(media);
                    const decimal  = media - inteiras;
                    let estrelas = '★'.repeat(inteiras);
                    if (decimal >= 0.25 && decimal < 0.75) estrelas += '½';
                    else if (decimal >= 0.75) estrelas += '★';
                    const vazias = 5 - Math.round(media);
                    estrelas += '☆'.repeat(Math.max(0, vazias));
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

            // 6. Recomendações
            buscarRecomendacoes(dados.genero, dados.colecao, idLivro);

        } else {
            console.error("Livro não encontrado!");
        }
    } catch (error) {
        console.error("Erro ao carregar dados do livro:", error);
    }
}

// --- LÓGICA DE RECOMENDAÇÕES ---
async function buscarRecomendacoes(genero, colecao, idAtual) {
    try {
        const obrasRef = collection(db, "obras");
        let similares = [];

        const q = query(obrasRef, limit(20));
        const querySnapshot = await getDocs(q);

        querySnapshot.forEach((doc) => {
            const d = doc.data();
            if (doc.id !== idAtual) {
                if (d.genero === genero || d.colecao === colecao) {
                    similares.push({ id: doc.id, ...d });
                }
            }
        });

        if (similares.length < 4) {
            querySnapshot.forEach((doc) => {
                if (doc.id !== idAtual && !similares.find(s => s.id === doc.id)) {
                    similares.push({ id: doc.id, ...doc.data() });
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

// --- INICIALIZAÇÃO ---
carregarAcervo();
carregarDadosLivro();