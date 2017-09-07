<?php
/*Формирует html таблицу с результатами скрипта CDR_mail.php*/
$table = "</br>
	<table border='1'>
	<tr bgcolor='#B6D6D9'>
		<th>Название</th>
		<th>ивц звонил на абонент</th>
		<th>абонент звонил на ивц</th>
	</tr>
	";
foreach ($callee_name_count as $name => $value) {
	
	$table = $table . "<tr>
		<td>$name</td>
		<td>$value мин.</td>
		<td>". $callers_name_count[$name] ." мин.</td>
	</tr>";
}
$table = $table . "</table>";
?>
