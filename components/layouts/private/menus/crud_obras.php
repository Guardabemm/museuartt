<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Se a sessão for 'admin', usa 'admin'. Para qualquer outro caso (incluindo registrador), usa 'registrador'
$tipoPerfil = ($_SESSION['perfil'] ?? '') === 'administrador' ? 'admin' : 'registrador';
?>


<li class="menu-crud-titulo">

    <span>Item</span>

</li>



<li>

    <a href="<?= $baseUrl ?>/<?= $tipoPerfil ?>/item/cadastrar_obra.php?origem=<?= $tipoPerfil ?>"

       class="menu-crud-botao menu-crud-cadastrar">

        <i class="bi bi-plus-circle"></i>

        <span>Cadastrar</span>

    </a>

</li>



<li>

    <a href="<?= $baseUrl ?>/<?= $tipoPerfil ?>/item/obras.php?selecionar=editar&origem=<?= $tipoPerfil ?>"

       class="menu-crud-botao menu-crud-editar">

        <i class="bi bi-pencil"></i>

        <span>Editar</span>

    </a>

</li>



<li>

    <a href="<?= $baseUrl ?>/<?= $tipoPerfil ?>/item/obras.php?selecionar=excluir&origem=<?= $tipoPerfil ?>"

       class="menu-crud-botao menu-crud-atualizar">

        <i class="bi bi-trash"></i>

        <span>Excluir</span>

    </a>

</li>

