<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
$emailConfig = require __DIR__ . '/includes/email-config.php';

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2;
    $mail->isSMTP();
    $mail->Host = $emailConfig['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $emailConfig['smtp_username'];
    $mail->Password = $emailConfig['smtp_password'];
    $mail->SMTPSecure = $emailConfig['smtp_secure'];
    $mail->Port = $emailConfig['smtp_port'];
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
    $mail->addAddress('patetagames2013@gmail.com', 'Teste'); // ALTERE AQUI
    
    $mail->isHTML(true);
    $mail->Subject = 'Teste de E-mail - SplitStore';
    $mail->Body = '<h1 style="color: #dc2626;">✅ FUNCIONOU!</h1><p>O e-mail está sendo enviado corretamente!</p>';
    
    $mail->send();
    echo '<h1 style="color: green;">✅ E-MAIL ENVIADO COM SUCESSO!</h1>';
    
} catch (Exception $e) {
    echo '<h1 style="color: red;">❌ ERRO:</h1>';
    echo '<pre>' . $mail->ErrorInfo . '</pre>';
}