<?php
/*Суммируем данные за весь предыдущий месяц и отправляем присьмо с результатом*/

include 'DB.php';
include 'functions.php';
 
 $week_day = time() - 86400;
 $month = date("n",$week_day);
 $end = date("Y-m-d",$week_day);

 		 $sum = 0;

		 while (date("n",$week_day) === $month)
		 {
			$query = "SELECT SUM(amount) 'sum' FROM `tel001".date("Ymd",$week_day)."` where original_numfrom 
			IN ('78314185873', '78314185874', '78314371773', '78314372929', '78314372971') and uid = '6814'";
			$sum = do_query_sum($query, $sum);
	
			$week_day = $week_day - 86400;

		 }
 
		 $begin = date("Y-m-d", $week_day + 86400);

 include 'send_mail.php';
?>
