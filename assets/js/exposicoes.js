document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    /*
    |--------------------------------------------------------------------------
    | DADOS
    |--------------------------------------------------------------------------
    */

    let eventos = Array.isArray(window.MUSEUART_EXPOSICOES)
        ? window.MUSEUART_EXPOSICOES
        : [];

    /*
    |--------------------------------------------------------------------------
    | ELEMENTOS
    |--------------------------------------------------------------------------
    */

    const modalExposicao = document.getElementById('modalExposicao');
    const modalVisualizar = document.getElementById('modalVisualizarExposicao');
    const modalCalendario = document.getElementById('modalCalendario');

    const btnNovaExposicao = document.getElementById('btnNovaExposicao');
    const btnCriarExposicao = document.getElementById('btnCriarExposicao');

    const fecharModal = document.getElementById('fecharModal');
    const cancelarModal = document.getElementById('cancelarModal');

    const fecharModalVisualizar =
        document.getElementById('fecharModalVisualizar');

    const fecharModalCalendario =
        document.getElementById('fecharModalCalendario');

    const btnEditarVisualizacao =
        document.getElementById('btnEditarVisualizacao');

    const btnNovaCalendario =
        document.getElementById('btnNovaCalendario');

    const formulario =
        modalExposicao
            ? modalExposicao.querySelector('form')
            : null;

    const titulo = document.getElementById('titulo');
    const autor = document.getElementById('autor');
    const descricao = document.getElementById('descricao');

    const dataInicio = document.getElementById('data_inicio');
    const dataFim = document.getElementById('data_fim');

    const horaInicio =
        document.getElementById('horario_inicio') ||
        document.getElementById('hora_inicio');

    const horaFim =
        document.getElementById('horario_fim') ||
        document.getElementById('hora_fim');

    const acaoExposicao =
        document.getElementById('acaoExposicao');

    const idExposicao =
        document.getElementById('idExposicao');

    const modalExposicaoTitulo =
        document.getElementById('modalExposicaoTitulo');

    const modalExposicaoLabel =
        document.getElementById('modalExposicaoLabel');

    const calendarGrid =
        document.getElementById('calendarGrid');

    const nomeMes =
        document.getElementById('nomeMes');

    const mesAnterior =
        document.getElementById('mesAnterior');

    const mesProximo =
        document.getElementById('mesProximo');

    const mesHoje =
        document.getElementById('mesHoje');

    const mesAnteriorMobile =
        document.getElementById('mesAnteriorMobile');

    const mesProximoMobile =
        document.getElementById('mesProximoMobile');

    const calendarioModalTitulo =
        document.getElementById('calendarioModalTitulo');

    const calendarioModalLista =
        document.getElementById('calendarioModalLista');

    const visualizarConteudo =
        document.getElementById('visualizarExposicaoConteudo');

    const toastMensagem =
        document.getElementById('toastMensagem');

    let dataAtual = new Date();

    let exposicaoSelecionada = null;

    let dataSelecionadaCalendario = null;


    /*
    |--------------------------------------------------------------------------
    | UTILITÁRIOS
    |--------------------------------------------------------------------------
    */

    function pad(numero) {
        return String(numero).padStart(2, '0');
    }


    function dataISO(data) {
        return (
            data.getFullYear() +
            '-' +
            pad(data.getMonth() + 1) +
            '-' +
            pad(data.getDate())
        );
    }


    function hojeISO() {
        return dataISO(new Date());
    }


    function amanhaISO() {
        const amanha = new Date();
        amanha.setDate(amanha.getDate() + 1);
        return dataISO(amanha);
    }


    function formatarDataBR(data) {

        if (!data) {
            return '';
        }

        const partes = String(data)
            .substring(0, 10)
            .split('-');

        if (partes.length !== 3) {
            return data;
        }

        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }


    function formatarMes(data) {

        return data.toLocaleDateString(
            'pt-BR',
            {
                month: 'long',
                year: 'numeric'
            }
        );
    }


    function escapeHtml(valor) {

        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    function normalizarData(valor) {

        if (!valor) {
            return '';
        }

        return String(valor)
            .substring(0, 10);
    }


    function eventoId(evento) {

        return String(
            evento.id_exposicao ??
            evento.id ??
            ''
        );
    }


    function encontrarExposicao(id) {

        return eventos.find(
            evento => eventoId(evento) === String(id)
        );
    }


    function eventoNoDia(iso) {

        return eventos.filter(evento => {

            const inicio = normalizarData(
                evento.data_inicio ||
                evento.inicio
            );

            const fim = normalizarData(
                evento.data_fim ||
                evento.fim
            );

            if (!inicio || !fim) {
                return false;
            }

            return (
                iso >= inicio &&
                iso <= fim
            );
        });
    }


    function dataEhPassada(data) {

        if (!data) {
            return false;
        }

        return normalizarData(data) < hojeISO();
    }


    function obterStatusClasse(status) {

        const valor = String(status || '')
            .toLowerCase();

        if (
            valor.includes('andamento') ||
            valor.includes('ativa')
        ) {
            return 'status-andamento';
        }

        if (
            valor.includes('programada') ||
            valor.includes('agendada')
        ) {
            return 'status-programada';
        }

        if (
            valor.includes('encerrada') ||
            valor.includes('finalizada')
        ) {
            return 'status-encerrada';
        }

        return '';
    }


    /*
    |--------------------------------------------------------------------------
    | MODAIS
    |--------------------------------------------------------------------------
    */

    function abrirOverlay(modal) {

        if (!modal) {
            return;
        }

        modal.classList.add('active', 'show');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        modal.style.display = 'flex';

        document.body.classList.add(
            'modal-aberto'
        );
    }


    function fecharOverlay(modal) {

        if (!modal) {
            return;
        }

        modal.classList.remove(
            'active',
            'show'
        );

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        modal.style.display = 'none';
    }


    function fecharTodosModais() {

        fecharOverlay(modalExposicao);
        fecharOverlay(modalVisualizar);
        fecharOverlay(modalCalendario);

        document
            .querySelectorAll('.expo-action-menu.aberto')
            .forEach(menu => {
                menu.classList.remove('aberto');
            });

        document
            .querySelectorAll('.calendar-expo-actions-menu.aberto')
            .forEach(menu => {
                menu.classList.remove('aberto');
            });

        document.body.classList.remove(
            'modal-aberto'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CADASTRO
    |--------------------------------------------------------------------------
    */

    function abrirModalCadastro(dataSelecionada = '') {

        const dataHoje = hojeISO();
        const dataAmanha = amanhaISO();

        /*
        | Se foi clicado em hoje ou em um dia passado, não permite abrir cadastro.
        */

        if (
            dataSelecionada &&
            dataSelecionada <= dataHoje
        ) {

            mostrarToast(
                'Não é possível cadastrar uma exposição no dia atual ou em datas passadas.',
                'error'
            );

            return;
        }


        if (!modalExposicao) {
            return;
        }


        if (formulario) {
            formulario.reset();
        }


        if (acaoExposicao) {
            acaoExposicao.value = 'cadastrar';
        }


        if (idExposicao) {
            idExposicao.value = '';
        }


        if (modalExposicaoTitulo) {
            modalExposicaoTitulo.textContent =
                'Nova exposição';
        }


        if (modalExposicaoLabel) {
            modalExposicaoLabel.textContent =
                'NOVA EXPOSIÇÃO';
        }


        if (dataInicio) {

            dataInicio.min = dataAmanha;

            dataInicio.value =
                dataSelecionada || '';
        }


        if (dataFim) {

            dataFim.min =
                dataSelecionada || dataAmanha;

            dataFim.value =
                dataSelecionada || '';
        }


        if (horaInicio) {
            horaInicio.value = '';
        }


        if (horaFim) {
            horaFim.value = '';
        }


        fecharOverlay(modalCalendario);

        fecharOverlay(modalVisualizar);

        abrirOverlay(modalExposicao);


        setTimeout(() => {

            if (titulo) {
                titulo.focus();
            }

        }, 100);
    }


    /*
    |--------------------------------------------------------------------------
    | EDIÇÃO
    |--------------------------------------------------------------------------
    */

    function abrirModalEdicao(evento) {

        if (!evento) {
            return;
        }


        if (!modalExposicao) {
            return;
        }


        fecharOverlay(modalVisualizar);
        fecharOverlay(modalCalendario);


        if (formulario) {
            formulario.reset();
        }


        if (acaoExposicao) {
            acaoExposicao.value = 'editar';
        }


        if (idExposicao) {
            idExposicao.value =
                evento.id_exposicao ||
                evento.id ||
                '';
        }


        if (modalExposicaoTitulo) {
            modalExposicaoTitulo.textContent =
                'Editar exposição';
        }


        if (modalExposicaoLabel) {

            const ala =
                evento.nome_ala ||
                evento.ala ||
                evento.cod_ala ||
                '';

            modalExposicaoLabel.textContent =
                ala
                    ? `ALA ${ala} · EDITAR EXPOSIÇÃO`
                    : 'EDITAR EXPOSIÇÃO';
        }


        if (titulo) {
            titulo.value =
                evento.titulo || '';
        }


        if (autor) {
            autor.value =
                evento.autor || '';
        }


        if (descricao) {
            descricao.value =
                evento.descricao || '';
        }


        const inicio =
            normalizarData(
                evento.data_inicio ||
                evento.inicio
            );

        const fim =
            normalizarData(
                evento.data_fim ||
                evento.fim
            );


        if (dataInicio) {

            dataInicio.min =
                amanhaISO();

            dataInicio.value =
                inicio;
        }


        if (dataFim) {

            dataFim.min =
                inicio || amanhaISO();

            dataFim.value =
                fim;
        }


        if (horaInicio) {

            horaInicio.value =
                evento.hora_inicio ||
                evento.horario_inicio ||
                '';
        }


        if (horaFim) {

            horaFim.value =
                evento.hora_fim ||
                evento.horario_fim ||
                '';
        }


        abrirOverlay(modalExposicao);
    }


    /*
    |--------------------------------------------------------------------------
    | VISUALIZAÇÃO
    |--------------------------------------------------------------------------
    */

    function abrirModalVisualizacao(evento) {

        if (!evento || !modalVisualizar) {
            return;
        }


        exposicaoSelecionada =
            evento;


        const inicio =
            normalizarData(
                evento.data_inicio ||
                evento.inicio
            );

        const fim =
            normalizarData(
                evento.data_fim ||
                evento.fim
            );


        const horaIni =
            evento.hora_inicio ||
            evento.horario_inicio ||
            '';

        const horaFimValor =
            evento.hora_fim ||
            evento.horario_fim ||
            '';


        const ala =
            evento.nome_ala ||
            evento.ala ||
            evento.cod_ala ||
            'Não informada';


        const status =
            evento.status ||
            'Não informado';


        const imagem =
            evento.imagem_capa ||
            evento.imagem ||
            '';


        const caminhoImagem =
            imagem
                ? imagem
                : '';


        if (visualizarConteudo) {

            visualizarConteudo.innerHTML = `

                <div class="visualizar-exposicao">

                    <div class="visualizar-exposicao-imagem">

                        ${
                            caminhoImagem
                                ? `
                                    <img
                                        src="${escapeHtml(caminhoImagem)}"
                                        alt="${escapeHtml(evento.titulo || 'Exposição')}"
                                    >
                                `
                                : `
                                    <div class="visualizar-sem-imagem">
                                        <i class="bi bi-image"></i>
                                    </div>
                                `
                        }

                    </div>


                    <div class="visualizar-exposicao-dados">

                        <div class="visualizar-titulo-area">

                            <span class="visualizar-kicker">
                                EXPOSIÇÃO
                            </span>

                            <h3>
                                ${escapeHtml(
                                    evento.titulo ||
                                    'Exposição sem título'
                                )}
                            </h3>

                            <p>
                                ${
                                    evento.autor
                                        ? escapeHtml(evento.autor)
                                        : 'Autor não informado'
                                }
                            </p>

                        </div>


                        <div class="visualizar-status">

                            <span class="status-dot"></span>

                            <span class="${obterStatusClasse(status)}">
                                ${escapeHtml(status)}
                            </span>

                        </div>


                        <div class="visualizar-grid">

                            <div class="visualizar-info">

                                <span>ALA</span>

                                <strong>
                                    ${escapeHtml(ala)}
                                </strong>

                            </div>


                            <div class="visualizar-info">

                                <span>CÓDIGO</span>

                                <strong>
                                    ${escapeHtml(
                                        evento.cod_ala ||
                                        '—'
                                    )}
                                </strong>

                            </div>


                            <div class="visualizar-info">

                                <span>INÍCIO</span>

                                <strong>
                                    ${formatarDataBR(inicio)}
                                </strong>

                            </div>


                            <div class="visualizar-info">

                                <span>ENCERRAMENTO</span>

                                <strong>
                                    ${formatarDataBR(fim)}
                                </strong>

                            </div>


                            <div class="visualizar-info">

                                <span>HORÁRIO</span>

                                <strong>
                                    ${
                                        horaIni || horaFimValor
                                            ? `${escapeHtml(horaIni || '--:--')} — ${escapeHtml(horaFimValor || '--:--')}`
                                            : 'Não informado'
                                    }
                                </strong>

                            </div>

                        </div>


                        <div class="visualizar-descricao">

                            <span>DESCRIÇÃO</span>

                            <p>
                                ${
                                    evento.descricao
                                        ? escapeHtml(evento.descricao)
                                        : 'Nenhuma descrição cadastrada.'
                                }
                            </p>

                        </div>

                    </div>

                </div>
            `;
        }


        if (btnEditarVisualizacao) {

            btnEditarVisualizacao.dataset.id =
                eventoId(evento);
        }


        fecharOverlay(modalCalendario);

        fecharOverlay(modalExposicao);

        abrirOverlay(modalVisualizar);
    }


    /*
    |--------------------------------------------------------------------------
    | MODAL DO CALENDÁRIO
    |--------------------------------------------------------------------------
    */

    function abrirModalCalendario(
        listaEventos,
        dataSelecionada
    ) {

        if (!modalCalendario) {
            return;
        }


        dataSelecionadaCalendario =
            dataSelecionada;


        if (calendarioModalTitulo) {

            calendarioModalTitulo.textContent =
                `Exposições em ${formatarDataBR(dataSelecionada)}`;
        }


        if (!calendarioModalLista) {
            return;
        }


        calendarioModalLista.innerHTML = '';


        /*
        | Nenhuma exposição.
        */

        if (
            !listaEventos ||
            listaEventos.length === 0
        ) {

            calendarioModalLista.innerHTML = `

                <div class="calendar-modal-vazio">

                    <i class="bi bi-calendar2-x"></i>

                    <strong>
                        Nenhuma exposição nesta data
                    </strong>

                    <span>
                        Este dia está disponível para um novo cadastro.
                    </span>

                    ${
                        dataSelecionada > hojeISO()
                            ? `
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    id="btnCadastrarDataCalendario"
                                >
                                    <i class="bi bi-plus-lg"></i>
                                    Cadastrar exposição
                                </button>
                            `
                            : `
                                <small class="calendar-data-passada">
                                    Esta data já passou ou é o dia atual e não pode receber novos cadastros.
                                </small>
                            `
                    }

                </div>
            `;


            const btnCadastrar =
                document.getElementById(
                    'btnCadastrarDataCalendario'
                );


            if (btnCadastrar) {

                btnCadastrar.addEventListener(
                    'click',
                    () => {

                        fecharOverlay(
                            modalCalendario
                        );

                        abrirModalCadastro(
                            dataSelecionada
                        );
                    }
                );
            }


            abrirOverlay(modalCalendario);

            return;
        }


        /*
        | Existem exposições.
        */

        listaEventos.forEach(evento => {

            const inicio =
                normalizarData(
                    evento.data_inicio ||
                    evento.inicio
                );

            const fim =
                normalizarData(
                    evento.data_fim ||
                    evento.fim
                );


            const ala =
                evento.nome_ala ||
                evento.ala ||
                evento.cod_ala ||
                'Ala não informada';


            const status =
                evento.status ||
                'Não informado';


            const item =
                document.createElement('div');


            item.className =
                'calendar-modal-exposicao';


            item.dataset.id =
                eventoId(evento);


            item.innerHTML = `

                <div class="calendar-modal-exposicao-imagem">

                    ${
                        evento.imagem_capa
                            ? `
                                <img
                                    src="${escapeHtml(evento.imagem_capa)}"
                                    alt="${escapeHtml(evento.titulo || '')}"
                                >
                            `
                            : `
                                <i class="bi bi-image"></i>
                            `
                    }

                </div>


                <div class="calendar-modal-exposicao-info">

                    <div class="calendar-modal-exposicao-top">

                        <div>

                            <span class="calendar-modal-exposicao-label">
                                ${escapeHtml(ala)}
                            </span>

                            <strong>
                                ${escapeHtml(
                                    evento.titulo ||
                                    'Exposição sem título'
                                )}
                            </strong>

                            <small>
                                ${
                                    evento.autor
                                        ? escapeHtml(evento.autor)
                                        : 'Autor não informado'
                                }
                            </small>

                        </div>


                        <div class="calendar-expo-actions">

                            <button
                                type="button"
                                class="calendar-expo-actions-trigger"
                                aria-label="Ações da exposição"
                                data-menu-calendar
                            >
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>


                            <div
                                class="calendar-expo-actions-menu"
                            >

                                <button
                                    type="button"
                                    data-expo-action="visualizar"
                                    data-id="${escapeHtml(eventoId(evento))}"
                                >
                                    <i class="bi bi-eye"></i>
                                    Visualizar
                                </button>


                                <button
                                    type="button"
                                    data-expo-action="editar"
                                    data-id="${escapeHtml(eventoId(evento))}"
                                >
                                    <i class="bi bi-pencil"></i>
                                    Editar
                                </button>

                            </div>

                        </div>

                    </div>


                    <div class="calendar-modal-exposicao-meta">

                        <span>
                            <i class="bi bi-calendar3"></i>
                            ${formatarDataBR(inicio)}
                            ${
                                fim
                                    ? ` — ${formatarDataBR(fim)}`
                                    : ''
                            }
                        </span>


                        <span>
                            <i class="bi bi-circle-fill"></i>
                            ${escapeHtml(status)}
                        </span>

                    </div>

                </div>
            `;


            calendarioModalLista.appendChild(
                item
            );
        });


        abrirOverlay(modalCalendario);
    }


    /*
    |--------------------------------------------------------------------------
    | CALENDÁRIO
    |--------------------------------------------------------------------------
    */

    function renderizarCalendario() {

        if (!calendarGrid) {
            return;
        }


        calendarGrid.innerHTML = '';


        if (nomeMes) {

            nomeMes.textContent =
                formatarMes(dataAtual);
        }


        const ano =
            dataAtual.getFullYear();

        const mes =
            dataAtual.getMonth();


        const primeiroDia =
            new Date(
                ano,
                mes,
                1
            );


        const ultimoDia =
            new Date(
                ano,
                mes + 1,
                0
            );


        const inicioSemana =
            primeiroDia.getDay();


        const totalDias =
            ultimoDia.getDate();


        const hoje =
            hojeISO();


        for (
            let i = 0;
            i < 42;
            i++
        ) {

            const numeroDia =
                i - inicioSemana + 1;


            const dia =
                document.createElement('button');


            dia.type =
                'button';


            dia.className =
                'calendar-day';


            const dataDia =
                new Date(
                    ano,
                    mes,
                    numeroDia
                );


            const iso =
                dataISO(dataDia);


            if (
                numeroDia < 1 ||
                numeroDia > totalDias
            ) {

                dia.classList.add(
                    'other-month'
                );
            }


            const numero =
                document.createElement(
                    'span'
                );


            numero.className =
                'day-number';


            numero.textContent =
                dataDia.getDate();


            dia.appendChild(
                numero
            );


            if (iso === hoje) {

                dia.classList.add(
                    'today'
                );
            }


            /*
            | Exposições naquele dia.
            */

            const eventosDoDia =
                eventoNoDia(iso);


            if (
                eventosDoDia.length > 0
            ) {

                dia.classList.add(
                    'has-event'
                );


                /*
                | Bolinha.
                */

                const indicador =
                    document.createElement(
                        'span'
                    );


                indicador.className =
                    'event-dot';


                indicador.setAttribute(
                    'aria-hidden',
                    'true'
                );


                dia.appendChild(
                    indicador
                );


                /*
                | Contador.
                */

                if (
                    eventosDoDia.length > 1
                ) {

                    const contador =
                        document.createElement(
                            'span'
                        );


                    contador.className =
                        'event-count';


                    contador.textContent =
                        eventosDoDia.length;


                    dia.appendChild(
                        contador
                    );
                }
            }


            /*
            | Clique no dia.
            */

            dia.addEventListener(
                'click',
                event => {

                    event.stopPropagation();


                    /*
                    | Se existem exposições:
                    | abre o modal com todas.
                    */

                    if (
                        eventosDoDia.length > 0
                    ) {

                        abrirModalCalendario(
                            eventosDoDia,
                            iso
                        );

                        return;
                    }


                    /*
                    | Se não existem:
                    | dia atual e datas passadas não podem cadastrar.
                    */

                    if (
                        iso <= hoje
                    ) {

                        mostrarToast(
                            'Não é possível cadastrar uma exposição no dia atual ou em datas passadas.',
                            'error'
                        );

                        return;
                    }


                    /*
                    | Dia futuro:
                    | abre cadastro.
                    */

                    abrirModalCadastro(
                        iso
                    );
                }
            );


            calendarGrid.appendChild(
                dia
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MENUS DOS 3 PONTINHOS DOS CARDS
    |--------------------------------------------------------------------------
    */

    function criarMenusDosCards() {

        const cards =
            document.querySelectorAll(
                '.expo-card[data-exposicao-id]'
            );


        cards.forEach(card => {

            if (
                card.querySelector(
                    '.expo-card-actions'
                )
            ) {
                return;
            }


            const id =
                card.dataset.exposicaoId;


            if (!id) {
                return;
            }


            const actions =
                document.createElement(
                    'div'
                );


            actions.className =
                'expo-card-actions';


            actions.innerHTML = `

                <button
                    type="button"
                    class="expo-actions-trigger"
                    aria-label="Ações da exposição"
                    data-menu-card
                >
                    <i class="bi bi-three-dots-vertical"></i>
                </button>


                <div class="expo-action-menu">

                    <button
                        type="button"
                        data-expo-action="visualizar"
                        data-id="${escapeHtml(id)}"
                    >
                        <i class="bi bi-eye"></i>
                        Visualizar
                    </button>


                    <button
                        type="button"
                        data-expo-action="editar"
                        data-id="${escapeHtml(id)}"
                    >
                        <i class="bi bi-pencil"></i>
                        Editar
                    </button>

                </div>
            `;


            card.appendChild(
                actions
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | TOAST
    |--------------------------------------------------------------------------
    */

    function mostrarToast(
        mensagem,
        tipo = 'success'
    ) {

        if (!toastMensagem) {
            alert(mensagem);
            return;
        }


        toastMensagem.textContent =
            mensagem;


        toastMensagem.classList.remove(
            'toast-success',
            'toast-error'
        );


        toastMensagem.classList.add(
            tipo === 'error'
                ? 'toast-error'
                : 'toast-success'
        );


        toastMensagem.style.display =
            'flex';


        clearTimeout(
            toastMensagem._timer
        );


        toastMensagem._timer =
            setTimeout(() => {

                toastMensagem.style.display =
                    'none';

            }, 3500);
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DO FORMULÁRIO
    |--------------------------------------------------------------------------
    */

    if (formulario) {

        formulario.addEventListener(
            'submit',
            event => {

                const acao =
                    acaoExposicao
                        ? acaoExposicao.value
                        : 'cadastrar';


                const inicio =
                    dataInicio
                        ? dataInicio.value
                        : '';


                const fim =
                    dataFim
                        ? dataFim.value
                        : '';

                const hoje = hojeISO();


                /*
                | Cadastro e Edição:
                | O início deve ser estritamente a partir de amanhã (maior que hoje).
                */

                if (
                    inicio &&
                    inicio <= hoje
                ) {

                    event.preventDefault();

                    mostrarToast(
                        'A data de início deve ser a partir de amanhã.',
                        'error'
                    );

                    return;
                }


                /*
                | Data final não pode ser menor ou igual a hoje
                */

                if (
                    fim &&
                    fim <= hoje
                ) {

                    event.preventDefault();

                    mostrarToast(
                        'A data de encerramento deve ser a partir de amanhã.',
                        'error'
                    );

                    return;
                }


                /*
                | Data final não pode ser menor
                | que a inicial.
                */

                if (
                    inicio &&
                    fim &&
                    fim < inicio
                ) {

                    event.preventDefault();

                    mostrarToast(
                        'A data de encerramento não pode ser anterior à data de início.',
                        'error'
                    );

                    return;
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LIMITES DE DATA
    |--------------------------------------------------------------------------
    */

    function configurarLimitesData() {

        const amanha =
            amanhaISO();


        if (dataInicio) {

            dataInicio.min =
                amanha;


            dataInicio.addEventListener(
                'change',
                () => {

                    if (
                        dataInicio.value &&
                        dataInicio.value <= hojeISO()
                    ) {

                        dataInicio.value =
                            amanha;
                    }


                    if (dataFim) {

                        dataFim.min =
                            dataInicio.value ||
                            amanha;


                        if (
                            dataFim.value &&
                            dataFim.value <
                            dataInicio.value
                        ) {

                            dataFim.value =
                                dataInicio.value;
                        }
                    }
                }
            );
        }


        if (dataFim) {

            dataFim.min =
                amanha;


            dataFim.addEventListener(
                'change',
                () => {

                    const inicio =
                        dataInicio
                            ? dataInicio.value
                            : '';


                    if (
                        inicio &&
                        dataFim.value < inicio
                    ) {

                        dataFim.value =
                            inicio;
                    }
                }
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BOTÃO NOVA EXPOSIÇÃO
    |--------------------------------------------------------------------------
    */

    if (btnNovaExposicao) {

        btnNovaExposicao.addEventListener(
            'click',
            event => {

                event.preventDefault();
                event.stopPropagation();

                abrirModalCadastro('');
            }
        );
    }


    if (btnCriarExposicao) {

        btnCriarExposicao.addEventListener(
            'click',
            event => {

                event.preventDefault();
                event.stopPropagation();

                abrirModalCadastro('');
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FECHAR MODAIS
    |--------------------------------------------------------------------------
    */

    if (fecharModal) {

        fecharModal.addEventListener(
            'click',
            fecharTodosModais
        );
    }


    if (cancelarModal) {

        cancelarModal.addEventListener(
            'click',
            fecharTodosModais
        );
    }


    if (fecharModalVisualizar) {

        fecharModalVisualizar.addEventListener(
            'click',
            fecharTodosModais
        );
    }


    if (fecharModalCalendario) {

        fecharModalCalendario.addEventListener(
            'click',
            fecharTodosModais
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EDITAR A PARTIR DA VISUALIZAÇÃO
    |--------------------------------------------------------------------------
    */

    if (btnEditarVisualizacao) {

        btnEditarVisualizacao.addEventListener(
            'click',
            () => {

                const id =
                    btnEditarVisualizacao.dataset.id;


                const evento =
                    encontrarExposicao(id);


                if (!evento) {
                    return;
                }


                abrirModalEdicao(
                    evento
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NOVA EXPOSIÇÃO PELO MODAL DO CALENDÁRIO
    |--------------------------------------------------------------------------
    */

    if (btnNovaCalendario) {

        btnNovaCalendario.addEventListener(
            'click',
            () => {

                const data =
                    dataSelecionadaCalendario ||
                    '';


                fecharOverlay(
                    modalCalendario
                );


                abrirModalCadastro(
                    data
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CLIQUES GERAIS
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        event => {

            /*
            | Ação Visualizar / Editar
            */

            const action =
                event.target.closest(
                    '[data-expo-action]'
                );


            if (action) {

                event.preventDefault();
                event.stopPropagation();


                const id =
                    action.dataset.id;


                const tipo =
                    action.dataset.expoAction;


                const evento =
                    encontrarExposicao(id);


                /*
                | Fecha menus.
                */

                document
                    .querySelectorAll(
                        '.expo-action-menu.aberto, .calendar-expo-actions-menu.aberto'
                    )
                    .forEach(menu => {

                        menu.classList.remove(
                            'aberto'
                        );
                    });


                if (!evento) {
                    return;
                }


                if (
                    tipo === 'visualizar'
                ) {

                    abrirModalVisualizacao(
                        evento
                    );

                    return;
                }


                if (
                    tipo === 'editar'
                ) {

                    abrirModalEdicao(
                        evento
                    );

                    return;
                }
            }


            /*
            | Botão de três pontos dos cards.
            */

            const menuCard =
                event.target.closest(
                    '[data-menu-card]'
                );


            if (menuCard) {

                event.preventDefault();
                event.stopPropagation();


                const menu =
                    menuCard.parentElement
                        ? menuCard.parentElement.querySelector(
                            '.expo-action-menu'
                        )
                        : null;


                document
                    .querySelectorAll(
                        '.expo-action-menu.aberto'
                    )
                    .forEach(outro => {

                        if (outro !== menu) {

                            outro.classList.remove(
                                'aberto'
                            );
                        }
                    });


                if (menu) {

                    menu.classList.toggle(
                        'aberto'
                    );
                }


                return;
            }


            /*
            | Botão de três pontos do calendário.
            */

            const menuCalendario =
                event.target.closest(
                    '[data-menu-calendar]'
                );


            if (menuCalendario) {

                event.preventDefault();
                event.stopPropagation();


                const menu =
                    menuCalendario.parentElement
                        ? menuCalendario.parentElement.querySelector(
                            '.calendar-expo-actions-menu'
                        )
                        : null;


                document
                    .querySelectorAll(
                        '.calendar-expo-actions-menu.aberto'
                    )
                    .forEach(outro => {

                        if (outro !== menu) {

                            outro.classList.remove(
                                'aberto'
                            );
                        }
                    });


                if (menu) {

                    menu.classList.toggle(
                        'aberto'
                    );
                }


                return;
            }


            /*
            | Clique fora fecha menus.
            */

            if (
                !event.target.closest(
                    '.expo-card-actions'
                )
            ) {

                document
                    .querySelectorAll(
                        '.expo-action-menu.aberto'
                    )
                    .forEach(menu => {

                        menu.classList.remove(
                            'aberto'
                        );
                    });
            }


            if (
                !event.target.closest(
                    '.calendar-expo-actions'
                )
            ) {

                document
                    .querySelectorAll(
                        '.calendar-expo-actions-menu.aberto'
                    )
                    .forEach(menu => {

                        menu.classList.remove(
                            'aberto'
                        );
                    });
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLIQUE FORA DOS MODAIS
    |--------------------------------------------------------------------------
    */

    if (modalExposicao) {

        modalExposicao.addEventListener(
            'click',
            event => {

                if (
                    event.target ===
                    modalExposicao
                ) {

                    fecharTodosModais();
                }
            }
        );
    }


    if (modalVisualizar) {

        modalVisualizar.addEventListener(
            'click',
            event => {

                if (
                    event.target ===
                    modalVisualizar
                ) {

                    fecharTodosModais();
                }
            }
        );
    }


    if (modalCalendario) {

        modalCalendario.addEventListener(
            'click',
            event => {

                if (
                    event.target ===
                    modalCalendario
                ) {

                    fecharTodosModais();
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NAVEGAÇÃO DO CALENDÁRIO
    |--------------------------------------------------------------------------
    */

    function mudarMes(delta) {

        dataAtual =
            new Date(
                dataAtual.getFullYear(),
                dataAtual.getMonth() + delta,
                1
            );


        renderizarCalendario();
    }


    if (mesAnterior) {

        mesAnterior.addEventListener(
            'click',
            () => mudarMes(-1)
        );
    }


    if (mesProximo) {

        mesProximo.addEventListener(
            'click',
            () => mudarMes(1)
        );
    }


    if (mesAnteriorMobile) {

        mesAnteriorMobile.addEventListener(
            'click',
            () => mudarMes(-1)
        );
    }


    if (mesProximoMobile) {

        mesProximoMobile.addEventListener(
            'click',
            () => mudarMes(1)
        );
    }


    if (mesHoje) {

        mesHoje.addEventListener(
            'click',
            () => {

                dataAtual =
                    new Date();


                renderizarCalendario();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ESC
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        event => {

            if (
                event.key === 'Escape'
            ) {

                fecharTodosModais();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | INICIALIZAÇÃO
    |--------------------------------------------------------------------------
    */

    configurarLimitesData();

    criarMenusDosCards();

    renderizarCalendario();

});