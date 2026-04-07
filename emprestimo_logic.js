import { db } from "./firebase-config.js"; 
import { 
    collection, 
    addDoc, 
    onSnapshot, 
    query, 
    orderBy, 
    serverTimestamp 
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

// Referência para a coleção de empréstimos
const colRef = collection(db, "emprestimos");

// --- FUNÇÃO 1: SALVAR EMPRÉSTIMO ---
const btnEmprestar = document.getElementById('btnEmprestar');

if (btnEmprestar) {
    btnEmprestar.addEventListener('click', async () => {
        const nome = document.getElementById('nomeUsuario').value;
        const livro = document.getElementById('codigoLivro').value;
        const dEmp = document.getElementById('dataEmp').value;
        const dPrev = document.getElementById('dataPrev').value;

        // Validação simples
        if (!nome || !livro || !dEmp) {
            alert("Preencha o nome, código do livro e data de empréstimo!");
            return;
        }

        try {
            await addDoc(colRef, {
                usuario: nome,
                livroCodigo: livro,
                dataEmprestimo: dEmp,
                dataPrevisao: dPrev,
                criadoEm: serverTimestamp() // Usado para ordenar a lista
            });
            
            alert("Sucesso! O livro agora aparecerá na lista abaixo.");
            document.getElementById('formEmprestimo').reset(); // Limpa os campos
        } catch (error) {
            console.error("Erro ao salvar no Firebase:", error);
            alert("Erro ao salvar. Verifique o console.");
        }
    });
}

// --- FUNÇÃO 2: ATUALIZAR LISTA EM TEMPO REAL ---
function carregarListaEmprestimos() {
    const tabelaBody = document.getElementById('lista-emprestimos-firebase');
    if (!tabelaBody) return;

    // Busca os dados ordenados pelo mais recente
    const q = query(colRef, orderBy("criadoEm", "desc"));

    // Escuta o banco de dados
    onSnapshot(q, (snapshot) => {
        tabelaBody.innerHTML = ""; // Limpa para não duplicar

        snapshot.forEach((doc) => {
            const item = doc.data();
            // Monta a linha da tabela
            tabelaBody.innerHTML += `
                <tr>
                    <td><strong>${item.livroCodigo}</strong></td>
                    <td>${item.usuario}</td>
                    <td>${item.dataEmprestimo}</td>
                    <td>${item.dataPrevisao}</td>
                    <td><button class="btn-tabela">Detalhes</button></td>
                </tr>
            `;
        });
    });
}

// Inicia a escuta da lista assim que o script carrega
carregarListaEmprestimos();