<?php

require_once "../backend/Config/conexao.php";

echo "<h2 style='color:green'> Conexão realizada com sucesso!</h2>";

$sql = "SELECT NOW() AS data_hora";
$resultado = mysqli_query($strcon, $sql);

if ($resultado) {

    $dados = mysqli_fetch_assoc($resultado);

    echo "<p>Banco respondeu corretamente.</p>";
    echo "<p>Data e hora do servidor: <strong>{$dados['data_hora']}</strong></p>";

} else {

    echo "<p style='color:red'>Erro ao executar consulta: " . mysqli_error($strcon) . "</p>";

}