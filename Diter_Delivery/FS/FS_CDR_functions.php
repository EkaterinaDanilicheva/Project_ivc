<?php
/*Функции скрипта FS_CDR.php*/

function write_log_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "FS_CDR.log"; 
		$log_error = fopen($error_log_file, "a");
		fwrite($log_error, "[$now] [error] $str");
	}

function parser_fs_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "Parser_error.log"; 
		$log_error = fopen($error_log_file, "a");
		fwrite($log_error, "[$now] [error] $str");
	}

function parser_fs($date, $str, $sec_array, $min_array) {

 $min_duration = 0;

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
	return array('sec_array'=> $sec_array, 'min_array'=> $min_array);
}
///////////////////////////////////////

function update_upstream_count($date, $sec_array, $min_array) {
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
							parser_fs_error("mysql_query($query)\n");
						}
					} else {
					
						$query = "INSERT INTO `upstream_count` ( date, operator, duration, minute_duration ) VALUES ( '$date', '$operator', '$sum', '".$min_array[$operator]."' )";
						if( !mysql_query($query) ){
							parser_fs_error("mysql_query($query)\n");
						}
					}
				}
			} else {
				foreach ($sec_array as $operator=>$sum)  { 
					$query = "INSERT INTO `upstream_count` ( date, operator, duration, minute_duration ) VALUES ( '$date', '$operator', '$sum', '".$min_array[$operator]."' )";
					if( !mysql_query($query) ){
						parser_fs_error("mysql_query($query)\n");
					}
				}
			}
		} else {
			parser_fs_error("mysql_query() error in $query\n");
		}
	} else {
		parser_fs_error("empty date\n");
	}
	return 0;
}
?>
