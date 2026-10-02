document.addEventListener("DOMContentLoaded", () => {

    const status = document.querySelectorAll(".status");

    status.forEach(item => {

        const texto = item.textContent.trim().toLowerCase();

        item.classList.remove(
            "exposicao",
            "restauracao",
            "reserva",
            "indisponivel"
        );

        switch (texto) {

            case "em exposição":
            case "em exposicao":
                item.classList.add("exposicao");
                break;

            case "em restauração":
            case "em restauracao":
                item.classList.add("restauracao");
                break;

            case "reserva técnica":
            case "reserva tecnica":
                item.classList.add("reserva");
                break;

            case "indisponível":
            case "indisponivel":
                item.classList.add("indisponivel");
                break;

        }

    });

});