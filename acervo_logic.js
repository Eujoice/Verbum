import { db } from "./firebase-config.js"; 
import { 
    collection, 
    getDocs, 
    doc, 
    getDoc, 
    query, 
    limit 
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

// --- CONFIGURAÇÃO INICIAL ---
const urlParams = new URLSearchParams(window.location.search);
const idLivro = urlParams.get('id');

// --- TELA DE ACERVO (Geral) ---
async function carregarAcervo() {
    const listaPopulares = document.getElementById('lista-populares');
    const listaClassicos = document.getElementById('lista-classicos');

    if (!listaPopulares && !listaClassicos) return;

    try {
        const q = query(collection(db, "obras"), limit(12));
        const querySnapshot = await getDocs(q);
        
        const livros = [];
        querySnapshot.forEach((doc) => {
            livros.push({ id: doc.id, ...doc.data() });
        });

        const gerarCardHTML = (livro) => `
            <div class="livro">
                <a href="detalheslivro.php?id=${livro.id}" style="text-decoration: none; color: inherit; display: block;">
                    <img src="${livro.capa}" alt="${livro.titulo}">
                    <p class="titulo">${livro.titulo}</p>
                    <p class="autor">${livro.autor}</p>
                </a>
            </div>
        `;

        if (listaPopulares) {
            listaPopulares.innerHTML = livros.slice(0, 6).map(gerarCardHTML).join('');
        }

        if (listaClassicos) {
            listaClassicos.innerHTML = livros.slice(6, 12).map(gerarCardHTML).join('');
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

            // 3. LOGICA ALTERADA: Resumo ao lado da capa
            const resumo = dados.resumo || "Resumo não disponível.";
            const resenhaEl = document.getElementById('resenha');
            
            // Exibe o resumo com a função de "Leia mais" caso ele seja grande
            const limite = 200;
            if (resumo.length > limite) {
                resenhaEl.innerHTML = `
                    ${resumo.substring(0, limite)}<span id="pontos">...</span>
                    <span id="mais" style="display: none">${resumo.substring(limite)}</span>
                    <button onclick="leiaMais()" id="btnLerMais">Leia mais</button>`;
            } else {
                resenhaEl.innerText = resumo;
            }

            // 4. LOGICA ALTERADA: Sinopse Completa (Card Inferior)
            // Aqui pegamos o campo 'sinopse' do Firebase
            const sinopseCompleta = dados.sinopse || "Sinopse completa não disponível.";
            document.getElementById('sinopse-completa').innerText = sinopseCompleta;

            // 5. Card de Informações do Acervo
            if(document.getElementById('det-colecao')) 
                document.getElementById('det-colecao').innerText = dados.colecao || "Nenhuma";
            if(document.getElementById('det-localizacao')) 
                document.getElementById('det-localizacao').innerText = dados.localizacao || "---";
            if(document.getElementById('det-exemplares-total')) 
                document.getElementById('det-exemplares-total').innerText = dados.exemplares_totais || "0";
            if (document.getElementById('det-avaliacao')) {
            const el = document.getElementById('det-avaliacao');

            if (dados.avaliacao) {
                const nota = parseFloat(dados.avaliacao);

                let estrelas = '';

                const inteiras = Math.floor(nota);
                const decimal = nota - inteiras;

                let meia = 0;

                if (decimal >= 0.75) {
                    // vira estrela cheia
                    estrelas += '★'.repeat(inteiras + 1);
                } else {
                    estrelas += '★'.repeat(inteiras);

                    if (decimal >= 0.25) {
                        estrelas += '⯨'; // meia estrela
                        meia = 1;
                    }
                }

                const totalEstrelas = inteiras + meia + (decimal >= 0.75 ? 1 : 0);
                const vazias = 5 - totalEstrelas;

                estrelas += '☆'.repeat(vazias);

                el.innerHTML = `<span class="estrelas">${estrelas}</span> — ${nota.toFixed(1)}`;
            } else {
                el.innerText = "Sem avaliações";
            }
        }
            if(document.getElementById('det-adicionado')) 
                document.getElementById('det-adicionado').innerText = dados.data_adicao || "---";
            if(document.getElementById('det-idioma')) 
                document.getElementById('det-idioma').innerText = dados.idioma_original || "Português";

            // 6. Chamada para Recomendações
            buscarRecomendacoes(dados.genero, dados.colecao, idLivro);

        } else {
            console.error("Livro não encontrado!");
        }
    } catch (error) {
        console.error("Erro ao carregar dados do livro:", error);
    }
}

// --- LÓGICA DE RECOMENDAÇÕES (Gênero ou Coleção) ---
async function buscarRecomendacoes(genero, colecao, idAtual) {
    try {
        const obrasRef = collection(db, "obras");
        let similares = [];

        // Busca uma amostra do acervo
        const q = query(obrasRef, limit(20)); 
        const querySnapshot = await getDocs(q);
        
        querySnapshot.forEach((doc) => {
            const d = doc.data();
            // Filtra: não pode ser o livro atual E deve ser do mesmo gênero OU mesma coleção
            if (doc.id !== idAtual) {
                if (d.genero === genero || d.colecao === colecao) {
                    similares.push({ id: doc.id, ...d });
                }
            }
        });

        // Fallback: Se não houver similares suficientes, preenche com outros livros aleatórios
        if (similares.length < 4) {
            querySnapshot.forEach((doc) => {
                if (doc.id !== idAtual && !similares.find(s => s.id === doc.id)) {
                    similares.push({ id: doc.id, ...doc.data() });
                }
            });
        }

        // Envia para a função de renderização no detalheslivro.php
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