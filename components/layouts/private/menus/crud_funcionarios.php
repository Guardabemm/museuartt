<li class="menu-crud-titulo">

    <span>
        Funcionários
    </span>

</li>


<li>

    <a href="<?= $baseUrl ?>/administrador/funcionario/cadastrar_funcionario.php"
        class="menu-crud-botao menu-crud-cadastrar">

        <i class="bi bi-person-plus"></i>

        <span>
            Cadastrar
        </span>

    </a>

</li>



<li>

    <a href="<?= $baseUrl ?>/administrador/funcionario/funcionarioslist.php?selecionar=editar"
        class="menu-crud-botao menu-crud-editar">

        <i class="bi bi-pencil"></i>

        <span>
            Editar
        </span>

    </a>

</li>


<li>

    <a href="<?= $baseUrl ?>/administrador/funcionario/funcionarioslist.php?selecionar=excluir"
        class="menu-crud-botao menu-crud-atualizar">

        <i class="bi bi-trash"></i>

        <span>
            Excluir
        </span>

    </a>

</li>