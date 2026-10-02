document.addEventListener("DOMContentLoaded", function () {
    const menu = document.querySelector(".menu-lateral");
    const botao = document.getElementById("toggleMenu");

    if (botao && menu) {
        botao.addEventListener("click", () => {
            menu.classList.toggle("fechado");
        });
    }

    const botaoLogout = document.getElementById("btnLogout");
    const modalSaida = document.getElementById("modalSaida");
    const cancelarSaida = document.getElementById("cancelarSaida");

    if (botaoLogout && modalSaida) {
        botaoLogout.addEventListener("click", (e) => {
            e.preventDefault();
            modalSaida.style.display = "flex";
        });
    }

    if (cancelarSaida && modalSaida) {
        cancelarSaida.addEventListener("click", () => {
            modalSaida.style.display = "none";
        });
    }

    window.addEventListener("click", (e) => {
        if (e.target === modalSaida) {
            modalSaida.style.display = "none";
        }
    });

    /* =========================================================
       MODAL DE CONFIGURAÇÕES
    ========================================================= */
    const btnConfiguracoes = document.getElementById("btnConfiguracoes");
    const modalConfiguracoes = document.getElementById("modalConfiguracoes");
    const fecharConfiguracoes = document.getElementById("fecharConfiguracoes");

    const configItens = document.querySelectorAll(".config-item");
    const configPaginas = document.querySelectorAll(".config-pagina");

    function abrirConfiguracoes() {
        if (!modalConfiguracoes) return;
        modalConfiguracoes.classList.add("aberto");
        modalConfiguracoes.setAttribute("aria-hidden", "false");
        document.body.classList.add("config-modal-aberto");
    }

    function fecharModalConfiguracoes() {
        if (!modalConfiguracoes) return;
        modalConfiguracoes.classList.remove("aberto");
        modalConfiguracoes.setAttribute("aria-hidden", "true");
        document.body.classList.remove("config-modal-aberto");
    }

    if (btnConfiguracoes) {
        btnConfiguracoes.addEventListener("click", function (event) {
            event.preventDefault();
            abrirConfiguracoes();
        });
    }

    if (fecharConfiguracoes) {
        fecharConfiguracoes.addEventListener("click", fecharModalConfiguracoes);
    }

    /* TROCA DE ABAS */
    configItens.forEach(function (item) {
        item.addEventListener("click", function () {
            const configSelecionada = item.getAttribute("data-config");

            configItens.forEach(function (botao) {
                botao.classList.remove("ativo");
            });

            configPaginas.forEach(function (pagina) {
                pagina.classList.remove("ativa");
            });

            item.classList.add("ativo");

            const pagina = document.getElementById("config-" + configSelecionada);
            if (pagina) {
                pagina.classList.add("ativa");
                const painel = document.querySelector(".config-painel");
                if (painel) painel.scrollTop = 0;
            }
        });
    });

    if (modalConfiguracoes) {
        modalConfiguracoes.addEventListener("click", function (event) {
            if (event.target === modalConfiguracoes) {
                fecharModalConfiguracoes();
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (
            event.key === "Escape" &&
            modalConfiguracoes &&
            modalConfiguracoes.classList.contains("aberto")
        ) {
            fecharModalConfiguracoes();
        }
    });

    /* =========================================================
       VALIDAÇÃO E VISIBILIDADE DE SENHA
    ========================================================= */
    window.mostrarSenhaModal = function (idCampo, botao) {
        const campo = document.getElementById(idCampo);
        if (!campo) return;

        const icone = botao.querySelector("i");

        if (campo.type === "password") {
            campo.type = "text";
            if (icone) {
                icone.classList.remove("bi-eye");
                icone.classList.add("bi-eye-slash");
            }
            botao.setAttribute("aria-label", "Ocultar senha");
        } else {
            campo.type = "password";
            if (icone) {
                icone.classList.remove("bi-eye-slash");
                icone.classList.add("bi-eye");
            }
            botao.setAttribute("aria-label", "Mostrar senha");
        }
    };

    window.validarSenhaModal = function () {
        const novaSenha = document.getElementById("nova_senha");
        const confirmarSenha = document.getElementById("confirmar_senha");
        const btnSubmit = document.getElementById("btnSubmit");
        const mensagemConfirmacao = document.getElementById("mensagemConfirmacao");

        const reqTamanho = document.getElementById("reqTamanho");
        const reqMaiuscula = document.getElementById("reqMaiuscula");
        const reqMinuscula = document.getElementById("reqMinuscula");
        const reqNumero = document.getElementById("reqNumero");
        const reqEspecial = document.getElementById("reqEspecial");

        if (!novaSenha) return;

        const senha = novaSenha.value;

        const tamanhoValido = senha.length >= 8;
        const maiusculaValida = /[A-Z]/.test(senha);
        const minusculaValida = /[a-z]/.test(senha);
        const numeroValido = /[0-9]/.test(senha);
        const especialValido = /[^A-Za-z0-9]/.test(senha);

        function atualizarRequisito(elemento, valido) {
            if (!elemento) return;
            if (valido) {
                elemento.classList.add("valido");
            } else {
                elemento.classList.remove("valido");
            }
        }

        atualizarRequisito(reqTamanho, tamanhoValido);
        atualizarRequisito(reqMaiuscula, maiusculaValida);
        atualizarRequisito(reqMinuscula, minusculaValida);
        atualizarRequisito(reqNumero, numeroValido);
        atualizarRequisito(reqEspecial, especialValido);

        const senhaValida =
            tamanhoValido &&
            maiusculaValida &&
            minusculaValida &&
            numeroValido &&
            especialValido;

        let confirmacaoValida = false;
        if (confirmarSenha && mensagemConfirmacao) {
            const confirmacao = confirmarSenha.value;

            if (confirmacao === "") {
                mensagemConfirmacao.textContent = "";
                mensagemConfirmacao.className = "mensagem-senha";
                confirmacaoValida = false;
            } else if (senha !== confirmacao) {
                mensagemConfirmacao.textContent = "As senhas não coincidem.";
                mensagemConfirmacao.className = "mensagem-senha erro";
                confirmacaoValida = false;
            } else {
                mensagemConfirmacao.textContent = "As senhas coincidem.";
                mensagemConfirmacao.className = "mensagem-senha sucesso";
                confirmacaoValida = true;
            }
        }

        if (btnSubmit) {
            btnSubmit.disabled = !(senhaValida && confirmacaoValida);
        }
    };

    const novaSenha = document.getElementById("nova_senha");
    const confirmarSenha = document.getElementById("confirmar_senha");

    if (novaSenha) {
        novaSenha.addEventListener("input", window.validarSenhaModal);
        novaSenha.addEventListener("keyup", window.validarSenhaModal);
    }
    if (confirmarSenha) {
        confirmarSenha.addEventListener("input", window.validarSenhaModal);
        confirmarSenha.addEventListener("keyup", window.validarSenhaModal);
    }
});