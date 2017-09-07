<?php
/*Отправляем письмо*/
	require_once('libphp-phpmailer/PHPMailerAutoload.php');
	
	include 'table_f_mail.php';

	$mail = new PHPMailer;
	$mail->IsSMTP(); //Устанавливает флаг что письмо будет отправлено через SMTP протокол.
	$mail->Host = '192.168.1.1';  // Список SMTP хостов
	$mail->Port = 25;   // TCP port to connect to
	$mail->setFrom('danilicheva@ivc.nnov.ru');//bankparser@ivc.nnov.ru  BankParser
	$mail->addAddress('danilicheva@ivc.nnov.ru');
		//$mail->addAddress('karpovich@informplusnn.ru');
		//$mail->addAddress('diter@ivc.nnov.ru');
	$mail->CharSet = 'utf-8';
	$mail->Subject = "Cтатистика за период $begin - $end";
	$mail->Body = $table;
	$mail->AltBody = "не поддерживает html";
	
	if(!$mail->send()) {
		echo 'Message could not be sent.';
		echo 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
		echo "Message has been sent\n"; 
	}
?>
