<?php
$host = "81.19.128.73"; //данные о БД
  $user = "tariff";
  $password = "TrubKakuRa";
  $db = "billing19_002";
  if (!mysql_connect($host, $user, $password)) //проверяет соединение с SQL сервером
  {
    echo "<h2>MySQL Error!</h2>";
    exit;
  }
 $descr_array = array();
 mysql_select_db($db);//выбирает для работы указанную базу данных на сервере, на который ссылается переданный указатель
 $descr_query = mysql_query("SELECT tel_cat.zone_id, tel_cat.descr FROM tel_cat;");
 for($j = 0; $j < mysql_num_rows($descr_query); ++$j) {
 
 $descr = mysql_fetch_array($descr_query);
 $descr_array[] = $descr;
 }
		// Формируем массив с ответом
		$result = NULL;
		$i = 0;
		foreach ($descr_array as $descr) {
			$result[$i]['descr_id'] = $descr['zone_id'];
			$result[$i]['descr'] = $descr['descr'];
			$i++;
		}

// Преобразуем данные в формат json, чтобы их смог обработать JavaScript-сценарий, приславший запрос
echo json_encode($result);
?>