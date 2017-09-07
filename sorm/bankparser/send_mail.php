<?php
	require_once('libphp-phpmailer/PHPMailerAutoload.php');

	include 'table_f_mail.php';

	$mail = new PHPMailer;
	$mail->IsSMTP(); //Устанавливает флаг что письмо будет отправлено через SMTP протокол.
	$mail->Host = '192.168.1.1';  // Список SMTP хостов
	$mail->Port = 25;   // TCP port to connect to
	$mail->setFrom('service@ivc.nnov.ru', 'bankparser');//bankparser@ivc.nnov.ru  BankParser
	$mail->addAddress('service@ivc.nnov.ru');
	$mail->addAddress('tereza@ivc.nnov.ru');
	$mail->addAddress('tema@ivc.nnov.ru');
	$mail->addAddress('olga@ivc.nnov.ru');
	$mail->addAddress('diter@ivc.nnov.ru');
	$mail->addAddress('mokhin@ivc.nnov.ru');
	$mail->addAddress('elko@ivc.nnov.ru');
	$mail->addAddress('danilicheva@ivc.nnov.ru');
	$mail->CharSet = 'utf-8';
	$mail->Subject = "Платежи абонентов из клиент-банк.";
	$mail->Body = $table; 
	$mail->AltBody = "не поддерживает html";
	
	if(!$mail->send()) {
		echo 'Message could not be sent.';
		echo 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
		echo "Message has been sent\n"; 
		unlink($file_name); //удаляем файл
	}
?>
