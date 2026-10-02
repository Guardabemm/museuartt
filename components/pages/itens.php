<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../backend/Config/conexao.php";

/** @var mysqli $strcon */

// 1. Deteção DINÂMICA do perfil pela sessão (fallback para registrador caso não exista)
$tipoPerfil = $_SESSION['usuario_perfil'] ?? $_SESSION['tipo_usuario'] ?? 'registrador';

$modoSelecao =
    $GLOBALS['selecionar']
    ?? ($_GET['selecionar'] ?? '');

$modosPermitidos = [
    'editar',
    'excluir',
    'atualizar'
];

if (!in_array($modoSelecao, $modosPermitidos, true)) {
    $modoSelecao = '';
}

$sql = "
    SELECT
        cod_item,
        nome_item,
        nome_autor,
        categoria,
        status_obra,
        foto_item,
        dt_criacao
    FROM item
     WHERE dt_arquivo IS NULL
    ORDER BY nome_item ASC
";

$resultado = mysqli_query($strcon, $sql);

if (!$resultado) {
    die(
        "Erro na consulta: " .
        mysqli_error($strcon)
    );
}

$itens = [];

while ($item = mysqli_fetch_assoc($resultado)) {
    $itens[] = $item;
}

$statusClassMap = [
    'Em Exposição' => 'status-em-exposicao',
    'Em Restauração' => 'status-em-restauracao',
    'Reserva Técnica' => 'status-reserva-tecnica',
    'Indisponível' => 'status-indisponivel'
];

?>

<div class="obras-grid <?= $modoSelecao ? 'modo-selecao-obra modo-selecao-' . htmlspecialchars(
    $modoSelecao,
    ENT_QUOTES,
    'UTF-8'
)
    : ''
    ?>" id="obrasGrid" data-modo-selecao="<?= htmlspecialchars(
    $modoSelecao,
    ENT_QUOTES,
    'UTF-8'
) ?>" data-tipo-perfil="<?= htmlspecialchars($tipoPerfil, ENT_QUOTES, 'UTF-8') ?>">

    <?php foreach ($itens as $item): ?>

        <article class="obra-card" data-id="<?= htmlspecialchars($item['cod_item']) ?>"
            data-nome="<?= htmlspecialchars($item['nome_item'], ENT_QUOTES, 'UTF-8') ?>"
            data-autor="<?= htmlspecialchars($item['nome_autor'], ENT_QUOTES, 'UTF-8') ?>"
            data-categoria="<?= htmlspecialchars($item['categoria'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="obra-card-imagem">

                <img src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(ltrim($item['foto_item'], '/')) ?>"
                    alt="<?= htmlspecialchars($item['nome_item']) ?>">

            </div>

            <div class="obra-card-conteudo">

                <div class="obra-card-titulo">

                    <div>

                        <h3>
                            <?= htmlspecialchars($item['nome_item']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($item['nome_autor']) ?>
                        </p>

                    </div>

                </div>

                <div class="categoria">

                    <span class="obra-card-detalhes categoria-<?=
                        strtolower(
                            str_replace(' ', '-', $item['categoria'])
                        ) ?>">
                        <?= htmlspecialchars($item['categoria']) ?>
                    </span>

                    <span class="obra-status <?= $statusClassMap[$item['status_obra']] ?? '' ?>">
                        <?= htmlspecialchars($item['status_obra']) ?>
                    </span>

                </div>

                <div class="obra-card-acoes">

                    <?php if (!$modoSelecao): ?>

                        <!-- 2. ORIGEM DINÂMICA VIA PHP -->
                        <a href="../../../components/pages/item.php?id=<?= $item['cod_item'] ?>&origem=<?= urlencode($tipoPerfil) ?>"
                            class="btn-visualizar">
                            <i class="bi bi-eye"></i>
                        </a>



                    <?php else: ?>

                        <span class="selecionar-indicador">

                            <?php if ($modoSelecao === 'editar'): ?>

                                <i class="bi bi-pencil"></i>
                                Editar

                            <?php elseif ($modoSelecao === 'excluir'): ?>

                                <i class="bi bi-trash"></i>
                                Excluir

                            <?php else: ?>

                                <i class="bi bi-arrow-repeat"></i>
                                Atualizar

                            <?php endif; ?>

                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </article>

    <?php endforeach; ?>

</div>

<script>

    document.addEventListener('DOMContentLoaded', function () {
        const menu = document.querySelector('.menu-lateral');
        if (menu) {
            menu.classList.add('fechado');
        }
    });

document.addEventListener('DOMContentLoaded', function () {

    const pagina =
        document.querySelector('#obrasGrid');

    if (!pagina) {
        return;
    }

    const modo =
        pagina.dataset.modoSelecao;

    // 3. RECUPERA O TIPO DE PERFIL DO DATASET DA GRID
    const tipoPerfil =
        pagina.dataset.tipoPerfil || 'registrador';

    if (!modo) {
        return;
    }

    const cards =
        document.querySelectorAll('.obra-card');

    cards.forEach(function (card) {

        card.classList.add(
            'obra-selecionavel'
        );

        card.addEventListener(
            'click',
            function (evento) {

                if (
                    evento.target.closest(
                        'a, button'
                    )
                ) {
                    return;
                }

                const id =
                    card.dataset.id;

                if (!id) {

                    console.error(
                        'Código da obra não encontrado.'
                    );

                    return;

                }

                abrirObra(
                    id,
                    modo,
                    tipoPerfil
                );

            }
        );

    });

    function abrirObra(
        id,
        modo,
        perfil
    ) {

        // 4. MONTA OS LINKS NO JS USANDO O PERFIL DINÂMICO
        if (modo === 'editar') {

            window.location.href =
                'editar_obra.php?cod_item=' +
                encodeURIComponent(id) + 
                '&origem=' + encodeURIComponent(perfil);

            return;

        }

        if (modo === 'excluir') {

            window.location.href =
                'excluir_obra.php?cod_item=' +
                encodeURIComponent(id) +
                '&origem=' + encodeURIComponent(perfil);

            return;

        }

        if (modo === 'atualizar') {

            window.location.href =
                'atualizar_obra.php?cod_item=' +
                encodeURIComponent(id) +
                '&origem=' + encodeURIComponent(perfil);

            return;

        }

        console.error(
            'Modo de seleção inválido:',
            modo
        );

    }

});

</script>