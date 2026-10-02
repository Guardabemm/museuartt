<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../../backend/Config/conexao.php";

if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../../index.php");
    exit();
}


$idFuncionario = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idFuncionario) {
    header("Location: funcionarioslist.php");
    exit();
}


$sql = "
    SELECT
        id_funcionario,
        nome,
        email,
        cpf,
        login,
        senha,
        telefone,
        endereco,
        dt_vinculo,
        dt_arquivo,
        tipo,
        foto_usuario
    FROM funcionario
    WHERE id_funcionario = ?
    LIMIT 1
";

$stmt = mysqli_prepare(
    $strcon,
    $sql
);

if (!$stmt) {
    die("Erro ao preparar consulta.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $idFuncionario
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

$funcionario = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);



if (!$funcionario) {
    die("Funcionário não encontrado.");
}



$nome = $funcionario['nome'] ?? '';
$email = $funcionario['email'] ?? '';
$cpf = $funcionario['cpf'] ?? '';
$login = $funcionario['login'] ?? '';
$telefone = $funcionario['telefone'] ?? '';
$endereco = $funcionario['endereco'] ?? '';
$tipo = $funcionario['tipo'] ?? '';

$fotoAtual = $funcionario['foto_usuario'] ?? '';

$dtVinculo = '';

if (!empty($funcionario['dt_vinculo'])) {

    $timestamp = strtotime(
        $funcionario['dt_vinculo']
    );

    if ($timestamp !== false) {

        $dtVinculo = date(
            'Y-m-d\TH:i',
            $timestamp
        );
    }
}


$dtArquivo = '';

if (!empty($funcionario['dt_arquivo'])) {

    $timestamp = strtotime(
        $funcionario['dt_arquivo']
    );

    if ($timestamp !== false) {

        $dtArquivo = date(
            'Y-m-d\TH:i',
            $timestamp
        );
    }
}



$fotoUrl = '';

if (!empty($fotoAtual)) {

    $fotoUrl =
        '/SistemaMuseuArt.GuardaBem/' .
        ltrim($fotoAtual, '/');
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $dtVinculo = $_POST['dt_vinculo'] ?? '';
    $tipo = trim($_POST['tipo'] ?? '');

    $removerFoto =
        isset($_POST['remover_foto']) &&
        $_POST['remover_foto'] === '1';



    if (
        empty($nome) ||
        empty($email) ||
        empty($cpf) ||
        empty($login) ||
        empty($telefone) ||
        empty($endereco) ||
        empty($dtVinculo) ||
        empty($tipo)
    ) {

        header(
            "Location: editar_funcionario.php?id=" .
            $idFuncionario .
            "&erro=" .
            urlencode("Preencha todos os campos obrigatórios.")
        );

        exit();
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        header(
            "Location: editar_funcionario.php?id=" .
            $idFuncionario .
            "&erro=" .
            urlencode("Informe um e-mail válido.")
        );

        exit();
    }


    if (strlen($login) > 6) {

        header(
            "Location: editar_funcionario.php?id=" .
            $idFuncionario .
            "&erro=" .
            urlencode("O login deve possuir no máximo 6 caracteres.")
        );

        exit();
    }


    if ($senha !== '' && strlen($senha) > 16) {

        header(
            "Location: editar_funcionario.php?id=" .
            $idFuncionario .
            "&erro=" .
            urlencode("A senha deve possuir no máximo 16 caracteres.")
        );

        exit();
    }



    $sqlLogin = "
        SELECT id_funcionario
        FROM funcionario
        WHERE login = ?
        AND id_funcionario <> ?
        LIMIT 1
    ";

    $stmtLogin = mysqli_prepare(
        $strcon,
        $sqlLogin
    );

    if (!$stmtLogin) {
        die("Erro ao verificar login.");
    }

    mysqli_stmt_bind_param(
        $stmtLogin,
        "si",
        $login,
        $idFuncionario
    );

    mysqli_stmt_execute($stmtLogin);

    $resultadoLogin =
        mysqli_stmt_get_result($stmtLogin);

    if (mysqli_num_rows($resultadoLogin) > 0) {

        mysqli_stmt_close($stmtLogin);

        header(
            "Location: editar_funcionario.php?id=" .
            $idFuncionario .
            "&erro=" .
            urlencode("Este login já está cadastrado.")
        );

        exit();
    }

    mysqli_stmt_close($stmtLogin);


    $timestampVinculo =
        strtotime($dtVinculo);

    if ($timestampVinculo === false) {

        header(
            "Location: editar_funcionario.php?id=" .
            $idFuncionario .
            "&erro=" .
            urlencode("Data de vínculo inválida.")
        );

        exit();
    }

    $dtVinculoBanco =
        date(
            'Y-m-d H:i:s',
            $timestampVinculo
        );


    $novaFoto = $fotoAtual;

    $fotoNovaSalva = null;



    if ($removerFoto) {

        $novaFoto = null;
    }


    if (
        isset($_FILES['foto_usuario']) &&
        $_FILES['foto_usuario']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['foto_usuario']['error'] !==
            UPLOAD_ERR_OK
        ) {

            header(
                "Location: editar_funcionario.php?id=" .
                $idFuncionario .
                "&erro=" .
                urlencode("Erro ao enviar a foto.")
            );

            exit();
        }


        $arquivo = $_FILES['foto_usuario'];


        if (
            $arquivo['size'] >
            5 * 1024 * 1024
        ) {

            header(
                "Location: editar_funcionario.php?id=" .
                $idFuncionario .
                "&erro=" .
                urlencode("A imagem deve possuir no máximo 5 MB.")
            );

            exit();
        }



        if (
            getimagesize(
                $arquivo['tmp_name']
            ) === false
        ) {

            header(
                "Location: editar_funcionario.php?id=" .
                $idFuncionario .
                "&erro=" .
                urlencode("O arquivo enviado não é uma imagem válida.")
            );

            exit();
        }



        $finfo = new finfo(
            FILEINFO_MIME_TYPE
        );

        $mime =
            $finfo->file(
                $arquivo['tmp_name']
            );


        $extensoesPermitidas = [

            'image/jpeg' => 'jpg',

            'image/png' => 'png',

            'image/webp' => 'webp'

        ];


        if (
            !isset(
            $extensoesPermitidas[$mime]
        )
        ) {

            header(
                "Location: editar_funcionario.php?id=" .
                $idFuncionario .
                "&erro=" .
                urlencode(
                    "Formato de imagem não permitido."
                )
            );

            exit();
        }


        $extensao =
            $extensoesPermitidas[$mime];


        $pastaFotos =
            __DIR__ .
            "/../../../assets/img/usuarios/";


        if (!is_dir($pastaFotos)) {

            if (
                !mkdir(
                    $pastaFotos,
                    0755,
                    true
                )
            ) {

                header(
                    "Location: editar_funcionario.php?id=" .
                    $idFuncionario .
                    "&erro=" .
                    urlencode(
                        "Não foi possível criar a pasta de imagens."
                    )
                );

                exit();
            }
        }


        $nomeArquivo =
            'usuario_' .
            $idFuncionario .
            '_' .
            bin2hex(
                random_bytes(6)
            ) .
            '.' .
            $extensao;


        $caminhoFoto =
            $pastaFotos .
            $nomeArquivo;


        if (
            !move_uploaded_file(
                $arquivo['tmp_name'],
                $caminhoFoto
            )
        ) {

            header(
                "Location: editar_funcionario.php?id=" .
                $idFuncionario .
                "&erro=" .
                urlencode(
                    "Não foi possível salvar a imagem."
                )
            );

            exit();
        }


        $fotoNovaSalva =
            $caminhoFoto;


        $novaFoto =
            "assets/img/usuarios/" .
            $nomeArquivo;
    }



    if ($senha !== '') {



        $sqlUpdate = "
            UPDATE funcionario
            SET
                nome = ?,
                email = ?,
                cpf = ?,
                login = ?,
                senha = ?,
                telefone = ?,
                endereco = ?,
                dt_vinculo = ?,
                tipo = ?,
                foto_usuario = ?
            WHERE id_funcionario = ?
        ";


        $senhaBanco =
            password_hash(
                $senha,
                PASSWORD_DEFAULT
            );


        $stmtUpdate =
            mysqli_prepare(
                $strcon,
                $sqlUpdate
            );


        if (!$stmtUpdate) {

            if ($fotoNovaSalva !== null) {
                @unlink($fotoNovaSalva);
            }

            die(
                "Erro ao preparar atualização."
            );
        }


        mysqli_stmt_bind_param(
            $stmtUpdate,
            "ssssssssssi",
            $nome,
            $email,
            $cpf,
            $login,
            $senhaBanco,
            $telefone,
            $endereco,
            $dtVinculoBanco,
            $tipo,
            $novaFoto,
            $idFuncionario
        );

    } else {


        $sqlUpdate = "
            UPDATE funcionario
            SET
                nome = ?,
                email = ?,
                cpf = ?,
                login = ?,
                telefone = ?,
                endereco = ?,
                dt_vinculo = ?,
                tipo = ?,
                foto_usuario = ?
            WHERE id_funcionario = ?
        ";


        $stmtUpdate =
            mysqli_prepare(
                $strcon,
                $sqlUpdate
            );


        if (!$stmtUpdate) {

            if ($fotoNovaSalva !== null) {
                @unlink($fotoNovaSalva);
            }

            die(
                "Erro ao preparar atualização."
            );
        }


        mysqli_stmt_bind_param(
            $stmtUpdate,
            "sssssssssi",
            $nome,
            $email,
            $cpf,
            $login,
            $telefone,
            $endereco,
            $dtVinculoBanco,
            $tipo,
            $novaFoto,
            $idFuncionario
        );
    }


    if (!mysqli_stmt_execute($stmtUpdate)) {

        if ($fotoNovaSalva !== null) {
            @unlink($fotoNovaSalva);
        }

        mysqli_stmt_close($stmtUpdate);

        header(
            "Location: editar_funcionario.php?id=" .
            $idFuncionario .
            "&erro=" .
            urlencode(
                "Não foi possível atualizar o funcionário."
            )
        );

        exit();
    }


    mysqli_stmt_close($stmtUpdate);



    if (
        !empty($fotoAtual) &&
        (
            $fotoNovaSalva !== null ||
            $removerFoto
        )
    ) {

        $fotoAntiga =
            __DIR__ .
            "/../../../" .
            ltrim(
                $fotoAtual,
                '/'
            );


        if (
            file_exists($fotoAntiga) &&
            is_file($fotoAntiga)
        ) {

            @unlink($fotoAntiga);
        }
    }


    header(
        "Location: funcionarioslist.php?edicao=sucesso"
    );

    exit();
}



$mensagemErro =
    $_GET['erro'] ?? '';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Editar funcionário
    </title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel= "stylesheet" href="../../../assets/css/design/CRUDS/editar_funcionario.css">

</head>

<body>


    <?php if ($mensagemErro !== ''): ?>

        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 20000;
            padding: 14px 18px;
            background: #302020;
            border: 1px solid #633c3c;
            border-radius: 9px;
            color: #e5a0a0;
            font-size: 13px;
            box-shadow: 0 10px 30px rgba(0,0,0,.4);
        ">

            <i class="bi bi-exclamation-circle"></i>

            <?= htmlspecialchars($mensagemErro) ?>

        </div>

    <?php endif; ?>


    <div class="cadastro-overlay">

        <div class="cadastro-modal">



            <header class="cadastro-header">

                <div class="cadastro-titulo">

                    <div class="cadastro-icone">

                        <i class="bi bi-person-gear"></i>

                    </div>

                    <div>

                        <h1>
                            Editar funcionário
                        </h1>

                        <p>
                            Altere as informações do funcionário selecionado.
                        </p>

                    </div>

                </div>


                <button type="button" class="btn-fechar" title="Voltar"
                    onclick="window.location.href='funcionarioslist.php'">

                    <i class="bi bi-x-lg"></i>

                </button>

            </header>




            <form class="form-funcionario" method="POST" action="editar_funcionario.php?id=<?= $idFuncionario ?>"
                enctype="multipart/form-data">

                <input type="hidden" name="id_funcionario" value="<?= $idFuncionario ?>">


                <div class="cadastro-conteudo">


                    <section class="cadastro-secao">

                        <div class="secao-cabecalho">

                            <div class="secao-icone">

                                <i class="bi bi-person"></i>

                            </div>

                            <div>

                                <h2>
                                    Dados pessoais
                                </h2>

                                <p>
                                    Informações básicas do funcionário.
                                </p>

                            </div>

                        </div>


                        <div class="form-grid">




                            <div class="campo campo-grande">

                                <label for="nome">

                                    Nome completo

                                    <span>*</span>

                                </label>

                                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>"
                                    maxlength="255" autocomplete="name" required>

                            </div>




                            <div class="campo">

                                <label for="cpf">

                                    CPF

                                    <span>*</span>

                                </label>

                                <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($cpf) ?>"
                                    placeholder="000.000.000-00" maxlength="14" inputmode="numeric" required>

                            </div>




                            <div class="campo">

                                <label for="telefone">

                                    Telefone

                                    <span>*</span>

                                </label>

                                <input type="text" id="telefone" name="telefone"
                                    value="<?= htmlspecialchars($telefone) ?>" maxlength="11" inputmode="numeric"
                                    required>

                            </div>


                            <div class="campo campo-grande">

                                <label for="email">

                                    E-mail

                                    <span>*</span>

                                </label>

                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>"
                                    maxlength="255" autocomplete="email" required>

                            </div>




                            <div class="campo campo-grande">

                                <label for="endereco">

                                    Endereço

                                    <span>*</span>

                                </label>

                                <input type="text" id="endereco" name="endereco"
                                    value="<?= htmlspecialchars($endereco) ?>" maxlength="255"
                                    autocomplete="street-address" required>

                            </div>



                            <div class="campo campo-grande">

                                <label for="foto_usuario">

                                    Foto do funcionário

                                </label>


                                <div class="campo-arquivo">

                                    <input type="file" id="foto_usuario" name="foto_usuario"
                                        accept="image/png, image/jpeg, image/webp">


                                    <label for="foto_usuario" class="arquivo-label">

                                        <i class="bi bi-camera"></i>

                                        <span id="nomeArquivo">

                                            <?php if (!empty($fotoAtual)): ?>

                                                Alterar imagem

                                            <?php else: ?>

                                                Escolher imagem

                                            <?php endif; ?>

                                        </span>

                                    </label>


                                    <button type="button" id="btnRemoverFoto" class="btn-remover-foto"
                                        title="Remover imagem" <?= empty($fotoAtual) ? 'disabled' : '' ?>>

                                        <i class="bi bi-trash3"></i>

                                    </button>

                                </div>


                                <small>

                                    JPG, PNG ou WEBP.

                                </small>


                                <div class="preview-foto <?= !empty($fotoUrl) ? 'ativo' : '' ?>" id="previewFoto">

                                    <img id="imagemPreview" src="<?= htmlspecialchars($fotoUrl) ?>"
                                        alt="Foto do funcionário" title="Clique para visualizar">

                                    <span>

                                        Clique na imagem para visualizar.

                                    </span>

                                </div>


                                <input type="hidden" name="remover_foto" id="remover_foto" value="0">

                            </div>

                        </div>

                    </section>



                    <section class="cadastro-secao">

                        <div class="secao-cabecalho">

                            <div class="secao-icone">

                                <i class="bi bi-shield-lock"></i>

                            </div>

                            <div>

                                <h2>
                                    Acesso ao sistema
                                </h2>

                                <p>
                                    Altere as informações de acesso do funcionário.
                                </p>

                            </div>

                        </div>


                        <div class="form-grid">




                            <div class="campo">

                                <label for="login">

                                    Login

                                    <span>*</span>

                                </label>

                                <input type="text" id="login" name="login" value="<?= htmlspecialchars($login) ?>"
                                    maxlength="6" autocomplete="username" required>

                                <small>

                                    O login deve possuir até 6 caracteres.

                                </small>

                            </div>



                            <div class="campo">

                                <label for="tipo">

                                    Tipo de funcionário

                                    <span>*</span>

                                </label>

                                <select id="tipo" name="tipo" required>

                                    <option value="">
                                        Selecione o tipo
                                    </option>

                                    <option value="Administrador" <?= $tipo === 'Administrador' ? 'selected' : '' ?>>
                                        Administrador
                                    </option>

                                    <option value="Registrador" <?= $tipo === 'Registrador' ? 'selected' : '' ?>>
                                        Registrador
                                    </option>

                                    <option value="Manipulador" <?= $tipo === 'Manipulador' ? 'selected' : '' ?>>
                                        Manipulador
                                    </option>

                                </select>

                            </div>



                            <div class="campo campo-grande">

                                <label for="senha">

                                    Nova senha

                                </label>


                                <div class="campo-senha">

                                    <input type="password" id="senha" name="senha"
                                        placeholder="Deixe vazio para manter a senha atual" maxlength="16"
                                        autocomplete="new-password">


                                    <button type="button" class="btn-mostrar-senha" id="btnMostrarSenha"
                                        title="Mostrar senha">

                                        <i class="bi bi-eye"></i>

                                    </button>

                                </div>


                                <small>

                                    Preencha somente se desejar alterar a senha.

                                </small>

                            </div>

                        </div>

                    </section>



                    <section class="cadastro-secao">

                        <div class="secao-cabecalho">

                            <div class="secao-icone">

                                <i class="bi bi-calendar3"></i>

                            </div>

                            <div>

                                <h2>
                                    Vínculo
                                </h2>

                                <p>
                                    Informações relacionadas ao vínculo.
                                </p>

                            </div>

                        </div>


                        <div class="form-grid">



                            <div class="campo">

                                <label for="dt_vinculo">

                                    Data de vínculo

                                    <span>*</span>

                                </label>

                                <input type="datetime-local" id="dt_vinculo" name="dt_vinculo"
                                    value="<?= htmlspecialchars($dtVinculo) ?>" required>

                            </div>




                            <div class="campo">

                                <label for="dt_arquivo">

                                    Data de arquivo

                                </label>

                                <input type="datetime-local" id="dt_arquivo" value="<?= htmlspecialchars($dtArquivo) ?>"
                                    disabled>

                                <small>

                                    Preenchida somente quando o funcionário for arquivado.

                                </small>

                            </div>

                        </div>

                    </section>



                    <section class="cadastro-secao sistema-secao">

                        <div class="informacao-sistema">

                            <i class="bi bi-info-circle"></i>

                            <div>

                                <strong>
                                    Funcionário selecionado
                                </strong>

                                <p>

                                    ID do funcionário:
                                    <strong>
                                        #<?= $idFuncionario ?>
                                    </strong>

                                </p>

                                <p>

                                    As alterações realizadas nesta tela serão
                                    aplicadas somente ao funcionário selecionado.

                                </p>

                            </div>

                        </div>

                    </section>


                </div>




                <footer class="cadastro-footer">

                    <button type="button" class="btn-cancelar" onclick="window.location.href='funcionarioslist.php'">

                        Cancelar

                    </button>


                    <button type="submit" class="btn-cadastrar">

                        <i class="bi bi-check-lg"></i>

                        Salvar alterações

                    </button>

                </footer>

            </form>

        </div>

    </div>


    <div class="visualizador-foto" id="visualizadorFoto">

        <button type="button" class="fechar-visualizador" id="fecharVisualizador">

            <i class="bi bi-x-lg"></i>

        </button>


        <img id="imagemAmpliada" src="" alt="Foto ampliada">

    </div>



    <script>




        const btnMostrarSenha =
            document.getElementById(
                'btnMostrarSenha'
            );

        const campoSenha =
            document.getElementById(
                'senha'
            );


        if (
            btnMostrarSenha &&
            campoSenha
        ) {

            btnMostrarSenha.addEventListener(
                'click',
                function () {

                    const icone =
                        this.querySelector('i');


                    if (
                        campoSenha.type ===
                        'password'
                    ) {

                        campoSenha.type =
                            'text';

                        icone.classList.remove(
                            'bi-eye'
                        );

                        icone.classList.add(
                            'bi-eye-slash'
                        );

                    } else {

                        campoSenha.type =
                            'password';

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





        const inputCPF =
            document.getElementById(
                'cpf'
            );


        if (inputCPF) {

            inputCPF.addEventListener(
                'input',
                function (e) {

                    let value =
                        e.target.value.replace(
                            /\D/g,
                            ''
                        );


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


                    e.target.value =
                        value;

                }
            );

        }





        const inputFoto =
            document.getElementById(
                'foto_usuario'
            );

        const nomeArquivo =
            document.getElementById(
                'nomeArquivo'
            );

        const previewFoto =
            document.getElementById(
                'previewFoto'
            );

        const imagemPreview =
            document.getElementById(
                'imagemPreview'
            );

        const btnRemoverFoto =
            document.getElementById(
                'btnRemoverFoto'
            );

        const removerFoto =
            document.getElementById(
                'remover_foto'
            );


        if (inputFoto) {

            inputFoto.addEventListener(
                'change',
                function () {

                    const arquivo =
                        this.files[0];


                    if (!arquivo) {
                        return;
                    }


                    if (
                        !arquivo.type.startsWith(
                            'image/'
                        )
                    ) {

                        alert(
                            'Selecione uma imagem válida.'
                        );

                        this.value = '';

                        return;
                    }


                    nomeArquivo.textContent =
                        arquivo.name;


                    btnRemoverFoto.disabled =
                        false;


                    removerFoto.value =
                        '0';


                    const leitor =
                        new FileReader();


                    leitor.onload =
                        function (evento) {

                            imagemPreview.src =
                                evento.target.result;

                            previewFoto.classList.add(
                                'ativo'
                            );

                        };


                    leitor.readAsDataURL(
                        arquivo
                    );

                }
            );

        }





        if (btnRemoverFoto) {

            btnRemoverFoto.addEventListener(
                'click',
                function () {

                    inputFoto.value = '';

                    imagemPreview.src = '';

                    previewFoto.classList.remove(
                        'ativo'
                    );

                    nomeArquivo.textContent =
                        'Escolher imagem';


                    removerFoto.value =
                        '1';


                    btnRemoverFoto.disabled =
                        true;

                }
            );

        }





        const visualizadorFoto =
            document.getElementById(
                'visualizadorFoto'
            );

        const imagemAmpliada =
            document.getElementById(
                'imagemAmpliada'
            );

        const fecharVisualizador =
            document.getElementById(
                'fecharVisualizador'
            );


        if (imagemPreview) {

            imagemPreview.addEventListener(
                'click',
                function () {

                    if (!this.src) {
                        return;
                    }


                    imagemAmpliada.src =
                        this.src;


                    visualizadorFoto.classList.add(
                        'ativo'
                    );


                    document.body.style.overflow =
                        'hidden';

                }
            );

        }


        function fecharImagem() {

            visualizadorFoto.classList.remove(
                'ativo'
            );

            imagemAmpliada.src =
                '';

            document.body.style.overflow =
                '';

        }


        if (fecharVisualizador) {

            fecharVisualizador.addEventListener(
                'click',
                fecharImagem
            );

        }


        if (visualizadorFoto) {

            visualizadorFoto.addEventListener(
                'click',
                function (evento) {

                    if (
                        evento.target ===
                        visualizadorFoto
                    ) {

                        fecharImagem();

                    }

                }
            );

        }


        document.addEventListener(
            'keydown',
            function (evento) {

                if (
                    evento.key ===
                    'Escape' &&
                    visualizadorFoto.classList.contains(
                        'ativo'
                    )
                ) {

                    fecharImagem();

                }

            }
        );

    </script>

</body>

</html>