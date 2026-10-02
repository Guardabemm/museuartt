<?php
require_once __DIR__ . "/../functions/Atividade/registrar_atividade.php";

function getAtividadesRecentes($limite = 5)
{
    global $strcon;

    $sql = "
        SELECT 
            a.id,
            a.acao,
            a.descricao,
            a.data_hora,
            a.icone,
            a.cor_icone,
            a.tipo,
            f.nome AS nome_funcionario,
            f.foto_usuario,
            i.nome_item AS nome_item,
            al.nome AS nome_ala
        FROM atividade_recente a
        LEFT JOIN funcionario f ON a.id_funcionario = f.id_funcionario
        LEFT JOIN item i ON a.id_item = i.cod_item
        LEFT JOIN alocacao al ON a.id_alocacao = al.cod_ala
        ORDER BY a.data_hora DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($strcon, $sql);
    mysqli_stmt_bind_param($stmt, "i", $limite);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $atividades = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['tempo_formatado'] = formatarTempo($row['data_hora']);
        $atividades[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $atividades;
}
?>