/**
 * multa.js  — sistema de multas Verbum
 * Incluído em todas as páginas autenticadas via <script src>.
 *
 * O que faz:
 *  1. Consulta /api/verificar_multa.php ao carregar
 *  2. Se houver multa, exibe banner fixo no topo da página
 *  3. Expõe window._verbumMulta = { temMulta, total, detalhes }
 *     para que outras páginas possam ler sem nova requisição
 */

(function () {
  'use strict';

  // ── Estilos do banner ────────────────────────────────────────────
  const CSS = `
    #verbum-banner-multa {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 99999;
      background: #b91c1c;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 11px 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,.35);
      animation: verbumSlideDown .3s ease;
    }
    @keyframes verbumSlideDown {
      from { transform: translateY(-100%); opacity: 0; }
      to   { transform: translateY(0);    opacity: 1; }
    }
    #verbum-banner-multa .vbm-icon {
      font-size: 20px;
      flex-shrink: 0;
    }
    #verbum-banner-multa .vbm-texto {
      flex: 1;
      line-height: 1.4;
    }
    #verbum-banner-multa .vbm-texto strong {
      display: block;
      font-size: 15px;
    }
    #verbum-banner-multa .vbm-texto span {
      opacity: .92;
    }
    #verbum-banner-multa .vbm-btn {
      background: #fff;
      color: #b91c1c;
      border: none;
      border-radius: 6px;
      padding: 7px 14px;
      font-weight: 700;
      font-size: 13px;
      cursor: pointer;
      white-space: nowrap;
      text-decoration: none;
      display: inline-block;
      transition: opacity .15s;
    }
    #verbum-banner-multa .vbm-btn:hover { opacity: .85; }
    #verbum-banner-multa .vbm-fechar {
      background: none;
      border: none;
      color: #fff;
      font-size: 20px;
      cursor: pointer;
      flex-shrink: 0;
      line-height: 1;
      opacity: .8;
    }
    #verbum-banner-multa .vbm-fechar:hover { opacity: 1; }
    /* Empurra o conteúdo da página para não ficar sob o banner */
    body.tem-multa-banner { padding-top: 56px !important; }
  `;

  function injetarEstilos() {
    const s = document.createElement('style');
    s.textContent = CSS;
    document.head.appendChild(s);
  }

  function criarBanner(total, detalhes) {
    const nLivros = detalhes.length;
    const valor   = total.toFixed(2).replace('.', ',');
    const url     = 'https://pagtesouro.tesouro.gov.br/portal-gru/#/pagamento-gru/formulario?servico=011327';

    const div = document.createElement('div');
    div.id = 'verbum-banner-multa';
    div.setAttribute('role', 'alert');
    div.innerHTML = `
      <span class="vbm-icon"> <img src="../assets/imgs/multa.png" alt="Atenção!" style="width:40px;"></span>
      <div class="vbm-texto">
        <strong>Multa pendente — R$ ${valor}</strong>
        <span>${nLivros} livro${nLivros > 1 ? 's' : ''} em atraso. Novas reservas estão bloqueadas até o pagamento.</span>
      </div>
      <a class="vbm-btn" href="${url}" target="_blank" rel="noopener">Pagar agora</a>
      <button class="vbm-fechar" title="Fechar aviso" aria-label="Fechar">✕</button>
    `;

    div.querySelector('.vbm-fechar').addEventListener('click', function () {
      div.remove();
      document.body.classList.remove('tem-multa-banner');
    });

    return div;
  }

  async function verificarEExibir() {
    try {
      const resp  = await fetch('/verbum/api/verificar_multa.php');
      const dados = await resp.json();

      // Expõe globalmente para outras partes da página lerem sem nova requisição
      window._verbumMulta = dados;

      if (!dados.temMulta) return;

      injetarEstilos();
      const banner = criarBanner(dados.total, dados.detalhes);
      document.body.prepend(banner);
      document.body.classList.add('tem-multa-banner');

      // Dispara evento customizado para que detalheslivro.js possa reagir
      document.dispatchEvent(new CustomEvent('verbum:multa', { detail: dados }));

    } catch (e) {
      // Silencioso — falha na verificação não impede o uso do site
    }
  }

  // Executa após o DOM estar pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', verificarEExibir);
  } else {
    verificarEExibir();
  }
})();
