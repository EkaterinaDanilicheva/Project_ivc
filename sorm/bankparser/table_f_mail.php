<?php
	$table = "
	<table border='1'>
	<caption>
		Таблица
	</caption>
	<tr>
		<th colspan='6'><font color = FF0000>Платеж не прошел (ИНН не найден)</font></th>
	</tr>
	<tr>
		<td>Дата</td>
		<td>Номер договора</td>
		<td>Плательщик</td>
		<td>Назначение платежа</td>
		<td>Сумма</td>
		<td>ИНН плательщика</td>
	</tr>";
	  
	foreach($NArray->inn_array as $inn) {
		$table = $table."<tr>
					<td>$inn->date</td>
					<td bgcolor = FFA6A6>$inn->contract_number</td>
					<td bgcolor = FFA6A6>$inn->payer1</td>
					<td>$inn->purpose_of_payment</td>
					<td>$inn->sum</td>
					<td>$inn->inn</td>
				</tr>";
	}
	$table = $table . "
	<tr>
		<th colspan='7'><font color = FF0000>Договор не прошел проверку платежной системы (Номер договора не найден)</font></th>
	</tr>
	<tr>
		<td>Дата</td>
		<td>Номер договора</td>
		<td>Плательщик</td>
		<td>Пользователь</td>
		<td>Назначение платежа</td>
		<td>Сумма</td>
		<td>ИНН плательщика</td>
	</tr>";
	  
	foreach($NArray->unfound_num_arrey as $unfound_num) {
		$table = $table."<tr>
					<td>$unfound_num->date</td>
					<td bgcolor = FFA6A6>$unfound_num->contract_number</td>
					<td bgcolor = FFA6A6>$unfound_num->payer1</td>
					<td>$unfound_num->name</td>
					<td>$unfound_num->purpose_of_payment</td>
					<td>$unfound_num->sum</td>
					<td>$unfound_num->inn</td>
				</tr>";
	}
	$table = $table . "
	<tr>
		<th colspan='7'><font color = FF0000>Платеж не прошел (Номер договора найден)</font></th>
	</tr>
	<tr>
		<td>Дата</td>
		<td>Номер договора</td>
		<td>Плательщик</td>
		<td>Пользователь</td>
		<td>Назначение платежа</td>
		<td>Сумма</td>
		<td>ИНН плательщика</td>
	</tr>";
	  
	foreach($NArray->payment_error_number as $payment_error) {
		$table = $table."<tr>
					<td>$payment_error->date</td>
					<td bgcolor = FFA6A6>$payment_error->contract_number</td>
					<td bgcolor = FFA6A6>$payment_error->payer1</td>
					<td>$payment_error->name</td>
					<td>$payment_error->purpose_of_payment</td>
					<td>$payment_error->sum</td>
					<td>$payment_error->inn</td>
				</tr>";
	}
	$table = $table."
	<tr>
		<th colspan='7'><font color = 5DE900>Успешные платежи</font></th>
	</tr>
	<tr>
		<td>Дата</td>
		<td>Номер договора</td>
		<td>Плательщик</td>
		<td>Пользователь</td>
		<td>Назначение платежа</td>
		<td>Сумма</td>
		<td>ИНН плательщика</td>
	</tr>";
	  
	foreach($NArray->payment_ok_num as $payment_ok) {
		$table = $table."<tr>
					<td>$payment_ok->date</td>
					<td bgcolor = D5FA8F>$payment_ok->contract_number</td>
					<td bgcolor = D5FA8F>$payment_ok->payer1</td>
					<td>$payment_ok->name</td>
					<td>$payment_ok->purpose_of_payment</td>
					<td>$payment_ok->sum</td>
					<td>$payment_ok->inn</td>
				</tr>";
	}
	$table = $table . "</table>";
?>