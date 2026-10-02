<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../../../backend/Config/conexao.php');

/** @var mysqli $strcon */
mysqli_set_charset($strcon, 'utf8mb4');


/* =========================================================
   CONSULTA DAS ALAS
   ========================================================= */

$sqlAlas = "
    SELECT
        a.cod_ala,
        a.nome,
        a.cor,
        a.descricao,
        a.dt_alocacao,
        a.imagem_capa,
        COUNT(DISTINCT r.cod_item) AS total_obras
    FROM alocacao a
    LEFT JOIN registro r
        ON r.cod_ala = a.cod_ala
    GROUP BY
        a.cod_ala,
        a.nome,
        a.cor,
        a.descricao,
        a.dt_alocacao,
        a.imagem_capa
    ORDER BY a.cod_ala
";

$resultAlas = mysqli_query($strcon, $sqlAlas);

if (!$resultAlas) {
    die('Erro ao consultar as alas: ' . mysqli_error($strcon));
}

$alas = [];

while ($ala = mysqli_fetch_assoc($resultAlas)) {
    $alas[] = $ala;
}


/* =========================================================
   CONSULTA DAS OBRAS POR ALA
   ========================================================= */

$obrasPorAla = [];

$sqlObras = "
    SELECT
        r.cod_ala,
        r.cod_registro,
        r.dt_registro,

        i.cod_item,
        i.nome_item,
        i.nome_autor,
        i.dt_criacao,
        i.foto_item,
        i.categoria,
        i.status_obra

    FROM registro r

    INNER JOIN item i
        ON i.cod_item = r.cod_item

    WHERE
        r.cod_ala = 'ALA008'

        OR (
            r.cod_ala <> 'ALA008'

            AND (
                i.status_obra IS NULL
                OR TRIM(i.status_obra) = ''

                OR LOWER(TRIM(i.status_obra)) NOT IN (
                    'indisponibilidade',
                    'indisponível',
                    'indisponivel',

                    'reserva técnica',
                    'reserva tecnica',

                    'em restauração',
                    'em restauracao'
                )
            )
        )

    ORDER BY
        r.cod_ala,
        r.dt_registro DESC
";

$resultObras = mysqli_query($strcon, $sqlObras);

if (!$resultObras) {
    die('Erro ao consultar as obras: ' . mysqli_error($strcon));
}

while ($obra = mysqli_fetch_assoc($resultObras)) {
    $codAla = $obra['cod_ala'];

    if (!isset($obrasPorAla[$codAla])) {
        $obrasPorAla[$codAla] = [];
    }

    $obrasPorAla[$codAla][] = $obra;
}


/* =========================================================
   CONSULTA DAS EXPOSIÇÕES (ALA007)
   ========================================================= */

$exposicoesAla007 = [];

$sqlExposicoes = "
    SELECT 
        id_exposicao,
        cod_ala,
        titulo,
        autor,
        descricao,
        imagem_capa,
        data_inicio,
        data_fim,
        hora_inicio,
        hora_fim,
        status
    FROM exposicao
    WHERE cod_ala = 'ALA007'
    ORDER BY data_inicio DESC
";

$resultExposicoes = mysqli_query($strcon, $sqlExposicoes);

if ($resultExposicoes) {
    while ($expo = mysqli_fetch_assoc($resultExposicoes)) {
        $exposicoesAla007[] = $expo;
    }
}


/* =========================================================
   FUNÇÕES AUXILIARES
   ========================================================= */

function e(?string $valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatarData(?string $data): string
{
    if (!$data) {
        return '-';
    }

    $timestamp = strtotime($data);

    if (!$timestamp) {
        return $data;
    }

    return date('d/m/Y H:i', $timestamp);
}

function formatarAno(?string $data): string
{
    if (!$data) {
        return '-';
    }

    $timestamp = strtotime($data);

    if (!$timestamp) {
        return '-';
    }

    return date('Y', $timestamp);
}


/* =========================================================
   DADOS PARA O JAVASCRIPT
   ========================================================= */

$dadosPreview = [];

foreach ($alas as $ala) {

    $codAla = $ala['cod_ala'];
    $obrasDaAla = $obrasPorAla[$codAla] ?? [];

    $isAla007 = ($codAla === 'ALA007');
    $totalItens = $isAla007 ? count($exposicoesAla007) : count($obrasDaAla);

    $dadosPreview[$codAla] = [
        'cod_ala'     => $ala['cod_ala'],
        'nome'        => $ala['nome'],
        'cor'         => !empty($ala['cor']) ? $ala['cor'] : '#ffffff',
        'descricao'   => $ala['descricao'] ?? '',
        'dt_alocacao' => $ala['dt_alocacao'],
        'imagem_capa' => $ala['imagem_capa'] ?? '',
        'total_obras' => $totalItens,
        'is_exposicao'=> $isAla007,
        'obras'       => $obrasDaAla,
        'exposicoes'  => $isAla007 ? $exposicoesAla007 : []
    ];
}

?>

<link rel="stylesheet" href="../../assets/css/pages/ala.css">

<div class="alocacoes-container">

    <!-- =====================================================
         ALAS
         ===================================================== -->

    <section class="alas-section">

        <div class="alas-header">
            <div class="alas-titulo">
                <div class="titulo-icone">
                    <i class="bi bi-bank"></i>
                </div>
                <div>
                    <h1>Alas do Museu</h1>
                    <p>Gerencie as alas e visualize o acervo ou exposições alocadas em cada uma dela.</p>
                </div>
            </div>
        </div>

        <div class="alas-grid">
            <?php foreach ($alas as $ala): ?>
                <?php
                $codAla = $ala['cod_ala'];
                $cor = !empty($ala['cor']) ? $ala['cor'] : '#ffffff';
                $totalItens = ($codAla === 'ALA007') ? count($exposicoesAla007) : (int)$ala['total_obras'];
                $labelItem = ($codAla === 'ALA007') ? ($totalItens === 1 ? 'exposição' : 'exposições') : ($totalItens === 1 ? 'obra' : 'obras');
                ?>

                <div
                    class="ala-card"
                    data-cod-ala="<?= e($codAla) ?>"
                    style="--ala-cor: <?= e($cor) ?>;"
                >
                    <div class="ala-brilho"></div>

                    <div class="ala-conteudo">
                        <div class="ala-identificacao">
                            <h2>Ala <?= e($ala['nome']) ?></h2>
                        </div>

                        <p class="ala-data">
                            Criada em <?= e(formatarData($ala['dt_alocacao'])) ?>
                        </p>

                        <div class="ala-footer">
                            <span>
                                <?= $totalItens ?> <?= $labelItem ?>
                            </span>

                            <button type="button" class="btn-abrir-ala">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </section>


    <!-- =====================================================
         PREVIEW
         ===================================================== -->

    <aside class="ala-preview" id="alaPreview">

        <div class="preview-topo">
            <div class="preview-imagem-fundo">
                <div class="preview-sem-imagem" id="previewSemImagem">
                    <i class="bi bi-bank"></i>
                </div>
            </div>

            <a href="#" class="btn-editar-ala" id="btnEditarAla">
                <i class="bi bi-pencil"></i> Editar Ala
            </a>
        </div>

        <div class="preview-conteudo">

            <!-- TÍTULO -->
            <div class="preview-titulo">
                <span class="preview-cor" id="previewCor"></span>
                <div>
                    <h2 id="previewNome">Selecione uma Ala</h2>
                </div>
            </div>

            <!-- TABS -->
            <div class="preview-tabs">
                <button type="button" class="tab ativa" data-tab="informacoes">
                    Informações
                </button>

                <button type="button" class="tab" data-tab="obras" id="tabItensLabel">
                    Obras Associadas <span id="previewQuantidade">0</span>
                </button>
            </div>

            <!-- PAINEL INFORMAÇÕES -->
            <div class="preview-painel" id="painelInformacoes">
                <div class="preview-info">
                    <div class="info-item">
                        <div class="info-icone">
                            <i class="bi bi-collection" id="iconeTotalInfo"></i>
                        </div>
                        <div>
                            <small id="labelTotalInfo">Total de obras</small>
                            <strong id="infoObras">0</strong>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icone">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <div>
                            <small>Data de Criação</small>
                            <strong id="infoData">-</strong>
                        </div>
                    </div>
                </div>

                <!-- DESCRIÇÃO DA ALA -->
                <div class="preview-descricao">
                    <h3>Sobre esta Ala</h3>
                    <p id="previewDescricao">
                        Selecione uma ala para visualizar suas informações.
                    </p>
                </div>
            </div>

            <!-- PAINEL OBRAS / EXPOSIÇÕES -->
            <div class="preview-painel painel-obras" id="painelObras" style="display: none;">
                <div class="preview-obras-header">
                    <h2 id="tituloPainelItens">Obras da Ala</h2>
                    <a href="#" id="btnVerTodas" class="btn-ver-todas">Ver todas</a>
                </div>

                <div class="preview-obras" id="previewObras">
                    <div class="sem-obras">
                        <i class="bi bi-images"></i>
                        <p>Selecione uma ala para visualizar os itens.</p>
                    </div>
                </div>
            </div>

        </div>

    </aside>

</div>


<script>

/* =========================================================
   DADOS DAS ALAS E ELEMENTOS DOM
   ========================================================= */

const dadosAlas = <?= json_encode(
    $dadosPreview,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
) ?>;

const alas = document.querySelectorAll('.ala-card');
const preview = document.getElementById('alaPreview');
const previewNome = document.getElementById('previewNome');
const previewCodigo = document.getElementById('previewCodigo');
const previewCor = document.getElementById('previewCor');
const previewQuantidade = document.getElementById('previewQuantidade');
const infoCodigo = document.getElementById('infoCodigo');
const infoObras = document.getElementById('infoObras');
const infoData = document.getElementById('infoData');
const previewDescricao = document.getElementById('previewDescricao');
const previewObras = document.getElementById('previewObras');
const btnEditarAla = document.getElementById('btnEditarAla');
const btnVerTodas = document.getElementById('btnVerTodas');
const tabItensLabel = document.getElementById('tabItensLabel');
const labelTotalInfo = document.getElementById('labelTotalInfo');
const tituloPainelItens = document.getElementById('tituloPainelItens');


/* =========================================================
   AUXILIARES
   ========================================================= */

function escaparHTML(valor) {
    const div = document.createElement('div');
    div.textContent = valor ?? '';
    return div.innerHTML;
}

function resolverCaminhoImagem(caminho) {
    if (!caminho) return '';
    let url = caminho.replace(/\\/g, '/');
    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/')) {
        return url;
    }
    return '../../../' + url;
}

function formatarData(data) {
    if (!data) return '-';
    const dataObj = new Date(data.replace(' ', 'T'));
    if (isNaN(dataObj.getTime())) return data;
    return (
        dataObj.toLocaleDateString('pt-BR') + ' ' +
        dataObj.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
    );
}

function formatarApenasData(data) {
    if (!data) return '-';
    const partes = data.split('-');
    if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`;
    return data;
}


/* =========================================================
   ATUALIZAR CONTEÚDO (OBRAS E EXPOSIÇÕES)
   ========================================================= */

function atualizarObras(ala) {
    if (!previewObras) return;

    previewObras.innerHTML = '';

    // LÓGICA PARA EXPOSIÇÕES (ALA007)
    if (ala.is_exposicao) {
        const exposicoes = ala.exposicoes || [];

        if (exposicoes.length === 0) {
            previewObras.innerHTML = `
                <div class="sem-obras">
                    <i class="bi bi-easel2"></i>
                    <p>Nenhuma exposição cadastrada nesta ala.</p>
                </div>
            `;
            return;
        }

        exposicoes.slice(0, 4).forEach(expo => {
            const titulo = escaparHTML(expo.titulo);
            const autor = escaparHTML(expo.autor);
            const inicio = formatarApenasData(expo.data_inicio);
            const fim = formatarApenasData(expo.data_fim);
            const status = escaparHTML(expo.status);

            let containerImagem = '';
            if (expo.imagem_capa) {
                const srcImagem = resolverCaminhoImagem(expo.imagem_capa);
                containerImagem = `<img src="${escaparHTML(srcImagem)}" alt="${titulo}">`;
            } else {
                containerImagem = `
                    <div class="obra-sem-imagem">
                        <i class="bi bi-easel2"></i>
                    </div>
                `;
            }

            const article = document.createElement('article');
            article.className = 'obra-mini';
            article.innerHTML = `
                ${containerImagem}
                <div class="obra-mini-conteudo">
                    <strong>${titulo}</strong>
                    <span>${autor}</span>
                    <small><i class="bi bi-calendar3"></i> ${inicio} — ${fim}</small>
                    <small style="margin-top:2px; font-weight:600; color: var(--ala-cor);">${status}</small>
                </div>
            `;
            previewObras.appendChild(article);
        });

        return;
    }

    // LÓGICA PARA OBRAS DEMAIS ALAS
    const obras = ala.obras || [];

    if (obras.length === 0) {
        previewObras.innerHTML = `
            <div class="sem-obras">
                <i class="bi bi-images"></i>
                <p>Nenhuma obra associada a esta ala.</p>
            </div>
        `;
        return;
    }

    obras.slice(0, 4).forEach(obra => {
        const nomeItem = escaparHTML(obra.nome_item);
        const nomeAutor = escaparHTML(obra.nome_autor);
        const categoria = obra.categoria ? `<small>${escaparHTML(obra.categoria)}</small>` : '';

        let containerImagem = '';
        if (obra.foto_item) {
            const srcImagem = resolverCaminhoImagem(obra.foto_item);
            containerImagem = `<img src="${escaparHTML(srcImagem)}" alt="${nomeItem}">`;
        } else {
            containerImagem = `
                <div class="obra-sem-imagem">
                    <i class="bi bi-image"></i>
                </div>
            `;
        }

        let ano = '-';
        if (obra.dt_criacao) {
            const dataCriacao = new Date(obra.dt_criacao + 'T00:00:00');
            if (!isNaN(dataCriacao.getTime())) {
                ano = dataCriacao.getFullYear();
            }
        }

        const article = document.createElement('article');
        article.className = 'obra-mini';
        article.innerHTML = `
            ${containerImagem}
            <div class="obra-mini-conteudo">
                <strong>${nomeItem}</strong>
                <span>${nomeAutor}</span>
                <span>${ano}</span>
                ${categoria}
            </div>
        `;
        previewObras.appendChild(article);
    });
}


/* =========================================================
   SELEÇÃO DA ALA
   ========================================================= */

function selecionarAla(codAla) {
    const ala = dadosAlas[codAla];
    if (!ala) return;

    // Destacar card ativo
    alas.forEach(item => item.classList.remove('selecionada'));
    const cardSelecionado = document.querySelector(`.ala-card[data-cod-ala="${CSS.escape(String(codAla))}"]`);
    if (cardSelecionado) {
        cardSelecionado.classList.add('selecionada');
    }

    // Ajusta labels dinamicamente conforme o tipo da ala (Obras x Exposições)
    if (ala.is_exposicao) {
        tabItensLabel.childNodes[0].nodeValue = 'Exposições ';
        labelTotalInfo.textContent = 'Total de exposições';
        tituloPainelItens.textContent = 'Exposições da Ala';
        
        // REDIRECIONA PARA EXPOSICOES.PHP PASSANDO O COD_ALA
        if (btnVerTodas) {
            btnVerTodas.href = `exposicoes.php?cod_ala=${encodeURIComponent(ala.cod_ala)}`;
        }
    } else {
        tabItensLabel.childNodes[0].nodeValue = 'Obras Associadas ';
        labelTotalInfo.textContent = 'Total de obras';
        tituloPainelItens.textContent = 'Obras da Ala';
        
        // REDIRECIONA PARA OBRAS_ALA.PHP PASSANDO O COD_ALA
        if (btnVerTodas) {
            btnVerTodas.href = `obras_ala.php?cod_ala=${encodeURIComponent(ala.cod_ala)}`;
        }
    }

    // Variáveis CSS e textos
    if (preview) preview.style.setProperty('--preview-cor', ala.cor);
    if (previewNome) previewNome.textContent = `Ala ${ala.nome}`;
    if (previewCodigo) previewCodigo.textContent = ala.cod_ala;

    if (previewCor) {
        previewCor.style.background = ala.cor;
        previewCor.style.boxShadow = `0 0 14px ${ala.cor}`;
    }

    if (previewQuantidade) previewQuantidade.textContent = ala.total_obras;
    if (infoCodigo) infoCodigo.textContent = ala.cod_ala;
    if (infoObras) infoObras.textContent = ala.total_obras;
    if (infoData) infoData.textContent = formatarData(ala.dt_alocacao);

    // Descrição
    if (previewDescricao) {
        if (ala.descricao) {
            previewDescricao.innerHTML = escaparHTML(ala.descricao).replace(/\n/g, '<br>');
        } else {
            const quantidade = Number(ala.total_obras);
            const tipoTexto = ala.is_exposicao 
                ? (quantidade === 1 ? 'exposição' : 'exposições') 
                : (quantidade === 1 ? 'obra' : 'obras');

            previewDescricao.innerHTML = `
                A ala <strong>Ala ${escaparHTML(ala.nome)}</strong> está cadastrada no sistema sob o código 
                <strong>${escaparHTML(ala.cod_ala)}</strong>. Atualmente possui <strong>${quantidade}</strong> ${tipoTexto} associadas.
            `;
        }
    }

    // Atualiza Lista de Itens (Obras/Exposições)
    atualizarObras(ala);

    // Editar Ala
    if (btnEditarAla) {
        btnEditarAla.href = `editar_ala.php?cod_ala=${encodeURIComponent(ala.cod_ala)}`;
    }

    // Imagem da capa
    const containerImagem = document.querySelector('.preview-imagem-fundo');
    if (containerImagem) {
        if (ala.imagem_capa) {
            const srcCapa = resolverCaminhoImagem(ala.imagem_capa);
            containerImagem.innerHTML = `
                <img id="previewImagemPrincipal" src="${escaparHTML(srcCapa)}" alt="Imagem capa da Ala ${escaparHTML(ala.nome)}">
            `;
        } else {
            containerImagem.innerHTML = `
                <div class="preview-sem-imagem" id="previewSemImagem">
                    <i class="bi bi-bank"></i>
                </div>
            `;
        }
    }
}


/* =========================================================
   EVENTOS
   ========================================================= */

alas.forEach(ala => {
    ala.addEventListener('click', function () {
        selecionarAla(this.dataset.codAla);
    });
});


/* =========================================================
   TABS DO PREVIEW
   ========================================================= */

const tabs = document.querySelectorAll('.preview-tabs .tab');
const painelInformacoes = document.getElementById('painelInformacoes');
const painelObras = document.getElementById('painelObras');

tabs.forEach(tab => {
    tab.addEventListener('click', function () {
        tabs.forEach(item => item.classList.remove('ativa'));
        this.classList.add('ativa');

        if (this.dataset.tab === 'obras') {
            if (painelInformacoes) painelInformacoes.style.display = 'none';
            if (painelObras) painelObras.style.display = 'block';
        } else {
            if (painelInformacoes) painelInformacoes.style.display = 'block';
            if (painelObras) painelObras.style.display = 'none';
        }
    });
});


document.addEventListener('DOMContentLoaded', () => {
    const primeiraAlaKey = Object.keys(dadosAlas)[0];
    if (primeiraAlaKey) {
        selecionarAla(primeiraAlaKey);
    }
});

</script>