<?php

session_start();

require_once '../../../backend/Config/conexao.php';

if (!isset($_GET['cod_ala']) || trim($_GET['cod_ala']) === '') {
    die('Ala não encontrada.');
}

$codAla = trim($_GET['cod_ala']);

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

$stmt = mysqli_prepare($strcon, $sql);

if (!$stmt) {
    die('Erro ao preparar a consulta da ala.');
}

mysqli_stmt_bind_param($stmt, 's', $codAla);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);
$ala = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

if (!$ala) {
    die('Ala não encontrada.');
}


/* =========================================================
   DADOS VISUAIS DA ALA
========================================================= */

$cor = trim($ala['cor']);
if ($cor === '') {
    $cor = '#8f0000';
}

$imagemCapa = '';
if (!empty($ala['imagem_capa'])) {
    $imagemCapa = '../../../' . ltrim($ala['imagem_capa'], '/');
}

$descricao = !empty($ala['descricao']) ? $ala['descricao'] : 'Nenhuma descrição cadastrada.';
$area = !empty($ala['area']) ? $ala['area'] : 'Não informada.';
$status = !empty($ala['status']) ? $ala['status'] : 'Não informado.';

$dtAlocacao = !empty($ala['dt_alocacao'])
    ? date('d/m/Y H:i', strtotime($ala['dt_alocacao']))
    : 'Não informada.';


/* =========================================================
   EXPOSIÇÕES ASSOCIADAS À ALA
========================================================= */

$sqlExposicoes = "
    SELECT
        id_exposicao,
        titulo,
        autor,
        descricao,
        imagem_capa,
        data_inicio,
        data_fim,
        status
    FROM exposicao
    WHERE cod_ala = ?
    ORDER BY data_inicio DESC
";

$stmtExposicoes = mysqli_prepare($strcon, $sqlExposicoes);

if (!$stmtExposicoes) {
    die('Erro ao preparar a consulta das exposições.');
}

mysqli_stmt_bind_param($stmtExposicoes, 's', $codAla);
mysqli_stmt_execute($stmtExposicoes);

$resultadoExposicoes = mysqli_stmt_get_result($stmtExposicoes);

$exposicoes = [];
while ($expo = mysqli_fetch_assoc($resultadoExposicoes)) {
    $exposicoes[] = $expo;
}

mysqli_stmt_close($stmtExposicoes);

$totalExposicoes = count($exposicoes);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($ala['nome']) ?> — Museu Art</title>

    <!-- CSS DO OBRAS_ALA -->
    <link rel="stylesheet" href="../../../assets/css/pages/obras_ala.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>

<div class="painel-container" style="--cor-ala: <?= htmlspecialchars($cor, ENT_QUOTES, 'UTF-8') ?>;">

    <!-- =====================================================
         PAINEL ESQUERDO
    ====================================================== -->

    <aside class="painel-esquerdo">

        <div class="marca-lateral">
            <span class="marca-simbolo">M</span>
            <span>MUSEU ART</span>
        </div>

        <div class="ala-conteudo">

            <?php if ($imagemCapa !== ''): ?>
                <div class="imagem-capa">
                    <img src="<?= htmlspecialchars($imagemCapa) ?>" alt="<?= htmlspecialchars($ala['nome']) ?>">
                    <div class="imagem-overlay"></div>
                </div>
            <?php endif; ?>

            <div class="ala-informacoes">

                <div class="ala-titulo">
                    <span class="rotulo">Ala</span>
                    <h1><?= htmlspecialchars($ala['nome']) ?></h1>
                </div>

                <div class="informacoes-lista">

                    <div class="informacao">
                        <div class="informacao-esquerda">
                            <i class="bi bi-upc-scan"></i>
                            <span class="rotulo">Código da ala</span>
                        </div>
                        <strong><?= htmlspecialchars($ala['cod_ala']) ?></strong>
                    </div>

                    <div class="informacao">
                        <div class="informacao-esquerda">
                            <i class="bi bi-grid-3x3-gap"></i>
                            <span class="rotulo">Área</span>
                        </div>
                        <strong><?= htmlspecialchars($area) ?></strong>
                    </div>

                    <div class="informacao">
                        <div class="informacao-esquerda">
                            <i class="bi bi-circle-fill"></i>
                            <span class="rotulo">Status</span>
                        </div>
                        <strong class="status-valor"><?= htmlspecialchars($status) ?></strong>
                    </div>

                    <div class="informacao">
                        <div class="informacao-esquerda">
                            <i class="bi bi-calendar3"></i>
                            <span class="rotulo">Data de alocação</span>
                        </div>
                        <strong><?= htmlspecialchars($dtAlocacao) ?></strong>
                    </div>

                </div>

                <div class="descricao">
                    <div class="descricao-titulo">
                        <span class="rotulo">Sobre a ala</span>
                        <span class="descricao-linha"></span>
                    </div>
                    <p><?= nl2br(htmlspecialchars($descricao)) ?></p>
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
                    <a href="../ala/alas.php" class="btn-voltar" title="Voltar para alas">
                        <i class="bi bi-arrow-left"></i>
                    </a>

                    <div class="obras-titulo">
                        <span class="rotulo">Galeria da ala</span>
                        <h2>Exposições associadas</h2>
                        <p>Exposições alocadas nesta ala: <strong><?= htmlspecialchars($ala['nome']) ?></strong></p>
                    </div>
                </div>
            </header>

            <div class="linha-ala"></div>

            <section class="acervo-topo">
                <div class="acervo-contagem">
                    <span class="contador-ponto"></span>
                    <span>
                        <?= $totalExposicoes ?>
                        <?= $totalExposicoes === 1 ? 'exposição' : 'exposições' ?>
                        associada<?= $totalExposicoes === 1 ? '' : 's' ?>
                    </span>
                </div>

                <div class="acervo-legenda">
                    EXPOSIÇÕES · <?= htmlspecialchars($ala['nome']) ?>
                </div>
            </section>

            <div class="obras-grid">

                <?php if (empty($exposicoes)): ?>

                    <div class="sem-obras">
                        <div class="sem-obras-icone">
                            <i class="bi bi-easel2"></i>
                        </div>
                        <h2>Nenhuma exposição nesta ala</h2>
                        <p>Ainda não existem exposições associadas a esta ala.</p>
                    </div>

                <?php else: ?>

                    <?php foreach ($exposicoes as $expo): ?>

                        <?php
                        $dataInicio = !empty($expo['data_inicio']) ? date('d/m/Y', strtotime($expo['data_inicio'])) : '-';
                        $dataFim = !empty($expo['data_fim']) ? date('d/m/Y', strtotime($expo['data_fim'])) : '-';
                        $caminhoImagem = !empty($expo['imagem_capa']) ? '../../../' . ltrim($expo['imagem_capa'], '/') : '';
                        ?>

                        <article class="obra-card" data-id="<?= htmlspecialchars($expo['id_exposicao']) ?>">

                            <div class="obra-card-imagem">
                                <?php if ($caminhoImagem !== ''): ?>
                                    <img src="<?= htmlspecialchars($caminhoImagem) ?>" alt="<?= htmlspecialchars($expo['titulo']) ?>">
                                <?php else: ?>
                                    <div class="obra-sem-imagem">
                                        <i class="bi bi-easel2"></i>
                                        <span>Sem imagem</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="obra-card-conteudo">

                                <div class="obra-card-cabecalho">
                                    <div>
                                        <span class="obra-indice">EXPOSIÇÃO</span>
                                        <h3><?= htmlspecialchars($expo['titulo']) ?></h3>
                                    </div>
                                </div>

                                <div class="categoria">
                                    <span class="categoria-geral">
                                        <?= htmlspecialchars($expo['autor'] ?? 'Autor não informado') ?>
                                    </span>
                                </div>

                                <div style="margin-top: 8px; font-size: 0.82rem; color: rgba(255,255,255,0.7);">
                                    <i class="bi bi-calendar-range"></i> <?= $dataInicio ?> até <?= $dataFim ?>
                                </div>

                                <div class="obra-card-footer">
                                    <span class="obra-codigo">
                                        #<?= htmlspecialchars($expo['id_exposicao']) ?>
                                    </span>

                                    <button 
                                        type="button" 
                                        class="btn-visualizar btn-detalhes-exposicao"
                                        data-titulo="<?= htmlspecialchars($expo['titulo']) ?>"
                                        data-autor="<?= htmlspecialchars($expo['autor'] ?? 'Autor não informado') ?>"
                                        data-status="<?= htmlspecialchars($expo['status'] ?? 'Não informado') ?>"
                                        data-periodo="<?= $dataInicio ?> até <?= $dataFim ?>"
                                        data-descricao="<?= htmlspecialchars($expo['descricao'] ?? 'Nenhuma descrição disponível.') ?>"
                                        data-imagem="<?= htmlspecialchars($caminhoImagem) ?>"
                                        data-id="<?= htmlspecialchars($expo['id_exposicao']) ?>"
                                    >
                                        <span>Ver detalhes</span>
                                        <i class="bi bi-arrow-up-right"></i>
                                    </button>
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
     MODAL DETALHES DA EXPOSIÇÃO
========================================================= -->

<div class="modal-ala" id="modalDetalhesExposicao" aria-hidden="true">
    <div class="modal-ala-fundo" data-fechar-modal></div>
    
    <div class="modal-ala-caixa" role="dialog" aria-modal="true" aria-labelledby="modalExpoTitulo" style="max-width: 600px;">
        <div class="modal-ala-cabecalho">
            <div>
                <span class="rotulo" id="modalExpoId">#0</span>
                <h2 id="modalExpoTitulo">Detalhes da Exposição</h2>
            </div>
            <button type="button" class="modal-fechar" data-fechar-modal aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 20px; color: #fff;">
            <div id="modalExpoImagemContainer" style="width: 100%; height: 220px; border-radius: 8px; overflow: hidden; margin-bottom: 20px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                <img id="modalExpoImagem" src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                <div id="modalExpoSemImagem" style="display: none; flex-direction: column; align-items: center; gap: 8px; color: rgba(255,255,255,0.5);">
                    <i class="bi bi-easel2" style="font-size: 2rem;"></i>
                    <span>Sem imagem disponível</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div>
                    <span class="rotulo" style="display: block; font-size: 0.75rem; color: rgba(255,255,255,0.6);">Autor / Curador</span>
                    <strong id="modalExpoAutor" style="font-size: 0.95rem;">-</strong>
                </div>

                <div>
                    <span class="rotulo" style="display: block; font-size: 0.75rem; color: rgba(255,255,255,0.6);">Status</span>
                    <strong id="modalExpoStatus" style="font-size: 0.95rem;">-</strong>
                </div>

                <div>
                    <span class="rotulo" style="display: block; font-size: 0.75rem; color: rgba(255,255,255,0.6);">Período</span>
                    <strong id="modalExpoPeriodo" style="font-size: 0.95rem;">-</strong>
                </div>
            </div>

            <div>
                <span class="rotulo" style="display: block; font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-bottom: 5px;">Descrição</span>
                <p id="modalExpoDescricao" style="font-size: 0.9rem; line-height: 1.5; color: rgba(255,255,255,0.85); white-space: pre-line; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 6px;"></p>
            </div>
        </div>

        <div class="modal-ala-rodape" style="justify-content: flex-end;">
            <button type="button" class="btn-modal-secundario" data-fechar-modal>
                Fechar
            </button>
        </div>
    </div>
</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>
document.addEventListener('DOMContentLoaded', () => {

    /* =====================================================
       CONTROLE DO MODAL DE DETALHES
    ===================================================== */
    const modalDetalhes = document.getElementById('modalDetalhesExposicao');
    const modalTitulo = document.getElementById('modalExpoTitulo');
    const modalId = document.getElementById('modalExpoId');
    const modalAutor = document.getElementById('modalExpoAutor');
    const modalStatus = document.getElementById('modalExpoStatus');
    const modalPeriodo = document.getElementById('modalExpoPeriodo');
    const modalDescricao = document.getElementById('modalExpoDescricao');
    const modalImagem = document.getElementById('modalExpoImagem');
    const modalSemImagem = document.getElementById('modalExpoSemImagem');

    const abrirModalDetalhes = (btn) => {
        const data = btn.dataset;

        modalId.textContent = `#${data.id}`;
        modalTitulo.textContent = data.titulo;
        modalAutor.textContent = data.autor;
        modalStatus.textContent = data.status;
        modalPeriodo.textContent = data.periodo;
        modalDescricao.textContent = data.descricao;

        if (data.imagem && data.imagem.trim() !== '') {
            modalImagem.src = data.imagem;
            modalImagem.style.display = 'block';
            modalSemImagem.style.display = 'none';
        } else {
            modalImagem.src = '';
            modalImagem.style.display = 'none';
            modalSemImagem.style.display = 'flex';
        }

        modalDetalhes.classList.add('aberto');
        modalDetalhes.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-aberto');
    };

    const fecharModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('aberto');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-aberto');
    };

    document.querySelectorAll('.btn-detalhes-exposicao').forEach((botao) => {
        botao.addEventListener('click', (e) => {
            e.stopPropagation();
            abrirModalDetalhes(botao);
        });
    });

    document.querySelectorAll('[data-fechar-modal]').forEach((botao) => {
        botao.addEventListener('click', () => {
            fecharModal(botao.closest('.modal-ala'));
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            fecharModal(modalDetalhes);
        }
    });
});
</script>

</body>
</html>