<?php
  $host = "81.19.128.73"; //данные о БД
  $user = "tariff";
  $password = "TrubKakuRa";
  $db = "billing19_002";
  if (!mysql_connect($host, $user, $password)) //проверяет соединение с SQL сервером
  {
    echo "<h2>MySQL Error!</h2>";
    exit;
  }
 mysql_select_db($db);//выбирает для работы указанную базу данных на сервере, на который ссылается переданный указатель
?>
