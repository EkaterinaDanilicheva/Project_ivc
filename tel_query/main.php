<?php
ini_set("memory_limit", "2000M");
ini_set("max_execution_time", "0");//set_time_limit(60);
include 'UserInfo.php';

if($_GET['Year']<= date("Y") && $_GET['Month_beg']>0 && $_GET['Month_beg']<12) {
	$Year = $_GET['Year'];
	setcookie("Year", $Year);
	$Month_beg = $_GET['Month_beg'];
}

else{
	echo "Введите нормальные числа!!!";
	die();
}

if(!empty($_GET['Month_end']) && $Month_beg<=$_GET['Month_end']) {

  	$Month_end = $_GET['Month_end'];

}
else{
 	$Month_end = $Month_beg;
 
}

include 'DB.php';

  $TelNum = '78314'; //$_GET['TelNum']
  $detalization = true; //$_GET['detalization']
  $tel_str = ""; //параметр, который нужен в запросе
  $dur_array = array(); //массив длительности разговоров? не используются
  $dur_total_sum = 0;  //сумма длительности разговоров? не используются
  $ArrayUserInfo = array(); //массив с инфой о юзере, для выделения памяти и записи в него

if(!empty($_GET['descr'])) {

	$zone_id = $_GET['descr'];
	include 'dur_sum_query.php';
} else {

	include 'tel_query.php';
}  
?>
