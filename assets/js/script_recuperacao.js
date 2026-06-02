document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const formRecuperar = document.getElementById('formRecuperar');

    // 1. Captura mensagens vindas do PHP (URL)
    if (params.has('sucesso')) {
        if (params.get('sucesso') === 'email_enviado') {
            Swal.fire({
                icon: 'success',
                title: 'E-mail Enviado!',
                text: 'Verifique sua caixa de entrada e a pasta de spam.',
                confirmButtonColor: '#2ecc71',
                heightAuto: false
            });
        }
    }

    if (params.has('erro')) {
        let msg = "Ocorreu um erro inesperado.";
        if (params.get('erro') === 'dados_invalidos') msg = "O e-mail informado não coincide com a matrícula.";
        if (params.get('erro') === 'usuario_nao_encontrado') msg = "Matrícula não cadastrada no sistema.";
        if (params.get('erro') === 'envio_falhou') msg = "Falha ao enviar e-mail. Tente novamente mais tarde.";

        Swal.fire({
            icon: 'error',
            title: 'Ops...',
            text: msg,
            confirmButtonColor: '#d33',
            heightAuto: false
        });
    }

    // 2. Feedback visual ao clicar no botão "Enviar Link"
    if (formRecuperar) {
        formRecuperar.addEventListener('submit', () => {
            let timerInterval;
            Swal.fire({
                title: 'Processando...',
                html: 'Estamos validando seus dados.',
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                },
                heightAuto: false,
                allowOutsideClick: false
            });
        });
    }
});