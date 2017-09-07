<?php
/*Собственно просто отправка письма. Если курс падает ниже 62.5р, то отправляется письмо с заголовком "!!! КУРС ДОЛЛАРА НА ЗАВТРА УПАЛ"*/
	require_once('libphp-phpmailer/PHPMailerAutoload.php');

	$mail = new PHPMailer;
	$mail->IsSMTP(); //Устанавливает флаг что письмо будет отправлено через SMTP протокол.
	$mail->Host = '192.168.1.1';  // Список SMTP хостов
	$mail->Port = 25;   // TCP port to connect to
	$mail->setFrom('billing@ivc.nnov.ru');//bankparser@ivc.nnov.ru  BankParser
	$mail->addAddress('service@ivc.nnov.ru');
	$mail->addAddress('danilicheva@ivc.nnov.ru');
	$mail->addAddress('diter@ivc.nnov.ru');
	$mail->CharSet = 'utf-8';
	if ($i === 1) {
		$mail->Subject = "!!! КУРС ДОЛЛАРА НА ЗАВТРА УПАЛ";
		$mail->Body = "<style type='text/css'>
   * { 
    font-size: 120%; 
    font-family: Verdana, Arial, Helvetica, sans-serif; 
    color: #240000;
    text-align: center;
    font-weight: bold;
    background: #F44F4F;
   }
  </style>Курс доллара ниже $limit руб. Требуется реакция.<br/>Текущий курс: $rate руб." ;
	} elseif ($i === 0) {
		$mail->Subject = "Курс доллара на завтра";
		$mail->Body = "Текущий курс доллара: $rate руб.<br/>Пока все хорошо." ;
	} elseif ($i === 2) {
		$mail->Subject = "Курс доллара";
		$mail->Body = "Скрипт работает некорректно." ;
	}
	$mail->AltBody = "не поддерживает html";
	
	if(!$mail->send()) {
		echo 'Message could not be sent.';
		echo 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
		echo "Message has been sent\n"; 
	}
?>
