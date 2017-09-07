<?php
 if ($detalization) {
         $duration_file = "/home/danilicheva/Projects/detal_file_".$k."_".$Year.".csv";
	 $str_pay_csv = "";
	 foreach($ArrayUserInfo as $UI) {
	      $str_pay_csv = $UI->telA.";".$UI->telB.";".$UI->duration.";".$UI->timeFrom.";".$UI->descr;
	      file_put_contents($duration_file,$str_pay_csv,FILE_APPEND | LOCK_EX); //Функция идентична последовательному вызову функций fopen(), fwrite() и fclose(). Возвращаемым функцией значением является количество записанных в файл байтов. FILE_APPEND Если файл filename уже существует, данные будут дописаны в конец файла вместо того, чтобы его перезаписать.LOCK_EX Получить эксклюзивную блокировку на файл на время записи. 
	      file_put_contents($duration_file,"\n",FILE_APPEND | LOCK_EX); //тут мы записываем отступ на новую строку?
	 }
  }
?>
