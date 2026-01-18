<?php
use PHPMailer\PHPMailer\PHPMailer;
require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    echo "<h2>Testando SMTP com localhost...</h2>";
    
    $mail->SMTPDebug = 3; // Debug detalhado
    $mail->isSMTP();
    $mail->Host = 'localhost';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@splitstore.com.br';
    $mail->Password = 'Lulisera@112';
    $mail->Port = 25;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom('noreply@splitstore.com.br', 'SplitStore');
    $mail->addAddress('patelagames2013@gmail.com', 'Teste'); // SEU E-MAIL
    
    $mail->isHTML(true);
    $mail->Subject = 'Teste SMTP Localhost';
    $mail->Body = '<h1>Funcionou!</h1>';
    
    $mail->send();
    echo '<h1 style="color: green;">✅ SUCESSO!</h1>';
    
} catch (Exception $e) {
    echo '<h1 style="color: red;">❌ ERRO:</h1>';
    echo '<pre>' . $e->getMessage() . '</pre>';
    echo '<pre>' . $mail->ErrorInfo . '</pre>';
}