<?php
function formatarTempo($data_hora)
{
    if (empty($data_hora))
        return 'agora';

    $agora = new DateTime();
    $data = new DateTime($data_hora);
    $diferenca = $agora->diff($data);

    if ($diferenca->y > 0)
        return $diferenca->y . ' ano' . ($diferenca->y > 1 ? 's' : '');
    if ($diferenca->m > 0)
        return $diferenca->m . ' mês' . ($diferenca->m > 1 ? 'es' : '');
    if ($diferenca->d > 0) {
        if ($diferenca->d == 1)
            return 'ontem';
        return $diferenca->d . ' dias';
    }
    if ($diferenca->h > 0)
        return $diferenca->h . ' hora' . ($diferenca->h > 1 ? 's' : '');
    if ($diferenca->i > 0)
        return $diferenca->i . ' minuto' . ($diferenca->i > 1 ? 's' : '');
    return 'agora mesmo';
}




function registrarAtividade(
    $id_funcionario,
    $acao,
    $descricao = '',
    $tipo = 'geral',
    $id_item = null,
    $id_alocacao = null,
    $id_registro = null
) {
    global $strcon;

    $icones = [
        'cadastro' => 'bi-plus-circle',
        'alteracao' => 'bi-pencil-square',
        'exclusao' => 'bi-trash',
        'restauracao' => 'bi-tools',
        'alocacao' => 'bi-plus-circle',
        'exposicao' => 'bi-easel',
        'login' => 'bi-box-arrow-in-right',
        'logout' => 'bi-box-arrow-right',
        'geral' => 'bi-clock'
    ];

    $cores = [
        'cadastro' => '#22c55e',
        'alteracao' => '#f59e0b',
        'exclusao' => '#ef4444',
        'restauracao' => '#60a5fa',
        'alocacao' => '#818cf8',
        'exposicao' => '#8b5cf6',
        'login' => '#22c55e',
        'logout' => '#ef4444',
        'geral' => '#6b7280'
    ];

    $icone = $icones[$tipo] ?? 'bi-clock';
    $cor = $cores[$tipo] ?? '#6b7280';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $sql = "INSERT INTO atividade_recente 
            (id_funcionario, id_item, id_alocacao, id_registro, 
             acao, descricao, tipo, icone, cor_icone, ip_usuario) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($strcon, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "iiiissssss",
        $id_funcionario,
        $id_item,
        $id_alocacao,
        $id_registro,
        $acao,
        $descricao,
        $tipo,
        $icone,
        $cor,
        $ip
    );

    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}
?>