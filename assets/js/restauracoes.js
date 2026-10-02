
/* =========================================================
   FILTROS
   ========================================================= */

const pesquisa =
    document.getElementById(
        "pesquisaRestauracao"
    );

const filtroStatus =
    document.getElementById(
        "filtroStatusRestauracao"
    );

const filtroPrioridade =
    document.getElementById(
        "filtroPrioridadeRestauracao"
    );

const filtroResponsavel =
    document.getElementById(
        "filtroResponsavelRestauracao"
    );

const linhas =
    document.querySelectorAll(
        ".restauracao-row"
    );

const semResultados =
    document.getElementById(
        "semResultados"
    );


function aplicarFiltros() {

    const texto =
        pesquisa.value
            .toLowerCase()
            .trim();

    const status =
        filtroStatus.value;

    const prioridade =
        filtroPrioridade.value;

    const responsavel =
        filtroResponsavel.value;

    let quantidadeVisivel = 0;


    linhas.forEach(function (linha) {

        const nome =
            linha.dataset.nome || "";

        const autor =
            linha.dataset.autor || "";

        const statusLinha =
            linha.dataset.status || "";

        const prioridadeLinha =
            linha.dataset.prioridade || "";

        const responsavelLinha =
            linha.dataset.responsavel || "";


        const correspondeTexto =
            !texto ||
            nome.includes(texto) ||
            autor.includes(texto);


        const correspondeStatus =
            !status ||
            statusLinha === status;


        const correspondePrioridade =
            !prioridade ||
            prioridadeLinha === prioridade;


        const correspondeResponsavel =
            !responsavel ||
            responsavelLinha === responsavel;


        const mostrar =
            correspondeTexto &&
            correspondeStatus &&
            correspondePrioridade &&
            correspondeResponsavel;


        if (mostrar) {

            linha.style.display = "grid";

            quantidadeVisivel++;

        } else {

            linha.style.display = "none";

        }

    });


    if (quantidadeVisivel === 0) {

        semResultados.style.display = "block";

    } else {

        semResultados.style.display = "none";

    }

}



/* =========================================================
   EVENTOS DOS FILTROS
   ========================================================= */

if (pesquisa) {

    pesquisa.addEventListener(
        "input",
        aplicarFiltros
    );

}


if (filtroStatus) {

    filtroStatus.addEventListener(
        "change",
        aplicarFiltros
    );

}


if (filtroPrioridade) {

    filtroPrioridade.addEventListener(
        "change",
        aplicarFiltros
    );

}


if (filtroResponsavel) {

    filtroResponsavel.addEventListener(
        "change",
        aplicarFiltros
    );

}



/* =========================================================
   LIMPAR FILTROS
   ========================================================= */

const btnLimpar =
    document.getElementById(
        "limparFiltrosRestauracao"
    );


if (btnLimpar) {

    btnLimpar.addEventListener(
        "click",
        function () {

            pesquisa.value = "";

            filtroStatus.value = "";

            filtroPrioridade.value = "";

            filtroResponsavel.value = "";

            aplicarFiltros();

        }
    );

}



/* =========================================================
   MODAL DE DETALHES
   ========================================================= */

function abrirDetalhesRestauracao(botao) {

    const dados = JSON.parse(
        botao.getAttribute('data-restauracao')
    );

    const modal = document.getElementById(
        'modalRestauracao'
    );

    const conteudo = document.getElementById(
        'conteudoModalRestauracao'
    );

    /*
     * ==========================================
     * CORRIGIR CAMINHO DA IMAGEM
     * ==========================================
     */

    let imagem = dados.foto_item || '';

    if (imagem.startsWith('assets/')) {
        imagem = '../../../' + imagem;
    }

    /*
     * Caso não exista imagem
     */

    if (!imagem) {
        imagem =
            '../../../assets/img/obras/imagem-padrao.jpg';
    }

    /*
     * ==========================================
     * CONTEÚDO DO MODAL
     * ==========================================
     */

    conteudo.innerHTML = `

        <div class="detalhes-restauracao">

            <div class="detalhes-obra">

                <img
                    src="${imagem}"
                    alt="${dados.nome_item || 'Obra'}"
                    class="imagem-detalhes-restauracao"
                    onerror="
                        this.onerror=null;
                        this.src='../../../assets/img/obras/imagem-padrao.jpg';
                    "
                >

                <div>

                    <h2>
                        ${dados.nome_item || 'Obra sem nome'}
                    </h2>

                    <p>
                        ${dados.nome_autor || 'Autor desconhecido'}
                    </p>

                </div>

            </div>


            <div class="detalhes-grid">

                <div class="detalhe-item">

                    <span>Status</span>

                    <strong>
                        ${dados.status || '—'}
                    </strong>

                </div>


                <div class="detalhe-item">

                    <span>Prioridade</span>

                    <strong>
                        ${dados.prioridade || '—'}
                    </strong>

                </div>


                <div class="detalhe-item">

                    <span>Responsável</span>

                    <strong>
                        ${dados.responsavel || '—'}
                    </strong>

                </div>


                <div class="detalhe-item">

                    <span>Progresso</span>

                    <strong>
                        ${dados.progresso ?? 0}%
                    </strong>

                </div>


                <div class="detalhe-item">

                    <span>Data de início</span>

                    <strong>
                        ${formatarDataModal(dados.data_inicio)}
                    </strong>

                </div>


                <div class="detalhe-item">

                    <span>Previsão</span>

                    <strong>
                        ${formatarDataModal(dados.data_previsao)}
                    </strong>

                </div>

            </div>


            <div class="detalhe-texto">

                <span>Motivo</span>

                <strong>
                    ${dados.motivo || '—'}
                </strong>

            </div>


            <div class="detalhe-texto">

                <span>Observações</span>

                <strong>
                    ${dados.observacoes || '—'}
                </strong>

            </div>

        </div>

    `;

    modal.classList.add('ativo');

}



function formatarDataModal(data) {

    if (!data) {
        return "—";
    }

    const partes =
        data.split("-");

    if (partes.length !== 3) {
        return data;
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}



function fecharDetalhesRestauracao() {

    const modal =
        document.getElementById(
            "modalRestauracao"
        );

    modal.classList.remove("ativo");
}



document.addEventListener(
    "click",
    function (event) {

        const modal =
            document.getElementById(
                "modalRestauracao"
            );


        if (
            event.target === modal
        ) {

            fecharDetalhesRestauracao();

        }

    }
);


/* =========================================================
   NOVA RESTAURAÇÃO
   ========================================================= */

const btnNovaRestauracao =
    document.getElementById(
        "novaRestauracao"
    );

const modalNovaRestauracao =
    document.getElementById(
        "modalNovaRestauracao"
    );

const btnFecharNovaRestauracao =
    document.getElementById(
        "fecharNovaRestauracao"
    );

const btnCancelarNovaRestauracao =
    document.getElementById(
        "cancelarNovaRestauracao"
    );


/* ABRIR */

if (btnNovaRestauracao) {

    btnNovaRestauracao.addEventListener(
        "click",
        function () {

            modalNovaRestauracao.classList.add(
                "ativo"
            );

            document.body.style.overflow =
                "hidden";

        }
    );

}


/* FECHAR */

function fecharNovaRestauracao() {

    modalNovaRestauracao.classList.remove(
        "ativo"
    );

    document.body.style.overflow =
        "";

}


if (btnFecharNovaRestauracao) {

    btnFecharNovaRestauracao.addEventListener(
        "click",
        fecharNovaRestauracao
    );

}


if (btnCancelarNovaRestauracao) {

    btnCancelarNovaRestauracao.addEventListener(
        "click",
        fecharNovaRestauracao
    );

}


/* CLICAR FORA DO MODAL */

if (modalNovaRestauracao) {

    modalNovaRestauracao.addEventListener(
        "click",
        function (event) {

            if (
                event.target ===
                modalNovaRestauracao
            ) {

                fecharNovaRestauracao();

            }

        }
    );

}


document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Escape" &&
            modalNovaRestauracao.classList.contains(
                "ativo"
            )
        ) {

            fecharNovaRestauracao();

        }

    }
);

