
<?php

session_start();

require_once '../../../backend/Config/conexao.php';

if (!isset($_GET['cod_ala']) || trim($_GET['cod_ala']) === '') {
    die('Ala não encontrada.');
}

$codAla = trim($_GET['cod_ala']);

/* =========================================================
   FUNCIONÁRIO RESPONSÁVEL
========================================================= */

$idFuncionario = $_SESSION['id_funcionario'] ?? null;

/* =========================================================
   ALA007 E ALA008 NÃO PODEM TER OBRAS ASSOCIADAS/DESASSOCIADAS
========================================================= */

$bloquearAcoesAlocacao = in_array(
    $codAla,
    ['ALA007', 'ALA008'],
    true
);

/* =========================================================
   CSRF
========================================================= */

if (empty($_SESSION['csrf_obras_ala'])) {
    $_SESSION['csrf_obras_ala'] = bin2hex(random_bytes(32));
}

$mensagem = $_SESSION['mensagem_obras_ala'] ?? null;

unset($_SESSION['mensagem_obras_ala']);


/* =========================================================
   ASSOCIAR / DESASSOCIAR OBRAS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* -----------------------------------------------------
       PROTEÇÃO ALA007 / ALA008
    ----------------------------------------------------- */

    if ($bloquearAcoesAlocacao) {

        $_SESSION['mensagem_obras_ala'] = [
            'tipo' => 'erro',
            'texto' => 'Não é permitido alterar as obras das alas ALA007 e ALA008.'
        ];

        header(
            'Location: ' .
            $_SERVER['PHP_SELF'] .
            '?cod_ala=' .
            urlencode($codAla)
        );

        exit;
    }


    $csrf = $_POST['csrf'] ?? '';
    $acao = $_POST['acao'] ?? '';
    $itensSelecionados = $_POST['itens'] ?? [];


    /* -----------------------------------------------------
       VALIDAÇÕES
    ----------------------------------------------------- */

    if (
        !hash_equals(
            $_SESSION['csrf_obras_ala'],
            $csrf
        )
    ) {

        $_SESSION['mensagem_obras_ala'] = [
            'tipo' => 'erro',
            'texto' => 'A sessão do formulário expirou. Tente novamente.'
        ];

    } elseif (
        !in_array(
            $acao,
            ['associar', 'desassociar'],
            true
        )
    ) {

        $_SESSION['mensagem_obras_ala'] = [
            'tipo' => 'erro',
            'texto' => 'Ação inválida.'
        ];

    } elseif (empty($idFuncionario)) {

        $_SESSION['mensagem_obras_ala'] = [
            'tipo' => 'erro',
            'texto' => 'Funcionário não identificado na sessão. Faça login novamente.'
        ];

    } elseif (
        !is_array($itensSelecionados) ||
        empty($itensSelecionados)
    ) {

        $_SESSION['mensagem_obras_ala'] = [
            'tipo' => 'erro',
            'texto' => 'Selecione pelo menos uma obra.'
        ];

    } else {

        mysqli_begin_transaction($strcon);

        try {

            $totalAlterados = 0;
            $totalBloqueados = 0;


            /* =====================================================
               ASSOCIAR
            ===================================================== */

            if ($acao === 'associar') {

                /*
                 * Verifica se a obra já possui associação.
                 */
                $sqlTemAssociacao = "
                    SELECT 1
                    FROM registro
                    WHERE cod_item = ?
                      AND cod_ala IS NOT NULL
                    LIMIT 1
                ";

                $stmtTemAssociacao = mysqli_prepare(
                    $strcon,
                    $sqlTemAssociacao
                );

                if (!$stmtTemAssociacao) {
                    throw new Exception(
                        'Não foi possível verificar a associação da obra.'
                    );
                }


                /*
                 * =================================================
                 * ATUALIZA REGISTRO EXISTENTE SEM ALA
                 *
                 * IMPORTANTE:
                 * SOMENTE Em Exposição pode ser associado.
                 * =================================================
                 */

                $sqlAtualizarVazio = "
                    UPDATE registro r
                    INNER JOIN item i
                        ON i.cod_item = r.cod_item
                    SET
                        r.cod_ala = ?,
                        r.dt_registro = NOW(),
                        r.id_funcionario = ?
                    WHERE r.cod_item = ?
                      AND r.cod_ala IS NULL
                      AND i.status_obra = 'Em Exposição'
                      AND i.dt_arquivo IS NULL
                    LIMIT 1
                ";

                $stmtAtualizarVazio = mysqli_prepare(
                    $strcon,
                    $sqlAtualizarVazio
                );

                if (!$stmtAtualizarVazio) {

                    mysqli_stmt_close(
                        $stmtTemAssociacao
                    );

                    throw new Exception(
                        'Não foi possível preparar a associação.'
                    );
                }


                /*
                 * =================================================
                 * CRIA NOVO REGISTRO
                 *
                 * SOMENTE Em Exposição pode ser inserido.
                 * =================================================
                 */

                $sqlInserir = "
                    INSERT INTO registro (
                        cod_item,
                        cod_ala,
                        dt_registro,
                        id_funcionario
                    )
                    SELECT
                        i.cod_item,
                        ?,
                        NOW(),
                        ?
                    FROM item i
                    WHERE i.cod_item = ?
                      AND i.status_obra = 'Em Exposição'
                      AND i.dt_arquivo IS NULL
                    LIMIT 1
                ";

                $stmtInserir = mysqli_prepare(
                    $strcon,
                    $sqlInserir
                );

                if (!$stmtInserir) {

                    mysqli_stmt_close(
                        $stmtTemAssociacao
                    );

                    mysqli_stmt_close(
                        $stmtAtualizarVazio
                    );

                    throw new Exception(
                        'Não foi possível preparar a criação da associação.'
                    );
                }


                /* =================================================
                   VERIFICA STATUS DA OBRA
                ================================================= */

                $sqlVerificarStatus = "
                    SELECT
                        status_obra
                    FROM item
                    WHERE cod_item = ?
                      AND dt_arquivo IS NULL
                    LIMIT 1
                ";

                $stmtVerificarStatus = mysqli_prepare(
                    $strcon,
                    $sqlVerificarStatus
                );

                if (!$stmtVerificarStatus) {

                    throw new Exception(
                        'Não foi possível verificar o status da obra.'
                    );
                }


                foreach ($itensSelecionados as $codItem) {

                    $codItem = trim(
                        (string) $codItem
                    );

                    if ($codItem === '') {
                        continue;
                    }


                    /* =================================================
                       BLOQUEIO DEFINITIVO POR STATUS
                    ================================================= */

                    mysqli_stmt_bind_param(
                        $stmtVerificarStatus,
                        's',
                        $codItem
                    );

                    if (
                        !mysqli_stmt_execute(
                            $stmtVerificarStatus
                        )
                    ) {

                        throw new Exception(
                            'Erro ao verificar o status de uma das obras.'
                        );
                    }


                    $resultadoStatus =
                        mysqli_stmt_get_result(
                            $stmtVerificarStatus
                        );

                    $dadosStatus =
                        $resultadoStatus
                            ? mysqli_fetch_assoc(
                                $resultadoStatus
                            )
                            : null;


                    if ($resultadoStatus) {
                        mysqli_free_result(
                            $resultadoStatus
                        );
                    }


                    /*
                     * Obra inexistente ou arquivada.
                     */
                    if (!$dadosStatus) {

                        $totalBloqueados++;

                        continue;
                    }


                    /*
                     * SOMENTE "Em Exposição" PODE SER ALOCADA.
                     */
                    if (
                        $dadosStatus['status_obra']
                        !== 'Em Exposição'
                    ) {

                        $totalBloqueados++;

                        continue;
                    }


                    /* =================================================
                       PRIMEIRO:
                       REUTILIZA REGISTRO SEM ALA
                    ================================================= */

                    mysqli_stmt_bind_param(
                        $stmtAtualizarVazio,
                        'sis',
                        $codAla,
                        $idFuncionario,
                        $codItem
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stmtAtualizarVazio
                        )
                    ) {

                        throw new Exception(
                            'Erro ao associar uma das obras.'
                        );
                    }


                    $alterados =
                        mysqli_stmt_affected_rows(
                            $stmtAtualizarVazio
                        );


                    if ($alterados > 0) {

                        $totalAlterados += $alterados;

                        continue;
                    }


                    /* =================================================
                       VERIFICA SE JÁ ESTÁ EM OUTRA ALA
                    ================================================= */

                    mysqli_stmt_bind_param(
                        $stmtTemAssociacao,
                        's',
                        $codItem
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stmtTemAssociacao
                        )
                    ) {

                        throw new Exception(
                            'Erro ao verificar uma das obras.'
                        );
                    }


                    $resultadoAssociacao =
                        mysqli_stmt_get_result(
                            $stmtTemAssociacao
                        );


                    $jaAssociada =
                        $resultadoAssociacao &&
                        mysqli_fetch_row(
                            $resultadoAssociacao
                        );


                    if ($resultadoAssociacao) {
                        mysqli_free_result(
                            $resultadoAssociacao
                        );
                    }


                    /*
                     * Se já estiver em outra ala,
                     * não altera.
                     */

                    if ($jaAssociada) {
                        continue;
                    }


                    /* =================================================
                       OBRA NUNCA TEVE REGISTRO:
                       CRIA ASSOCIAÇÃO
                    ================================================= */

                    mysqli_stmt_bind_param(
                        $stmtInserir,
                        'sis',
                        $codAla,
                        $idFuncionario,
                        $codItem
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stmtInserir
                        )
                    ) {

                        throw new Exception(
                            'Erro ao criar a associação de uma das obras.'
                        );
                    }


                    $totalAlterados +=
                        mysqli_stmt_affected_rows(
                            $stmtInserir
                        );
                }


                mysqli_stmt_close(
                    $stmtVerificarStatus
                );

                mysqli_stmt_close(
                    $stmtTemAssociacao
                );

                mysqli_stmt_close(
                    $stmtAtualizarVazio
                );

                mysqli_stmt_close(
                    $stmtInserir
                );


                /* =================================================
                   MENSAGEM
                ================================================= */

                if ($totalAlterados > 0) {

                    if ($totalBloqueados > 0) {

                        $_SESSION['mensagem_obras_ala'] = [
                            'tipo' => 'sucesso',
                            'texto' =>
                                $totalAlterados .
                                ' obra(s) associada(s) com sucesso. ' .
                                $totalBloqueados .
                                ' obra(s) não puderam ser associadas porque não estão Em Exposição.'
                        ];

                    } else {

                        $_SESSION['mensagem_obras_ala'] = [
                            'tipo' => 'sucesso',
                            'texto' =>
                                'Obra(s) associada(s) à ala com sucesso.'
                        ];
                    }

                } elseif ($totalBloqueados > 0) {

                    $_SESSION['mensagem_obras_ala'] = [
                        'tipo' => 'erro',
                        'texto' =>
                            'Nenhuma obra foi associada. Somente obras com status Em Exposição podem ser alocadas.'
                    ];

                } else {

                    $_SESSION['mensagem_obras_ala'] = [
                        'tipo' => 'sucesso',
                        'texto' =>
                            'Nenhuma associação foi alterada. As obras selecionadas podem já estar associadas a outra ala.'
                    ];
                }
            }


            /* =====================================================
               DESASSOCIAR
            ===================================================== */

            else {

                /*
                 * A regra de status NÃO é aplicada aqui.
                 *
                 * Se uma obra já estiver em uma ala e depois
                 * mudar de status, ainda será possível removê-la
                 * da ala.
                 */

                $sqlAlterar = "
                    UPDATE registro
                    SET cod_ala = NULL
                    WHERE cod_item = ?
                      AND cod_ala = ?
                ";

                $stmtAlterar = mysqli_prepare(
                    $strcon,
                    $sqlAlterar
                );

                if (!$stmtAlterar) {

                    throw new Exception(
                        'Não foi possível preparar a desassociação.'
                    );
                }


                foreach ($itensSelecionados as $codItem) {

                    $codItem = trim(
                        (string) $codItem
                    );

                    if ($codItem === '') {
                        continue;
                    }


                    mysqli_stmt_bind_param(
                        $stmtAlterar,
                        'ss',
                        $codItem,
                        $codAla
                    );


                    if (
                        !mysqli_stmt_execute(
                            $stmtAlterar
                        )
                    ) {

                        throw new Exception(
                            'Erro ao desassociar uma das obras.'
                        );
                    }


                    $totalAlterados +=
                        mysqli_stmt_affected_rows(
                            $stmtAlterar
                        );
                }


                mysqli_stmt_close(
                    $stmtAlterar
                );


                $_SESSION['mensagem_obras_ala'] = [
                    'tipo' => 'sucesso',
                    'texto' => $totalAlterados > 0
                        ? 'Associação removida com sucesso.'
                        : 'Nenhuma associação foi alterada.'
                ];
            }


            mysqli_commit($strcon);

        } catch (Throwable $e) {

            mysqli_rollback($strcon);

            $_SESSION['mensagem_obras_ala'] = [
                'tipo' => 'erro',
                'texto' => $e->getMessage()
            ];
        }
    }


    header(
        'Location: ' .
        $_SERVER['PHP_SELF'] .
        '?cod_ala=' .
        urlencode($codAla)
    );

    exit;
}


/* =========================================================
   CONSULTA DA ALA
========================================================= */

$sql = "
    SELECT
        cod_ala,
        imagem_capa,
        nome,
        cor,
        descricao,
        area,
        status,
        dt_alocacao
    FROM alocacao
    WHERE cod_ala = ?
    LIMIT 1
";

$stmt = mysqli_prepare(
    $strcon,
    $sql
);

if (!$stmt) {
    die('Erro ao preparar a consulta da ala.');
}

mysqli_stmt_bind_param(
    $stmt,
    's',
    $codAla
);

mysqli_stmt_execute($stmt);

$resultado =
    mysqli_stmt_get_result($stmt);

$ala =
    mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);


if (!$ala) {
    die('Ala não encontrada.');
}


/* =========================================================
   DADOS VISUAIS DA ALA
========================================================= */

$cor = trim(
    $ala['cor']
);

if ($cor === '') {
    $cor = '#8f0000';
}


$imagemCapa = '';

if (!empty($ala['imagem_capa'])) {

    $imagemCapa =
        '../../../' .
        ltrim(
            $ala['imagem_capa'],
            '/'
        );
}


$descricao = !empty($ala['descricao'])
    ? $ala['descricao']
    : 'Nenhuma descrição cadastrada.';


$area = !empty($ala['area'])
    ? $ala['area']
    : 'Não informada.';


$status = !empty($ala['status'])
    ? $ala['status']
    : 'Não informado.';


$dtAlocacao = !empty($ala['dt_alocacao'])
    ? date(
        'd/m/Y H:i',
        strtotime(
            $ala['dt_alocacao']
        )
    )
    : 'Não informada.';


/* =========================================================
   OBRAS ASSOCIADAS À ALA
========================================================= */

$sqlObras = "
    SELECT DISTINCT
        i.cod_item,
        i.nome_item,
        i.categoria,
        i.foto_item,
        i.status_obra
    FROM registro r
    INNER JOIN item i
        ON i.cod_item = r.cod_item
    WHERE r.cod_ala = ?
      AND i.dt_arquivo IS NULL
    ORDER BY i.nome_item ASC
";

$stmtObras = mysqli_prepare(
    $strcon,
    $sqlObras
);

if (!$stmtObras) {
    die('Erro ao preparar a consulta das obras.');
}

mysqli_stmt_bind_param(
    $stmtObras,
    's',
    $codAla
);

mysqli_stmt_execute(
    $stmtObras
);

$resultadoObras =
    mysqli_stmt_get_result(
        $stmtObras
    );

$obras = [];

while (
    $obra = mysqli_fetch_assoc(
        $resultadoObras
    )
) {

    $obras[] = $obra;
}

mysqli_stmt_close(
    $stmtObras
);

$totalObras =
    count($obras);


/* =========================================================
   OBRAS DISPONÍVEIS PARA ASSOCIAÇÃO

   REGRA:
   SOMENTE:
       status_obra = 'Em Exposição'

   E:
       dt_arquivo IS NULL

   E:
       não pertencem a outra ala
========================================================= */

$sqlDisponiveis = "
    SELECT
        i.cod_item,
        i.nome_item,
        i.categoria,
        i.status_obra
    FROM item i
    WHERE i.dt_arquivo IS NULL
      AND i.status_obra = 'Em Exposição'
      AND (
          EXISTS (
              SELECT 1
              FROM registro r_null
              WHERE r_null.cod_item = i.cod_item
                AND r_null.cod_ala IS NULL
          )
          OR NOT EXISTS (
              SELECT 1
              FROM registro r_any
              WHERE r_any.cod_item = i.cod_item
          )
      )
    ORDER BY i.nome_item ASC
";


$resultadoDisponiveis =
    mysqli_query(
        $strcon,
        $sqlDisponiveis
    );

$obrasDisponiveis = [];

if ($resultadoDisponiveis) {

    while (
        $obraDisponivel =
            mysqli_fetch_assoc(
                $resultadoDisponiveis
            )
    ) {

        $obrasDisponiveis[] =
            $obraDisponivel;
    }
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($ala['nome']) ?> — Museu Art
    </title>

    <link
        rel="stylesheet"
        href="../../../assets/css/pages/obras_ala.css"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

</head>


<body>

<div
    class="painel-container"
    style="--cor-ala: <?= htmlspecialchars($cor, ENT_QUOTES, 'UTF-8') ?>;"
>


    <!-- =====================================================
         PAINEL ESQUERDO
    ====================================================== -->

    <aside class="painel-esquerdo">


        <div class="marca-lateral">

            <span class="marca-simbolo">
                M
            </span>

            <span>
                MUSEU ART
            </span>

        </div>


        <div class="ala-conteudo">


            <?php if ($imagemCapa !== ''): ?>

                <div class="imagem-capa">

                    <img
                        src="<?= htmlspecialchars($imagemCapa) ?>"
                        alt="<?= htmlspecialchars($ala['nome']) ?>"
                    >

                    <div class="imagem-overlay"></div>

                </div>

            <?php endif; ?>


            <div class="ala-informacoes">


                <div class="ala-titulo">

                    <span class="rotulo">
                        Ala
                    </span>

                    <h1>
                        <?= htmlspecialchars($ala['nome']) ?>
                    </h1>

                </div>


                <div class="informacoes-lista">


                    <div class="informacao">

                        <div class="informacao-esquerda">

                            <i class="bi bi-upc-scan"></i>

                            <span class="rotulo">
                                Código da ala
                            </span>

                        </div>

                        <strong>
                            <?= htmlspecialchars($ala['cod_ala']) ?>
                        </strong>

                    </div>


                    <div class="informacao">

                        <div class="informacao-esquerda">

                            <i class="bi bi-grid-3x3-gap"></i>

                            <span class="rotulo">
                                Área
                            </span>

                        </div>

                        <strong>
                            <?= htmlspecialchars($area) ?>
                        </strong>

                    </div>


                    <div class="informacao">

                        <div class="informacao-esquerda">

                            <i class="bi bi-circle-fill"></i>

                            <span class="rotulo">
                                Status
                            </span>

                        </div>

                        <strong class="status-valor">
                            <?= htmlspecialchars($status) ?>
                        </strong>

                    </div>


                    <div class="informacao">

                        <div class="informacao-esquerda">

                            <i class="bi bi-calendar3"></i>

                            <span class="rotulo">
                                Data de alocação
                            </span>

                        </div>

                        <strong>
                            <?= htmlspecialchars($dtAlocacao) ?>
                        </strong>

                    </div>


                </div>


                <div class="descricao">

                    <div class="descricao-titulo">

                        <span class="rotulo">
                            Sobre a ala
                        </span>

                        <span class="descricao-linha"></span>

                    </div>

                    <p>

                        <?= nl2br(
                            htmlspecialchars(
                                $descricao
                            )
                        ) ?>

                    </p>

                </div>


            </div>

        </div>

    </aside>


    <!-- =====================================================
         PAINEL DIREITO
    ====================================================== -->

    <main class="painel-direito">


        <div class="obras-container">


            <header class="obras-cabecalho">


                <div class="cabecalho-esquerda">


                    <a
                        href="../ala/alas.php"
                        class="btn-voltar"
                        title="Voltar para alas"
                    >

                        <i class="bi bi-arrow-left"></i>

                    </a>


                    <div class="obras-titulo">

                        <span class="rotulo">
                            Acervo da ala
                        </span>

                        <h2>
                            Obras associadas
                        </h2>

                        <p>

                            Obras registradas nesta ala:

                            <strong>
                                <?= htmlspecialchars($ala['nome']) ?>
                            </strong>

                        </p>

                    </div>


                </div>


            </header>


            <div class="linha-ala"></div>


            <section class="acervo-topo">


                <div class="acervo-contagem">

                    <span class="contador-ponto"></span>

                    <span>

                        <?= $totalObras ?>

                        <?= $totalObras === 1
                            ? 'obra'
                            : 'obras'
                        ?>

                        associada<?= $totalObras === 1
                            ? ''
                            : 's'
                        ?>

                    </span>

                </div>


                <div class="acervo-legenda">

                    ACERVO ·

                    <?= htmlspecialchars($ala['nome']) ?>

                </div>


            </section>


            <div class="obras-grid">


                <?php if (empty($obras)): ?>


                    <div class="sem-obras">


                        <div class="sem-obras-icone">

                            <i class="bi bi-images"></i>

                        </div>


                        <h2>
                            Nenhuma obra nesta ala
                        </h2>


                        <p>

                            Ainda não existem obras registradas
                            nesta ala.

                        </p>


                    </div>


                <?php else: ?>


                    <?php foreach ($obras as $obra): ?>


                        <?php

                        $categoriaClasse =
                            strtolower(
                                str_replace(
                                    ' ',
                                    '-',
                                    trim(
                                        $obra['categoria'] ?? ''
                                    )
                                )
                            );

                        ?>


                        <article
                            class="obra-card"
                            data-id="<?= htmlspecialchars($obra['cod_item']) ?>"
                        >


                            <div class="obra-card-imagem">


                                <?php if (!empty($obra['foto_item'])): ?>


                                    <img
                                        src="../../../<?= htmlspecialchars(
                                            ltrim(
                                                $obra['foto_item'],
                                                '/'
                                            )
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $obra['nome_item']
                                        ) ?>"
                                    >


                                <?php else: ?>


                                    <div class="obra-sem-imagem">

                                        <i class="bi bi-image"></i>

                                        <span>
                                            Sem imagem
                                        </span>

                                    </div>


                                <?php endif; ?>


                            </div>


                            <div class="obra-card-conteudo">


                                <div class="obra-card-cabecalho">


                                    <div>

                                        <span class="obra-indice">
                                            OBRA
                                        </span>

                                        <h3>

                                            <?= htmlspecialchars(
                                                $obra['nome_item']
                                            ) ?>

                                        </h3>

                                    </div>


                                </div>


                                <div class="categoria">


                                    <span
                                        class="categoria-<?= htmlspecialchars(
                                            $categoriaClasse
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $obra['categoria']
                                        ) ?>

                                    </span>


                                </div>


                                <div class="obra-card-footer">


                                    <span class="obra-codigo">

                                        #<?= htmlspecialchars(
                                            $obra['cod_item']
                                        ) ?>

                                    </span>


                                    <a
                                        href="../../../components/pages/item.php?id=<?= urlencode(
                                            $obra['cod_item']
                                        ) ?>&origem=ala"
                                        class="btn-visualizar"
                                    >

                                        <span>
                                            Ver detalhes
                                        </span>

                                        <i class="bi bi-arrow-up-right"></i>

                                    </a>


                                </div>


                            </div>


                        </article>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     TOAST
========================================================= -->

<?php if ($mensagem): ?>


    <div
        class="toast-ala toast-<?= htmlspecialchars($mensagem['tipo']) ?>"
        id="toastAla"
    >

        <i
            class="bi <?= $mensagem['tipo'] === 'sucesso'
                ? 'bi-check-circle'
                : 'bi-exclamation-circle' ?>"
        ></i>

        <span>

            <?= htmlspecialchars($mensagem['texto']) ?>

        </span>

    </div>


<?php endif; ?>


<!-- =========================================================
     BOTÕES DE AÇÃO

     ALA007 E ALA008 NÃO EXIBEM ESTES BOTÕES.
========================================================= -->

<?php if (!$bloquearAcoesAlocacao): ?>


    <div
        class="acoes-ala-fixas"
        aria-label="Ações das obras da ala"
    >


        <button
            type="button"
            class="btn-acao-ala btn-associar"
            data-modal="modalAssociar"
        >

            <i class="bi bi-plus-lg"></i>

            <span>
                Associar nova obra a essa ala
            </span>

        </button>


        <button
            type="button"
            class="btn-acao-ala btn-desassociar"
            data-modal="modalDesassociar"
        >

            <i class="bi bi-link-45deg"></i>

            <span>
                Tirar associação de uma obra a essa ala
            </span>

        </button>


    </div>


<?php endif; ?>


<!-- =========================================================
     MODAL ASSOCIAR

     ALA007 E ALA008 NÃO POSSUEM ESTE MODAL.
========================================================= -->

<?php if (!$bloquearAcoesAlocacao): ?>


    <div
        class="modal-ala"
        id="modalAssociar"
        aria-hidden="true"
    >


        <div
            class="modal-ala-fundo"
            data-fechar-modal
        ></div>


        <div
            class="modal-ala-caixa"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloAssociar"
        >


            <div class="modal-ala-cabecalho">


                <div>

                    <span class="rotulo">
                        Gerenciar acervo
                    </span>

                    <h2 id="tituloAssociar">
                        Associar obras à ala
                    </h2>

                    <p>
                        Selecione uma ou mais obras que estejam
                        com status <strong>Em Exposição</strong>
                        e ainda não pertençam a nenhuma ala.
                    </p>

                </div>


                <button
                    type="button"
                    class="modal-fechar"
                    data-fechar-modal
                    aria-label="Fechar"
                >

                    <i class="bi bi-x-lg"></i>

                </button>


            </div>


            <form
                method="post"
                class="modal-form"
            >


                <input
                    type="hidden"
                    name="csrf"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_obras_ala']
                    ) ?>"
                >


                <input
                    type="hidden"
                    name="acao"
                    value="associar"
                >


                <div class="modal-lista-obras">


                    <?php if (empty($obrasDisponiveis)): ?>


                        <div class="modal-vazio">


                            <i class="bi bi-inbox"></i>


                            <strong>
                                Nenhuma obra disponível
                            </strong>


                            <span>
                                Não há obras com status
                                <strong>Em Exposição</strong>
                                disponíveis para associar.
                            </span>


                        </div>


                    <?php else: ?>


                        <?php foreach (
                            $obrasDisponiveis
                            as $obraDisponivel
                        ): ?>


                            <label class="obra-opcao">


                                <input
                                    type="checkbox"
                                    name="itens[]"
                                    value="<?= htmlspecialchars(
                                        $obraDisponivel['cod_item']
                                    ) ?>"
                                >


                                <span class="obra-opcao-check">

                                    <i class="bi bi-check-lg"></i>

                                </span>


                                <span class="obra-opcao-info">


                                    <strong>

                                        <?= htmlspecialchars(
                                            $obraDisponivel['nome_item']
                                        ) ?>

                                    </strong>


                                    <small>

                                        #<?= htmlspecialchars(
                                            $obraDisponivel['cod_item']
                                        ) ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $obraDisponivel['categoria']
                                            ?? 'Sem categoria'
                                        ) ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $obraDisponivel['status_obra']
                                        ) ?>

                                    </small>


                                </span>


                            </label>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


                <div class="modal-ala-rodape">


                    <span class="selecionados-contador">

                        0 selecionada(s)

                    </span>


                    <div class="modal-botoes">


                        <button
                            type="button"
                            class="btn-modal-secundario"
                            data-fechar-modal
                        >

                            Cancelar

                        </button>


                        <button
                            type="submit"
                            class="btn-modal-principal"
                            <?= empty($obrasDisponiveis)
                                ? 'disabled'
                                : '' ?>
                        >

                            Associar selecionadas

                        </button>


                    </div>


                </div>


            </form>


        </div>


    </div>


<?php endif; ?>


<!-- =========================================================
     MODAL DESASSOCIAR

     ALA007 E ALA008 NÃO POSSUEM ESTE MODAL.
========================================================= -->

<?php if (!$bloquearAcoesAlocacao): ?>


    <div
        class="modal-ala"
        id="modalDesassociar"
        aria-hidden="true"
    >


        <div
            class="modal-ala-fundo"
            data-fechar-modal
        ></div>


        <div
            class="modal-ala-caixa"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloDesassociar"
        >


            <div class="modal-ala-cabecalho">


                <div>

                    <span class="rotulo">
                        Gerenciar acervo
                    </span>

                    <h2 id="tituloDesassociar">
                        Remover associação
                    </h2>

                    <p>
                        Selecione as obras que deixarão
                        de pertencer a esta ala.
                    </p>

                </div>


                <button
                    type="button"
                    class="modal-fechar"
                    data-fechar-modal
                    aria-label="Fechar"
                >

                    <i class="bi bi-x-lg"></i>

                </button>


            </div>


            <form
                method="post"
                class="modal-form"
            >


                <input
                    type="hidden"
                    name="csrf"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_obras_ala']
                    ) ?>"
                >


                <input
                    type="hidden"
                    name="acao"
                    value="desassociar"
                >


                <div class="modal-lista-obras">


                    <?php if (empty($obras)): ?>


                        <div class="modal-vazio">


                            <i class="bi bi-link-45deg"></i>


                            <strong>
                                Nenhuma obra associada
                            </strong>


                            <span>
                                Esta ala ainda não possui
                                obras para remover.
                            </span>


                        </div>


                    <?php else: ?>


                        <?php foreach (
                            $obras
                            as $obraAssociada
                        ): ?>


                            <label class="obra-opcao">


                                <input
                                    type="checkbox"
                                    name="itens[]"
                                    value="<?= htmlspecialchars(
                                        $obraAssociada['cod_item']
                                    ) ?>"
                                >


                                <span class="obra-opcao-check">

                                    <i class="bi bi-check-lg"></i>

                                </span>


                                <span class="obra-opcao-info">


                                    <strong>

                                        <?= htmlspecialchars(
                                            $obraAssociada['nome_item']
                                        ) ?>

                                    </strong>


                                    <small>

                                        #<?= htmlspecialchars(
                                            $obraAssociada['cod_item']
                                        ) ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $obraAssociada['categoria']
                                            ?? 'Sem categoria'
                                        ) ?>

                                    </small>


                                </span>


                            </label>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


                <div class="modal-ala-rodape">


                    <span class="selecionados-contador">

                        0 selecionada(s)

                    </span>


                    <div class="modal-botoes">


                        <button
                            type="button"
                            class="btn-modal-secundario"
                            data-fechar-modal
                        >

                            Cancelar

                        </button>


                        <button
                            type="submit"
                            class="btn-modal-perigo"
                            <?= empty($obras)
                                ? 'disabled'
                                : '' ?>
                        >

                            Remover associação

                        </button>


                    </div>


                </div>


            </form>


        </div>


    </div>


<?php endif; ?>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    () => {


        /* =====================================================
           ABRIR MODAL
        ===================================================== */

        const abrirModal = (id) => {

            const modal =
                document.getElementById(id);

            if (!modal) return;


            modal.classList.add('aberto');

            modal.setAttribute(
                'aria-hidden',
                'false'
            );


            document.body.classList.add(
                'modal-aberto'
            );
        };


        /* =====================================================
           FECHAR MODAL
        ===================================================== */

        const fecharModal = (modal) => {

            if (!modal) return;


            modal.classList.remove(
                'aberto'
            );


            modal.setAttribute(
                'aria-hidden',
                'true'
            );


            document.body.classList.remove(
                'modal-aberto'
            );
        };


        /* =====================================================
           BOTÕES QUE ABREM MODAL
        ===================================================== */

        document
            .querySelectorAll('[data-modal]')
            .forEach((botao) => {

                botao.addEventListener(
                    'click',
                    () => {

                        abrirModal(
                            botao.dataset.modal
                        );

                    }
                );

            });


        /* =====================================================
           BOTÕES QUE FECHAM MODAL
        ===================================================== */

        document
            .querySelectorAll('[data-fechar-modal]')
            .forEach((botao) => {

                botao.addEventListener(
                    'click',
                    () => {

                        fecharModal(
                            botao.closest(
                                '.modal-ala'
                            )
                        );

                    }
                );

            });


        /* =====================================================
           ESC FECHA MODAL
        ===================================================== */

        document.addEventListener(
            'keydown',
            (event) => {

                if (event.key === 'Escape') {

                    document
                        .querySelectorAll(
                            '.modal-ala.aberto'
                        )
                        .forEach(
                            fecharModal
                        );

                }

            }
        );


        /* =====================================================
           CONTADOR DE SELECIONADOS
        ===================================================== */

        document
            .querySelectorAll('.modal-form')
            .forEach((form) => {


                const contador =
                    form.querySelector(
                        '.selecionados-contador'
                    );


                const checkboxes =
                    form.querySelectorAll(
                        'input[name="itens[]"]'
                    );


                const atualizarContador = () => {

                    const total =
                        form.querySelectorAll(
                            'input[name="itens[]"]:checked'
                        ).length;


                    if (contador) {

                        contador.textContent =
                            `${total} selecionada(s)`;

                    }

                };


                checkboxes.forEach(
                    (checkbox) => {

                        checkbox.addEventListener(
                            'change',
                            atualizarContador
                        );

                    }
                );

            });


        /* =====================================================
           TOAST
        ===================================================== */

        const toast =
            document.getElementById(
                'toastAla'
            );


        if (toast) {

            window.setTimeout(
                () => {

                    toast.classList.add(
                        'sumir'
                    );

                },
                3500
            );

        }

    }
);

</script>


</body>

</html>

