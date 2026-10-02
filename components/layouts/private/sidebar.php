<?php
/*
|--------------------------------------------------------------------------
| Conexão com o Banco de Dados
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../../../backend/Config/conexao.php';

$baseUrl = '/SistemaMuseuArt.GuardaBem/sistem';

/*
|--------------------------------------------------------------------------
| Sessão
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Verifica se o usuário está logado
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: /SistemaMuseuArt.GuardaBem/public/index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Dados do funcionário
|--------------------------------------------------------------------------
*/
$id = (int) $_SESSION['id_funcionario'];

$sql = "SELECT * FROM funcionario WHERE id_funcionario = ?";
$stmt = mysqli_prepare($strcon, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $funcionario = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);
} else {
    $funcionario = null;
}

if (!$funcionario) {
    session_destroy();
    header("Location: /SistemaMuseuArt.GuardaBem/public/index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Tipo de usuário
|--------------------------------------------------------------------------
*/
$tipoPasta = strtolower($funcionario['tipo'] ?? 'administrador');
$userBaseUrl = $baseUrl . '/' . $tipoPasta;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
switch ($tipoPasta) {
    case 'administrador':
    case 'manipulador':
    case 'registrador':
        $dashboard = $userBaseUrl . '/dashboard.php';
        break;
    default:
        $dashboard = '/SistemaMuseuArt.GuardaBem/public/index.php';
        break;
}

/*
|--------------------------------------------------------------------------
| Página ativa
|--------------------------------------------------------------------------
*/
function menuAtivo($pagina)
{
    $atual = basename($_SERVER['PHP_SELF']);
    return $atual === $pagina ? 'ativo' : '';
}

/*
|--------------------------------------------------------------------------
| Dados exibidos
|--------------------------------------------------------------------------
*/
$nomeFuncionario = htmlspecialchars($funcionario['nome'] ?? 'Usuário', ENT_QUOTES, 'UTF-8');
$emailFuncionario = htmlspecialchars($funcionario['email'] ?? '', ENT_QUOTES, 'UTF-8');
$telefoneFuncionario = htmlspecialchars($funcionario['telefone'] ?? '', ENT_QUOTES, 'UTF-8');
$tipoFuncionario = htmlspecialchars(ucfirst($tipoPasta), ENT_QUOTES, 'UTF-8');

?>

<!-- =========================================================
     SIDEBAR
========================================================= -->
<aside class="menu-lateral" id="sidebar">

    <div class="logo">
        <img src="/SistemaMuseuArt.GuardaBem/assets/img/logomenu.svg" alt="Logo">
        <button type="button" class="btn-menu" id="toggleMenu" aria-label="Abrir ou fechar menu">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <nav>
        <ul class="menu-superiorbase">
            <li>
                <a href="<?= htmlspecialchars($dashboard) ?>" class="<?= menuAtivo('dashboard.php') ?>">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Início</span>
                </a>
            </li>
        </ul>

        <ul class="menu-especifico">
            <?php
            $caminhoMenuEspecifico = __DIR__ . '/menus/' . $tipoPasta . '.php';

            if (file_exists($caminhoMenuEspecifico)) {
                require $caminhoMenuEspecifico;
            }
            ?>
        </ul>

        <ul class="menu-crud">
            <?php
            if (isset($menuCrud) && file_exists($menuCrud)) {
                require $menuCrud;
            }
            ?>
        </ul>

        <ul class="menu-inferiorbase">
            <li>
                <a href="#" id="btnLogout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sair</span>
                </a>
            </li>
            <li>
                <a href="#" id="btnConfiguracoes">
                    <i class="bi bi-gear-wide-connected"></i>
                    <span>Configurações</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- =========================================================
     MODAL DE SAÍDA
========================================================= -->
<div class="modal-saida" id="modalSaida" aria-hidden="true">
    <div class="modal-conteudo">
        <i class="bi bi-box-arrow-right icone-saida"></i>
        <h3>Confirmar saída</h3>
        <p>Deseja realmente sair do sistema?</p>
        <div class="modal-botoes">
            <button type="button" class="btn-cancelar" id="cancelarSaida">Cancelar</button>
            <a href="/SistemaMuseuArt.GuardaBem/public/logout.php" class="btn-sair">Sair</a>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL DE CONFIGURAÇÕES
========================================================= -->
<div class="modal-configuracoes" id="modalConfiguracoes" aria-hidden="true">
    <div class="configuracoes-conteudo">

        <div class="configuracoes-header">
            <div>
                <h2>Configurações</h2>
                <p>Personalize o sistema do seu jeito.</p>
            </div>
            <button type="button" class="btn-fechar-config" id="fecharConfiguracoes" aria-label="Fechar configurações">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="configuracoes-body">

            <!-- MENU DE CONFIGURAÇÕES -->
            <aside class="config-menu">
                <button type="button" class="config-item ativo" data-config="conta">
                    <i class="bi bi-person"></i>
                    <div>
                        <strong>Conta</strong>
                        <span>Dados pessoais</span>
                    </div>
                </button>

                <button type="button" class="config-item" data-config="seguranca">
                    <i class="bi bi-shield-lock"></i>
                    <div>
                        <strong>Segurança</strong>
                        <span>Senha e acesso</span>
                    </div>
                </button>

                <button type="button" class="config-item" data-config="aparencia">
                    <i class="bi bi-palette"></i>
                    <div>
                        <strong>Aparência</strong>
                        <span>Personalize o visual</span>
                    </div>
                </button>
            </aside>

            <!-- PAINEL -->
            <section class="config-painel">

                <!-- CONTA -->
                <form action="/SistemaMuseuArt.GuardaBem/components/atualizar_conta.php" method="POST"
                    enctype="multipart/form-data" class="config-pagina ativa" id="config-conta">
                    <input type="hidden" name="acao" value="atualizar_conta">

                    <div class="config-titulo">
                        <h3>Conta</h3>
                        <p>Permite alterar seus dados pessoais utilizados para identificação no sistema.</p>
                    </div>

                    <div class="config-card">
                        <!-- NOME -->
                        <div class="config-opcao">
                            <div>
                                <strong>Nome completo</strong>
                                <span>Altere o nome utilizado para identificação no sistema.</span>
                            </div>
                            <input type="text" name="nome" class="config-input" value="<?= $nomeFuncionario ?>" required>
                        </div>

                        <!-- EMAIL -->
                        <div class="config-opcao">
                            <div>
                                <strong>E-mail de acesso</strong>
                                <span>E-mail utilizado para acessar sua conta.</span>
                            </div>
                            <input type="email" name="email" class="config-input" value="<?= $emailFuncionario ?>" required>
                        </div>

                        <!-- TELEFONE -->
                        <div class="config-opcao">
                            <div>
                                <strong>Telefone de contato</strong>
                                <span>Número utilizado para contato com o usuário.</span>
                            </div>
                            <input type="tel" id="telefone" name="telefone" class="config-input"
                                value="<?= $telefoneFuncionario ?>" maxlength="15" required>
                        </div>

                        <!-- FOTO -->
                        <div class="config-opcao config-foto">
                            <div>
                                <strong>Foto do usuário</strong>
                                <span>Adicione uma foto para facilitar sua identificação dentro do sistema.</span>
                            </div>
                            <div class="foto-usuario">
                                <div class="foto-preview">
                                    <i class="bi bi-person"></i>
                                </div>
                                <label class="btn-upload">
                                    Alterar foto
                                    <input type="file" name="foto" accept="image/*">
                                </label>
                            </div>
                        </div>

                        <!-- TIPO -->
                        <div class="config-opcao">
                            <div>
                                <strong>Tipo de usuário</strong>
                                <span>Perfil de acesso atualmente utilizado.</span>
                            </div>
                            <span class="valor-config"><?= $tipoFuncionario ?></span>
                        </div>
                    </div>

                    <button type="submit" class="btn-salvar-config">
                        <i class="bi bi-check2"></i> Salvar alterações
                    </button>
                </form>

                <!-- SEGURANÇA -->
                <form action="/SistemaMuseuArt.GuardaBem/components/atualizar_conta.php" method="POST"
                    class="config-pagina" id="config-seguranca">
                    <input type="hidden" name="acao" value="atualizar_senha">

                    <div class="config-titulo">
                        <h3>Segurança</h3>
                        <p>Opções para alterar a senha atual e proteger o acesso à sua conta.</p>
                    </div>

                    <div class="config-card">
                        <div class="config-opcao config-opcao-coluna">
                            <div>
                                <strong>Alterar senha</strong>
                                <span>Atualize sua senha atual para manter sua conta protegida.</span>
                            </div>

                            <div class="config-form">
                                <!-- SENHA ATUAL -->
                                <div class="campo-config">
                                    <label for="senha_atual">Senha atual *</label>
                                    <div class="campo-senha">
                                        <input type="password" name="senha_atual" id="senha_atual" class="config-input"
                                            placeholder="Digite sua senha atual" required>
                                        <button type="button" class="btn-mostrar-senha"
                                            onclick="mostrarSenhaModal('senha_atual', this)" aria-label="Mostrar senha">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- NOVA SENHA -->
                                <div class="campo-config">
                                    <label for="nova_senha">Nova senha *</label>
                                    <div class="campo-senha">
                                        <input type="password" name="nova_senha" id="nova_senha" class="config-input"
                                            placeholder="Digite a nova senha" required>
                                        <button type="button" class="btn-mostrar-senha"
                                            onclick="mostrarSenhaModal('nova_senha', this)" aria-label="Mostrar senha">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- REQUISITOS -->
                                <div class="requisitos">
                                    <div class="requisitos-titulo">
                                        Sua nova senha precisa ter:
                                    </div>
                                    <div class="requisito" id="reqTamanho">
                                        <span>Mínimo de 8 caracteres</span>
                                    </div>
                                    <div class="requisito" id="reqMaiuscula">
                                        <span>Uma letra maiúscula</span>
                                    </div>
                                    <div class="requisito" id="reqMinuscula">
                                        <span>Uma letra minúscula</span>
                                    </div>
                                    <div class="requisito" id="reqNumero">
                                        <span>Um número</span>
                                    </div>
                                    <div class="requisito" id="reqEspecial">
                                        <span>Um caractere especial</span>
                                    </div>
                                </div>

                                <!-- CONFIRMAR SENHA -->
                                <div class="campo-config">
                                    <label for="confirmar_senha">Confirmar nova senha *</label>
                                    <div class="campo-senha">
                                        <input type="password" name="confirmar_senha" id="confirmar_senha"
                                            class="config-input" placeholder="Repita a nova senha" required>
                                        <button type="button" class="btn-mostrar-senha"
                                            onclick="mostrarSenhaModal('confirmar_senha', this)"
                                            aria-label="Mostrar senha">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="mensagem-senha" id="mensagemConfirmacao"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-salvar-config" id="btnSubmit" disabled>
                        <i class="bi bi-shield-check"></i> Alterar senha
                    </button>
                </form>

                <!-- APARÊNCIA -->
                <div class="config-pagina" id="config-aparencia">
                    <div class="config-titulo">
                        <h3>Aparência</h3>
                        <p>Personalize a aparência e a experiência visual do painel.</p>
                    </div>

                    <div class="config-card">
                        <div class="config-opcao">
                            <div>
                                <strong>Preferências do painel</strong>
                                <span>Alterne o tema do painel entre modo claro e escuro.</span>
                            </div>
                            <select class="config-select">
                                <option value="claro">Modo claro</option>
                                <option value="escuro" selected>Modo escuro</option>
                            </select>
                        </div>

                        <div class="config-opcao">
                            <div>
                                <strong>Tamanho das fontes</strong>
                                <span>Ajuste o tamanho dos textos exibidos no painel.</span>
                            </div>
                            <select class="config-select">
                                <option value="pequena">Pequena</option>
                                <option value="media" selected>Média</option>
                                <option value="grande">Grande</option>
                            </select>
                        </div>
                    </div>
                </div>

            </section>
        </div>
    </div>
</div>