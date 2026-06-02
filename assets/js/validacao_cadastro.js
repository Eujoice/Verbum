document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const inputCpf = document.querySelector('input[name="cpf"]');
    const inputCep = document.querySelector('input[name="cep"]');
    const btnSubmit = document.getElementById('btnFinalizar');

    // Injeta o spinner HTML no botão
    btnSubmit.innerHTML = `<span class="spinner"></span> ` + btnSubmit.innerHTML;

    // Função global para mostrar o popup
    window.mostrarPopup = (mensagem, titulo = "Aviso", icone = "⚠️") => {
    const modal = document.getElementById('modalAlert');
    const modalIcon = document.getElementById('modalIcon');

    document.getElementById('modalMsg').innerText = mensagem;
    document.getElementById('modalTitle').innerText = titulo;

    if (icone.includes('.png') || icone.includes('.jpg') || icone.includes('.svg')) {
        modalIcon.innerHTML = `<img src="${icone}" class="icone-popup">`;
    } else {
        modalIcon.innerText = icone;
    }

    modal.style.display = 'flex';
};

    window.fecharModal = () => {
        document.getElementById('modalAlert').style.display = 'none';
    };

    // Máscara CPF
    inputCpf.addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, "");
        v = v.replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        e.target.value = v.substring(0, 14);
    });

    // Máscara CEP e Busca Automática
    inputCep.addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, "");
        v = v.replace(/^(\d{5})(\d)/, "$1-$2");
        e.target.value = v.substring(0, 9);
        if (v.replace("-", "").length === 8) {
            buscarEndereco(v.replace("-", ""));
        }
    });

    async function buscarEndereco(cep) {
        try {
            const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await res.json();
            if (!data.erro) {
                document.querySelector('input[name="rua"]').value = data.logradouro;
                document.querySelector('input[name="bairro"]').value = data.bairro;
                document.querySelector('input[name="cidade"]').value = data.localidade;
            }
        } catch (error) {}
    }

    // Submissão
    form.addEventListener('submit', (e) => {
        btnSubmit.classList.add('loading');
        btnSubmit.style.pointerEvents = 'none';
        btnSubmit.style.opacity = '0.8';
    });
});