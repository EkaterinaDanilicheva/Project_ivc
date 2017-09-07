<?php
	include 'functions.php';
	include 'DB.php';

	//массивы для формирования таблиц в письме
	class Arrays {
		public $inn_array = array(); //с Payments у которых в БД нет номера договора
		public $id_inn_array = 0;
		public $unfound_num_arrey = array(); //с Payments у которых не прошел check
		public $id_unfound_num = 0;
		public $payment_error_number = array();
		public $id_payment_error_number = 0; //с Payments у которых не прошел payment
		public $payment_ok_num = array(); //с Payments которые прошел payment
		public $id_payment_ok_num = 0;
		
		public $banks_black_list = array("5261005926", "5254004350", "7707083893"); //черный список банков
		
	}

	class Payment { 
		public $contract_number = 0; //номер договора
		public $date; //дата
		public $number; //номер чека
		public $sum; //сумма
		public $acount; //ПлательщикСчет
		public $inn; //ПлательщикИнн
		public $kpp; //ПлательщикКПП
		public $payer1; //Плательщик1
		public $check_ac; //ПлательщикРасч.Счет
		public $bank1;	//ПлательщикБанк1
		public $bik; //ВИК
		public $cor_ac; //Корсчет
		public $payment_type; //Вид платежа
		public $purpose_of_payment; //назначение платежа
		public $name; //кому платят
	}
	$file_name = $argv[1]; //забираем имя файла из суперглобального массива "kl_to_1c.txt"

	if(file($file_name)) {
		$file_array = file($file_name);  //записываем данные из файла в массив 
	} else {
		exit();
	}

	$NArray = new Arrays();

	foreach ($file_array as $i=>$str)  { 
	
		$str = mb_convert_encoding($str, "UTF-8", "Windows-1251" ); //переводим $str из кодировки Windows-1251 в UTF-8
		
		if (strpos ($str, "ПолучательИНН=5262067308")!==false) { 
		
			//выбираем из массива нужные значения и переводим их в кодировку UTF-8	
			list(, $date) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-13], "UTF-8", "Windows-1251" ));
			list(, $number) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-14], "UTF-8", "Windows-1251" ));
			list(, $sum) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-12], "UTF-8", "Windows-1251" ));
			list(, $acount) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-11], "UTF-8", "Windows-1251" ));
			list(, $inn) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-9], "UTF-8", "Windows-1251" ));
			list(, $kpp) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-8], "UTF-8", "Windows-1251" ));
			list(, $payer1) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-10], "UTF-8", "Windows-1251" ));
			list(, $check_ac) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-7], "UTF-8", "Windows-1251" ));
			list(, $bank1) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-6], "UTF-8", "Windows-1251" ));
			list(, $bik) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-5], "UTF-8", "Windows-1251" ));
			list(, $cor_ac) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-4], "UTF-8", "Windows-1251" ));
			list(, $payment_type) = split('[=]', $nstr = mb_convert_encoding($file_array[$i+6], "UTF-8", "Windows-1251" ));
			list(, $purpose_of_payment) = split('[=]', $nstr = mb_convert_encoding($file_array[$i+18], "UTF-8", "Windows-1251" ));
			
			/* раскладка старого документа. поиск был по "Получатель=ИНН 5262067308"
			list(, $date) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-14], "UTF-8", "Windows-1251" ));
			list(, $number) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-13], "UTF-8", "Windows-1251" ));
			list(, $sum) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-12], "UTF-8", "Windows-1251" ));
			list(, $acount) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-11], "UTF-8", "Windows-1251" ));
			list(, $inn) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-8], "UTF-8", "Windows-1251" ));
			list(, $kpp) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-7], "UTF-8", "Windows-1251" ));
			list(, $payer1) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-6], "UTF-8", "Windows-1251" ));
			list(, $check_ac) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-5], "UTF-8", "Windows-1251" ));
			list(, $bank1) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-4], "UTF-8", "Windows-1251" ));
			list(, $bik) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-3], "UTF-8", "Windows-1251" ));
			list(, $cor_ac) = split('[=]', $nstr = mb_convert_encoding($file_array[$i-2], "UTF-8", "Windows-1251" ));
			list(, $payment_type) = split('[=]', $nstr = mb_convert_encoding($file_array[$i+9], "UTF-8", "Windows-1251" ));
			list(, $purpose_of_payment) = split('[=]', $nstr = mb_convert_encoding($file_array[$i+22], "UTF-8", "Windows-1251" ));
*/
			//создаем обьект класса Payment
			$MPayment = new Payment();
			$MPayment->date = cut($date);
			$MPayment->number = cut($number);
			$MPayment->sum = cut($sum);
			$MPayment->acount = cut($acount);
			$MPayment->inn = cut($inn);
			$MPayment->kpp = cut($kpp);
			$MPayment->payer1 = cut($payer1);
			$MPayment->check_ac = cut($check_ac); 
			$MPayment->bank1 = cut($bank1);
			$MPayment->bik = cut($bik);
			$MPayment->cor_ac = cut($cor_ac);
			$MPayment->payment_type = cut($payment_type);
			$MPayment->purpose_of_payment = cut($purpose_of_payment);
			
			if ($MPayment->sum > 99999) {
				continue;
			} else if(strpos($MPayment->purpose_of_payment, "ОПЛАТА.RU")!==false) {
		
				$NArray->inn_array[$NArray->id_inn_array] = $MPayment;
				$NArray->id_inn_array ++;
			
			} else if( in_array( $MPayment->inn, $NArray->banks_black_list) ) {
		
				$NArray->inn_array[$NArray->id_inn_array] = $MPayment;
				$NArray->id_inn_array ++;
			
			} else {
				contract_number_query($MPayment, $NArray);
			}
		}
		
	}
	
	include 'send_mail.php';
?>
