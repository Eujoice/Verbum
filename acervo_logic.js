import { db } from "./firebase-config.js"; // Traz a conexão com o Firebase feita no firebase-config.js

import { collection, getDocs, doc, getDoc, query, limit } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js"; // Cria uma "referência" para um documento na coleção

console.log("Script carregou");

// TELA DE ACERVO
async function carregarAcervo() {
    // Pegamos os dois containers que criamos no HTML
    const listaPopulares = document.getElementById('lista-populares');
    const listaClassicos = document.getElementById('lista-classicos');

    // Se nenhum dos dois existir, o script para aqui para evitar erros
    if (!listaPopulares && !listaClassicos) return;

    try {
        // Buscamos 12 obras no total (6 para cada seção)
        const q = query(collection(db, "obras"), limit(12));
        const querySnapshot = await getDocs(q);
        
        const livros = [];
        querySnapshot.forEach((doc) => {
            livros.push({ id: doc.id, ...doc.data() });
        });

        // Função auxiliar para criar o HTML de cada card de livro
        const gerarCardHTML = (livro) => `
            <div class="livro">
                <a href="detalheslivro.php?id=${livro.id}" style="text-decoration: none; color: inherit; display: block;">
                    <img src="${livro.capa}" alt="${livro.titulo}">
                    <p class="titulo">${livro.titulo}</p>
                    <p class="autor">${livro.autor}</p>
                </a>
            </div>
        `;

        // Lógica para preencher a Seção POPULARES (índices 0 a 5)
        if (listaPopulares) {
            const popularesData = livros.slice(0, 6);
            listaPopulares.innerHTML = popularesData.map(gerarCardHTML).join('');
        }

        // Lógica para preencher a Seção CLÁSSICOS (índices 6 a 11)
        if (listaClassicos) {
            // Se o banco tiver menos de 12 livros, ele pegará o que sobrar após o índice 6
            const classicosData = livros.slice(6, 12);
            listaClassicos.innerHTML = classicosData.map(gerarCardHTML).join('');
        }

        console.log("Livros renderizados com sucesso!");

    } catch (error) {
        console.error("Erro ao carregar dados do Firebase:", error);
    }
}

// TELA DE DETALHES
// Pega o ID da URL (ex: detalhes.php?id=id_do_livro)
const urlParams = new URLSearchParams(window.location.search); // window.location.search pega o que vem depois da interrogação no URL
const idLivro = urlParams.get('id');

// Async indica que a função lida com um processo que pode ser demorado, como a busca de dados na internet
async function carregarDadosLivro() {
    if (!idLivro || !document.getElementById('ttl-livro')) return;

    try {
        const docRef = doc(db, "obras", idLivro); // Indica o cainho para o documento: banco "db", pasta "obras", arq "idLivro"
        const docSnap = await getDoc(docRef); // O código espera o Firebase responder e o resultado é armazenado em "docSnap"

        if (docSnap.exists()) {
            const dados = docSnap.data(); // A variável "dados" é um objeto js que contém tudo que foi cadastrado no Firebase (titulo, autor...)

            // Preenche os elementos html com os dados do Firebase
            document.title = dados.titulo; // Muda o título na aba do navegador
            document.getElementById('ttl-livro').innerText = dados.titulo;
            document.getElementById('autor-livro').innerText = dados.autor;
            document.getElementById('capa-livro-det').src = dados.capa;

            const sinopse = dados.sinopse || "Sinopse não disponível."
            document.getElementById('resenha').innerHTML = `
                ${dados.sinopse.substring(0, 200)}<span id="pontos">...</span>
                <span id="mais" style="display: none">${dados.sinopse.substring(200)}</span>
                <button onclick="leiaMais()" id="btnLerMais">Leia mais</button>`;

            document.getElementById('publicacao').innerText = dados.publicacao || "---";
            document.getElementById('editora').innerText = dados.editora || "---";
            document.getElementById('isbn').innerText = dados.isbn || "---";    

        } else {
            alert("Obra não encontrada!");
        }
    }
    catch (error) {
        console.error("Erro ao carregar:", error);
    }
}
carregarAcervo();
carregarDadosLivro(); 
console.log("chamado");
