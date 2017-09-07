<?php
/*Парсит файл vgroups.csv, считает сколько занято портов у коммутатора. Для этого обрабатывает $login в каждой строке файла.*/

function write_log_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "com-port.log"; 
		$log_error = fopen($error_log_file, "a");
		fwrite($log_error, "[$now] [error] $str\n");
	}


if(file('vgroups.csv')) {
	$file_array = file('vgroups.csv');  //записываем данные из файла в массив 
} else {
	write_log_error( "Can't open file vgroups.csv");
	exit();
}
$com_array = array();
foreach ($file_array as $i=>$str)  {

	list( , , , , ,$login, , , , , , , , , , , , , , , , , , , , , , , , , ) = split(';', $str );
	
	if ( strlen($login) === 29 && substr_count($login, '-') === 1 ) { //substr( $login, 0, 4 ) === '0006'
		
		list( $com, $port ) = split('-', $login );
		
		if(!ctype_alnum($com)){
			continue;
		}
		if ( isset( $com_array[$com] ) ) {
		
			$com_array[$com][] = $port;
		} else {
		
			$com_array[$com] = array($port);
		}
	} else {
		continue;
	}
	
}

//print_r($com_array);
///////////////////////////////////////
require_once('libphp-phpmailer/PHPMailerAutoload.php');

///////////////////////////////////////	
$table = "</br>
	<table border='1'>
	<tr bgcolor='#5CC1FF'>
		<th>Коммутатор</th>
		<th>Всего портов</th>
		<th>Порт</th>
	</tr>
	";
foreach ($com_array as $com => $port_arr) {
	
	$table = $table . "<tr>
		<td rowspan='".count($port_arr)."'>$com</td>
		<td rowspan='".count($port_arr)."'>".count($port_arr)."</td>";
	
	foreach ($port_arr as $i => $port) {
		if ($i===0) {
			$table = $table . "<td>$port</td></tr>";
		} else {
			$table = $table . "<tr><td>$port</td></tr>";
		}
	}

}
$table = $table . "</table>";

///////////////////////////////////////

$mail = new PHPMailer;
$mail->IsSMTP(); //Устанавливает флаг что письмо будет отправлено через SMTP протокол.
$mail->Host = '192.168.1.1';  // Список SMTP хостов
$mail->Port = 25;   // TCP port to connect to
$mail->setFrom('danilicheva@ivc.nnov.ru');//bankparser@ivc.nnov.ru  BankParser
$mail->addAddress('danilicheva@ivc.nnov.ru');
		//$mail->addAddress('mokhin@ivc.nnov.ru');
$mail->CharSet = 'utf-8';
$mail->Subject = "com-port";
$mail->Body = $table;
$mail->AltBody = "не поддерживает html";
	
if(!$mail->send()) {
	echo 'Message could not be sent.';
	echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
	echo "Message has been sent"; 
}
//////////////////////////////////////

?>
