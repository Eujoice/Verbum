<?php
// dp_modal.php — inclua com: include 'dp_modal.php';
// O botao do menu deve chamar: onclick="abrirDpOverlay()"
?>

<!-- ─── Link do CSS de Dados Pessoais ─── -->
<link rel="stylesheet" href="styledp.css">

<!-- ─── Overlay de Dados Pessoais ─── -->
<div class="dp-overlay" id="dpOverlay">
    <div class="container-dp">

        <!-- Botão fechar -->
        <button class="dp-btn-fechar" onclick="fecharDpOverlay()" title="Voltar">
            <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <div class="header-perfil">
            <div class="header-banner"></div>

            <!-- Avatar SVG igual ao do menu lateral -->
            <div class="foto3">
                <div class="dp-avatar">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                    </svg>
                </div>
            </div>

            <div class="dados-entrada-dp">
                <span id="dpNomeUsuario"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
                <p id="dpEmailUsuario"><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></p>
            </div>
        </div>

        <div class="divider"></div>

        <form method="POST" action="dpessoais.php" class="formulario">
            <input type="hidden" name="pagina_origem" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="salvar_dp" value="1">

            <div class="campo">
                <label class="label-dp">Nome</label>
                <input type="text" name="nome" class="formu"
                       value="<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>"
                       placeholder="Seu nome completo">
            </div>

            <div class="campo">
                <label class="label-dp">E-mail</label>
                <input type="email" name="email" class="formu"
                       value="<?php echo htmlspecialchars($_SESSION['usuario_email']); ?>"
                       placeholder="seu@email.com">
            </div>

            <div class="campo">
                <label class="label-dp">Telefone</label>
                <input type="tel" name="telefone" class="formu"
                       value="<?php echo htmlspecialchars($_SESSION['usuario_telefone'] ?? ''); ?>"
                       placeholder="(27) 9 0000-0000">
            </div>

            <div class="campo">
                <label class="label-dp">Endereço</label>
                <input type="text" name="endereco" class="formu"
                       value="<?php echo htmlspecialchars($_SESSION['usuario_endereco'] ?? ''); ?>"
                       placeholder="Rua, número, bairro">
            </div>

            <div class="btn-wrapper">
                <button type="submit" class="btn">Salvar alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Modal de sucesso ─── -->
<div id="modalSucesso" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-avatar">
            <svg viewBox="0 0 24 24">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>
        <h3>Alterações salvas!</h3>
        <p>Seus dados foram atualizados com sucesso.</p>
        <button type="button" onclick="fecharModalSucesso()" class="btn-confirmar">Ok</button>
    </div>
</div>

<script>
    function abrirDpOverlay() {
        document.getElementById('dpOverlay').classList.add('ativo');
        document.body.style.overflow = 'hidden';
    }

    function fecharDpOverlay() {
        document.getElementById('dpOverlay').classList.remove('ativo');
        document.body.style.overflow = '';
    }

    function fecharModalSucesso() {
        document.getElementById('modalSucesso').classList.remove('ativo');
        fecharDpOverlay();
    }

    document.getElementById('dpOverlay').addEventListener('click', function(e) {
        if (e.target === this) fecharDpOverlay();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') fecharDpOverlay();
    });

    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['sucesso_dp'])): ?>
        abrirDpOverlay();
        document.getElementById('modalSucesso').classList.add('ativo');
        <?php unset($_SESSION['sucesso_dp']); ?>
        <?php endif; ?>
    });
</script>