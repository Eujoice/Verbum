// busca-detalhes.js

document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("pesquisa");
    if (!input) return;

    let container = document.getElementById("resultados-busca");

    if (!container) {
        container = document.createElement("div");
        container.id = "resultados-busca";
        container.className = "resultados-busca";
        input.parentElement.appendChild(container);
    }

    input.addEventListener("input", async function () {
        const termo = this.value.trim();

        if (termo.length < 2) {
            container.style.display = "none";
            return;
        }

        try {
            const res = await fetch(`buscar_livros.php?q=${encodeURIComponent(termo)}`);
            const dados = await res.json();

            if (dados.length === 0) {
                container.innerHTML = `<div class="resultado-vazio">Nenhum livro encontrado</div>`;
                container.style.display = "block";
                return;
            }

            container.innerHTML = dados.map(livro => {
                const statusClasse = livro.status.toLowerCase().includes("dispon")
                    ? "disp"
                    : "emp";

                const capa = livro.capa || "imgs/sem-capa.png";

                return `
                    <a href="detalheslivro.php?id=${livro.id}" class="resultado-item">
                        <div class="res-capa">
                            <img src="${capa}" alt="${livro.titulo}">
                        </div>

                        <div class="res-conteudo">
                            <div class="res-titulo">${livro.titulo}</div>

                            <div class="res-linha">
                                <span class="res-id">#${livro.id}</span>
                                <span class="res-status ${statusClasse}">
                                    ${livro.status}
                                </span>
                            </div>
                        </div>
                    </a>
                `;
            }).join("");

            container.style.display = "block";

        } catch (erro) {
            console.error("Erro na busca:", erro);
        }
    });

    document.addEventListener("click", (e) => {
        if (!e.target.closest(".busca")) {
            container.style.display = "none";
        }
    });
});