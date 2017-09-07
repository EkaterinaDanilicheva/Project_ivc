<?php

for ($k = $Month_beg; $k < $Month_end+1; ++$k) { //циклим в отрезке месяцев
  
    $day_in_mounth_count = cal_days_in_month(CAL_GREGORIAN, $k, $Year); //возвращает количество дней месяца
    
    for ($i = 1; $i < $day_in_mounth_count+1; ++$i) { //пробегаем каждый день в месяце

      if ($i < 10)
	$tel_str = "tel001$Year"."0".$k."0".$i;
      else
	$tel_str = "tel001$Year"."0".$k.$i;
	
      $dur_sum = 0;  

      $dur_sum_query = mysql_query("SELECT SUM( $tel_str.duration ) FROM $tel_str WHERE $tel_str.zone_id = $zone_id"); //собственно запрос к БД '2066'
      
      for($j = 0; $j < mysql_num_rows($dur_sum_query); ++$j) {
	
	$dur = mysql_fetch_array($dur_sum_query); // Возвращает массив с обработанным рядом результата запроса
      }
      
	$dur_total_sum += $dur[0]; //увеличиваем  $dur_total_sum
 
    }
    echo "Месяц: $k На направление: $zone_id продолжительность исходящих звонков сек. : $dur_total_sum</br>";
   // include 'vivod.php';

  }
?>
