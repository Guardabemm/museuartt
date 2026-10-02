<?php

$itemMenu = "funcionariolist";

require_once __DIR__ . "/../../../backend/Config/conexao.php";
require_once __DIR__ . "/../../../functions/Atividade/registrar_atividade.php";


$tituloPagina = "MuseuArt - Funcionários";

$cssPagina = "../../../assets/css/pages/funcionarios.css";
$menuCrud ="../../../components/layouts/private/menus/crud_funcionarios.php";
$menuEspecifico ="../../../components/layouts/private/menus/administrador.php";
$scriptPagina = "../../../assets/js/funcionarios.js";
$pagina ="../../../components/pages/funcionarios.php";

$selecionar = $_GET['selecionar'] ?? '';

$modosPermitidos = [
    'editar',
    'excluir',
    'atualizar'
];

if (!in_array($selecionar, $modosPermitidos, true)) {
    $selecionar = '';
}


$toast = null;

if (isset($_GET['cadastro'])) {

    if ($_GET['cadastro'] === 'sucesso') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            mostrarToast(
                "sucesso",
                "Funcionário cadastrado",
                "O funcionário foi cadastrado com sucesso."
            );
        });
        </script>';
    }

    if ($_GET['cadastro'] === 'erro') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            mostrarToast(
                "erro",
                "Erro no cadastro",
                "Não foi possível cadastrar o funcionário."
            );
        });
        </script>';
    }
}

if (isset($_GET['edicao'])) {

    if ($_GET['edicao'] === 'sucesso') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            mostrarToast(
                "sucesso",
                "Funcionário atualizado",
                "Os dados do funcionário foram atualizados com sucesso."
            );
        });
        </script>';
    }

    if ($_GET['edicao'] === 'erro') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            mostrarToast(
                "erro",
                "Erro na edição",
                "Não foi possível atualizar os dados do funcionário."
            );
        });
        </script>';
    }
}

if (isset($_GET['exclusao'])) {

    if ($_GET['exclusao'] === 'sucesso') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            mostrarToast(
                "sucesso",
                "Funcionário excluído",
                "O funcionário foi excluído com sucesso."
            );
        });
        </script>';
    }

    if ($_GET['exclusao'] === 'erro') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            mostrarToast(
                "erro",
                "Erro na exclusão",
                "Não foi possível excluir o funcionário."
            );
        });
        </script>';
    }
}


include "../../../components/layouts/private/layout.php"; ?>

<script>

window.modoSelecaoFuncionario =
    <?= json_encode(
        $selecionar,
        JSON_UNESCAPED_UNICODE
    ) ?>;

</script>

</script>



