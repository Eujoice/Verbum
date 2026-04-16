import { db } from "./firebase-config.js";
import { collection, getDocs, query, where } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

const CONFIG_GENEROS = {
    'Ficção': { cor: 'linear-gradient(135deg, #7c5cbf, #3eb8a0)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616215.png', pattern: 'arcs', desc: 'Universos futuristas, tecnologia e imaginação além dos limites.' },
    'Fantasia': { cor: 'linear-gradient(135deg, #8e44c2, #5e6dd4)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616231.png', pattern: 'stars', desc: 'Mundos mágicos, criaturas e aventuras épicas.' },
    'Romance': { cor: 'linear-gradient(135deg, #d95c7a, #f0965a)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616223.png', pattern: 'dots', desc: 'Histórias de amor, paixão e relacionamentos humanos.' },
    'Terror': { cor: 'linear-gradient(135deg, #3d2b5e, #1f4068)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616210.png', pattern: 'lines', desc: 'Suspense, mistério e histórias que arrepiam a espinha.' },
    'Clássico': { cor: 'linear-gradient(135deg, #c47b2b, #e8b84b)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616217.png', pattern: 'diagonal', desc: 'Obras imortais que moldaram a literatura mundial.' },
    'Autoajuda': { cor: 'linear-gradient(135deg, #2e9e72, #66c47a)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616220.png', pattern: 'dots', desc: 'Desenvolvimento pessoal, motivação e bem-estar.' },
    'História': { cor: 'linear-gradient(135deg, #a0522d, #c8773e)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616228.png', pattern: 'cross', desc: 'Fatos, civilizações e personagens que mudaram o mundo.' },
    'Policial': { cor: 'linear-gradient(135deg, #1a3a5c, #2d6e8e)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616219.png', pattern: 'arcs', desc: 'Crimes, investigações e reviravoltas surpreendentes.' },
    'Biografia': { cor: 'linear-gradient(135deg, #3d7abf, #56b4d3)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616209.png', pattern: 'lines', desc: 'Vidas extraordinárias que inspiram e ensinam.' },
    'HQ': { cor: 'linear-gradient(135deg, #c04fa0, #e87090)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616211.png', pattern: 'dots', desc: 'Quadrinhos, graphic novels e mangás de todos os estilos.' },
    'Literatura Brasileira': { cor: 'linear-gradient(135deg, #1a8c5e, #5ab042)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616230.png', pattern: 'diagonal', desc: 'A riqueza e diversidade da literatura produzida no Brasil.' },
    'Suspense Psicológico': { cor: 'linear-gradient(135deg, #2a6b9c, #6a3a8c)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616226.png', pattern: 'cross', desc: 'Tensão mental, reviravoltas e narrativas que perturbam.' },
    'Outros': { cor: 'linear-gradient(135deg, #5a7a55, #7aaa6a)', icon: 'https://cdn-icons-png.flaticon.com/512/3616/3616214.png', pattern: 'dots', desc: 'Obras que escapam das categorias tradicionais.' },
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
        
        card.innerHTML = `
            <div class="genero-card-pattern ${config.pattern}"></div>
            <div class="genero-icon-wrap">
                <img src="${config.icon}" style="width: 22px; height: 22px; filter: brightness(0) invert(1);">
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
        const q = query(collection(db, "obras"), where("genero", "==", generoNome));
        const querySnapshot = await getDocs(q);
        
        gridLivros.innerHTML = "";
        let count = 0;

        if (querySnapshot.empty) {
            gridLivros.innerHTML = '<div class="estado-vazio"><p>Nenhum livro encontrado neste gênero.</p></div>';
        }

        querySnapshot.forEach((doc) => {
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

    } catch (error) {
        console.error("Erro ao carregar livros:", error);
    }
}

window.voltarGeneros = function() {
    document.getElementById('tela-generos').style.display = 'block';
    document.getElementById('tela-livros').style.display = 'none';
};

inicializarGeneros();