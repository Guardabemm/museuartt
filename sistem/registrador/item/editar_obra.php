<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../backend/Config/conexao.php';

if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../public/index.php");
    exit();
}

/* =========================================================
   USUÁRIO LOGADO
========================================================= */

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['tipo'] ?? 'Funcionário';


/* =========================================================
   ORIGEM
========================================================= */

$origem = $_GET['origem'] ?? $_POST['origem'] ?? 'registrador';

switch ($origem) {

    case 'administrador':
        $paginaVoltar = '../../administrador/item/obras.php';
        break;

    case 'manipulador':
        $paginaVoltar = '../../manipulador/item/obras.php';
        break;

    case 'registrador':
    default:
        $origem = 'registrador';
        $paginaVoltar = '../../registrador/item/obras.php';
        break;
}


/* =========================================================
   CÓDIGO DA OBRA
   Aceita cod_item ou id
========================================================= */

$codItem =
    $_GET['cod_item']
    ?? $_GET['id']
    ?? $_POST['cod_item']
    ?? $_POST['id']
    ?? '';

if ($codItem === '' || !is_numeric($codItem)) {

    header(
        "Location: " .
        $paginaVoltar .
        "?erro=codigo_invalido"
    );

    exit();
}

$codItem = (int) $codItem;


/* =========================================================
   VARIÁVEIS
========================================================= */

$nomeItem = '';
$nomeAutor = '';
$dtCriacao = '';
$dimensao = '';
$material = '';
$estadoConservacao = '';
$requisitoConservacao = '';
$proveniencia = '';
$dtAquisicao = '';
$historicoProprietario = '';
$metodoAquisicao = '';
$descricao = '';
$statusObra = '';
$categoria = '';

$fotoItem = '';
$fotoAutor = '';
$fotoItem2 = '';
$fotoItem3 = '';
$fotoItem4 = '';

$erro = '';
$sucesso = false;


/* =========================================================
   VERIFICAÇÃO DA CONEXÃO
========================================================= */

if (!isset($strcon) || !($strcon instanceof mysqli)) {

    $erro = 'A conexão com o banco de dados não foi encontrada.';
}


/* =========================================================
   FUNÇÃO PARA FORMATAR DATAS
========================================================= */

function formatarDataInput($data)
{
    if (empty($data)) {
        return '';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return $data;
    }

    return date('Y-m-d', $timestamp);
}


/* =========================================================
   BUSCAR OBRA
========================================================= */

if (empty($erro)) {

    try {

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
                foto_item4
            FROM item
            WHERE cod_item = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($strcon, $sql);

        if (!$stmt) {
            throw new Exception(
                mysqli_error($strcon)
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            'i',
            $codItem
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }

        $resultado = mysqli_stmt_get_result($stmt);

        if (
            !$resultado ||
            mysqli_num_rows($resultado) === 0
        ) {

            mysqli_stmt_close($stmt);

            $erro =
                "Obra com código #$codItem não encontrada. " .
                "Verifique se o código está correto.";

        } else {

            $obra = mysqli_fetch_assoc($resultado);

            mysqli_stmt_close($stmt);


            /* =====================================================
               PREENCHER TODOS OS CAMPOS COM OS DADOS DO BANCO
            ===================================================== */

            $codItem =
                (int) ($obra['cod_item'] ?? $codItem);

            $nomeItem =
                $obra['nome_item'] ?? '';

            $nomeAutor =
                $obra['nome_autor'] ?? '';

            $dtCriacao =
                formatarDataInput(
                    $obra['dt_criacao'] ?? ''
                );

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
                formatarDataInput(
                    $obra['dt_aquisicao'] ?? ''
                );

            $historicoProprietario =
                $obra['historico_proprietario'] ?? '';

            $metodoAquisicao =
                $obra['metodo_aquisicao'] ?? '';

            $descricao =
                $obra['descricao'] ?? '';

            $statusObra =
                $obra['status_obra'] ?? '';

            $categoria =
                $obra['categoria'] ?? '';

            $fotoItem =
                $obra['foto_item'] ?? '';

            $fotoAutor =
                $obra['foto_autor'] ?? '';

            $fotoItem2 =
                $obra['foto_item2'] ?? '';

            $fotoItem3 =
                $obra['foto_item3'] ?? '';

            $fotoItem4 =
                $obra['foto_item4'] ?? '';
        }

    } catch (Throwable $e) {

        $erro =
            'Erro ao carregar a obra: ' .
            $e->getMessage();

        error_log(
            "Erro ao carregar obra: " .
            $e->getMessage()
        );
    }
}


/* =========================================================
   ATUALIZAR OBRA
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    empty($erro)
) {

    /*
     * Recarrega os valores enviados pelo formulário.
     */

    $nomeItem =
        trim($_POST['nome_item'] ?? '');

    $nomeAutor =
        trim($_POST['nome_autor'] ?? '');

    $dtCriacao =
        trim($_POST['dt_criacao'] ?? '');

    $dimensao =
        trim($_POST['dimensao'] ?? '');

    $material =
        trim($_POST['material'] ?? '');

    $estadoConservacao =
        trim($_POST['estado_conservacao'] ?? '');

    $requisitoConservacao =
        trim($_POST['requisito_conservacao'] ?? '');

    $proveniencia =
        trim($_POST['proveniencia'] ?? '');

    $dtAquisicao =
        trim($_POST['dt_aquisicao'] ?? '');

    $historicoProprietario =
        trim($_POST['historico_proprietario'] ?? '');

    $metodoAquisicao =
        trim($_POST['metodo_aquisicao'] ?? '');

    $descricao =
        trim($_POST['descricao'] ?? '');

    $statusObra =
        trim($_POST['status_obra'] ?? '');

    $categoria =
        trim($_POST['categoria'] ?? '');


    /* =====================================================
       VALIDAÇÕES
    ===================================================== */

    if ($nomeItem === '') {

        $erro = 'Informe o nome da obra.';

    } elseif ($nomeAutor === '') {

        $erro = 'Informe o nome do autor.';

    } elseif ($dtCriacao === '') {

        $erro = 'Informe a data de criação.';

    } elseif ($dimensao === '') {

        $erro = 'Informe as dimensões da obra.';

    } elseif ($material === '') {

        $erro = 'Informe o material da obra.';

    } elseif ($estadoConservacao === '') {

        $erro = 'Informe o estado de conservação.';

    } elseif ($proveniencia === '') {

        $erro = 'Informe a proveniência da obra.';

    } elseif ($dtAquisicao === '') {

        $erro = 'Informe a data de aquisição.';

    } elseif ($metodoAquisicao === '') {

        $erro = 'Informe o método de aquisição.';

    } elseif ($descricao === '') {

        $erro = 'Informe a descrição da obra.';

    } elseif ($statusObra === '') {

        $erro = 'Selecione o status da obra.';

    } elseif ($categoria === '') {

        $erro = 'Selecione uma categoria.';

    } else {

        try {

            /* =================================================
               FUNÇÃO PARA PROCESSAR IMAGENS
            ================================================= */

            function processarImagem(
                $campo,
                $imagemAtual
            ) {

                /*
                 * Se nenhuma imagem nova foi selecionada,
                 * mantém a imagem existente.
                 */

                if (
                    !isset($_FILES[$campo]) ||
                    $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE
                ) {

                    return $imagemAtual;
                }


                if (
                    $_FILES[$campo]['error'] !==
                    UPLOAD_ERR_OK
                ) {

                    throw new Exception(
                        "Erro ao enviar a imagem: " .
                        $campo
                    );
                }


                $arquivo = $_FILES[$campo];


                /* Limite de 5 MB */

                if (
                    $arquivo['size'] >
                    5 * 1024 * 1024
                ) {

                    throw new Exception(
                        "A imagem " .
                        $campo .
                        " não pode ultrapassar 5 MB."
                    );
                }


                /* Tipos permitidos */

                $tiposPermitidos = [

                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'

                ];


                $finfo =
                    finfo_open(FILEINFO_MIME_TYPE);

                $mime =
                    finfo_file(
                        $finfo,
                        $arquivo['tmp_name']
                    );

                finfo_close($finfo);


                if (
                    !isset(
                        $tiposPermitidos[$mime]
                    )
                ) {

                    throw new Exception(
                        "Formato de imagem inválido em " .
                        $campo .
                        "."
                    );
                }


                /* Pasta das imagens */

                $pasta =
                    '../../../assets/img/obras/';


                if (!is_dir($pasta)) {

                    mkdir(
                        $pasta,
                        0777,
                        true
                    );
                }


                /* Nome único */

                $nomeArquivo =
                    'obra_' .
                    uniqid() .
                    '.' .
                    $tiposPermitidos[$mime];


                $destino =
                    $pasta .
                    $nomeArquivo;


                if (
                    !move_uploaded_file(
                        $arquivo['tmp_name'],
                        $destino
                    )
                ) {

                    throw new Exception(
                        "Não foi possível salvar a imagem " .
                        $campo .
                        "."
                    );
                }


                /*
                 * Remove a imagem anterior.
                 */

                if (!empty($imagemAtual)) {

                    $arquivoAntigo =
                        $pasta .
                        basename($imagemAtual);

                    if (
                        file_exists(
                            $arquivoAntigo
                        )
                    ) {

                        @unlink(
                            $arquivoAntigo
                        );
                    }
                }


                return $nomeArquivo;
            }


            /* =================================================
               PROCESSAR IMAGENS
            ================================================= */

            $fotoItem =
                processarImagem(
                    'foto_item',
                    $fotoItem
                );

            $fotoAutor =
                processarImagem(
                    'foto_autor',
                    $fotoAutor
                );

            $fotoItem2 =
                processarImagem(
                    'foto_item_2',
                    $fotoItem2
                );

            $fotoItem3 =
                processarImagem(
                    'foto_item_3',
                    $fotoItem3
                );

            $fotoItem4 =
                processarImagem(
                    'foto_item_4',
                    $fotoItem4
                );


            /* =================================================
               UPDATE
            ================================================= */

            $sql = "

                UPDATE item

                SET

                    nome_item = ?,
                    nome_autor = ?,
                    dt_criacao = ?,
                    dimensao = ?,
                    material = ?,
                    estado_conservacao = ?,
                    requisito_conservacao = ?,
                    proveniencia = ?,
                    dt_aquisicao = ?,
                    historico_proprietario = ?,
                    metodo_aquisicao = ?,
                    descricao = ?,
                    foto_item = ?,
                    foto_autor = ?,
                    status_obra = ?,
                    categoria = ?,
                    foto_item2 = ?,
                    foto_item3 = ?,
                    foto_item4 = ?

                WHERE cod_item = ?

            ";


            $stmt =
                mysqli_prepare(
                    $strcon,
                    $sql
                );


            if (!$stmt) {

                throw new Exception(
                    mysqli_error($strcon)
                );
            }


            mysqli_stmt_bind_param(

                $stmt,

                'sssssssssssssssssssi',

                $nomeItem,
                $nomeAutor,
                $dtCriacao,
                $dimensao,
                $material,
                $estadoConservacao,
                $requisitoConservacao,
                $proveniencia,
                $dtAquisicao,
                $historicoProprietario,
                $metodoAquisicao,
                $descricao,
                $fotoItem,
                $fotoAutor,
                $statusObra,
                $categoria,
                $fotoItem2,
                $fotoItem3,
                $fotoItem4,
                $codItem

            );


            if (
                !mysqli_stmt_execute($stmt)
            ) {

                throw new Exception(
                    mysqli_stmt_error($stmt)
                );
            }


            mysqli_stmt_close($stmt);


            /*
             * Depois de salvar,
             * retorna para a lista correta.
             */

            header(
                "Location: " .
                $paginaVoltar .
                "?edicao=sucesso"
            );

            exit();

        } catch (Throwable $e) {

            $erro =
                'Erro ao atualizar a obra: ' .
                $e->getMessage();
        }
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
        Editar obra - MuseuArt
    </title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="../../../assets/css/design/CRUDS/editar_obra.css"
    >

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="crud-sidebar">

    <div class="crud-logo">

        <img
            src="/SistemaMuseuArt.GuardaBem/assets/img/logomenu.svg"
            alt="MuseuArt"
        >

    </div>


    <div class="crud-titulo">

        <span>AÇÕES</span>

    </div>


    <nav class="crud-menu">

        <a
            href="#"
            class="crud-item criar ativo"
        >

            <i class="bi bi-pencil-square"></i>

            <span>
                Editar obra
            </span>

        </a>

    </nav>


    <div class="crud-voltar">

        <a
            href="<?= htmlspecialchars($paginaVoltar) ?>"
        >

            <i class="bi bi-arrow-left"></i>

            <span>
                Sair
            </span>

        </a>

    </div>

</aside>


<!-- =====================================================
     CONTEÚDO
===================================================== -->

<main class="ala">


    <!-- TOPO -->

    <div class="ala-topo">

        <div class="breadcrumb">

            <a
                href="<?= htmlspecialchars($paginaVoltar) ?>"
            >
                Obras
            </a>

            <i class="bi bi-chevron-right"></i>

            <strong>
                Editar obra
            </strong>

        </div>


        <a
            href="<?= htmlspecialchars($paginaVoltar) ?>"
            class="btn-voltar"
        >

            <i class="bi bi-arrow-left"></i>

            Voltar para a lista

        </a>

    </div>


    <!-- CABEÇALHO -->

    <div class="ala-cabecalho">

        <div>

            <span class="ala-label">
                EDIÇÃO
            </span>

            <h1>
                Editar obra
            </h1>

            <p>
                Altere as informações da obra abaixo.
                O código da obra não pode ser alterado.
            </p>

        </div>

    </div>


    <!-- CONTEÚDO -->

    <section class="ala-conteudo">


        <!-- =================================================
             FORMULÁRIO
        ================================================= -->

        <form
            method="POST"
            action="?cod_item=<?= urlencode((string)$codItem) ?>&origem=<?= urlencode($origem) ?>"
            enctype="multipart/form-data"
            class="ala-form"
        >


            <!-- Código -->

            <input
                type="hidden"
                name="cod_item"
                value="<?= (int)$codItem ?>"
            >


            <!-- Origem -->

            <input
                type="hidden"
                name="origem"
                value="<?= htmlspecialchars(
                    $origem,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >


            <!-- =================================================
                 IDENTIFICAÇÃO
            ================================================= -->

            <div class="card">

                <div class="card-titulo">

                    <div class="card-icone">

                        <i class="bi bi-info-circle"></i>

                    </div>

                    <div>

                        <h2>
                            Identificação da obra
                        </h2>

                        <p>
                            Informações básicas para identificar
                            a obra no acervo.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- CÓDIGO -->

                    <div class="campo">

                        <label for="cod_item">
                            Código da obra
                        </label>

                        <div class="input-container">

                            <i class="bi bi-upc-scan"></i>

                            <input
                                type="text"
                                id="cod_item"
                                value="<?= htmlspecialchars(
                                    (string)$codItem,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                readonly
                                aria-readonly="true"
                            >

                        </div>

                    </div>


                    <!-- CATEGORIA -->

                    <div class="campo">

                        <label for="categoria">

                            Categoria

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-tag"></i>

                            <select
                                id="categoria"
                                name="categoria"
                                required
                            >

                                <option value="">
                                    Selecione uma categoria
                                </option>

                                <option
                                    value="Quadro"
                                    <?= $categoria === 'Quadro'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Quadro
                                </option>

                                <option
                                    value="Escultura"
                                    <?= $categoria === 'Escultura'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Escultura
                                </option>

                                <option
                                    value="Pintura"
                                    <?= $categoria === 'Pintura'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Pintura
                                </option>

                                <option
                                    value="Fotografia"
                                    <?= $categoria === 'Fotografia'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Fotografia
                                </option>

                                <option
                                    value="Taxidermia"
                                    <?= $categoria === 'Taxidermia'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Taxidermia
                                </option>

                                <option
                                    value="Conservação"
                                    <?= $categoria === 'Conservação'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Animal em conservação
                                </option>

                                <option
                                    value="Fóssil"
                                    <?= $categoria === 'Fóssil'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Fóssil
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- NOME -->

                    <div class="campo campo-grande">

                        <label for="nome_item">

                            Nome da obra

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-card-heading"></i>

                            <input
                                type="text"
                                id="nome_item"
                                name="nome_item"
                                value="<?= htmlspecialchars(
                                    $nomeItem,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="255"
                                required
                            >

                        </div>

                    </div>


                    <!-- AUTOR -->

                    <div class="campo campo-grande">

                        <label for="nome_autor">

                            Nome do autor

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-person"></i>

                            <input
                                type="text"
                                id="nome_autor"
                                name="nome_autor"
                                value="<?= htmlspecialchars(
                                    $nomeAutor,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="255"
                                required
                            >

                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="campo">

                        <label for="status_obra">
                            Status da obra
                        </label>

                        <div class="input-container">

                            <i class="bi bi-circle"></i>

                            <select
                                id="status_obra"
                                name="status_obra"
                                required
                            >

                                <option value="">
                                    Selecione
                                </option>

                                <option
                                    value="Em exposição"
                                    <?= $statusObra === 'Em exposição'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Em exposição
                                </option>

                                <option
                                    value="Em restauração"
                                    <?= $statusObra === 'Em restauração'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Em restauração
                                </option>

                                <option
                                    value="Reserva técnica"
                                    <?= $statusObra === 'Reserva técnica'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Reserva técnica
                                </option>

                                <option
                                    value="Indisponível"
                                    <?= $statusObra === 'Indisponível'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Indisponível
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 CARACTERÍSTICAS
            ================================================= -->

            <div class="card">

                <div class="card-titulo">

                    <div class="card-icone">

                        <i class="bi bi-brush"></i>

                    </div>

                    <div>

                        <h2>
                            Características
                        </h2>

                        <p>
                            Informações técnicas e físicas
                            da obra.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- DATA CRIAÇÃO -->

                    <div class="campo">

                        <label for="dt_criacao">

                            Data de criação

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-calendar"></i>

                            <input
                                type="date"
                                id="dt_criacao"
                                name="dt_criacao"
                                value="<?= htmlspecialchars(
                                    $dtCriacao,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- MATERIAL -->

                    <div class="campo">

                        <label for="material">

                            Material

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-brush"></i>

                            <input
                                type="text"
                                id="material"
                                name="material"
                                value="<?= htmlspecialchars(
                                    $material,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="255"
                                required
                            >

                        </div>

                    </div>


                    <!-- DIMENSÃO -->

                    <div class="campo">

                        <label for="dimensao">

                            Dimensões

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-arrows-angle-expand"></i>

                            <input
                                type="text"
                                id="dimensao"
                                name="dimensao"
                                value="<?= htmlspecialchars(
                                    $dimensao,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="255"
                                required
                            >

                        </div>

                    </div>


                    <!-- ESTADO -->

                    <div class="campo">

                        <label for="estado_conservacao">

                            Estado de conservação

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-shield-check"></i>

                            <select
                                id="estado_conservacao"
                                name="estado_conservacao"
                                required
                            >

                                <option value="">
                                    Selecione
                                </option>

                                <option
                                    value="Excelente"
                                    <?= $estadoConservacao === 'Excelente'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Excelente
                                </option>

                                <option
                                    value="Bom"
                                    <?= $estadoConservacao === 'Bom'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Bom
                                </option>

                                <option
                                    value="Regular"
                                    <?= $estadoConservacao === 'Regular'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Regular
                                </option>

                                <option
                                    value="Ruim"
                                    <?= $estadoConservacao === 'Ruim'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Ruim
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- REQUISITO -->

                    <div class="campo campo-full">

                        <label for="requisito_conservacao">

                            Requisito de conservação

                        </label>

                        <div class="textarea-container">

                            <i class="bi bi-shield"></i>

                            <textarea
                                id="requisito_conservacao"
                                name="requisito_conservacao"
                                rows="5"
                                maxlength="5000"
                                placeholder="Informe os requisitos de conservação..."
                            ><?= htmlspecialchars(
                                $requisitoConservacao,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 AQUISIÇÃO
            ================================================= -->

            <div class="card">

                <div class="card-titulo">

                    <div class="card-icone">

                        <i class="bi bi-briefcase"></i>

                    </div>

                    <div>

                        <h2>
                            Aquisição e proveniência
                        </h2>

                        <p>
                            Histórico e informações sobre a
                            aquisição da obra.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- DATA -->

                    <div class="campo">

                        <label for="dt_aquisicao">

                            Data de aquisição

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-calendar-event"></i>

                            <input
                                type="date"
                                id="dt_aquisicao"
                                name="dt_aquisicao"
                                value="<?= htmlspecialchars(
                                    $dtAquisicao,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- MÉTODO -->

                    <div class="campo">

                        <label for="metodo_aquisicao">

                            Método de aquisição

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-arrow-down-circle"></i>

                            <input
                                type="text"
                                id="metodo_aquisicao"
                                name="metodo_aquisicao"
                                value="<?= htmlspecialchars(
                                    $metodoAquisicao,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="255"
                                required
                            >

                        </div>

                    </div>


                    <!-- PROVENIÊNCIA -->

                    <div class="campo campo-grande">

                        <label for="proveniencia">

                            Proveniência

                            <span>*</span>

                        </label>

                        <div class="input-container">

                            <i class="bi bi-geo-alt"></i>

                            <input
                                type="text"
                                id="proveniencia"
                                name="proveniencia"
                                value="<?= htmlspecialchars(
                                    $proveniencia,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="255"
                                required
                            >

                        </div>

                    </div>


                    <!-- HISTÓRICO -->

                    <div class="campo campo-full">

                        <label for="historico_proprietario">

                            Histórico do proprietário

                        </label>

                        <div class="textarea-container">

                            <i class="bi bi-clock-history"></i>

                            <textarea
                                id="historico_proprietario"
                                name="historico_proprietario"
                                rows="5"
                                maxlength="5000"
                                placeholder="Informe o histórico de proprietários da obra..."
                            ><?= htmlspecialchars(
                                $historicoProprietario,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 DESCRIÇÃO
            ================================================= -->

            <div class="card">

                <div class="card-titulo">

                    <div class="card-icone">

                        <i class="bi bi-card-text"></i>

                    </div>

                    <div>

                        <h2>
                            Descrição
                        </h2>

                        <p>
                            Informações detalhadas sobre a obra.
                        </p>

                    </div>

                </div>


                <div class="campo">

                    <label for="descricao">

                        Descrição

                        <span>*</span>

                    </label>

                    <div class="textarea-container">

                        <i class="bi bi-card-text"></i>

                        <textarea
                            id="descricao"
                            name="descricao"
                            rows="8"
                            maxlength="255"
                            required
                        ><?= htmlspecialchars(
                            $descricao,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 IMAGENS
            ================================================= -->

            <div class="card">

                <div class="card-titulo">

                    <div class="card-icone">

                        <i class="bi bi-images"></i>

                    </div>

                    <div>

                        <h2>
                            Imagens
                        </h2>

                        <p>
                            Substitua as imagens da obra quando necessário.
                            Caso não selecione uma nova imagem, a atual será mantida.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- FOTO ITEM -->

                    <div class="campo campo-full">

                        <label for="foto_item">
                            Imagem principal da obra
                        </label>

                        <?php if (!empty($fotoItem)): ?>

                            <div class="imagem-atual">

                                <span>
                                    Imagem atual:
                                </span>

                                <img
                                    src="../../../assets/img/obras/<?= htmlspecialchars(
                                        basename($fotoItem),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="Imagem atual da obra"
                                >

                            </div>

                        <?php endif; ?>


                        <div class="input-container">

                            <i class="bi bi-image"></i>

                            <input
                                type="file"
                                id="foto_item"
                                name="foto_item"
                                accept="image/jpeg,image/png,image/webp"
                            >

                        </div>

                    </div>


                    <!-- FOTO AUTOR -->

                    <div class="campo campo-full">

                        <label for="foto_autor">
                            Foto do autor
                        </label>

                        <?php if (!empty($fotoAutor)): ?>

                            <div class="imagem-atual">

                                <span>
                                    Imagem atual:
                                </span>

                                <img
                                    src="../../../assets/img/obras/<?= htmlspecialchars(
                                        basename($fotoAutor),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="Foto atual do autor"
                                >

                            </div>

                        <?php endif; ?>


                        <div class="input-container">

                            <i class="bi bi-person-square"></i>

                            <input
                                type="file"
                                id="foto_autor"
                                name="foto_autor"
                                accept="image/jpeg,image/png,image/webp"
                            >

                        </div>

                    </div>


                    <!-- FOTO 2 -->

                    <div class="campo">

                        <label for="foto_item_2">
                            Imagem adicional 2
                        </label>

                        <?php if (!empty($fotoItem2)): ?>

                            <div class="imagem-atual">

                                <span>
                                    Imagem atual:
                                </span>

                                <img
                                    src="../../../assets/img/obras/<?= htmlspecialchars(
                                        basename($fotoItem2),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="Imagem adicional 2"
                                >

                            </div>

                        <?php endif; ?>


                        <div class="input-container">

                            <i class="bi bi-image"></i>

                            <input
                                type="file"
                                id="foto_item_2"
                                name="foto_item_2"
                                accept="image/jpeg,image/png,image/webp"
                            >

                        </div>

                    </div>


                    <!-- FOTO 3 -->

                    <div class="campo">

                        <label for="foto_item_3">
                            Imagem adicional 3
                        </label>

                        <?php if (!empty($fotoItem3)): ?>

                            <div class="imagem-atual">

                                <span>
                                    Imagem atual:
                                </span>

                                <img
                                    src="../../../assets/img/obras/<?= htmlspecialchars(
                                        basename($fotoItem3),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="Imagem adicional 3"
                                >

                            </div>

                        <?php endif; ?>


                        <div class="input-container">

                            <i class="bi bi-image"></i>

                            <input
                                type="file"
                                id="foto_item_3"
                                name="foto_item_3"
                                accept="image/jpeg,image/png,image/webp"
                            >

                        </div>

                    </div>


                    <!-- FOTO 4 -->

                    <div class="campo">

                        <label for="foto_item_4">
                            Imagem adicional 4
                        </label>

                        <?php if (!empty($fotoItem4)): ?>

                            <div class="imagem-atual">

                                <span>
                                    Imagem atual:
                                </span>

                                <img
                                    src="../../../assets/img/obras/<?= htmlspecialchars(
                                        basename($fotoItem4),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="Imagem adicional 4"
                                >

                            </div>

                        <?php endif; ?>


                        <div class="input-container">

                            <i class="bi bi-image"></i>

                            <input
                                type="file"
                                id="foto_item_4"
                                name="foto_item_4"
                                accept="image/jpeg,image/png,image/webp"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 MENSAGEM
            ================================================= -->

            <?php if ($sucesso): ?>

                <div class="mensagem sucesso">

                    <i class="bi bi-check-circle"></i>

                    <span>

                        Obra

                        <strong>
                            <?= htmlspecialchars(
                                $codItem
                            ) ?>
                        </strong>

                        atualizada com sucesso!

                    </span>

                </div>

            <?php endif; ?>


            <?php if (!empty($erro)): ?>

                <div class="mensagem erro">

                    <i class="bi bi-exclamation-circle"></i>

                    <span>

                        <?= htmlspecialchars(
                            $erro,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 BOTÕES
            ================================================= -->

            <div class="form-acoes">

                <a
                    href="<?= htmlspecialchars(
                        $paginaVoltar
                    ) ?>"
                    class="btn-cancelar"
                >

                    <i class="bi bi-x-lg"></i>

                    Cancelar

                </a>


                <button
                    type="submit"
                    class="btn-cadastrar"
                >

                    <i class="bi bi-check-lg"></i>

                    Salvar alterações

                </button>

            </div>

        </form>


        <!-- =================================================
             LATERAL
        ================================================= -->

        <aside class="ala-lateral">


            <div class="card resumo-cadastro">

                <h2>

                    <i class="bi bi-pencil-square"></i>

                    Editando uma obra

                </h2>

                <p>

                    Você está editando uma obra já cadastrada
                    no acervo. Altere somente as informações
                    que precisam ser corrigidas ou atualizadas.

                </p>

            </div>


            <div class="card cadastro-info">

                <h2>

                    <i class="bi bi-person-check"></i>

                    Responsável pela edição

                </h2>


                <div class="responsavel">

                    <div class="responsavel-avatar">

                        <i class="bi bi-person"></i>

                    </div>


                    <div class="responsavel-dados">

                        <span>
                            Editado por
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $nomeUsuario,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <small>
                            <?= htmlspecialchars(
                                $tipoUsuario,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </small>

                    </div>

                </div>

            </div>


            <div class="card campos-obrigatorios">

                <h2>

                    <i class="bi bi-lock"></i>

                    Código da obra

                </h2>

                <p>

                    O código

                    <strong>
                        #<?= htmlspecialchars(
                            (string)$codItem,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>

                    é gerado pelo sistema e não pode
                    ser alterado durante a edição.

                </p>

            </div>


        </aside>

    </section>

</main>

</body>

</html>