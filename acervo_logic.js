import { db } from "./firebase-config.js"; // Traz a conexão com o Firebase feita no firebase-config.js

import { collection, getDocs, doc, getDoc, query, limit } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js"; // Cria uma "referência" para um documento na coleção

console.log("Script carregou");

// TELA DE ACERVO

async function carregarAcervo() {
    const lista = document.getElementById('lista-livros-firebase');
    if (!lista) return; // Só executa se estiver na pág de acervo

    try {
        const q = query(collection(db, "obras"), limit(6)); // Limita a exibição de livros à 6
        const querySnapshot = await getDocs(q);
        lista.innerHTML = ""; // Limpa o container antes de carregar

        querySnapshot.forEach((doc) => {
        const livro = doc.data();
        console.log("renderizando livro");
        lista.innerHTML += `
            <div class="livro">
                <a href="detalheslivro.php?id=${doc.id}" style="text-decoration: none; color: inherit; display: block";>
                    <img src="${livro.capa}">
                    <p class="titulo">${livro.titulo}</p>
                    <p class="autor">${livro.autor}</p>
                </a>
            </div>
        `
});
    } catch (error) {
    console.error("Erro ao carregar acervo:", error);
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
