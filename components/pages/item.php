
<?php
session_start();
require('../../backend/Config/conexao.php');
/** @var mysqli $strcon */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Item não encontrado.');
}

$tipo_perfil = $_SESSION['usuario_perfil'] ?? $_GET['origem'] ?? 'administrador';


// 2. Define a página de retorno com base no perfil detectado
switch ($tipo_perfil) {
    case 'registrador':
        $paginaVoltar = '../../sistem/registrador/item/obras.php';
        break;

    case 'manipulador':
        $paginaVoltar = '../../sistem/manipulador/item/obras.php';
        break;

    case 'administrador':
    default:
        $paginaVoltar = '../../sistem/administrador/item/obras.php';
        break;
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM Item WHERE cod_item = ?";
$stmt = mysqli_prepare($strcon, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) == 0) {
    die('Item não encontrado.');
}

$item = mysqli_fetch_assoc($resultado);



$classeCategoria = [
    'Quadro' => 'categoria-quadro',
    'Escultura' => 'categoria-escultura',
    'Pintura' => 'categoria-pintura',
    'Fotografia' => 'categoria-fotografia',
    'Taxidermia' => 'categoria-taxidermia',
    'Conservação' => 'categoria-conserva'
];


$classeStatus = [
    'Em exposição' => 'status-em-exposição',
    'Reserva técnica' => 'status-reserva-tecnica',
    'Em restauração' => 'status-em-restauração',
    'Indisponível' => 'status-indisponivel'
];

$categoriaClasse = $classeCategoria[$item['categoria']] ?? '';
$statusClasse = $classeStatus[$item['status_obra']] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($item['nome_item']) ?> - MuseuArt</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../assets/css/pages/item.css">
</head>
<body>

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

    <!-- Se $tipo_perfil for 'registrador', a URL será: /sistem/registrador/editar.php?id=4 -->
    <!-- Se $tipo_perfil for 'administrador', a URL será: /sistem/administrador/editar.php?id=4 -->
    <a 
        href="/SistemaMuseuArt.GuardaBem/sistem/<?= urlencode($tipo_perfil) ?>/item/editar_obra.php?id=<?= $id ?>&origem=<?= urlencode($tipo_perfil) ?>"
        class="crud-item editar"
    >
        <i class="bi bi-pencil-square"></i>
        <span>Editar obra</span>
    </a>

<a 
    href="/SistemaMuseuArt.GuardaBem/sistem/<?= urlencode($tipo_perfil) ?>/item/excluir_obra.php?id=<?= $id ?>&origem=<?= urlencode($tipo_perfil) ?>"
    class="crud-item excluir"
>
    <i class="bi bi-trash3"></i>
    <span>Excluir obra</span>
</a>
</nav>

    <div class="crud-voltar">

        <a href="<?= htmlspecialchars($paginaVoltar) ?>">
            <i class="bi bi-arrow-left"></i>
            <span>Voltar para obras</span>
        </a>

    </div>

</aside>


<main class="item">

    <div class="item-topo">

        <div class="breadcrumb">
        <a href="<?= $paginaVoltar ?>">Obras</a>
            <i class="bi bi-chevron-right"></i>
            <strong><?= htmlspecialchars($item['nome_item']) ?></strong>
        </div>

        <a href="<?= $paginaVoltar ?>" class="btn-voltar">
            <i class="bi bi-arrow-left"></i>
            Voltar para a lista
        </a>

    </div>

    <section class="item-conteudo">

        <div class="item-esquerda">

            <div class="card imagem-card">

              <img 
    src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(ltrim($item['foto_item'], '/')) ?>"
    alt="<?= htmlspecialchars($item['nome_item']) ?>"
    class="imagem-obra"
    id="imagemObra"
>

            </div>

<div class="galeria-miniaturas">

    <?php
    
    $imagensObra = [
        'foto_item_2' => $item['foto_item_2'] ?? '',
        'foto_item_3' => $item['foto_item_3'] ?? '',
        'foto_item_4' => $item['foto_item_4'] ?? ''
    ];
    
    $indice = 0;
    $primeiraImagemEncontrada = false;
    ?>

    <?php foreach ($imagensObra as $campo => $imagem): ?>
        
        <?php 
        $temImagem = !empty($imagem);
        

        $selecionada = '';
        if ($temImagem && !$primeiraImagemEncontrada) {
            $selecionada = 'selecionada';
            $primeiraImagemEncontrada = true;
        }
        ?>

        <button
            type="button"
            class="miniatura <?= $selecionada ?>"
            data-imagem="<?= $temImagem ? '/SistemaMuseuArt.GuardaBem/' . htmlspecialchars(ltrim($imagem, '/')) : '' ?>"
            title="<?= $temImagem ? 'Imagem ' . ($indice + 1) : 'Imagem não cadastrada' ?>"
            <?= !$temImagem ? 'disabled' : '' ?>
        >
            <?php if ($temImagem): ?>
                <img
                    src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(ltrim($imagem, '/')) ?>"
                    alt="Imagem <?= $indice + 1 ?> da obra"
                >
            <?php else: ?>
                <i class="bi bi-image"></i>
            <?php endif; ?>
        </button>

    <?php 
    $indice++;
    endforeach; 
    ?>

</div>

        </div>

        <div class="item-centro">

            <div class="obra-informacoes">

                <h1><?= htmlspecialchars($item['nome_item']) ?></h1>

                <h2><?= htmlspecialchars($item['nome_autor']) ?></h2>

                <div class="grid-info">

                    <div class="info">
                        <i class="bi bi-tag"></i>
                        <div>
                            <label>Categoria</label>
                            <strong class="<?= $categoriaClasse ?>">
                                <?= htmlspecialchars($item['categoria']) ?>
                            </strong>
                        </div>
                    </div>

                    <div class="info">
                        <i class="bi bi-circle"></i>
                        <div>
                            <label>Status</label>
                            <strong class="<?= $statusClasse ?>">
                                <?= htmlspecialchars($item['status_obra']) ?>
                            </strong>
                        </div>
                    </div>

                    <div class="info">
                        <i class="bi bi-calendar"></i>
                        <div>
                            <label>Data de criação</label>
                            <strong>
                                <?= date('d/m/Y', strtotime($item['dt_criacao'])) ?>
                            </strong>
                        </div>
                    </div>

                    <div class="info">
                        <i class="bi bi-brush"></i>
                        <div>
                            <label>Material</label>
                            <strong><?= htmlspecialchars($item['material']) ?></strong>
                        </div>
                    </div>

                    <div class="info">
                        <i class="bi bi-arrows-angle-expand"></i>
                        <div>
                            <label>Dimensões</label>
                            <strong><?= htmlspecialchars($item['dimensao']) ?></strong>
                        </div>
                    </div>

                    <div class="info">
                        <i class="bi bi-upc-scan"></i>
                        <div>
                            <label>Código</label>
                            <strong><?= $item['cod_item'] ?></strong>
                        </div>
                    </div>

                    <div class="info">
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <label>Conservação</label>
                            <strong><?= htmlspecialchars($item['estado_conservacao']) ?></strong>
                        </div>
                    </div>

                    <div class="info">
                        <i class="bi bi-calendar-event"></i>
                        <div>
                            <label>Aquisição</label>
                            <strong>
                                <?= date('d/m/Y', strtotime($item['dt_aquisicao'])) ?>
                            </strong>
                        </div>
                    </div>

                </div>

            </div>

            <section class="card descricao">

                <h2>
                    <i class="bi bi-card-text"></i>
                    Descrição
                </h2>

                <p>
                    <?= nl2br(htmlspecialchars($item['descricao'])) ?>
                </p>

            </section>

            <section class="card artista">

                <div class="artista-foto">

                     <img src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(ltrim($item['foto_autor'], '/')) ?>"
                     alt="<?= htmlspecialchars($item['nome_autor']) ?>">

                </div>

                <div class="artista-info">

                    <h2><?= htmlspecialchars($item['nome_autor']) ?></h2>

                    <p>
                        Autor responsável pela obra
                        <strong><?= htmlspecialchars($item['nome_item']) ?></strong>.
                    </p>

                </div>

            </section>

        </div>

        <aside class="item-direita">

            <div class="card resumo">

                <h2>
                    <i class="bi bi-briefcase"></i>
                    Resumo
                </h2>

                <div class="registro-item">
                    <span>Proveniência</span>
                    <strong><?= htmlspecialchars($item['proveniencia']) ?></strong>
                </div>

                <div class="registro-item">
                    <span>Método de aquisição</span>
                    <strong><?= htmlspecialchars($item['metodo_aquisicao']) ?></strong>
                </div>

                <div class="registro-item">
                    <span>Histórico do proprietário</span>
                    <strong><?= htmlspecialchars($item['historico_proprietario']) ?></strong>
                </div>

            </div>

            <div class="card autor-resumo">

                <h2>
                    <i class="bi bi-person"></i>
                    Autor
                </h2>

                <div class="autor-mini">

                    <img src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(ltrim($item['foto_autor'], '/')) ?>"
                     alt="<?= htmlspecialchars($item['nome_autor']) ?>">

                    <div>

                        <strong><?= htmlspecialchars($item['nome_autor']) ?></strong>

                        <span>Autor da obra</span>

                    </div>

                </div>

            </div>

            <div class="card registro">

                <h2>
                    <i class="bi bi-info-circle"></i>
                    Informações de Registro
                </h2>

                <div class="registro-item">
                    <span>Código</span>
                    <strong>#<?= $item['cod_item'] ?></strong>
                </div>

                <div class="registro-item">
                    <span>Data de criação</span>
                    <strong><?= date('d/m/Y', strtotime($item['dt_criacao'])) ?></strong>
                </div>

                <div class="registro-item">
                    <span>Data de aquisição</span>
                    <strong><?= date('d/m/Y', strtotime($item['dt_aquisicao'])) ?></strong>
                </div>

                <div class="registro-item">
                    <span>Requisito de conservação</span>
                    <strong><?= htmlspecialchars($item['requisito_conservacao']) ?></strong>
                </div>

            </div>

        </aside>

    </section>
<div class="visualizador" id="visualizador">

    <button class="visualizador-fechar" id="fecharVisualizador">
        <i class="bi bi-x-lg"></i>
    </button>

    <div class="visualizador-conteudo">

        <div class="imagem-container" id="imagemContainer">

            <img
                src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(ltrim($item['foto_item'], '/')) ?>"
                alt="<?= htmlspecialchars($item['nome_item']) ?>"
                id="imagemVisualizada"
            >

        </div>

     
        <div class="controles-imagem">

            <button id="zoomMenos" title="Diminuir zoom">
                <i class="bi bi-dash-lg"></i>
            </button>

            <button id="zoomMais" title="Aumentar zoom">
                <i class="bi bi-plus-lg"></i>
            </button>

            <button id="zoomReset" title="Restaurar">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>

            <button id="telaCheia" title="Tela cheia">
                <i class="bi bi-fullscreen"></i>
            </button>

        </div>

    </div>

</div>
</main>
<script src="../../assets/js/item.js"></script>
</body>
</html>