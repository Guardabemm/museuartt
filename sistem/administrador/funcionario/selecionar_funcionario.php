<?php

$acao = $_GET['acao'] ?? '';

if ($acao === 'editar') {

    header(
        'Location: funcionarios.php?selecionar=editar'
    );

    exit();

}

if ($acao === 'excluir') {

    header(
        'Location: funcionarios.php?selecionar=excluir'
    );

    exit();

}

header('Location: funcionarios.php');

exit();