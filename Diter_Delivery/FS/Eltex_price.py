#!usr/bin/python
# -*- coding: utf-8 -*-

import MySQLdb
import string
import mysql.connector
from mysql.connector import errorcode
from datetime import datetime
import sys

import logging
logging.basicConfig(filename='Eltex_price.log', format='%(asctime)s %(message)s',level=logging.INFO)

callee_names = {u'SIP_Ростелеком':'rt_price', u'SIP_Билайн':'beeline_price'}
# подключаемся к базе данных (не забываем указать кодировку, а то в базу запишутся иероглифы)
config = {
  'user': 'ivc',
  'password': 'TrubKakuRa',
  'host': '81.19.128.73',
  'database': 'ivc_noc',
  'raise_on_warnings': True,
}
try:
  eltex_db = mysql.connector.connect(**config)
except mysql.connector.Error as err:
    if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
        logging.critical("Something is wrong with your user name or password.")
    elif err.errno == errorcode.ER_BAD_DB_ERROR:
        logging.critical("Database does not exist.")
    else:
        logging.critical(err)
    exit()
logging.info("eltex MySQL connected.")
# формируем курсор, с помощью которого можно исполнять SQL-запросы
eltex_cursor = eltex_db.cursor()

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
logging.info("billing MySQL connected.")
# формируем курсор, с помощью которого можно исполнять SQL-запросы
billing_cursor = billing_db.cursor()


#update всех операторов, которых не знаем (потом надо убрать этот блок)
eltex_sql = "UPDATE `cdr_eltex` SET `price` = '-1', `sum_cost` = '-1' WHERE `cdr_eltex`.`price` =0 AND `cdr_eltex`.`sum_cost` =0 AND `cdr_eltex`.`call_duration`>0 AND `callee_name` NOT IN ("
for operator in callee_names:
    eltex_sql += "'" + operator + "', "
eltex_sql += ")"
eltex_sql = eltex_sql.replace(', )', ')')
eltex_cursor.execute( eltex_sql )
eltex_db.commit()
###########

if len(sys.argv)==2 :
    yesterday_date = sys.argv[1]
else :
    yesterday_date = datetime.today().strftime("%Y-%m-") + str(datetime.today().day - 1)
#AND callee_name = 'SIP_Ростелеком'
eltex_sql = "SELECT start, call_duration, callee_name, callee_number_exit_SMG, callers_number_exit_SMG FROM `cdr_eltex` WHERE start LIKE '"+yesterday_date+"%' AND `cdr_eltex`.`price` =0 AND `cdr_eltex`.`sum_cost` =0 AND `cdr_eltex`.`call_duration`>0"
# исполняем SQL-запрос
eltex_cursor.execute(eltex_sql)
# получаем результат выполнения запроса
eltex_cdr = eltex_cursor.fetchall()
# перебираем записи
for cdr_str in eltex_cdr:

    # извлекаем данные из записей - в том же порядке, как и в SQL-запросе
    start, call_duration, callee_name, callee_number_exit_SMG, callers_number_exit_SMG = cdr_str
    #Узнаем amount
    fs_tel_table = "tel001" + start.strftime("%Y%m%d")
    billing_sql = "SELECT amount FROM `"+ fs_tel_table +"` WHERE timefrom = '"+ str(start) +"' AND numfrom = '"+ callers_number_exit_SMG +"' AND numto = '"+ callee_number_exit_SMG +"'"
    # исполняем SQL-запрос
    billing_cursor.execute(billing_sql)
    # получаем результат выполнения запроса
    amount = billing_cursor.fetchall()
    if len(amount)>0:
        billing_amount = amount[0][0]
    else :
        billing_sql = "SELECT amount FROM `"+ fs_tel_table +"` WHERE timefrom LIKE '"+ start.strftime("%Y-%m-%d %H:%M") +"%' AND numfrom = '"+ callers_number_exit_SMG +"' AND numto = '"+ callee_number_exit_SMG +"' AND duration = "+ str(call_duration)
        # исполняем SQL-запрос
        billing_cursor.execute(billing_sql)
        # получаем результат выполнения запроса
        amount = billing_cursor.fetchall()
        if len(amount) == 1:
            billing_amount = amount[0][0]
        else :
            billing_sql = "SELECT amount FROM `"+ fs_tel_table +"` WHERE timefrom LIKE '"+ start.strftime("%Y-%m-%d %H:%M") +"%' AND numfrom = '"+ callers_number_exit_SMG +"' AND numto = '"+ callee_number_exit_SMG +"'"
            # исполняем SQL-запрос
            billing_cursor.execute(billing_sql)
            # получаем результат выполнения запроса
            amount = billing_cursor.fetchall()
            if len(amount) == 1:
                billing_amount = amount[0][0]
            else :
                logging.critical( "Query return "+ str(len(amount)) +" str "+ billing_sql )
                billing_amount =0

    #если знаем такого оператора
    if callee_names.keys().count(callee_name) >0:
        freeswitch_table = callee_names[callee_name]
        if len(callee_number_exit_SMG)==7 and ['2', '4'].index(callee_number_exit_SMG[0]):
            pref = '831'
            number = callee_number_exit_SMG
        else:
            pref = callee_number_exit_SMG[1:4]
            number = callee_number_exit_SMG[4:]
        price_query_str = "SELECT price FROM "+freeswitch_table+" WHERE abc="+pref+" AND from_n<="+number+" AND to_n>="+number+" ORDER BY `"+freeswitch_table+"`.`from_n` DESC LIMIT 1"
        freeswitch_cursor.execute(price_query_str)
        price_arr = freeswitch_cursor.fetchall()
        if len(price_arr) > 0 : #нашли цену
            price = price_arr[0][0]  #цена за секунду
            sum_cost = (price/60)*call_duration*1.18 #цена с НДС
            update_eltex_str = "UPDATE `cdr_eltex` SET `price` = "+ str(price) +", `sum_cost` = " + str(sum_cost) +", `billing_amount` = " + str(billing_amount) +" WHERE `start` = '"+ str(start) +"' AND `callee_number_exit_SMG` = "+ callee_number_exit_SMG +" AND `callee_name` = '"+ callee_name +"'"
            #update_eltex_str = "UPDATE `cdr_eltex` SET `price` = 0.008, `sum_cost` = 0.08496 WHERE `start` = '2017-03-31 10:58:46' AND `callee_number_exit_SMG` = 74996483143 AND `callee_name` = 'SIP_Ростелеком'"
            # исполняем SQL-запрос
            eltex_cursor.execute(update_eltex_str)
            eltex_db.commit()
            #print update_eltex_str
        elif len(price_arr) == 0: #запрос на цену возвращает 0 строк
            price_query_str = "SELECT price FROM "+freeswitch_table+" WHERE abc=7 AND from_n<="+number+" AND to_n>="+number+" ORDER BY `"+freeswitch_table+"`.`from_n` DESC LIMIT 1"
            freeswitch_cursor.execute(price_query_str)
            price_arr = freeswitch_cursor.fetchall()
            if len(price_arr) > 0 : #нашли цену
                price = price_arr[0][0]  #цена за секунду
                sum_cost = (price/60)*call_duration*1.18 #цена с НДС
                update_eltex_str = "UPDATE `cdr_eltex` SET `price` = "+ str(price) +", `sum_cost` = " + str(sum_cost) +", `billing_amount` = " + str(billing_amount) +" WHERE `start` = '"+ str(start) +"' AND `callee_number_exit_SMG` = "+ callee_number_exit_SMG +" AND `callee_name` = '"+ callee_name +"'"
                # исполняем SQL-запрос
                eltex_cursor.execute(update_eltex_str)
                eltex_db.commit()
                #print '=)))'
            else:
                logging.critical("Empty answer "+ price_query_str)
                update_eltex_str = "UPDATE `cdr_eltex` SET `price` = '-1', `sum_cost` = '-1', `billing_amount` = " + str(billing_amount) +" WHERE `start` = '"+ str(start) +"' AND `callee_number_exit_SMG` = "+ callee_number_exit_SMG +" AND `callee_name` = '"+ callee_name +"'"
                # исполняем SQL-запрос
                eltex_cursor.execute(update_eltex_str)
                eltex_db.commit()
        else :
            logging.critical("Empty answer "+ price_query_str)
            update_eltex_str = "UPDATE `cdr_eltex` SET `price` = '-1', `sum_cost` = '-1', `billing_amount` = " + str(billing_amount) +" WHERE `start` = '"+ str(start) +"' AND `callee_number_exit_SMG` = "+ callee_number_exit_SMG +" AND `callee_name` = '"+ callee_name +"'"
            # исполняем SQL-запрос
            eltex_cursor.execute(update_eltex_str)
            eltex_db.commit()
    else:
        logging.critical("Unknown operator "+ callee_name)
        update_eltex_str = "UPDATE `cdr_eltex` SET `price` = '-1', `sum_cost` = '-1', `billing_amount` = " + str(billing_amount) +" WHERE `start` = '"+ str(start) +"' AND `callee_number_exit_SMG` = "+ callee_number_exit_SMG +" AND `callee_name` = '"+ callee_name +"'"
        # исполняем SQL-запрос
        eltex_cursor.execute(update_eltex_str)
        eltex_db.commit()

# закрываем соединение с базой данных
freeswitch_db.close()
eltex_db.close()
billing_db.close()
