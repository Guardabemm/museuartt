<?php
require_once "../../backend/Config/conexao.php";
/** @var mysqli $strcon */

$sql = "
SELECT
    nome_item,
    categoria,
    status_obra,
    foto_item,
    dt_criacao
FROM item
ORDER BY dt_criacao DESC
LIMIT 5
";

$resultado = mysqli_query($strcon, $sql);

$itens = [];

while ($item = mysqli_fetch_assoc($resultado)) {
    $itens[] = $item;
}
?>

<section class="dashboard-list">

    <div class="dashboard-list-topo">

        <h3>Itens Recentes</h3>

        <a href="../sistema/administrador/item/obras.php">
            Ver todos
            <i class="bi bi-arrow-right"></i>
        </a>

    </div>

    <div class="dashboard-list-conteudo">

        <?php foreach ($itens as $item): ?>

            <?php
            switch (trim($item['status_obra'])) {

                case 'Em exposicao':
                    $statusClasse = 'exposicao';
                    break;

                case 'Em restauracao':
                    $statusClasse = 'restauracao';
                    break;

                case 'Reserva tecnica':
                    $statusClasse = 'reserva';
                    break;

                case 'Indisponivel':
                    $statusClasse = 'indisponivel';
                    break;

                default:
                    $statusClasse = '';
                    break;
            }
            ?>

            <article class="dashboard-item">

                <div class="dashboard-item-info">

                    <img src="<?= htmlspecialchars($item['foto_item']) ?>"
                        alt="<?= htmlspecialchars($item['nome_item']) ?>">

                    <div class="dashboard-item-texto">

                        <strong>
                            <?= htmlspecialchars($item['nome_item']) ?>
                        </strong>

                        <span>
                            <?= date('d/m/Y', strtotime($item['dt_criacao'])) ?>
                        </span>

                    </div>

                </div>

                <span class="categoria <?= strtolower(str_replace(' ', '-', $item['categoria'])) ?>">
                    <?= htmlspecialchars($item['categoria']) ?>
                </span>

                <span class="status <?= $statusClasse ?>">
                    <span class="status-dot"></span>
                    <span class="status-texto">
                        <?= htmlspecialchars($item['status_obra']) ?>
                    </span>
                </span>

            </article>

        <?php endforeach; ?>

    </div>

</section>