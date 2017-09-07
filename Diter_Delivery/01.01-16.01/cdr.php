<?php
/* с 01.01-16.01 в billing не записались данные с eltex. 
Этот скрипт берет данные из таблицы cdr_eltex и записывает их в соответствующую таблицу tel001201701day*/


function write_log_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "lanbilling_error.log";
		$log_error = fopen($error_log_file, "a");
		fwrite($log_error, "[$now] [error] $str\n");
		return;
	}


class  ivc_noc {

	var $data;
	
	function __construct() {
		$host = "81.19.128.73"; //данные о БД
		$user = "tariff";
		$password = "TrubKakuRa";
		$db = "ivc_noc";

		if (!$this->data = mysqli_connect($host, $user, $password)) { //проверяет соединение с SQL сервером
			write_log_error("mysqli_connect ERROR :" . mysqli_error());
			exit;
		}
		if ( !mysqli_select_db($this->data, $db) ) { //выбирает для работы указанную базу данных на сервере, на который ссылается переданный указатель
			write_log_error("mysqli_select_db($db) ERROR :" . mysqli_error());
			exit;
		}
		return;
	}

	function ivc_noc_query($query_str) {

		$tel_query = mysqli_query($this->data, $query_str); //запрос
		if (!$tel_query) {
			//echo "$query_str\n";
			write_log_error(mysql_errno() . ": " . mysql_error());
		}
		return $tel_query;
	}
		
}

class  billing19_002 {

	var $data;
	
	function __construct() {
		$host = "81.19.128.73"; //данные о БД
		$user = "root";//"tariff";
		$password = "mypasMYsqp";//"TrubKakuRa";
		$db = "billing19_002";

		if (!$this->data = mysqli_connect($host, $user, $password)) { //проверяет соединение с SQL сервером
			write_log_error("mysqli_connect ERROR :" . mysqli_error());
			exit;
		}
		if ( !mysqli_select_db($this->data, $db) ) { //выбирает для работы указанную базу данных на сервере, на который ссылается переданный указатель
			write_log_error("mysqli_select_db($db) ERROR :" . mysqli_error());
			exit;
		}
		return;
	}

	function billing_query($q_str) {

		$t_query = mysqli_query($this->data, $q_str); //запрос
		if (!$t_query) {
			//echo "$q_str\n";
			write_log_error(mysql_errno() . ": " . mysql_error());
		}
		return $t_query;
	}
		
}

$days = array('01');//, '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16');
$numbers = array('43716', '43717', '26195', '26196', '26197', '26198', '26199', '41858', '41859', '43729', '43725');
$mob_tel_query = "SELECT `tel_cat`.zone_num
		FROM `tel_cat_idx`
		LEFT JOIN `tel_cat` ON `tel_cat`.zone_id = `tel_cat_idx`.zone_id
		WHERE `tel_cat_idx`.tar_id = '2671' and `tel_cat`.descr = 'Нижегородская обл, СПС'";
$mob_tel_array = array();

$myivc_noc = new ivc_noc;
$mybilling19_002 = new billing19_002;
	
/* Массив мобильников за 2 р/мин */
$billing_query = $mybilling19_002 ->billing_query($mob_tel_query);
for ( $mob_j = 0; $mob_j < mysqli_num_rows($billing_query); ++$mob_j ) {
	$mob_tel_array[] = mysqli_fetch_assoc($billing_query)['zone_num'];
}

foreach($days as $day) { //цикл по дням

	$i = 1;
	$query_str = "SELECT * FROM `cdr_eltex`
	WHERE `cdr_eltex`.`start` LIKE '2017-01-".$day."%' AND `cdr_eltex`.`call_duration` >0 
	AND (";
			
	foreach($numbers as $number){ //формируем строку запроса
		$query_str = $query_str . " `cdr_eltex`.`callers_number_exit_SMG` LIKE '%$number%' ";
		if( isset($numbers[$i]) ){
			$query_str = $query_str . "OR";
		}
		$i++;
	}
	$query_str = $query_str . ")";

	//print $query_str . "\n";
	
	$query = $myivc_noc ->ivc_noc_query($query_str);

	if ( !$query ) {
		return;
	}

	for ( $j = 0; $j < mysqli_num_rows($query); ++$j ) { //цикл по номрам за день

		$respons_str = mysqli_fetch_assoc($query);
		
		/* Находим tar_id */
		$tar_id_query = "SELECT v.tar_id FROM  `vgroups` = v,  `phones_history` = ph,  `phones` = p
		WHERE p.number =  '".$respons_str['callers_number_entrance_SMG']."' AND ph.phone_id = p.record_id AND ph.timeto IS NULL  AND v.vg_id = ph.vg_id";
		$billing_query = $mybilling19_002 ->billing_query($tar_id_query);
		if( mysqli_num_rows($billing_query)===1 ){
			$tar_id = mysqli_fetch_assoc($billing_query)['tar_id'];
		} elseif(mysqli_num_rows($billing_query)===2) {
			print_r($billing_query);
		} else {
			write_log_error("Запрос возвращает ".mysqli_num_rows($billing_query)." строк: $tar_id_query");
			echo "=(((\n";
			continue;
		}
		$billing_query = 0;
		
		/* Нажодим тариф */
		
		if( $respons_str['callee_number_entrance_SMG']{0} === '8' ) {
			$respons_str['callee_number_entrance_SMG']{0} = '7';
		}
		if ( strlen($respons_str['callee_number_entrance_SMG']) === 11 ) {
		
			if ( substr($respons_str['callee_number_entrance_SMG'], 0, 2) !== '79' ) { // если не мобильный
			
				$pref = substr($respons_str['callee_number_entrance_SMG'], 0, 4);
				
				/* Находим cat_idx */
				$cat_idx_query = "SELECT cat_idx FROM  `tel_cat_idx` 
				WHERE tar_id =  '$tar_id' 
				AND zone_id IN (SELECT zone_id FROM  `tel_cat` 
				WHERE zone_num =  '$pref')";
				$billing_query = $mybilling19_002 ->billing_query($cat_idx_query);
				if( mysqli_num_rows($billing_query)===1 ){
					$cat_idx = mysqli_fetch_assoc($billing_query)['cat_idx'];
				} else {
					write_log_error("Запрос возвращает ".mysqli_num_rows($billing_query)." строк: $tar_id_query");
					echo "=(((\n";
					continue;
				}
				$billing_query = 0;
				
				/* Находим above */
		
				$above_query = "SELECT above 
				FROM  `categories` 
				WHERE tar_id =  '$tar_id'
				AND cat_idx =  '$cat_idx'";
				$billing_query = $mybilling19_002 ->billing_query($above_query);
				if( mysqli_num_rows($billing_query)===1 ){
					$above = mysqli_fetch_assoc($billing_query)['above'];
					
				} else {
					write_log_error(" Запрос возвращает ".mysqli_num_rows($billing_query)." строк: $tar_id_query");
					echo "=(((\n";
					continue;
				}
				$billing_query = 0;
			} else { // если мобильный
				$cat_idx = '';
				$above = 2.9;
				foreach($mob_tel_array as $mob_tel_pref) {
					if ( strpos($respons_str['callee_number_entrance_SMG'], $mob_tel_pref) ) {
						$above = 2;
					}
				}
			}
			
		} else { //если номер 7 значный 
			$cat_idx = '';
			$above = 0.36;
		} 
		
		$billing_query_str = "INSERT INTO tel001201701".$day." ( numfrom, numto, duration, timefrom, vg_id, uid, amount, duration_round, tar_id,
		cat_idx, zone_id, oper_id, original_numfrom, original_numto, original_vg_id )
		VALUES ('".$respons_str['callers_number_entrance_SMG']."', '".$respons_str['callee_number_entrance_SMG']."',
		'".$respons_str['call_duration']."', '".$respons_str['start']."',
		(SELECT ph.vg_id FROM  `phones_history` = ph,  `phones` = p
		WHERE p.number =  '".$respons_str['callers_number_entrance_SMG']."' AND ph.phone_id = p.record_id AND ph.timeto IS NULL ),
		(SELECT v.uid FROM  `vgroups` = v,  `phones_history` = ph,  `phones` = p
		WHERE p.number =  '".$respons_str['callers_number_entrance_SMG']."' AND ph.phone_id = p.record_id AND ph.timeto IS NULL AND v.vg_id = ph.vg_id), 
		'".$above * round($respons_str['call_duration']/60)."', 
		'".round($respons_str['call_duration']/60)."', 
		'$tar_id',
		'$cat_idx',
		( SELECT zone_id FROM `tel_cat_idx` where tar_id = '$tar_id' 
		and cat_idx = '$cat_idx' ),
		( SELECT oper_id FROM `categories` where tar_id = '$tar_id' 
		and cat_idx = '$cat_idx' ),
		'".$respons_str['callers_number_entrance_SMG']."', 
		'".$respons_str['callee_number_entrance_SMG']."', 
		(SELECT ph.vg_id FROM  `phones_history` = ph,  `phones` = p
		WHERE p.number =  '".$respons_str['callers_number_entrance_SMG']."' AND ph.phone_id = p.record_id AND ph.timeto IS NULL )
		)";
		
		$billing_query = $mybilling19_002 ->billing_query($billing_query_str);
		if(!$billing_query) {
			write_log_error("Не проходит $billing_query_str");
		} else {
			print "-----\n";
		}

	}
	echo "$day\n";
}

?>
