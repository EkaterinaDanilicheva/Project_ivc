<?php
/*Находит ценник и считает цену каждого вызова в cdr freeswitch. Это старый скрипт вместо него работает  FS_price.py*/
function write_log_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "FS_price.log"; 
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

$gateway_names = array('RT' => 'rt_price', 'Beeline' => 'beeline_price', 'beeline' => 'beeline_price'); //, 'Beeline_reg', 'NLS'

/////////////////////update всех операторов, которых не знаем (потом надо убрать этот блок)
$eltex_sql = "UPDATE `cdr` SET `price` = '-1', `sum_cost` = '-1' 
	      WHERE `cdr`.`price` IS NULL
	      AND `cdr`.`sum_cost` IS NULL
	      AND `cdr`.`billsec`>0
	      AND `cdr`.`billing_number` LIKE '7__________' 
	      AND `gateway_name` NOT IN (";
foreach ( $gateway_names as $oper_name => $table ) {
	$eltex_sql = $eltex_sql . "'$oper_name', ";
}
$eltex_sql = str_replace(', )', ')', $eltex_sql.")");
if( !mysql_query($eltex_sql) ){
	write_log_error("mysql_query($eltex_sql)\n"); 
}
///////////////////////////////////////////////// 

//Читаем cdr по строкам (где нет цены, длительность > 0 и номер LIKE '7__________')
$query_str = "SELECT * FROM `cdr`
	      WHERE `cdr`.`price` IS NULL
	      AND `cdr`.`sum_cost` IS NULL
	      AND `cdr`.`billsec`>0
	      AND `cdr`.`billing_number` LIKE '7__________'"; 
	      
if( $query_row = mysql_query($query_str) ){

	while( $row = mysql_fetch_assoc($query_row) ) { //цикл по строкам cdr
	
		if ( $gateway_name = $gateway_names[$row['gateway_name']] ) { //если знаем такого оператора
		
			$pref = substr( $row['billing_number'], 1, 3 );
			$num = substr( $row['billing_number'], 4, 11 );
			$price_query_str = "SELECT price FROM $gateway_name WHERE abc=$pref AND from_n<=$num AND to_n>=$num ORDER BY `$gateway_name`.`from_n` DESC LIMIT 1";
			
			if( $price_query_row = mysql_query($price_query_str) ) { //если нашли цену
				if ( mysql_num_rows($price_query_row)===1 ) {
					$price = mysql_fetch_assoc($price_query_row)['price']; //цена за минуту
					$update_query = "UPDATE `cdr`
							SET `price` = '$price', `sum_cost` = '". ($price/60)*$row['billsec']*1.18 ."'
							WHERE `start_stamp` = '".$row['start_stamp']."' 
							AND `billing_number` = '".$row['billing_number']."'
							AND `gateway_name` = '".$row['gateway_name']."'";
						
					if( !mysql_query($update_query) ){
					
						write_log_error("mysql_query($update_query)\n"); 
					}
					
				} elseif ( mysql_num_rows($price_query_row)===0 ) {
				
						$price_query_str = "SELECT price FROM $gateway_name WHERE abc=7 AND from_n<=$num AND to_n>=$num ORDER BY `$gateway_name`.`from_n` DESC LIMIT 1";
						if( $price_query_row = mysql_query($price_query_str) ) {
							$price = mysql_fetch_assoc($price_query_row)['price']; //цена за минуту
							$update_query = "UPDATE `cdr`
									SET `price` = '$price', `sum_cost` = '". ($price/60)*$row['billsec']*1.18 ."'
									WHERE `start_stamp` = '".$row['start_stamp']."' 
									AND `billing_number` = '".$row['billing_number']."'
									AND `gateway_name` = '".$row['gateway_name']."'";
							
							if( !mysql_query($update_query) ){
							
								write_log_error("mysql_query($update_query)\n"); 
							}
						} else { //если НЕ нашли цену
							write_log_error("mysql_query($price_query_str)\n");
						}
				}
			} else { //если НЕ нашли цену
				write_log_error("НЕ нашли цену mysql_query($price_query_str)\n");
				$update_query = "UPDATE `cdr`
						SET `price` = '-1', `sum_cost` = '-1'
						WHERE `start_stamp` = '".$row['start_stamp']."' 
						AND `billing_number` = '".$row['billing_number']."'
						AND `gateway_name` = '".$row['gateway_name']."'";
		
				if( !mysql_query($update_query) ){
							
					write_log_error("mysql_query($update_query)\n"); 
				}
			}
			} else { //если НЕ знаем такого оператора

				write_log_error("gateway_name ".$row['gateway_name']."\n");
				$update_query = "UPDATE `cdr`
						SET `price` = '-1', `sum_cost` = '-1'
						WHERE `start_stamp` = '".$row['start_stamp']."' 
						AND `billing_number` = '".$row['billing_number']."'
						AND `gateway_name` = '".$row['gateway_name']."'";
		
				if( !mysql_query($update_query) ){
							
					write_log_error("mysql_query($update_query)\n"); 
				}
			}
	}
} else {
	write_log_error("mysql_query($query_str)\n");
}

?>
