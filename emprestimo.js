document.addEventListener('DOMContentLoaded', () => {
    let idEmprestimoAtual = ''; // Variável para rastrear o ID do empréstimo no Firestore

    const setupSearch = (inputId, listId, url, isUser) => {
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);

        input.addEventListener('input', async () => {
            const query = input.value.trim();
            if (query.length < 2) { 
                list.innerHTML = ''; 
                list.style.display = 'none';
                return; 
            }

            try {
                const res = await fetch(`${url}?q=${encodeURIComponent(query)}`);
                const data = await res.json();

                list.innerHTML = '';
                if (data.length > 0) {
                    list.style.display = 'block';
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'sugestao-item';
                        
                        if (isUser) {
                            div.textContent = `${item.nome} (${item.matricula})`;
                            div.onclick = (e) => {
                                e.stopPropagation();
                                input.value = item.nome;
                                document.getElementById('usuario_id').value = item.matricula;
                                list.style.display = 'none';
                            };
                        } else {
                            div.innerHTML = `<strong>${item.id}</strong> - ${item.titulo} (${item.status})`;
                            div.onclick = (e) => {
                                e.stopPropagation();
                                input.value = item.id;
                                
                                // Se o livro estiver emprestado, carregamos os dados do empréstimo
                                if(item.status === 'Emprestado') {
                                    idEmprestimoAtual = item.id_emprestimo_atual || ''; 
                                    
                                    abrirModal("Atenção", `Este livro está com: <strong>${item.emprestado_por}</strong>`, false);
                                    document.getElementById('buscaUsuario').value = item.emprestado_por || '';
                                    document.getElementById('usuario_id').value = item.matricula_usuario || '';
                                    
                                    if(item.data_prevista) {
                                        calcularMulta(item.data_prevista);
                                    }
                                } else {
                                    idEmprestimoAtual = ''; 
                                    document.getElementById('diasAtraso').value = 0;
                                    document.getElementById('valorMulta').value = "R$ 0,00";
                                }
                                list.style.display = 'none';
                            };
                        }
                        list.appendChild(div);
                    });
                } else {
                    list.style.display = 'none';
                }
            } catch (e) { console.error(e); }
        });
    };

    setupSearch('buscaUsuario', 'listaSugestoes', 'buscar_usuarios.php', true);
    setupSearch('buscaLivro', 'listaSugestoesLivro', 'buscar_livros.php', false);

    /* -- Lógica de Empréstimo -- */
    document.getElementById('btnEmprestar').onclick = async function() {
        const user = document.getElementById('usuario_id').value;
        const livro = document.getElementById('buscaLivro').value;
        const dataIni = document.getElementById('data_ini').value;
        const dataFim = document.getElementById('data_fim').value;

        if(!user || !livro || !dataIni || !dataFim) {
            abrirModal("Erro", "Por favor, preencha todos os campos.", false);
            return;
        }

        const fd = new FormData();
        fd.append('usuario_id', user);
        fd.append('livro_id', livro);
        fd.append('data_ini', dataIni);
        fd.append('data_fim', dataFim);

        const res = await fetch('processar_emprestimo.php', { method: 'POST', body: fd });
        const txt = await res.text();
        if(txt.trim() === 'Sucesso') {
            mostrarToast('Empréstimo realizado com sucesso!');
            setTimeout(() => location.reload(), 2000);
        } else {
            abrirModal("Erro no Processamento", txt, false);
        }
    };

    /* -- Lógica de Devolução --*/
    document.getElementById('btnDevolver').onclick = function() {
        const idObra = document.getElementById('buscaLivro').value;
        
        if(!idObra) {
            return abrirModal("Aviso", "Selecione um livro para devolver.", false);
        }

        // Verificação -> se o campo idEmprestimoAtual estiver vazio, o sistema não saberá qual registro fechar
        if(!idEmprestimoAtual) {
            return abrirModal("Aviso", "Este exemplar não possui um empréstimo ativo registrado.", false);
        }

        abrirModal("Confirmar Devolução", `Deseja confirmar a devolução do exemplar <strong>#${idObra}</strong>?`, true, async () => {
            const fd = new FormData();
            fd.append('acao', 'devolver');
            fd.append('livro_id', idObra);
            fd.append('emprestimo_id', idEmprestimoAtual);

            try {
                const res = await fetch('processar_devolucao.php', { method: 'POST', body: fd });
                const result = await res.text();
                
                fecharModal();
                mostrarToast(result);
                
                // Limpa os campos após a devolução
                setTimeout(() => location.reload(), 2000);
            } catch (error) {
                console.error("Erro na devolução:", error);
                abrirModal("Erro", "Falha ao conectar com o servidor.", false);
            }
        });
    };
});

/* -- Modal e Toast -- */
function abrirModal(titulo, mensagem, mostrarConfirmar = false, acaoConfirmar = null) {
    const modal = document.getElementById('modalConfirm');
    document.getElementById('modalTitulo').innerText = titulo;
    document.getElementById('modalMsg').innerHTML = mensagem;
    const btnConfirmar = document.getElementById('btnConfirmarAcao');
    
    if (mostrarConfirmar) {
        btnConfirmar.style.display = 'block';
        btnConfirmar.onclick = acaoConfirmar;
    } else {
        btnConfirmar.style.display = 'none';
    }
    modal.style.display = 'flex';
}

function fecharModal() { document.getElementById('modalConfirm').style.display = 'none'; }

function mostrarToast(texto) {
    const toast = document.getElementById('toast');
    toast.innerText = texto;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 3000);
}

function calcularMulta(dataPrevista) {
    const hoje = new Date();
    const prevista = new Date(dataPrevista);
    hoje.setHours(0,0,0,0); prevista.setHours(0,0,0,0);
    if (hoje > prevista) {
        const diffDias = Math.ceil(Math.abs(hoje - prevista) / (1000 * 60 * 60 * 24));
        document.getElementById('diasAtraso').value = diffDias;
        document.getElementById('valorMulta').value = `R$ ${(diffDias * 2).toFixed(2)}`;
    } else {
        document.getElementById('diasAtraso').value = 0;
        document.getElementById('valorMulta').value = "R$ 0,00";
    }
}