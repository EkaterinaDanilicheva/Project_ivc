<?php
/*Этот скрипт считает сколько ивц звонил на абонент и сколько абонент звонил на ивц в минутах потом отправляет email с таблицей результатов.*/


class  billing19_002 {

	var $data;
	
	function write_log_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "lanbilling_error.log";
		$log_error = fopen($error_log_file, "a");
		fwrite($log_error, "[$now] [error] $str");
		return;
	}
	
	function __construct() {
		$host = "81.19.128.73"; //данные о БД
		$user = "tariff";
		$password = "TrubKakuRa";
		$db = "ivc_noc";

		if (!$this->data = mysqli_connect($host, $user, $password)) { //проверяет соединение с SQL сервером
			$this -> write_log_error("mysqli_connect ERROR :" . mysqli_error() . "\n");
			exit;
		}
		if ( !mysqli_select_db($this->data, $db) ) { //выбирает для работы указанную базу данных на сервере, на который ссылается переданный указатель
			$this -> write_log_error("mysqli_select_db($db) ERROR :" . mysqli_error() . "\n");
			exit;
		}
		return;
	}

	function DB_query($query_str) {

		$tel_query = mysqli_query($this->data, $query_str); //запрос
		if (!$tel_query) {
			echo "$query_str\n";
			//$this -> write_log_error(mysqli_error() . "\n");
		}
		return $tel_query;
	}
		
}

$callee_name_count = array(); //ивц звонил на абонент
$callers_name_count = array(); //абонент звонил на ивц

$sip = array("SIP_Мегафон", "SIP_МТС");
$names = array("SIP_Мегафон"=>array('283', '413', '423'),
"SIP-Баранов-Местная"=>array('22990', '22992', '22998', '22999', '21230', '21231', '21232', '21233', '21234'),
// "ЭР-Телеком Холдинг"=>array('214', '215', '217', '218', '281', '282'),
"ЕН-Телеком"=>array('21180', '21181', '21182', '21183', '21184'),
"SIP_ЭраТелеком"=>array('26175', '26176', '26177', '26178', '26179', '26220', '26223', '26224', '26225', '26226', '26227'),
"Вымпел-Коммуникации"=>array('20', '21202', '21203','21204', '21205','21206','21207','21208','21209',
'21210','21211','21212','21213','21214','21215','21216','21217','21218','21219','21220','21221','21222','21223','21224','21225',
'21226','21227','21228','21229','2128','2129','2135','2136','2137','2138','220','2494065','2494066','2494067','2494068','2494069',
'2494070','2494071','2494072','2494073','2494074','2494075','2494076','2494077','2494078','2494079','2494080','2494081','2494082','2494083','2494084','2494085',
'2494086','2494087','2494088','2494089','2494090','2494091','2494092','2494093','2494094','2494095','2494096','2494097','2494098','2494099','24941','24942',
'24943','24944','24945','24946','24947','24948','24949','2496','2497','2576','2578','2579','2590','2591','2592','2593',
'2597','2598','2722','2723','277','278','2900','2901','2902','2903','2904','2905','2906','2960','2961','2962',
'2963','2964','2965','2966','2967','2968','29690','29692','29693','29694','29695','29696','29698','29699','2997','2998',
'2999','414','41613','41614','41615','41616','41617','41618','41619','41640','41641','41642','41643','41644','41645','41665',
'41680','41681','41682','41683','41690','41691','41692','41693','4191','4616','4617','4618','46190','46192','46193','46194',
'46370','46371','46372','46373','46374','4693'),
"SIP_МТС"=>array('2113', '2114', '2115', '2192', '2193', '2194', '2195', '2196', '2197', '2198', '2721127', '27289', '410', '424'),
"SIP_Айва"=>array('2889'),
"SIP_Мегамакс"=>array('21170', '21171', '21172', '21173', '21174', '2160', '2161', '2162', '2163', '2164', '27250', '27251', '27252', '27253', '27254', '27255', '27286', '41150', '41151', '41152', '41153', '41154', '41155', '4129'),
"МедиаСети"=>array('24595', '24596', '24597', '24598', '24599', '24810', '24812', '24818', '24819', '41695', '41696', '41697', '41698', '41699', '41850', '41851', '41852', '41853', '41854', '41855', '41856', '41864', '43710', '43711', '43712', '43713', '43714', '43715', '43718', '43719', '43720', '43721', '43723', '43728', '43760', '43762', '43763', '43764', '43765', '43766', '43767', '43768', '43769', '46392', '46393', '46394', '46395', '46396', '46397' ),
"ивц"=>array('43716', '43717', '26195', '26196', '26197', '26198', '26199',
'41858', '41859', '43729', '43725'));

$query_str = "SELECT * 
FROM  `cdr_eltex`";
$date = date("Y-m", time() - 86400);//date("Y",time()) . "-$month";
$i = 0;
$mybilling = new billing19_002;

 foreach ($names as $value => $numbers) {
   if ( $value === 'ивц' ) { 
      continue;
   }
   if ( in_array($value, array('ЭР-Телеком Холдинг', 'ЕН-Телеком', 'Вымпел-Коммуникации', 'МедиаСети') ) ) {
      $name = '%';
   } else {
      $name = $value;
   }
   if ( $i === 0 ) { //ивц звонил на абонент
	$query_str = "SELECT CEILING( SUM(  `cdr_eltex`.`call_duration` )/60 ) `call_duration`  FROM  `cdr_eltex` 
	WHERE  `cdr_eltex`.`start` LIKE  '".$date."%' AND `cdr_eltex`.`callee_name` LIKE '$name'";
	if (!in_array($value, $sip)) {
	
		foreach ($names[$value] as $key => $number ) {
		
			while (strlen($number) < 7) {
				$number = $number . "_";
			}
			if( $key=== 0 ) {
				$query_str = $query_str . " AND ( `cdr_eltex`.`callee_number_exit_SMG` LIKE '%831$number' ";
			} else {
				$query_str = $query_str . " OR `cdr_eltex`.`callee_number_exit_SMG` LIKE '%831$number' ";
			}
		}
		$query_str = $query_str . " ) ";
	}
	foreach ($names['ивц'] as $key => $number ) {
		while (strlen($number) < 7) {
				$number = $number . "_";
		}
		if( $key=== 0 ) {
			$query_str = $query_str . " AND ( `cdr_eltex`.`callers_number_exit_SMG` LIKE '%831$number' ";
		} else {
			$query_str = $query_str . " OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%831$number' ";
		}
	}
	$query_str = $query_str . " ) ";
	$i = 1;
  }
  //echo $query_str . "\n\n\n";
  $query = $mybilling ->DB_query($query_str);

	if ( !$query ) {
		return;
	}
  $callee_name_count[$value] = mysqli_fetch_assoc($query)['call_duration'];
 
 
  if ($i === 1) { //абонент звонил на ивц
	$query_str = "SELECT CEILING( SUM(  `cdr_eltex`.`call_duration` )/60 ) `call_duration`  FROM  `cdr_eltex` 
	WHERE  `cdr_eltex`.`start` LIKE  '".$date."%' AND `cdr_eltex`.`callers_name` LIKE '$name'";
	if (!in_array($value, $sip)) {
	
		foreach ($names[$value] as $key => $number ) {
		
			while (strlen($number) < 7) {
				$number = $number . "_";
			}
			
			if( $key=== 0 ) {
				$query_str = $query_str . " AND ( `cdr_eltex`.`callers_number_exit_SMG` LIKE '%831$number' ";
			} else {
				$query_str = $query_str . " OR `cdr_eltex`.`callers_number_exit_SMG` LIKE '%831$number' ";
			}
		}
		$query_str = $query_str . " ) ";
	}
	
	foreach ($names['ивц'] as $key => $number ) {
	
		while (strlen($number) < 7) {
			$number = $number . "_";
		}
		if( $key=== 0 ) {
			$query_str = $query_str . " AND (`cdr_eltex`.`callee_number_exit_SMG` LIKE '%831$number' ";
		} else {
			$query_str = $query_str . " OR `cdr_eltex`.`callee_number_exit_SMG` LIKE '%831$number' ";
		}
	}

	$query_str = $query_str . " ) ";
	$i = 0;
	//echo $query_str . "\n\n\n";
	$query = $mybilling ->DB_query($query_str);

	if ( !$query ) {
		return;
	}
        $callers_name_count[$value] = mysqli_fetch_assoc($query)['call_duration'];
  }
 }
 
//  print_r($callee_name_count);
//  print_r($callers_name_count);
include 'send_mail.php';

?>

