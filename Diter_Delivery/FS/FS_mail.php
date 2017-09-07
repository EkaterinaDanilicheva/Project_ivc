<?php
/*Это рассылка поминутный базис расчета за период. Грубо говоря она считает на сколько денег мы наговорили с каждым оператором. Здесь обсчитыывется eltex и freeswitch и формируются две разные таблички. */

////////////////////////////////////
$host='81.19.142.2'; // имя хоста (уточняется у провайдера)
$database='freeswitch'; // имя базы данных, которую вы должны создать
$user='portuser'; // заданное вами имя пользователя, либо определенное провайдером
$pswd='TrubKakuRa'; // заданный вами пароль

$eltex_host = "81.19.128.73";//данные о БД
$eltex_user = "tariff";
$eltex_password = "TrubKakuRa";
$eltex_database = "ivc_noc";

$dbh = mysql_connect($host, $user, $pswd) or die("Не могу соединиться с MySQL.");
mysql_select_db($database) or die("Не могу подключиться к базе.");

///////////////////////////////////////

$date = date("Y-m");
$sec_arr = array();
$min_arr = array();
$minuts = array ();
$oper_array = array('beeline'=> 1.09*1.18, 'beeline_reg'=>0.8, 'rt'=>1.18*1.18, 'baranov'=>1.0, 'baranov_mts'=>1.0, 'connect'=>1.08);
/*eltex*/
$eltex_sec_arr = array();
$eltex_min_arr = array();
$eltex_minuts = array ();
/**/
$query_str = "SELECT operator, SUM(duration) 'sec', SUM(minute_duration) 'min' 
FROM `upstream_count` 
WHERE date LIKE '$date%' 
GROUP BY operator";
$eltex_query_str = "SELECT callee_name, SUM( call_duration )  'sec', CEIL( SUM( call_duration ) /60 )  'min'
FROM  `cdr_eltex` 
WHERE START LIKE  '$date%' 
AND ( `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83143716__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83143717__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126195__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126196__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126197__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126198__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126199__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83141858__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83141859__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83143729__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83143725__' )
AND ( `cdr_eltex`.callee_name = 'SIP_Билайн' OR `cdr_eltex`.callee_name = 'SIP_Ростелеком' )
GROUP BY callee_name";
$res = mysql_query($query_str);

while($row = mysql_fetch_assoc($res))
{
	if ( isset ($oper_array[$row['operator']]) ) {
		$min_arr[$row['operator']] = round( $row['min'] * $oper_array[$row['operator']], 4 );
		$sec_arr[$row['operator']] = round( ($row['sec']/60) * $oper_array[$row['operator']], 4 );
		$minuts[$row['operator']] = $row['min'];
	}
	
}

$min_arr['baranov'] = $min_arr['baranov'] + $min_arr['baranov_mts'];
unset($min_arr['baranov_mts']);
$sec_arr['baranov'] = $sec_arr['baranov'] + $sec_arr['baranov_mts'];
unset($sec_arr['baranov_mts']);

/*Из eltex*/
$eltex_db = mysql_connect($eltex_host, $eltex_user, $eltex_password) or die("Не могу соединиться с MySQL eltex.");
mysql_select_db($eltex_database) or die("Не могу подключиться к базе eltex.");

$res = mysql_query($eltex_query_str);

while($row = mysql_fetch_assoc($res))
{
	if ( strpos( $row['callee_name'], 'Билайн') ) {
		$eltex_min_arr[$row['callee_name']] = round( $row['min'] * $oper_array['beeline'], 4 );
		$eltex_sec_arr[$row['callee_name']] = round( ($row['sec']/60) * $oper_array['beeline'], 4 );
		$eltex_minuts[$row['callee_name']] = $row['min'];
	} else {
		$eltex_min_arr[$row['callee_name']] = round( $row['min'] * $oper_array['rt'], 4 );
		$eltex_sec_arr[$row['callee_name']] = round( ($row['sec']/60) * $oper_array['rt'], 4 );
		$eltex_minuts[$row['callee_name']] = $row['min'];
	}
	
}

///////////////////////////////////////
require_once('libphp-phpmailer/PHPMailerAutoload.php');

///////////////////////////////////////	
$table = "</br><ins><b>RTU :</b></ins></br>
	<table border='1'>
	<tr bgcolor='#FEC236'>
		<th>Оператор</th>
		<th>Расчет по минутам</th>
		<th>Расчет по Секундам</th>
		<th>Минуты</th>
	</tr>
	";
foreach ($min_arr as $oper => $value) {
	
	$table = $table . "<tr>
		<td>$oper</td>
		<td>$value р.</td>
		<td>". $sec_arr[$oper] ." p.</td>
		<td>". $minuts[$oper] ." мин.</td>
	</tr>";
}
$table = $table . "</table>";
/*eltex*/
$table = $table . "</br><ins><b>Eltex :</b></ins></br>
	<table border='1'>
	<tr bgcolor='#8ED55E'>
		<th>Оператор</th>
		<th>Расчет по минутам</th>
		<th>Расчет по Секундам</th>
		<th>Минуты</th>
	</tr>
	";
foreach ($eltex_min_arr as $eltex_oper => $eltex_value) {
	
	$table = $table . "<tr>
		<td>$eltex_oper</td>
		<td>$eltex_value р.</td>
		<td>". $eltex_sec_arr[$eltex_oper] ." p.</td>
		<td>". $eltex_minuts[$eltex_oper] ." мин.</td>
	</tr>";
}
$table = $table . "</table>";
///////////////////////////////////////

$mail = new PHPMailer;
$mail->IsSMTP(); //Устанавливает флаг что письмо будет отправлено через SMTP протокол.
$mail->Host = '192.168.1.1';  // Список SMTP хостов
$mail->Port = 25;   // TCP port to connect to
$mail->setFrom('danilicheva@ivc.nnov.ru');//bankparser@ivc.nnov.ru  BankParser
$mail->addAddress('danilicheva@ivc.nnov.ru');
		//$mail->addAddress('diter@ivc.nnov.ru');
$mail->CharSet = 'utf-8';
$mail->Subject = "Поминутный базис расчета за $date";
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

