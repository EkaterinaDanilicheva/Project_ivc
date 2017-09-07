<?php

	function write_log_error($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$error_log_file = "/var/log/clbank_error.log"; ///var/log/
		$log_error = fopen($error_log_file, "a");
		fwrite($log_error, "[$now] [error] $str");
	}
	
	function write_log_succes($str) {
		$now = date("D M d H:i:s Y"); //дата для записи в логи
		$succes_log_file = "/var/log/clbank_succes.log"; ///var/log/
		$log_succes = fopen($succes_log_file, "a");
		fwrite($log_succes, "[$now] [succes] $str");
	}
	
	function cut($str) {
		if(!empty($str)) {
			if(ord($str[strlen($str)-1]) == "10" || ord($str[strlen($str)-1]) == "13l") {
				$str = substr($str, 0, -1);
				//cut($str);
				return cut($str);//$str;
			} else {
				return $str;
			}
		} else {
			return $str;
		}
	}
	
	
	function is_the_same($name, $payer1) {
		
		$replace = array('"', "ооо", "оао", "ип", "(", ")");
		$str1 = preg_split( "/[\s,-]+/", str_replace( $replace, "", mb_strtolower($name, 'UTF-8') ) ); //Разбиваем строку по регулярному выражению (убираем '"'( Преобразует строку в нижний регистр() ) )
		$str2 = preg_split( "/[\s,-]+/", str_replace( $replace, "", mb_strtolower($payer1, 'UTF-8') ) );
		$diff = array_intersect($str1, $str2); //смотрим, есть ли в массивах одинаковые элементы

		if ($diff) {
			return true;
		} else {
			return false;
		}
		
	}
	
	function contract_number_query($MPayment, $NArray) {
		$contract_number_query = mysql_query("SELECT ag.number, a.name FROM accounts=a, agreements=ag 
			WHERE a.inn=$MPayment->inn 
			AND ag.uid=a.uid 
			AND a.archive=0 
			AND ag.state =0;");

		if (!$contract_number_query) {
			$NArray->inn_array[$NArray->id_inn_array] = $MPayment;
			$NArray->id_inn_array ++;
			write_log_error(mysql_error() . "Contract_number_query: acount $MPayment->acount ; 
						  inn $MPayment->inn ; kpp $MPayment->kpp ; payer1 $MPayment->payer1 ; 
						  check_ac $MPayment->check_ac ; bank1 $MPayment->bank1 ; bik $MPayment->bik ; 
						  cor_ac $MPayment->cor_ac ; payment_type $MPayment->payment_type ; 
						  purpose_of_payment $MPayment->purpose_of_payment ; date $MPayment->date ; 
						  sum $MPayment->sum\n");
			return;
		}
		
		$contract_number_array = mysql_fetch_array($contract_number_query);

		if (!$contract_number_array) {
			$NArray->inn_array[$NArray->id_inn_array] = $MPayment;
			$NArray->id_inn_array ++;
			write_log_error("Contract_number_array: acount $MPayment->acount ; 
						  inn $MPayment->inn ; kpp $MPayment->kpp ; payer1 $MPayment->payer1 ; 
						  check_ac $MPayment->check_ac ; bank1 $MPayment->bank1 ; bik $MPayment->bik ; 
						  cor_ac $MPayment->cor_ac ; payment_type $MPayment->payment_type ; 
						  purpose_of_payment $MPayment->purpose_of_payment ; date $MPayment->date ; 
						  sum $MPayment->sum\n");
		} else {
			if (mysql_num_rows($contract_number_query)) {
				$MPayment->contract_number = $contract_number_array['number'];
				$MPayment->name = $contract_number_array['name'];
				if( !is_the_same($MPayment->name, $MPayment->payer1) ) {
					$NArray->unfound_num_arrey[$NArray->id_unfound_num] = $MPayment;
					$NArray->id_unfound_num ++;
					write_log_error("Name and payer1 is different : contract number $MPayment->contract_number ; acount $MPayment->acount ; 
							  inn $MPayment->inn ; kpp $MPayment->kpp ; payer1 $MPayment->payer1 ; name $MPayment->name ; 
							  check_ac $MPayment->check_ac ; bank1 $MPayment->bank1 ; bik $MPayment->bik ; 
							  cor_ac $MPayment->cor_ac ; payment_type $MPayment->payment_type ; 
							  purpose_of_payment $MPayment->purpose_of_payment ; date $MPayment->date ; 
							  sum $MPayment->sum\n"
					);
				} else {
				
					write_log_succes("Found  contract number in DB : contract number $MPayment->contract_number ; acount $MPayment->acount ; inn $MPayment->inn ; kpp $MPayment->kpp ; payer1 $MPayment->payer1 ; check_ac $MPayment->check_ac ; bank1 $MPayment->bank1 ; bik $MPayment->bik ; cor_ac $MPayment->cor_ac ; payment_type $MPayment->payment_type ; purpose_of_payment $MPayment->purpose_of_payment ; date $MPayment->date ; sum $MPayment->sum\n");
					clbank_action_check($MPayment, $NArray);
				}
			} else {
				$NArray->inn_array[$NArray->id_inn_array] = $MPayment;
				$NArray->id_inn_array ++;
				write_log_error("SQL query returned an empty string acount $MPayment->acount ; 
						  inn $MPayment->inn ; kpp $MPayment->kpp ; payer1 $MPayment->payer1 ; 
						  check_ac $MPayment->check_ac ; bank1 $MPayment->bank1 ; bik $MPayment->bik ; 
						  cor_ac $MPayment->cor_ac ; payment_type $MPayment->payment_type ; 
						  purpose_of_payment $MPayment->purpose_of_payment ; date $MPayment->date ; 
						  sum $MPayment->sum\n"
				);
			}
		}
	}
	
	function clbank_action_check($MPayment, $NArray) {
		$link="https://bil.ivc.nnov.ru/oplata/clbank.cgi?action=check&number=";
		$number = $MPayment->contract_number;
		$url = $link . $number; // клеим ссылку
		$curl = curl_init(); // Устанавливает параметр для сеанса CURL
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 'clbank PHP script',
			CURLOPT_SSL_VERIFYPEER => false
		));
		$resp = curl_exec($curl); // Выполняет запрос CURL
		if(!$resp){   //если ответ на запрос CURL пустой
			$NArray->unfound_num_arrey[$NArray->id_unfound_num] = $MPayment;
			$NArray->id_unfound_num ++;
			write_log_error("Cann't execute the CURL query in clbank_action_check.php\n");
			exit();
		}
		      
		curl_close($curl); // Close request to clear up some resources
		$responce = new SimpleXMLElement($resp);
			
		if ($responce->code!=0) {

			$NArray->unfound_num_arrey[$NArray->id_unfound_num] = $MPayment;
			$NArray->id_unfound_num ++;
			write_log_error(" Action check error acount $MPayment->acount ; inn $MPayment->inn ; 
					  kpp $MPayment->kpp ; payer1 $MPayment->payer1 ; check_ac $MPayment->check_ac ;
					  bank1 $MPayment->bank1 ; bik $MPayment->bik ; cor_ac $MPayment->cor_ac ; 
					  payment_type $MPayment->payment_type ; purpose_of_payment $MPayment->purpose_of_payment ;
					  date $MPayment->date ; sum $MPayment->sum\n"
			);
		} else {
			write_log_succes("Found  contract number in clbank.cgi?action=check: contract number $MPayment->contract_number ;
					  acount $MPayment->acount ; inn $MPayment->inn ; kpp $MPayment->kpp ; payer1 $MPayment->payer1 ;
					  check_ac $MPayment->check_ac ; bank1 $MPayment->bank1 ; bik $MPayment->bik ; cor_ac $MPayment->cor_ac ; 
					  payment_type $MPayment->payment_type ; purpose_of_payment $MPayment->purpose_of_payment ; date $MPayment->date ;
					  sum $MPayment->sum\n"
			);
			clbank_action_payment($MPayment, $NArray);
		}
	}
	
	function clbank_action_payment($MPayment, $NArray) {
		$link="https://bil.ivc.nnov.ru/oplata/clbank.cgi?action=payment&number=";
		$number = $MPayment -> contract_number;
		$amount = $MPayment -> sum;
		list($day , $month , $year) = split('[.]', $MPayment -> date); //делаем дату нужного формата
		$receipt = $MPayment->number . $day . $month . $year;
		$date = $year . "-" . $month . "-" . $day . "T12:00:00";
		$url = $link . $number . "&amount=" . $amount . "&receipt=" . $receipt . "&date=" . $date; // клеим ссылку, заглушка "94/07-C1" = $number 
	
		$curl = curl_init(); // Устанавливает параметр для сеанса CURL
		
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 'clbank PHP script',
			CURLOPT_SSL_VERIFYPEER => false
		));
		
		$resp = curl_exec($curl); // Выполняет запрос CURL

		if(!$resp){  //если ответ на запрос CURL пустой
			$NArray->payment_error_number[$NArray->id_payment_error_number] = $MPayment;  
			$NArray->id_payment_error_number ++;
			write_log_error("Cann't execute the CURL query in clbank_action_check.php\n");
			write_log_error("CURL response is empty : $url\n");
			exit();
		}
		curl_close($curl);  // Close request to clear up some resources
		$responce = new SimpleXMLElement($resp);
		
		if ($responce->code!=0) {
			$NArray->payment_error_number[$NArray->id_payment_error_number] = $MPayment;  
			$NArray->id_payment_error_number ++;
			write_log_error("Payment error number : $number \n");
			write_log_error("Link with payment error number : $url \n");
			write_log_error("CURL response : " . $responce -> asXML() );
		} else {
			if ($responce->date != $date && $responce->sum != $MPayment->sum) {

				$NArray->payment_error_number[$NArray->id_payment_error_number] = $MPayment;  
				$NArray->id_payment_error_number ++;				
				write_log_error("date and sum does not equal with Payment, error number : $number \n");

			} else {

				$NArray->payment_ok_num[$NArray->id_payment_ok_num] = $MPayment;
				$NArray->id_payment_ok_num ++;
				write_log_succes("Payment was with: contract number $MPayment->contract_number ; acount $MPayment->acount ;
					  inn $MPayment->inn ; kpp $MPayment->kpp ; payer1 $MPayment->payer1 ; check_ac $MPayment->check_ac ; 
					  bank1 $MPayment->bank1 ; bik $MPayment->bik ; cor_ac $MPayment->cor_ac ; payment_type $MPayment->payment_type ; 
					  purpose_of_payment $MPayment->purpose_of_payment ; date $MPayment->date ; sum $MPayment->sum\n"

				);
			}
			write_log_succes("CURL link : $url \n");
			write_log_succes("CURL response : " . $responce -> asXML() );

		}
	}
?>
