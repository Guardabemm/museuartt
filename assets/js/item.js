

const imagemObra =
    document.getElementById("imagemObra");

const visualizador =
    document.getElementById("visualizador");

const imagemVisualizada =
    document.getElementById("imagemVisualizada");

const fecharVisualizador =
    document.getElementById("fecharVisualizador");

const zoomMais =
    document.getElementById("zoomMais");

const zoomMenos =
    document.getElementById("zoomMenos");

const zoomReset =
    document.getElementById("zoomReset");

const telaCheia =
    document.getElementById("telaCheia");

const imagemContainer =
    document.getElementById("imagemContainer");

const miniaturas =
    document.querySelectorAll(".miniatura");




let escala = 1;
let posicaoX = 0;
let posicaoY = 0;
let arrastando = false;
let inicioX = 0;
let inicioY = 0;




function atualizarImagem() {
    imagemVisualizada.style.transform =
        `translate(${posicaoX}px, ${posicaoY}px) scale(${escala})`;
}




function resetarImagem() {
    escala = 1;
    posicaoX = 0;
    posicaoY = 0;
    atualizarImagem();
}




imagemObra.addEventListener("click", () => {
    visualizador.classList.add("aberto");
    document.body.style.overflow = "hidden";
    resetarImagem();
});




function fecharImagem() {
    visualizador.classList.remove("aberto");
    document.body.style.overflow = "";
    resetarImagem();
}

fecharVisualizador.addEventListener("click", fecharImagem);




visualizador.addEventListener("click", (event) => {
    if (event.target === visualizador) {
        fecharImagem();
    }
});




document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && visualizador.classList.contains("aberto")) {
        fecharImagem();
    }
});




zoomMais.addEventListener("click", () => {
    escala += .25;
    if (escala > 4) {
        escala = 4;
    }
    atualizarImagem();
});




zoomMenos.addEventListener("click", () => {
    escala -= .25;
    if (escala < .5) {
        escala = .5;
    }
    if (escala === 1) {
        posicaoX = 0;
        posicaoY = 0;
    }
    atualizarImagem();
});




zoomReset.addEventListener("click", () => {
    resetarImagem();
});




miniaturas.forEach((miniatura) => {
    miniatura.addEventListener("click", () => {
        const temImagem = miniatura.dataset.temImagem === 'true';
        if (!temImagem) {
            return;
        }
        const novaImagem = miniatura.dataset.imagem;
        if (!novaImagem) {
            return;
        }
        imagemVisualizada.src = novaImagem;
        if (imagemObra) {
            imagemObra.src = novaImagem;
        }
        miniaturas.forEach((item) => {
            item.classList.remove("selecionada");
        });
        miniatura.classList.add("selecionada");
        resetarImagem();
    });
});




imagemContainer.addEventListener("mousedown", (event) => {
    if (escala <= 1) {
        return;
    }
    arrastando = true;
    inicioX = event.clientX - posicaoX;
    inicioY = event.clientY - posicaoY;
    imagemContainer.classList.add("arrastando");
});

document.addEventListener("mousemove", (event) => {
    if (!arrastando) {
        return;
    }
    posicaoX = event.clientX - inicioX;
    posicaoY = event.clientY - inicioY;
    atualizarImagem();
});

document.addEventListener("mouseup", () => {
    arrastando = false;
    imagemContainer.classList.remove("arrastando");
});




imagemVisualizada.addEventListener("dblclick", () => {
    if (escala === 1) {
        escala = 2;
    } else {
        resetarImagem();
        return;
    }
    atualizarImagem();
});




telaCheia.addEventListener("click", async () => {
    try {
        if (!document.fullscreenElement) {
            await visualizador.requestFullscreen();
        } else {
            await document.exitFullscreen();
        }
    } catch (erro) {
        console.error(
            "Não foi possível ativar tela cheia:",
            erro
        );
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const modoSelecao = window.modoSelecaoObra || "";
    if (!modoSelecao) {
        return;
    }
    const obras = document.querySelectorAll(
        ".obra-selecionavel"
    );
    const containerObras =
        document.querySelector(".obras-grid");
    if (!containerObras) {
        return;
    }
    const aviso = document.createElement("div");
    aviso.className = "aviso-selecao-obra ativo";
    if (modoSelecao === "editar") {
        aviso.innerHTML = `
            <i class="bi bi-pencil-square"></i>
            <div class="aviso-selecao-texto">
                <strong>Modo de edição</strong>
                <span>
                    Selecione uma obra abaixo para editar seus dados.
                </span>
            </div>
        `;
        document.body.classList.add("modo-editar");
    }
    else if (modoSelecao === "excluir") {
        aviso.innerHTML = `
            <i class="bi bi-trash3"></i>
            <div class="aviso-selecao-texto">
                <strong>Modo de exclusão</strong>
                <span>
                    Selecione uma obra abaixo para excluir.
                </span>
            </div>
        `;
        document.body.classList.add("modo-excluir");
    }
    else if (modoSelecao === "atualizar") {
        aviso.innerHTML = `
            <i class="bi bi-arrow-clockwise"></i>
            <div class="aviso-selecao-texto">
                <strong>Modo de atualização</strong>
                <span>
                    Selecione uma obra abaixo para atualizar.
                </span>
            </div>
        `;
    }
    containerObras.parentNode.insertBefore(
        aviso,
        containerObras
    );
    obras.forEach(function (obra) {
        obra.classList.add("obra-modo-selecao");
        obra.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            obras.forEach(function (outraObra) {
                outraObra.classList.remove(
                    "obra-selecionada"
                );
            });
            obra.classList.add(
                "obra-selecionada"
            );
            const id =
                obra.dataset.id ||
                obra.getAttribute("data-id");
            if (!id) {
                console.error(
                    "Não foi possível encontrar o ID da obra."
                );
                return;
            }
            if (modoSelecao === "editar") {
                window.location.href =
                    "editar.php?id=" + encodeURIComponent(id);
            }
            else if (modoSelecao === "excluir") {
                if (typeof excluirItem === "function") {
                    excluirItem(id);
                }
            }
            else if (modoSelecao === "atualizar") {
                window.location.href =
                    "editar.php?id=" + encodeURIComponent(id);
            }
        });
    });
});