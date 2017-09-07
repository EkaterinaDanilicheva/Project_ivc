#!usr/bin/python
# -*- coding: utf-8

import MySQLdb
import string
import mysql.connector
from mysql.connector import errorcode
from datetime import datetime
import sys

import logging
logging.basicConfig(filename='FS_price.log', format='%(asctime)s %(message)s',level=logging.INFO)

callee_names = {u'RT':'rt_price', u'Beeline':'beeline_price', u'beeline':'beeline_price'}
# подключаемся к базе данных (не забываем указать кодировку, а то в базу запишутся иероглифы)

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
logging.info("billing19_002 MySQL connected.")
# формируем курсор, с помощью которого можно исполнять SQL-запросы
billing_cursor = billing_db.cursor()

#update всех операторов, которых не знаем (потом надо убрать этот блок)
update_freeswitch_sql = "UPDATE `cdr` SET `price` = '-1', `sum_cost` = '-1' WHERE `cdr`.`price` IS NULL AND `cdr`.`sum_cost` IS NULL AND `cdr`.`billsec`>0 AND `cdr`.`billing_number` LIKE '7__________' AND `gateway_name` NOT IN ("
for operator in callee_names:
    update_freeswitch_sql += "'" + operator + "', "
update_freeswitch_sql += ")"
update_freeswitch_sql = update_freeswitch_sql.replace(', )', ')')
freeswitch_cursor.execute( update_freeswitch_sql )
freeswitch_db.commit()
###########

if len(sys.argv)==2 :
    yesterday_date = sys.argv[1]
else :
    yesterday_date = datetime.today().strftime("%Y-%m-") + str(datetime.today().day - 1)

freeswitch_sql = "SELECT start_stamp, gateway_name, billing_number, billsec, uuid  FROM `cdr` WHERE start_stamp LIKE '"+ yesterday_date +"%' AND `cdr`.`price` IS NULL AND `cdr`.`sum_cost` IS NULL AND `cdr`.`billsec`>0 AND `cdr`.`billing_number` LIKE '7__________' "
# исполняем SQL-запрос
freeswitch_cursor.execute(freeswitch_sql)
# получаем результат выполнения запроса
freeswitch_cdr = freeswitch_cursor.fetchall()
# перебираем записи
for cdr_str in freeswitch_cdr:

    # извлекаем данные из записей - в том же порядке, как и в SQL-запросе
    start_stamp, gateway_name, billing_number, billsec, uuid = cdr_str

    #Находим amount
    fs_tel_table = "tel029" + start_stamp.strftime("%Y%m%d")
    billing_sql = "SELECT amount FROM `"+ fs_tel_table +"` WHERE timefrom = '"+ str(start_stamp) +"' AND session_id = '"+ uuid +"'"
    # исполняем SQL-запрос
    billing_cursor.execute(billing_sql)
    # получаем результат выполнения запроса
    amount = billing_cursor.fetchall()
    if len(amount)>0:
        billing_amount = amount[0][0]
        #print billing_amount
    else :
        billing_amount = -1
        logging.critical("Query return "+ str(len(amount)) +" str "+ billing_sql)

    #если знаем такого оператора
    if callee_names.keys().count(gateway_name) >0:
        freeswitch_table = callee_names[gateway_name]
        pref = billing_number[1:4]
        number = billing_number[4:]
        price_query_str = "SELECT price FROM "+freeswitch_table+" WHERE abc="+pref+" AND from_n<="+number+" AND to_n>="+number+" ORDER BY `"+freeswitch_table+"`.`from_n` DESC LIMIT 1"
        freeswitch_cursor.execute(price_query_str)
        price_arr = freeswitch_cursor.fetchall()
        if len(price_arr) > 0 : #нашли цену
            price = price_arr[0][0]  #цена за секунду
            sum_cost = (price/60)*billsec*1.18 #цена с НДС
            update_freeswitch_str = "UPDATE `cdr` SET `price` = "+ str(price) +", `sum_cost` = " + str(sum_cost) +", `billing_amount` = '"+ str(billing_amount) +"' WHERE `start_stamp` = '"+ str(start_stamp) +"' AND `billing_number` = "+ billing_number +" AND `gateway_name` = '"+ gateway_name +"'"
            # исполняем SQL-запрос
            #print update_freeswitch_str
            freeswitch_cursor.execute(update_freeswitch_str)
            freeswitch_db.commit()
        elif len(price_arr) == 0: #запрос на цену возвращает 0 строк
            price_query_str = "SELECT price FROM "+freeswitch_table+" WHERE abc=7 AND from_n<="+number+" AND to_n>="+number+" ORDER BY `"+freeswitch_table+"`.`from_n` DESC LIMIT 1"
            freeswitch_cursor.execute(price_query_str)
            price_arr = freeswitch_cursor.fetchall()
            if len(price_arr) > 0 : #нашли цену
                price = price_arr[0][0]  #цена за секунду
                sum_cost = (price/60)*billsec*1.18 #цена с НДС
                update_freeswitch_str = "UPDATE `cdr` SET `price` = "+ str(price) +", `sum_cost` = " + str(sum_cost) +", `billing_amount` = '"+ str(billing_amount) +"' WHERE `start_stamp` = '"+ str(start_stamp) +"' AND `billing_number` = "+ billing_number +" AND `gateway_name` = '"+ gateway_name +"'"
                # исполняем SQL-запрос
                #print update_freeswitch_str
                freeswitch_cursor.execute(update_freeswitch_str)
                freeswitch_db.commit()
            else:
                logging.critical("Empty answer "+ price_query_str)
                update_freeswitch_str = "UPDATE `cdr` SET `price` = '-1', `sum_cost` = '-1', `billing_amount` = '"+ str(billing_amount) +"' WHERE `start_stamp` = '"+ str(start_stamp) +"' AND `billing_number` = "+ billing_number +" AND `gateway_name` = '"+ gateway_name +"'"
                # исполняем SQL-запрос
                #print update_freeswitch_str
                freeswitch_cursor.execute(update_freeswitch_str)
                freeswitch_db.commit()
        else :
            logging.critical("Empty answer "+ price_query_str)
            update_freeswitch_str = "UPDATE `cdr` SET `price` = '-1', `sum_cost` = '-1', `billing_amount` = '"+ str(billing_amount) +"' WHERE `start_stamp` = '"+ str(start_stamp) +"' AND `billing_number` = "+ billing_number +" AND `gateway_name` = '"+ gateway_name +"'"
            # исполняем SQL-запрос
            #print update_freeswitch_str
            freeswitch_cursor.execute(update_freeswitch_str)
            freeswitch_db.commit()
    else:
        logging.critical("Unknown operator "+ callee_name)
        update_freeswitch_str = "UPDATE `cdr` SET `price` = '-1', `sum_cost` = '-1' WHERE `start_stamp` = '"+ str(start_stamp) +"' AND `billing_number` = "+ billing_number +" AND `gateway_name` = '"+ gateway_name +"'"
        # исполняем SQL-запрос
        #print update_freeswitch_str
        freeswitch_cursor.execute(update_freeswitch_str)
        freeswitch_db.commit()

# закрываем соединение с базой данных
freeswitch_db.close()
billing_db.close()
