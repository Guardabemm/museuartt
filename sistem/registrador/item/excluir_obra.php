<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../backend/Config/conexao.php";

if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../public/index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| DEFINE A URL BASE DO PROJETO
|--------------------------------------------------------------------------
*/

$baseUrl = '/SistemaMuseuArt.GuardaBem/sistem';

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['tipo'] ?? 'Funcionário';

/*
|--------------------------------------------------------------------------
| ORIGEM DA PÁGINA
|--------------------------------------------------------------------------
*/

$origem = $_GET['origem'] ?? $_POST['origem'] ?? 'registrador';

switch ($origem) {

    case 'administrador':
        $paginaVoltar = $baseUrl . '/administrador/item/obras.php';
        break;

    case 'manipulador':
        $paginaVoltar = $baseUrl . '/manipulador/item/obras.php';
        break;

    case 'registrador':
    default:
        $origem = 'registrador';
        $paginaVoltar = $baseUrl . '/registrador/item/obras.php';
        break;
}

/*
|--------------------------------------------------------------------------
| CÓDIGO DA OBRA
|--------------------------------------------------------------------------
| Aceita tanto id quanto cod_item
|--------------------------------------------------------------------------
*/

$codItem = $_GET['cod_item'] ?? $_GET['id'] ?? '';

if ($codItem === '' || !is_numeric($codItem)) {
    header("Location: " . $paginaVoltar . "?erro=codigo_invalido");
    exit();
}

$codItem = (int) $codItem;

/*
|--------------------------------------------------------------------------
| PROCESSAMENTO POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | CANCELAR
    |--------------------------------------------------------------------------
    */

    if ($acao === 'cancelar') {

        header("Location: " . $paginaVoltar);
        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | ARQUIVAR OBRA
    |--------------------------------------------------------------------------
    */

    if ($acao === 'excluir') {

        mysqli_begin_transaction($strcon);

        try {

            /*
            |--------------------------------------------------------------------------
            | Em vez de DELETE, a obra recebe a data/hora atual.
            |--------------------------------------------------------------------------
            */

            $sqlArquivar = "
                UPDATE item
                SET dt_arquivo = NOW()
                WHERE cod_item = ?
                AND dt_arquivo IS NULL
            ";

            $stmtArquivar = mysqli_prepare(
                $strcon,
                $sqlArquivar
            );

            if (!$stmtArquivar) {
                throw new Exception(
                    "Não foi possível preparar o arquivamento da obra."
                );
            }

            mysqli_stmt_bind_param(
                $stmtArquivar,
                "i",
                $codItem
            );

            if (!mysqli_stmt_execute($stmtArquivar)) {

                throw new Exception(
                    mysqli_stmt_error($stmtArquivar)
                );
            }

            $linhasAfetadas =
                mysqli_stmt_affected_rows(
                    $stmtArquivar
                );

            mysqli_stmt_close(
                $stmtArquivar
            );

            /*
            |--------------------------------------------------------------------------
            | Se nenhuma linha foi alterada,
            | a obra pode já estar arquivada ou não existir.
            |--------------------------------------------------------------------------
            */

            if ($linhasAfetadas <= 0) {

                throw new Exception(
                    "A obra já está arquivada ou não foi encontrada."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Confirma a alteração no banco.
            |--------------------------------------------------------------------------
            */

            mysqli_commit($strcon);

            /*
            |--------------------------------------------------------------------------
            | Redireciona para a lista.
            |--------------------------------------------------------------------------
            */

            header(
                "Location: " .
                $paginaVoltar .
                "?arquivamento=sucesso"
            );

            exit();

        } catch (Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | Cancela a transação em caso de erro.
            |--------------------------------------------------------------------------
            */

            mysqli_rollback($strcon);

            header(
                "Location: " .
                $paginaVoltar .
                "?erro=" .
                urlencode(
                    $e->getMessage()
                )
            );

            exit();
        }
    }
}

/*
|--------------------------------------------------------------------------
| BUSCAR OBRA
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        cod_item,
        nome_item,
        nome_autor,
        dt_criacao,
        dimensao,
        material,
        estado_conservacao,
        requisito_conservacao,
        proveniencia,
        dt_aquisicao,
        historico_proprietario,
        metodo_aquisicao,
        descricao,
        foto_item,
        foto_autor,
        status_obra,
        categoria,
        foto_item2,
        foto_item3,
        foto_item4,
        dt_arquivo
    FROM item
    WHERE cod_item = ?
    LIMIT 1
";

$stmt = mysqli_prepare(
    $strcon,
    $sql
);

if (!$stmt) {

    die(
        "Erro ao preparar consulta da obra."
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $codItem
);

mysqli_stmt_execute(
    $stmt
);

$resultado =
    mysqli_stmt_get_result(
        $stmt
    );

$obra =
    mysqli_fetch_assoc(
        $resultado
    );

mysqli_stmt_close(
    $stmt
);

/*
|--------------------------------------------------------------------------
| VERIFICA SE A OBRA EXISTE
|--------------------------------------------------------------------------
*/

if (!$obra) {

    header(
        "Location: " .
        $paginaVoltar .
        "?erro=obra_nao_encontrada"
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| VERIFICA SE JÁ ESTÁ ARQUIVADA
|--------------------------------------------------------------------------
*/

if (!empty($obra['dt_arquivo'])) {

    header(
        "Location: " .
        $paginaVoltar .
        "?erro=" .
        urlencode(
            "Esta obra já está arquivada."
        )
    );

    exit();
}

/*
|--------------------------------------------------------------------------
| PREENCHER VARIÁVEIS
|--------------------------------------------------------------------------
*/

$nomeItem =
    $obra['nome_item'] ?? '';

$nomeAutor =
    $obra['nome_autor'] ?? '';

$dtCriacao =
    $obra['dt_criacao'] ?? '';

$dimensao =
    $obra['dimensao'] ?? '';

$material =
    $obra['material'] ?? '';

$estadoConservacao =
    $obra['estado_conservacao'] ?? '';

$requisitoConservacao =
    $obra['requisito_conservacao'] ?? '';

$proveniencia =
    $obra['proveniencia'] ?? '';

$dtAquisicao =
    $obra['dt_aquisicao'] ?? '';

$historicoProprietario =
    $obra['historico_proprietario'] ?? '';

$metodoAquisicao =
    $obra['metodo_aquisicao'] ?? '';

$descricao =
    $obra['descricao'] ?? '';

$fotoItem =
    $obra['foto_item'] ?? '';

$fotoAutor =
    $obra['foto_autor'] ?? '';

$statusObra =
    $obra['status_obra'] ?? '';

$categoria =
    $obra['categoria'] ?? '';

$fotoItem2 =
    $obra['foto_item2'] ?? '';

$fotoItem3 =
    $obra['foto_item3'] ?? '';

$fotoItem4 =
    $obra['foto_item4'] ?? '';

$dtArquivo =
    $obra['dt_arquivo'] ?? '';

/*
|--------------------------------------------------------------------------
| FUSO HORÁRIO
|--------------------------------------------------------------------------
*/

date_default_timezone_set(
    'America/Sao_Paulo'
);

/*
|--------------------------------------------------------------------------
| DATA E HORA ATUAL
|--------------------------------------------------------------------------
*/

$dtArquivoAtual =
    date(
        'd/m/Y H:i'
    );

/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DA DATA DE CRIAÇÃO
|--------------------------------------------------------------------------
*/

$dtCriacaoFormatada = '';

if (!empty($dtCriacao)) {

    $timestamp =
        strtotime(
            $dtCriacao
        );

    if ($timestamp !== false) {

        $dtCriacaoFormatada =
            date(
                'd/m/Y',
                $timestamp
            );
    }
}

/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DA DATA DE AQUISIÇÃO
|--------------------------------------------------------------------------
*/

$dtAquisicaoFormatada = '';

if (!empty($dtAquisicao)) {

    $timestamp =
        strtotime(
            $dtAquisicao
        );

    if ($timestamp !== false) {

        $dtAquisicaoFormatada =
            date(
                'd/m/Y',
                $timestamp
            );
    }
}

/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DA DATA DE ARQUIVO
|--------------------------------------------------------------------------
*/

$dtArquivoFormatada = '';

if (!empty($dtArquivo)) {

    $timestamp =
        strtotime(
            $dtArquivo
        );

    if ($timestamp !== false) {

        $dtArquivoFormatada =
            date(
                'd/m/Y H:i',
                $timestamp
            );
    }
}

/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA MONTAR URL DAS IMAGENS
|--------------------------------------------------------------------------
*/

function montarUrlImagem(
    string $imagem
): string {

    if ($imagem === '') {
        return '';
    }

    return
        '/SistemaMuseuArt.GuardaBem/' .
        ltrim(
            $imagem,
            '/'
        );
}

/*
|--------------------------------------------------------------------------
| URLs DAS IMAGENS
|--------------------------------------------------------------------------
*/

$fotoItemUrl =
    montarUrlImagem(
        $fotoItem
    );

$fotoAutorUrl =
    montarUrlImagem(
        $fotoAutor
    );

$fotoItem2Url =
    montarUrlImagem(
        $fotoItem2
    );

$fotoItem3Url =
    montarUrlImagem(
        $fotoItem3
    );

$fotoItem4Url =
    montarUrlImagem(
        $fotoItem4
    );

/*
|--------------------------------------------------------------------------
| MENSAGEM DE ERRO
|--------------------------------------------------------------------------
*/

$mensagemErro =
    $_GET['erro'] ?? '';

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
        Arquivar obra - MuseuArt
    </title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="../../../assets/css/design/CRUDS/excluir_obra.css"
    >

    
</head>

<body>

    <div class="excluir-overlay">

        <div class="excluir-modal">

            <!-- =====================================================
                 CABEÇALHO
            ====================================================== -->

            <header class="excluir-header">

                <div class="titulo-container">

                    <div class="icone-excluir">

                        <i class="bi bi-archive"></i>

                    </div>

                    <div>

                        <h1>
                            Arquivar obra
                        </h1>

                        <p>
                            Confira os dados antes de continuar.
                        </p>

                    </div>

                </div>

                <button
                    type="button"
                    class="btn-fechar"
                    title="Voltar"
                    onclick="window.location.href='<?= htmlspecialchars($paginaVoltar) ?>'"
                >

                    <i class="bi bi-x-lg"></i>

                </button>

            </header>

            <!-- =====================================================
                 CONTEÚDO
            ====================================================== -->

            <main class="excluir-conteudo">

                <?php if ($mensagemErro !== ''): ?>

                    <div class="mensagem-erro">

                        <i class="bi bi-exclamation-circle"></i>

                        <span>
                            <?= htmlspecialchars($mensagemErro) ?>
                        </span>

                    </div>

                <?php endif; ?>

                <!-- =================================================
                     IDENTIFICAÇÃO
                ================================================== -->

                <section class="obra-card">

                    <div class="obra-imagem-principal">

                        <?php if (!empty($fotoItemUrl)): ?>

                            <img
                                src="<?= htmlspecialchars($fotoItemUrl) ?>"
                                alt="Imagem da obra <?= htmlspecialchars($nomeItem) ?>"
                            >

                        <?php else: ?>

                            <div class="obra-placeholder">

                                <i class="bi bi-image"></i>

                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="dados-obra">

                        <div class="nome-obra">

                            <span>
                                OBRA
                            </span>

                            <h2>
                                <?= htmlspecialchars($nomeItem) ?>
                            </h2>

                        </div>

                        <div class="linha-dados">

                            <div class="dado">

                                <span>
                                    Código
                                </span>

                                <strong>
                                    #<?= htmlspecialchars((string)$codItem) ?>
                                </strong>

                            </div>

                            <div class="dado">

                                <span>
                                    Categoria
                                </span>

                                <strong>
                                    <?= htmlspecialchars($categoria) ?>
                                </strong>

                            </div>

                            <div class="dado">

                                <span>
                                    Autor
                                </span>

                                <strong>
                                    <?= htmlspecialchars($nomeAutor) ?>
                                </strong>

                            </div>

                            <div class="dado">

                                <span>
                                    Status
                                </span>

                                <strong>
                                    <?= htmlspecialchars($statusObra) ?>
                                </strong>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- =================================================
                     CARACTERÍSTICAS
                ================================================== -->

                <section class="dados-card">

                    <div class="dados-card-header">

                        <i class="bi bi-info-circle"></i>

                        <div>

                            <h3>
                                Identificação e características
                            </h3>

                            <p>
                                Informações atualmente registradas no acervo.
                            </p>

                        </div>

                    </div>

                    <div class="dados-grid">

                        <div class="campo">

                            <span>
                                Código da obra
                            </span>

                            <strong>
                                <?= htmlspecialchars((string)$codItem) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Categoria
                            </span>

                            <strong>
                                <?= htmlspecialchars($categoria) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Nome da obra
                            </span>

                            <strong>
                                <?= htmlspecialchars($nomeItem) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Nome do autor
                            </span>

                            <strong>
                                <?= htmlspecialchars($nomeAutor) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Data de criação
                            </span>

                            <strong>
                                <?= htmlspecialchars($dtCriacaoFormatada) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Material
                            </span>

                            <strong>
                                <?= htmlspecialchars($material) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Dimensões
                            </span>

                            <strong>
                                <?= htmlspecialchars($dimensao) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Estado de conservação
                            </span>

                            <strong>
                                <?= htmlspecialchars($estadoConservacao) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Status da obra
                            </span>

                            <strong>
                                <?= htmlspecialchars($statusObra) ?>
                            </strong>

                        </div>

                        <?php if ($requisitoConservacao !== ''): ?>

                            <div class="campo campo-grande">

                                <span>
                                    Requisito de conservação
                                </span>

                                <strong>
                                    <?= nl2br(
                                        htmlspecialchars(
                                            $requisitoConservacao
                                        )
                                    ) ?>
                                </strong>

                            </div>

                        <?php endif; ?>

                    </div>

                </section>

                <!-- =================================================
                     AQUISIÇÃO
                ================================================== -->

                <section class="dados-card">

                    <div class="dados-card-header">

                        <i class="bi bi-briefcase"></i>

                        <div>

                            <h3>
                                Aquisição e proveniência
                            </h3>

                            <p>
                                Histórico e informações sobre a aquisição.
                            </p>

                        </div>

                    </div>

                    <div class="dados-grid">

                        <div class="campo">

                            <span>
                                Data de aquisição
                            </span>

                            <strong>
                                <?= htmlspecialchars($dtAquisicaoFormatada) ?>
                            </strong>

                        </div>

                        <div class="campo">

                            <span>
                                Método de aquisição
                            </span>

                            <strong>
                                <?= htmlspecialchars($metodoAquisicao) ?>
                            </strong>

                        </div>

                        <div class="campo campo-grande">

                            <span>
                                Proveniência
                            </span>

                            <strong>
                                <?= nl2br(
                                    htmlspecialchars(
                                        $proveniencia
                                    )
                                ) ?>
                            </strong>

                        </div>

                        <div class="campo campo-grande">

                            <span>
                                Histórico do proprietário
                            </span>

                            <strong>

                                <?= $historicoProprietario !== ''
                                    ? nl2br(
                                        htmlspecialchars(
                                            $historicoProprietario
                                        )
                                    )
                                    : 'Não informado'
                                ?>

                            </strong>

                        </div>

                    </div>

                </section>

                <!-- =================================================
                     DESCRIÇÃO
                ================================================== -->

                <section class="dados-card">

                    <div class="dados-card-header">

                        <i class="bi bi-card-text"></i>

                        <div>

                            <h3>
                                Descrição
                            </h3>

                            <p>
                                Informações detalhadas sobre a obra.
                            </p>

                        </div>

                    </div>

                    <div class="descricao-obra">

                        <?= nl2br(
                            htmlspecialchars(
                                $descricao
                            )
                        ) ?>

                    </div>

                </section>

                <!-- =================================================
                     IMAGENS
                ================================================== -->

                <section class="dados-card">

                    <div class="dados-card-header">

                        <i class="bi bi-images"></i>

                        <div>

                            <h3>
                                Imagens cadastradas
                            </h3>

                            <p>
                                Imagens atualmente associadas à obra.
                            </p>

                        </div>

                    </div>

                    <div class="galeria-imagens">

                        <?php if (!empty($fotoItemUrl)): ?>

                            <div class="imagem-item">

                                <img
                                    src="<?= htmlspecialchars($fotoItemUrl) ?>"
                                    alt="Imagem principal"
                                >

                                <span>
                                    Imagem principal
                                </span>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($fotoAutorUrl)): ?>

                            <div class="imagem-item">

                                <img
                                    src="<?= htmlspecialchars($fotoAutorUrl) ?>"
                                    alt="Foto do autor"
                                >

                                <span>
                                    Foto do autor
                                </span>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($fotoItem2Url)): ?>

                            <div class="imagem-item">

                                <img
                                    src="<?= htmlspecialchars($fotoItem2Url) ?>"
                                    alt="Imagem adicional 2"
                                >

                                <span>
                                    Imagem adicional 2
                                </span>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($fotoItem3Url)): ?>

                            <div class="imagem-item">

                                <img
                                    src="<?= htmlspecialchars($fotoItem3Url) ?>"
                                    alt="Imagem adicional 3"
                                >

                                <span>
                                    Imagem adicional 3
                                </span>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($fotoItem4Url)): ?>

                            <div class="imagem-item">

                                <img
                                    src="<?= htmlspecialchars($fotoItem4Url) ?>"
                                    alt="Imagem adicional 4"
                                >

                                <span>
                                    Imagem adicional 4
                                </span>

                            </div>

                        <?php endif; ?>

                        <?php if (
                            empty($fotoItemUrl) &&
                            empty($fotoAutorUrl) &&
                            empty($fotoItem2Url) &&
                            empty($fotoItem3Url) &&
                            empty($fotoItem4Url)
                        ): ?>

                            <div class="sem-imagens">

                                <i class="bi bi-image"></i>

                                <span>
                                    Nenhuma imagem cadastrada.
                                </span>

                            </div>

                        <?php endif; ?>

                    </div>

                </section>

                <!-- =================================================
                     DATA/HORA DO ARQUIVAMENTO
                ================================================== -->

                <section class="dados-card">

                    <div class="dados-card-header">

                        <i class="bi bi-clock"></i>

                        <div>

                            <h3>
                                Data do arquivamento
                            </h3>

                            <p>
                                Data e hora em que a obra será arquivada.
                            </p>

                        </div>

                    </div>

                    <div class="dados-grid">

                        <div class="campo campo-grande">

                            <span>
                                Data e hora atual
                            </span>

                            <strong>
                                <?= htmlspecialchars($dtArquivoAtual) ?>
                            </strong>

                        </div>

                    </div>

                </section>

                <!-- =================================================
                     AVISO
                ================================================== -->

                <section class="aviso-exclusao">

                    <div class="aviso-icone">

                        <i class="bi bi-exclamation-triangle"></i>

                    </div>

                    <div>

                        <strong>
                            A obra será arquivada
                        </strong>

                        <p>
                            A obra não será apagada permanentemente
                            do banco de dados. Ela receberá a data e
                            hora de arquivamento e poderá ser consultada
                            posteriormente na área de obras arquivadas.
                        </p>

                    </div>

                </section>

            </main>

            <!-- =====================================================
                 RODAPÉ
            ====================================================== -->

            <footer class="excluir-footer">

                <!-- CANCELAR -->

                <form
                    method="POST"
                    action="<?= htmlspecialchars(
                        $paginaVoltar .
                        '?cod_item=' .
                        $codItem .
                        '&origem=' .
                        urlencode($origem)
                    ) ?>"
                >

                    <input
                        type="hidden"
                        name="acao"
                        value="cancelar"
                    >

                    <input
                        type="hidden"
                        name="origem"
                        value="<?= htmlspecialchars($origem) ?>"
                    >

                    <button
                        type="submit"
                        class="btn-cancelar"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Não, voltar

                    </button>

                </form>

                <!-- ARQUIVAR -->

                <form
                    method="POST"
                    action="excluir_obra.php?cod_item=<?= $codItem ?>&origem=<?= urlencode($origem) ?>"
                    id="formExcluir"
                >

                    <input
                        type="hidden"
                        name="acao"
                        value="excluir"
                    >

                    <input
                        type="hidden"
                        name="origem"
                        value="<?= htmlspecialchars($origem) ?>"
                    >

                    <button
                        type="button"
                        class="btn-excluir"
                        id="btnExcluir"
                    >

                        <i
                            class="bi bi-archive"
                            id="iconeBotao"
                        ></i>

                        <span id="textoBotao">
                            Sim, arquivar obra
                        </span>

                    </button>

                </form>

            </footer>

        </div>

    </div>

    <!-- =========================================================
         MODAL DE CONFIRMAÇÃO
    ========================================================= -->

    <div
        class="modal-confirmacao"
        id="modalConfirmacao"
    >

        <div class="modal-caixa">

            <div class="modal-icone">

                <i class="bi bi-archive"></i>

            </div>

            <h2>
                Arquivar obra?
            </h2>

            <p>

                Tem certeza que deseja arquivar
                a obra

                <strong id="nomeObraModal"></strong>?

            </p>

            <div class="modal-data">

                <i class="bi bi-clock"></i>

                <span>
                    Data e hora do arquivamento:
                </span>

                <strong id="dataHoraModal"></strong>

            </div>

            <p class="modal-aviso">

                A obra não será apagada permanentemente.
                Ela poderá ser consultada posteriormente
                na área de obras arquivadas.

            </p>

            <div class="modal-botoes">

                <button
                    type="button"
                    class="modal-btn-cancelar"
                    id="btnCancelarModal"
                >

                    <i class="bi bi-arrow-left"></i>

                    Não, voltar

                </button>

                <button
                    type="button"
                    class="modal-btn-confirmar"
                    id="btnConfirmarModal"
                >

                    <i class="bi bi-archive"></i>

                    Sim, arquivar obra

                </button>

            </div>

        </div>

    </div>

    <!-- =========================================================
         JAVASCRIPT
    ========================================================= -->

    <script>

        const formExcluir =
            document.getElementById(
                'formExcluir'
            );

        const btnExcluir =
            document.getElementById(
                'btnExcluir'
            );

        const textoBotao =
            document.getElementById(
                'textoBotao'
            );

        const iconeBotao =
            document.getElementById(
                'iconeBotao'
            );

        /*
        |--------------------------------------------------------------------------
        | ELEMENTOS DO MODAL
        |--------------------------------------------------------------------------
        */

        const modalConfirmacao =
            document.getElementById(
                'modalConfirmacao'
            );

        const btnCancelarModal =
            document.getElementById(
                'btnCancelarModal'
            );

        const btnConfirmarModal =
            document.getElementById(
                'btnConfirmarModal'
            );

        const nomeObraModal =
            document.getElementById(
                'nomeObraModal'
            );

        const dataHoraModal =
            document.getElementById(
                'dataHoraModal'
            );

        /*
        |--------------------------------------------------------------------------
        | DADOS VINDOS DO PHP
        |--------------------------------------------------------------------------
        */

        const nome =
            <?= json_encode(
                $nomeItem,
                JSON_UNESCAPED_UNICODE
            ) ?>;

        const dataHora =
            <?= json_encode(
                $dtArquivoAtual,
                JSON_UNESCAPED_UNICODE
            ) ?>;

        /*
        |--------------------------------------------------------------------------
        | ABRIR MODAL
        |--------------------------------------------------------------------------
        */

        if (btnExcluir) {

            btnExcluir.addEventListener(
                'click',
                function () {

                    nomeObraModal.textContent =
                        '"' + nome + '"';

                    dataHoraModal.textContent =
                        dataHora;

                    modalConfirmacao.classList.add(
                        'ativo'
                    );

                    document.body.style.overflow =
                        'hidden';

                }
            );

        }

        /*
        |--------------------------------------------------------------------------
        | CANCELAR
        |--------------------------------------------------------------------------
        */

        if (btnCancelarModal) {

            btnCancelarModal.addEventListener(
                'click',
                function () {

                    modalConfirmacao.classList.remove(
                        'ativo'
                    );

                    document.body.style.overflow =
                        '';

                }
            );

        }

        /*
        |--------------------------------------------------------------------------
        | CLICAR FORA DO MODAL
        |--------------------------------------------------------------------------
        */

        if (modalConfirmacao) {

            modalConfirmacao.addEventListener(
                'click',
                function (evento) {

                    if (
                        evento.target ===
                        modalConfirmacao
                    ) {

                        modalConfirmacao.classList.remove(
                            'ativo'
                        );

                        document.body.style.overflow =
                            '';

                    }

                }
            );

        }

        /*
        |--------------------------------------------------------------------------
        | ESC FECHA O MODAL
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (evento) {

                if (
                    evento.key === 'Escape' &&
                    modalConfirmacao.classList.contains('ativo')
                ) {

                    modalConfirmacao.classList.remove(
                        'ativo'
                    );

                    document.body.style.overflow =
                        '';

                }

            }
        );

        /*
        |--------------------------------------------------------------------------
        | CONFIRMAR ARQUIVAMENTO
        |--------------------------------------------------------------------------
        */

        if (btnConfirmarModal) {

            btnConfirmarModal.addEventListener(
                'click',
                function () {

                    /*
                    | Desabilita o botão para evitar
                    | duplo clique.
                    */

                    btnConfirmarModal.disabled =
                        true;

                    btnConfirmarModal.style.pointerEvents =
                        'none';

                    btnConfirmarModal.style.opacity =
                        '0.7';

                    /*
                    | Fecha o modal.
                    */

                    modalConfirmacao.classList.remove(
                        'ativo'
                    );

                    /*
                    | Mostra "Arquivando..."
                    */

                    textoBotao.textContent =
                        'Arquivando...';

                    iconeBotao.className =
                        'bi bi-arrow-repeat';

                    btnExcluir.disabled =
                        true;

                    btnExcluir.style.pointerEvents =
                        'none';

                    btnExcluir.style.opacity =
                        '0.7';

                    /*
                    | Envia o formulário para o PHP.
                    */

                    formExcluir.submit();

                }
            );

        }

    </script>

</body>

</html>