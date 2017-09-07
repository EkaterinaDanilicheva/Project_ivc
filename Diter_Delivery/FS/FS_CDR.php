<?php
/*Этот скрипт парсит файл Master_201703271010.csv в котором находятся cdr с freeswitch и записывает их и соответствующую табличку в БД*/

include 'FS_CDR_functions.php';

////////////////////////////////////
$host='81.19.142.2'; // имя хоста (уточняется у провайдера)
$database='freeswitch'; // имя базы данных, которую вы должны создать
$user='portuser'; // заданное вами имя пользователя, либо определенное провайдером
$pswd='TrubKakuRa'; // заданный вами пароль
 
$dbh = mysql_connect($host, $user, $pswd) or die("Не могу соединиться с MySQL.");
mysql_select_db($database) or die("Не могу подключиться к базе.");
/////////////////для update upstream_count//////////////////////

$upstream_count_array = array('sec_array'=>array(), 'min_array'=>array());
$date = '';
//////////////////

$column_name = array('caller_id_name', 'caller_id_number', 'effective_caller_id_number', 'destination_number' , 'accountcode' , 
'network_addr' , 'billing_number' , 'start_stamp', 'answer_stamp' , 'end_stamp' , 'duration' , 'billsec' , 'hangup_cause' , 'uuid' ,
'bleg_uuid' , 'read_codec' , 'write_codec' , 'gateway_name');

$file_name = $argv[1]; //забираем имя файла из суперглобального массива

if(file($file_name)) {
	$file_array = file($file_name);  //записываем данные из файла в массив 
} else {
	write_log_error("can't open file $file_name\n");
	exit();
}

foreach ($file_array as $i=>$str)  { // читаем файл по строкам

	if (!$date) { //делаем дату
		list( , , , , , , , $strdate, , , , , , , , , , ) = split('","', $str = substr($str, 0, -2) );
		list($date,) = split(' ', $strdate);
	}
	$upstream_count_array = parser_fs( $date, $str, $upstream_count_array['sec_array'], $upstream_count_array['min_array'] ); //обрабатываем строку и записываем ее в upstream_count
	
	$str = str_replace('","', ';', substr($str, 0, -1));
	$str = str_replace('"', '', $str);
	$file_str_arr = explode(';', $str);
	if ( $file_str_arr[11]!=='0' ) 
	{
		$query_str = "INSERT INTO `cdr` ( caller_id_name, caller_id_number, effective_caller_id_number, destination_number, accountcode
		, network_addr, billing_number, start_stamp, answer_stamp, end_stamp, duration, billsec, hangup_cause, uuid, bleg_uuid, read_codec,
		write_codec, gateway_name )
		VALUES (";
		foreach( $file_str_arr as $kl=>$atr ){
			if( $atr==='' ) {
				$query_str = str_replace(', '.$column_name[$kl], '', $query_str);
			} else {
				$query_str = $query_str . " '$atr',";
			}
		}
		$query_str = substr($query_str, 0, -1);
		$query_str = $query_str . " )";
		
		if( !mysql_query($query_str) ){
			write_log_error("mysql_query($query_str)\n");
		}
	}
}
update_upstream_count($date, $upstream_count_array['sec_array'], $upstream_count_array['min_array']);

?>
