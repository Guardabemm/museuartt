<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
}

function enviaremail($nome, $email, $assunto, $mensagem)
{
    $mail_host = 'smtp.gmail.com';
    $mail_port = 587;

   
    $mail_user = 'museuartt@gmail.com';
    $mail_pass = 'hdovcgueygxruzgk';

    $mail_from = $mail_user;
    $mail_name = 'Museu Art';

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = $mail_host;
        $mail->SMTPAuth = true;

        $mail->Username = $mail_user;
        $mail->Password = $mail_pass;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mail_port;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom($mail_from, $mail_name);
        $mail->addReplyTo($mail_from, $mail_name);

        $mail->addAddress($email, $nome);

        $mail->isHTML(true);

        $mail->Subject = $assunto;
        $mail->Body = $mensagem;

        $mail->AltBody = strip_tags($mensagem);

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log("Erro PHPMailer: " . $mail->ErrorInfo);

        return false;
    }
}
