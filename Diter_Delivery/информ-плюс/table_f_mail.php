<?php
/*Формируем html тфблицу с результатами работы скрипта month_mail.php или table_f_mail.php*/
	$table = "
	<table border='1'>
	<tr>
		<th colspan='2'>Общая сумма по всем номерам за период $begin - $end</font></th>
	</tr>
	<tr>
		<td>$begin - $end</td>
		<td bgcolor='#FBF0DB'>$sum руб.</td>
	</tr>
   </table>";
?>
