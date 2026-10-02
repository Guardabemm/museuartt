/*
=========================================================
    MUSEUART - CONFIGURAÇÃO GLOBAL DOS FORMULÁRIOS
=========================================================
    Este arquivo remove sugestões, autocomplete,
    correção automática e preenchimentos automáticos
    dos campos do sistema.
=========================================================
*/

(function () {
    "use strict";

    function configurarElemento(elemento) {
        if (!elemento || !elemento.tagName) {
            return;
        }

        const tag = elemento.tagName;

        /*
        -------------------------------------------------
            FORMULÁRIOS
        -------------------------------------------------
        */
        if (tag === "FORM") {
            elemento.setAttribute("autocomplete", "off");
            elemento.setAttribute("rel", "nofollow");
        }

        /*
        -------------------------------------------------
            INPUTS
        -------------------------------------------------
        */
        if (tag === "INPUT") {
            const tipo = (elemento.getAttribute("type") || "text").toLowerCase();

            // Desativa correções e sugestões de texto nativas
            elemento.setAttribute("autocorrect", "off");
            elemento.setAttribute("autocapitalize", "none");
            elemento.setAttribute("spellcheck", "false");

            // 'new-password' e valores desconhecidos forçam o navegador
            // a ignorar o histórico de preenchimento e senhas salvas
            if (tipo === "password" || tipo === "text" || tipo === "email") {
                elemento.setAttribute("autocomplete", "new-password");
            } else {
                elemento.setAttribute("autocomplete", "chrome-off");
            }
        }

        /*
        -------------------------------------------------
            TEXTAREAS
        -------------------------------------------------
        */
        if (tag === "TEXTAREA") {
            elemento.setAttribute("autocomplete", "off");
            elemento.setAttribute("autocorrect", "off");
            elemento.setAttribute("autocapitalize", "none");
            elemento.setAttribute("spellcheck", "false");
        }

        /*
        -------------------------------------------------
            SELECTS
        -------------------------------------------------
        */
        if (tag === "SELECT") {
            elemento.setAttribute("autocomplete", "off");
        }
    }

    /*
    =====================================================
        CONFIGURA TODOS OS ELEMENTOS EXISTENTES
    =====================================================
    */
    function configurarTodos() {
        document
            .querySelectorAll("form, input, textarea, select")
            .forEach(configurarElemento);
    }

    /*
    =====================================================
        OBSERVADOR DE ELEMENTOS NOVOS (DOM DINÂMICO)
    =====================================================
    */
    const observador = new MutationObserver(function (mutacoes) {
        mutacoes.forEach(function (mutacao) {
            if (
                mutacao.type !== "childList" ||
                !mutacao.addedNodes.length
            ) {
                return;
            }

            mutacao.addedNodes.forEach(function (node) {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }

                // Configura o próprio elemento se for um campo/form
                configurarElemento(node);

                // Configura os elementos filhos dentro dele
                node.querySelectorAll(
                    "form, input, textarea, select"
                ).forEach(configurarElemento);
            });
        });
    });

    /*
    =====================================================
        INICIALIZAÇÃO
    =====================================================
    */
    function iniciar() {
        configurarTodos();

        observador.observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }

    /*
    =====================================================
        EXECUTA QUANDO O DOM ESTIVER PRONTO
    =====================================================
    */
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", iniciar);
    } else {
        iniciar();
    }
})();