document.addEventListener('DOMContentLoaded', function () {

    /* =========================================
       MENU LATERAL
    ========================================= */
    const hambtn    = document.getElementById('hambtn');
    const menu      = document.getElementById('menuLateral');
    const overlay   = document.getElementById('overlayMenu');

    function abrirMenu() {
        menu.classList.add('aberto');
        overlay.classList.add('ativo');
        hambtn.classList.add('aberto');
        document.body.style.overflow = 'hidden';
    }

    function fecharMenu() {
        menu.classList.remove('aberto');
        overlay.classList.remove('ativo');
        hambtn.classList.remove('aberto');
        document.body.style.overflow = '';
    }

    window.fecharMenu = fecharMenu;

    hambtn.addEventListener('click', function () {
        if (menu.classList.contains('aberto')) {
            fecharMenu();
        } else {
            abrirMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') fecharMenu();
    });

    /* =========================================
       CARROSSEL DE BANNERS
    ========================================= */
    var slideAtual = 0;
    var totalSlides = 5;
    var timer;
    var drag = {};

    function irParaSlide(n) {
        slideAtual = ((n % totalSlides) + totalSlides) % totalSlides;
        document.getElementById('slides').style.transform =
            'translateX(-' + (slideAtual * 100) + '%)';
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
        timer = setInterval(function () {
            irParaSlide(slideAtual + 1);
        }, 4200);
    }

    window.irParaSlide = irParaSlide;
    window.mudarSlide  = mudarSlide;

    resetTimer();

    var carousel = document.getElementById('carousel');

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