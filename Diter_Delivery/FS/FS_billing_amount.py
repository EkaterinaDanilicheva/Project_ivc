# -*- coding: utf-8

import MySQLdb
import string
import mysql.connector
from mysql.connector import errorcode

import logging
logging.basicConfig(filename='FS_billing_amount.log', format='%(asctime)s %(message)s',level=logging.INFO)

# billing19_002 подключаемся к базе данных (не забываем указать кодировку, а то в базу запишутся иероглифы)
config = {
  'user': 'tariff',
  'password': 'TrubKakuRa',
  'host': '81.19.128.73',
  'database': 'billing19_002',
  'raise_on_warnings': True,
}
try:
  billing_db = mysql.connector.connect(**config)
except mysql.connector.Error as err:
    if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
        logging.critical("Something is wrong with your user name or password.")
    elif err.errno == errorcode.ER_BAD_DB_ERROR:
        logging.critical("Database does not exist.")
    else:
        logging.critical(err)
    exit()
logging.info("billing MySQL connected.")
# формируем курсор, с помощью которого можно исполнять SQL-запросы
billing_cursor = billing_db.cursor()

# freeswitch подключаемся к базе данных (не забываем указать кодировку, а то в базу запишутся иероглифы)
config = {
  'user': 'portuser',
  'password': 'TrubKakuRa',
  'host': '81.19.142.2',
  'database': 'freeswitch',
  'raise_on_warnings': True,
}
try:
  freeswitch_db = mysql.connector.connect(**config)
except mysql.connector.Error as err:
    if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
        logging.critical("freeswitch_db:Something is wrong with your user name or password.")
    elif err.errno == errorcode.ER_BAD_DB_ERROR:
        logging.critical("freeswitch_db: Database does not exist.")
    else:
        logging.critical(err)
    exit()
logging.info("freeswitch MySQL connected.")
freeswitch_cursor = freeswitch_db.cursor()


#IS NULL
freeswitch_sql = "SELECT start_stamp, uuid FROM `cdr` WHERE `cdr`.`billing_amount` = 0 AND `cdr`.`billsec`>0 AND `cdr`.`billing_number` LIKE '7__________'"
# исполняем SQL-запрос
freeswitch_cursor.execute(freeswitch_sql)
# получаем результат выполнения запроса
freeswitch_uuid_arr = freeswitch_cursor.fetchall()
# перебираем записи
for freeswitch_uuid in freeswitch_uuid_arr:

    # извлекаем данные из записей - в том же порядке, как и в SQL-запросе
    start_stamp, uuid = freeswitch_uuid
    fs_tel_table = "tel029" + start_stamp.strftime("%Y%m%d")
    billing_sql = "SELECT amount FROM `"+ fs_tel_table +"` WHERE timefrom = '"+ str(start_stamp) +"' AND session_id = '"+ uuid +"'"
    # исполняем SQL-запрос
    billing_cursor.execute(billing_sql)
    # получаем результат выполнения запроса
    amount = billing_cursor.fetchall()
    billing_amount = amount[0][0]
    # update cdr на freeswitch добавляем billing_amount
    update_sql = "UPDATE `cdr` SET `billing_amount` = '"+ str(billing_amount) +"' WHERE `start_stamp` = '"+ str(start_stamp) +"' AND `uuid` = '"+ uuid +"'"
    # исполняем SQL-запрос
    freeswitch_cursor.execute(update_sql)
    freeswitch_db.commit()
    print (update_sql)

# закрываем соединение с базой данных
freeswitch_db.close()
billing_db.close()
