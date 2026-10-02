<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . "/../../../backend/Config/conexao.php";


/** @var mysqli $strcon */


mysqli_set_charset($strcon, 'utf8mb4');


$mensagem = '';
$tipoMensagem = '';


$codAlaOriginal = trim($_GET['cod_ala'] ?? '');


$cod_ala = '';
$nome = '';
$cor = '#ffffff';
$descricao = '';
$area = '';
$status = '';
$dt_alocacao = '';
$imagemAtual = '';


$pastaImagem = __DIR__ . "/../../../assets/img/alas/";
$urlImagem = "../../../assets/img/alas/";


if (!is_dir($pastaImagem)) {
    mkdir($pastaImagem, 0777, true);
}


if ($codAlaOriginal === '') {


    $mensagem = 'Código da ala não informado.';
    $tipoMensagem = 'erro';


} else {


    $sqlBusca = "
        SELECT
            cod_ala,
            nome,
            cor,
            descricao,
            area,
            status,
            imagem_capa,
            dt_alocacao
        FROM alocacao
        WHERE cod_ala = ?
        LIMIT 1
    ";


    $stmtBusca = mysqli_prepare($strcon, $sqlBusca);


    if ($stmtBusca) {


        mysqli_stmt_bind_param(
            $stmtBusca,
            "s",
            $codAlaOriginal
        );


        mysqli_stmt_execute($stmtBusca);


        $resultado = mysqli_stmt_get_result($stmtBusca);


        if ($resultado && mysqli_num_rows($resultado) > 0) {


            $ala = mysqli_fetch_assoc($resultado);


            $cod_ala = $ala['cod_ala'];
            $nome = $ala['nome'];
            $cor = $ala['cor'] ?: '#ffffff';
            $descricao = $ala['descricao'] ?? '';
            $area = $ala['area'] ?? '';
            $status = $ala['status'] ?? '';
            $imagemAtual = $ala['imagem_capa'] ?? '';


            if (!empty($ala['dt_alocacao'])) {


                $dt = new DateTime($ala['dt_alocacao']);


                $dt_alocacao = $dt->format('Y-m-d\TH:i');
            }


        } else {


            $mensagem = 'Ala não encontrada.';
            $tipoMensagem = 'erro';
        }


        mysqli_stmt_close($stmtBusca);


    } else {


        $mensagem = 'Não foi possível consultar a ala.';
        $tipoMensagem = 'erro';
    }
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $codAlaOriginal !== ''
) {


    $cod_ala = trim($_POST['cod_ala'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $cor = trim($_POST['cor'] ?? '#ffffff');
    $descricao = trim($_POST['descricao'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $dt_alocacao = trim($_POST['dt_alocacao'] ?? '');


    $removerImagem = (
        isset($_POST['remover_imagem']) &&
        $_POST['remover_imagem'] === '1'
    );


    if (
        $cod_ala === '' ||
        $nome === '' ||
        $status === ''
    ) {


        $mensagem = 'Preencha todos os campos obrigatórios.';
        $tipoMensagem = 'erro';


    } elseif (strlen($cod_ala) > 10) {


        $mensagem = 'O código da ala deve possuir no máximo 10 caracteres.';
        $tipoMensagem = 'erro';


    } elseif (strlen($nome) > 255) {


        $mensagem = 'O nome da ala deve possuir no máximo 255 caracteres.';
        $tipoMensagem = 'erro';


    } else {


        $sqlVerifica = "
            SELECT cod_ala
            FROM alocacao
            WHERE cod_ala = ?
            AND cod_ala <> ?
            LIMIT 1
        ";


        $stmtVerifica = mysqli_prepare(
            $strcon,
            $sqlVerifica
        );


        if (!$stmtVerifica) {


            $mensagem = 'Não foi possível validar o código da ala.';
            $tipoMensagem = 'erro';


        } else {


            mysqli_stmt_bind_param(
                $stmtVerifica,
                "ss",
                $cod_ala,
                $codAlaOriginal
            );


            mysqli_stmt_execute($stmtVerifica);


            mysqli_stmt_store_result($stmtVerifica);


            if (mysqli_stmt_num_rows($stmtVerifica) > 0) {


                $mensagem = 'Já existe outra ala cadastrada com esse código.';
                $tipoMensagem = 'erro';


                mysqli_stmt_close($stmtVerifica);


            } else {


                mysqli_stmt_close($stmtVerifica);


                $nomeImagem = $imagemAtual;
                $novoArquivo = null;


                if ($removerImagem) {
                    $nomeImagem = null;
                }


                if (
                    isset($_FILES['imagem_capa']) &&
                    $_FILES['imagem_capa']['error'] !== UPLOAD_ERR_NO_FILE
                ) {


                    if (
                        $_FILES['imagem_capa']['error'] !== UPLOAD_ERR_OK
                    ) {


                        $mensagem = 'Ocorreu um erro ao enviar a imagem.';
                        $tipoMensagem = 'erro';


                    } else {


                        $arquivo = $_FILES['imagem_capa'];


                        $tamanhoMaximo = 5 * 1024 * 1024;


                        if ($arquivo['size'] > $tamanhoMaximo) {


                            $mensagem = 'A imagem deve possuir no máximo 5 MB.';
                            $tipoMensagem = 'erro';


                        } else {


                            $tiposPermitidos = [
                                'image/jpeg' => 'jpg',
                                'image/png' => 'png',
                                'image/webp' => 'webp'
                            ];


                            $finfo = finfo_open(FILEINFO_MIME_TYPE);


                            $mime = finfo_file(
                                $finfo,
                                $arquivo['tmp_name']
                            );


                            finfo_close($finfo);


                            if (!isset($tiposPermitidos[$mime])) {


                                $mensagem =
                                    'Formato de imagem inválido. Utilize JPG, PNG ou WEBP.';


                                $tipoMensagem = 'erro';


                            } else {


                                $extensao = $tiposPermitidos[$mime];


                                $codigoArquivo = preg_replace(
                                    '/[^a-zA-Z0-9_-]/',
                                    '',
                                    $cod_ala
                                );


                                $nomeImagem =
                                    'ala_' .
                                    $codigoArquivo .
                                    '_' .
                                    time() .
                                    '.' .
                                    $extensao;


                                $destino =
                                    $pastaImagem .
                                    $nomeImagem;


                                if (
                                    !move_uploaded_file(
                                        $arquivo['tmp_name'],
                                        $destino
                                    )
                                ) {


                                    $mensagem =
                                        'Não foi possível salvar a imagem.';


                                    $tipoMensagem = 'erro';


                                    $nomeImagem = $imagemAtual;


                                } else {


                                    $novoArquivo = $destino;
                                }
                            }
                        }
                    }
                }


                if ($tipoMensagem !== 'erro') {


                    $areaBanco = null;


                    if ($area !== '') {
                        $areaBanco = str_replace(',', '.', $area);
                    }


                    $dtBanco = null;


                    if ($dt_alocacao !== '') {


                        $dtBanco = str_replace(
                            'T',
                            ' ',
                            $dt_alocacao
                        );


                        if (strlen($dtBanco) === 16) {
                            $dtBanco .= ':00';
                        }
                    }


                    $sql = "
                        UPDATE alocacao
                        SET
                            cod_ala = ?,
                            nome = ?,
                            cor = ?,
                            descricao = ?,
                            area = ?,
                            status = ?,
                            imagem_capa = ?,
                            dt_alocacao = ?
                        WHERE cod_ala = ?
                        LIMIT 1
                    ";


                    $stmt = mysqli_prepare(
                        $strcon,
                        $sql
                    );


                    if (!$stmt) {


                        if (
                            $novoArquivo !== null &&
                            file_exists($novoArquivo)
                        ) {
                            unlink($novoArquivo);
                        }


                        $mensagem =
                            'Não foi possível preparar a atualização.';


                        $tipoMensagem = 'erro';


                    } else {


                        mysqli_stmt_bind_param(
                            $stmt,
                            "sssssssss",
                            $cod_ala,
                            $nome,
                            $cor,
                            $descricao,
                            $areaBanco,
                            $status,
                            $nomeImagem,
                            $dtBanco,
                            $codAlaOriginal
                        );


                        if (mysqli_stmt_execute($stmt)) {


                            if (
                                !empty($imagemAtual) &&
                                (
                                    $removerImagem ||
                                    $novoArquivo !== null
                                )
                            ) {


                                $imagemAntiga =
                                    $pastaImagem .
                                    basename($imagemAtual);


                                if (
                                    file_exists($imagemAntiga) &&
                                    is_file($imagemAntiga)
                                ) {
                                    unlink($imagemAntiga);
                                }
                            }


                            mysqli_stmt_close($stmt);


                            header(
                                "Location: alas.php?edicao=sucesso"
                            );


                            exit;


                        } else {


                            if (
                                $novoArquivo !== null &&
                                file_exists($novoArquivo)
                            ) {
                                unlink($novoArquivo);
                            }


                            mysqli_stmt_close($stmt);


                            header(
                                "Location: /alas.php?edicao=erro"
                            );


                            exit;
                        }
                    }
                }
            }
        }
    }
}


$imagemUrl = '';


if (!empty($imagemAtual)) {


    $imagemUrl =
        $urlImagem .
        rawurlencode(
            basename($imagemAtual)
        );
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


    <title>Editar ala | Museu Art</title>


    <link
        rel="stylesheet"
        href="../../../assets/css/design/CRUDS/editar_ala.css"
    >


</head>


<body>


<div class="pagina-cadastro">


    <header class="topo">


        <div class="topo-esquerda">


            <a
                href="javascript:history.back()"
                class="btn-voltar"
            >
                ←
            </a>


            <div>


                <span class="caminho">
                    Alas / Editar
                </span>


                <h1>
                    Editar ala
                </h1>


                <p>
                    Altere as informações da ala cadastrada no Museu Art.
                </p>


            </div>


        </div>


        <a
            href="javascript:history.back()"
            class="btn-cancelar"
        >
            Cancelar
        </a>


    </header>


    <?php if ($mensagem !== ''): ?>


        <div class="mensagem <?= htmlspecialchars($tipoMensagem) ?>">


            <span class="mensagem-icone">
                <?= $tipoMensagem === 'sucesso' ? '✓' : '!' ?>
            </span>


            <span>
                <?= htmlspecialchars($mensagem) ?>
            </span>


        </div>


    <?php endif; ?>


    <main class="conteudo">


        <section class="card formulario-card">


            <div class="card-titulo">


                <div>


                    <span class="numero">
                        01
                    </span>


                    <div>


                        <h2>
                            Informações da ala
                        </h2>


                        <p>
                            Identificação e características principais.
                        </p>


                    </div>


                </div>


            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
                id="formAla"
            >


                <div class="linha">


                    <div class="campo">


                        <label for="nome">
                            Nome da ala
                            <span>*</span>
                        </label>


                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            maxlength="255"
                            placeholder="Ex.: Ala do Renascimento"
                            value="<?= htmlspecialchars($nome) ?>"
                            required
                        >


                        <small>
                            Nome que será apresentado aos visitantes.
                        </small>


                    </div>


                </div>


                <div class="campo">


                    <label for="cor">
                        Cor da ala
                        <span>*</span>
                    </label>


                    <div class="cor-container">


                        <input
                            type="color"
                            id="cor"
                            name="cor"
                            value="<?= htmlspecialchars($cor) ?>"
                        >


                        <input
                            type="text"
                            id="codigoCor"
                            value="<?= htmlspecialchars($cor) ?>"
                            maxlength="7"
                            placeholder="#ffffff"
                        >


                    </div>


                    <small>
                        Essa cor será utilizada para identificação visual da ala.
                    </small>


                </div>


                <div class="campo">


                    <label for="descricao">
                        Descrição
                    </label>


                    <textarea
                        id="descricao"
                        name="descricao"
                        maxlength="1000"
                        placeholder="Descreva a temática, proposta ou conceito desta ala..."
                    ><?= htmlspecialchars($descricao) ?></textarea>


                    <div class="contador">


                        <span id="contadorDescricao">
                            <?= strlen($descricao) ?>
                        </span>/1000


                    </div>


                </div>


                <div class="linha">


                    <div class="campo">


                        <label for="area">
                            Área da ala
                        </label>


                        <div class="campo-medida">


                            <input
                                type="number"
                                id="area"
                                name="area"
                                min="0"
                                step="0.01"
                                placeholder="Ex.: 125.50"
                                value="<?= htmlspecialchars($area) ?>"
                            >


                            <span>
                                m²
                            </span>


                        </div>


                        <small>
                            Área total ocupada pela ala.
                        </small>


                    </div>


                    <div class="campo">


                        <label for="status">
                            Status
                            <span>*</span>
                        </label>


                        <select
                            id="status"
                            name="status"
                            required
                        >


                            <option value="">
                                Selecione o status
                            </option>


                            <option
                                value="Ativa"
                                <?= $status === 'Ativa' ? 'selected' : '' ?>
                            >
                                Ativa
                            </option>


                            <option
                                value="Fechada"
                                <?= $status === 'Fechada' ? 'selected' : '' ?>
                            >
                                Fechada
                            </option>


                            <option
                                value="Em manutenção"
                                <?= $status === 'Em manutenção' ? 'selected' : '' ?>
                            >
                                Em manutenção
                            </option>


                        </select>


                        <small>
                            Situação atual da ala.
                        </small>


                    </div>


                </div>


                <div class="campo">


                    <label>
                        Imagem de capa
                    </label>


                    <div
                        class="upload-area"
                        id="uploadArea"
                    >


                        <input
                            type="file"
                            id="imagem_capa"
                            name="imagem_capa"
                            accept="image/jpeg,image/png,image/webp"
                            hidden
                        >


                        <input
                            type="hidden"
                            name="remover_imagem"
                            id="removerImagemInput"
                            value="0"
                        >


                        <div
                            class="upload-placeholder"
                            id="uploadPlaceholder"
                            style="<?= !empty($imagemUrl) ? 'display:none;' : '' ?>"
                        >


                            <div class="upload-icone">
                                ↑
                            </div>


                            <strong>
                                Arraste e solte uma imagem aqui
                            </strong>


                            <span>
                                ou clique para selecionar
                            </span>


                            <small>
                                JPG, PNG ou WEBP · Máximo de 5 MB
                            </small>


                        </div>


                        <div
                            class="preview-upload"
                            id="previewUpload"
                            style="<?= !empty($imagemUrl) ? 'display:block;' : 'display:none;' ?>"
                        >


                            <img
                                id="imagemPreview"
                                src="<?= htmlspecialchars($imagemUrl) ?>"
                                alt="Pré-visualização da imagem"
                            >


                            <button
                                type="button"
                                id="removerImagem"
                            >
                                ×
                            </button>


                        </div>


                    </div>


                </div>


                <div class="campo">


                    <label for="dt_alocacao">
                        Data de alocação
                    </label>


                    <input
                        type="datetime-local"
                        id="dt_alocacao"
                        name="dt_alocacao"
                        value="<?= htmlspecialchars($dt_alocacao) ?>"
                    >


                    <small>
                        Data e hora em que a ala foi criada ou alocada.
                    </small>


                </div>


                <div class="acoes">


                    <button
                        type="button"
                        class="btn-limpar"
                        id="btnLimpar"
                    >
                        Restaurar dados
                    </button>


                    <button
                        type="submit"
                        class="btn-cadastrar"
                    >
                        Salvar alterações
                    </button>


                </div>


            </form>


        </section>


        <aside class="card preview-card">


            <div class="preview-cabecalho">


                <div>


                    <span class="etiqueta">
                        PRÉ-VISUALIZAÇÃO
                    </span>


                    <h2>
                        Como a ala ficará
                    </h2>


                </div>


            </div>


            <div
                class="ala-preview"
                id="alaPreview"
            >


                <div
                    class="preview-imagem"
                    id="previewImagemContainer"
                >


                    <div
                        class="preview-sem-imagem"
                        style="<?= !empty($imagemUrl) ? 'display:none;' : '' ?>"
                    >


                        <span>
                            +
                        </span>


                        <p>
                            Imagem da ala
                        </p>


                    </div>


                    <img
                        id="previewImagem"
                        src="<?= htmlspecialchars($imagemUrl) ?>"
                        alt="<?= htmlspecialchars($nome) ?>"
                        style="<?= !empty($imagemUrl) ? 'display:block;' : 'display:none;' ?>"
                    >


                    <div class="preview-overlay"></div>


                    <span
                        class="preview-status"
                        id="previewStatus"
                    >
                        <?= htmlspecialchars($status ?: 'STATUS') ?>
                    </span>


                    <div class="preview-texto">


                        <h3 id="previewNome">
                            <?= htmlspecialchars($nome ?: 'Nome da Ala') ?>
                        </h3>


                        <p id="previewDescricao">
                            <?= htmlspecialchars(
                                $descricao ?: 'A descrição da ala aparecerá aqui.'
                            ) ?>
                        </p>


                    </div>


                </div>


                <div class="preview-informacoes">


                    <div class="info-item">


                        <span class="info-label">
                            Cor
                        </span>


                        <div class="cor-preview">


                            <span id="bolinhaCor"></span>


                            <strong id="infoCor">
                                <?= htmlspecialchars($cor) ?>
                            </strong>


                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">
                            Área
                        </span>


                        <strong id="infoArea">


                            <?= $area !== ''
                                ? htmlspecialchars(
                                    str_replace('.', ',', $area)
                                ) . ' m²'
                                : '— m²'
                            ?>


                        </strong>


                    </div>


                    <div class="info-item">


                        <span class="info-label">
                            Status
                        </span>


                        <strong
                            class="status-preview"
                            id="infoStatus"
                        >
                            <?= htmlspecialchars($status ?: '—') ?>
                        </strong>


                    </div>


                    <div class="info-item">


                        <span class="info-label">
                            Data de Criação
                        </span>


                        <strong id="infoData">
                            —
                        </strong>


                    </div>


                </div>


            </div>


        </aside>


    </main>


</div>


<script>


const form = document.getElementById('formAla');


const codigo = document.getElementById('cod_ala');
const nome = document.getElementById('nome');
const cor = document.getElementById('cor');
const codigoCor = document.getElementById('codigoCor');
const descricao = document.getElementById('descricao');
const area = document.getElementById('area');
const status = document.getElementById('status');
const data = document.getElementById('dt_alocacao');


const imagemInput = document.getElementById('imagem_capa');
const previewImagem = document.getElementById('previewImagem');
const previewUpload = document.getElementById('previewUpload');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
const uploadArea = document.getElementById('uploadArea');
const imagemPreview = document.getElementById('imagemPreview');
const removerImagem = document.getElementById('removerImagem');
const removerImagemInput = document.getElementById('removerImagemInput');


const contadorDescricao =
    document.getElementById('contadorDescricao');


const dadosOriginais = {


    codigo: <?= json_encode($cod_ala) ?>,


    nome: <?= json_encode($nome) ?>,


    cor: <?= json_encode($cor) ?>,


    descricao: <?= json_encode($descricao) ?>,


    area: <?= json_encode($area) ?>,


    status: <?= json_encode($status) ?>,


    data: <?= json_encode($dt_alocacao) ?>,


    imagem: <?= json_encode($imagemUrl) ?>


};


function atualizarPreview() {


    const valorCodigo =
        codigo.value.trim() || 'ALA001';


    const valorNome =
        nome.value.trim() || 'Nome da Ala';


    const valorDescricao =
        descricao.value.trim() ||
        'A descrição da ala aparecerá aqui.';


    const valorArea =
        area.value.trim();


    const valorStatus =
        status.value;


    const valorCor =
        cor.value || '#ffffff';


    document.getElementById('previewCodigo').textContent =
        valorCodigo;


    document.getElementById('infoCodigo').textContent =
        valorCodigo;


    document.getElementById('previewNome').textContent =
        valorNome;


    document.getElementById('previewDescricao').textContent =
        valorDescricao;


    document.getElementById('infoArea').textContent =
        valorArea !== ''
            ? valorArea.replace('.', ',') + ' m²'
            : '— m²';


    document.getElementById('previewStatus').textContent =
        valorStatus || 'STATUS';


    document.getElementById('infoStatus').textContent =
        valorStatus || '—';


    document.getElementById('infoCor').textContent =
        valorCor;


    document.getElementById('bolinhaCor').style.background =
        valorCor;


    document
        .getElementById('alaPreview')
        .style
        .setProperty(
            '--cor-ala',
            valorCor
        );


    document.getElementById('previewStatus').style.background =
        valorCor;


    if (data.value) {


        const dataObj = new Date(data.value);


        if (!isNaN(dataObj.getTime())) {


            const dia = String(
                dataObj.getDate()
            ).padStart(2, '0');


            const mes = String(
                dataObj.getMonth() + 1
            ).padStart(2, '0');


            const ano =
                dataObj.getFullYear();


            const hora = String(
                dataObj.getHours()
            ).padStart(2, '0');


            const minuto = String(
                dataObj.getMinutes()
            ).padStart(2, '0');


            document.getElementById('infoData').textContent =
                `${dia}/${mes}/${ano} ${hora}:${minuto}`;
        }


    } else {


        document.getElementById('infoData').textContent =
            '—';
    }


    contadorDescricao.textContent =
        descricao.value.length;
}


[
    codigo,
    nome,
    cor,
    descricao,
    area,
    status,
    data
].forEach(campo => {


    campo.addEventListener(
        'input',
        atualizarPreview
    );


    campo.addEventListener(
        'change',
        atualizarPreview
    );


});


cor.addEventListener(
    'input',
    function () {


        codigoCor.value =
            this.value;


        atualizarPreview();
    }
);


codigoCor.addEventListener(
    'input',
    function () {


        let valor =
            this.value.trim();


        if (!valor.startsWith('#')) {
            valor = '#' + valor;
        }


        if (/^#[0-9A-Fa-f]{6}$/.test(valor)) {


            cor.value =
                valor;


            atualizarPreview();
        }
    }
);


uploadArea.addEventListener(
    'click',
    function () {


        imagemInput.click();
    }
);


imagemInput.addEventListener(
    'change',
    function () {


        if (this.files && this.files[0]) {


            removerImagemInput.value =
                '0';


            mostrarImagem(
                this.files[0]
            );
        }
    }
);


function mostrarImagem(arquivo) {


    const leitor =
        new FileReader();


    leitor.onload =
        function (evento) {


            imagemPreview.src =
                evento.target.result;


            previewImagem.src =
                evento.target.result;


            uploadPlaceholder.style.display =
                'none';


            previewUpload.style.display =
                'block';


            previewImagem.style.display =
                'block';


            const semImagem =
                document.querySelector(
                    '.preview-sem-imagem'
                );


            if (semImagem) {


                semImagem.style.display =
                    'none';
            }
        };


    leitor.readAsDataURL(
        arquivo
    );
}


removerImagem.addEventListener(
    'click',
    function (evento) {


        evento.stopPropagation();


        imagemInput.value =
            '';


        removerImagemInput.value =
            '1';


        imagemPreview.src =
            '';


        previewImagem.src =
            '';


        previewUpload.style.display =
            'none';


        uploadPlaceholder.style.display =
            'flex';


        const semImagem =
            document.querySelector(
                '.preview-sem-imagem'
            );


        if (semImagem) {


            semImagem.style.display =
                'flex';
        }
    }
);


[
    'dragenter',
    'dragover'
].forEach(evento => {


    uploadArea.addEventListener(
        evento,
        function (e) {


            e.preventDefault();


            uploadArea.classList.add(
                'dragover'
            );
        }
    );
});


[
    'dragleave',
    'drop'
].forEach(evento => {


    uploadArea.addEventListener(
        evento,
        function (e) {


            e.preventDefault();


            uploadArea.classList.remove(
                'dragover'
            );
        }
    );
});


uploadArea.addEventListener(
    'drop',
    function (e) {


        const arquivos =
            e.dataTransfer.files;


        if (arquivos.length > 0) {


            imagemInput.files =
                arquivos;


            removerImagemInput.value =
                '0';


            mostrarImagem(
                arquivos[0]
            );
        }
    }
);


document
    .getElementById('btnLimpar')
    .addEventListener(
        'click',
        function () {


            codigo.value =
                dadosOriginais.codigo;


            nome.value =
                dadosOriginais.nome;


            cor.value =
                dadosOriginais.cor || '#ffffff';


            codigoCor.value =
                dadosOriginais.cor || '#ffffff';


            descricao.value =
                dadosOriginais.descricao;


            area.value =
                dadosOriginais.area;


            status.value =
                dadosOriginais.status;


            data.value =
                dadosOriginais.data;


            imagemInput.value =
                '';


            removerImagemInput.value =
                '0';


            if (dadosOriginais.imagem) {


                imagemPreview.src =
                    dadosOriginais.imagem;


                previewImagem.src =
                    dadosOriginais.imagem;


                previewUpload.style.display =
                    'block';


                uploadPlaceholder.style.display =
                    'none';


                previewImagem.style.display =
                    'block';


                const semImagem =
                    document.querySelector(
                        '.preview-sem-imagem'
                    );


                if (semImagem) {


                    semImagem.style.display =
                        'none';
                }


            } else {


                imagemPreview.src =
                    '';


                previewImagem.src =
                    '';


                previewUpload.style.display =
                    'none';


                uploadPlaceholder.style.display =
                    'flex';


                previewImagem.style.display =
                    'none';


                const semImagem =
                    document.querySelector(
                        '.preview-sem-imagem'
                    );


                if (semImagem) {


                    semImagem.style.display =
                        'flex';
                }
            }


            atualizarPreview();
        }
    );


atualizarPreview();


</script>


</body>


</html>

