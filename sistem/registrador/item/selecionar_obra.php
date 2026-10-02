<?php

$acao = $_GET['acao'] ?? '';
$cod_item = $_GET['cod_item'] ?? '';

if ($acao === 'editar') {
    
    if (empty($cod_item) || !is_numeric($cod_item)) {
        header('Location: obras.php');
        exit();
    }
    
    
    header('Location: editar_obra.php?cod_item=' . $cod_item . '&origem=registrador');
    exit();
}

if ($acao === 'excluir') {
    if (empty($cod_item) || !is_numeric($cod_item)) {
        header('Location: obras.php');
        exit();
    }
    
    
    header('Location: excluir_obra.php?cod_item=' . $cod_item);
    exit();
}

header('Location: obras.php');
exit();