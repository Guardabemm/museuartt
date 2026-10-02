<?php
require('../../backend/Config/conexao.php');
session_start();
/** @var mysqli $strcon */

header('Content-Type: application/json');

$ID = trim($_POST["ID"] ?? "");
$senha = trim($_POST["senha"] ?? "");

if (empty($ID) || empty($senha)) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Preencha todos os campos."
    ]);
    exit();
}

$sql = "
SELECT *
FROM Funcionario
WHERE login = ?
";

$stmt = mysqli_prepare($strcon, $sql);

if (!$stmt) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro interno do sistema."
    ]);
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $ID
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) == 0) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Login não encontrado."
    ]);
    exit();
}

$funcionario = mysqli_fetch_assoc($resultado);

/* ============================================================
   CORREÇÃO AQUI: Usa password_verify para comparar o Hash
   ============================================================ */
if (!password_verify($senha, $funcionario["senha"])) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Senha incorreta."
    ]);
    exit();
}

$_SESSION["id_funcionario"] = $funcionario["id_funcionario"];
$_SESSION["nome"] = $funcionario["nome"];
$_SESSION["tipo"] = $funcionario["tipo"];

$redirect = "";

switch ($funcionario["tipo"]) {

    case "Administrador":
        $redirect = "../sistem/administrador/dashboard.php";
        break;

    case "Registrador":
        $redirect = "../sistem/registrador/dashboard.php";
        break;

    case "Manipulador":
        $redirect = "../sistem/manipulador/dashboard.php";
        break;

    default:
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Tipo de usuário inválido."
        ]);
        exit();
}

echo json_encode([
    "status" => "sucesso",
    "mensagem" => "Login realizado com sucesso.",
    "redirect" => $redirect
]);

exit();