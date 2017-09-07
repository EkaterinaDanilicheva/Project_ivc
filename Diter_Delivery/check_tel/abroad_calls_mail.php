<?php
/*Находит и блокирует "больные" учетки в billing*/


 $host = "81.19.128.73"; //данные о БД
 $user = "tariff";
 $password = "TrubKakuRa";
 $db = "billing19_002";
 if (!mysql_connect($host, $user, $password)) { //проверяет соединение с SQL сервером
	echo "MySQL Error!";
	exit;
 }
 mysql_select_db($db);//выбирает для работы указанную базу данных на сервере, на который ссылается переданный указатель

/////////////////////////////ПЕРЕМЕННЫЕ////////////////////////////// 

 $mail_array = array();
 $table = "<table border='2' align='center' bgcolor='#D7FFFD' cellpadding='7' bordercolor='#000000'>
	<tr bgcolor='#E73535'>
		<th>Дата</th><th>Логин</th><th>Номер А</th><th>Номер Б</th><th>Длительность</th><th>Стоимость</th>
	</tr>";
 $date = date("Ymd", time());
 $table_name = "tel001" . $date; //'tel00120170117'
 $query = "SELECT vg_id, COUNT( * )  'count' FROM  `$table_name` 
		WHERE numto NOT LIKE  '7%' AND duration >300
		GROUP BY vg_id";

/////////////////////////////////////////

 $query_res =  mysql_query($query) or die("Invalid query: " . mysql_error());

 while ($row = mysql_fetch_assoc($query_res)) { 

	if ( $row['count'] >= 5 ) { //блокируем 'больные' учетки
		
		$update_query = "UPDATE vgroups
		SET blocked = '3'
		WHERE vg_id = '" . $row['vg_id'] . "'";
		$update_query_res =  mysql_query($update_query) or die("Invalid query: " . mysql_error());

//находим информацию о каждом зарубежном звонке с 'больной' учетки и записываем ее в массив
		$info_query = "SELECT t.timefrom, v.login, t.numfrom, t.numto, t.duration, t.amount FROM  `$table_name`=t,  `vgroups` = v
		WHERE t.numto NOT LIKE  '7%' AND t.duration >300
		AND t.vg_id = '".$row['vg_id']."' AND t.vg_id = v.vg_id";

		$info_query_res =  mysql_query($info_query) or die("Invalid query: " . mysql_error());

		while ($info_query_row = mysql_fetch_assoc($info_query_res)) {

			$mail_array[] = $info_query_row;
		}
	}
 }

if( !empty($mail_array) ) { //если нашлись больные номера то шлем письмо

	foreach ($mail_array as $table_str) { //формируем таблицу для письма
		$table = $table . "<tr>
			<td>".$table_str['timefrom']."</td><td>".$table_str['login']."</td><td>".$table_str['numfrom']."</td><td>".$table_str['numto']."</td><td>".$table_str['duration']."сек</td><td>".$table_str['amount']."р</td>
		</tr>";
	}

	$table = $table . "</table>";

////////////////////////////ОТПРАВЛЯЕМ  ПИСЬМО///////////////////////////////////////

 	require_once('libphp-phpmailer/PHPMailerAutoload.php');

 	$mail = new PHPMailer;
 	$mail->IsSMTP(); //Устанавливает флаг что письмо будет отправлено через SMTP протокол.
 	$mail->Host = '192.168.1.1';  // Список SMTP хостов
 	$mail->Port = 25;   // TCP port to connect to
 	$mail->setFrom('danilicheva@ivc.nnov.ru');//bankparser@ivc.nnov.ru  BankParser
 	$mail->addAddress('danilicheva@ivc.nnov.ru');
 	//$mail->addAddress('karpovich@informplusnn.ru');
	//$mail->addAddress('diter@ivc.nnov.ru');
 	$mail->CharSet = 'utf-8';
 	$mail->Subject = "Звонки на зарубежные номера";
 	$mail->Body = $table;
	$mail->AltBody = "не поддерживает html";
	
	if(!$mail->send()) {
		echo 'Message could not be sent.';
		echo 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
		echo "Message has been sent\n"; 
	}
}
?>
