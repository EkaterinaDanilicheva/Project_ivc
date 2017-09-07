<?php
/*Это должна была быть рассылка "Поминутный базис расчета" + Баранов, но в итоге этот скрипт не правильный и нигде не нужен.*/

////////////////////////////////////
$host='81.19.142.2'; // имя хоста (уточняется у провайдера)
$database='freeswitch'; // имя базы данных, которую вы должны создать
$user='portuser'; // заданное вами имя пользователя, либо определенное провайдером
$pswd='TrubKakuRa'; // заданный вами пароль

$eltex_host = "81.19.128.73";//данные о БД
$eltex_user = "tariff";
$eltex_password = "TrubKakuRa";
$eltex_database = "ivc_noc";

$dbh = mysql_connect($host, $user, $pswd) or die("Не могу соединиться с MySQL.");
mysql_select_db($database) or die("Не могу подключиться к базе.");

///////////////////////////////////////

$date = date("Y-m");
$sec_arr = array();
$min_arr = array();
$minuts = array ();
$oper_array = array('beeline'=> 1.09*1.18, 'beeline_reg'=>0.9, 'rt'=>1.18*1.18, 'baranov'=>1.0, 'baranov_mts'=>1.0);
/*eltex*/
$eltex_sec_arr = array();
$eltex_min_arr = array();
$eltex_minuts = array ();
/**/
$query_str = "SELECT operator, SUM(duration) 'sec', SUM(minute_duration) 'min' 
FROM `upstream_count` 
WHERE date LIKE '$date%' 
GROUP BY operator";
$eltex_query_str = "SELECT callee_name, SUM( call_duration )  'sec', CEIL( SUM( call_duration ) /60 )  'min'
FROM  `cdr_eltex` 
WHERE START LIKE  '$date%' 
AND ( `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83143716__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83143717__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126195__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126196__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126197__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126198__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83126199__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83141858__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83141859__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83143729__' OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%83143725__' )
AND ( `cdr_eltex`.callee_name = 'SIP_Билайн' OR `cdr_eltex`.callee_name = 'SIP_Ростелеком' )
GROUP BY callee_name";

$sip_arr = array("SIP_Мегафон", "SIP_МТС");
$num_arr = array(
"SIP-Баранов-Местная"=>array('83122990', '83122992', '83122998', '83122999', '83121230', '83121231', '83121232', '83121233', '83121234'),
"ивц"=>array('83143716', '83143717', '83126195', '83126196', '83126197', '83126198', '83126199',
'83141858', '83141859', '83143729', '83143725'),
"SIP_Мегафон"=>array('831283', '831413', '831423'),
"SIP_Мегамакс"=>array('83121170', '83121171', '83121172', '83121173', '83121174', '8312160', '8312161', '8312162', '8312163', '8312164', '83127250', '83127251', '83127252', '83127253', '83127254', '83127255', '83127286', '83141150', '83141151', '83141152', '83141153', '83141154', '83141155', '8314129'),
"SIP_МТС"=>array('8312113', '8312114', '8312115', '8312192', '8312193', '8312194', '8312195', '8312196', '8312197', '8312198', '8312721127', '83127289', '831410', '831424'),
"SIP_Айва"=>array('8312889'),
"SIP_ЭраТелеком"=>array('83126175', '83126176', '83126177', '83126178', '83126179', '83126220', '83126223', '83126224', '83126225', '83126226', '83126227'),
"Билайн"=>array('83120_____', '83121202__', '83121203__', '83121204__', '83121205__', '83121206__', '83121207__', '83121208__', '83121209__', '8312121___', '8312122___', '8312128___', '8312129___', '8312135___', '8312136___', '8312137___', '8312138___', '831220____', '8312496___', '8312497___', '8312576___', '8312577___', '8312578___', '8312579___', '8312590___', '8312591___', '8312592___', '8312593___', '8312597___', '8312598___', '8312722___', '8312723___', '831277____', '831278____', '8312900___', '8312901___', '8312902___', '8312903___', '8312904___', '8312905___', '8312906___', '8312960___', '8312961___', '8312962___', '8312963___', '8312964___', '8312965___', '8312966___', '8312967___', '8312968___', '83129690__', '83129692__', '83129693__', '83129694__', '83129695__', '83129696__', '83129698__', '83129699__', '8312997___', '8312998___', '8312999___', '831414____', '83141613__', '83141614__', '83141615__', '83141616__', '83141617__', '83141618__', '83141619__', '83141640__', '83141641__', '83141642__', '83141643__', '83141644__', '83141645__', '83141665__', '83141680__', '83141681__', '83141682__', '83141683__', '83141690__', '83141691__', '83141692__', '83141693__', '8314191____', '8314616___', '8314617___', '8314618___', '83146190__', '83146192__', '83146193__', '83146194__', '83146370__', '83146371__', '83146372__', '83146373__', '83146374__', '8314693___', '83124941__', '83124942__', '83124943__', '83124944__', '83124945__', '83124946__', '83124947__', '83124948__', '83124949__', '831249407_', '831249408_', '831249409_', '8312494065', '8312494066', '8312494067', '8312494068', '8312494069'),
"ТТК"=>array('831233____', '83141156__', '83141157__', '83141158__', '83141159__', '8314349928', '8314349929', '8314349938', '8314349939', '8314349948', '8314349949', '8314349958', '8314349959', '8314349968', '8314349969', '8314349978', '8314349979', '8314349988', '8314349989', '8314349998', '8314349999'),
"Телеком-МК"=>array('83124449__', '83124496__', '83124497__', '83124498__', '83124499__', '83126000__', '83126001__', '83126002__', '83126003__', '83126004__', '83126005__', '83126006__', '83126007__', '83126008__', '83126009__', '8312608___', '8312609___', '83126105__', '83126106__', '83126107__', '83126108__', '83126109__', '8312614___', '8312615___', '83127288__', '83127297__'),
"МедиаСети"=>array('83124595__', '83124596__', '83124597__', '83124598__', '83124599__', '83124810__', '83124812__', '83124818__', '83124819__', '83141695__', '83141696__', '83141697__', '83141698__', '83141699__', '83141850__', '83141851__', '83141852__', '83141853__', '83141854__', '83141855__', '83141856__', '83141864__', '83143710__', '83143711__', '83143712__', '83143713__', '83143714__', '83143715__', '83143718__', '83143719__', '83143720__', '83143721__', '83143723__', '83143728__', '83143760__', '83143762__', '83143763__', '83143764__', '83143765__', '83143766__', '83143767__', '83143768__', '83143769__', '83146392__', '83146393__', '83146394__', '83146395__', '83146396__', '83146397__'),
"город"=>array('800_______', '8312______', '83141_____','831420____', '831421____', '8314220___','8314221___','8314222___','8314223___', '8314224___', '8314225___', '8314226___','8314227___', '8314228___', '831423____', '831424____', '831425____', '831426____','831427____', '831428____', '831429____', '83143_____','83144000__', '831460____', '831461____', '831462____', '8314630___', '8314632___','8314633___', '8314631___', '83146340__', '83146341__', '83146343__','83146344__', '83146345__', '83146346__','83146347__','83146348__', '83146349__','831464____', '831465____','831466____', '831467____', '831468____', '831469____'),
);

$eltex_baranov_query_str = array('baranov_on' => array("Баранов на ивц"=>"ивц", 
							"Баранов на Мегафон"=>"SIP_Мегафон",
							"Баранов на МегаМакс"=>"SIP_Мегамакс",
							"Баранов на МТС"=>"SIP_МТС",
							"Баранов на Айва"=>"SIP_Айва",
							"Баранов на ЭраТелеком"=>"SIP_ЭраТелеком",
							"Баранов на Билайн"=>"Билайн",
							"Баранов на ТТК"=>"ТТК",
							"Баранов на Телеком-МК"=>"Телеком-МК",
							"Баранов на МедиаСети"=>"МедиаСети",
							"Баранов на город"=>"город"
							),
'baranov_not_in' => "",
"trunk" => array("trunk-Q931", "trunk-SIP"),
'on_baranov' => array("ивц на Баранова"=>"ивц")
);

$res = mysql_query($query_str);

while($row = mysql_fetch_assoc($res))
{
	if ( isset ($oper_array[$row['operator']]) ) {
		$min_arr[$row['operator']] = round( $row['min'] * $oper_array[$row['operator']], 4 );
		$sec_arr[$row['operator']] = round( ($row['sec']/60) * $oper_array[$row['operator']], 4 );
		$minuts[$row['operator']] = $row['min'];
	}
	
}

$min_arr['baranov'] = $min_arr['baranov'] + $min_arr['baranov_mts'];
unset($min_arr['baranov_mts']);
$sec_arr['baranov'] = $sec_arr['baranov'] + $sec_arr['baranov_mts'];
unset($sec_arr['baranov_mts']);

/*Из eltex*/
$eltex_db = mysql_connect($eltex_host, $eltex_user, $eltex_password) or die("Не могу соединиться с MySQL eltex.");
mysql_select_db($eltex_database) or die("Не могу подключиться к базе eltex.");

$res = mysql_query($eltex_query_str);

while($row = mysql_fetch_assoc($res))
{
	if ( strpos( $row['callee_name'], 'Билайн') ) {
		$eltex_min_arr[$row['callee_name']] = round( $row['min'] * $oper_array['beeline'], 4 );
		$eltex_sec_arr[$row['callee_name']] = round( ($row['sec']/60) * $oper_array['beeline'], 4 );
		$eltex_minuts[$row['callee_name']] = $row['min'];
	} else {
		$eltex_min_arr[$row['callee_name']] = round( $row['min'] * $oper_array['rt'], 4 );
		$eltex_sec_arr[$row['callee_name']] = round( ($row['sec']/60) * $oper_array['rt'], 4 );
		$eltex_minuts[$row['callee_name']] = $row['min'];
	}
	
}

foreach ( $eltex_baranov_query_str as $mykey => $array ) {

	$baranov_query_str = "SELECT callers_name, callee_name, SUM( call_duration )  'sec', CEIL( SUM( call_duration ) /60 )  'min' FROM `cdr_eltex` WHERE `cdr_eltex`.`start` LIKE '$date%' ";
	if( $mykey === "baranov_on" ) { //форминуем начало запроса (SIP и номера Баранова)
		
		$baranov_query_str = $baranov_query_str . "AND `cdr_eltex`.`callers_name` = 'SIP-Баранов-Местная'  AND ( ";
		/* Добавляем номера Баранова
		foreach ( $num_arr['SIP-Баранов-Местная'] as $baranov_numbers ) {
			$baranov_query_str = $baranov_query_str . " `cdr_eltex`.`callers_number_exit_SMG` LIKE '%$baranov_numbers' OR";
		}
		$baranov_query_str = substr( $baranov_query_str, 0, -2) . ") AND ("; */
		
		foreach ( $array as $caption => $sip ) { // массив по заголовкам
				
			$new_baranov_query_str = $baranov_query_str;
			if ( in_array($sip, $sip_arr) ) {
				$new_baranov_query_str = str_replace("AND ( ", "AND `cdr_eltex`.`callee_name` = '$sip'", $new_baranov_query_str ); 
			} else {
				foreach ( $num_arr[$sip] as $number ) { //по sip находим номера
					while (strlen($number) < 10) {
						$number = $number . "_";
					}
					$new_baranov_query_str = $new_baranov_query_str . " `cdr_eltex`.`callee_number_exit_SMG` LIKE '%$number' OR";
				}
				$new_baranov_query_str = str_replace(" OR)", " )", $new_baranov_query_str . ")");
			}
			//echo $caption ."\n". $new_baranov_query_str . "\n\n";
			$res = mysql_query( $new_baranov_query_str );

			while($row = mysql_fetch_assoc($res)) {

					$eltex_min_arr[$caption] = round( $row['min'] * $oper_array['baranov'], 4 );
					$eltex_sec_arr[$caption] = round( ($row['sec']/60) * $oper_array['baranov'], 4 );
					$eltex_minuts[$caption] = $row['min'];
				
			}
		}
	} elseif ( $mykey === "baranov_not_in" ) {
		$baranov_query_str = $baranov_query_str . "AND `cdr_eltex`.`callers_name` = 'SIP-Баранов-Местная'  AND ( ";
		foreach ( $eltex_baranov_query_str['baranov_on'] as $sip ) { // массив по заголовкам

			foreach ( $num_arr[$sip] as $number ) { //по sip находим номера
				while (strlen($number) < 10) {
					$number = $number . "_";
				}
				$baranov_query_str = $baranov_query_str . " `cdr_eltex`.`callee_number_exit_SMG` NOT LIKE '%$number' OR";
			}
		}
		$baranov_query_str = str_replace(" OR)", " )", $baranov_query_str . ")");
			//echo "Баранов На всё, что не попало под предыдущие\n". $baranov_query_str . "\n\n";
			$res = mysql_query( $baranov_query_str );

			while($row = mysql_fetch_assoc($res)) {

					$eltex_min_arr['Баранов на всё, что не попало под предыдущие'] = round( $row['min'] * $oper_array['baranov'], 4 );
					$eltex_sec_arr['Баранов на всё, что не попало под предыдущие'] = round( ($row['sec']/60) * $oper_array['baranov'], 4 );
					$eltex_minuts['Баранов на всё, что не попало под предыдущие'] = $row['min'];
				
			}
	} elseif ( $mykey === "trunk" ) {
		$baranov_query_str = $baranov_query_str . "AND `cdr_eltex`.`callee_name` = 'SIP-Баранов-Местная' AND(";
		foreach ( $array as $source_type ) { // массив по заголовкам

			$baranov_query_str = $baranov_query_str . " `cdr_eltex`.`source_type` =  '$source_type' OR";
		}
		$baranov_query_str = str_replace(" OR)", " )", $baranov_query_str . ")") . " AND ("; //GROUP BY source_type
		foreach ( $num_arr['ивц'] as $number ) { // не считаем ивц
			while (strlen($number) < 10) {
				$number = $number . "_";
			}
			$baranov_query_str = $baranov_query_str . " `cdr_eltex`.`callers_number_entrance_SMG` NOT LIKE '%$number' OR";
		}
		$baranov_query_str = str_replace(" OR)", " )", $baranov_query_str . ")") . " GROUP BY source_type";
	
			//echo "на Баранова От всех остальных по источнику\n". $baranov_query_str . "\n\n";
			$res = mysql_query( $baranov_query_str );

			while($row = mysql_fetch_assoc($res)) {

					$eltex_min_arr['на Баранова От всех остальных по источнику '. $row['callers_name']] = round( $row['min'] * $oper_array['baranov'], 4 );
					$eltex_sec_arr['на Баранова От всех остальных по источнику '. $row['callers_name']] = round( ($row['sec']/60) * $oper_array['baranov'], 4 );
					$eltex_minuts['на Баранова От всех остальных по источнику '. $row['callers_name']] = $row['min'];
				
			}
	} else { // На Баранова звонили
		$baranov_query_str = $baranov_query_str . "AND `cdr_eltex`.`callee_name` = 'SIP-Баранов-Местная' AND (";
		/* Добавляем номера Баранова
		foreach ( $num_arr['SIP-Баранов-Местная'] as $baranov_numbers ) {
			$baranov_query_str = $baranov_query_str . " `cdr_eltex`.`callee_number_exit_SMG` LIKE '%$baranov_numbers' OR";
		}
		$baranov_query_str = substr( $baranov_query_str, 0, -2) . ") AND (";*/
		foreach ( $array as $caption => $sip ) { // массив по заголовкам
			$new_baranov_query_str = $baranov_query_str;
			foreach ( $num_arr[$sip] as $number ) { //по sip находим номера
				while (strlen($number) < 10) {
					$number = $number . "_";
				}
				$new_baranov_query_str = $new_baranov_query_str . " `cdr_eltex`.`callers_number_exit_SMG` LIKE '%$number' OR";
			}
			$new_baranov_query_str = str_replace(" OR)", " )", $new_baranov_query_str . ")");
			//echo $caption ."\n". $new_baranov_query_str . "\n\n";
			$res = mysql_query( $new_baranov_query_str );

			while($row = mysql_fetch_assoc($res)) {

					$eltex_min_arr[$caption] = round( $row['min'] * $oper_array['baranov'], 4 );
					$eltex_sec_arr[$caption] = round( ($row['sec']/60) * $oper_array['baranov'], 4 );
					$eltex_minuts[$caption] = $row['min'];
				
			}
		}
	}
	
}

///////////////////////////////////////
require_once('libphp-phpmailer/PHPMailerAutoload.php');

///////////////////////////////////////	
$table = "</br><ins><b>Freeswitch :</b></ins></br>
	<table border='1'>
	<tr bgcolor='#FEC236'>
		<th>Оператор</th>
		<th>Расчет по минутам</th>
		<th>Расчет по Секундам</th>
		<th>Минуты</th>
	</tr>
	";
foreach ($min_arr as $oper => $value) {
	
	$table = $table . "<tr>
		<td>$oper</td>
		<td>$value р.</td>
		<td>". $sec_arr[$oper] ." p.</td>
		<td>". $minuts[$oper] ." мин.</td>
	</tr>";
}
$table = $table . "</table>";
/*eltex*/
$table = $table . "</br><ins><b>Eltex :</b></ins></br>
	<table border='1'>
	<tr bgcolor='#8ED55E'>
		<th>Оператор</th>
		<th>Расчет по минутам</th>
		<th>Расчет по Секундам</th>
		<th>Минуты</th>
	</tr>
	";
foreach ($eltex_min_arr as $eltex_oper => $eltex_value) {
	
	$table = $table . "<tr>
		<td>$eltex_oper</td>
		<td>$eltex_value р.</td>
		<td>". $eltex_sec_arr[$eltex_oper] ." p.</td>
		<td>". $eltex_minuts[$eltex_oper] ." мин.</td>
	</tr>";
}
$table = $table . "</table>";
///////////////////////////////////////

$mail = new PHPMailer;
$mail->IsSMTP(); //Устанавливает флаг что письмо будет отправлено через SMTP протокол.
$mail->Host = '192.168.1.1';  // Список SMTP хостов
$mail->Port = 25;   // TCP port to connect to
$mail->setFrom('danilicheva@ivc.nnov.ru');//bankparser@ivc.nnov.ru  BankParser
$mail->addAddress('danilicheva@ivc.nnov.ru');
		//$mail->addAddress('diter@ivc.nnov.ru');
		//$mail->addAddress('d.karishin@ivc.nnov.ru');
$mail->CharSet = 'utf-8';
$mail->Subject = "Поминутный базис расчета за $date";
$mail->Body = $table;
$mail->AltBody = "не поддерживает html";
	
if(!$mail->send()) {
	echo 'Message could not be sent.';
	echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
	echo "Message has been sent"; 
}
//////////////////////////////////////
?>

