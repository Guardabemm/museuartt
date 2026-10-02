<?php

header("Content-Type: application/json; charset=UTF-8");
 
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../helpers/email.php';

if (!$strcon) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro na conexão com o banco de dados."
    ]);
    exit;
}

$id = trim($_POST["ID"] ?? "");
$email = trim($_POST["email"] ?? "");


if ($id === "" || $email === "") {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "ID e e-mail são obrigatórios."
    ]);
    exit;
}

if (strlen($id) !== 6) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "O código de identificação deve ter 6 caracteres."
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Digite um e-mail válido."
    ]);
    exit;
}

$sql = "
    SELECT
        id_funcionario,
        nome,
        email
    FROM Funcionario
    WHERE login = ?
      AND email = ?
";

$stmt = mysqli_prepare($strcon, $sql);

if (!$stmt) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao preparar a consulta no banco: " . mysqli_error($strcon)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ss", $id, $email);
mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (!$resultado) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao consultar o banco de dados."
    ]);
    exit;
}

if (mysqli_num_rows($resultado) === 0) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Funcionário não encontrado. Verifique seu código e e-mail."
    ]);
    exit;
}

$funcionario = mysqli_fetch_assoc($resultado);
$nome = $funcionario["nome"];
$id_funcionario = $funcionario["id_funcionario"];


$token = bin2hex(random_bytes(32));


$token_expira = date("Y-m-d H:i:s", strtotime("+1 hour"));


$sql_update = "
    UPDATE Funcionario
    SET
        token = ?,
        token_expira = ?
    WHERE id_funcionario = ?
";

$stmt_update = mysqli_prepare($strcon, $sql_update);

if (!$stmt_update) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao preparar atualização do token: " . mysqli_error($strcon)
    ]);
    exit;
}


mysqli_stmt_bind_param(
    $stmt_update,
    "ssi",
    $token,
    $token_expira,
    $id_funcionario
);

if (!mysqli_stmt_execute($stmt_update)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao salvar token no banco: " . mysqli_stmt_error($stmt_update)
    ]);
    exit;
}


$link = "http://" . $_SERVER["HTTP_HOST"] . "/SistemaMuseuArt.GuardaBem/backend/Controllers/novasenha.php?token=" . urlencode($token);

$texto = "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
</head>
<body style='background:#f4f4f4; padding:20px;'>
    <div style='max-width:600px; margin:auto; font-family:Arial,sans-serif; padding:30px; background:#ffffff; border-radius:8px;'>
        <h2 style='color:#1565C0;'>Recuperação de Senha</h2>
        <p>Olá, <strong>{$nome}</strong>!</p>
        <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Museu Art.GuardaBem</strong>.</p>
        <p>Clique no botão abaixo para criar uma nova senha:</p>
        <p style='text-align:center; margin: 30px 0;'>
            <a href='{$link}' style='background:#1565C0; color:#ffffff; padding:14px 30px; text-decoration:none; border-radius:6px; font-weight:bold; display:inline-block;'>Redefinir Senha</a>
        </p>
        <p style='font-size:13px; color:#666;'>Este link é válido por 1 hora.</p>
        <p style='font-size:13px; color:#666;'>Se você não solicitou esta alteração, ignore este e-mail.</p>
        <hr style='border:0; border-top:1px solid #eee; margin-top:20px;'>
        <p style='font-size:12px; color:#999; text-align:center;'>Museu Art.GuardaBem</p>
    </div>
</body>
</html>
";


if (enviaremail($nome, $email, "Recuperação de senha - Museu Art.GuardaBem", $texto)) {
    echo json_encode([
        "status" => "ok",
        "mensagem" => "E-mail enviado com sucesso para {$email}."
    ]);
} else {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Não foi possível enviar o e-mail. Verifique as configurações de SMTP."
    ]);
}

exit;