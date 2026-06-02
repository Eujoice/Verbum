document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formTrocaSenha');
    const inputSenha = document.getElementById('nova_senha');
    const inputConfirmar = document.getElementById('confirmar_senha');

    // Se o form for null, o script para aqui para não dar erro no console
    if (!form) {
        console.error("Formulário não encontrado! Verifique se o ID 'formTrocaSenha' existe no HTML.");
        return;
    }

    const reqs = {
        comprimento: document.getElementById('req-comprimento'),
        maiuscula: document.getElementById('req-maiuscula'),
        numero: document.getElementById('req-numero'),
        especial: document.getElementById('req-especial')
    };

    inputSenha.addEventListener('input', () => {
        const senha = inputSenha.value;
        const regras = {
            comprimento: senha.length >= 8,
            maiuscula: /[A-Z]/.test(senha),
            numero: /[0-9]/.test(senha),
            especial: /[!@#$%^&*(),.?":{}|<>]/.test(senha)
        };

        Object.keys(regras).forEach(regra => {
            if (regras[regra]) {
                reqs[regra].style.color = "#2ecc71"; 
                reqs[regra].innerText = reqs[regra].innerText.replace('✖', '✔');
            } else {
                reqs[regra].style.color = "#ff4d4d"; 
                reqs[regra].innerText = reqs[regra].innerText.replace('✔', '✖');
            }
        });
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault(); 

        const senha = inputSenha.value;
        const confirmar = inputConfirmar.value;

        const senhaValida = senha.length >= 8 && 
                            /[A-Z]/.test(senha) && 
                            /[0-9]/.test(senha) && 
                            /[!@#$%^&*(),.?":{}|<>]/.test(senha);

        if (!senhaValida) {
            Swal.fire({
                icon: 'error',
                title: 'Senha Fraca',
                text: 'Atenda a todos os requisitos em vermelho!',
                confirmButtonColor: '#3085d6',
                heightAuto: false 
            });
            return;
        }

        if (senha !== confirmar) {
            Swal.fire({
                icon: 'warning',
                title: 'Senhas Diferentes',
                text: 'A confirmação não confere.',
                confirmButtonColor: '#3085d6',
                heightAuto: false 
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Tudo pronto!',
            text: 'Atualizando sua conta...',
            showConfirmButton: false,
            heightAuto: false, 
            
            timer: 1500
        }).then(() => {
            form.submit(); // envia para o processar_troca.php
        });
    });
});