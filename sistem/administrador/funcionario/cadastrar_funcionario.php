<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../../backend/Config/conexao.php";
require_once __DIR__ . "/../../../functions/Atividade/registrar_atividade.php";


/* ============================================================
   VERIFICAR USUÁRIO LOGADO
   ============================================================ */

if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../public/index.php");
    exit();
}


/* ============================================================
   FUNÇÃO DE ERRO
   ============================================================ */

function erroCadastro($mensagem = ''): void
{
    $url = "funcionarioslist.php?cadastro=erro";

    if ($mensagem !== '') {
        $url .= "&mensagem=" . urlencode($mensagem);
    }

    header("Location: " . $url);
    exit();
}


/* ============================================================
   RESPONSÁVEL PELO CADASTRO
   ============================================================ */

$idResponsavel = $_SESSION['id_funcionario'];

$sqlResponsavel = "
    SELECT
        id_funcionario,
        nome,
        tipo,
        foto_usuario
    FROM funcionario
    WHERE id_funcionario = ?
";

$stmtResponsavel = mysqli_prepare($strcon, $sqlResponsavel);

if (!$stmtResponsavel) {
    erroCadastro("Erro ao consultar o responsável pelo cadastro.");
}

mysqli_stmt_bind_param(
    $stmtResponsavel,
    "i",
    $idResponsavel
);

mysqli_stmt_execute($stmtResponsavel);

$resultadoResponsavel = mysqli_stmt_get_result($stmtResponsavel);

$responsavel = mysqli_fetch_assoc($resultadoResponsavel);

mysqli_stmt_close($stmtResponsavel);


/* ============================================================
   DADOS DO RESPONSÁVEL
   ============================================================ */

$nomeResponsavel = $responsavel['nome'] ?? 'Usuário não identificado';
$tipoResponsavel = $responsavel['tipo'] ?? 'Não informado';
$fotoResponsavel = $responsavel['foto_usuario'] ?? '';

if (empty($fotoResponsavel)) {
    $fotoResponsavel = 'assets/img/usuarios/avatarpadrao.jpg';
}


/* ============================================================
   PROCESSAMENTO DO FORMULÁRIO
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* --------------------------------------------------------
       RECEBER DADOS
       -------------------------------------------------------- */

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $telefonebruto = $_POST['telefone'] ?? '';
    $telefone = preg_replace('/\D/', '', $telefonebruto);
    $endereco = trim($_POST['endereco'] ?? '');
    $dtVinculo = $_POST['dt_vinculo'] ?? '';
    $tipo = trim($_POST['tipo'] ?? '');

    /* --------------------------------------------------------
       VALIDAR CAMPOS OBRIGATÓRIOS
       (Sem checar senha/login, pois são gerados pelo sistema)
       -------------------------------------------------------- */

    if (
        empty($nome) ||
        empty($email) ||
        empty($cpf) ||
        empty($telefone) ||
        empty($endereco) ||
        empty($dtVinculo) ||
        empty($tipo)
    ) {
        erroCadastro("Preencha todos os campos obrigatórios.");
    }

    /* --------------------------------------------------------
       GERAR LOGIN E SENHA AUTOMÁTICOS
       -------------------------------------------------------- */

    // 1. Gera um login numérico aleatório de 6 dígitos
    $login = (string) rand(100000, 999999);

    // Garantir que o login gerado seja único no banco
    $sqlCheck = "SELECT id_funcionario FROM funcionario WHERE login = ? LIMIT 1";
    $stmtCheck = mysqli_prepare($strcon, $sqlCheck);

    if ($stmtCheck) {
        mysqli_stmt_bind_param($stmtCheck, "s", $login);
        mysqli_stmt_execute($stmtCheck);
        $resCheck = mysqli_stmt_get_result($stmtCheck);

        // Se o login já existir, sorteia outro até achar um livre
        while (mysqli_num_rows($resCheck) > 0) {
            $login = (string) rand(100000, 999999);
            mysqli_stmt_execute($stmtCheck);
            $resCheck = mysqli_stmt_get_result($stmtCheck);
        }
        mysqli_stmt_close($stmtCheck);
    }

    // 2. Monta a senha padrão (Ex: M.art123456) e criptografa
    $senhaTextoPuro = "M.art" . $login;
    $senhaHash = password_hash($senhaTextoPuro, PASSWORD_DEFAULT);

    /* ========================================================
       UPLOAD DA FOTO
       ======================================================== */

    $fotoUsuario = null;

    if (
        isset($_FILES['foto_usuario']) &&
        $_FILES['foto_usuario']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['foto_usuario']['error'] !== UPLOAD_ERR_OK) {
            erroCadastro("Erro ao enviar a foto.");
        }

        $arquivo = $_FILES['foto_usuario'];

        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        if (!in_array($extensao, $extensoesPermitidas, true)) {
            erroCadastro("Formato de imagem não permitido.");
        }

        if (getimagesize($arquivo['tmp_name']) === false) {
            erroCadastro("O arquivo enviado não é uma imagem válida.");
        }

        if ($arquivo['size'] > 5 * 1024 * 1024) {
            erroCadastro("A imagem deve possuir no máximo 5 MB.");
        }

        $pastaFotos = __DIR__ . "/../../../assets/img/usuarios/";

        if (!is_dir($pastaFotos)) {
            if (!mkdir($pastaFotos, 0755, true)) {
                erroCadastro("Não foi possível criar a pasta de imagens.");
            }
        }

        $nomeArquivo = uniqid('usuario_', true) . '.' . $extensao;
        $caminhoCompleto = $pastaFotos . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            erroCadastro("Não foi possível salvar a imagem.");
        }

        $fotoUsuario = "assets/img/usuarios/" . $nomeArquivo;
    }

    /* ========================================================
       VALIDAR DATA DE VÍNCULO
       ======================================================== */

    $timestampVinculo = strtotime($dtVinculo);

    if ($timestampVinculo === false) {
        if ($fotoUsuario !== null) {
            $arquivoSalvo = __DIR__ . "/../../../" . $fotoUsuario;
            if (file_exists($arquivoSalvo)) {
                unlink($arquivoSalvo);
            }
        }
        erroCadastro("Data de vínculo inválida.");
    }

    $dtVinculoBanco = date('Y-m-d H:i:s', $timestampVinculo);

    /* ========================================================
       CADASTRAR FUNCIONÁRIO NO BANCO DE DADOS
       ======================================================== */

    $sql = "
        INSERT INTO funcionario (
            nome,
            email,
            cpf,
            login,
            senha,
            telefone,
            endereco,
            dt_vinculo,
            tipo,
            foto_usuario
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($strcon, $sql);

    if (!$stmt) {
        if ($fotoUsuario !== null) {
            $arquivoSalvo = __DIR__ . "/../../../" . $fotoUsuario;
            if (file_exists($arquivoSalvo)) {
                unlink($arquivoSalvo);
            }
        }
        erroCadastro("Erro ao preparar o cadastro.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssss",
        $nome,
        $email,
        $cpf,
        $login,      // Salva o login de 6 dígitos sorteado
        $senhaHash,  // Salva a senha "M.art" + login criptografada
        $telefone,
        $endereco,
        $dtVinculoBanco,
        $tipo,
        $fotoUsuario
    );

    if (!mysqli_stmt_execute($stmt)) {
        if ($fotoUsuario !== null) {
            $arquivoSalvo = __DIR__ . "/../../../" . $fotoUsuario;
            if (file_exists($arquivoSalvo)) {
                unlink($arquivoSalvo);
            }
        }
        mysqli_stmt_close($stmt);
        erroCadastro("Erro ao cadastrar funcionário.");
    }

    $novoIdFuncionario = mysqli_insert_id($strcon);
    mysqli_stmt_close($stmt);

    /* ========================================================
       REGISTRAR ATIVIDADE E REDIRECIONAR
       ======================================================== */

    if (function_exists('registrar_atividade')) {
        registrar_atividade(
            $strcon,
            $idResponsavel,
            "Cadastrou novo funcionário: $nome (Login: $login | ID: $novoIdFuncionario)"
        );
    }

    header("Location: funcionarioslist.php?cadastro=sucesso&id=" . $novoIdFuncionario);
    exit();
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

    <title>Cadastrar funcionário</title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="../../../assets/css/design/CRUDS/cruds_funcionarios.css"
    >

</head>


<body>


    <div class="cadastro-overlay">

        <div class="cadastro-modal">


            <!-- ==================================================
                 CABEÇALHO
                 ================================================== -->

            <header class="cadastro-header">

                <div class="cadastro-titulo">

                    <div class="cadastro-icone">

                        <i class="bi bi-person-plus"></i>

                    </div>

                    <div>

                        <h1>Cadastrar funcionário</h1>

                        <p>
                            Adicione um novo funcionário ao sistema.
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    class="btn-fechar"
                    title="Voltar"
                    onclick="window.location.href='funcionarioslist.php'"
                >

                    <i class="bi bi-x-lg"></i>

                </button>

            </header>


            <!-- ==================================================
                 FORMULÁRIO
                 ================================================== -->

            <form
                class="form-funcionario"
                method="POST"
                action=""
                enctype="multipart/form-data"
                autocomplete="off"
            >


                <div class="cadastro-conteudo">


                    <!-- ==================================================
                         DADOS PESSOAIS
                         ================================================== -->

                    <section class="cadastro-secao">


                        <div class="secao-cabecalho">

                            <div class="secao-icone">

                                <i class="bi bi-person"></i>

                            </div>

                            <div>

                                <h2>Dados pessoais</h2>

                                <p>
                                    Informações básicas do funcionário.
                                </p>

                            </div>

                        </div>


                        <div class="form-grid">


                            <div class="campo campo-grande">

                                <label for="nome">
                                    Nome completo <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    placeholder="Digite o nome completo"
                                    maxlength="255"
                                    autocomplete="off"
                                    required
                                >

                            </div>


                            <div class="campo">

                                <label for="cpf">
                                    CPF <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="cpf"
                                    name="cpf"
                                    placeholder="000.000.000-00"
                                    maxlength="14"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    required
                                >

                            </div>


                            <div class="campo">

                                <label for="telefone">
                                    Telefone <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="telefone"
                                    name="telefone"
                                    placeholder="(21)99999-9999"
                                    maxlength="15"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    required
                                >

                            </div>


                            <div class="campo campo-grande">

                                <label for="email">
                                    E-mail <span>*</span>
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    placeholder="exemplo@email.com"
                                    maxlength="255"
                                    autocomplete="off"
                                    required
                                >

                            </div>


                            <div class="campo campo-grande">

                                <label for="endereco">
                                    Endereço <span>*</span>
                                </label>

                                <input
                                    type="text"
                                    id="endereco"
                                    name="endereco"
                                    placeholder="Digite o endereço completo"
                                    maxlength="255"
                                    autocomplete="off"
                                    required
                                >

                            </div>


                            <!-- ==================================================
                                 FOTO
                                 ================================================== -->

                            <div class="campo campo-grande">

                                <label for="foto_usuario">
                                    Foto do funcionário
                                </label>

                                <div class="campo-arquivo">

                                    <input
                                        type="file"
                                        id="foto_usuario"
                                        name="foto_usuario"
                                        accept="image/png, image/jpeg, image/webp"
                                    >

                                    <label
                                        for="foto_usuario"
                                        class="arquivo-label"
                                    >

                                        <i class="bi bi-camera"></i>

                                        <span id="nomeArquivo">
                                            Escolher imagem
                                        </span>

                                    </label>


                                    <button
                                        type="button"
                                        id="btnRemoverFoto"
                                        class="btn-remover-foto"
                                        title="Remover imagem"
                                        disabled
                                    >

                                        <i class="bi bi-trash3"></i>

                                    </button>

                                </div>


                                <small>
                                    JPG, PNG ou WEBP.
                                </small>


                                <div
                                    class="preview-foto"
                                    id="previewFoto"
                                >

                                    <img
                                        id="imagemPreview"
                                        src=""
                                        alt="Pré-visualização da foto"
                                        title="Clique para visualizar"
                                    >

                                    <span>
                                        Clique na imagem para visualizar
                                    </span>

                                </div>

                            </div>


                        </div>

                    </section>


                    <!-- ==================================================
                         ACESSO AO SISTEMA
                         ================================================== -->

                    <section class="cadastro-secao">


                        <div class="secao-cabecalho">

                            <div class="secao-icone">

                                <i class="bi bi-shield-lock"></i>

                            </div>

                            <div>

                                <h2>Acesso ao sistema</h2>

                                <p>
                                    Defina as informações de acesso do funcionário.
                                </p>

                            </div>

                        </div>


                        <div class="form-grid">


                            <div class="campo">
                                <label for="login">Login de Acesso</label>
                                <input 
                                    type="text" 
                                    id="login" 
                                    name="login" 
                                    value="Gerado Automaticamente" 
                                    disabled 
                                    readonly
                                >
                                <small>Um código de 6 dígitos e uma senha padrão serão criados ao salvar.</small>
                            </div>


                            <div class="campo">

                                <label for="tipo">
                                    Tipo de funcionário <span>*</span>
                                </label>

                                <select
                                    id="tipo"
                                    name="tipo"
                                    required
                                >

                                    <option value="">
                                        Selecione o tipo
                                    </option>

                                    <option value="Administrador">
                                        Administrador
                                    </option>

                                    <option value="Registrador">
                                        Registrador
                                    </option>

                                    <option value="Manipulador">
                                        Manipulador
                                    </option>

                                </select>

                            </div>
                        </div>
                    </section>


                    <!-- ==================================================
                         VÍNCULO
                         ================================================== -->

                    <section class="cadastro-secao">


                        <div class="secao-cabecalho">

                            <div class="secao-icone">

                                <i class="bi bi-calendar3"></i>

                            </div>

                            <div>

                                <h2>Vínculo</h2>

                                <p>
                                    Informações relacionadas ao vínculo do funcionário.
                                </p>

                            </div>

                        </div>


                        <div class="form-grid">


                            <div class="campo">

                                <label for="dt_vinculo">
                                    Data de vínculo <span>*</span>
                                </label>

                                <input
                                    type="datetime-local"
                                    id="dt_vinculo"
                                    name="dt_vinculo"
                                    required
                                >

                            </div>


                        </div>

                    </section>


                    <!-- ==================================================
                         RESPONSÁVEL PELO CADASTRO
                         ================================================== -->

                    <section class="cadastro-secao responsavel-secao">


                        <div class="secao-cabecalho">

                            <div class="secao-icone">

                                <i class="bi bi-person-check"></i>

                            </div>

                            <div>

                                <h2>Responsável pelo cadastro</h2>

                                <p>
                                    Usuário responsável por realizar este cadastro.
                                </p>

                            </div>

                        </div>


                        <div class="responsavel-card">


                            <div class="responsavel-foto">

                                <?php if (!empty($fotoResponsavel)): ?>

                                    <img
                                        src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(ltrim($fotoResponsavel, '/')) ?>"
                                        alt="<?= htmlspecialchars($nomeResponsavel) ?>"
                                    >

                                <?php else: ?>

                                    <div class="responsavel-sem-foto">

                                        <i class="bi bi-person"></i>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="responsavel-informacoes">

                                <span class="responsavel-legenda">
                                    CADASTRO REALIZADO POR
                                </span>

                                <strong>
                                    <?= htmlspecialchars($nomeResponsavel) ?>
                                </strong>

                                <span class="responsavel-tipo">
                                    <?= htmlspecialchars($tipoResponsavel) ?>
                                </span>

                            </div>


                            <div class="responsavel-bloqueio">

                                <i class="bi bi-lock"></i>

                                <span>
                                    Somente leitura
                                </span>

                            </div>


                        </div>


                        <!-- ID DO RESPONSÁVEL PELO CADASTRO -->
                        <input
                            type="hidden"
                            name="id_responsavel"
                            value="<?= htmlspecialchars($idResponsavel) ?>"
                        >


                    </section>

                </div>


                <!-- ==================================================
                     RODAPÉ
                     ================================================== -->

                <footer class="cadastro-footer">


                    <button
                        type="button"
                        class="btn-cancelar"
                        onclick="window.location.href='funcionarioslist.php'"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn-cadastrar"
                    >

                        <i class="bi bi-check-lg"></i>

                        Cadastrar funcionário

                    </button>


                </footer>


            </form>


        </div>

    </div>


    <!-- ============================================================
         VISUALIZADOR DA FOTO
         ============================================================ -->

    <div
        class="visualizador-foto"
        id="visualizadorFoto"
    >

        <button
            type="button"
            class="fechar-visualizador"
            id="fecharVisualizador"
        >

            <i class="bi bi-x-lg"></i>

        </button>


        <img
            id="imagemAmpliada"
            src=""
            alt="Foto ampliada"
        >

    </div>


    <!-- ============================================================
         JAVASCRIPT
         ============================================================ -->

    <script>


        /* ========================================================
           MOSTRAR / OCULTAR SENHA
           ======================================================== */

        const btnMostrarSenha =
            document.getElementById('btnMostrarSenha');

        const campoSenha =
            document.getElementById('senha');


        if (btnMostrarSenha && campoSenha) {

            btnMostrarSenha.addEventListener(
                'click',
                function() {

                    const icone =
                        this.querySelector('i');


                    if (campoSenha.type === 'password') {

                        campoSenha.type = 'text';

                        icone.classList.remove(
                            'bi-eye'
                        );

                        icone.classList.add(
                            'bi-eye-slash'
                        );

                    } else {

                        campoSenha.type = 'password';

                        icone.classList.remove(
                            'bi-eye-slash'
                        );

                        icone.classList.add(
                            'bi-eye'
                        );

                    }

                }
            );

        }


        /* ========================================================
           DATA DE VÍNCULO
           ======================================================== */

        const campoDataVinculo =
            document.getElementById('dt_vinculo');


        if (
            campoDataVinculo &&
            !campoDataVinculo.value
        ) {

            const agora = new Date();

            const ano =
                agora.getFullYear();

            const mes =
                String(
                    agora.getMonth() + 1
                ).padStart(2, '0');

            const dia =
                String(
                    agora.getDate()
                ).padStart(2, '0');

            const hora =
                String(
                    agora.getHours()
                ).padStart(2, '0');

            const minuto =
                String(
                    agora.getMinutes()
                ).padStart(2, '0');


            campoDataVinculo.value =
                `${ano}-${mes}-${dia}T${hora}:${minuto}`;

        }


        /* ========================================================
           MÁSCARA CPF
           ======================================================== */

        const inputCPF =
            document.getElementById('cpf');


        if (inputCPF) {

            inputCPF.addEventListener(
                'input',
                (e) => {

                    let value =
                        e.target.value.replace(/\D/g, '');


                    value =
                        value.replace(
                            /(\d{3})(\d)/,
                            '$1.$2'
                        );


                    value =
                        value.replace(
                            /(\d{3})(\d)/,
                            '$1.$2'
                        );


                    value =
                        value.replace(
                            /(\d{3})(\d{1,2})$/,
                            '$1-$2'
                        );


                    e.target.value = value;

                }
            );

        }


        /* ========================================================
        MÁSCARA TELEFONE
        ======================================================== */

        const inputTelefone = document.getElementById('telefone');

        if (inputTelefone) {
            inputTelefone.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é número

                // Limita a 11 dígitos (DDD + 9 números)
                value = value.substring(0, 11);

                // Coloca o DDD entre parênteses: (11) 9
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');

                // Coloca o hífen entre o 5º e 6º dígito do número principal (para Celular)
                value = value.replace(/(\d{5})(\d{1,4})$/, '$1-$2');

                e.target.value = value;
            });
        }


        /* ========================================================
           FOTO
           ======================================================== */

        const inputFoto =
            document.getElementById("foto_usuario");

        const nomeArquivo =
            document.getElementById("nomeArquivo");

        const previewFoto =
            document.getElementById("previewFoto");

        const imagemPreview =
            document.getElementById("imagemPreview");

        const btnRemoverFoto =
            document.getElementById("btnRemoverFoto");

        const visualizadorFoto =
            document.getElementById("visualizadorFoto");

        const imagemAmpliada =
            document.getElementById("imagemAmpliada");

        const fecharVisualizador =
            document.getElementById("fecharVisualizador");


        /* ========================================================
           SELECIONAR FOTO
           ======================================================== */

        if (inputFoto) {

            inputFoto.addEventListener(
                "change",
                function() {

                    const arquivo =
                        this.files[0];


                    if (!arquivo) {
                        return;
                    }


                    if (!arquivo.type.startsWith("image/")) {

                        alert(
                            "Selecione uma imagem válida."
                        );

                        this.value = "";

                        nomeArquivo.textContent =
                            "Escolher imagem";

                        previewFoto.classList.remove(
                            "ativo"
                        );

                        btnRemoverFoto.disabled =
                            true;

                        return;

                    }


                    nomeArquivo.textContent =
                        arquivo.name;

                    btnRemoverFoto.disabled =
                        false;


                    const leitor =
                        new FileReader();


                    leitor.onload =
                        function(evento) {

                            imagemPreview.src =
                                evento.target.result;

                            previewFoto.classList.add(
                                "ativo"
                            );

                        };


                    leitor.readAsDataURL(arquivo);

                }
            );

        }


        /* ========================================================
           REMOVER FOTO
           ======================================================== */

        if (btnRemoverFoto) {

            btnRemoverFoto.addEventListener(
                "click",
                function() {

                    inputFoto.value = "";

                    imagemPreview.src = "";

                    previewFoto.classList.remove(
                        "ativo"
                    );

                    nomeArquivo.textContent =
                        "Escolher imagem";

                    btnRemoverFoto.disabled =
                        true;

                }
            );

        }


        /* ========================================================
           ABRIR FOTO AMPLIADA
           ======================================================== */

        if (imagemPreview) {

            imagemPreview.addEventListener(
                "click",
                function() {

                    if (!this.src) {
                        return;
                    }


                    imagemAmpliada.src =
                        this.src;


                    visualizadorFoto.classList.add(
                        "ativo"
                    );


                    document.body.style.overflow =
                        "hidden";

                }
            );

        }


        /* ========================================================
           FECHAR FOTO
           ======================================================== */

        function fecharImagem() {

            visualizadorFoto.classList.remove(
                "ativo"
            );

            imagemAmpliada.src = "";

            document.body.style.overflow = "";

        }


        if (fecharVisualizador) {

            fecharVisualizador.addEventListener(
                "click",
                function() {

                    fecharImagem();

                }
            );

        }


        /* ========================================================
           FECHAR CLICANDO FORA DA IMAGEM
           ======================================================== */

        if (visualizadorFoto) {

            visualizadorFoto.addEventListener(
                "click",
                function(evento) {

                    if (
                        evento.target ===
                        visualizadorFoto
                    ) {

                        fecharImagem();

                    }

                }
            );

        }


        /* ========================================================
           FECHAR COM ESC
           ======================================================== */

        document.addEventListener(
            "keydown",
            function(evento) {

                if (
                    evento.key === "Escape" &&
                    visualizadorFoto &&
                    visualizadorFoto.classList.contains("ativo")
                ) {

                    fecharImagem();

                }

            }
        );


    </script>


    <!-- ============================================================
         DESATIVAR AUTOCOMPLETE GLOBAL
         ============================================================ -->

    <script
        src="../../../assets/js/desativar-autocomplete.js"
        defer
    ></script>


</body>

</html>