document.addEventListener("DOMContentLoaded", function () {






    const pesquisa =
        document.getElementById("pesquisaFuncionarios");


    const filtroTipo =
        document.getElementById("filtroTipo");


    const ordenacao =
        document.getElementById("ordenacaoFuncionarios");


    const btnCards =
        document.getElementById("btnCards");


    const btnTabela =
        document.getElementById("btnTabela");


    const visualizacaoCards =
        document.getElementById("visualizacaoCards");


    const visualizacaoTabela =
        document.getElementById("visualizacaoTabela");








    if (
        !pesquisa ||
        !filtroTipo ||
        !ordenacao ||
        !btnCards ||
        !btnTabela ||
        !visualizacaoCards ||
        !visualizacaoTabela
    ) {


        console.error(
            "Elementos da página de funcionários não encontrados."
        );


        return;


    }








    let cards = Array.from(
        visualizacaoCards.querySelectorAll(".funcionario-card")
    );


    let linhas = Array.from(
        visualizacaoTabela.querySelectorAll(
            "tbody tr"
        )
    );








    btnCards.addEventListener("click", function () {


        visualizacaoCards.style.display = "grid";
        visualizacaoTabela.style.display = "none";


        btnCards.classList.add("ativo");
        btnTabela.classList.remove("ativo");


        localStorage.setItem(
            "visualizacaoFuncionarios",
            "cards"
        );


    });




    btnTabela.addEventListener("click", function () {


        visualizacaoCards.style.display = "none";
        visualizacaoTabela.style.display = "block";


        btnTabela.classList.add("ativo");
        btnCards.classList.remove("ativo");


        localStorage.setItem(
            "visualizacaoFuncionarios",
            "tabela"
        );


    });








    const visualizacaoSalva =
        localStorage.getItem(
            "visualizacaoFuncionarios"
        );




    if (visualizacaoSalva === "tabela") {


        visualizacaoCards.style.display = "none";
        visualizacaoTabela.style.display = "block";


        btnTabela.classList.add("ativo");
        btnCards.classList.remove("ativo");


    } else {


        visualizacaoCards.style.display = "grid";
        visualizacaoTabela.style.display = "none";


        btnCards.classList.add("ativo");
        btnTabela.classList.remove("ativo");


    }




    function obterPontuacao(elemento, termo) {
        if (!termo) return 0;


        const nome = String(elemento.dataset.nome || "").toLowerCase();
        const email = String(elemento.dataset.email || "").toLowerCase();


        if (nome.startsWith(termo)) return 1;  // Prioridade 1: Nome começa com o termo
        if (email.startsWith(termo)) return 2; // Prioridade 2: E-mail começa com o termo
        return 3;                              // Prioridade 3: Aparece no meio de algum campo
    }


    function aplicarFiltros() {
        const texto = pesquisa.value.trim().toLowerCase();
        const tipoSelecionado = filtroTipo.value.trim().toLowerCase();


        let cardsVisiveis = 0;


        // 1. FILTRAR E OCULTAR/EXIBIR CARDS
        cards.forEach(function (card) {
            const nome = String(card.dataset.nome || "").toLowerCase();
            const email = String(card.dataset.email || "").toLowerCase();
            const tipo = String(card.dataset.tipo || "").toLowerCase();
            const login = String(card.dataset.login || "").toLowerCase();


            const correspondePesquisa =
                texto === "" ||
                nome.includes(texto) ||
                email.includes(texto) ||
                tipo.includes(texto) ||
                login.includes(texto);


            const correspondeTipo =
                tipoSelecionado === "" ||
                tipo === tipoSelecionado;


            const mostrar = correspondePesquisa && correspondeTipo;


            if (mostrar) {
                card.style.display = "flex";
                cardsVisiveis++;
            } else {
                card.style.display = "none";
            }
        });


        // REORDENAR CARDS POR PRIORIDADE SE HOUVER BUSCA POR TEXTO
        if (texto !== "") {
            const cardsOrdenados = [...cards].sort(function (a, b) {
                const pesoA = obterPontuacao(a, texto);
                const pesoB = obterPontuacao(b, texto);


                if (pesoA !== pesoB) {
                    return pesoA - pesoB;
                }
                return String(a.dataset.nome || "").localeCompare(String(b.dataset.nome || ""), "pt-BR");
            });


            cardsOrdenados.forEach(function (card) {
                visualizacaoCards.appendChild(card);
            });
        }


        // 2. FILTRAR E OCULTAR/EXIBIR LINHAS DA TABELA
        let linhasVisiveis = 0;


        linhas.forEach(function (linha) {
            const nome = String(linha.dataset.nome || "").toLowerCase();
            const email = String(linha.dataset.email || "").toLowerCase();
            const tipo = String(linha.dataset.tipo || "").toLowerCase();
            const login = String(linha.dataset.login || "").toLowerCase();


            const correspondePesquisa =
                texto === "" ||
                nome.includes(texto) ||
                email.includes(texto) ||
                tipo.includes(texto) ||
                login.includes(texto);


            const correspondeTipo =
                tipoSelecionado === "" ||
                tipo === tipoSelecionado;


            const mostrar = correspondePesquisa && correspondeTipo;


            if (mostrar) {
                linha.style.display = "";
                linhasVisiveis++;
            } else {
                linha.style.display = "none";
            }
        });


        // REORDENAR TABELA POR PRIORIDADE SE HOUVER BUSCA POR TEXTO
        const tbody = document.getElementById("tabelaFuncionarios");
        if (tbody && texto !== "") {
            const linhasOrdenadas = [...linhas].sort(function (a, b) {
                const pesoA = obterPontuacao(a, texto);
                const pesoB = obterPontuacao(b, texto);


                if (pesoA !== pesoB) {
                    return pesoA - pesoB;
                }
                return String(a.dataset.nome || "").localeCompare(String(b.dataset.nome || ""), "pt-BR");
            });


            linhasOrdenadas.forEach(function (linha) {
                tbody.appendChild(linha);
            });
        }


        atualizarMensagemVazia(
            cardsVisiveis,
            linhasVisiveis,
            texto
        );
    }








    function atualizarMensagemVazia(
        cardsVisiveis,
        linhasVisiveis,
        texto
    ) {


        let mensagemCards =
            document.getElementById(
                "mensagemVaziaFuncionarios"
            );




        if (
            cardsVisiveis === 0 &&
            cards.length > 0
        ) {


            if (!mensagemCards) {


                mensagemCards =
                    document.createElement("div");


                mensagemCards.id =
                    "mensagemVaziaFuncionarios";


                mensagemCards.className =
                    "funcionarios-pesquisa-vazia";


                visualizacaoCards.appendChild(
                    mensagemCards
                );


            }




            mensagemCards.innerHTML = `
                <i class="bi bi-search"></i>


                <h3>
                    Nenhum funcionário encontrado
                </h3>


                <p>
                    Não encontramos resultados para
                    <strong>"${escapeHTML(texto)}"</strong>.
                </p>
            `;


        } else if (mensagemCards) {


            mensagemCards.remove();


        }








        const tbody =
            document.getElementById(
                "tabelaFuncionarios"
            );




        if (!tbody) {
            return;
        }




        let mensagemTabela =
            document.getElementById(
                "mensagemTabelaVazia"
            );




        if (
            linhasVisiveis === 0 &&
            linhas.length > 0
        ) {


            if (!mensagemTabela) {


                mensagemTabela =
                    document.createElement("tr");


                mensagemTabela.id =
                    "mensagemTabelaVazia";


                mensagemTabela.innerHTML = `
                    <td
                        colspan="6"
                        style="
                            text-align:center;
                            padding:40px;
                            color:#555;
                        "
                    >
                        <i
                            class="bi bi-search"
                            style="
                                font-size:25px;
                                display:block;
                                margin-bottom:10px;
                            "
                        ></i>


                        Nenhum funcionário encontrado.
                    </td>
                `;


                tbody.appendChild(
                    mensagemTabela
                );


            }


        } else if (mensagemTabela) {


            mensagemTabela.remove();


        }


    }








    pesquisa.addEventListener(
        "input",
        aplicarFiltros
    );




    filtroTipo.addEventListener(
        "change",
        aplicarFiltros
    );








    ordenacao.addEventListener(
        "change",
        function () {


            const valor =
                ordenacao.value;




            ordenarFuncionarios(
                valor
            );


        }
    );




    function ordenarFuncionarios(tipo) {


        const cardsOrdenados =
            [...cards];




        switch (tipo) {


            case "nome-az":


                cardsOrdenados.sort(
                    function (a, b) {


                        return a.dataset.nome
                            .localeCompare(
                                b.dataset.nome,
                                "pt-BR"
                            );


                    }
                );


                break;




            case "nome-za":


                cardsOrdenados.sort(
                    function (a, b) {


                        return b.dataset.nome
                            .localeCompare(
                                a.dataset.nome,
                                "pt-BR"
                            );


                    }
                );


                break;




            case "recente":


                cardsOrdenados.sort(
                    function (a, b) {


                        return new Date(
                            b.dataset.vinculo || 0
                        ) -
                            new Date(
                                a.dataset.vinculo || 0
                            );


                    }
                );


                break;




            case "antigo":


                cardsOrdenados.sort(
                    function (a, b) {


                        return new Date(
                            a.dataset.vinculo || 0
                        ) -
                            new Date(
                                b.dataset.vinculo || 0
                            );


                    }
                );


                break;


        }








        cardsOrdenados.forEach(
            function (card) {


                visualizacaoCards.appendChild(
                    card
                );


            }
        );




        cards =
            Array.from(
                visualizacaoCards.querySelectorAll(
                    ".funcionario-card"
                )
            );








        const tbody =
            document.getElementById(
                "tabelaFuncionarios"
            );




        if (!tbody) {
            return;
        }




        cardsOrdenados.forEach(
            function (card) {


                const id =
                    card.dataset.id;




                const linha =
                    linhas.find(
                        function (item) {


                            return item.dataset.id === id;


                        }
                    );




                if (linha) {


                    tbody.appendChild(
                        linha
                    );


                }


            }
        );




        linhas =
            Array.from(
                tbody.querySelectorAll(
                    "tr"
                )
            );




        aplicarFiltros();


    }








    document.addEventListener(
        "click",
        function (e) {


            const botao =
                e.target.closest(
                    ".btn-crud"
                );




            if (botao) {


                e.stopPropagation();




                const menu =
                    botao.parentElement
                        .querySelector(
                            ".crud-menu"
                        );




                document
                    .querySelectorAll(
                        ".crud-menu.ativo"
                    )
                    .forEach(
                        function (outroMenu) {


                            if (
                                outroMenu !== menu
                            ) {


                                outroMenu.classList.remove(
                                    "ativo"
                                );


                            }


                        }
                    );




                if (menu) {


                    menu.classList.toggle(
                        "ativo"
                    );


                }


                return;


            }




            if (
                !e.target.closest(
                    ".dropdown-crud"
                )
            ) {


                document
                    .querySelectorAll(
                        ".crud-menu.ativo"
                    )
                    .forEach(
                        function (menu) {


                            menu.classList.remove(
                                "ativo"
                            );


                        }
                    );


            }


        }
    );








    const favoritos =
        JSON.parse(
            localStorage.getItem(
                "funcionariosFavoritos"
            ) || "[]"
        );




    document.addEventListener(
        "click",
        function (e) {


            const botao =
                e.target.closest(
                    ".btn-favorito"
                );




            if (!botao) {
                return;
            }




            e.stopPropagation();




            const id =
                String(
                    botao.dataset.id
                );




            const icone =
                botao.querySelector(
                    "i"
                );




            const indice =
                favoritos.indexOf(id);




            if (indice === -1) {


                favoritos.push(id);


                botao.classList.add(
                    "favoritado"
                );


                if (icone) {


                    icone.classList.remove(
                        "bi-star"
                    );


                    icone.classList.add(
                        "bi-star-fill"
                    );


                }


            } else {


                favoritos.splice(
                    indice,
                    1
                );


                botao.classList.remove(
                    "favoritado"
                );


                if (icone) {


                    icone.classList.remove(
                        "bi-star-fill"
                    );


                    icone.classList.add(
                        "bi-star"
                    );


                }


            }




            localStorage.setItem(
                "funcionariosFavoritos",
                JSON.stringify(
                    favoritos
                )
            );


        }
    );








    document
        .querySelectorAll(
            ".btn-favorito"
        )
        .forEach(
            function (botao) {


                const id =
                    String(
                        botao.dataset.id
                    );




                if (
                    favoritos.includes(id)
                ) {


                    botao.classList.add(
                        "favoritado"
                    );




                    const icone =
                        botao.querySelector(
                            "i"
                        );




                    if (icone) {


                        icone.classList.remove(
                            "bi-star"
                        );


                        icone.classList.add(
                            "bi-star-fill"
                        );


                    }


                }


            }
        );








    function escapeHTML(texto) {


        const div =
            document.createElement(
                "div"
            );


        div.textContent =
            texto || "";


        return div.innerHTML;


    }








    aplicarFiltros();




    console.log(
        "Sistema de funcionários carregado.",
        funcionariosQuantidade()
    );




    function funcionariosQuantidade() {


        return {
            cards: cards.length,
            tabela: linhas.length
        };


    }


});
