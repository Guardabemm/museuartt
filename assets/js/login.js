function mostrarSenha() {

    const senhaInput = document.getElementById("senha");
    const btnShowSenha = document.getElementById("btn-senha");

    if (senhaInput.type === "password") {
        senhaInput.type = "text";
        btnShowSenha.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        senhaInput.type = "password";
        btnShowSenha.classList.replace("bi-eye-slash", "bi-eye");
    }

}

document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById("formLogin");

    form.addEventListener("submit", async (e) => {

        e.preventDefault();

        const dados = new FormData(form);

        try {

            const resposta = await fetch("../backend/API/Login.php", {
                method: "POST",
                body: dados
            });

            if (!resposta.ok) {
                throw new Error("Erro HTTP: " + resposta.status);
            }

            const texto = await resposta.text();

            let json;

            try {
                json = JSON.parse(texto);
            } catch (erroJson) {

                console.error("Resposta do PHP:");
                console.log(texto);

                mostrarToast(
                    "erro",
                    "Erro",
                    "O servidor retornou uma resposta inválida."
                );

                return;
            }

            if (json.status === "erro") {

                mostrarToast(
                    "erro",
                    "Erro",
                    json.mensagem
                );

                return;
            }

            if (json.status === "sucesso") {

                mostrarToast(
                    "sucesso",
                    "Bem-vindo!",
                    json.mensagem
                );

                setTimeout(() => {
                    window.location.href = json.redirect;
                }, 1000);

            }

        } catch (erro) {

            console.error(erro);

            mostrarToast(
                "erro",
                "Erro",
                "Não foi possível conectar ao servidor."
            );

        }

    });

    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".indicadores span");

    let atual = 0;

    function trocarSlide() {

        if (slides.length === 0) return;

        slides[atual].classList.remove("active");

        if (dots[atual]) {
            dots[atual].classList.remove("ativo");
        }

        atual++;

        if (atual >= slides.length) {
            atual = 0;
        }

        slides[atual].classList.add("active");

        if (dots[atual]) {
            dots[atual].classList.add("ativo");
        }

    }

    setInterval(trocarSlide, 3000);

});