<?php
/*Находит ценник и считает цену каждого вызова в cdr eltex. Это старый скрипт вместо него работает  Eltex_price.py*/

function write_log_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "Eltex_price.log"; 
		$log_error = fopen($error_log_file, "a");
		fwrite($log_error, "[$now] [error] $str");
	}

////////////////////////////////////
class ivc_noc {
	function __construct() {
		$host='81.19.128.73'; // имя хоста (уточняется у провайдера)
		$database='ivc_noc'; // имя базы данных, которую вы должны создать
		$user='tariff'; // заданное вами имя пользователя, либо определенное провайдером
		$pswd='TrubKakuRa'; // заданный вами пароль
		
		if( !$dbh = mysql_connect($host, $user, $pswd) ){
			write_log_error("Не могу соединиться с MySQL $host\n");
		}
		if ( !mysql_select_db($database) ){
			write_log_error("Не могу подключиться к базе $database\n");
		}
	}
	
	function do_query( $str ) {
		if( $query_row = mysql_query($str) ) {
			return mysql_fetch_assoc($query_row);
		} else {
			write_log_error("mysql_query($str)\n");
		}
	}
}
/////////
class freeswitch {
	function __construct() {
		$host='81.19.142.2'; // имя хоста (уточняется у провайдера)
		$database='freeswitch'; // имя базы данных, которую вы должны создать
		$user='portuser'; // заданное вами имя пользователя, либо определенное провайдером
		$pswd='TrubKakuRa'; // заданный вами пароль
		
		if( !$dbh = mysql_connect($host, $user, $pswd) ){
			write_log_error("Не могу соединиться с MySQL $host\n");
		}
		if ( !mysql_select_db($database) ){
			write_log_error("Не могу подключиться к базе $database\n");
		}
	}
	
	function do_query( $str ) {
		if( $query_row = mysql_query($str) ) {
			return mysql_fetch_assoc($query_row);
		} else {
			write_log_error("mysql_query($str)\n");
		}
	}
	function num_rows( $str ) {
		if( $query_row = mysql_query($str) ) {
			return mysql_num_rows($query_row);
		}
	}
}
///////////////////////////////////////

$callee_names = array('SIP_Ростелеком' => 'rt_price'); //, 'Beeline_reg', 'NLS'
$eltex_info = array ();

$query_str = "SELECT * FROM `cdr_eltex` 
	      WHERE `cdr_eltex`.`price` =0
	      AND `cdr_eltex`.`sum_cost` =0
              AND `cdr_eltex`.`call_duration`>0
              AND `cdr_eltex`.`callee_name`='SIP_Ростелеком'"; 
$ivc_noc = new ivc_noc;

while( $row = $ivc_noc->do_query($query_str) ) {
	
	if ( $callee_name = $callee_names[$row['callee_name']] ) { //если знаем такого оператора
		
		$pref = substr( $row['callee_number_exit_SMG'], 1, 3 );
		$num = substr( $row['callee_number_exit_SMG'], 4, 11 );
		$eltex_info[]=array('start'=>$row['start'], 'callee_name'=>$row['callee_name'], 'callee_number_exit_SMG'=>$row['callee_number_exit_SMG'],
		'price'=>$price, 'sum_cost'=>$price*$row['call_duration']*1.18)
		/*
		$price_query_str = "SELECT price FROM $callee_name WHERE abc=$pref AND from_n<=$num AND to_n>=$num ORDER BY `$callee_name`.`from_n` DESC LIMIT 1";
		$freeswitch = new freeswitch;
		if ( $freeswitch->num_rows($price_query_str)===1 ) {
				$price = $freeswitch->do_query($price_query_str)['price']; //цена за минуту
				$price = $price/60; //узнаем цену за секунду
				$update_query = "UPDATE `cdr_eltex`
						SET `price` = '$price', `sum_cost` = '". $price*$row['call_duration']*1.18 ."'
						WHERE `start` = '".$row['start']."' 
						AND `callee_number_exit_SMG` = '".$row['callee_number_exit_SMG']."'
						AND `callee_name` = '".$row['callee_name']."'";
				
				/*if( !mysql_query($update_query) ){
				
					write_log_error("mysql_query($update_query)\n"); 
				}*//*
		} elseif ( $freeswitch->num_rows($price_query_str)===0 ) {
			
			$price_query_str = "SELECT price FROM $callee_name WHERE abc=7 AND from_n<=$num AND to_n>=$num ORDER BY `$callee_name`.`from_n` DESC LIMIT 1";
			if( $price_query_row = $freeswitch->query($price_query_str) ) {
				$price = $freeswitch->do_query($price_query_str)['price']; //цена за минуту
				$price = $price/60; //узнаем цену за секунду
				$update_query = "UPDATE `cdr_eltex`
						SET `price` = '$price', `sum_cost` = '". $price*$row['call_duration']*1.18 ."'
						WHERE `start` = '".$row['start']."' 
						AND `callee_number_exit_SMG` = '".$row['callee_number_exit_SMG']."'
						AND `callee_name` = '".$row['callee_name']."'";
					
				/*if( !mysql_query($update_query) ){
					
					write_log_error("mysql_query($update_query)\n"); 
				}*//*
				} else { //если НЕ нашли цену
					write_log_error("mysql_query($price_query_str)\n");
				}
		}*/
	} else {

		write_log_error("callee_name ".$row['callee_name']."\n");
	}
}

?>
