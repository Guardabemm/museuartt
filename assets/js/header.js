document.addEventListener("DOMContentLoaded", function () {

    const pesquisa = document.getElementById("pesquisa");
    const sugestoes = document.getElementById("sugestoes");
    const btnLimpar = document.getElementById("btnLimpar");

    const perfilBtn = document.getElementById("perfilBtn");
    const perfilMenu = document.getElementById("perfilMenu");

    const botaoLogout = document.getElementById("btnLogout");
    const modalSaida = document.getElementById("modalSaida");
    const cancelarSaida = document.getElementById("cancelarSaida");

    if (!perfilBtn || !perfilMenu) {
        return;
    }

    perfilBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        perfilMenu.classList.toggle("ativo");
    });

    document.addEventListener("click", function (e) {
        if (
            !perfilMenu.contains(e.target) &&
            !perfilBtn.contains(e.target)
        ) {
            perfilMenu.classList.remove("ativo");
        }
    });

    // BLOCO DA PESQUISA
    if (pesquisa && sugestoes) {
        if (typeof itens === "undefined") {
            console.error("Variável 'itens' não definida. Certifique-se de carregar os dados antes do header.js.");
        } else {
            console.log("Sistema de pesquisa iniciado. Itens carregados:", itens.length);

            let indiceSelecionado = -1;

            pesquisa.addEventListener("input", function () {
                const texto = this.value.trim().toLowerCase();

                if (btnLimpar) {
                    btnLimpar.style.display = texto.length > 0 ? "flex" : "none";
                }

                filtrarCardsPesquisa(texto);

                sugestoes.innerHTML = "";
                indiceSelecionado = -1;

                if (texto === "") {
                    sugestoes.classList.remove("ativo");
                    return;
                }

                if (typeof itens === "undefined" || !Array.isArray(itens)) return;

                const itensFiltrados = itens.filter(function (item) {
                    const nome = String(item.nome_item || "").toLowerCase();
                    const autor = String(item.nome_autor || "").toLowerCase();
                    const categoria = String(item.categoria || "").toLowerCase();

                    return nome.includes(texto) || autor.includes(texto) || categoria.includes(texto);
                });

                function obterPontuacao(item, termo) {
                    const nome = String(item.nome_item || "").toLowerCase();
                    const autor = String(item.nome_autor || "").toLowerCase();
                    const categoria = String(item.categoria || "").toLowerCase();

                    if (nome.startsWith(termo)) return 1;
                    if (autor.startsWith(termo)) return 2;
                    if (categoria.startsWith(termo)) return 3;
                    return 4;
                }

                const resultados = itensFiltrados
                    .sort(function (a, b) {
                        const pesoA = obterPontuacao(a, texto);
                        const pesoB = obterPontuacao(b, texto);

                        if (pesoA !== pesoB) {
                            return pesoA - pesoB;
                        }

                        return String(a.nome_item || "").localeCompare(String(b.nome_item || ""));
                    })
                    .slice(0, 8);

                if (resultados.length === 0) {
                    sugestoes.innerHTML = `
                        <div class="sugestao-vazia">
                            <i class="bi bi-search"></i>
                            <span>Nenhum resultado encontrado para "<strong>${escapeHTML(texto)}</strong>"</span>
                        </div>
                    `;
                    sugestoes.classList.add("ativo");
                    return;
                }

                resultados.forEach(function (item) {
                    const sugestao = document.createElement("div");
                    sugestao.className = "sugestao";

                    let icone = "bi-image";
                    const categoria = String(item.categoria || "").toLowerCase();

                    if (categoria.includes("quadro") || categoria.includes("pintura")) {
                        icone = "bi-palette";
                    } else if (categoria.includes("escultura")) {
                        icone = "bi-cube";
                    } else if (categoria.includes("fotografia")) {
                        icone = "bi-camera";
                    } else if (categoria.includes("taxidermia")) {
                        icone = "bi-tree";
                    } else if (categoria.includes("conservação") || categoria.includes("conservacao")) {
                        icone = "bi-shield-check";
                    }

                    const nomeDestacado = destacarTexto(item.nome_item || "Sem título", texto);
                    const autorDestacado = destacarTexto(item.nome_autor || "Autor desconhecido", texto);

                    sugestao.innerHTML = `
                        <i class="bi ${icone}"></i>
                        <div class="sugestao-info">
                            <span class="sugestao-nome">${nomeDestacado}</span>
                            <span class="sugestao-autor">${autorDestacado}</span>
                        </div>
                        <span class="sugestao-categoria">${escapeHTML(item.categoria || "Geral")}</span>
                    `;

                    sugestao.addEventListener("click", function () {
                        const id = item.cod_item;

                        if (!id) {
                            console.error("Código da obra não encontrado.", item);
                            return;
                        }

                        // CAMINHO CORRIGIDO AQUI:
                        window.location.href = `/SistemaMuseuArt.GuardaBem/components/pages/item.php?id=${encodeURIComponent(id)}&origem=administrador`;
                    });

                    sugestoes.appendChild(sugestao);
                });

                sugestoes.classList.add("ativo");
            });

            if (btnLimpar) {
                btnLimpar.addEventListener("click", function (e) {
                    e.preventDefault();
                    pesquisa.value = "";
                    filtrarCardsPesquisa("");
                    sugestoes.innerHTML = "";
                    sugestoes.classList.remove("ativo");
                    btnLimpar.style.display = "none";
                    indiceSelecionado = -1;
                    pesquisa.focus();
                });
            }
        }
    }

    function filtrarCardsPesquisa(texto) {
        const obrasGrid = document.getElementById("obrasGrid");
        if (!obrasGrid) return;

        const cards = obrasGrid.querySelectorAll(".obra-card");
        let quantidadeVisivel = 0;

        cards.forEach(function (card) {
            const nome = String(card.dataset.nome || "").toLowerCase();
            const autor = String(card.dataset.autor || "").toLowerCase();
            const categoria = String(card.dataset.categoria || "").toLowerCase();

            if (texto === "") {
                card.style.display = "";
                quantidadeVisivel++;
                return;
            }

            const corresponde = nome.includes(texto) || autor.includes(texto) || categoria.includes(texto);

            if (corresponde) {
                card.style.display = "";
                quantidadeVisivel++;
            } else {
                card.style.display = "none";
            }
        });

        let mensagem = document.getElementById("nenhumaObraPesquisa");

        if (texto !== "" && quantidadeVisivel === 0) {
            if (!mensagem) {
                mensagem = document.createElement("div");
                mensagem.id = "nenhumaObraPesquisa";
                mensagem.className = "nenhuma-obra-pesquisa";
                mensagem.innerHTML = `
                    <i class="bi bi-search"></i>
                    <h3>Nenhuma obra encontrada</h3>
                    <p>Não encontramos obras para "<strong></strong>"</p>
                `;
                obrasGrid.appendChild(mensagem);
            }
            mensagem.querySelector("strong").textContent = texto;
            mensagem.style.display = "flex";
        } else {
            if (mensagem) {
                mensagem.style.display = "none";
            }
        }
    }

    if (cancelarSaida && modalSaida) {
        cancelarSaida.addEventListener("click", function () {
            modalSaida.style.display = "none";
        });
    }

    window.addEventListener("click", function (e) {
        if (modalSaida && e.target === modalSaida) {
            modalSaida.style.display = "none";
        }
    });

    function destacarTexto(texto, termo) {
        if (!texto) return "Sem título";
        const textoSeguro = escapeHTML(texto);
        const termoEscapado = termo.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        const regex = new RegExp(`(${termoEscapado})`, "gi");
        return textoSeguro.replace(regex, "<mark>$1</mark>");
    }

    function escapeHTML(texto) {
        return String(texto)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    console.log("header.js carregado com sucesso!");
});