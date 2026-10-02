<?php
session_start();
require_once __DIR__ . '/../backend/Config/conexao.php';

// Pega a página de origem de onde o formulário foi enviado
$paginaOrigem = $_SERVER['HTTP_REFERER'] ?? '../../sistem/administrador/dashboard.php';

// Função para redirecionar de volta para a página anterior com parâmetros na URL
function redirecionar($url, $config, $status)
{
    $urlLimpa = strtok($url, '?');
    header("Location: " . $urlLimpa . "?config=" . $config . "&status=" . $status);
    exit();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['id_funcionario'])) {
    header("Location: ../../public/index.php");
    exit();
}

$idFuncionario = $_SESSION['id_funcionario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    /* ========================================================
       1. ATUALIZAR DADOS PESSOAIS (CONTA)
       ======================================================== */
    if ($acao === 'atualizar_conta') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefoneBruto = $_POST['telefone'] ?? '';

        $telefone = preg_replace('/\D/', '', $telefoneBruto);

        if (empty($nome) || empty($email) || empty($telefone)) {
            redirecionar($paginaOrigem, 'conta', 'erro_campos');
        }

        // Upload da nova foto (opcional)
        $fotoUsuario = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($extensao, $permitidas, true)) {
                $pastaFotos = __DIR__ . "/../assets/img/usuarios/";
                if (!is_dir($pastaFotos)) {
                    mkdir($pastaFotos, 0755, true);
                }

                $nomeArquivo = uniqid('usuario_', true) . '.' . $extensao;
                $caminhoCompleto = $pastaFotos . $nomeArquivo;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoCompleto)) {
                    $fotoUsuario = "assets/img/usuarios/" . $nomeArquivo;
                }
            }
        }

        // Consulta SQL
        if ($fotoUsuario !== null) {
            $sql = "UPDATE funcionario SET nome = ?, email = ?, telefone = ?, foto_usuario = ? WHERE id_funcionario = ?";
            $stmt = mysqli_prepare($strcon, $sql);
            mysqli_stmt_bind_param($stmt, "ssssi", $nome, $email, $telefone, $fotoUsuario, $idFuncionario);
        } else {
            $sql = "UPDATE funcionario SET nome = ?, email = ?, telefone = ? WHERE id_funcionario = ?";
            $stmt = mysqli_prepare($strcon, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $nome, $email, $telefone, $idFuncionario);
        }

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['nome_funcionario'] = $nome;
            mysqli_stmt_close($stmt);
            redirecionar($paginaOrigem, 'conta', 'sucesso');
        } else {
            mysqli_stmt_close($stmt);
            redirecionar($paginaOrigem, 'conta', 'erro_banco');
        }
    }

    /* ========================================================
       2. ATUALIZAR SENHA (SEGURANÇA)
       ======================================================== */
    if ($acao === 'atualizar_senha') {
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
            redirecionar($paginaOrigem, 'seguranca', 'erro_campos');
        }

        if ($novaSenha !== $confirmarSenha) {
            redirecionar($paginaOrigem, 'seguranca', 'erro_coincidencia');
        }

        $tamanhoValido = strlen($novaSenha) >= 8;
        $maiusculaValida = preg_match('/[A-Z]/', $novaSenha);
        $minusculaValida = preg_match('/[a-z]/', $novaSenha);
        $numeroValido = preg_match('/[0-9]/', $novaSenha);
        $especialValido = preg_match('/[^A-Za-z0-9]/', $novaSenha);

        if (!$tamanhoValido || !$maiusculaValida || !$minusculaValida || !$numeroValido || !$especialValido) {
            redirecionar($paginaOrigem, 'seguranca', 'erro_formato');
        }

        $sqlSenha = "SELECT senha FROM funcionario WHERE id_funcionario = ?";
        $stmtSenha = mysqli_prepare($strcon, $sqlSenha);
        mysqli_stmt_bind_param($stmtSenha, "i", $idFuncionario);
        mysqli_stmt_execute($stmtSenha);
        $resSenha = mysqli_stmt_get_result($stmtSenha);
        $usuario = mysqli_fetch_assoc($resSenha);
        mysqli_stmt_close($stmtSenha);

        // 1. Verifica se a senha do banco é um hash criado pelo PHP ($2y$...)
        $ehHash = str_starts_with($usuario['senha'], '$2y$');

        // 2. Se for hash, usa password_verify. Se for texto puro, compara diretamente (==)
        $senhaValida = $ehHash
            ? password_verify($senhaAtual, $usuario['senha'])
            : ($senhaAtual === $usuario['senha']);

        if (!$usuario || !$senhaValida) {
            redirecionar($paginaOrigem, 'seguranca', 'erro_senha_incorreta');
        }

        $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);

        $sqlUpdate = "UPDATE funcionario SET senha = ? WHERE id_funcionario = ?";
        $stmtUpdate = mysqli_prepare($strcon, $sqlUpdate);
        mysqli_stmt_bind_param($stmtUpdate, "si", $novoHash, $idFuncionario);

        if (mysqli_stmt_execute($stmtUpdate)) {
            mysqli_stmt_close($stmtUpdate);
            redirecionar($paginaOrigem, 'seguranca', 'sucesso');
        } else {
            mysqli_stmt_close($stmtUpdate);
            redirecionar($paginaOrigem, 'seguranca', 'erro_banco');
        }
    }
}