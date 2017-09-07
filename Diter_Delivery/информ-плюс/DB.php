<?php
/*Подклюсаемся к БД*/
	$host = "81.19.128.73"; //данные о БД
	$user = "tariff";
	$password = "TrubKakuRa";
	$db = "billing19_002";
	if (!mysql_connect($host, $user, $password)) { //проверяет соединение с SQL сервером
		echo "MySQL Error!";
		exit;
	}
	mysql_select_db($db);//выбирает для работы указанную базу данных на сервере, на который ссылается переданный указатель
?>
