<?php
// funcoes_email.php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function enviarEmail($destinatario, $nomeDestinatario, $assunto, $mensagem) {
    if (EMAIL_DEBUG > 0) {
        error_log("Tentativa de envio para: $destinatario");
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = EMAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_USERNAME;
        $mail->Password = EMAIL_PASSWORD;
        $mail->SMTPSecure = EMAIL_SECURE; 
        $mail->Port = EMAIL_PORT;
        
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($destinatario, $nomeDestinatario);
        $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
        
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;
        $mail->AltBody = strip_tags($mensagem);
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Erro PHPMailer: " . $e->getMessage());
        return false;
    }
}
?> 