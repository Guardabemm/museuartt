<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../../backend/Config/conexao.php";




if (!isset($_SESSION['id_funcionario'])) {

    header("Location: ../../../index.php");

    exit();
}




$idFuncionario = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idFuncionario) {

    header("Location: funcionarioslist.php");

    exit();
}




if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';




    if ($acao === 'cancelar') {

        header(
            "Location: funcionarioslist.php"
        );

        exit();
    }




    if ($acao === 'arquivar') {

        $sqlArquivar = "
            UPDATE funcionario
            SET dt_arquivo = NOW()
            WHERE id_funcionario = ?
            AND dt_arquivo IS NULL
        ";


        $stmtArquivar = mysqli_prepare(
            $strcon,
            $sqlArquivar
        );


        if (!$stmtArquivar) {

            header(
                "Location: excluir_funcionario.php?id=" .
                $idFuncionario .
                "&erro=" .
                urlencode(
                    "Não foi possível excluir."
                )
            );

            exit();
        }


        mysqli_stmt_bind_param(
            $stmtArquivar,
            "i",
            $idFuncionario
        );


        if (!mysqli_stmt_execute($stmtArquivar)) {

            mysqli_stmt_close($stmtArquivar);


            header(
                "Location: excluir_funcionario.php?id=" .
                $idFuncionario .
                "&erro=" .
                urlencode(
                    "Não foi possível excluir o funcionário."
                )
            );

            exit();
        }


        $linhasAfetadas =
            mysqli_stmt_affected_rows(
                $stmtArquivar
            );


        mysqli_stmt_close(
            $stmtArquivar
        );




        if ($linhasAfetadas <= 0) {

            header(
                "Location: excluir_funcionario.php?id=" .
                $idFuncionario .
                "&erro=" .
                urlencode(
                    "Este funcionário já está arquivado ou não foi encontrado."
                )
            );

            exit();
        }




        header(
            "Location: funcionarioslist.php?arquivamento=sucesso"
        );

        exit();
    }
}




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
    LIMIT 1
";


$stmt = mysqli_prepare(
    $strcon,
    $sql
);


if (!$stmt) {

    die(
        "Erro ao preparar consulta."
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $idFuncionario
);


mysqli_stmt_execute(
    $stmt
);


$resultado =
    mysqli_stmt_get_result(
        $stmt
    );


$funcionario =
    mysqli_fetch_assoc(
        $resultado
    );


mysqli_stmt_close(
    $stmt
);




if (!$funcionario) {

    die(
        "Funcionário não encontrado."
    );
}




if (!empty($funcionario['dt_arquivo'])) {

    header(
        "Location: funcionarioslist.php?erro=" .
        urlencode(
            "Este funcionário já foi excluido."
        )
    );

    exit();
}




$nome =
    $funcionario['nome'] ?? '';

$email =
    $funcionario['email'] ?? '';

$cpf =
    $funcionario['cpf'] ?? '';

$login =
    $funcionario['login'] ?? '';

$telefone =
    $funcionario['telefone'] ?? '';

$endereco =
    $funcionario['endereco'] ?? '';

$tipo =
    $funcionario['tipo'] ?? '';

$dtVinculo =
    $funcionario['dt_vinculo'] ?? '';

$fotoAtual =
    $funcionario['foto_usuario'] ?? '';




$dtVinculoFormatada = '';

if (!empty($dtVinculo)) {

    $timestamp =
        strtotime($dtVinculo);

    if ($timestamp !== false) {

        $dtVinculoFormatada =
            date(
                'd/m/Y H:i',
                $timestamp
            );
    }
}




$fotoUrl = '';

if (!empty($fotoAtual)) {

    $fotoUrl =
        '/SistemaMuseuArt.GuardaBem/' .
        ltrim(
            $fotoAtual,
            '/'
        );
}




$mensagemErro =
    $_GET['erro'] ?? '';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Arquivar funcionário
    </title>


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel=" stylesheet" href="../../../assets/css/design/CRUDS/excluir_funcionario.css">

</head>


<body>


    <div class="excluir-overlay">


        <div class="excluir-modal">


       

            <header class="excluir-header">


                <div class="titulo-container">


                    <div class="icone-excluir">

                        <i class="bi bi-person-x"></i>

                    </div>


                    <div>

                        <h1>
                            Excluir funcionário
                        </h1>

                        <p>
                            Confira os dados antes de continuar.
                        </p>

                    </div>


                </div>


                <button type="button" class="btn-fechar" title="Voltar"
                    onclick="window.location.href='funcionarioslist.php'">

                    <i class="bi bi-x-lg"></i>

                </button>


            </header>



            <main class="excluir-conteudo">


                <?php if ($mensagemErro !== ''): ?>

                    <div class="mensagem-erro">

                        <i class="bi bi-exclamation-circle"></i>

                        <span>

                            <?= htmlspecialchars($mensagemErro) ?>

                        </span>

                    </div>

                <?php endif; ?>


                <section class="funcionario-card">


                 

                    <div class="foto-container">

                        <?php if (!empty($fotoUrl)): ?>

                            <img src="<?= htmlspecialchars($fotoUrl) ?>" alt="Foto de <?= htmlspecialchars($nome) ?>">

                        <?php else: ?>

                            <div class="foto-placeholder">

                                <i class="bi bi-person"></i>

                            </div>

                        <?php endif; ?>

                    </div>



                   
                    <div class="dados-funcionario">


                        <div class="nome-funcionario">

                            <span>
                                Funcionário
                            </span>

                            <h2>
                                <?= htmlspecialchars($nome) ?>
                            </h2>

                        </div>


                        <div class="linha-dados">


                            <div class="dado">

                                <span>
                                    ID
                                </span>

                                <strong>
                                    #<?= $idFuncionario ?>
                                </strong>

                            </div>


                            <div class="dado">

                                <span>
                                    Tipo
                                </span>

                                <strong>
                                    <?= htmlspecialchars($tipo) ?>
                                </strong>

                            </div>


                            <div class="dado">

                                <span>
                                    Login
                                </span>

                                <strong>
                                    <?= htmlspecialchars($login) ?>
                                </strong>

                            </div>


                        </div>


                    </div>


                </section>



               

                <section class="dados-card">


                    <div class="dados-card-header">

                        <i class="bi bi-person-vcard"></i>

                        <div>

                            <h3>
                                Dados cadastrais
                            </h3>

                            <p>
                                Informações atualmente registradas.
                            </p>

                        </div>

                    </div>



                    <div class="dados-grid">


                        <div class="campo">

                            <span>
                                E-mail
                            </span>

                            <strong>
                                <?= htmlspecialchars($email) ?>
                            </strong>

                        </div>


                        <div class="campo">

                            <span>
                                CPF
                            </span>

                            <strong>
                                <?= htmlspecialchars($cpf) ?>
                            </strong>

                        </div>


                        <div class="campo">

                            <span>
                                Telefone
                            </span>

                            <strong>
                                <?= htmlspecialchars($telefone) ?>
                            </strong>

                        </div>


                        <div class="campo">

                            <span>
                                Data de vínculo
                            </span>

                            <strong>
                                <?= htmlspecialchars($dtVinculoFormatada) ?>
                            </strong>

                        </div>


                        <div class="campo campo-grande">

                            <span>
                                Endereço
                            </span>

                            <strong>
                                <?= htmlspecialchars($endereco) ?>
                            </strong>

                        </div>


                    </div>


                </section>


                <section class="aviso-arquivamento">


                    <div class="aviso-icone">

                        <i class="bi bi-archive"></i>

                    </div>


                    <div>

                        <strong>
                            O funcionário será excluido
                        </strong>

                        <p>

                            O funcionário não será apagado
                            permanentemente do banco de dados.

                            Ele receberá a data de arquivo e deixará
                            de aparecer na lista de funcionários ativos.

                            Os dados poderão continuar disponíveis
                            na área de funcionários arquivados.

                        </p>

                    </div>


                </section>



                <div class="confirmacao">


                    <i class="bi bi-question-circle"></i>


                    <div>

                        <h3>
                            Você deseja apagar este funcionário?
                        </h3>

                        <p>
                            Ao confirmar, <strong><?= htmlspecialchars($nome) ?></strong>
                            será excluido.
                        </p>

                    </div>


                </div>


            </main>



            <footer class="excluir-footer">


                <form method="POST" action="excluir_funcionario.php?id=<?= $idFuncionario ?>">

                    <input type="hidden" name="acao" value="cancelar">

                    <button type="submit" class="btn-cancelar">

                        <i class="bi bi-arrow-left"></i>

                        Não, voltar

                    </button>

                </form>



                <form method="POST" action="excluir_funcionario.php?id=<?= $idFuncionario ?>" id="formArquivar">

                    <input type="hidden" name="acao" value="arquivar">

                    <button type="submit" class="btn-excluir">

                        <i class="bi bi-archive"></i>

                        Sim, excluir funcionário

                    </button>

                </form>


            </footer>


        </div>

    </div>



    <script>

        const formArquivar =
            document.getElementById(
                'formArquivar'
            );


        if (formArquivar) {

            formArquivar.addEventListener(
                'submit',
                function (evento) {

                    const nome =
                        <?= json_encode($nome, JSON_UNESCAPED_UNICODE) ?>;


                    const confirmou =
                        confirm(
                            'Tem certeza que deseja arquivar o funcionário "' +
                            nome +
                            '"?'
                        );


                    if (!confirmou) {

                        evento.preventDefault();

                    }

                }
            );

        }

    </script>


</body>

</html>