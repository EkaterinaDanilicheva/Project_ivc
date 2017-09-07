<?php
/*Суммируем данные за предыдущую неделю и отправляем присьмо с результатом*/

include 'DB.php';
include 'functions.php';
 
 $week_day = time() - 604800;
 $begin = date("Y-m-d",$week_day);
		 $sum = 0;
		 
		 $query = "SELECT SUM(amount) 'sum' FROM `tel001".date("Ymd",$week_day)."` where 
		 original_numfrom IN ('78314185873', '78314185874', '78314371773', '78314372929', '78314372971') and uid = '6814'";
		 $sum = do_query_sum($query, $sum);
print $query. "\n";
		 $week_day = $week_day + 86400;

		 while (date("w",$week_day) != 1)
		 {
			$query = "SELECT SUM(amount) 'sum' FROM `tel001".date("Ymd",$week_day)."` where original_numfrom 
			IN ('78314185873', '78314185874', '78314371773', '78314372929', '78314372971') and uid = '6814'";
			$sum = do_query_sum($query, $sum);
	
			$week_day = $week_day + 86400;

		 }
 
		 $end = date("Y-m-d", $week_day - 86400);
		 

 include 'send_mail.php';
?>
