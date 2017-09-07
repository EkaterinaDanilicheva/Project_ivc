<?php 
	//данные о БД
$host = "81.19.128.73"; 
$user = "ivc";
$password = "TrubKakuRa";
$db = "ivc_sorm";

	//проверка соединение с SQL сервером
	if (!mysql_connect($host, $user, $password)) {

		 echo "<h2>MySQL Error!</h2>";
		 exit;
	}

	mysql_select_db($db);//выбирает для работы указанную базу данных на сервере, на который ссылается переданный указатель

	class Info { 

		public $name; 
		public $tipe; 
		public $coments;	
	}

$file_name= array("ip_addr_list", "abonents", "abonents_service_list", "bunches" , "clnt_account_histories" , "commutators" , "connection_service" , "payment" , "pay_type" , "service_list" , "supplementary_service");//имя файла с данными о таблице 
$ArraymInfo = array();

foreach ($file_name as $fname) {

$file_array = file("$fname.txt"); // Считывание файла в массив $file_array

	for($i = 0; $i < count($file_array); $i=$i+3) { 
		
		//echo $file_array[$i]."\n"; 
		$mInfo = new Info();
		$mInfo->name = substr($file_array[$i], 0, -1); //отрезаем \n

			if (strpos(substr($file_array[$i+1], 0, -1), "VARCHAR")!==false){ //заменяем VARCHAR на TEXT

				$mInfo->tipe = "TEXT";
			} else {

				if (strpos(substr($file_array[$i+1], 0, -1), "DATE")!==false){ //заменяем DATE на DATETIME

				      $mInfo->tipe = "DATETIME";
				} else {

					if (strpos(substr($file_array[$i+1], 0, -1), "NUMBER")!==false){ //заменяем NUMBER на BIGINT

					      $mInfo->tipe = "BIGINT";
					} else {

					      $mInfo->tipe = substr($file_array[$i+1], 0, -1);
					}
				}
			}

		$mInfo->coments = substr($file_array[$i+2], 0, -1);
		//print_r($mInfo);
		array_push($ArraymInfo, $mInfo);
	} 


	//запись названия и типа столбцов в $text
$text="";
	for($j = 0; $j < count($ArraymInfo); $j++)  {

		$name=$ArraymInfo[$j]->name;
		$tipe=$ArraymInfo[$j]->tipe;

			if($j< count($ArraymInfo)-1) {
	 
				$text= "$text"."$name"." "."$tipe".", ";
			} else {

				$text= "$text"."$name"." "."$tipe";
			}
	}

$query = mysql_query("CREATE TABLE $fname ( $text );");//создаем таблицу
//echo $text ."\n";

}
?>
