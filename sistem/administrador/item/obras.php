<?php
$baseUrl = '/SistemaMuseuArt.GuardaBem/sistem';
$itemMenu = "obras";

require_once __DIR__ . "/../../../backend/Config/conexao.php";
require_once __DIR__ . "/../../../functions/Atividade/registrar_atividade.php";

$tituloPagina = "MuseuArt - Obras";

$cssPagina = "../../../assets/css/pages/itens.css";

$menuCrud = "../../../components/layouts/private/menus/crud_obras.php";

$menuEspecifico = "../../../components/layouts/private/menus/registrador.php";

$pagina = "../../../components/pages/itens.php";




$selecionar = $_GET['selecionar'] ?? '';

$modosPermitidos = [
    'editar',
    'excluir',
    'atualizar'
];

if (!in_array($selecionar, $modosPermitidos, true)) {
    $selecionar = '';
}




if (isset($_GET['cadastro'])) {

    if ($_GET['cadastro'] === 'sucesso') {

        echo '
        <script>
        document.addEventListener("DOMContentLoaded", function () {

            if (typeof mostrarToast === "function") {

                mostrarToast(
                    "sucesso",
                    "Obra cadastrada",
                    "A obra foi cadastrada com sucesso."
                );

            }

        });
        </script>';

    }

    if ($_GET['cadastro'] === 'erro') {

        echo '
        <script>
        document.addEventListener("DOMContentLoaded", function () {

            if (typeof mostrarToast === "function") {

                mostrarToast(
                    "erro",
                    "Erro no cadastro",
                    "Não foi possível cadastrar a obra."
                );

            }

        });
        </script>';

    }
}


if (isset($_GET['edicao'])) {

    if ($_GET['edicao'] === 'sucesso') {

        echo '
        <script>
        document.addEventListener("DOMContentLoaded", function () {

            if (typeof mostrarToast === "function") {

                mostrarToast(
                    "sucesso",
                    "Obra atualizada",
                    "Os dados da obra foram atualizados com sucesso."
                );

            }

        });
        </script>';

    }

    if ($_GET['edicao'] === 'erro') {

        echo '
        <script>
        document.addEventListener("DOMContentLoaded", function () {

            if (typeof mostrarToast === "function") {

                mostrarToast(
                    "erro",
                    "Erro na edição",
                    "Não foi possível atualizar os dados da obra."
                );

            }

        });
        </script>';

    }
}
if (isset($_GET['arquivamento'])) {
    if ($_GET['arquivamento'] === 'sucesso') {
        echo '
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                if (typeof mostrarToast === "function") {
                    mostrarToast(
                        "sucesso",
                        "Obra arquivada",
                        "A obra foi arquivada com sucesso."
                    );
                }
            });
        </script>';
    }
    
    if ($_GET['arquivamento'] === 'erro') {
        echo '
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                if (typeof mostrarToast === "function") {
                    mostrarToast(
                        "erro",
                        "Erro ao arquivar",
                        "Não foi possível arquivar a obra."
                    );
                }
            });
        </script>';
    }
}

include "../../../components/layouts/private/layout.php";

?>

<script>

    window.modoSelecaoObra = <?= json_encode(
        $selecionar,
        JSON_UNESCAPED_UNICODE
    ) ?>;

</script>
