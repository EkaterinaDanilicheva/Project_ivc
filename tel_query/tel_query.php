<?php

for ($k = $Month_beg; $k < $Month_end+1; ++$k) { //циклим в отрезке месяцев
    echo "for 222<br>";
    $dur_total_sum = 0;
    $day_in_mounth_count = cal_days_in_month(CAL_GREGORIAN, $k, $Year); //возвращает количество дней месяца
    echo "for кол-во дней в $k месяце $day_in_mounth_count<br>"; //вывели кол-во дней в k месяце */
    for ($i = 1; $i < $day_in_mounth_count+1; ++$i) { //пробегаем каждый день в месяце
echo "for пробегаем $i день в $k месяце <br>";
      if ($i < 10) {
	$tel_str = "tel001$Year"."0".$k."0".$i;
      }
      else {
	$tel_str = "tel001$Year"."0".$k.$i;
      }

      $dur_sum = 0;  
      $tel_query = mysql_query("SELECT $tel_str.numfrom, $tel_str.numto, $tel_str.duration, 
      $tel_str.timefrom, tel_cat.descr FROM $tel_str, tel_cat where $tel_str.zone_id=tel_cat.zone_id"); //собственно запрос к БД

      for($j = 0; $j < mysql_num_rows($tel_query); ++$j) {//mysql_num_rows() возвращает количество рядов результата запроса.
	$tel = mysql_fetch_array($tel_query); // Возвращает массив с обработанным рядом результата запроса // в этом массиве хранятся результаты запроса?
	
	//print_r($tel);
	$dur_sum += $tel["duration"]; //$dur_sum = $dur_sum + $tel["duration"]
	
	if ($detalization) {
	    $MUserInfo = new UserInfo();
	    $MUserInfo->telA = $tel["numfrom"];
	    $MUserInfo->telB = $tel["numto"];
	    $MUserInfo->duration = $tel["duration"];
	    $MUserInfo->timeFrom = $tel["timefrom"];
	    $MUserInfo->descr = $tel["descr"];
	    $ArrayUserInfo[] = $MUserInfo;
	}
      }
      
      $dur_total_sum += $dur_sum; //увеличиваем  $dur_total_sum это пока не используем
    }
echo "for-> vivod<br>";
	include 'vivod.php';
    echo "for Месяц: $k На номера: $TelNum продолжительность исходящих звонков сек. : $dur_total_sum\n\r";
    
  }
?>
