<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../../backend/Config/conexao.php";

/*
|--------------------------------------------------------------------------
| VERIFICAR ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Funcionário não encontrado.");
}

$id_funcionario = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| BUSCAR FUNCIONÁRIO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id_funcionario,
        nome,
        email,
        cpf,
        login,
        telefone,
        endereco,
        dt_vinculo,
        dt_arquivo,
        tipo,
        foto_usuario
    FROM funcionario
    WHERE id_funcionario = ?
";

$stmt = mysqli_prepare($strcon, $sql);

if (!$stmt) {
    die("Erro ao preparar consulta: " . mysqli_error($strcon));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_funcionario
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

$funcionario = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

if (!$funcionario) {
    die("Funcionário não encontrado.");
}

/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DO CPF
|--------------------------------------------------------------------------
*/

$cpf = $funcionario['cpf'];

if (!empty($cpf)) {

    $cpfNumeros = preg_replace('/\D/', '', $cpf);

    if (strlen($cpfNumeros) === 11) {

        $cpf =
            substr($cpfNumeros, 0, 3) . "." .
            substr($cpfNumeros, 3, 3) . "." .
            substr($cpfNumeros, 6, 3) . "-" .
            substr($cpfNumeros, 9, 2);
    }
}

/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DO TELEFONE
|--------------------------------------------------------------------------
*/

$telefone = $funcionario['telefone'];

if (!empty($telefone)) {

    $telefoneNumeros = preg_replace('/\D/', '', $telefone);

    if (strlen($telefoneNumeros) === 11) {

        $telefone =
            "(" . substr($telefoneNumeros, 0, 2) . ") " .
            substr($telefoneNumeros, 2, 5) . "-" .
            substr($telefoneNumeros, 7, 4);
    }
}

/*
|--------------------------------------------------------------------------
| FORMATAÇÃO DAS DATAS
|--------------------------------------------------------------------------
*/

$dt_vinculo = '';

if (!empty($funcionario['dt_vinculo'])) {

    $dt_vinculo = date(
        'd/m/Y H:i',
        strtotime($funcionario['dt_vinculo'])
    );
}

$dt_arquivo = '';

if (!empty($funcionario['dt_arquivo'])) {

    $dt_arquivo = date(
        'd/m/Y H:i',
        strtotime($funcionario['dt_arquivo'])
    );
}

/*
|--------------------------------------------------------------------------
| FOTO
|--------------------------------------------------------------------------
*/

$foto = !empty($funcionario['foto_usuario'])
    ? $funcionario['foto_usuario']
    : '/assets/img/usuarios/avatarpadrao.jpg';

if (strpos($foto, '/SistemaMuseuArt.GuardaBem/') !== 0) {

    $foto =
        '/SistemaMuseuArt.GuardaBem/' .
        ltrim($foto, '/');
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

    <title>
        Visualizar funcionário
    </title>

    <link
        rel="stylesheet"
        href="../../../assets/css/design/CRUDS/visualizar_funcionario.css"
    >

</head>

<body>

<main class="visualizador-funcionario">

    <!--
    |--------------------------------------------------------------------------
    | BOTÃO FECHAR
    |--------------------------------------------------------------------------
    -->

    <button
        type="button"
        class="btn-fechar"
        onclick="fecharVisualizador()"
        title="Fechar"
        aria-label="Fechar"
    >

        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            fill="currentColor"
            viewBox="0 0 16 16"
        >

            <path
                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"
            />

        </svg>

    </button>


    <!--
    |--------------------------------------------------------------------------
    | TÍTULO
    |--------------------------------------------------------------------------
    -->

    <div class="visualizador-titulo">

        <span class="visualizador-legenda">
            FUNCIONÁRIO
        </span>

        <h1>
            Informações do funcionário
        </h1>

        <p>
            Consulte as informações cadastrais e funcionais deste funcionário.
        </p>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | CARD PRINCIPAL
    |--------------------------------------------------------------------------
    -->

    <section class="funcionario-card">


        <!--
        |--------------------------------------------------------------------------
        | TOPO
        |--------------------------------------------------------------------------
        -->

        <div class="funcionario-card-topo">

            <div class="funcionario-foto">

                <img
                    src="<?= htmlspecialchars($foto, ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($funcionario['nome'], ENT_QUOTES, 'UTF-8') ?>"
                >

            </div>


            <div class="funcionario-identificacao">

                <h2>
                    <?= htmlspecialchars(
                        $funcionario['nome'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <div class="funcionario-meta">

                    <span class="tipo-funcionario">

                        <?= htmlspecialchars(
                            $funcionario['tipo'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                    <span class="id-funcionario">

                        ID #<?= htmlspecialchars(
                            $funcionario['id_funcionario'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </span>

                </div>

            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | INFORMAÇÕES
        |--------------------------------------------------------------------------
        -->

        <div class="funcionario-informacoes">


            <div class="secao-titulo">

                <div class="secao-icone">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        fill="currentColor"
                        viewBox="0 0 16 16"
                    >

                        <path
                            d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"
                        />

                        <path
                            d="M8 9a5 5 0 0 0-5 5 .5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5 5 5 0 0 0-5-5zm-4 4a4 4 0 0 1 8 0H4z"
                        />

                    </svg>

                </div>

                <div>

                    <strong>
                        Dados pessoais
                    </strong>

                    <span>
                        Informações cadastrais
                    </span>

                </div>

            </div>


            <div class="campos-grid">


                <!-- NOME -->

                <div class="campo">

                    <label>
                        Nome completo
                    </label>

                    <div class="valor">

                        <?= htmlspecialchars(
                            $funcionario['nome'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="campo">

                    <label>
                        E-mail
                    </label>

                    <div class="valor">

                        <?= htmlspecialchars(
                            $funcionario['email'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                </div>


                <!-- CPF -->

                <div class="campo">

                    <label>
                        CPF
                    </label>

                    <div class="valor">

                        <?= !empty($cpf)
                            ? htmlspecialchars(
                                $cpf,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'Não informado'
                        ?>

                    </div>

                </div>


                <!-- TELEFONE -->

                <div class="campo">

                    <label>
                        Telefone
                    </label>

                    <div class="valor">

                        <?= !empty($telefone)
                            ? htmlspecialchars(
                                $telefone,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'Não informado'
                        ?>

                    </div>

                </div>


                <!-- ENDEREÇO -->

                <div class="campo campo-largo">

                    <label>
                        Endereço
                    </label>

                    <div class="valor">

                        <?= !empty($funcionario['endereco'])
                            ? htmlspecialchars(
                                $funcionario['endereco'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'Não informado'
                        ?>

                    </div>

                </div>


            </div>


            <!--
            |--------------------------------------------------------------------------
            | DADOS FUNCIONAIS
            |--------------------------------------------------------------------------
            -->

            <div class="secao-titulo secao-funcional">

                <div class="secao-icone">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        fill="currentColor"
                        viewBox="0 0 16 16"
                    >

                        <path
                            d="M6.5 2a.5.5 0 0 1 .5.5V3h2v-.5a.5.5 0 0 1 1 0V3h1.5A1.5 1.5 0 0 1 13 4.5v8A1.5 1.5 0 0 1 11.5 14h-7A1.5 1.5 0 0 1 3 12.5v-8A1.5 1.5 0 0 1 4.5 3H6v-.5a.5.5 0 0 1 .5-.5z"
                        />

                        <path
                            d="M4 6h8v1H4V6zm0 2h8v1H4V8zm0 2h5v1H4v-1z"
                        />

                    </svg>

                </div>

                <div>

                    <strong>
                        Dados funcionais
                    </strong>

                    <span>
                        Informações relacionadas ao vínculo
                    </span>

                </div>

            </div>


            <div class="campos-grid">


                <!-- FUNÇÃO -->

                <div class="campo">

                    <label>
                        Função
                    </label>

                    <div class="valor">

                        <?= htmlspecialchars(
                            $funcionario['tipo'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                </div>


                <!-- DATA VÍNCULO -->

                <div class="campo">

                    <label>
                        Data de vínculo
                    </label>

                    <div class="valor">

                        <?= !empty($dt_vinculo)
                            ? htmlspecialchars(
                                $dt_vinculo,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'Não informado'
                        ?>

                    </div>

                </div>


                <!-- DATA ARQUIVAMENTO -->

                <div class="campo">

                    <label>
                        Status do vínculo
                    </label>

                    <div class="valor">

                        <?php if (!empty($funcionario['dt_arquivo'])): ?>

                            <span class="status status-arquivado">
                                Arquivado
                            </span>

                        <?php else: ?>

                            <span class="status status-ativo">
                                Ativo
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- ARQUIVAMENTO -->

                <div class="campo">

                    <label>
                        Data de arquivamento
                    </label>

                    <div class="valor">

                        <?= !empty($dt_arquivo)
                            ? htmlspecialchars(
                                $dt_arquivo,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : 'Não arquivado'
                        ?>

                    </div>

                </div>


            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | RODAPÉ
        |--------------------------------------------------------------------------
        -->

        <div class="funcionario-card-footer">

            <div class="footer-info">

                <span class="footer-ponto"></span>

                <span>
                    Visualização somente para consulta
                </span>

            </div>

            <button
                type="button"
                class="btn-footer-fechar"
                onclick="fecharVisualizador()"
            >
                Fechar
            </button>

        </div>

    </section>

</main>


<script>

function fecharVisualizador() {

    /*
     * Quando o visualizador estiver dentro do iframe/modal,
     * pede para a página principal fechar o modal.
     */

    if (
        window.parent &&
        window.parent !== window &&
        typeof window.parent.fecharModalFuncionario === 'function'
    ) {

        window.parent.fecharModalFuncionario();

        return;
    }


    /*
     * Caso a página seja aberta diretamente,
     * volta para a página anterior.
     */

    if (window.history.length > 1) {

        window.history.back();

    }

}


/*
|--------------------------------------------------------------------------
| ESC PARA FECHAR
|--------------------------------------------------------------------------
*/

document.addEventListener('keydown', function (evento) {

    if (evento.key === 'Escape') {

        fecharVisualizador();

    }

});

</script>

</body>

</html>