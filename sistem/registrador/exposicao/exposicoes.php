<?php

$itemMenu = "exposicoes";
require_once __DIR__ . "/../../../backend/Config/conexao.php";
require_once __DIR__ . "/../../../functions/Atividade/registrar_atividade.php";


$tituloPagina = "MuseuArt - Exposições";

$cssPagina = "../../../assets/css/pages/exposicoes.css";

$menuEspecifico ="../../../components/layouts/private/menus/registrador.php";
$pagina ="../../../components/pages/exposicoes.php";

include "../../../components/layouts/private/layout.php"; ?>
