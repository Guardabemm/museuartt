<?php
require_once __DIR__ . '/../../backend/Config/conexao.php';

/* =========================================================
   CONFIGURAÇÕES INICIAIS, FUSO HORÁRIO & UTF-8
   ========================================================= */

date_default_timezone_set('America/Sao_Paulo');

$codAla = 'ALA007';
$nomeAla = 'G';
mysqli_set_charset($strcon, 'latin1');

/* =========================================================
   FUNÇÕES AUXILIARES
   ========================================================= */

function utf8s($v)
{
    if (is_array($v)) {
        foreach ($v as $k => $x) {
            $v[$k] = utf8s($x);
        }
        return $v;
    }
    if (is_string($v) && $v !== '' && function_exists('mb_check_encoding') && !mb_check_encoding($v, 'UTF-8')) {
        $v = mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1');
    }
    return $v;
}

function h($v)
{
    return htmlspecialchars(utf8s((string)($v ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function statusExpo($inicio, $fim)
{
    $hoje = date('Y-m-d');

    if ($inicio > $hoje) {
        return 'Programada';
    } elseif ($fim < $hoje) {
        return 'Encerrada';
    } else {
        return 'Em andamento';
    }
}

function uploadExpo()
{
    if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio da imagem.');
    }
    if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
    }
    if (!getimagesize($_FILES['imagem']['tmp_name'])) {
        throw new RuntimeException('Arquivo de imagem inválido.');
    }

    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Use JPG, PNG ou WEBP.');
    }

    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
    $dir = __DIR__ . '/../../../assets/img/exposicoes/';

    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Não foi possível criar a pasta de imagens.');
    }

    $file = 'expo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $dir . $file)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }

    return 'assets/img/exposicoes/' . $file;
}

/* =========================================================
   ESTRUTURA DO BANCO DE DADOS
   ========================================================= */

mysqli_query($strcon, "CREATE TABLE IF NOT EXISTS exposicao (
    id_exposicao INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cod_ala CHAR(10) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    imagem_capa VARCHAR(255) NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    hora_inicio TIME NULL,
    hora_fim TIME NULL,
    status ENUM('Programada','Em andamento','Encerrada') NOT NULL DEFAULT 'Programada',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_exposicao_ala FOREIGN KEY (cod_ala) REFERENCES alocacao(cod_ala) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

/* =========================================================
   PROCESSAMENTO DE FORMULÁRIO (POST)
   ========================================================= */

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $acao      = $_POST['acao'] ?? 'cadastrar';
        $id        = (int)($_POST['id_exposicao'] ?? 0);
        $titulo    = trim($_POST['titulo'] ?? '');
        $autor     = trim($_POST['autor'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $inicio    = trim($_POST['data_inicio'] ?? '');
        $fim       = trim($_POST['data_fim'] ?? '');
        $hi        = trim($_POST['horario_inicio'] ?? '');
        $hf        = trim($_POST['horario_fim'] ?? '');
        $hoje      = date('Y-m-d');

        if ($titulo === '' || $autor === '') {
            throw new RuntimeException('Preencha título e autor/responsável.');
        }

        $di = DateTime::createFromFormat('!Y-m-d', $inicio);
        $df = DateTime::createFromFormat('!Y-m-d', $fim);

        if (!$di || !$df || $di->format('Y-m-d') !== $inicio || $df->format('Y-m-d') !== $fim) {
            throw new RuntimeException('Informe datas válidas.');
        }
        if ($fim < $inicio) {
            throw new RuntimeException('A data final não pode ser anterior à inicial.');
        }
        
        // Exige que a data de início seja estritamente posterior a hoje (a partir de amanhã)
        if ($inicio <= $hoje) {
            throw new RuntimeException('A data de início deve ser a partir de amanhã.');
        }
        if ($hi !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hi)) {
            throw new RuntimeException('Horário inicial inválido.');
        }
        if ($hf !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hf)) {
            throw new RuntimeException('Horário final inválido.');
        }

        $status = statusExpo($inicio, $fim);
        $imagem = uploadExpo();

        if ($acao === 'editar') {
            if ($id <= 0) {
                throw new RuntimeException('Exposição inválida.');
            }
            $sql = "UPDATE exposicao SET titulo=?, autor=?, descricao=?, imagem_capa=COALESCE(?,imagem_capa), data_inicio=?, data_fim=?, hora_inicio=NULLIF(?,''), hora_fim=NULLIF(?,''), status=? WHERE id_exposicao=?";
            $st  = mysqli_prepare($strcon, $sql);
            mysqli_stmt_bind_param($st, 'sssssssssi', $titulo, $autor, $descricao, $imagem, $inicio, $fim, $hi, $hf, $status, $id);
            if (!mysqli_stmt_execute($st)) {
                throw new RuntimeException('Não foi possível atualizar a exposição.');
            }
            mysqli_stmt_close($st);
            $mensagem = 'Exposição atualizada com sucesso.';
        } else {
            $sql = "INSERT INTO exposicao (cod_ala,titulo,autor,descricao,imagem_capa,data_inicio,data_fim,hora_inicio,hora_fim,status) VALUES (?,?,?,?,?,?,?,NULLIF(?,''),NULLIF(?,''),?)";
            $st  = mysqli_prepare($strcon, $sql);
            mysqli_stmt_bind_param($st, 'ssssssssss', $codAla, $titulo, $autor, $descricao, $imagem, $inicio, $fim, $hi, $hf, $status);
            if (!mysqli_stmt_execute($st)) {
                throw new RuntimeException('Não foi possível cadastrar a exposição.');
            }
            mysqli_stmt_close($st);
            $mensagem = 'Exposição cadastrada com sucesso.';
        }
        $tipoMensagem = 'sucesso';
    } catch (Throwable $e) {
        $mensagem     = $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

/* =========================================================
   ATUALIZAÇÃO AUTOMÁTICA DE STATUS & BUSCA DE DADOS
   ========================================================= */

$r = mysqli_query($strcon, 'SELECT id_exposicao,data_inicio,data_fim,status FROM exposicao');
if ($r) {
    while ($x = mysqli_fetch_assoc($r)) {
        $novo = statusExpo($x['data_inicio'], $x['data_fim']);
        if ($novo !== $x['status']) {
            $st = mysqli_prepare($strcon, 'UPDATE exposicao SET status=? WHERE id_exposicao=?');
            mysqli_stmt_bind_param($st, 'si', $novo, $x['id_exposicao']);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
        }
    }
}

$lista = [];
$st    = mysqli_prepare($strcon, 'SELECT * FROM exposicao WHERE cod_ala=? ORDER BY data_inicio DESC, id_exposicao DESC');
mysqli_stmt_bind_param($st, 's', $codAla);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
while ($r && ($x = mysqli_fetch_assoc($r))) {
    $lista[] = $x;
}
mysqli_stmt_close($st);

$total       = count($lista);
$andamento   = 0;
$programadas = 0;
$encerradas  = 0;

foreach ($lista as $x) {
    if ($x['status'] === 'Em andamento') {
        $andamento++;
    } elseif ($x['status'] === 'Programada') {
        $programadas++;
    } else {
        $encerradas++;
    }
}

$eventos = [];
$r       = mysqli_query($strcon, "SELECT e.*, COALESCE(a.nome,e.cod_ala) AS nome_ala FROM exposicao e LEFT JOIN alocacao a ON a.cod_ala=e.cod_ala ORDER BY e.data_inicio,e.hora_inicio,e.id_exposicao");
if ($r) {
    while ($x = mysqli_fetch_assoc($r)) {
        $eventos[] = [
            'id'           => (int)$x['id_exposicao'],
            'id_exposicao' => (int)$x['id_exposicao'],
            'cod_ala'      => $x['cod_ala'],
            'ala'          => utf8s($x['nome_ala'] ?: $x['cod_ala']),
            'titulo'       => utf8s($x['titulo']),
            'autor'        => utf8s($x['autor']),
            'descricao'    => utf8s($x['descricao']),
            'imagem_capa'  => $x['imagem_capa'],
            'data_inicio'  => $x['data_inicio'],
            'data_fim'     => $x['data_fim'],
            'inicio'       => $x['data_inicio'],
            'fim'          => $x['data_fim'],
            'hora_inicio'  => $x['hora_inicio'],
            'hora_fim'     => $x['hora_fim'],
            'status'       => utf8s($x['status'])
        ];
    }
}
$jsonEventos = json_encode(utf8s($eventos), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]';

$atuais    = array_values(array_filter($lista, fn($x) => $x['status'] === 'Em andamento'));
$historico = array_values(array_filter($lista, fn($x) => $x['status'] === 'Encerrada'));
$proximas  = array_values(array_filter($lista, fn($x) => $x['status'] === 'Programada'));

usort($proximas, fn($a, $b) => strcmp($a['data_inicio'] . ' ' . $a['hora_inicio'], $b['data_inicio'] . ' ' . $b['hora_inicio']));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MuseuArt - Exposições</title>
    <link rel="stylesheet" href="../../../assets/css/pages/exposicoes.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <main class="exposicoes-page">
        <!-- CABEÇALHO -->
        <header class="page-header">
            <div>
                <div class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    ALA <?= h($nomeAla) ?> · EXPOSIÇÕES TEMPORÁRIAS
                </div>
                <h1>Exposições</h1>
                <p>Organize, acompanhe e consulte as exposições temporárias do MuseuArt.</p>
            </div>
            <button class="btn btn-primary" id="btnNovaExposicao">
                <i class="bi bi-plus-lg"></i>Nova exposição
            </button>
        </header>

        <!-- ESTATÍSTICAS -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-collection"></i></div>
                <div>
                    <span>TOTAL</span>
                    <strong><?= $total ?></strong>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-green"><i class="bi bi-broadcast"></i></div>
                <div>
                    <span>EM ANDAMENTO</span>
                    <strong><?= $andamento ?></strong>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-blue"><i class="bi bi-calendar-event"></i></div>
                <div>
                    <span>PROGRAMADAS</span>
                    <strong><?= $programadas ?></strong>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-archive"></i></div>
                <div>
                    <span>ENCERRADAS</span>
                    <strong><?= $encerradas ?></strong>
                </div>
            </div>
        </section>

        <!-- GRID PRINCIPAL -->
        <section class="content-grid">
            <!-- COLUNA DA ESQUERDA -->
            <div class="left-column">
                <?php foreach ([
                    ['atuais', 'Em andamento', 'Exposições atuais', $atuais, ''],
                    ['historico', 'Histórico', 'Exposições encerradas', $historico, 'card-encerrada']
                ] as $grupo): ?>
                    <section class="panel <?= $grupo[0] === 'historico' ? 'panel-closed' : '' ?>">
                        <div class="panel-header">
                            <div>
                                <div class="section-label"><span></span><?= h(strtoupper($grupo[1])) ?></div>
                                <h2><?= h($grupo[2]) ?></h2>
                            </div>
                        </div>
                        <div class="current-content">
                            <div class="expo-list">
                                <?php if (!$grupo[3]): ?>
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="bi bi-calendar2-x"></i></div>
                                        <h3>Nenhuma exposição</h3>
                                        <p>Não há registros para esta seção.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($grupo[3] as $x): ?>
                                        <article class="expo-card <?= $grupo[4] ?>" data-exposicao-id="<?= (int)$x['id_exposicao'] ?>">
                                            <div class="expo-card-content">
                                                <div class="expo-image">
                                                    <?php if ($x['imagem_capa']): ?>
                                                        <img src="../../../<?= h($x['imagem_capa']) ?>" alt="<?= h($x['titulo']) ?>">
                                                    <?php else: ?>
                                                        <div class="expo-image-placeholder"><i class="bi bi-image"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="expo-info">
                                                    <div class="expo-status"><span></span><?= h($x['status']) ?></div>
                                                    <h3><?= h($x['titulo']) ?></h3>
                                                    <p><?= h($x['descricao']) ?></p>
                                                    <div class="expo-meta">
                                                        <span><i class="bi bi-person"></i><?= h($x['autor']) ?></span>
                                                        <span><i class="bi bi-calendar3"></i><?= date('d/m/Y', strtotime($x['data_inicio'])) ?> — <?= date('d/m/Y', strtotime($x['data_fim'])) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <!-- COLUNA DA DIREITA -->
            <div class="right-column">
                <!-- CALENDÁRIO -->
                <section class="panel calendar-panel">
                    <div class="panel-header calendar-header">
                        <div class="calendar-title"><i class="bi bi-calendar3"></i>CALENDÁRIO</div>
                        <div class="calendar-actions">
                            <button class="calendar-nav" id="mesAnterior"><i class="bi bi-chevron-left"></i></button>
                            <button class="calendar-today" id="mesHoje">Hoje</button>
                            <button class="calendar-nav" id="mesProximo"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="calendar-body">
                        <div class="calendar-month-row">
                            <button class="month-arrow" id="mesAnteriorMobile"><i class="bi bi-chevron-left"></i></button>
                            <h3 id="nomeMes"></h3>
                            <button class="month-arrow" id="mesProximoMobile"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <div class="weekdays">
                            <span>DOM</span><span>SEG</span><span>TER</span><span>QUA</span><span>QUI</span><span>SEX</span><span>SÁB</span>
                        </div>
                        <div class="calendar-grid" id="calendarGrid"></div>
                        <div class="calendar-legend">
                            <span><i class="legend-dot"></i>Exposição</span>
                            <span><i class="legend-today"></i>Hoje</span>
                        </div>
                    </div>
                </section>

                <!-- PRÓXIMOS EVENTOS -->
                <section class="panel upcoming-panel">
                    <div class="panel-header">
                        <div>
                            <div class="section-label"><span></span>PRÓXIMOS EVENTOS</div>
                            <h2>Próximas exposições</h2>
                        </div>
                    </div>
                    <div class="upcoming-list">
                        <?php if (!$proximas): ?>
                            <div class="empty-upcoming"><i class="bi bi-calendar2"></i>Nenhuma exposição programada.</div>
                        <?php else: ?>
                            <?php foreach (array_slice($proximas, 0, 4) as $x): ?>
                                <button class="upcoming-item" data-exposicao-id="<?= (int)$x['id_exposicao'] ?>">
                                    <div class="date-box">
                                        <strong><?= date('d', strtotime($x['data_inicio'])) ?></strong>
                                        <span><?= strtoupper(date('M', strtotime($x['data_inicio']))) ?></span>
                                    </div>
                                    <div class="upcoming-info">
                                        <strong><?= h($x['titulo']) ?></strong>
                                        <small><?= h($x['autor']) ?></small>
                                        <div>
                                            <span><i class="bi bi-clock"></i><?= $x['hora_inicio'] ? date('H:i', strtotime($x['hora_inicio'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </section>
    </main>

    <!-- MODAL: CADASTRO / EDIÇÃO DE EXPOSIÇÃO -->
    <div class="modal-overlay" id="modalExposicao" aria-hidden="true">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <span class="modal-label" id="modalExposicaoLabel">ALA G · NOVA EXPOSIÇÃO</span>
                    <h2 id="modalExposicaoTitulo">Nova exposição</h2>
                    <p id="modalExposicaoDescricao">Cadastre uma nova exposição temporária.</p>
                </div>
                <button class="modal-close" id="fecharModal">&times;</button>
            </div>
            <form class="expo-form" id="formExposicao" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" id="acaoExposicao" value="cadastrar">
                <input type="hidden" name="id_exposicao" id="idExposicao">

                <div class="form-field">
                    <label>Título</label>
                    <input id="titulo" name="titulo" maxlength="255" required>
                </div>
                <div class="form-field">
                    <label>Autor / responsável</label>
                    <input id="autor" name="autor" maxlength="255" required>
                </div>
                <div class="form-field">
                    <label>Descrição</label>
                    <textarea id="descricao" name="descricao" maxlength="3000"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Data de início</label>
                        <!-- Força o campo HTML a desabilitar seleção de hoje ou datas passadas -->
                        <input type="date" id="data_inicio" name="data_inicio" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                    <div class="form-field">
                        <label>Data de término</label>
                        <input type="date" id="data_fim" name="data_fim" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Horário de início</label>
                        <input type="time" id="horario_inicio" name="horario_inicio">
                    </div>
                    <div class="form-field">
                        <label>Horário de término</label>
                        <input type="time" id="horario_fim" name="horario_fim">
                    </div>
                </div>
                <div class="form-field">
                    <label>Imagem <span>(opcional)</span></label>
                    <input type="file" id="imagem" name="imagem" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" id="cancelarModal">Cancelar</button>
                    <button class="btn btn-primary" id="btnSalvarExposicao">
                        <i class="bi bi-check-lg"></i><span>Salvar exposição</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: CALENDÁRIO -->
    <div class="modal-overlay" id="modalCalendario" aria-hidden="true">
        <div class="modal modal-calendario">
            <div class="modal-header">
                <div>
                    <span class="modal-label">CALENDÁRIO · EXPOSIÇÕES</span>
                    <h2 id="calendarioModalTitulo">Exposições</h2>
                    <p id="calendarioModalDescricao"></p>
                </div>
                <button class="modal-close" id="fecharModalCalendario">&times;</button>
            </div>
            <div class="calendario-modal-lista" id="calendarioModalLista"></div>
            <div class="calendario-modal-footer">
               
                
            </div>
        </div>
    </div>

    <!-- MODAL: VISUALIZAR EXPOSIÇÃO -->
    <div class="modal-overlay" id="modalVisualizarExposicao" aria-hidden="true">
        <div class="modal modal-visualizar">
            <div class="modal-header">
                <div>
                    <span class="modal-label" id="visualizarLabel">EXPOSIÇÃO</span>
                    <h2 id="visualizarTitulo"></h2>
                    <p id="visualizarSubtitulo"></p>
                </div>
                <button class="modal-close" id="fecharModalVisualizar">&times;</button>
            </div>
            <div class="visualizar-conteudo" id="visualizarExposicaoConteudo"></div>
            <div class="calendario-modal-footer">
                <button class="btn btn-cancel" id="fecharVisualizacao">Fechar</button>
                <button class="btn btn-primary" id="btnEditarVisualizacao">
                    <i class="bi bi-pencil"></i>Editar exposição
                </button>
            </div>
        </div>
    </div>

    <!-- NOTIFICAÇÕES (TOAST) -->
    <?php if ($mensagem): ?>
        <div class="toast <?= $tipoMensagem === 'sucesso' ? 'toast-success' : 'toast-error' ?>" id="toastMensagem">
            <i class="bi <?= $tipoMensagem === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-circle' ?>"></i>
            <span><?= h($mensagem) ?></span>
        </div>
    <?php endif; ?>

    <!-- SCRIPTS -->
    <script>
        window.MUSEUART_EXPOSICOES = <?= $jsonEventos ?>;
    </script>
    <script src="../../../assets/js/exposicoes.js"></script>
</body>
</html>