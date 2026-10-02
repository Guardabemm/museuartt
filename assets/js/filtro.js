const pesquisa = document.getElementById("pesquisarObra");
const filtroCategoria = document.getElementById("filtroCategoria");
const filtroStatus = document.getElementById("filtroStatus");
const ordenacao = document.getElementById("ordenacao");
const limpar = document.getElementById("limparFiltros");

const lista = document.getElementById("listaConteudo");
const linhas = Array.from(document.querySelectorAll(".linha-obra"));
const contador = document.getElementById("contadorObras");

function aplicarFiltros() {

    const texto = pesquisa.value.toLowerCase().trim();
    const categoria = filtroCategoria.value.toLowerCase();
    const status = filtroStatus.value.toLowerCase();

    let quantidade = 0;

    linhas.forEach(linha => {

        const pesquisaOK =
            linha.dataset.nome.includes(texto) ||
            linha.dataset.artista.includes(texto) ||
            linha.dataset.codigo.includes(texto);

        const categoriaOK =
            categoria === "" ||
            linha.dataset.categoria === categoria;

        const statusOK =
            status === "" ||
            linha.dataset.status === status;

        const exibir = pesquisaOK && categoriaOK && statusOK;

        linha.style.display = exibir ? "grid" : "none";

        if (exibir) {
            quantidade++;
        }

    });

    atualizarContador(quantidade);

    ordenarLista();

}

function atualizarContador(total) {

    contador.textContent = total;

}

function ordenarLista() {

    const itens = Array.from(lista.querySelectorAll(".linha-obra"));

    itens.sort((a, b) => {

        switch (ordenacao.value) {

            case "az":
                return a.dataset.nome.localeCompare(b.dataset.nome);

            case "za":
                return b.dataset.nome.localeCompare(a.dataset.nome);

            case "artista":
                return a.dataset.artista.localeCompare(b.dataset.artista);

            case "categoria":
                return a.dataset.categoria.localeCompare(b.dataset.categoria);

            case "codigo":
                return a.dataset.codigo.localeCompare(b.dataset.codigo);

            default:
                return 0;

        }

    });

    itens.forEach(item => lista.appendChild(item));

}

function limparFiltros() {

    pesquisa.value = "";
    filtroCategoria.value = "";
    filtroStatus.value = "";
    ordenacao.value = "az";

    aplicarFiltros();

}

pesquisa.addEventListener("input", aplicarFiltros);
filtroCategoria.addEventListener("change", aplicarFiltros);
filtroStatus.addEventListener("change", aplicarFiltros);

ordenacao.addEventListener("change", () => {

    ordenarLista();

});

limpar.addEventListener("click", limparFiltros);

aplicarFiltros();