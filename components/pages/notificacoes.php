<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . "/../../backend/Config/conexao.php";


if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../../public/index.php");
    exit();
}


$id_funcionario = $_SESSION['id_funcionario'];




/*
|--------------------------------------------------------------------------
| BUSCAR FUNCIONÁRIO LOGADO
|--------------------------------------------------------------------------
*/


$sql = "
    SELECT
        id_funcionario,
        nome,
        email,
        cpf,
        login,
        telefone,
        endereco,
        dt_vinculo,
        dt_arquivo,
        tipo,
        foto_usuario
    FROM funcionario
    WHERE id_funcionario = ?
";


$stmt = mysqli_prepare($strcon, $sql);


if (!$stmt) {
    die("Erro ao preparar consulta: " . mysqli_error($strcon));
}


mysqli_stmt_bind_param($stmt, "i", $id_funcionario);


mysqli_stmt_execute($stmt);


$resultado = mysqli_stmt_get_result($stmt);


$funcionario = mysqli_fetch_assoc($resultado);


mysqli_stmt_close($stmt);




if (!$funcionario) {
    die("Funcionário não encontrado.");
}




/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DO CPF
|--------------------------------------------------------------------------
*/


$cpf = $funcionario['cpf'];


if (!empty($cpf) && strlen($cpf) == 11) {


    $cpf = substr($cpf, 0, 3) . "." .
           substr($cpf, 3, 3) . "." .
           substr($cpf, 6, 3) . "-" .
           substr($cpf, 9, 2);
}




/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DO TELEFONE
|--------------------------------------------------------------------------
*/


$telefone = $funcionario['telefone'];


if (!empty($telefone) && strlen($telefone) == 11) {


    $telefone = "(" . substr($telefone, 0, 2) . ") " .
                substr($telefone, 2, 5) . "-" .
                substr($telefone, 7, 4);
}




/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DAS DATAS
|--------------------------------------------------------------------------
*/


$dt_vinculo = '';


if (!empty($funcionario['dt_vinculo'])) {


    $dt_vinculo = date(
        'd/m/Y H:i',
        strtotime($funcionario['dt_vinculo'])
    );
}




$dt_arquivo = '';


if (!empty($funcionario['dt_arquivo'])) {


    $dt_arquivo = date(
        'd/m/Y H:i',
        strtotime($funcionario['dt_arquivo'])
    );
}




/*
|--------------------------------------------------------------------------
| FOTO
|--------------------------------------------------------------------------
*/


$foto = !empty($funcionario['foto_usuario'])
    ? $funcionario['foto_usuario']
    : '/assets/img/usuarios/avatarpadrao.jpg';


if (strpos($foto, '/SistemaMuseuArt.GuardaBem/') !== 0) {


    $foto = '/SistemaMuseuArt.GuardaBem/' . ltrim($foto, '/');
}


?>




<!DOCTYPE html>


<html lang="pt-BR">


<head>


    <meta charset="UTF-8">


    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <title>Meu Perfil</title>


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link
        rel="stylesheet" href="../../assets/css/pages/notificacoes.css"
    >


</head>




<body>




<main class="perfil-pagina">


    <button type="button" class="btn-voltar" onclick="history.back()" title="Voltar">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
    </button>




    <div class="perfil-titulo">


        <h1>Notificações</h1>


        <p>
            Visualize informações de uso do sistema.
        </p>


    </div>




    <section class="perfil-card">




        <div class="perfil-card-topo">




            <div class="perfil-foto">


                <img
                    src="<?= htmlspecialchars($foto) ?>"
                    alt="<?= htmlspecialchars($funcionario['nome']) ?>"
                >


            </div>




            <div class="perfil-identificacao">


                <h2>
                    <?= htmlspecialchars($funcionario['nome']) ?>
                </h2>


                <span>
                    <?= htmlspecialchars($funcionario['tipo']) ?>
                </span>


            </div>


        </div>


        <section class="nova-aba-area">




        <div class="section-header">
            <div>
                <i class="bi bi-clock-history"></i>
                <h2>Atividades Recentes no Sistema</h2>
            </div>
        </div>




        <div class="itens-lista atividades-lista">




            <?php if (!empty($atividades)): ?>




                <?php foreach ($atividades as $atividade): ?>




                    <div class="item atividade-item">




                     
                        <div class="item-imagem atividade-icone"
                            style="background: <?= htmlspecialchars($atividade['cor_icone']) ?>25;">




                            <?php if (!empty($atividade['foto_usuario'])): ?>
                                <img src="../../assets/img/funcionarios/<?= htmlspecialchars($atividade['foto_usuario']) ?>"
                                    alt="<?= htmlspecialchars($atividade['nome_funcionario']) ?>">
                            <?php else: ?>
                                <i class="bi <?= htmlspecialchars($atividade['icone']) ?>"
                                    style="color: <?= htmlspecialchars($atividade['cor_icone']) ?>; font-size: 22px;"></i>
                            <?php endif; ?>




                        </div>




                       
                        <div class="item-info">
                            <h3>
                                <?= htmlspecialchars($atividade['nome_funcionario'] ?? 'Sistema') ?>
                            </h3>
                            <span>
                                <?= htmlspecialchars($atividade['acao']) ?>




                                <?php if (!empty($atividade['descricao'])): ?>
                                    <strong style="color: <?= htmlspecialchars($atividade['cor_icone']) ?>;">
                                        <?= htmlspecialchars($atividade['descricao']) ?>
                                    </strong>
                                <?php endif; ?>




                                <?php if (!empty($atividade['nome_item'])): ?>
                                    <small style="color: #6b7280; font-size: 10px;">
                                        (<?= htmlspecialchars($atividade['nome_item']) ?>)
                                    </small>
                                <?php endif; ?>
                            </span>
                        </div>








                        <span class="status atividade-tempo">
                            <i class="bi bi-clock" style="font-size: 10px; margin-right: 4px;"></i>
                            há <?= htmlspecialchars($atividade['tempo_formatado']) ?>
                        </span>




                    </div>




                <?php endforeach; ?>




            <?php else: ?>




                <div class="atividade-vazia">
                    <i class="bi bi-inbox"></i>
                    <p>Nenhuma atividade recente</p>
                    <small style="color: #6b7280;">As atividades aparecerão aqui</small>
                </div>




            <?php endif; ?>




        </div>




    </section>




    </section>




</main>




</body>


</html>
