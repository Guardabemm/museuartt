<?php

$itemMenu = "alas";

require_once __DIR__ . "/../../../backend/Config/conexao.php";
require_once __DIR__ . "/../../../functions/Atividade/registrar_atividade.php";

$tituloPagina = "MuseuArt - Alas";

$cssPagina = "../../../assets/css/pages/ala.css";

$menuEspecifico = "../../../components/layouts/private/menus/manipulador.php";

$pagina = "../../../components/pages/ala.php";

if (isset($_GET['edicao'])) {

    if ($_GET['edicao'] === 'sucesso') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            mostrarToast(
                "sucesso",
                "Ala atualizada",
                "Os dados da ala foram atualizados com sucesso."
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
                "Não foi possível atualizar os dados da ala."
            );
        });
        </script>';
    }
}

include "../../../components/layouts/private/layout.php";
?>
