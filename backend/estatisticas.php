<?php

/** @var mysqli $strcon */


$sql = "SELECT COUNT(*) AS total FROM item";
$resultado = mysqli_query($strcon, $sql);
$totalItens = mysqli_fetch_assoc($resultado)['total'];


$sql = "SELECT COUNT(*) AS total FROM funcionario";
$resultado = mysqli_query($strcon, $sql);
$totalFuncionarios = mysqli_fetch_assoc($resultado)['total'];


$sql = "SELECT COUNT(*) AS total FROM item
WHERE status_obra = 'Em exposição'
";

$resultado = mysqli_query($strcon, $sql);
$totalExposicoes = mysqli_fetch_assoc($resultado)['total'];


$sql = "SELECT COUNT(*) AS total FROM item
WHERE status_obra = 'Em Restauração'
";

$resultado = mysqli_query($strcon, $sql);
$totalRestauracoes = mysqli_fetch_assoc($resultado)['total'];