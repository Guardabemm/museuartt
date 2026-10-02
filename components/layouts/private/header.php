<?php




if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




require_once __DIR__ . "/../../../backend/Config/conexao.php";








if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../../public/index.php");
    exit();
}




$id = $_SESSION['id_funcionario'];




$sql = "
    SELECT *
    FROM funcionario
    WHERE id_funcionario = ?
";




$stmt = mysqli_prepare($strcon, $sql);




if (!$stmt) {
    die("Erro ao preparar consulta do funcionário.");
}




mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);




$resultado = mysqli_stmt_get_result($stmt);
$funcionario = mysqli_fetch_assoc($resultado);




if (!$funcionario) {




    session_destroy();




    header("Location: ../../../public/index.php");
    exit();
}




$sqlItens = "
SELECT
        cod_item,
        nome_item,
        nome_autor,
        categoria,
        status_obra,
        foto_item
    FROM item
    ORDER BY nome_item ASC
";




$resultadoItens = mysqli_query($strcon, $sqlItens);




$itens = [];




if ($resultadoItens) {




    while ($item = mysqli_fetch_assoc($resultadoItens)) {
        $itens[] = $item;
    }




}




?>




<header class="topo">




    <div class="barra-pesquisa">
        <i class="bi bi-search icone-pesquisa"></i>
        <input type="text" id="pesquisa" placeholder="Pesquisar obras, autores, categorias..." autocomplete="off">
        <button class="btn-limpar" id="btnLimpar" style="display:none;">
            <i class="bi bi-x-circle"></i>
        </button>




        <div id="sugestoes" class="sugestoes"></div>
    </div>




<div class="perfil">
   
    <button type="button" class="perfil-btn" id="perfilBtn">




<?php
   
        $foto = !empty($funcionario['foto_usuario']) ? $funcionario['foto_usuario'] : '/assets/img/usuarios/avatarpadrao.jpg';
 
     
        if (strpos($foto, '/SistemaMuseuArt.GuardaBem/') !== 0) {
            $foto = '/SistemaMuseuArt.GuardaBem/' . ltrim($foto, '/');
        }
        ?>
        <img
            src="<?= htmlspecialchars($foto) ?>"
            alt="<?= htmlspecialchars($funcionario['nome']) ?>"
        >




        <span class="perfil-nome">
            <?= htmlspecialchars($funcionario['nome']) ?>
        </span>




        <i class="bi bi-caret-down-fill"></i>




    </button>








    <div class="perfil-menu" id="perfilMenu">




        <div class="perfil-topo">




            <div class="perfil-user">




                <?php
               
                $foto = !empty($funcionario['foto_usuario']) ? $funcionario['foto_usuario'] : '/assets/img/usuarios/avatarpadrao.jpg';
 
               
                if (strpos($foto, '/SistemaMuseuArt.GuardaBem/') !== 0) {
 $foto = '/SistemaMuseuArt.GuardaBem/' . ltrim($foto, '/');
                }
                ?>
                <img
                    src="<?= htmlspecialchars($foto) ?>"
                    alt="<?= htmlspecialchars($funcionario['nome']) ?>"
                >








                <span>
                    <?= htmlspecialchars($funcionario['nome']) ?>
                </span>




            </div>




        </div>








        <a href="/SistemaMuseuArt.GuardaBem/components/pages/perfil.php">
            <i class="bi bi-person"></i>
            <span>Meu Perfil</span>
        </a>








        <a href="/SistemaMuseuArt.GuardaBem/components/pages/notificacoes.php">
            <i class="bi bi-bell"></i>
            <span>Notificações</span>
        </a>




    </div>




</div>




</header>


<script>
    window.itens = <?= json_encode(
        $itens,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ); ?>;
</script>
