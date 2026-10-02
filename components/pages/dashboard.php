<?php


require_once __DIR__ . "/../../backend/Config/conexao.php";
require_once __DIR__ . "/../../components/atividade_recente.php";
$atividades = getAtividadesRecentes(5);


$sql = "SELECT COUNT(*) AS total FROM item";
$result = mysqli_query($strcon, $sql);
$totalItens = (int) mysqli_fetch_assoc($result)['total'];


$sql = "SELECT COUNT(*) AS total FROM funcionario";
$result = mysqli_query($strcon, $sql);
$totalFuncionarios = (int) mysqli_fetch_assoc($result)['total'];


$sql = "SELECT COUNT(*) AS total FROM exposicao WHERE status = 'Em andamento'";
$result = mysqli_query($strcon, $sql);
$totalExposicoes = (int) mysqli_fetch_assoc($result)['total'];


$sql = "SELECT COUNT(*) AS total FROM restauracao WHERE status = 'Em andamento'";
$result = mysqli_query($strcon, $sql);
$totalRestauracoes = (int) mysqli_fetch_assoc($result)['total'];


$sql = "SELECT COUNT(*) AS total FROM item WHERE status_obra = 'Em exposição'";
$result = mysqli_query($strcon, $sql);
$totalEmExposicao = (int) mysqli_fetch_assoc($result)['total'];


$sql = "SELECT COUNT(*) AS total FROM item WHERE status_obra = 'Em restauração'";
$result = mysqli_query($strcon, $sql);
$totalEmRestauracao = (int) mysqli_fetch_assoc($result)['total'];


$sql = "SELECT COUNT(*) AS total FROM item WHERE status_obra = 'Reserva Tecnica'";
$result = mysqli_query($strcon, $sql);
$totalReservaTecnica = (int) mysqli_fetch_assoc($result)['total'];


$sql = "SELECT COUNT(*) AS total FROM item WHERE status_obra = 'Indisponivel'";
$result = mysqli_query($strcon, $sql);
$totalIndisponiveis = (int) mysqli_fetch_assoc($result)['total'];


$sql = "
    SELECT
        cod_item,
        nome_item,
        nome_autor,
        dt_criacao,
        dt_aquisicao,
        foto_item,
        status_obra,
        categoria
    FROM item
    ORDER BY dt_aquisicao DESC, cod_item DESC
    LIMIT 5
";


$result = mysqli_query($strcon, $sql);
$itensRecentes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $itensRecentes[] = $row;
}


$sql = "
    SELECT
        categoria,
        COUNT(*) AS total
    FROM item
    WHERE categoria IS NOT NULL
    AND categoria <> ''
    GROUP BY categoria
    ORDER BY total DESC
";


$result = mysqli_query($strcon, $sql);
$categorias = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categorias[] = $row;
}


$labelsCategorias = [];
$valoresCategorias = [];
foreach ($categorias as $categoria) {
    $labelsCategorias[] = $categoria['categoria'];
    $valoresCategorias[] = (int) $categoria['total'];
}


$sql = "
    SELECT
        a.cod_ala,
        a.nome,
        COUNT(DISTINCT r.cod_item) AS total
    FROM alocacao a
    LEFT JOIN registro r
        ON r.cod_ala = a.cod_ala
    GROUP BY
        a.cod_ala,
        a.nome
    ORDER BY
        a.nome
";


$result = mysqli_query($strcon, $sql);
$itensPorAla = [];
while ($row = mysqli_fetch_assoc($result)) {
    $itensPorAla[] = $row;
}


function formatarData($data)
{
    if (empty($data)) {
        return '-';
    }


    $timestamp = strtotime($data);


    if ($timestamp === false) {
        return $data;
    }


    return date('d/m/Y', $timestamp);
}


function classeStatus($status)
{
    $status = trim($status);


    switch ($status) {
        case 'Em Exposição':
            return 'status-exposicao';


        case 'Em Restauração':
            return 'status-restauracao';


        case 'Reserva Técnica':
            return 'status-reserva';


        case 'Indisponível':
            return 'status-indisponivel';


        default:
            return '';
    }
}


function classeCategoria($categoria)
{
    $categoria = trim($categoria);


    switch ($categoria) {
        case 'Quadro':
            return 'categoria-quadro';


        case 'Escultura':
            return 'categoria-escultura';


        case 'Pintura':
            return 'categoria-pintura';


        case 'Fotografia':
            return 'categoria-fotografia';


        case 'Taxidermia':
            return 'categoria-taxidermia';


        case 'Animal em conserva':
            return 'categoria-animal-conserva';


        case 'Fossil':
            return 'categoria-fossil';


        default:
            return 'categoria-sem-cor';
    }
}
?>


<div class="dashboard-grid">


    <!-- CABEÇALHO -->
    <header class="dashboard-header">
        <div class="dashboard-boas-vindas">
            <span id="saudacao">Boa noite</span>
            <h1>Olá, <?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?></h1>
        </div>


        <div class="dashboard-info">
            <div class="dashboard-data">
                <i class="bi bi-calendar3"></i>
                <div>
                    <span>Data</span>
                    <strong id="data-atual">--/--/----</strong>
                </div>
            </div>


            <div class="dashboard-hora">
                <i class="bi bi-clock"></i>
                <div>
                    <span>Horário</span>
                    <strong id="hora-atual">--:--:--</strong>
                </div>
            </div>
        </div>
    </header>


    <!-- CARDS SUPERIORES -->
    <section class="cards">
        <div class="card-item">
            <div class="card-icon-box"><i class="bi bi-palette"></i></div>
            <div class="card-info">
                <strong><?= $totalItens ?></strong>
                <small>Itens cadastrados</small>
            </div>
        </div>


        <div class="card-item">
            <div class="card-icon-box"><i class="bi bi-people"></i></div>
            <div class="card-info">
                <strong><?= $totalFuncionarios ?></strong>
                <small>Funcionários cadastrados</small>
            </div>
        </div>


        <div class="card-item">
            <div class="card-icon-box"><i class="bi bi-easel"></i></div>
            <div class="card-info">
                <strong><?= $totalExposicoes ?></strong>
                <small>Exposições Atuais</small>
            </div>
        </div>


        <div class="card-item">
            <div class="card-icon-box"><i class="bi bi-tools"></i></div>
            <div class="card-info">
                <strong><?= $totalRestauracoes ?></strong>
                <small>Restaurações Atuais</small>
            </div>
        </div>
    </section>


    <!-- COLUNA DA ESQUERDA -->
    <div class="coluna-esquerda">


        <!-- ITENS RECENTES -->
        <section class="lista-area">
            <div class="section-header">
                <div>
                    <i class="bi bi-collection"></i>
                    <h2>Itens Recentes</h2>
                </div>
            </div>


            <div class="itens-lista">
                <?php if (!empty($itensRecentes)): ?>
                    <?php foreach ($itensRecentes as $item): ?>
                        <div class="item">
                            <div class="item-imagem">
                                <?php if (!empty($item['foto_item'])): ?>
                                    <img src="../../assets/img/obras/<?= htmlspecialchars(basename($item['foto_item'])) ?>"
                                        alt="<?= htmlspecialchars($item['nome_item']) ?>">
                                <?php else: ?>
                                    <div class="imagem-placeholder"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                            </div>


                            <div class="item-info">
                                <h3><?= htmlspecialchars($item['nome_item']) ?></h3>
                                <span><?= htmlspecialchars($item['nome_autor'] ?? 'Autor não informado') ?></span>
                                <small><?= formatarData($item['dt_criacao']) ?></small>
                            </div>


                            <span class="categoria <?= classeCategoria($item['categoria'] ?? '') ?>">
                                <?= htmlspecialchars($item['categoria'] ?? 'Sem categoria') ?>
                            </span>


                            <span class="status <?= classeStatus($item['status_obra']) ?>">
                                <?= htmlspecialchars($item['status_obra']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="lista-vazia">
                        <i class="bi bi-inbox"></i>
                        <p>Nenhum item cadastrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>


        <!-- ATIVIDADE RECENTE -->
        <section class="nova-aba-area">
            <div class="section-header">
                <div>
                    <i class="bi bi-clock-history"></i>
                    <h2>Atividade Recente</h2>
                </div>
                <a href="../../components/pages/notificacoes.php" class="btn-ver-todos">Ver todas</a>
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
                                        style="color: <?= htmlspecialchars($atividade['cor_icone']) ?>; font-size: 20px;"></i>
                                <?php endif; ?>
                            </div>


                            <div class="item-info">
                                <h3><?= htmlspecialchars($atividade['nome_funcionario'] ?? 'Sistema') ?></h3>
                                <span>
                                    <?= htmlspecialchars($atividade['acao']) ?>
                                    <?php if (!empty($atividade['descricao'])): ?>
                                        <strong style="color: <?= htmlspecialchars($atividade['cor_icone']) ?>;">
                                            <?= htmlspecialchars($atividade['descricao']) ?>
                                        </strong>
                                    <?php endif; ?>
                                    <?php if (!empty($atividade['nome_item'])): ?>
                                        <small
                                            style="color: #6b7280; font-size: 10px;">(<?= htmlspecialchars($atividade['nome_item']) ?>)</small>
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


    </div>


    <!-- COLUNA DA DIREITA -->
    <div class="coluna-direita">


        <!-- ITENS POR CATEGORIA (GRÁFICO) -->
        <section class="grafico-area">
            <div class="section-header">
                <div>
                    <i class="bi bi-pie-chart-fill"></i>
                    <h2>Itens por Categoria</h2>
                </div>
            </div>


            <div class="grafico-container">
                <canvas id="graficoCategorias"></canvas>


                <div class="legenda-grafico">
                    <?php
                    $cores = ['roxa', 'azul', 'rosa', 'verde', 'laranja', 'marrom', 'vermelho'];
                    foreach ($categorias as $index => $categoria):
                        $totalCategoria = (int) $categoria['total'];
                        $porcentagem = $totalItens > 0 ? round(($totalCategoria / $totalItens) * 100) : 0;
                        $classeCor = $cores[$index % count($cores)];
                        ?>
                        <div>
                            <span class="bolinha <?= $classeCor ?>"></span>
                            <?= htmlspecialchars($categoria['categoria']) ?>
                            <strong><?= $porcentagem ?>% (<?= $totalCategoria ?>)</strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>


        <!-- STATUS DOS ITENS -->
        <section class="status-area">
            <div class="section-header">
                <div>
                    <i class="bi bi-clipboard-data"></i>
                    <h2>Status dos Itens</h2>
                </div>
            </div>


            <div class="status-grid">
                <div class="status-card em-exposicao">
                    <div class="status-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="status-content">
                        <strong><?= $totalEmExposicao ?></strong>
                        <span>Em exposição</span>
                    </div>
                </div>


                <div class="status-card restauracao">
                    <div class="status-icon"><i class="bi bi-tools"></i></div>
                    <div class="status-content">
                        <strong><?= $totalEmRestauracao ?></strong>
                        <span>Em restauração</span>
                    </div>
                </div>


                <div class="status-card reservado">
                    <div class="status-icon"><i class="bi bi-bookmark-fill"></i></div>
                    <div class="status-content">
                        <strong><?= $totalReservaTecnica ?></strong>
                        <span>Reserva técnica</span>
                    </div>
                </div>


                <div class="status-card indisponivel">
                    <div class="status-icon"><i class="bi bi-x-circle-fill"></i></div>
                    <div class="status-content">
                        <strong><?= $totalIndisponiveis ?></strong>
                        <span>Indisponível</span>
                    </div>
                </div>
            </div>
        </section>


        <!-- ITENS POR ALA -->
        <section class="agenda-area">
            <div class="section-header">
                <div>
                    <i class="bi bi-calendar-event"></i>
                    <h2>Itens por Ala</h2>
                </div>
            </div>


            <div class="agenda-grid">
                <?php if (!empty($itensPorAla)): ?>
                    <?php
                    $coresAgenda = [
                        'vermelho',
                        'laranja',
                        'amarelo',
                        'verde',
                        'azulclaro',
                        'azulescuro',
                        'roxo'
                    ];
                    foreach ($itensPorAla as $index => $ala):
                        $classeCorAgenda = $coresAgenda[$index % count($coresAgenda)];
                        $nomeAla = trim($ala['nome']);
                        $letraAla = (mb_strtoupper($nomeAla) === 'DEPOSITO' || mb_strtoupper($nomeAla) === 'DEPÓSITO') ? 'D' : mb_substr($nomeAla, 0, 1);
                        ?>
                        <div class="exposicao">
                            <div class="data <?= $classeCorAgenda ?>">
                                <strong><?= htmlspecialchars($letraAla) ?></strong>
                                <span>ALA</span>
                            </div>
                            <div class="exposicao-info">
                                <h3><?= htmlspecialchars($ala['nome']) ?></h3>
                                <span>Itens alocados</span>
                                <small><?= (int) $ala['total'] ?> item(ns)</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="exposicao">
                        <div class="data">
                            <strong>-</strong>
                            <span>ALA</span>
                        </div>
                        <div class="exposicao-info">
                            <h3>Nenhuma ala cadastrada</h3>
                            <span>Nenhum item alocado</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>


    </div>


</div>


<script>
    window.dashboardCategorias = <?= json_encode($labelsCategorias, JSON_UNESCAPED_UNICODE) ?>;
    window.dashboardValoresCategorias = <?= json_encode($valoresCategorias) ?>;
</script>
<script src="../../assets/js/card.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/dashboard.js"></script>

