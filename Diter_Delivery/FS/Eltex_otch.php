<?php

////////////////////////////////////
$host='81.19.128.73'; // имя хоста (уточняется у провайдера)
$database='ivc_noc'; // имя базы данных, которую вы должны создать
$user='tariff'; // заданное вами имя пользователя, либо определенное провайдером
$pswd='TrubKakuRa'; // заданный вами пароль
 
$dbh = mysql_connect($host, $user, $pswd) or die("Не могу соединиться с MySQL.");
mysql_select_db($database) or die("Не могу подключиться к базе.");
/////////////////

$date = date("Y-m-d", time() - 86400);
$good_price = fopen("good_price_$date.csv", 'w');
$i = 0;
$bad_price = fopen("bad_price_$date.csv", 'w');
$j = 0;

$query_str = "SELECT callers_name, callers_number_exit_SMG, callee_name, callee_number_exit_SMG, price, sum_cost, billing_amount, (billing_amount - sum_cost)'dif' FROM `cdr_eltex` WHERE start LIKE '$date%'
AND price NOT IN (-1) AND sum_cost NOT IN (-1)";
if( $query_row = mysql_query($query_str) ){

	while( $row = mysql_fetch_assoc($query_row) ) {
		if ( $row['dif'] > 0 ) { //если нормальная цена
			if ( $i===0 ) { //заголовки записываем
				$i ++;
				foreach ($row as $caption => $value) {
					fwrite($good_price, "$caption;");
				}
					fwrite($good_price, "\n");
			}
			foreach ($row as $value) {
				fwrite($good_price, "$value;");
			}
				fwrite($good_price, "\n");
		} else { //если плохая цена
			if ( $j===0 ) { //заголовки записываем
				$j ++;
				foreach ($row as $caption => $value) {
					fwrite($bad_price, "$caption;");
				}
					fwrite($bad_price, "\n");
			}
			foreach ($row as $value) {
				fwrite($bad_price, "$value;");
			}
				fwrite($bad_price, "\n");
		}
		//print_r($row);
	}
}


?>
