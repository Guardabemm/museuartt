let toast = null;
let toastTimeout = null;

function criarToast() {

    
    if (document.getElementById("toast")) {

        toast = document.getElementById("toast");

        return;
    }


    toast = document.createElement("div");

    toast.id = "toast";
    toast.className = "toast";


    toast.innerHTML = `

        <i id="toastIcon" class="bi"></i>

        <div class="toast-texto">

            <h4 id="toastTitulo"></h4>

            <p id="toastMensagem"></p>

        </div>

        <div class="toast-barra"></div>

    `;


    document.body.appendChild(toast);
}






function mostrarToast(tipo, titulo, mensagem) {

    criarToast();


    const tituloElemento =
        document.getElementById("toastTitulo");

    const mensagemElemento =
        document.getElementById("toastMensagem");

    const icon =
        document.getElementById("toastIcon");

    const barra =
        toast.querySelector(".toast-barra");


    
    clearTimeout(toastTimeout);


    
    toast.classList.remove(
        "sucesso",
        "erro",
        "aviso",
        "mostrar"
    );


    
    toast.classList.add(tipo);


    
    tituloElemento.textContent = titulo;

    mensagemElemento.textContent = mensagem;


    
    switch (tipo) {

        case "sucesso":

            icon.className =
                "bi bi-check-circle-fill";

            break;


        case "erro":

            icon.className =
                "bi bi-x-circle-fill";

            break;


        case "aviso":

            icon.className =
                "bi bi-exclamation-triangle-fill";

            break;


        default:

            icon.className =
                "bi bi-info-circle-fill";

            break;
    }


    
    barra.style.animation = "none";

    void barra.offsetWidth;

    barra.style.animation =
        "barra 4s linear forwards";


    
    toast.classList.add("mostrar");


    
    toastTimeout = setTimeout(() => {

        toast.classList.remove("mostrar");

    }, 4000);

}