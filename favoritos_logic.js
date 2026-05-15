import { db } from "./firebase-config.js";
import {
    collection, getDocs, getDoc, doc, query, where, deleteDoc
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

var todosFavoritos = [];

document.addEventListener('DOMContentLoaded', async function () {
    await carregarFavoritos();

    // Ordenação
    document.querySelectorAll('.filtro-pill').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filtro-pill').forEach(function (b) { b.classList.remove('ativo'); });
            this.classList.add('ativo');
            ordenarEExibir(this.dataset.ordem);
        });
    });
});

async function carregarFavoritos() {
    const grid = document.getElementById('fav-grid');

    try {
        const q = query(
            collection(db, 'favoritos'),
            where('usuario_id', '==', window.MATRICULA_USUARIO)
        );
        const snap = await getDocs(q);

        if (snap.empty) {
            exibirVazio(grid);
            atualizarContador(0);
            return;
        }

        // Busca dados da obra para cada favorito
        const promessas = snap.docs.map(async function (favDoc) {
            const fav = Object.assign({ id: favDoc.id }, favDoc.data());

            try {
                const obraSnap = await getDoc(doc(db, 'obras', fav.obra_id));
                if (obraSnap.exists()) {
                    fav.obra = Object.assign({ id: obraSnap.id }, obraSnap.data());
                } else {
                    fav.obra = { titulo: 'Obra não encontrada', autor: '', capa: '', genero: '' };
                }
            } catch (e) {
                fav.obra = { titulo: fav.obra_id, autor: '', capa: '', genero: '' };
            }

            return fav;
        });

        todosFavoritos = await Promise.all(promessas);
        ordenarEExibir('recente');
        atualizarContador(todosFavoritos.length);

    } catch (e) {
        console.error('Erro ao carregar favoritos:', e);
        grid.innerHTML = '<div class="fav-vazio"><p>Erro ao carregar favoritos.</p></div>';
    }
}

function ordenarEExibir(ordem) {
    var lista = todosFavoritos.slice();

    if (ordem === 'titulo') {
        lista.sort(function (a, b) {
            return (a.obra.titulo || '').localeCompare(b.obra.titulo || '', 'pt-BR');
        });
    } else if (ordem === 'autor') {
        lista.sort(function (a, b) {
            return (a.obra.autor || '').localeCompare(b.obra.autor || '', 'pt-BR');
        });
    } else {
        // recente: usa campo salvo_em (timestamp) se existir
        lista.sort(function (a, b) {
            var ta = a.salvo_em ? a.salvo_em.seconds : 0;
            var tb = b.salvo_em ? b.salvo_em.seconds : 0;
            return tb - ta;
        });
    }

    renderizarGrid(lista);
}

function renderizarGrid(lista) {
    const grid = document.getElementById('fav-grid');

    if (!lista.length) {
        exibirVazio(grid);
        return;
    }

    var cores = ['#477740', '#354A2B', '#6C9467', '#3d6139', '#5a8055'];

    grid.innerHTML = lista.map(function (fav, i) {
        var obra = fav.obra;
        var cor = cores[i % cores.length];

        var capaHtml = obra.capa
            ? '<img class="fav-capa" src="' + htmlEscape(obra.capa) + '" alt="' + htmlEscape(obra.titulo) + '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">' +
              '<div class="fav-capa-placeholder" style="background:' + cor + ';display:none">' + htmlEscape(obra.titulo) + '</div>'
            : '<div class="fav-capa-placeholder" style="background:' + cor + '">' + htmlEscape(obra.titulo) + '</div>';

        var generoHtml = obra.genero
            ? '<span class="fav-livro-genero">' + htmlEscape(obra.genero) + '</span>'
            : '';

        return (
            '<div class="fav-card" data-id="' + fav.id + '">' +
                '<div class="fav-capa-wrap">' +
                    capaHtml +
                    '<button class="fav-remover" title="Remover dos favoritos" onclick="removerFavorito(event, \'' + fav.id + '\')">' +
                        '<svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>' +
                    '</button>' +
                '</div>' +
                '<a class="fav-info" href="detalheslivro.php?id=' + htmlEscape(obra.id || fav.obra_id) + '">' +
                    '<span class="fav-livro-titulo">' + htmlEscape(obra.titulo) + '</span>' +
                    '<span class="fav-livro-autor">' + htmlEscape(obra.autor || 'Autor desconhecido') + '</span>' +
                    generoHtml +
                '</a>' +
            '</div>'
        );
    }).join('');
}

window.removerFavorito = async function (evento, favId) {
    evento.preventDefault();
    evento.stopPropagation();

    var card = document.querySelector('[data-id="' + favId + '"]');
    if (!card) return;

    // Animação de saída
    card.style.transition = 'opacity 0.25s, transform 0.25s';
    card.style.opacity = '0';
    card.style.transform = 'scale(0.9)';

    try {
        await deleteDoc(doc(db, 'favoritos', favId));

        setTimeout(function () {
            card.remove();
            todosFavoritos = todosFavoritos.filter(function (f) { return f.id !== favId; });
            atualizarContador(todosFavoritos.length);

            if (!todosFavoritos.length) {
                exibirVazio(document.getElementById('fav-grid'));
            }
        }, 280);

        exibirToast('Removido dos favoritos');

    } catch (e) {
        console.error('Erro ao remover favorito:', e);
        card.style.opacity = '1';
        card.style.transform = '';
        exibirToast('Erro ao remover. Tente novamente.');
    }
};

function exibirVazio(grid) {
    grid.innerHTML =
        '<div class="fav-vazio">' +
            '<svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>' +
            '<h3>Nenhum favorito ainda</h3>' +
            '<p>Explore o acervo e salve os livros que você quer ler.</p>' +
            '<a class="btn-explorar" href="acervo.php">Explorar acervo</a>' +
        '</div>';
}

function atualizarContador(total) {
    var el = document.getElementById('stat-total');
    if (el) el.textContent = total;
}

function exibirToast(mensagem) {
    var toast = document.getElementById('fav-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'fav-toast';
        toast.className = 'fav-toast';
        document.body.appendChild(toast);
    }
    toast.textContent = mensagem;
    toast.classList.add('visivel');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
        toast.classList.remove('visivel');
    }, 2800);
}

function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}