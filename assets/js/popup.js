
document.addEventListener("DOMContentLoaded", function () {

    console.log("Popup JS carregado.");





    const esqueciSenha = document.getElementById("esqueciSenha");
    const overlay = document.getElementById("overlay");
    const popupRecuperar = document.getElementById("popupRecuperar");
    const formRecuperacao = document.getElementById("formRecuperacao");
    const loader = document.getElementById("loader");
    const popupSucesso = document.getElementById("popupSucesso");
    const popupErro = document.getElementById("popupErro");
    const emailRecuperacao = document.getElementById("emailRecuperacao");
    const btnEnviarRecuperacao = document.getElementById("btnEnviarRecuperacao");
    const btnTentarNovamente = document.getElementById("btnTentarNovamente");
    const btnReenviar = document.getElementById("btnReenviar");

    console.log("esqueciSenha:", esqueciSenha);
    console.log("overlay:", overlay);
    console.log("popupRecuperar:", popupRecuperar);
    console.log("formRecuperacao:", formRecuperacao);



    if (esqueciSenha) {
        esqueciSenha.addEventListener("click", function (e) {
            e.preventDefault();
            console.log("🚀 O clique no link 'Esqueceu a senha' FUNCIONOU!");
            if (overlay) overlay.classList.add("ativo");
            if (popupRecuperar) popupRecuperar.classList.add("ativo");

            abrirRecuperacao();
        });
    }


    function abrirRecuperacao() {
        if (!overlay) {
            console.error("Overlay não encontrado.");
            return;
        }

        overlay.style.display = "flex";

        esconderTelas();

        if (popupRecuperar) {
            popupRecuperar.style.display = "flex";
        }

        if (emailRecuperacao) {
            setTimeout(function () {
                emailRecuperacao.focus();
            }, 200);
        }
    }

    function esconderTelas() {
        if (popupRecuperar) {
            popupRecuperar.style.display = "none";
        }

        if (loader) {
            loader.style.display = "none";
        }

        if (popupSucesso) {
            popupSucesso.style.display = "none";
        }

        if (popupErro) {
            popupErro.style.display = "none";
        }
    }


    window.fecharPopup = function () {
        esconderTelas();

        if (overlay) {
            overlay.style.display = "none";
            overlay.classList.remove("ativo");
        }
    };


    if (formRecuperacao) {

        formRecuperacao.addEventListener("submit", async function (e) {
            e.preventDefault();

            const campoID = document.getElementById("ID");

            if (!campoID) {
                mostrarErro("Campo de identificação não encontrado.");
                return;
            }

            const ID = campoID.value.trim();
            const email = emailRecuperacao ? emailRecuperacao.value.trim() : "";


            if (ID === "") {
                mostrarErro("Digite seu Código de Identificação no login.");
                campoID.focus();
                return;
            }

            if (ID.length !== 6) {
                mostrarErro("O código de identificação deve ter exatamente 6 caracteres.");
                campoID.focus();
                return;
            }

            if (email === "") {
                mostrarErro("Digite seu e-mail cadastrado.");
                if (emailRecuperacao) emailRecuperacao.focus();
                return;
            }

            if (!email.includes("@") || !email.includes(".")) {
                mostrarErro("Digite um e-mail válido.");
                if (emailRecuperacao) emailRecuperacao.focus();
                return;
            }


            esconderTelas();

            if (loader) {
                loader.style.display = "flex";
            }

            if (btnEnviarRecuperacao) {
                btnEnviarRecuperacao.disabled = true;
                btnEnviarRecuperacao.textContent = "Enviando...";
            }

            const dados = new FormData();
            dados.append("ID", ID);
            dados.append("email", email);


            try {
                const resposta = await fetch(
                    "../backend/Controllers/recuperarsenha.php",
                    {
                        method: "POST",
                        body: dados
                    }
                );

                const texto = await resposta.text();
                console.log("Resposta bruta do PHP:", texto);


                const textoLimpo = texto.trim();

                let resultado;
                try {
                    resultado = JSON.parse(textoLimpo);
                } catch (erroJSON) {
                    console.error("Erro ao converter JSON:", erroJSON);
                    mostrarErro("Resposta do servidor em formato inválido. Verifique o F12.");
                    return;
                }

                console.log("Resultado do PHP:", resultado);


                const statusNormalizado = String(resultado.status || "").trim().toLowerCase();


                if (statusNormalizado === "ok" || statusNormalizado === "sucesso") {

                    esconderTelas();

                    if (popupSucesso) {
                        const msgSucesso = popupSucesso.querySelector(".info");
                        if (msgSucesso && resultado.mensagem) {
                            msgSucesso.textContent = resultado.mensagem;
                        }

                        popupSucesso.classList.add("ativo");
                        popupSucesso.style.display = "flex";
                    } else {

                        alert(resultado.mensagem || "E-mail enviado com sucesso!");
                    }

                } else {

                    mostrarErro(resultado.mensagem || "Não foi possível enviar o e-mail.");
                }

            } catch (erro) {
                console.error("Erro capturado no catch:", erro);

                mostrarErro("Erro no script: " + erro.message);
            } finally {
                if (btnEnviarRecuperacao) {
                    btnEnviarRecuperacao.disabled = false;
                    btnEnviarRecuperacao.textContent = "Enviar e-mail";
                }
            }
        });

    }


    function mostrarErro(mensagem) {
        if (!overlay) return;

        overlay.style.display = "flex";
        esconderTelas();

        if (!popupErro) return;

        const msgErro = popupErro.querySelector(".info");

        if (msgErro) {
            msgErro.textContent = mensagem;
        }

        popupErro.style.display = "flex";
    }

    if (btnTentarNovamente) {
        btnTentarNovamente.addEventListener("click", function () {
            esconderTelas();

            if (popupRecuperar) {
                popupRecuperar.style.display = "flex";
            }

            if (emailRecuperacao) {
                emailRecuperacao.value = "";
                setTimeout(function () {
                    emailRecuperacao.focus();
                }, 200);
            }
        });
    }


    if (btnReenviar) {
        btnReenviar.addEventListener("click", function () {
            esconderTelas();

            if (popupRecuperar) {
                popupRecuperar.style.display = "flex";
            }

            if (emailRecuperacao) {
                emailRecuperacao.focus();
            }
        });
    }


    if (overlay) {
        overlay.addEventListener("click", function (e) {
            if (e.target === overlay) {
                fecharPopup();
            }
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            fecharPopup();
        }
    });

});