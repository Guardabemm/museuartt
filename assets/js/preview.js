document.addEventListener("DOMContentLoaded", () => {

    const preview = document.getElementById("previewObra");
    const fechar = document.getElementById("fecharPreview");

    const lista = document.querySelector(".pagina");

    const linhas = document.querySelectorAll(".linha-obra");

    const img = document.getElementById("previewImagem");
    const titulo = document.getElementById("previewTitulo");
    const artista = document.getElementById("previewArtista");
    const codigo = document.getElementById("previewCodigo");
    const categoria = document.getElementById("previewCategoria");
    const status = document.getElementById("previewStatus");

    linhas.forEach(linha => {

        linha.addEventListener("click", function(e){

            if(e.target.closest(".btn-acao")) return;

            linhas.forEach(item=>{
                item.classList.remove("selecionada");
            });

            linha.classList.add("selecionada");

            img.src = linha.dataset.imagem;
            img.alt = linha.dataset.nome;

            titulo.textContent = linha.dataset.nome;
            artista.textContent = linha.dataset.artista;
            codigo.textContent = linha.dataset.codigo;
            categoria.textContent = linha.dataset.categoria;
            status.textContent = linha.dataset.status;

            preview.classList.add("aberto");

            
            pagina.classList.add("preview-aberto");

        });

    });

    fechar.addEventListener("click", ()=>{

        preview.classList.remove("aberto");

        pagina.classList.remove("preview-aberto");

        linhas.forEach(item=>{
            item.classList.remove("selecionada");
        });

    });

});