<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../backend/Config/conexao.php';

if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../public/index.php");
    exit();
}

$nomeUsuario = $_SESSION['nome'] ?? 'Usuário';
$tipoUsuario = $_SESSION['tipo'] ?? 'Funcionário';

$origem = $_GET['origem'] ?? 'admin';

switch ($origem) {
    case 'registrador':
        $paginaVoltar = '../../registrador/item/obras.php';
        break;

    case 'manipulador':
        $paginaVoltar = '../../manipulador/item/obras.php';
        break;

    default:
        $paginaVoltar = '../../administrador/item/obras.php';
        break;
}

$nomeTabela = 'item';

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
$statusObra = 'Em exposição';
$categoria = '';
$codAla = '';

$erro = '';

if (!isset($strcon) || !($strcon instanceof mysqli)) {
    $erro = 'A conexão com o banco de dados não foi encontrada.';
}

/* =========================================================
   1. BUSCAR ALAS DA TABELA 'ALOCACAO' E AUTORES DO BANCO
   ========================================================= */
$alas = [];
$sqlAlas = "SELECT cod_ala, nome FROM alocacao ORDER BY nome ASC";
$resAlas = @mysqli_query($strcon, $sqlAlas);

if ($resAlas) {
    while ($row = mysqli_fetch_assoc($resAlas)) {
        $alas[] = $row;
    }
}

$autoresExistentes = [];
$sqlAutores = "
    SELECT DISTINCT nome_autor, foto_autor 
    FROM item 
    WHERE nome_autor IS NOT NULL AND nome_autor <> '' 
    ORDER BY nome_autor ASC
";
$resAutores = @mysqli_query($strcon, $sqlAutores);
if ($resAutores) {
    while ($row = mysqli_fetch_assoc($resAutores)) {
        $autoresExistentes[] = $row;
    }
}

/* =========================================================
   FUNÇÃO AUXILIAR PARA UPLOAD DE IMAGENS
   ========================================================= */
function processarUploadFoto($inputName, $pastaRelativa = 'assets/img/obras/')
{
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $diretorioDestino = __DIR__ . '/../../../' . trim($pastaRelativa, '/') . '/';
    if (!is_dir($diretorioDestino)) {
        mkdir($diretorioDestino, 0755, true);
    }

    $extensao = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extensao, $extensoesPermitidas)) {
        return null;
    }

    $prefixo = ($pastaRelativa === 'assets/img/autor/') ? 'autor_' : 'obra_';
    $nomeArquivo = uniqid($prefixo, true) . '.' . $extensao;
    $caminhoCompleto = $diretorioDestino . $nomeArquivo;

    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $caminhoCompleto)) {
        return trim($pastaRelativa, '/') . '/' . $nomeArquivo;
    }

    return null;
}

/* =========================================================
   PROCESSAMENTO DO FORMULÁRIO (POST)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erro)) {
    $nomeItem = trim($_POST['nome_item'] ?? '');
    $nomeAutor = trim($_POST['nome_autor'] ?? '');
    $dtCriacao = trim($_POST['dt_criacao'] ?? '');
    $dimensao = trim($_POST['dimensao'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $estadoConservacao = trim($_POST['estado_conservacao'] ?? '');
    $requisitoConservacao = trim($_POST['requisito_conservacao'] ?? '');
    $proveniencia = trim($_POST['proveniencia'] ?? '');
    $dtAquisicao = trim($_POST['dt_aquisicao'] ?? '');
    $historicoProprietario = trim($_POST['historico_proprietario'] ?? '');
    $metodoAquisicao = trim($_POST['metodo_aquisicao'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $statusObra = trim($_POST['status_obra'] ?? 'Em exposição');
    $categoria = trim($_POST['categoria'] ?? '');
    $codAla = trim($_POST['cod_ala'] ?? '');

    // Força alocação na Ala Depósito (ALA008) se o status não for "Em exposição"
    if ($statusObra !== 'Em exposição') {
        $codAla = 'ALA008';
    }

    // Processamento de Fotos
    $fotoItem = processarUploadFoto('foto_item', 'assets/img/obras/');
    $fotoAutor = processarUploadFoto('foto_autor', 'assets/img/autor/');

    if (empty($fotoAutor) && !empty($_POST['foto_autor_existente'])) {
        $fotoAutor = trim($_POST['foto_autor_existente']);
    }

    $fotoItem2 = processarUploadFoto('foto_item_2', 'assets/img/obras/');
    $fotoItem3 = processarUploadFoto('foto_item_3', 'assets/img/obras/');
    $fotoItem4 = processarUploadFoto('foto_item_4', 'assets/img/obras/');

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
    } elseif ($categoria === '') {
        $erro = 'Selecione uma categoria.';
    } elseif ($codAla === '') {
        $erro = 'Selecione uma ala para a obra.';
    } else {
        try {
            $sql = "
                INSERT INTO `$nomeTabela` (
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
                    status_obra,
                    categoria,
                    foto_item,
                    foto_autor,
                    foto_item2,
                    foto_item3,
                    foto_item4
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare($strcon, $sql);

            if (!$stmt) {
                throw new Exception(mysqli_error($strcon));
            }

            mysqli_stmt_bind_param(
                $stmt,
                'sssssssssssssssssss',
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
                $statusObra,
                $categoria,
                $fotoItem,
                $fotoAutor,
                $fotoItem2,
                $fotoItem3,
                $fotoItem4
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            $codItem = (string) mysqli_insert_id($strcon);
            mysqli_stmt_close($stmt);

            if ($codItem === '0') {
                throw new Exception('Erro ao obter o ID da obra.');
            }

            /* =================================================================
               2. REGISTRA A ALOCAÇÃO NA TABELA 'REGISTRO'
               ================================================================= */
            $id_funcionario = intval($_SESSION['id_funcionario']);
            $dt_registro = date('Y-m-d H:i:s');

            $sqlRegistro = "INSERT INTO registro (cod_item, cod_ala, id_funcionario, dt_registro) VALUES (?, ?, ?, ?)";
            $stmtRegistro = mysqli_prepare($strcon, $sqlRegistro);

            if ($stmtRegistro) {
                $codItemInt = intval($codItem);
                mysqli_stmt_bind_param($stmtRegistro, 'isis', $codItemInt, $codAla, $id_funcionario, $dt_registro);

                if (mysqli_stmt_execute($stmtRegistro)) {
                    $cod_registro = mysqli_insert_id($strcon);
                    mysqli_stmt_close($stmtRegistro);

                    if (mb_strtolower($statusObra) === 'em restauração') {
                        $sqlRestauracao = "
                            INSERT INTO restauracao (cod_registro, status, prioridade, progresso, motivo)
                            VALUES (?, 'Aguardando', 'Média', 0, 'Item cadastrado diretamente com status em restauração')
                        ";
                        $stmtRestauracao = mysqli_prepare($strcon, $sqlRestauracao);

                        if ($stmtRestauracao) {
                            mysqli_stmt_bind_param($stmtRestauracao, 'i', $cod_registro);
                            mysqli_stmt_execute($stmtRestauracao);
                            mysqli_stmt_close($stmtRestauracao);
                        }
                    }
                } else {
                    mysqli_stmt_close($stmtRegistro);
                }
            }

            header("Location: " . $paginaVoltar . "?status=sucesso");
            exit();

        } catch (Throwable $e) {
            $erro = 'Erro ao cadastrar a obra: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar nova obra - MuseuArt</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../../assets/css/design/CRUDS/cadastrar_obra.css">

    <style>
        .campo-autocomplete {
            position: relative;
        }

        .sugestoes-autor {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #1e1e1e;
            border: 1px solid #333;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .sugestoes-autor.ativo {
            display: block;
        }

        .sugestao-autor-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            cursor: pointer;
            color: #fff;
            transition: background 0.2s;
        }

        .sugestao-autor-item:hover {
            background: #2a2a2a;
        }

        .sugestao-autor-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>

<body>

    <aside class="crud-sidebar">
        <div class="crud-logo">
            <img src="/SistemaMuseuArt.GuardaBem/assets/img/logomenu.svg" alt="MuseuArt">
        </div>

        <div class="crud-titulo">
            <span>AÇÕES</span>
        </div>

        <nav class="crud-menu">
            <a href="#" class="crud-item criar ativo">
                <i class="bi bi-plus-lg"></i>
                <span>Nova obra</span>
            </a>
        </nav>

        <div class="crud-voltar">
            <a href="<?= htmlspecialchars($paginaVoltar) ?>">
                <i class="bi bi-arrow-left"></i>
                <span>Sair</span>
            </a>
        </div>
    </aside>

    <main class="ala">
        <div class="ala-topo">
            <div class="breadcrumb">
                <a href="<?= htmlspecialchars($paginaVoltar) ?>">Obras</a>
                <i class="bi bi-chevron-right"></i>
                <strong>Nova obra</strong>
            </div>

            <a href="<?= htmlspecialchars($paginaVoltar) ?>" class="btn-voltar">
                <i class="bi bi-arrow-left"></i>
                Voltar para a lista
            </a>
        </div>

        <div class="ala-cabecalho">
            <div>
                <span class="ala-label">CADASTRO</span>
                <h1>Cadastrar nova obra</h1>
                <p>Preencha as informações abaixo para cadastrar uma nova obra no MuseuArt.</p>
            </div>
        </div>

        <section class="ala-conteudo">
            <form method="POST" action="" enctype="multipart/form-data" class="ala-form">

                <?php if (!empty($erro)): ?>
                    <div class="mensagem erro" style="margin-bottom: 20px;">
                        <i class="bi bi-exclamation-circle"></i>
                        <span><?= htmlspecialchars($erro) ?></span>
                    </div>
                <?php endif; ?>

                <!-- IDENTIFICAÇÃO -->
                <div class="card">
                    <div class="card-titulo">
                        <div class="card-icone"><i class="bi bi-info-circle"></i></div>
                        <div>
                            <h2>Identificação da obra</h2>
                            <p>Informações básicas para identificar a obra no acervo.</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="campo">
                            <label for="cod_item">Código da obra <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-upc-scan"></i>
                                <input type="text" id="cod_item" value="" placeholder="Será gerado automaticamente"
                                    readonly aria-readonly="true">
                            </div>
                        </div>

                        <div class="campo">
                            <label for="categoria">Categoria <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-tag"></i>
                                <select id="categoria" name="categoria" required>
                                    <option value="">Selecione uma categoria</option>
                                    <option value="Quadro" <?= $categoria === 'Quadro' ? 'selected' : '' ?>>Quadro</option>
                                    <option value="Escultura" <?= $categoria === 'Escultura' ? 'selected' : '' ?>>Escultura
                                    </option>
                                    <option value="Pintura" <?= $categoria === 'Pintura' ? 'selected' : '' ?>>Pintura
                                    </option>
                                    <option value="Fotografia" <?= $categoria === 'Fotografia' ? 'selected' : '' ?>>
                                        Fotografia</option>
                                    <option value="Taxidermia" <?= $categoria === 'Taxidermia' ? 'selected' : '' ?>>
                                        Taxidermia</option>
                                    <option value="Conservação" <?= $categoria === 'Conservação' ? 'selected' : '' ?>>
                                        Animal em conservação</option>
                                    <option value="Fóssil" <?= $categoria === 'Fóssil' ? 'selected' : '' ?>>Fóssil</option>
                                </select>
                            </div>
                        </div>

                        <div class="campo campo-grande">
                            <label for="nome_item">Nome da obra <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-card-heading"></i>
                                <input type="text" id="nome_item" name="nome_item"
                                    value="<?= htmlspecialchars($nomeItem) ?>" placeholder="Digite o nome da obra"
                                    maxlength="150" required>
                            </div>
                        </div>

                        <div class="campo campo-grande campo-autocomplete">
                            <label for="nome_autor">Nome do autor <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-person"></i>
                                <input type="text" id="nome_autor" name="nome_autor"
                                    value="<?= htmlspecialchars($nomeAutor) ?>"
                                    placeholder="Digite para buscar ou adicionar autor" maxlength="150"
                                    autocomplete="off" required>
                            </div>
                            <input type="hidden" id="foto_autor_existente" name="foto_autor_existente" value="">
                            <div id="sugestoesAutor" class="sugestoes-autor"></div>
                        </div>

                        <div class="campo">
                            <label for="status_obra">Status da obra</label>
                            <div class="input-container">
                                <i class="bi bi-circle"></i>
                                <select id="status_obra" name="status_obra">
                                    <option value="Em exposição" <?= $statusObra === 'Em exposição' ? 'selected' : '' ?>>Em
                                        exposição</option>
                                    <option value="Em restauração" <?= $statusObra === 'Em restauração' ? 'selected' : '' ?>>Em restauração</option>
                                    <option value="Reserva técnica" <?= $statusObra === 'Reserva técnica' ? 'selected' : '' ?>>Reserva técnica</option>
                                    <option value="Indisponível" <?= $statusObra === 'Indisponível' ? 'selected' : '' ?>>
                                        Indisponível</option>
                                </select>
                            </div>
                        </div>

                        <div class="campo">
                            <label for="cod_ala">Ala Alocada <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-door-open"></i>
                                <select id="cod_ala" name="cod_ala" required>
                                    <option value="">Selecione uma ala</option>
                                    <?php foreach ($alas as $ala): ?>
                                        <option value="<?= $ala['cod_ala'] ?>" <?= $codAla === $ala['cod_ala'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ala['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARACTERÍSTICAS -->
                <div class="card">
                    <div class="card-titulo">
                        <div class="card-icone"><i class="bi bi-brush"></i></div>
                        <div>
                            <h2>Características</h2>
                            <p>Informações técnicas e físicas da obra.</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="campo">
                            <label for="dt_criacao">Data de criação <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-calendar"></i>
                                <input type="date" id="dt_criacao" name="dt_criacao"
                                    value="<?= htmlspecialchars($dtCriacao) ?>" required>
                            </div>
                        </div>

                        <div class="campo">
                            <label for="material">Material <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-brush"></i>
                                <input type="text" id="material" name="material"
                                    value="<?= htmlspecialchars($material) ?>" placeholder="Ex.: Óleo sobre tela"
                                    maxlength="150" required>
                            </div>
                        </div>

                        <div class="campo">
                            <label for="dimensao">Dimensões <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-arrows-angle-expand"></i>
                                <input type="text" id="dimensao" name="dimensao"
                                    value="<?= htmlspecialchars($dimensao) ?>" placeholder="Ex.: 80 x 120 cm"
                                    maxlength="100" required>
                            </div>
                        </div>

                        <div class="campo">
                            <label for="estado_conservacao">Estado de conservação <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-shield-check"></i>
                                <select id="estado_conservacao" name="estado_conservacao" required>
                                    <option value="">Selecione</option>
                                    <option value="Excelente" <?= $estadoConservacao === 'Excelente' ? 'selected' : '' ?>>
                                        Excelente</option>
                                    <option value="Bom" <?= $estadoConservacao === 'Bom' ? 'selected' : '' ?>>Bom</option>
                                    <option value="Regular" <?= $estadoConservacao === 'Regular' ? 'selected' : '' ?>>
                                        Regular</option>
                                    <option value="Ruim" <?= $estadoConservacao === 'Ruim' ? 'selected' : '' ?>>Ruim
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="campo campo-full">
                            <label for="requisito_conservacao">Requisito de conservação</label>
                            <div class="textarea-container">
                                <i class="bi bi-exclamation-triangle"></i>
                                <textarea id="requisito_conservacao" name="requisito_conservacao" rows="3"
                                    maxlength="500"
                                    placeholder="Descreva os fatores que podem afetar a conservação do item..."><?= htmlspecialchars($requisitoConservacao) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AQUISIÇÃO E PROVENIÊNCIA -->
                <div class="card">
                    <div class="card-titulo">
                        <div class="card-icone"><i class="bi bi-briefcase"></i></div>
                        <div>
                            <h2>Aquisição e proveniência</h2>
                            <p>Histórico e informações sobre a aquisição da obra.</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="campo">
                            <label for="dt_aquisicao">Data de aquisição <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-calendar-event"></i>
                                <input type="date" id="dt_aquisicao" name="dt_aquisicao"
                                    value="<?= htmlspecialchars($dtAquisicao) ?>" required>
                            </div>
                        </div>

                        <div class="campo">
                            <label for="metodo_aquisicao">Método de aquisição <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-arrow-down-circle"></i>
                                <input type="text" id="metodo_aquisicao" name="metodo_aquisicao"
                                    value="<?= htmlspecialchars($metodoAquisicao) ?>"
                                    placeholder="Ex.: Doação, compra, transferência" maxlength="100" required>
                            </div>
                        </div>

                        <div class="campo campo-grande">
                            <label for="proveniencia">Proveniência <span>*</span></label>
                            <div class="input-container">
                                <i class="bi bi-geo-alt"></i>
                                <input type="text" id="proveniencia" name="proveniencia"
                                    value="<?= htmlspecialchars($proveniencia) ?>"
                                    placeholder="Informe a origem ou procedência da obra" maxlength="250" required>
                            </div>
                        </div>

                        <div class="campo campo-full">
                            <label for="historico_proprietario">Histórico do proprietário</label>
                            <div class="textarea-container">
                                <i class="bi bi-clock-history"></i>
                                <textarea id="historico_proprietario" name="historico_proprietario" rows="5"
                                    maxlength="1000"
                                    placeholder="Informe o histórico de proprietários da obra..."><?= htmlspecialchars($historicoProprietario) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DESCRIÇÃO -->
                <div class="card">
                    <div class="card-titulo">
                        <div class="card-icone"><i class="bi bi-card-text"></i></div>
                        <div>
                            <h2>Descrição</h2>
                            <p>Informações detalhadas sobre a obra.</p>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="descricao">Descrição <span>*</span></label>
                        <div class="textarea-container">
                            <i class="bi bi-card-text"></i>
                            <textarea id="descricao" name="descricao" rows="8" maxlength="3000"
                                placeholder="Digite uma descrição detalhada da obra..."
                                required><?= htmlspecialchars($descricao) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- IMAGENS -->
                <div class="card">
                    <div class="card-titulo">
                        <div class="card-icone"><i class="bi bi-images"></i></div>
                        <div>
                            <h2>Imagens</h2>
                            <p>Adicione as imagens da obra e do autor (opcionais).</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="campo campo-full">
                            <label for="foto_item">Imagem principal da obra</label>
                            <div class="input-container">
                                <i class="bi bi-image"></i>
                                <input type="file" id="foto_item" name="foto_item"
                                    accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>

                        <div class="campo campo-full">
                            <label for="foto_autor">Foto do autor</label>
                            <div class="input-container">
                                <i class="bi bi-person-square"></i>
                                <input type="file" id="foto_autor" name="foto_autor"
                                    accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>

                        <div class="campo">
                            <label for="foto_item_2">Imagem adicional 2</label>
                            <div class="input-container">
                                <i class="bi bi-image"></i>
                                <input type="file" id="foto_item_2" name="foto_item_2"
                                    accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>

                        <div class="campo">
                            <label for="foto_item_3">Imagem adicional 3</label>
                            <div class="input-container">
                                <i class="bi bi-image"></i>
                                <input type="file" id="foto_item_3" name="foto_item_3"
                                    accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>

                        <div class="campo">
                            <label for="foto_item_4">Imagem adicional 4</label>
                            <div class="input-container">
                                <i class="bi bi-image"></i>
                                <input type="file" id="foto_item_4" name="foto_item_4"
                                    accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-acoes">
                    <a href="<?= htmlspecialchars($paginaVoltar) ?>" class="btn-cancelar">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </a>
                    <button type="submit" class="btn-cadastrar">
                        <i class="bi bi-check-lg"></i> Cadastrar obra
                    </button>
                </div>
            </form>

            <aside class="ala-lateral">
                <div class="card resumo-cadastro">
                    <h2><i class="bi bi-info-circle"></i> Sobre o cadastro</h2>
                    <p>Preencha as informações da obra com atenção. Os dados serão utilizados para identificação,
                        catalogação e consulta do acervo.</p>
                </div>

                <div class="card cadastro-info">
                    <h2><i class="bi bi-person-check"></i> Quem está cadastrando</h2>
                    <div class="responsavel">
                        <div class="responsavel-avatar"><i class="bi bi-person"></i></div>
                        <div class="responsavel-dados">
                            <span>Cadastrado por</span>
                            <strong><?= htmlspecialchars($nomeUsuario) ?></strong>
                            <small><?= htmlspecialchars($tipoUsuario) ?></small>
                        </div>
                    </div>
                </div>

                <div class="card campos-obrigatorios">
                    <h2><i class="bi bi-asterisk"></i> Campos obrigatórios</h2>
                    <p>Os campos marcados com <strong>*</strong> precisam ser preenchidos antes do cadastro.</p>
                </div>
            </aside>
        </section>
    </main>

    <script>
        window.autoresExistentes = <?= json_encode($autoresExistentes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const selectStatus = document.getElementById("status_obra");
            const selectAla = document.getElementById("cod_ala");

            const inputAutor = document.getElementById("nome_autor");
            const caixaSugestoes = document.getElementById("sugestoesAutor");
            const inputFotoAutorExistente = document.getElementById("foto_autor_existente");

            /* =========================================================
       1. REGRA DE EXCLUSÃO/SELEÇÃO DE ALA POR STATUS
       ========================================================= */
            function atualizarAla() {
                if (!selectStatus || !selectAla) return;

                const status = selectStatus.value;
                const opcaoDeposito = selectAla.querySelector('option[value="ALA008"]');

                if (status === "Em exposição") {
                    // Se a Ala Depósito estiver selecionada, reseta a seleção
                    if (selectAla.value === "ALA008") {
                        selectAla.value = "";
                    }

                    // Esconde a opção Ala Depósito
                    if (opcaoDeposito) {
                        opcaoDeposito.style.display = "none";
                    }

                    // Habilita a escolha normal de alas de exposição
                    selectAla.style.pointerEvents = "auto";
                    selectAla.style.opacity = "1";
                } else {
                    // Exibe e seleciona automaticamente a Ala Depósito para outros status
                    if (opcaoDeposito) {
                        opcaoDeposito.style.display = "block";
                    }

                    selectAla.value = "ALA008";
                    selectAla.style.pointerEvents = "none";
                    selectAla.style.opacity = "0.7";
                }
            }

            if (selectStatus) {
                selectStatus.addEventListener("change", atualizarAla);
                atualizarAla(); // Executa ao carregar a página
            }

            if (inputAutor && caixaSugestoes && Array.isArray(window.autoresExistentes)) {
                inputAutor.addEventListener("input", function () {
                    const termo = this.value.trim().toLowerCase();
                    caixaSugestoes.innerHTML = "";
                    inputFotoAutorExistente.value = "";

                    if (termo === "") {
                        caixaSugestoes.classList.remove("ativo");
                        return;
                    }

                    const filtrados = window.autoresExistentes.filter(a =>
                        a.nome_autor.toLowerCase().includes(termo)
                    );

                    filtrados.sort((a, b) => {
                        const nomeA = a.nome_autor.toLowerCase();
                        const nomeB = b.nome_autor.toLowerCase();

                        const comecaA = nomeA.startsWith(termo);
                        const comecaB = nomeB.startsWith(termo);

                        if (comecaA && !comecaB) return -1;
                        if (!comecaA && comecaB) return 1;

                        return nomeA.localeCompare(nomeB);
                    });

                    if (filtrados.length === 0) {
                        caixaSugestoes.classList.remove("ativo");
                        return;
                    }

                    filtrados.slice(0, 5).forEach(a => {
                        const div = document.createElement("div");
                        div.className = "sugestao-autor-item";

                        let fotoSrc = a.foto_autor ? '/' + a.foto_autor.replace(/^\//, '') : '/assets/img/usuarios/avatarpadrao.jpg';
                        if (!fotoSrc.includes('/SistemaMuseuArt.GuardaBem/')) {
                            fotoSrc = '/SistemaMuseuArt.GuardaBem' + fotoSrc;
                        }

                        div.innerHTML = `
                        <img src="${fotoSrc}" alt="${a.nome_autor}" onerror="this.src='/SistemaMuseuArt.GuardaBem/assets/img/usuarios/avatarpadrao.jpg'">
                        <span>${a.nome_autor}</span>
                    `;

                        div.addEventListener("click", function () {
                            inputAutor.value = a.nome_autor;
                            inputFotoAutorExistente.value = a.foto_autor || "";
                            caixaSugestoes.innerHTML = "";
                            caixaSugestoes.classList.remove("ativo");
                        });

                        caixaSugestoes.appendChild(div);
                    });

                    caixaSugestoes.classList.add("ativo");
                });

                document.addEventListener("click", function (e) {
                    if (!inputAutor.contains(e.target) && !caixaSugestoes.contains(e.target)) {
                        caixaSugestoes.classList.remove("ativo");
                    }
                });
            }
        });
    </script>

</body>

</html>