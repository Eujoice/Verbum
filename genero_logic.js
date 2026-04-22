import { db } from "./firebase-config.js";
import { collection, getDocs, query, where } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

const CONFIG_GENEROS = {
    'Ficção' : { cor: 'linear-gradient(135deg, #7c5cbf, #3eb8a0)', icon: 'imgs/extraterrestrial.png', pattern: 'arcs' },
    'Fantasia': { cor: 'linear-gradient(135deg, #8e44c2, #5e6dd4)', icon: 'imgs/wizard (1).png', pattern: 'stars'  },
    'Romance': { cor: 'linear-gradient(135deg, #d95c7a, #f0965a)', icon: 'imgs/relationship.png', pattern: 'dots' },
    'Romance Gótico': { cor: 'linear-gradient(135deg, #d95c7a, 	#800000)', icon: 'imgs/dracula.png', pattern: 'dots' },
    'Terror': { cor: 'linear-gradient(135deg, #3d2b5e, #1f4068)', icon: 'imgs/scream.png', pattern: 'lines' },
    'Suspense Psicológico': { cor: 'linear-gradient(135deg, #2a6b9c, #6a3a8c)', icon: 'imgs/spider.png', pattern: 'cross' },
    'Literatura Brasileira': { cor: 'linear-gradient(135deg, #1a8c5e, #5ab042)', icon: 'imgs/christ.png', pattern: 'diagonal' },
    'Clássico': { cor: 'linear-gradient(135deg, #c47b2b, #e8b84b)', icon: 'imgs/temple.png', pattern: 'diagonal' },
    'Ficção Científica' : { cor: 'linear-gradient(135deg, #7c5cbf, #e8b84b)', icon: 'imgs/robot.png', pattern: 'arcs' },
    'Ficção Distópica' : { cor: 'linear-gradient(135deg, #FA8072, #e8b84b)', icon: 'imgs/dystopia.png', pattern: 'arcs' },
    'Não Ficção': { cor: 'linear-gradient(135deg, #a0522d, #c8773e)', icon: 'imgs/parchment.png', pattern: 'cross' },
    'Romance Policial': { cor: 'linear-gradient(135deg, #1a3a5c, #2d6e8e)', icon: 'imgs/private-detective.png', pattern: 'arcs' },
    'Biografia': { cor: 'linear-gradient(135deg, #3d7abf, #56b4d3)', icon: 'imgs/biography.png', pattern: 'lines' },
    'HQ': { cor: 'linear-gradient(135deg, #c04fa0, #e87090)', icon: 'imgs/comic.png', pattern: 'dots'},
    'Tecnologia': { cor: 'linear-gradient(135deg, #2F4F4F, #66c47a)', icon: 'imgs/technology.png', pattern: 'dots' },
    'Autoajuda': { cor: 'linear-gradient(135deg, #2e9e72, #66c47a)', icon: 'imgs/love-yourself (1).png', pattern: 'dots' },
    'Outros': { cor: 'linear-gradient(135deg, #5a7a55, #7aaa6a)', icon: 'imgs/more.png', pattern: 'dots' },
};

async function inicializarGeneros() {
    const grid = document.getElementById('generos-grid');
    if (!grid) return;

    grid.innerHTML = "";

    Object.keys(CONFIG_GENEROS).forEach(nome => {
        const config = CONFIG_GENEROS[nome];
        const card = document.createElement('div');
        card.className = 'genero-card';
        card.style.background = config.cor;

        // Inverti a ordem e mudei a estrutura para suportar o layout lateral
        card.innerHTML = `
            <div class="genero-card-pattern ${config.pattern}"></div>
            <div class="genero-icon-wrap">
                <img src="${config.icon}" alt="${nome}">
            </div>
            <div class="genero-nome">${nome}</div>
        `;

        card.onclick = () => carregarLivrosPorGenero(nome);
        grid.appendChild(card);
    });
}

async function carregarLivrosPorGenero(generoNome) {
    const telaGeneros = document.getElementById('tela-generos');
    const telaLivros = document.getElementById('tela-livros');
    const gridLivros = document.getElementById('livros-grid');
    const tituloHtml = document.getElementById('titulo-genero');
    const descHtml = document.getElementById('desc-genero');

    telaGeneros.style.display = 'none';
    telaLivros.style.display = 'block';

    tituloHtml.innerText = generoNome;
    descHtml.innerText = CONFIG_GENEROS[generoNome]?.desc || "";
    gridLivros.innerHTML = '<div class="livro-skeleton"></div><div class="livro-skeleton"></div>';

    try {
        let q;
        const colRef = collection(db, "obras");

        // LÓGICA PARA "OUTROS": busca o que é "Outros" ou o que está vazio/inexistente
        if (generoNome === "Outros") {
            // No Firebase é difícil fazer "WHERE campo == null", 
            // então buscamos todos e filtramos no cliente para garantir
            const allDocs = await getDocs(colRef);
            renderizarLivros(allDocs.docs.filter(doc => {
                const g = doc.data().genero;
                return !g || g === "" || g === "Outros";
            }));
        } else {
            q = query(colRef, where("genero", "==", generoNome));
            const querySnapshot = await getDocs(q);
            renderizarLivros(querySnapshot.docs);
        }

    } catch (error) {
        console.error("Erro ao carregar livros:", error);
    }

    function renderizarLivros(docs) {
        gridLivros.innerHTML = "";
        let count = 0;

        if (docs.length === 0) {
            gridLivros.innerHTML = '<div class="estado-vazio"><p>Nenhum livro encontrado neste gênero.</p></div>';
        }

        docs.forEach((doc) => {
            const livro = doc.data();
            count++;
            gridLivros.innerHTML += `
                <div class="livro-card">
                    <a href="detalheslivro.php?id=${doc.id}" style="text-decoration: none; color: inherit;">
                        <img class="livro-capa" src="${livro.capa}" alt="${livro.titulo}">
                        <div class="livro-titulo">${livro.titulo}</div>
                        <div class="livro-autor">${livro.autor}</div>
                    </a>
                </div>
            `;
        });
        document.getElementById('livros-count').innerText = `${count} títulos encontrados`;
    }
}

window.voltarGeneros = function() {
    document.getElementById('tela-generos').style.display = 'block';
    document.getElementById('tela-livros').style.display = 'none';
};

inicializarGeneros();