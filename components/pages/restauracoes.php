<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../backend/config/conexao.php';

$erroRestauracao = null;
$sucessoRestauracao = null;

/* Status restritos padrão */
$statusRestritos = [
    'indisponibilidade', 'indisponível', 'indisponivel',
    'reserva técnica', 'reserva tecnica',
    'em restauração', 'em restauracao'
];

/* =========================================================
   AÇÃO: CONCLUIR RESTAURAÇÃO + DESTINO DA OBRA
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['acao'])
    && $_POST['acao'] === 'concluir_restauracao')
{

    $id_restauracao = intval($_POST['id_restauracao'] ?? 0);
    $destinoObra = trim($_POST['destino_obra'] ?? '');
    $codAlaDestino = trim($_POST['cod_ala_destino'] ?? '');
    $id_funcionario_logado = intval($_SESSION['id_funcionario'] ?? 0);

    $alasPermitidas = [
        'ALA001', 'ALA002', 'ALA003', 'ALA004',
        'ALA005', 'ALA006', 'ALA007'
    ];

    if ($id_funcionario_logado <= 0) {
        $erroRestauracao = 'Sua sessão expirou. Faça login novamente para concluir a restauração.';

    } elseif ($id_restauracao <= 0) {
        $erroRestauracao = 'Restauração inválida.';

    } elseif (!in_array($destinoObra, ['Em Exposição', 'Reserva Técnica'], true)) {
        $erroRestauracao = 'Selecione o destino da obra.';

    } elseif ($destinoObra === 'Em Exposição'
        && !in_array($codAlaDestino, $alasPermitidas, true)) {

        $erroRestauracao = 'Para colocar a obra em exposição, selecione uma ala entre ALA001 e ALA007.';

    } elseif ($destinoObra === 'Reserva Técnica') {
            $codAlaDestino = 'ALA008';
    } else {

    }

    if (empty($erroRestauracao)) {
        mysqli_begin_transaction($strcon);

        try {

            $sqlDadosConclusao = "
                SELECT
                    r.id_restauracao,
                    r.cod_registro,
                    reg.cod_item,
                    reg.id_funcionario
                FROM restauracao r
                INNER JOIN registro reg
                    ON reg.cod_registro = r.cod_registro
                WHERE r.id_restauracao = ?
                  AND r.status IN ('Aguardando', 'Em andamento')
                LIMIT 1
                FOR UPDATE
            ";

            $stmtDados = mysqli_prepare($strcon, $sqlDadosConclusao);

            if (!$stmtDados) {
                throw new Exception(
                    'Erro ao preparar consulta da restauração: ' . mysqli_error($strcon)
                );
            }

            mysqli_stmt_bind_param($stmtDados, 'i', $id_restauracao);
            mysqli_stmt_execute($stmtDados);

            $resultadoDados = mysqli_stmt_get_result($stmtDados);
            $dadosConclusao = mysqli_fetch_assoc($resultadoDados);

            mysqli_stmt_close($stmtDados);

            if (!$dadosConclusao) {
                throw new Exception(
                    'Esta restauração já foi concluída, cancelada ou não foi encontrada.'
                );
            }

            /* VALID AÇÃO DE PERMISSÃO: Apenas quem registrou pode concluir */
            if ((int)$dadosConclusao['id_funcionario'] !== $id_funcionario_logado) {
                throw new Exception(
                    'Você só pode marcar como concluída uma restauração registrada por você.'
                );
            }

            $codRegistro = (int) $dadosConclusao['cod_registro'];
            $codItem = (int) $dadosConclusao['cod_item'];

            $sqlAla = "
                SELECT cod_ala
                FROM alocacao
                WHERE cod_ala = ?
                LIMIT 1
            ";

            $stmtAla = mysqli_prepare($strcon, $sqlAla);

            if (!$stmtAla) {
                throw new Exception(
                    'Erro ao verificar a ala de destino: ' . mysqli_error($strcon)
                );
            }

            mysqli_stmt_bind_param($stmtAla, 's', $codAlaDestino);
            mysqli_stmt_execute($stmtAla);

            $resultadoAla = mysqli_stmt_get_result($stmtAla);
            $alaExiste = mysqli_fetch_assoc($resultadoAla);

            mysqli_stmt_close($stmtAla);

            if (!$alaExiste) {
                throw new Exception(
                    'A ala selecionada não existe no cadastro do MuseuArt.'
                );
            }

            $sqlConcluir = "
                UPDATE restauracao
                SET
                    status = 'Concluída',
                    progresso = 100,
                    data_conclusao = NOW()
                WHERE id_restauracao = ?
                  AND status IN ('Aguardando', 'Em andamento')
            ";

            $stmtConcluir = mysqli_prepare($strcon, $sqlConcluir);

            if (!$stmtConcluir) {
                throw new Exception(
                    'Erro ao preparar conclusão da restauração: ' . mysqli_error($strcon)
                );
            }

            mysqli_stmt_bind_param($stmtConcluir, 'i', $id_restauracao);
            mysqli_stmt_execute($stmtConcluir);

            if (mysqli_stmt_affected_rows($stmtConcluir) <= 0) {
                mysqli_stmt_close($stmtConcluir);

                throw new Exception(
                    'A restauração não pôde ser concluída. Ela pode já ter sido finalizada.'
                );
            }

            mysqli_stmt_close($stmtConcluir);

            $sqlAlocar = "
                UPDATE registro
                SET cod_ala = ?
                WHERE cod_registro = ?
                  AND cod_item = ?
            ";

            $stmtAlocar = mysqli_prepare($strcon, $sqlAlocar);

            if (!$stmtAlocar) {
                throw new Exception(
                    'Erro ao preparar a alocação da obra: ' . mysqli_error($strcon)
                );
            }

            mysqli_stmt_bind_param(
                $stmtAlocar,
                'sii',
                $codAlaDestino,
                $codRegistro,
                $codItem
            );

            if (!mysqli_stmt_execute($stmtAlocar)) {
                mysqli_stmt_close($stmtAlocar);

                throw new Exception(
                    'Erro ao alocar a obra na ala escolhida: ' . mysqli_error($strcon)
                );
            }

            mysqli_stmt_close($stmtAlocar);

            $sqlStatusObra = "
                UPDATE item
                SET status_obra = ?
                WHERE cod_item = ?
            ";

            $stmtStatusObra = mysqli_prepare($strcon, $sqlStatusObra);

            if (!$stmtStatusObra) {
                throw new Exception(
                    'Erro ao preparar atualização do status da obra: ' . mysqli_error($strcon)
                );
            }

            mysqli_stmt_bind_param(
                $stmtStatusObra,
                'si',
                $destinoObra,
                $codItem
            );

            if (!mysqli_stmt_execute($stmtStatusObra)) {
                mysqli_stmt_close($stmtStatusObra);

                throw new Exception(
                    'Erro ao atualizar o status da obra: ' . mysqli_error($strcon)
                );
            }

            mysqli_stmt_close($stmtStatusObra);

            mysqli_commit($strcon);

            $nomeDestino = ($destinoObra === 'Reserva Técnica')
                ? 'Reserva Técnica (ALA008)'
                : 'Exposição (' . $codAlaDestino . ')';

            $sucessoRestauracao =
                'Restauração concluída. A obra foi encaminhada para ' . $nomeDestino . '.';

        } catch (Throwable $e) {

            mysqli_rollback($strcon);

            $erroRestauracao = $e->getMessage();
        }  
    }
}

/* =========================================================
   BUSCA DAS ALAS PARA O MODAL DE CONCLUSÃO
   ========================================================= */
$alasExposicao = [];

$sqlAlasExposicao = "
    SELECT cod_ala, nome
    FROM alocacao
    WHERE cod_ala IN (
        'ALA001', 'ALA002', 'ALA003', 'ALA004',
        'ALA005', 'ALA006', 'ALA007'
    )
    ORDER BY cod_ala ASC
";

$resultAlasExposicao = mysqli_query($strcon, $sqlAlasExposicao);

if ($resultAlasExposicao) {
    while ($ala = mysqli_fetch_assoc($resultAlasExposicao)) {
        $alasExposicao[] = $ala;
    }
}

/* =========================================================
   AÇÃO: NOVA RESTAURAÇÃO
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['acao'])
    && $_POST['acao'] === 'nova_restauracao') {

    $cod_item = intval($_POST['cod_item'] ?? 0);
    $id_funcionario = intval($_SESSION['id_funcionario'] ?? 0);

    if ($id_funcionario <= 0) {
        $erroRestauracao = 'Não foi possível identificar o funcionário logado. Faça login novamente.';
    }

    $cod_ala_deposito = 'ALA008';

    $status = $_POST['status'] ?? 'Aguardando';
    $prioridade = $_POST['prioridade'] ?? 'Média';

    $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
    $data_previsao = !empty($_POST['data_previsao']) ? $_POST['data_previsao'] : null;

    $motivo = trim($_POST['motivo'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $progresso = 0;

    if ($id_funcionario > 0 && empty($erroRestauracao)) {

        $sqlVerificar = "
            SELECT r.id_restauracao
            FROM restauracao r
            INNER JOIN registro reg ON r.cod_registro = reg.cod_registro
            WHERE reg.cod_item = ?
              AND r.status IN ('Aguardando', 'Em andamento')
            LIMIT 1
        ";

        $stmtVerificar = mysqli_prepare($strcon, $sqlVerificar);

        if (!$stmtVerificar) {
            $erroRestauracao = 'Erro ao verificar restauração existente: ' . mysqli_error($strcon);
        } else {

            mysqli_stmt_bind_param($stmtVerificar, 'i', $cod_item);
            mysqli_stmt_execute($stmtVerificar);
            $resultVerificar = mysqli_stmt_get_result($stmtVerificar);
            $jaExiste = mysqli_num_rows($resultVerificar) > 0;
            mysqli_stmt_close($stmtVerificar);

            if ($jaExiste) {
                $erroRestauracao = 'Esta obra já possui uma restauração ativa. Conclua ou cancele a restauração atual antes de criar outra.';
            } else {

                $dt_registro = date('Y-m-d H:i:s');
                $sqlRegistro = "
                    INSERT INTO registro (cod_item, cod_ala, id_funcionario, dt_registro)
                    VALUES (?, ?, ?, ?)
                ";

                $stmtRegistro = mysqli_prepare($strcon, $sqlRegistro);

                if (!$stmtRegistro) {
                    $erroRestauracao = 'Erro ao criar o registro de alocação: ' . mysqli_error($strcon);
                } else {

                    mysqli_stmt_bind_param($stmtRegistro, 'isis', $cod_item, $cod_ala_deposito, $id_funcionario, $dt_registro);

                    if (mysqli_stmt_execute($stmtRegistro)) {

                        $cod_registro = mysqli_insert_id($strcon);
                        mysqli_stmt_close($stmtRegistro);

                        $sqlInsert = "
                            INSERT INTO restauracao
                            (
                                cod_registro,
                                status,
                                prioridade,
                                progresso,
                                data_inicio,
                                data_previsao,
                                motivo,
                                observacoes
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ";

                        $stmt = mysqli_prepare($strcon, $sqlInsert);

                        if (!$stmt) {
                            $erroRestauracao = 'Erro ao preparar cadastro da restauração: ' . mysqli_error($strcon);
                        } else {

                            mysqli_stmt_bind_param(
                                $stmt,
                                'ississss',
                                $cod_registro,
                                $status,
                                $prioridade,
                                $progresso,
                                $data_inicio,
                                $data_previsao,
                                $motivo,
                                $observacoes
                            );

                            if (mysqli_stmt_execute($stmt)) {

                                $sqlItem = "UPDATE item SET status_obra = 'Em Restauração' WHERE cod_item = ?";
                                $stmtItem = mysqli_prepare($strcon, $sqlItem);

                                if ($stmtItem) {
                                    mysqli_stmt_bind_param($stmtItem, 'i', $cod_item);
                                    mysqli_stmt_execute($stmtItem);
                                    mysqli_stmt_close($stmtItem);
                                }

                                $sucessoRestauracao = 'Restauração criada com sucesso!';

                            } else {
                                $erroRestauracao = 'Erro ao salvar restauração: ' . mysqli_stmt_error($stmt);
                            }

                            mysqli_stmt_close($stmt);
                        }

                    } else {
                        $erroRestauracao = 'Erro ao registrar a movimentação da obra: ' . mysqli_stmt_error($stmtRegistro);
                        mysqli_stmt_close($stmtRegistro);
                    }
                }
            }
        }
    }
}

/* =========================================================
   BUSCA DE OBRAS PARA O MODAL (DIVIDIDAS EM DURAÇÃO / EXPOSIÇÃO)
   ========================================================= */
$obrasStatusRestrito = [];
$obrasExposicao = [];

// 1. Obras com status restrito que ainda NÃO possuem NENHUMA restauração cadastrada
$sqlObrasRestritas = "
    SELECT i.cod_item, i.nome_item, i.nome_autor, i.status_obra, i.foto_item
    FROM item i
    WHERE i.dt_arquivo IS NULL
      AND LOWER(TRIM(i.status_obra)) IN ('indisponibilidade', 'indisponível', 'indisponivel', 'reserva técnica', 'reserva tecnica', 'em restauração', 'em restauracao')
      AND NOT EXISTS (
          SELECT 1
          FROM restauracao r
          INNER JOIN registro reg ON r.cod_registro = reg.cod_registro
          WHERE reg.cod_item = i.cod_item
      )
    ORDER BY i.nome_item ASC
";
$resRestritas = mysqli_query($strcon, $sqlObrasRestritas);
if ($resRestritas) {
    while ($row = mysqli_fetch_assoc($resRestritas)) {
        $obrasStatusRestrito[] = $row;
    }
}

// 2. Obras em Exposição (Demais obras sem status restrito e sem restauração ativa)
$sqlObrasExposicao = "
    SELECT i.cod_item, i.nome_item, i.nome_autor, i.status_obra
    FROM item i
    WHERE i.dt_arquivo IS NULL
      AND (i.status_obra IS NULL OR TRIM(i.status_obra) = '' OR LOWER(TRIM(i.status_obra)) NOT IN ('indisponibilidade', 'indisponível', 'indisponivel', 'reserva técnica', 'reserva tecnica', 'em restauração', 'em restauracao'))
      AND NOT EXISTS (
          SELECT 1
          FROM restauracao r
          INNER JOIN registro reg ON r.cod_registro = reg.cod_registro
          WHERE reg.cod_item = i.cod_item
            AND r.status IN ('Aguardando', 'Em andamento')
      )
    ORDER BY i.nome_item ASC
";
$resExposicao = mysqli_query($strcon, $sqlObrasExposicao);
if ($resExposicao) {
    while ($row = mysqli_fetch_assoc($resExposicao)) {
        $obrasExposicao[] = $row;
    }
}

/* =========================================================
   BUSCA DE RESTAURAÇÕES
   ========================================================= */
$restauracoes = [];

$sql = "
    SELECT
        r.id_restauracao,
        r.cod_registro,
        r.status,
        r.prioridade,
        r.progresso,
        r.data_inicio,
        r.data_previsao,
        r.data_conclusao,
        r.motivo,
        r.observacoes,

        reg.cod_item,
        reg.id_funcionario,

        i.nome_item,
        i.nome_autor,
        i.categoria,
        i.foto_item,

        f.nome AS responsavel

    FROM restauracao r

    INNER JOIN registro reg
        ON r.cod_registro = reg.cod_registro

    INNER JOIN item i
        ON reg.cod_item = i.cod_item

    LEFT JOIN funcionario f
        ON reg.id_funcionario = f.id_funcionario

    WHERE NOT (
        r.status IN ('Aguardando', 'Em andamento')
        AND EXISTS (
            SELECT 1
            FROM restauracao r2
            INNER JOIN registro reg2 ON r2.cod_registro = reg2.cod_registro
            WHERE reg2.cod_item = reg.cod_item
              AND r2.status IN ('Aguardando', 'Em andamento')
              AND r2.id_restauracao > r.id_restauracao
        )
    )

    ORDER BY
        CASE
            WHEN r.status = 'Em andamento' THEN 1
            WHEN r.status = 'Aguardando' THEN 2
            WHEN r.status = 'Concluída' THEN 3
            ELSE 4
        END,
        r.data_previsao ASC
";

$resultado = mysqli_query($strcon, $sql);

if (!$resultado) {
    die("Erro ao buscar restaurações: " . mysqli_error($strcon));
}

while ($row = mysqli_fetch_assoc($resultado)) {
    $restauracoes[] = $row;
}

/* =========================================================
   CONTADORES
   ========================================================= */
$totalAndamento = 0;
$totalAguardando = 0;
$totalConcluidas = 0;
$totalAlta = 0;

foreach ($restauracoes as $r) {
    if ($r['status'] === 'Em andamento') {
        $totalAndamento++;
    }
    if ($r['status'] === 'Aguardando') {
        $totalAguardando++;
    }
    if ($r['status'] === 'Concluída') {
        $totalConcluidas++;
    }
    if ($r['prioridade'] === 'Alta') {
        $totalAlta++;
    }
}

/* =========================================================
   FUNÇÕES AUXILIARES
   ========================================================= */
function formatarDataRestauracao($data)
{
    if (empty($data) || $data === '0000-00-00') {
        return '—';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return '—';
    }

    $ano = (int) date('Y', $timestamp);

    if ($ano < 1900 || $ano > 2100) {
        return '—';
    }

    return date('d/m/Y', $timestamp);
}

function fotoRestauracao($foto)
{
    $padrao = '../../../assets/img/obras/imagem-padrao.jpg';

    if (empty($foto)) {
        return $padrao;
    }

    $foto = trim($foto);

    if (
        preg_match('#^https?://#i', $foto) ||
        str_starts_with($foto, '/')
    ) {
        return $foto;
    }

    if (str_starts_with($foto, 'assets/')) {
        return '../../../' . $foto;
    }

    return $foto;
}
?>

<div class="restauracoes-page">

    <!-- MENSAGENS DE FEEDBACK -->
    <?php if (!empty($erroRestauracao)): ?>
        <div class="alert alert-danger" style="padding: 12px; margin-bottom: 20px; border-radius: 6px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
            <i class="bi bi-exclamation-octagon-fill"></i> <?= htmlspecialchars($erroRestauracao) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($sucessoRestauracao)): ?>
        <div class="alert alert-success" style="padding: 12px; margin-bottom: 20px; border-radius: 6px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($sucessoRestauracao) ?>
        </div>
    <?php endif; ?>

    <!-- CABEÇALHO -->
    <section class="restauracoes-header">
        <div>
            <h1>Restaurações</h1>
            <p>Acompanhe o estado de conservação e os processos de restauração das obras.</p>
        </div>
    </section>

    <!-- CARDS SUPERIORES -->
    <section class="restauracao-cards">
        <div class="restauracao-card">
            <div class="rest-card-icon andamento">
                <i class="bi bi-tools"></i>
            </div>
            <div class="rest-card-info">
                <span>EM ANDAMENTO</span>
                <strong><?= $totalAndamento ?></strong>
                <small><?= $totalAndamento == 1 ? 'Restauração' : 'Restaurações' ?></small>
            </div>
        </div>

        <div class="restauracao-card">
            <div class="rest-card-icon aguardando">
                <i class="bi bi-clock-fill"></i>
            </div>
            <div class="rest-card-info">
                <span>AGUARDANDO</span>
                <strong><?= $totalAguardando ?></strong>
                <small><?= $totalAguardando == 1 ? 'Restauração' : 'Restaurações' ?></small>
            </div>
        </div>

        <div class="restauracao-card">
            <div class="rest-card-icon concluida">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="rest-card-info">
                <span>CONCLUÍDAS</span>
                <strong><?= $totalConcluidas ?></strong>
                <small><?= $totalConcluidas == 1 ? 'Restauração' : 'Restaurações' ?></small>
            </div>
        </div>

        <div class="restauracao-card">
            <div class="rest-card-icon prioridade">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="rest-card-info">
                <span>ALTA PRIORIDADE</span>
                <strong><?= $totalAlta ?></strong>
                <small><?= $totalAlta == 1 ? 'Restauração' : 'Restaurações' ?></small>
            </div>
        </div>
    </section>

    <!-- ÁREA PRINCIPAL -->
    <section class="restauracoes-container">

        <!-- FILTROS -->
        <div class="filtros-restauracao">
            <div class="campo-pesquisa-restauracao">
                <i class="bi bi-search"></i>
                <input type="text" id="pesquisaRestauracao" placeholder="Pesquisar restauração...">
            </div>

            <select id="filtroStatusRestauracao">
                <option value="">Todos os status</option>
                <option value="Em andamento">Em andamento</option>
                <option value="Aguardando">Aguardando</option>
                <option value="Cancelada">Cancelada</option>
            </select>

            <select id="filtroPrioridadeRestauracao">
                <option value="">Todas as prioridades</option>
                <option value="Alta">Alta</option>
                <option value="Média">Média</option>
                <option value="Baixa">Baixa</option>
            </select>

            <select id="filtroResponsavelRestauracao">
                <option value="">Todos os responsáveis</option>
                <?php
                $responsaveis = [];
                foreach ($restauracoes as $r) {
                    if (!empty($r['responsavel']) && !in_array($r['responsavel'], $responsaveis)) {
                        $responsaveis[] = $r['responsavel'];
                    }
                }
                sort($responsaveis);
                foreach ($responsaveis as $responsavel):
                ?>
                    <option value="<?= htmlspecialchars($responsavel) ?>">
                        <?= htmlspecialchars($responsavel) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="button" id="limparFiltrosRestauracao" class="btn-limpar">
                <i class="bi bi-arrow-clockwise"></i> Limpar filtros
            </button>

            <button type="button" class="btn-nova-restauracao" id="novaRestauracao">
                <i class="bi bi-plus-lg"></i> Nova restauração
            </button>
        </div>

        <!-- RESTAURAÇÕES PANEL -->
        <div class="restauracoes-panel">
            <div class="panel-title">
                <h2>Restaurações em andamento</h2>
            </div>

            <div class="restauracao-table-header">
                <span>Obra</span>
                <span>Tipo</span>
                <span>Status</span>
                <span>Responsável</span>
                <span>Início</span>
                <span>Previsão</span>
                <span>Prioridade</span>
                <span>Ações</span>
            </div>

            <div class="restauracao-table" id="listaRestauracoes">

    <?php 
    $id_funcionario_logado = (int)($_SESSION['id_funcionario'] ?? 0);
    foreach ($restauracoes as $r): 
    ?>

        <?php if ($r['status'] === 'Concluída'): ?>
            <?php continue; ?>
        <?php endif; ?>

        <div class="restauracao-row"
            data-nome="<?= htmlspecialchars(strtolower($r['nome_item'])) ?>"
            data-autor="<?= htmlspecialchars(strtolower($r['nome_autor'])) ?>"
            data-status="<?= htmlspecialchars($r['status']) ?>"
            data-prioridade="<?= htmlspecialchars($r['prioridade']) ?>"
            data-responsavel="<?= htmlspecialchars($r['responsavel'] ?? '') ?>"
        >

            <!-- OBRA -->
            <div class="obra-col">
                <img
                    src="<?= htmlspecialchars(fotoRestauracao($r['foto_item'])) ?>"
                    alt="<?= htmlspecialchars($r['nome_item']) ?>"
                    onerror="this.src='../../../assets/img/obras/imagem-padrao.jpg'"
                >

                <div class="obra-nome">
                    <strong><?= htmlspecialchars($r['nome_item']) ?></strong>
                    <span><?= htmlspecialchars($r['nome_autor'] ?: 'Desconhecido') ?></span>
                </div>
            </div>

            <!-- TIPO -->
            <div>
                <span class="tipo-badge">
                    <?= htmlspecialchars($r['categoria']) ?>
                </span>
            </div>

            <!-- STATUS -->
            <div>
                <?php
                $classeStatus = 'status-andamento';

                if ($r['status'] === 'Aguardando') {
                    $classeStatus = 'status-aguardando';
                } elseif ($r['status'] === 'Concluída') {
                    $classeStatus = 'status-concluida';
                } elseif ($r['status'] === 'Cancelada') {
                    $classeStatus = 'status-cancelada';
                }
                ?>

                <span class="status-restauracao <?= $classeStatus ?>">
                    <span class="status-ponto"></span>
                    <?= htmlspecialchars($r['status']) ?>
                </span>
            </div>

            <!-- RESPONSÁVEL -->
            <div class="responsavel-col">
                <i class="bi bi-person"></i>
                <span>
                    <?= htmlspecialchars($r['responsavel'] ?: 'Não definido') ?>
                </span>
            </div>

            <!-- INÍCIO -->
            <div class="data-col">
                <?= formatarDataRestauracao($r['data_inicio']) ?>
            </div>

            <!-- PREVISÃO -->
            <div class="data-col">
                <?= formatarDataRestauracao($r['data_previsao']) ?>
            </div>

            <!-- PRIORIDADE -->
            <div>
                <?php
                $classePrioridade = 'prioridade-media';

                if ($r['prioridade'] === 'Alta') {
                    $classePrioridade = 'prioridade-alta';
                } elseif ($r['prioridade'] === 'Baixa') {
                    $classePrioridade = 'prioridade-baixa';
                }
                ?>

                <span class="prioridade-badge <?= $classePrioridade ?>">
                    <?= htmlspecialchars($r['prioridade']) ?>
                </span>
            </div>

            <!-- AÇÕES -->
            <div class="acoes-col">

                <!-- Botão exibido apenas se a restauração estiver pendente E o funcionário logado for quem criou o registro -->
                <?php if (
                    in_array($r['status'], ['Aguardando', 'Em andamento'], true) 
                    && (int)$r['id_funcionario'] === $id_funcionario_logado
                ): ?>

                    <button
                        type="button"
                        class="btn-concluir-restauracao"
                        title="Marcar como concluída"
                        onclick="abrirModalConclusaoRestauracao(
                            <?= (int) $r['id_restauracao'] ?>,
                            <?= htmlspecialchars(
                                json_encode(
                                    $r['nome_item'],
                                    JSON_HEX_TAG |
                                    JSON_HEX_APOS |
                                    JSON_HEX_QUOT |
                                    JSON_HEX_AMP
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        )"
                    >
                        <i class="bi bi-check-lg"></i>
                    </button>

                <?php endif; ?>

                <button
                    type="button"
                    class="btn-acoes"
                    onclick="abrirDetalhesRestauracao(this)"
                    data-restauracao="<?= htmlspecialchars(
                        json_encode(
                            $r,
                            JSON_HEX_TAG |
                            JSON_HEX_APOS |
                            JSON_HEX_QUOT |
                            JSON_HEX_AMP
                        )
                    ) ?>"
                >
                    <i class="bi bi-three-dots-vertical"></i>
                </button>

            </div>

        </div>

    <?php endforeach; ?>

    <div class="sem-resultados" id="semResultados">
        <i class="bi bi-search"></i>
        <strong>Nenhuma restauração encontrada</strong>
        <span>Tente alterar os filtros utilizados.</span>
    </div>

</div>

        <!-- PARTE INFERIOR -->
        <div class="restauracoes-bottom">

            <!-- OBRAS NÃO REGISTRADAS -->
            <div class="bottom-panel">
                <div class="bottom-panel-header">
                    <div>
                        <h2><i class="bi bi-exclamation-circle"></i> Obras não registradas</h2>
                    </div>
                </div>

                <div class="mini-list">
                    <?php
                    $qtdNaoRegistradas = 0;
                    foreach ($obrasStatusRestrito as $obraPend):
                        $qtdNaoRegistradas++;
                    ?>
                        <div class="mini-restauracao">
                            <img src="<?= htmlspecialchars(fotoRestauracao($obraPend['foto_item'])) ?>" alt="<?= htmlspecialchars($obraPend['nome_item']) ?>">
                            <div class="mini-obra">
                                <strong><?= htmlspecialchars($obraPend['nome_item']) ?></strong>
                                <span><?= htmlspecialchars($obraPend['nome_autor'] ?: 'Desconhecido') ?></span>
                            </div>
                            <div class="mini-data">
                                <span>Status:</span>
                                <strong><?= htmlspecialchars($obraPend['status_obra']) ?></strong>
                                <small style="color: #e74c3c;">Pendente de registro</small>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($qtdNaoRegistradas === 0): ?>
                        <div class="mini-vazio">Nenhuma obra pendente de registro.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RESTAURAÇÕES CONCLUÍDAS -->
            <div class="bottom-panel">
                <div class="bottom-panel-header">
                    <div>
                        <h2><i class="bi bi-check-circle"></i> Restaurações concluídas recentemente</h2>
                    </div>
                </div>

                <div class="mini-list">
                    <?php
                    $concluidasRecentes = 0;
                    foreach ($restauracoes as $r):
                        if ($r['status'] !== 'Concluída') continue;
                        if ($concluidasRecentes >= 3) break;
                        $concluidasRecentes++;
                    ?>
                        <div class="mini-restauracao">
                            <img src="<?= htmlspecialchars(fotoRestauracao($r['foto_item'])) ?>" alt="<?= htmlspecialchars($r['nome_item']) ?>">
                            <div class="mini-obra">
                                <strong><?= htmlspecialchars($r['nome_item']) ?></strong>
                                <span><?= htmlspecialchars($r['nome_autor'] ?: 'Desconhecido') ?></span>
                            </div>
                            <div class="mini-data">
                                <span>Concluída em:</span>
                                <strong><?= formatarDataRestauracao($r['data_conclusao']) ?></strong>
                                <small class="concluida-label">Concluída</small>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($concluidasRecentes === 0): ?>
                        <div class="mini-vazio">Nenhuma restauração concluída.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- =========================================================
     MODAL - CONCLUIR RESTAURAÇÃO / DESTINO DA OBRA
     ========================================================= -->
<div class="modal-restauracao modal-conclusao-restauracao" id="modalConclusaoRestauracao">
    <div class="modal-conclusao-restauracao-content">

        <button type="button"
                class="fechar-modal-restauracao"
                onclick="fecharModalConclusaoRestauracao()"
                aria-label="Fechar">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="modal-conclusao-header">
            <div class="modal-conclusao-icone">
                <i class="bi bi-check2-circle"></i>
            </div>

            <div>
                <h2>Concluir restauração</h2>
                <p>Defina o destino da obra após a restauração.</p>
            </div>
        </div>

        <div class="conclusao-obra-info">
            <span>OBRA</span>
            <strong id="nomeObraConclusao">—</strong>
        </div>

        <div class="conclusao-data-info">
            <div>
                <span>DATA DE CONCLUSÃO</span>
                <strong id="dataHoraConclusao">—</strong>
            </div>

            <small>
                A data e o horário serão registrados automaticamente no momento da confirmação.
            </small>
        </div>

        <form method="POST" id="formConclusaoRestauracao">
            <input type="hidden" name="acao" value="concluir_restauracao">
            <input type="hidden" name="id_restauracao" id="idRestauracaoConclusao">
            <input type="hidden" name="cod_ala_destino" id="codAlaDestino">

            <div class="campo-conclusao">
                <label>Destino da obra <span>*</span></label>

                <div class="opcoes-destino">

                    <label class="opcao-destino">
                        <input type="radio"
                               name="destino_obra"
                               value="Em Exposição"
                               onchange="alterarDestinoConclusao(this.value)">

                        <span class="opcao-destino-card">
                            <span class="opcao-destino-icone">
                                <i class="bi bi-easel2"></i>
                            </span>

                            <span>
                                <strong>Em exposição</strong>
                                <small>A obra será disponibilizada em uma das alas de exposição.</small>
                            </span>
                        </span>
                    </label>

                    <label class="opcao-destino">
                        <input type="radio"
                               name="destino_obra"
                               value="Reserva Técnica"
                               onchange="alterarDestinoConclusao(this.value)">

                        <span class="opcao-destino-card">
                            <span class="opcao-destino-icone">
                                <i class="bi bi-archive"></i>
                            </span>

                            <span>
                                <strong>Em reserva técnica</strong>
                                <small>A obra será encaminhada automaticamente para a ALA008.</small>
                            </span>
                        </span>
                    </label>

                </div>
            </div>

            <div class="campo-conclusao" id="campoAlaConclusao" style="display:none;">
                <label for="selectAlaConclusao">
                    Ala de exposição <span>*</span>
                </label>

                <select id="selectAlaConclusao">
                    <option value="">Selecione a ala</option>

                    <?php foreach ($alasExposicao as $ala): ?>
                        <option value="<?= htmlspecialchars($ala['cod_ala']) ?>">
                            <?= htmlspecialchars($ala['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (empty($alasExposicao)): ?>
                    <small class="alerta-alas-conclusao">
                        Nenhuma ala de exposição (ALA001–ALA007) foi encontrada no cadastro.
                    </small>
                <?php endif; ?>
            </div>

            <div class="reserva-tecnica-info" id="infoReservaTecnica" style="display:none;">
                <i class="bi bi-info-circle"></i>

                <div>
                    <strong>Destino automático</strong>
                    <span>
                        A obra será alocada automaticamente na
                        <strong>ALA008</strong>.
                    </span>
                </div>
            </div>

            <div class="botoes-conclusao-restauracao">
                <button type="button"
                        class="btn-cancelar-restauracao"
                        onclick="fecharModalConclusaoRestauracao()">
                    Cancelar
                </button>

                <button type="submit"
                        class="btn-confirmar-conclusao"
                        id="btnConfirmarConclusao">
                    <i class="bi bi-check-lg"></i>
                    Confirmar conclusão
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE DETALHES -->
<div class="modal-restauracao" id="modalRestauracao">
    <div class="modal-restauracao-content">
        <button type="button" class="fechar-modal-restauracao" onclick="fecharDetalhesRestauracao()">
            <i class="bi bi-x-lg"></i>
        </button>
        <div id="conteudoModalRestauracao"></div>
    </div>
</div>

<!-- MODAL - NOVA RESTAURAÇÃO -->
<div class="modal-restauracao" id="modalNovaRestauracao">
    <div class="modal-nova-restauracao">
        <div class="modal-nova-header">
            <div>
                <h2>Nova restauração</h2>
                <p>Cadastre um novo processo de restauração.</p>
            </div>
            <button type="button" class="fechar-modal-restauracao" id="fecharNovaRestauracao">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" id="formNovaRestauracao">
            <input type="hidden" name="acao" value="nova_restauracao">

            <div class="campo-restauracao-form">
                <label>Obra *</label>
                <select name="cod_item" id="novaObra" required>
                    <option value="">Selecione uma obra</option>
                   
                    <?php if (!empty($obrasStatusRestrito)): ?>
                        <optgroup label="Obras em Indisponibilidade / Reserva Técnica / Restauração">
                            <?php foreach ($obrasStatusRestrito as $obra): ?>
                                <option value="<?= (int) $obra['cod_item'] ?>">
                                    <?= htmlspecialchars($obra['nome_item']) ?> — <?= htmlspecialchars($obra['nome_autor'] ?: 'Sem Autor') ?> (<?= htmlspecialchars($obra['status_obra']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>

                    <?php if (!empty($obrasExposicao)): ?>
                        <optgroup label="Obras em Exposição">
                            <?php foreach ($obrasExposicao as $obra): ?>
                                <option value="<?= (int) $obra['cod_item'] ?>">
                                    <?= htmlspecialchars($obra['nome_item']) ?> — <?= htmlspecialchars($obra['nome_autor'] ?: 'Sem Autor') ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>

            <div class="campo-restauracao-form">
                <label>Responsável</label>

                <div class="responsavel-restauracao-info">
                    <div class="responsavel-restauracao-icone">
                        <i class="bi bi-person-check-fill"></i>
                    </div>

                    <div>
                        <strong>
                            <?= htmlspecialchars($_SESSION['nome'] ?? 'Funcionário logado') ?>
                        </strong>

                        <small>
                            Você será o responsável por esta restauração.
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-duas-colunas">
                <div class="campo-restauracao-form">
                    <label>Status</label>
                    <select name="status">
                        <option value="Aguardando">Aguardando</option>
                        <option value="Em andamento">Em andamento</option>
                    </select>
                </div>

                <div class="campo-restauracao-form">
                    <label>Prioridade</label>
                    <select name="prioridade">
                        <option value="Baixa">Baixa</option>
                        <option value="Média" selected>Média</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>
            </div>

            <div class="form-duas-colunas">
                <div class="campo-restauracao-form">
                    <label>Data de início</label>
                    <input type="date" name="data_inicio">
                </div>

                <div class="campo-restauracao-form">
                    <label>Previsão de conclusão</label>
                    <input type="date" name="data_previsao">
                </div>
            </div>

            <div class="campo-restauracao-form">
                <label>Motivo da restauração</label>
                <textarea name="motivo" rows="3" placeholder="Descreva o motivo da restauração..."></textarea>
            </div>

            <div class="campo-restauracao-form">
                <label>Observações</label>
                <textarea name="observacoes" rows="3" placeholder="Adicione observações sobre o processo..."></textarea>
            </div>

            <div class="botoes-nova-restauracao">
                <button type="button" class="btn-cancelar-restauracao" id="cancelarNovaRestauracao">Cancelar</button>
                <button type="submit" class="btn-salvar-restauracao">
                    <i class="bi bi-check-lg"></i> Criar restauração
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* =========================================================
   CONCLUSÃO DA RESTAURAÇÃO
   ========================================================= */

function abrirModalConclusaoRestauracao(idRestauracao, nomeObra) {
    const modal = document.getElementById('modalConclusaoRestauracao');
    const campoId = document.getElementById('idRestauracaoConclusao');
    const campoNome = document.getElementById('nomeObraConclusao');
    const campoData = document.getElementById('dataHoraConclusao');
    const campoAla = document.getElementById('codAlaDestino');
    const selectAla = document.getElementById('selectAlaConclusao');
    const campoAlaContainer = document.getElementById('campoAlaConclusao');
    const infoReserva = document.getElementById('infoReservaTecnica');
    const form = document.getElementById('formConclusaoRestauracao');

    if (!modal || !form) return;

    campoId.value = idRestauracao;
    campoNome.textContent = nomeObra || 'Obra';
    campoData.textContent = obterDataHoraAtual();

    campoAla.value = '';
    selectAla.value = '';
    campoAlaContainer.style.display = 'none';
    infoReserva.style.display = 'none';

    form.querySelectorAll('input[name="destino_obra"]').forEach(function (radio) {
        radio.checked = false;
    });

    modal.classList.add('ativo');
    document.body.style.overflow = 'hidden';
}

function fecharModalConclusaoRestauracao() {
    const modal = document.getElementById('modalConclusaoRestauracao');

    if (!modal) return;

    modal.classList.remove('ativo');
    document.body.style.overflow = '';
}

function alterarDestinoConclusao(destino) {
    const campoAlaContainer = document.getElementById('campoAlaConclusao');
    const selectAla = document.getElementById('selectAlaConclusao');
    const campoAla = document.getElementById('codAlaDestino');
    const infoReserva = document.getElementById('infoReservaTecnica');

    if (destino === 'Em Exposição') {
        campoAlaContainer.style.display = 'block';
        infoReserva.style.display = 'none';

        selectAla.required = true;
        campoAla.value = selectAla.value || '';

    } else if (destino === 'Reserva Técnica') {
        campoAlaContainer.style.display = 'none';
        infoReserva.style.display = 'flex';

        selectAla.required = false;
        selectAla.value = '';
        campoAla.value = 'ALA008';
    }
}

function obterDataHoraAtual() {
    const agora = new Date();

    return agora.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

(function () {
    const modal = document.getElementById('modalConclusaoRestauracao');
    const formConclusao = document.getElementById('formConclusaoRestauracao');
    const selectAla = document.getElementById('selectAlaConclusao');

    if (selectAla) {
        selectAla.addEventListener('change', function () {
            const destino = document.querySelector(
                'input[name="destino_obra"]:checked'
            );

            if (destino && destino.value === 'Em Exposição') {
                document.getElementById('codAlaDestino').value = this.value;
            }
        });
    }

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                fecharModalConclusaoRestauracao();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            fecharModalConclusaoRestauracao();
        }
    });

    if (formConclusao) {
        formConclusao.addEventListener('submit', function (event) {
            const destino = formConclusao.querySelector(
                'input[name="destino_obra"]:checked'
            );

            const select = document.getElementById('selectAlaConclusao');
            const campoAla = document.getElementById('codAlaDestino');

            if (!destino) {
                event.preventDefault();
                alert('Selecione o destino da obra.');
                return;
            }

            if (destino.value === 'Em Exposição') {
                if (!select.value) {
                    event.preventDefault();
                    alert('Selecione obrigatoriamente a ala onde a obra ficará em exposição.');
                    select.focus();
                    return;
                }

                campoAla.value = select.value;
            } else {
                campoAla.value = 'ALA008';
            }

            const botao = document.getElementById('btnConfirmarConclusao');

            if (botao) {
                botao.disabled = true;
                botao.innerHTML =
                    '<i class="bi bi-hourglass-split"></i> Concluindo...';
            }
        });
    }
})();

(function () {
    const form = document.getElementById('formNovaRestauracao');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        const botao = form.querySelector('button[type="submit"]');
        if (!botao) return;

        if (form.dataset.enviando === '1') {
            event.preventDefault();
            return;
        }

        form.dataset.enviando = '1';
        botao.disabled = true;
        botao.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';
    });
})();
</script>

<script src="../../../assets/js/restauracoes.js"></script>