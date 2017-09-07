<?php
/*Записывает данные из таблички  vgroups в файл vgroups.csv*/

////////////////////////////////////
$host='81.88.145.12'; // имя хоста (уточняется у провайдера)
$database='billing'; // имя базы данных, которую вы должны создать
$user='moscow'; // заданное вами имя пользователя, либо определенное провайдером
$pswd='5o8V0dSyntGxRiP2'; // заданный вами пароль
 
$dbh = mysql_connect($host, $user, $pswd) or die("Не могу соединиться с MySQL.");
mysql_select_db($database) or die("Не могу подключиться к базе.");
/////////////////

$query_str = "SELECT * FROM vgroups;";
if( $query_row = mysql_query($query_str) ){

	$file = fopen('vgroups.csv', 'w');
	$i = 0;
	while( $row = mysql_fetch_assoc($query_row) ) { //цикл по строкам
		
		if ( $i===0 ) {
			$i ++;
			foreach ($row as $caption => $value) {
				fwrite($file, "$caption;");
			}
			fwrite($file, "\n");
		}
		foreach ($row as $value) {
			fwrite($file, "$value;");
		}
		fwrite($file, "\n");
	}
	
} else {
	echo "ERROR $query_str\n";
}

?>
