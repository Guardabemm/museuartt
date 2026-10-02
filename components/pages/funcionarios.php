<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




require_once
    __DIR__ . "/../../backend/Config/conexao.php";




/** @var mysqli $strcon */






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


$idLogado = $_SESSION['id_funcionario'] ?? 0;


$sql = "
    SELECT
        id_funcionario,
        nome,
        email,
        login,
        tipo,
        foto_usuario,
        dt_vinculo
    FROM funcionario
    WHERE dt_arquivo IS NULL
      AND id_funcionario <> ?
    ORDER BY nome ASC
";


$stmt = mysqli_prepare($strcon, $sql);


if (!$stmt) {
    die("Erro ao preparar a consulta de funcionários: " . mysqli_error($strcon));
}


mysqli_stmt_bind_param($stmt, "i", $idLogado);
mysqli_stmt_execute($stmt);


$resultado = mysqli_stmt_get_result($stmt);


$funcionarios = [];


while ($funcionario = mysqli_fetch_assoc($resultado)) {
    $funcionarios[] = $funcionario;
}


?>




<div class="
        pagina-funcionarios
        <?= $modoSelecao
            ? 'modo-selecao-funcionario modo-selecao-' . htmlspecialchars($modoSelecao, ENT_QUOTES, 'UTF-8')
            : ''
            ?>
    " data-modo-selecao="<?= htmlspecialchars(
        $modoSelecao,
        ENT_QUOTES,
        'UTF-8'
    ) ?>">


    <div class="pagina-cabecalho">




        <div class="pagina-titulo">


            <h1>
                Funcionários
            </h1>


            <p>
                <?php if ($modoSelecao === 'editar'): ?>


                    Selecione o funcionário que deseja editar.


                <?php elseif ($modoSelecao === 'excluir'): ?>


                    Selecione o funcionário que deseja arquivar.


                <?php elseif ($modoSelecao === 'atualizar'): ?>


                    Selecione o funcionário que deseja atualizar.


                <?php else: ?>


                    Gerencie os funcionários cadastrados no sistema.


                <?php endif; ?>


            </p>


        </div>




        <div class="pagina-acoes">


            <button type="button" class="btn-novo-funcionario"
                onclick="window.location.href='cadastrar_funcionario.php'">


                <i class="bi bi-plus-lg"></i>


                Novo funcionário


            </button>


        </div>


    </div>




    <?php if ($modoSelecao): ?>




        <div class="aviso-selecao-funcionario">


            <div class="aviso-selecao-icone">


                <?php if ($modoSelecao === 'editar'): ?>


                    <i class="bi bi-pencil"></i>


                <?php elseif ($modoSelecao === 'excluir'): ?>


                    <i class="bi bi-trash"></i>


                <?php else: ?>


                    <i class="bi bi-arrow-repeat"></i>


                <?php endif; ?>


            </div>




            <div class="aviso-selecao-texto">


                <strong>


                    <?php if ($modoSelecao === 'editar'): ?>


                        Modo de edição


                    <?php elseif ($modoSelecao === 'excluir'): ?>


                        Modo de arquivamento


                    <?php else: ?>


                        Modo de atualização


                    <?php endif; ?>


                </strong>




                <span>
                    Clique em um funcionário para continuar.
                </span>


            </div>




            <a href="funcionarioslist.php" class="aviso-selecao-cancelar">


                Cancelar


            </a>


        </div>


    <?php endif; ?>






    <div class="funcionarios-controles">




        <div class="funcionarios-pesquisa">


            <i class="bi bi-search"></i>


            <input type="text" id="pesquisaFuncionarios" placeholder="Pesquisar funcionários..." autocomplete="off">


        </div>




        <select id="filtroTipo">


            <option value="">
                Todos os tipos
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




        <select id="ordenacaoFuncionarios">


            <option value="nome-az">
                Nome A-Z
            </option>


            <option value="nome-za">
                Nome Z-A
            </option>


            <option value="recente">
                Mais recentes
            </option>


            <option value="antigo">
                Mais antigos
            </option>


        </select>




        <div class="tipo-visualizacao">




            <button type="button" id="btnCards" class="btn-visualizacao ativo" title="Visualização em cards">


                <i class="bi bi-grid-3x3-gap"></i>


            </button>




            <button type="button" id="btnTabela" class="btn-visualizacao" title="Visualização em tabela">


                <i class="bi bi-list"></i>


            </button>




        </div>


    </div>






    <div class="funcionarios-grid" id="visualizacaoCards">




        <?php if (count($funcionarios) > 0): ?>




            <?php foreach ($funcionarios as $funcionario): ?>




                <?php


                $id =
                    $funcionario['id_funcionario'];




                $nome =
                    $funcionario['nome']
                    ?? 'Sem nome';




                $email =
                    $funcionario['email']
                    ?? '';




                $login =
                    $funcionario['login']
                    ?? '';




                $tipo =
                    $funcionario['tipo']
                    ?? 'Não informado';




                $foto =
                    $funcionario['foto_usuario']
                    ?? '';




                $dtVinculo =
                    $funcionario['dt_vinculo']
                    ?? '';




                $tipoClasse =
                    match (
                    mb_strtolower(
                        trim($tipo)
                    )
                    ) {


                        'administrador'
                        => 'tipo-administrador',


                        'registrador'
                        => 'tipo-registrador',


                        'manipulador'
                        => 'tipo-manipulador',


                        default
                        => 'tipo-padrao'


                    };


                ?>




                <article class="funcionario-card" data-id="<?= htmlspecialchars($id) ?>"
                    data-nome="<?= htmlspecialchars(mb_strtolower($nome)) ?>"
                    data-email="<?= htmlspecialchars(mb_strtolower($email)) ?>"
                    data-tipo="<?= htmlspecialchars(mb_strtolower($tipo)) ?>"
                    data-login="<?= htmlspecialchars(mb_strtolower($login)) ?>"
                    data-vinculo="<?= htmlspecialchars($dtVinculo) ?>">




                    <div class="funcionario-foto">




                        <?php if (!empty($foto)): ?>




                            <img src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(
                                ltrim($foto, '/')
                            ) ?>" alt="<?= htmlspecialchars($nome) ?>">




                        <?php else: ?>




                            <div class="funcionario-sem-foto">


                                <i class="bi bi-person"></i>


                            </div>




                        <?php endif; ?>




                    </div>




                    <div class="funcionario-conteudo">




                        <div class="funcionario-topo">




                            <div class="funcionario-identificacao">


                                <h3>
                                    <?= htmlspecialchars($nome) ?>
                                </h3>


                                <p>
                                    <?= htmlspecialchars($email) ?>
                                </p>


                            </div>




                        </div>




                        <div class="funcionario-detalhes">




                            <span class="tipo-funcionario <?= $tipoClasse ?>">


                                <?= htmlspecialchars($tipo) ?>


                            </span>




                            <span class="funcionario-login">


                                <i class="bi bi-key"></i>


                                <?= htmlspecialchars($login) ?>


                            </span>




                        </div>




                        <div class="funcionario-acoes">




                            <span class="funcionario-vinculo">


                                <i class="bi bi-calendar3"></i>




                                <?php


                                if (!empty($dtVinculo)) {


                                    echo htmlspecialchars(
                                        date(
                                            'd/m/Y',
                                            strtotime($dtVinculo)
                                        )
                                    );


                                } else {


                                    echo 'Sem data';


                                }


                                ?>


                            </span>




                            <div class="acoes-direita">




                                <?php if (!$modoSelecao): ?>




                                    <a href="visualizar_funcionario.php?id=<?= htmlspecialchars($id) ?>" class="btn-visualizar"
                                        title="Visualizar">


                                        <i class="bi bi-eye"></i>


                                    </a>




                                    <div class="dropdown-crud">




                                        <button type="button" class="btn-crud" title="Mais opções">


                                            <i class="bi bi-three-dots-vertical"></i>


                                        </button>




                                        <div class="crud-menu">




                                            <a href="editar_funcionario.php?id=<?= htmlspecialchars($id) ?>">


                                                <i class="bi bi-pencil"></i>


                                                Editar


                                            </a>




                                            <a href="excluir_funcionario.php?id=<?= htmlspecialchars($id) ?>">


                                                <i class="bi bi-trash"></i>


                                                Excluir


                                            </a>




                                        </div>


                                    </div>




                                <?php else: ?>




                                    <span class="selecionar-indicador">


                                        <?php if ($modoSelecao === 'editar'): ?>


                                            <i class="bi bi-pencil"></i>
                                            Editar


                                        <?php elseif ($modoSelecao === 'excluir'): ?>


                                            <i class="bi bi-trash"></i>
                                            Arquivar


                                        <?php else: ?>


                                            <i class="bi bi-arrow-repeat"></i>
                                            Atualizar


                                        <?php endif; ?>


                                    </span>




                                <?php endif; ?>




                            </div>


                        </div>


                    </div>


                </article>




            <?php endforeach; ?>




        <?php else: ?>




            <div class="lista-vazia">


                <i class="bi bi-people"></i>


                <h3>
                    Nenhum funcionário cadastrado
                </h3>


                <p>
                    Cadastre um novo funcionário para começar.
                </p>


            </div>




        <?php endif; ?>




    </div>




    <div class="funcionarios-tabela-container" id="visualizacaoTabela">




        <table class="funcionarios-tabela">




            <thead>


                <tr>


                    <th>
                        Funcionário
                    </th>


                    <th>
                        E-mail
                    </th>


                    <th>
                        Tipo
                    </th>


                    <th>
                        Login
                    </th>


                    <th>
                        Data de vínculo
                    </th>


                    <th>
                        Ações
                    </th>


                </tr>


            </thead>




            <tbody id="tabelaFuncionarios">




                <?php foreach ($funcionarios as $funcionario): ?>




                    <?php


                    $id =
                        $funcionario['id_funcionario'];


                    $nome =
                        $funcionario['nome']
                        ?? 'Sem nome';


                    $email =
                        $funcionario['email']
                        ?? '';


                    $login =
                        $funcionario['login']
                        ?? '';


                    $tipo =
                        $funcionario['tipo']
                        ?? 'Não informado';


                    $foto =
                        $funcionario['foto_usuario']
                        ?? '';


                    $dtVinculo =
                        $funcionario['dt_vinculo']
                        ?? '';




                    $tipoClasse =
                        match (
                        mb_strtolower(
                            trim($tipo)
                        )
                        ) {


                            'administrador'
                            => 'tipo-administrador',


                            'registrador'
                            => 'tipo-registrador',


                            'manipulador'
                            => 'tipo-manipulador',


                            default
                            => 'tipo-padrao'


                        };


                    ?>




                    <tr data-id="<?= htmlspecialchars($id) ?>" data-nome="<?= htmlspecialchars(mb_strtolower($nome)) ?>"
                        data-email="<?= htmlspecialchars(mb_strtolower($email)) ?>"
                        data-tipo="<?= htmlspecialchars(mb_strtolower($tipo)) ?>"
                        data-login="<?= htmlspecialchars(mb_strtolower($login)) ?>"
                        data-vinculo="<?= htmlspecialchars($dtVinculo) ?>">




                        <td>




                            <div class="tabela-funcionario">




                                <?php if (!empty($foto)): ?>




                                    <img src="/SistemaMuseuArt.GuardaBem/<?= htmlspecialchars(
                                        ltrim($foto, '/')
                                    ) ?>" alt="<?= htmlspecialchars($nome) ?>">




                                <?php else: ?>




                                    <div class="funcionario-sem-foto">


                                        <i class="bi bi-person"></i>


                                    </div>




                                <?php endif; ?>




                                <div>


                                    <strong>
                                        <?= htmlspecialchars($nome) ?>
                                    </strong>


                                    <span>
                                        #<?= htmlspecialchars($id) ?>
                                    </span>


                                </div>




                            </div>




                        </td>




                        <td>
                            <?= htmlspecialchars($email) ?>
                        </td>




                        <td>


                            <span class="tipo-funcionario <?= $tipoClasse ?>">


                                <?= htmlspecialchars($tipo) ?>


                            </span>


                        </td>




                        <td>
                            <?= htmlspecialchars($login) ?>
                        </td>




                        <td>




                            <?php


                            if (!empty($dtVinculo)) {


                                echo htmlspecialchars(
                                    date(
                                        'd/m/Y',
                                        strtotime($dtVinculo)
                                    )
                                );


                            } else {


                                echo '—';


                            }


                            ?>




                        </td>




                        <td>




                            <div class="acoes-tabela">




                                <?php if (!$modoSelecao): ?>




                                    <a href="visualizar_funcionario.php?id=<?= htmlspecialchars($id) ?>" title="Visualizar">


                                        <i class="bi bi-eye"></i>


                                    </a>




                                    <a href="editar_funcionario.php?id=<?= htmlspecialchars($id) ?>" title="Editar">


                                        <i class="bi bi-pencil"></i>


                                    </a>




                                    <a href="excluir_funcionario.php?id=<?= htmlspecialchars($id) ?>" title="Excluir"
                                        class="acao-excluir">


                                        <i class="bi bi-trash"></i>


                                    </a>




                                <?php else: ?>




                                    <span class="selecionar-indicador">


                                        <?php if ($modoSelecao === 'editar'): ?>


                                            <i class="bi bi-pencil"></i>


                                            Selecionar


                                        <?php elseif ($modoSelecao === 'excluir'): ?>


                                            <i class="bi bi-trash"></i>


                                            Arquivar


                                        <?php else: ?>


                                            <i class="bi bi-arrow-repeat"></i>


                                            Selecionar


                                        <?php endif; ?>


                                    </span>




                                <?php endif; ?>




                            </div>




                        </td>




                    </tr>




                <?php endforeach; ?>




            </tbody>


        </table>


    </div>




</div>




<script>


    document.addEventListener('DOMContentLoaded', function () {






        const pagina = document.querySelector('.pagina-funcionarios');


        if (!pagina) {
            return;
        }








        const modo = pagina.dataset.modoSelecao;


        if (!modo) {
            return;
        }








        const cards = document.querySelectorAll('.funcionario-card');


        cards.forEach(function (card) {




            card.classList.add('funcionario-selecionavel');




            card.addEventListener('click', function (evento) {






                if (evento.target.closest('a, button')) {
                    return;
                }








                const id = card.dataset.id;




                if (!id) {
                    console.error(
                        'ID do funcionário não encontrado.'
                    );


                    return;
                }








                abrirFuncionario(id, modo);


            });


        });








        const linhas = document.querySelectorAll(
            '#tabelaFuncionarios tr'
        );




        linhas.forEach(function (linha) {




            linha.classList.add('funcionario-selecionavel');




            linha.addEventListener('click', function (evento) {






                if (evento.target.closest('a, button')) {
                    return;
                }








                const id = linha.dataset.id;




                if (!id) {
                    console.error(
                        'ID do funcionário não encontrado.'
                    );


                    return;
                }








                abrirFuncionario(id, modo);


            });


        });








        function abrirFuncionario(id, modo) {






            if (modo === 'editar') {


                window.location.href =
                    'editar_funcionario.php?id=' +
                    encodeURIComponent(id);


                return;
            }








            if (modo === 'excluir') {


                window.location.href =
                    'excluir_funcionario.php?id=' +
                    encodeURIComponent(id);


                return;
            }








            if (modo === 'atualizar') {


                window.location.href =
                    'atualizar_funcionario.php?id=' +
                    encodeURIComponent(id);


                return;
            }

            console.error(
                'Modo de seleção inválido:',
                modo
            );


        }


    });


</script>
