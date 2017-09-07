#!/usr/bin/perl

use lib '/usr/local/billing/payments_new';
use strict;
use warnings;
use CGI;
use LB;
use POSIX;
use utf8;
use Data::Dumper;
use DBI;	###!!!!


# Атрибуты для доступа к LBcore
my $proto           = 'http';
my $lbcore_host     = '127.0.0.1';
my $manager_login   = 'oplata';
my $manager_pass    = '7318uSpX';
my $type = TYPE_AGRM_NUM;               # Тип идентификатора !!!!!
#########################################
my $host = 'localhost'; 
my $user = 'tariff';
my $password = 'TrubKakuRa';
my $db = 'billing19_002';
#########################################!!!!!!!

my $log_file        = "/tmp/sber-acq.log";

# Подготавливаем данные для логирования
#my $action = $1 if $ENV{'PATH_INFO'} =~ m/\/(\w*)/;
my $tm = strftime( '%Y.%m.%d %T', localtime );
open( LOG, ">>$log_file" );
print LOG "\n############## BEGIN Start callback ###############\n";
#print LOG "=> $tm, $action, <". CGI::query_string()  .">\n";
print LOG "=> $tm, <". CGI::query_string()  .">\n";

# Входные параметры скрипта
# {merchant-url}?mdOrder={mdOrder}&orderNumber={orderNumber}&operation={operation}&status={status}
my $receipt         = CGI::param('mdOrder') || '';	# Номер заказа в платежной системе. Уникален в пределах системы.
my $pre_pay_id      = CGI::param('orderNumber') || '';  # Номер (идентификатор) заказа в системе магазина, уникален для каждого магазина в пределах системы (pre_payments.record_id)
my $operation       = CGI::param('operation') || '';    # Тип операции:
															# approved - операция холдирования суммы;
															# deposited - операция завершения;
															# reversed - операция отмены;
															# refunded - операция возврата;
															# declined - неуспешная операция (20 минут или 3 попытки)
my $status          = CGI::param('status');           	# Индикатор успешности операции, указанной в параметре operation 
															# 1 - операция прошла успешно, 
															# 0 - операция завершилась ошибкой

my $response_status = 200;

my $lb = new LB(proto=>$proto, host=>$lbcore_host, user=>$manager_login, pass=>$manager_pass);
my $dbh = DBI->connect("DBI:mysql:$db:$host", $user, $password); #!!!!!!!!	Подключаемся к billing19_002

if (!$lb->connect()) {   
	print LOG now()." [ERROR] Could not connect to LBcore $manager_login\:$manager_pass\@$lbcore_host\n";
	$response_status = 503;
}
# Success
elsif ($status eq 1) {
	if ($operation eq 'approved') {
		print LOG now()." [INFO] Approved operation. Nothing to do. Waiting for deposite operation.\n";
	}
	elsif ($operation eq 'deposited') {
		my $ret = $lb->getprepayment('record_id'=>$pre_pay_id);
		if (!defined($ret)) {    	
			print LOG now()." [ERROR] prepayment not found\n";
			$response_status = 404;    
		}
		else {
			####################!!!!!	Забираем из pre_payments значение amount (сумма) и number (номер договора)
			my $sth = $dbh->prepare("SELECT  `pre_payments`.`amount` ,  `agreements`.`number` 
					  FROM  `pre_payments` ,  `agreements` 
					  WHERE  `pre_payments`.`record_id` =  '$pre_pay_id'
					  AND  `pre_payments`.`agrm_id` =  `agreements`.`agrm_id` ");
			my $rv = $sth->execute;
			my @row = $sth->fetchrow_array;
			my $rc = $sth->finish;
			my $amount = $row[0];
			my $number = $row[1];

			####################!!!!!

			my $r_date = time;
			my ( $ret, $pay_id ) = $lb->payment (
				  'number'    => $number, #номер договора
				  'type'      => $type,
				  'amount'    => $amount,	#сумма
				  'receipt'   => $receipt,
				  'date'      => $r_date,
				  'additional'=> 'Сбербанк acquiring', #комментарий
				);

			# Логируем запрос к коре и возвращаемый ею результат
			print LOG "################## LBCore ##################\n"; 
			print LOG "# LB->prepayment\n";
			print LOG "recordid = $pre_pay_id\n";
			print LOG "receipt = $receipt\n";
			print LOG "pay_date = $r_date\n";
			print LOG "# LB->return\n";
			print LOG "code = $ret\n";
			print LOG "payment_id = $pay_id\n";
			print LOG "################## LBCore ##################\n";

			# Платеж успешно прошел
			if ($ret == 0) {
				##########!!!!!!!! 	Меняем статус перевода в таблице pre_payments
				my $sth = $dbh->prepare("SELECT record_id FROM  `payments` WHERE receipt = '$receipt' ");
				my $rv = $sth->execute;
				my @row = $sth->fetchrow_array;
				my $rc = $sth->finish;
				my $payment_id = $row[0];
			
				my $sth = $dbh->prepare("UPDATE `pre_payments` SET status = 1, manager_id = 10, payment_id = $payment_id WHERE `pre_payments`.`record_id` = '$pre_pay_id' ");
				
				if (!defined($sth->execute) ) {
					print LOG now()." [ERROR] Cannot update table pre_payments \n";
				} 
				else {
					print LOG now()." [OK] Payment OK \n";
					$response_status = 200;
				}
				my $rc = $sth->finish; 
				##########!!!!!!!!
			}
			# Аккаунт не найден
			elsif ($ret == 2) {
				print LOG now()." [ERROR] Account not found\n";
				$response_status = 404;
			}
			# Платеж уже проведен
			elsif ($ret == 12) {
				print LOG now()." [ERROR] Payment already exists\n";
				$response_status = 200;
			}
			elsif ($ret == 7) {
				print LOG now()." [ERROR] Prepayment status = $pay_id\n";
				$response_status = 200;
			}
			# Внутренняя ошибка LB
			else {
				print LOG now()." [ERROR] Internal server error \n";
				$response_status = 500;
			}
		}
	}
	#TODO такие запросы возможны?
	elsif ($operation eq 'reversed' or $operation eq 'refunded') {
	
		my $ret = $lb->getprepayment('record_id'=>$pre_pay_id);
		
		if (!defined($ret)) {
			print LOG now()." [ERROR] prepayment not found\n";
			$response_status = 404;    
		}
		else {
			my $pay_id = $ret->result->{'paymentid'};
			my $receipt = $ret->result->{'receipt'};
			
			if (defined($pay_id) and $pay_id ne 0 and defined($receipt) and $receipt ne 0) {
				my $ret = lb->cancel('receipt' => $receipt);
				# Платеж отменен успешно
				if ($ret == 0) {
				    print LOG now()." [OK] Cancal payment OK \n";
				    $response_status = 200;
				}
				# Платеж не найден
				elsif ($ret == 6) {
				    print LOG now()." [ERROR] Payment not found\n";
				    $response_status = 404;
				}
				# Платеж уже отменен
				elsif ($ret == 7) {
				    print LOG now()." [ERROR] Payment already cancelled\n";
				    $response_status = 200;
				}
				# Платеж не может быть отменен
				elsif ($ret == 9) {
				    print LOG now()." [ERROR] Prepayment cannot be cancelled\n";
				    $response_status = 500;
				}
				# Внутренняя ошибка LB
				else {
				    print LOG now()." [ERROR] Internal server error \n";
				    $response_status = 500;
				}
			}
			else {
				my $r_date = time;
# 				my $ret = $lb->cancel_prepayment('record_id'=>$pre_pay_id, 'cancel_date' => $r_date);
				
				##########!!!!!!!!	Меняем статус перевода в таблице pre_payments
				my $sth = $dbh->prepare("SELECT record_id FROM  `payments` WHERE receipt = '$receipt' ");
				my $rv = $sth->execute;
				my @row = $sth->fetchrow_array;
				my $rc = $sth->finish;
				my $payment_id = $row[0];
				
				my $sth = $dbh->prepare("UPDATE `pre_payments` SET , manager_id = 10, payment_id = $payment_id cancel_date = '". strftime("%Y-%m-%d %H:%M:%S", localtime($r_date)) ."', status = 2 WHERE `pre_payments`.`record_id` = '$pre_pay_id' ");
				
				if (!defined($sth->execute) ) {
					$ret = 0;
				} 
				else {
					$ret = 1;
				}
				my $rc = $sth->finish; 
				##########!!!!!!!!

				# Логируем запрос к коре и возвращаемый ею результат
				print LOG "################## LBCore ##################\n"; 
				print LOG "# LB->cancel_prepayment\n";
				print LOG "recordid = $pre_pay_id\n";
				print LOG "cancel_date = $r_date\n";
				print LOG "# LB->return\n";
				print LOG "code = $ret\n";
				print LOG "################## LBCore ##################\n";
				    
				if (!defined($ret) or $ret eq "0") {
				    print LOG now()." [ERROR] Internal server error or cannot to cancel prepayment\n";
				    $response_status = 500;
				}
				else {
				    print LOG now()." [OK] Prepayment $pre_pay_id has been cancelled\n";
				    $response_status = 200;
				}
			}
		}
	}
	else {
		print LOG now()." [ERROR] Undefined operation\n";
		$response_status = 405;
	}
}
elsif ($status eq 0) {

	if ($operation eq 'declined') {
		print LOG now()." [INFO] Operation declined. Prepayment = $pre_pay_id\n";
		my $r_date = time;
		my $ret = 0;
# 		my $ret = $lb->cancel_prepayment('record_id'=>$pre_pay_id, 'cancel_date' => $r_date);
		
		##########!!!!!!!!	Меняем статус перевода в таблице pre_payments
		my $sth = $dbh->prepare("UPDATE `pre_payments` SET cancel_date = '". strftime("%Y-%m-%d %H:%M:%S", localtime($r_date)) ."', status = 2 WHERE `pre_payments`.`record_id` = '$pre_pay_id' ");
		
		if (!defined($sth->execute) ) {
			$ret = 0;
		} 
		else {
			$ret = 1;
		}
		my $rc = $sth->finish; 
		##########!!!!!!!!
        
		# Логируем запрос к коре и возвращаемый ею результат
		print LOG "################## LBCore ##################\n"; 
		print LOG "# LB->cancel_prepayment\n";
		print LOG "recordid = $pre_pay_id\n";
		print LOG "cancel_date = $r_date\n";
		print LOG "# LB->return\n";
		print LOG "code = $ret\n";
		print LOG "################## LBCore ##################\n";
			        
		if (!defined($ret) or $ret eq "0") {
			print LOG now()." [ERROR] Internal server error or cannot to cancel prepayment\n";
			$response_status = 500;
		}
		else {
			print LOG now()." [OK] Prepayment $pre_pay_id has been cancelled\n";
			$response_status = 200;
		}
	}
	else {
		print LOG now()." [WARNING] Operation fault. Prepayment = $pre_pay_id\n";
	}	
}
else {
	print LOG now()." [ERROR] Undefined status\n";
	$response_status = 405;
}


my $response = CGI::header(-status=>$response_status);

print LOG "\nResponse:\n";
print LOG $response;
print LOG "\n##################### END ##################\n";
close LOG;

print $response;

sub now {
	return strftime('%Y.%m.%d %T', localtime);
}

exit;

