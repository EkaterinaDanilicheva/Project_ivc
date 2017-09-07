<?php
/*Этот скрипт = FS_CDR.php + FS_CDR_functions.php. Собственно он и работает.*/
function write_log_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "Parser_error.log"; 
		$log_error = fopen($error_log_file, "a");
		fwrite($log_error, "[$now] [error] $str");
	}

////////////////////////////////////
$host='81.19.142.2'; // имя хоста (уточняется у провайдера)
$database='freeswitch'; // имя базы данных, которую вы должны создать
$user='portuser'; // заданное вами имя пользователя, либо определенное провайдером
$pswd='TrubKakuRa'; // заданный вами пароль
 
$dbh = mysql_connect($host, $user, $pswd) or die("Не могу соединиться с MySQL.");
mysql_select_db($database) or die("Не могу подключиться к базе.");
///////////////////////////////////////

$sec_array = array();
$min_array = array();
$min_duration = 0;
$date = '';

$file_name = $argv[1]; //забираем имя файла из суперглобального массива

if(file($file_name)) {
	$file_array = file($file_name);  //записываем данные из файла в массив 
} else {
	write_log_error("can't open file $file_name\n");
	exit();
}

foreach ($file_array as $i=>$str)  { // читаем файл по строкам

	list( , , , , , , , $strdate, , , ,$duration, , , , , ,$name ) = split('","', $str = substr($str, 0, -2) );

	$min_duration = ceil($duration/60);
	
	if (!$date) {
		list($date,) = split(' ', $strdate);
	}
	
	if($date) {
	
		$name = strtolower($name);
		
		if ( isset($sec_array["$name"]) ) {
			$sec_array["$name"] = $sec_array["$name"] + $duration;
			$min_array["$name"] = $min_array["$name"] + $min_duration;
		} else {
			if ($name !== '') {
				$sec_array["$name"] = $duration;
				$min_array["$name"] = $min_duration;
			}
		}
	}
	 
}

///////////////////////////////////////

if ($date) {
	$operators = array();
	$query = "SELECT * FROM `upstream_count` WHERE upstream_count.date = '$date'";
	$res = mysql_query($query);
	if ($res) {

		if ( mysql_fetch_assoc($res) ) { //если запись с этой датой существует

			$query = "SELECT operator FROM `upstream_count` WHERE upstream_count.date = '$date'"; //выбираем всех операторов на заданную дату
			$res = mysql_query($query);
			while( $oper = mysql_fetch_assoc($res) ){
				$operators[] = $oper['operator'];
			}

			foreach ($sec_array as $operator=>$sum)  {
				if( in_array($operator, $operators) ) {
				
					$query = "UPDATE `upstream_count` SET duration = `upstream_count`.`duration` + $sum,
					minute_duration = `upstream_count`.`minute_duration` + " . $min_array[$operator] . "
					WHERE upstream_count.date = '$date' AND operator = '$operator'";
					if( !mysql_query($query) ){
						write_log_error("mysql_query($query)\n");
					}
				} else {
				
					$query = "INSERT INTO `upstream_count` ( date, operator, duration, minute_duration ) VALUES ( '$date', '$operator', '$sum', '".$min_array[$operator]."' )";
					if( !mysql_query($query) ){
						write_log_error("mysql_query($query)\n");
					}
				}
			}
		} else {
			foreach ($sec_array as $operator=>$sum)  { 
				$query = "INSERT INTO `upstream_count` ( date, operator, duration, minute_duration ) VALUES ( '$date', '$operator', '$sum', '".$min_array[$operator]."' )";
				if( !mysql_query($query) ){
					write_log_error("mysql_query($query)\n");
				}
			}
		}
	} else {
		write_log_error("mysql_query() error in $query\n");
	}
} else {
	write_log_error("empty date\n");
}

?>
