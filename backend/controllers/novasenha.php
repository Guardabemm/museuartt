<?php
require_once __DIR__ . '/../config/conexao.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$mensagem = '';
$tipoMensagem = '';
$tokenValido = false;
$id_funcionario = null;

if (!empty($token)) {
    $sql = "SELECT id_funcionario FROM Funcionario WHERE token = ? AND token_expira > NOW()";
    $stmt = mysqli_prepare($strcon, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($res)) {
            $tokenValido = true;
            $id_funcionario = $row['id_funcionario'];
        } else {
            $mensagem = "O link de recuperação é inválido ou já expirou.";
            $tipoMensagem = "erro";
        }
    } else {
        $mensagem = "Erro interno no servidor ao verificar o token.";
        $tipoMensagem = "erro";
    }
} else {
    $mensagem = "Nenhum código de validação foi fornecido.";
    $tipoMensagem = "erro";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (empty($novaSenha) || empty($confirmarSenha)) {
        $mensagem = "Por favor, preencha todos os campos de senha.";
        $tipoMensagem = "erro";
    } elseif ($novaSenha !== $confirmarSenha) {
        $mensagem = "As senhas digitadas não coincidem.";
        $tipoMensagem = "erro";
    } elseif (strlen($novaSenha) < 6) {
        $mensagem = "A nova senha deve ter no mínimo 6 caracteres.";
        $tipoMensagem = "erro";
    } else {
        
        /* ============================================================
           GERAR O HASH DA NOVA SENHA
           ============================================================ */
        $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

        $sqlUpdate = "UPDATE Funcionario SET senha = ?, token = NULL, token_expira = NULL WHERE id_funcionario = ?";
        $stmtUpdate = mysqli_prepare($strcon, $sqlUpdate);

        if ($stmtUpdate) {
            // Passa a variável $novaSenhaHash no lugar de $novaSenha
            mysqli_stmt_bind_param($stmtUpdate, "si", $novaSenhaHash, $id_funcionario);
            
            if (mysqli_stmt_execute($stmtUpdate)) {
                $mensagem = "Sua senha foi redefinida com sucesso!";
                $tipoMensagem = "sucesso";
                $tokenValido = false;
            } else {
                $mensagem = "Erro ao atualizar a senha. Tente novamente.";
                $tipoMensagem = "erro";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Museu ART</title>
    <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: #0b0b0e;
            color: #f3f3f3;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card-container {
            background-color: #16161a;
            border: 1px solid #26262e;
            border-radius: 12px;
            width: 100%;
            max-width: 440px;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .logo-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-title {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #ffffff;
            display: inline-block;
            position: relative;
        }

        .logo-title::after {
            content: '';
            display: block;
            height: 3px;
            width: 160px;
            
            background: linear-gradient(90deg, #ff416c, #e8ec23 , #2be231, #00d2ff);
            border-radius: 2px;
            margin-top: 4px;
        }

        .subtitle {
            color: #8e8e93;
            font-size: 14px;
            margin-top: 6px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #d1d1d6;
            margin-bottom: 8px;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            background-color: #1f1f26;
            border: 1px solid #32323d;
            border-radius: 8px;
            color: #ffffff;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="password"]:focus {
            border-color: #5d5d6d;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.05);
        }

        .requisitos {
            background-color: #1b1b21;
            border: 1px solid #292932;
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: -8px;
            margin-bottom: 20px;
        }

        .requisitos-titulo {
            font-size: 12px;
            color: #a1a1aa;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .requisito {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #777780;
            margin: 5px 0;
            transition: color 0.2s ease;
        }

        .requisito .icone {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1px solid #45454f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .requisito.valido {
            color: #30d158;
        }

        .requisito.valido .icone {
            background-color: #30d158;
            border-color: #30d158;
            color: #0b0b0e;
            font-weight: bold;
        }

        .mensagem-senha {
            display: none;
            font-size: 12px;
            margin-top: 7px;
        }

        .mensagem-senha.erro {
            display: block;
            color: #ff453a;
        }

        .mensagem-senha.sucesso {
            display: block;
            color: #30d158;
        }



        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #2b2b32;
            color: #ffffff;
            border: 1px solid #3a3a44;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease, opacity 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover:not(:disabled) {
            background-color: #383842;
        }

        .btn-submit:active:not(:disabled) {
            transform: scale(0.98);
        }

        .btn-submit:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .alerta {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .alerta.erro {
            background-color: rgba(255, 69, 58, 0.12);
            border: 1px solid rgba(255, 69, 58, 0.3);
            color: #ff453a;
        }

        .alerta.sucesso {
            background-color: rgba(48, 209, 88, 0.12);
            border: 1px solid rgba(48, 209, 88, 0.3);
            color: #30d158;
        }

        .btn-voltar {
            display: inline-block;
            text-align: center;
            width: 100%;
            color: #8e8e93;
            text-decoration: none;
            font-size: 13px;
            margin-top: 16px;
            transition: color 0.2s;
        }

        .btn-voltar:hover {
            color: #ffffff;
        }

        .campo-senha {
    position: relative;
    width: 100%;
}

.campo-senha {
    position: relative;
    width: 100%;
}

.campo-senha input[type="password"],
.campo-senha input[type="text"] {
    width: 100%;
    padding: 12px 45px 12px 14px;
    background-color: #1f1f26;
    border: 1px solid #32323d;
    border-radius: 8px;
    color: #ffffff;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.campo-senha input[type="password"]:focus,
.campo-senha input[type="text"]:focus {
    border-color: #5d5d6d;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.05);
}

.btn-mostrar-senha {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);

    width: 30px;
    height: 30px;

    padding: 0;
    margin: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background: transparent;
    border: none;

    color: #777780;
    font-size: 16px;

    cursor: pointer;

    z-index: 2;

    transition: color 0.2s ease;
}

.btn-mostrar-senha:hover {
    color: #ffffff;
}

.btn-mostrar-senha:focus {
    outline: none;
}

    </style>
</head>

<body>

<div class="card-container">

    <div class="logo-header">
        <div class="logo-title">Museu art</div>
        <p class="subtitle">Criar nova senha de acesso</p>
    </div>

    <?php if (!empty($mensagem)): ?>

        <div class="alerta <?= $tipoMensagem ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>


 
    <?php if ($tokenValido): ?>

        <form method="POST" action="" id="formSenha">

            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($token) ?>"
            >


          <div class="form-group">

    <label for="nova_senha">
        Nova Senha *
    </label>

    <div class="campo-senha">

        <input
            type="password"
            id="nova_senha"
            name="nova_senha"
            placeholder="Digite sua nova senha"
            required
            autofocus
        >

        <button
            type="button"
            class="btn-mostrar-senha"
            onclick="mostrarSenha('nova_senha', this)"
            aria-label="Mostrar senha"
        >
            <i class="bi bi-eye"></i>
        </button>

    </div>

</div>

            <div class="requisitos">

                <div class="requisitos-titulo">
                    Sua senha precisa ter:
                </div>

                <div class="requisito" id="reqTamanho">
                    <span class="icone">✓</span>
                    <span>Mínimo de 8 caracteres</span>
                </div>

                <div class="requisito" id="reqMaiuscula">
                    <span class="icone">✓</span>
                    <span>Uma letra maiúscula</span>
                </div>

                <div class="requisito" id="reqMinuscula">
                    <span class="icone">✓</span>
                    <span>Uma letra minúscula</span>
                </div>

                <div class="requisito" id="reqNumero">
                    <span class="icone">✓</span>
                    <span>Um número</span>
                </div>

                <div class="requisito" id="reqEspecial">
                    <span class="icone">✓</span>
                    <span>Um caractere especial</span>
                </div>

            </div>


            <div class="form-group">

                <label for="confirmar_senha">
                    Confirmar Nova Senha *
                </label>

                <input
                    type="password"
                    id="confirmar_senha"
                    name="confirmar_senha"
                    placeholder="Repita a nova senha"
                    required
                >

                <div
                    class="mensagem-senha"
                    id="mensagemConfirmacao"
                ></div>

            </div>


            <button
                type="submit"
                class="btn-submit"
                id="btnSubmit"
                disabled
            >
                Redefinir Senha
            </button>

        </form>

    <?php else: ?>

        <a
            href="../../public/index.php"
            class="btn-voltar"
        >
            ← Voltar para a tela de login
        </a>

    <?php endif; ?>

</div>


<script>

    const novaSenha = document.getElementById('nova_senha');
    const confirmarSenha = document.getElementById('confirmar_senha');
    const btnSubmit = document.getElementById('btnSubmit');
    const mensagemConfirmacao = document.getElementById('mensagemConfirmacao');
    const reqTamanho = document.getElementById('reqTamanho');
    const reqMaiuscula = document.getElementById('reqMaiuscula');
    const reqMinuscula = document.getElementById('reqMinuscula');
    const reqNumero = document.getElementById('reqNumero');
    const reqEspecial = document.getElementById('reqEspecial');


    function atualizarRequisito(elemento, valido) {

        if (valido) {

            elemento.classList.add('valido');

        } else {

            elemento.classList.remove('valido');

        }

    }


    function validarSenha() {

        const senha = novaSenha.value;


        const tamanhoValido = senha.length >= 8;
        const maiusculaValida = /[A-Z]/.test(senha);
        const minusculaValida = /[a-z]/.test(senha);
        const numeroValido = /[0-9]/.test(senha);
        const especialValido = /[^A-Za-z0-9]/.test(senha);


        atualizarRequisito(
            reqTamanho,
            tamanhoValido
        );

        atualizarRequisito(
            reqMaiuscula,
            maiusculaValida
        );

        atualizarRequisito(
            reqMinuscula,
            minusculaValida
        );

        atualizarRequisito(
            reqNumero,
            numeroValido
        );

        atualizarRequisito(
            reqEspecial,
            especialValido
        );

        return (
            tamanhoValido &&
            maiusculaValida &&
            minusculaValida &&
            numeroValido &&
            especialValido
        );
    }


    function validarConfirmacao() {

        const senha = novaSenha.value;

        const confirmacao = confirmarSenha.value;


        if (confirmacao === '') {

            mensagemConfirmacao.textContent = '';

            mensagemConfirmacao.className = 'mensagem-senha';

            return false;
        }


        if (senha !== confirmacao) {

            mensagemConfirmacao.textContent =
                'As senhas não coincidem.';

            mensagemConfirmacao.className =
                'mensagem-senha erro';

            return false;
        }


        mensagemConfirmacao.textContent =
            'As senhas coincidem.';

        mensagemConfirmacao.className =
            'mensagem-senha sucesso';

        return true;
    }


    function atualizarBotao() {

        const senhaValida = validarSenha();

        const confirmacaoValida = validarConfirmacao();


        btnSubmit.disabled = !(
            senhaValida &&
            confirmacaoValida
        );
    }


    novaSenha.addEventListener(
        'input',
        atualizarBotao
    );


    confirmarSenha.addEventListener(
        'input',
        atualizarBotao
    );


   

    document
        .getElementById('formSenha')
        .addEventListener('submit', function(event) {

            const senhaValida = validarSenha();

            const confirmacaoValida = validarConfirmacao();


            if (!senhaValida || !confirmacaoValida) {

                event.preventDefault();

                atualizarBotao();

                return;
            }

        });

function mostrarSenha(id, botao) {

    const campo = document.getElementById(id);
    const icone = botao.querySelector('i');

    if (campo.type === 'password') {

        campo.type = 'text';

        icone.classList.remove('bi-eye');
        icone.classList.add('bi-eye-slash');

        botao.setAttribute(
            'aria-label',
            'Ocultar senha'
        );

    } else {

        campo.type = 'password';

        icone.classList.remove('bi-eye-slash');
        icone.classList.add('bi-eye');

        botao.setAttribute(
            'aria-label',
            'Mostrar senha'
        );
    }
}



</script>

</body>

</html>