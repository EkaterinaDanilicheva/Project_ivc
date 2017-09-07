<?php
/* Этот скрипт присылает email с текущим курсом валюты
** Класс CBR
** Производит запрос курсов валют с Веб службы Центробанка России
*/
class CBR
{
 // WSDL службы Центробанка
 const WSDL = "http://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL";
 // Экземпляр класса SoapClient
 protected $soap;
 // Дата запроса в формате SOAP
 protected $soapDatedate = "";
 // XML ответа Веб-службы
 protected $soapResponse = "";
 // Ассоциативный массив с кодами валют
 public $currencyCodes = array();

 // Первоначальная инициализация
 public function __construct()
 {
  $this->soap = new SoapClient(CBR::WSDL);
  $this->getCurrencyCodes();
 }

 // Метод формирует строку с датой временем для SOAP вызода
 // http://www.w3.org/TR/xmlschema-2/#dateTime
 // Параметры:
 //   $timeStamp - дата/время в формате UNIX
 //   $withTime - необязательно если true, 
      //                то преобразование вместе со временем суток, 
 //                иначе только дата
 protected function getSOAPDate($timeStamp, $withTime = false)
 {
  $soapDate = date("Y-m-d", $timeStamp);
  return ($withTime) ? 
   $soapDate .  "T" . date("H:i:s", $timeStamp) :
   $soapDate . "T00:00:00";
 }
 
 // Метод возвращает XML строку с результатами вызова Веб-службы
 // Параметры:
 //   $date - дата, на которую производится запрос, 0 - сегодня 
 protected function getXML($date)
 {
  // Строка даты, на которую производится вызов
  $currentDate = $this->getSOAPDate($date);
  // Если предыдущий запрос службы был не на эту дату...
 // if ($currentDate != $this->soapDate)
  //{
   // Вызов Веб-службы
   $this->soapDate = $currentDate;
   $params["On_date"] = $currentDate;
   $response = $this->soap->GetCursOnDateXML($params);
   $this->soapResponse = 
                    $response->GetCursOnDateXMLResult->any;
  //}
  return  $this->soapResponse;
 }
 
 // Метод возвращает курс указанной валюты 
 // Параметры:
 //   $currencyCode - код валюты: USD, EUR и т.п.
 //   $date - необязательно, дата, на которую производится запрос, 
      //           0 - сегодня
 public function getRate($currencyCode, $date = 0)
 {
  if (!$date) $date = time();
  $xml = simplexml_load_string($this->getXML($date));
  $xPath = "/ValuteData/ValuteCursOnDate[VchCode='$currencyCode']";
  $result = $xml->xpath($xPath);
  if (count($result) == 0) {
    return 0;
  } else {
  return $result[0]->Vcurs;
  }
 }
 
 // Метод заполняет массив кодов валют, по данным на текущий день
 protected function getCurrencyCodes()
 {
  $xml = simplexml_load_string($this->getXML(time()));
  $xPath = "/ValuteData/ValuteCursOnDate";
  $allCurrencies = $xml->xpath($xPath);
  foreach ($allCurrencies as $currency)
  {
   $code = trim($currency->VchCode);
   $name = trim( $currency->Vname );//trim(iconv("UTF-8", "windows-1251", $currency->Vname));
   $this->currencyCodes[$code] = $name;
  }
 }
}

$i = 0;
$cbr = new CBR;
$limit = 62.5;
if ($cbr) {
	$rate = $cbr->getRate('USD', time ()+86400);
	if($rate <= $limit) {
		$i = 1;
	}
} else {
	$i = 2;
}
include 'send_mail.php';
?>
