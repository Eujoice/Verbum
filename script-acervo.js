document.addEventListener('DOMContentLoaded', function () {

    /* =========================================
       MENU LATERAL (Correção Definitiva de Clique)
    ========================================= */
    const hambtn    = document.getElementById('hambtn');
    const menu      = document.getElementById('menuLateral');
    const overlay   = document.getElementById('overlayMenu');

    function abrirMenu() {
        if (menu && overlay && hambtn) {
            menu.classList.add('aberto');
            overlay.classList.add('ativo');
            hambtn.classList.add('aberto');
            document.body.style.overflow = 'hidden';
        }
    }

    function fecharMenu() {
        if (menu && overlay && hambtn) {
            menu.classList.remove('aberto');
            overlay.classList.remove('ativo');
            hambtn.classList.remove('aberto');
            document.body.style.overflow = '';
        }
    }

    // Torna as funções disponíveis globalmente para o overlay e outros links
    window.fecharMenu = fecharMenu;
    window.abrirMenu  = abrirMenu;
    
    window.toggleMenu = function() {
        if (menu && menu.classList.contains('aberto')) {
            fecharMenu();
        } else {
            abrirMenu();
        }
    };

    // Gerencia o clique de forma única e limpa pelo ID do botão
    if (hambtn) {
        hambtn.addEventListener('click', function (event) {
            event.stopPropagation(); // Evita que o clique se espalhe para outros elementos
            window.toggleMenu();
        });
    }

    // Fecha o menu se clicar no overlay de fundo escuro
    if (overlay) {
        overlay.addEventListener('click', function() {
            fecharMenu();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') fecharMenu();
    });

    /* =========================================
       CARROSSEL DE BANNERS (Protegido)
    ========================================= */
    var slideAtual = 0;
    var totalSlides = 5;
    var timer;
    var drag = {};

    function irParaSlide(n) {
        const slidesEl = document.getElementById('slides');
        if (!slidesEl) return;
        
        slideAtual = ((n % totalSlides) + totalSlides) % totalSlides;
        slidesEl.style.transform = 'translateX(-' + (slideAtual * 100) + '%)';
        
        document.querySelectorAll('.dot').forEach(function (dot, i) {
            dot.classList.toggle('active', i === slideAtual);
        });
    }

    function mudarSlide(direcao) {
        irParaSlide(slideAtual + direcao);
        resetTimer();
    }

    function resetTimer() {
        clearInterval(timer);
        const slidesEl = document.getElementById('slides');
        if (!slidesEl) return;

        timer = setInterval(function () {
            irParaSlide(slideAtual + 1);
        }, 4200);
    }

    window.irParaSlide = irParaSlide;
    window.mudarSlide  = mudarSlide;

    resetTimer();

    var carousel = document.getElementById('carousel');
    
    if (carousel) {
        carousel.addEventListener('mousedown', function (e) {
            drag.x    = e.clientX;
            drag.ativo = true;
        });

        carousel.addEventListener('mouseup', function (e) {
            if (!drag.ativo) return;
            drag.ativo = false;
            var dx = e.clientX - drag.x;
            if (Math.abs(dx) > 40) {
                mudarSlide(dx < 0 ? 1 : -1);
            }
        });

        carousel.addEventListener('mouseleave', function () {
            drag.ativo = false;
        });

        carousel.addEventListener('touchstart', function (e) {
            drag.x = e.touches[0].clientX;
        }, { passive: true });

        carousel.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - drag.x;
            if (Math.abs(dx) > 40) {
                mudarSlide(dx < 0 ? 1 : -1);
            }
        });
    }

    /* =========================================
       TABS DE NAVEGAÇÃO
    ========================================= */
    document.querySelectorAll('.tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tab').forEach(function (t) {
                t.classList.remove('ativo');
            });
            this.classList.add('ativo');
        });
    });

});

/* =========================================
   SISTEMA DE NOTIFICAÇÕES (ALUNO) — v2
========================================= */

// Cache das notificações para abrir detalhes sem nova requisição
let _notifCache = [];

function toggleDropdownNotificacoes(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('notificacaoDropdown');
    if (!dropdown) return;

    const aberto = dropdown.classList.contains('aberto');
    if (aberto) {
        fecharDropdownNotif();
    } else {
        dropdown.style.display = 'block';
        // Força reflow para a transição funcionar
        dropdown.offsetHeight;
        dropdown.classList.add('aberto');
        buscarNotificacoesAluno();
    }
}
window.toggleDropdownNotificacoes = toggleDropdownNotificacoes;

function fecharDropdownNotif() {
    const dropdown = document.getElementById('notificacaoDropdown');
    if (!dropdown) return;
    dropdown.classList.remove('aberto');
    dropdown.addEventListener('transitionend', () => {
        if (!dropdown.classList.contains('aberto')) dropdown.style.display = 'none';
    }, { once: true });
    // Fecha painel de detalhes também
    fecharPainelDetalhe();
}

document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('notificacaoDropdown');
    const container = document.getElementById('notificacaoContainer');
    if (dropdown && dropdown.classList.contains('aberto')) {
        if (container && !container.contains(e.target)) {
            fecharDropdownNotif();
        }
    }
});

function formatarDataNotif(dataStr) {
    if (!dataStr || dataStr.length < 16) return dataStr || '—';
    const [data, hora] = dataStr.split(' ');
    const [ano, mes, dia] = data.split('-');
    return `${dia}/${mes}/${ano} às ${hora.substring(0, 5)}`;
}

function tempoRelativo(dataStr) {
    if (!dataStr) return '';
    try {
        const agora = new Date();
        const passado = new Date(dataStr.replace(' ', 'T'));
        const diff = Math.floor((agora - passado) / 1000); // segundos
        if (diff < 60) return 'agora mesmo';
        if (diff < 3600) return `há ${Math.floor(diff / 60)} min`;
        if (diff < 86400) return `há ${Math.floor(diff / 3600)}h`;
        if (diff < 172800) return 'ontem';
        return formatarDataNotif(dataStr).split(' às')[0];
    } catch { return ''; }
}

function iconePorTipo(tipo, mensagem) {
    // Detecta o tipo pela mensagem se o campo tipo não existir
    const txt = (tipo + mensagem).toLowerCase();
    if (txt.includes('empréstimo') || txt.includes('retirada')) {
        return `<svg class="notif-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>`;
    }
    if (txt.includes('atraso') || txt.includes('pendente') || txt.includes('devolução')) {
        return `<svg class="notif-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
    }
    if (txt.includes('reserva')) {
        return `<svg class="notif-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>`;
    }
    // padrão
    return `<svg class="notif-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`;
}

function classePorTipo(tipo, mensagem) {
    const txt = (tipo + mensagem).toLowerCase();
    if (txt.includes('atraso') || txt.includes('pendente')) return 'notif-tipo--alerta';
    if (txt.includes('empréstimo') || txt.includes('retirada')) return 'notif-tipo--info';
    if (txt.includes('reserva')) return 'notif-tipo--reserva';
    return 'notif-tipo--geral';
}

async function buscarNotificacoesAluno() {
    try {
        const badge  = document.getElementById('notificacaoBadge');
        const lista  = document.getElementById('notificacaoLista');
        if (!badge || !lista) return;

        const response = await fetch('listar_notificacoes.php');
        const dados = await response.json();
        if (!dados.sucesso) return;

        _notifCache = dados.notificacoes || [];

        // Badge
        if (dados.total_nao_lidas > 0) {
            badge.textContent = dados.total_nao_lidas > 9 ? '9+' : dados.total_nao_lidas;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }

        renderListaNotificacoes();

    } catch (e) {
        console.error("Erro ao buscar notificações:", e);
    }
}

function renderListaNotificacoes() {
    const lista = document.getElementById('notificacaoLista');
    if (!lista) return;

    if (!_notifCache.length) {
        lista.innerHTML = `
        <div class="notif-vazia">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <p>Nenhuma notificação</p>
        </div>`;
        return;
    }

    const naoLidas = _notifCache.filter(n => !n.lida);
    const lidas    = _notifCache.filter(n => n.lida);

    let html = '';

    if (naoLidas.length) {
        html += `<div class="notif-grupo-label">Novas</div>`;
        html += naoLidas.map(n => cardNotif(n)).join('');
    }
    if (lidas.length) {
        html += `<div class="notif-grupo-label">Anteriores</div>`;
        html += lidas.map(n => cardNotif(n)).join('');
    }

    lista.innerHTML = html;
}

function cardNotif(notif) {
    const tipo   = notif.tipo || '';
    const lida   = notif.lida;
    const tempo  = tempoRelativo(notif.data);
    const classe = classePorTipo(tipo, notif.mensagem);
    const icone  = iconePorTipo(tipo, notif.mensagem);

    // Trunca a mensagem para o card
    const msgCurta = notif.mensagem.length > 72
        ? notif.mensagem.substring(0, 72) + '…'
        : notif.mensagem;

    return `
    <div class="notif-item ${lida ? 'lida' : 'nao-lida'}" 
         onclick="abrirDetalheNotif('${notif.id}')"
         role="button" tabindex="0">
        <div class="notif-item-icon ${classe}">${icone}</div>
        <div class="notif-item-corpo">
            <p class="notif-item-msg">${msgCurta}</p>
            <span class="notif-item-tempo">${tempo}</span>
        </div>
        ${!lida ? `<div class="notif-ponto-novo" title="Não lida"></div>` : ''}
    </div>`;
}

function abrirDetalheNotif(id) {
    const notif = _notifCache.find(n => n.id === id);
    if (!notif) return;

    // Marca como lida ao abrir
    if (!notif.lida) marcarComoLida({ stopPropagation: () => {} }, id);

    const painel = document.getElementById('notifPainelDetalhe');
    if (!painel) return;

    const tipo    = notif.tipo || '';
    const classe  = classePorTipo(tipo, notif.mensagem);
    const icone   = iconePorTipo(tipo, notif.mensagem);

    const tipoLabel = {
        'devolucao':  'Cobrança de devolução',
        'atraso':     'Aviso de atraso',
        'renovacao':  'Oferta de renovação',
    }[tipo] || 'Notificação da Biblioteca';

    painel.innerHTML = `
    <div class="notif-det-header">
        <button class="notif-det-voltar" onclick="fecharPainelDetalhe()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar
        </button>
    </div>
    <div class="notif-det-corpo">
        <div class="notif-det-icon ${classe}">${icone}</div>
        <div class="notif-det-tipo">${tipoLabel}</div>
        <div class="notif-det-mensagem">${notif.mensagem}</div>
        <div class="notif-det-meta">
            <div class="notif-det-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <span>${formatarDataNotif(notif.data)}</span>
            </div>
            <div class="notif-det-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Enviado pela Biblioteca Verbum</span>
            </div>
            <div class="notif-det-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Para você</span>
            </div>
        </div>
    </div>`;

    painel.classList.add('aberto');
}
window.abrirDetalheNotif = abrirDetalheNotif;

function fecharPainelDetalhe() {
    const painel = document.getElementById('notifPainelDetalhe');
    if (painel) painel.classList.remove('aberto');
}
window.fecharPainelDetalhe = fecharPainelDetalhe;

/* =========================================
   FUNÇÃO: MARCAR COMO LIDA (POST)
========================================= */
async function marcarComoLida(event, idNotificacao) {
    event.stopPropagation();

    // Atualiza cache imediatamente (otimistic update)
    const notif = _notifCache.find(n => n.id === idNotificacao);
    if (notif) notif.lida = true;
    renderListaNotificacoes();

    // Atualiza badge
    const naoLidas = _notifCache.filter(n => !n.lida).length;
    const badge = document.getElementById('notificacaoBadge');
    if (badge) {
        if (naoLidas > 0) { badge.textContent = naoLidas > 9 ? '9+' : naoLidas; badge.style.display = 'flex'; }
        else badge.style.display = 'none';
    }

    const formData = new FormData();
    formData.append('id', idNotificacao);

    try {
        await fetch('listar_notificacoes.php', { method: 'POST', body: formData });
    } catch (e) {
        console.error("Erro ao marcar como lida:", e);
    }
}
window.marcarComoLida = marcarComoLida;

// Executa assim que a página carregar
document.addEventListener('DOMContentLoaded', function() {
    buscarNotificacoesAluno();
    setInterval(buscarNotificacoesAluno, 30000);
});