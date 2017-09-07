#!/usr/bin/perl
###############################################################################
#                                                                             #
# Данный скрипт - пример сервера приёма платежей для взаимодействия с СБ РФ   #
#                                                                             #
###############################################################################

use lib '/usr/local/billing/payments';
use strict;
use CGI;
use LB;
use POSIX;
use DBI; #!!!!!!

############## ! fix me ! ###############
my $lbcore_host = '127.0.0.1';          # IP сервера LBcore
my $manager_login = 'oplata';             # логин менеджера АСР
my $manager_pass = '7318uSpX';          # пароль менеджера АСР
my $type = TYPE_AGRM_NUM;               # Тип идентификатора
#########################################
my $host = 'localhost'; 
my $user = 'tariff';
my $password = 'TrubKakuRa';
my $db = 'billing19_002';
#########################################!!!!!!!
# Список примерных расшифровок ответов для некоторых кодов возврата
my %result_messages = (
#   -3  => 'Внутренняя ошибка провайдера услуг',
#   -2  => 'Неверное значение типа платежа (type)',
#   -1  => 'Неверный формат дополнительного параметра',
#   0   => 'OK',
#   1   => 'Неизвестный тип запроса',
#   2   => 'Абонент не найден',
#   3   => 'Неверная сумма платежа',
#   4   => 'Неверное значение номера платежа',
#   5   => 'Неверное значение даты',
#   6   => 'Успешный платеж с таким номером не найден',
#   7   => 'Платеж с таким номером отменен',
#   8   => 'Состояние платежа неопределенно',
#   9   => 'Платеж не может быть отменен',
# 
#   11  => 'Временная ошибка. Повторите запрос позже.',
#   100 => 'Неизвестная ошибка'
# );

  -1  => 'Внутренняя ошибка Организации',
  0   => 'OK',
  2   => 'Неизвестный тип запроса',
  3   => 'Плательщик не найден',
  4   => 'Нверная сумма платежа',
  5   => 'Неверное значение идентификатора транцакции',
  6   => 'Неверное значение даты',
  8   => 'Дублирование транзакции',
  12  => 'Транзакция не подтверждена'
);

# Некоторые входные параметры скрипта
my $command          = CGI::param ( 'ACTION' )  || '';      # Тип запроса
my $account          = CGI::param ( 'ACCOUNT' )  || '';      # Номер абонента
my $txn_id           = CGI::param ( 'PAY_ID' ) || '';      # Номер платежа (уник. для внешней системы)
my $amount           = CGI::param ( 'AMOUNT' ) || '';		    # Сумма платежа

# Информация, возвращаемая скриптом
my $result_code     = 0;       # Код ответа
my $prv_txn = '';	       # Номер платежа в системе провайдера

my $pay = new LB(host => $lbcore_host, user => $manager_login, pass => $manager_pass);
my $dbh = DBI->connect("DBI:mysql:$db:$host", $user, $password); #!!!!!!
my $r;
my @params;

if ( $command eq 'check' )
{
	# Тип запроса - проверка номера абонента для платежа
	if ( $account !~ /^\d+$/  )
	{
		# Неверное значение № абонента (синтаксис)
		$result_code = 3;#2;
	}
	else
	{
		$pay->connect || tmp_error();
		$result_code = -1; 
		#$r = $pay->check( 'ACCOUNT' => $account, 'type' => $type );
		 my ($r, $sum, $name) = $pay->check #!!!!!
				      (
					'number' => $account,
					'type' => $type,
					'fetch_balance' => 'yes',
					'fetch_param' => TYPE_FIO,
				      );
 		my $sth = $dbh->prepare("SELECT address_format( 0, vgroups.uid,  ' %r %R, %a %A , %l %L, %c %C, %s %S, %b %B, %f %F '  ) 
  FROM vgroups, agreements
  WHERE agreements.agrm_id = vgroups.agrm_id
  AND agreements.number =  '$account' ");
		my $rv = $sth->execute;
		my @row = $sth->fetchrow_array;
		my $rc = $sth->finish;
   push( @params, ($name, $sum, $row[0]));
  $rc = $dbh->disconnect;
		$result_code = 0 if $r == 0;
		$result_code = 100 if $r == -2;
		$result_code = 3 if $r == 2;
		$result_code = -1 if $r == 11;
		$pay->disconnect;
	}
}
elsif ( $command eq 'payment' )
{
	# Тип запроса - проведение платежа
	my $date    = CGI::param ( 'PAY_DATE' );     # Дата и время платежа (внешней системы)
	my $sth = $dbh->prepare("SELECT * FROM  `payments` WHERE receipt =  '$txn_id'");
	
	if ( $account !~ /^\d+$/ )
	{
		# Неверное значение № абонента (синтаксис)
		$result_code = 3;#2;
	}
	elsif ( $amount !~ /^\d+\.\d{0,2}$/ ) {
		# Неверное значение суммы (синтаксис)
		$result_code = 4;
	}
	elsif ( $amount <=0 ) {
		$result_code = 4;
	}
	elsif ( $txn_id !~ /^\d+$/ ) {
		# Неверное значение номера платежа (синтаксис)
		$result_code = 4;
	}
	elsif ( $sth->execute == 1 ) {
		# Дублирование транзакции
		$result_code = 8;
		my $rc = $sth->finish;
		$rc = $dbh->disconnect;
	}
	else
	{
		my $rc = $sth->finish;
		$rc = $dbh->disconnect;
		my $timestamp;
		my $amountcurr;
		if ($date =~ /(\d{2}).(\d{2}).(\d{4}).(\d{2}).(\d{2}).(\d{2})/)
		{
		  $result_code = -1;
		  $pay->connect || tmp_error();
		  $timestamp = mktime(00,$5,$4,$1,$2-1,$3-1900,0,0,-1);
# 		  print "Content-type: text/xml\n\n";
# 		  print $timestamp;
		  ( $r, $prv_txn, $amountcurr )
			= $pay->payment (
				'number'    => $account,
				'type'      => $type,
				'amount'    => $amount,
				'receipt'   => $txn_id,
				'date'      => $timestamp,
				'additional'=> 'Сбербанк банкомат', #комент
			);
		  $amount = $amountcurr if $r == 12;
		  if (($r == 0)||($r == 12)) {
			 $result_code = 0;
			 $result_messages{0}='';
		  }
		  $result_code = -1 if $r == -2;
		  $result_code = 3 if $r == 2;
		  $result_code = -1 if $r == 11;
		  $result_code = 2 if !defined($prv_txn) || ($prv_txn eq '');
		  $pay->disconnect;
		}
		else
		{
			# Неверное значение даты (синтаксис)
			$result_code = 6;
		}
	}
}
elsif ( $command eq 'cancel' )
{
	if ( $txn_id !~ /^\d+$/ ) {
		# Неверное значение номера платежа (синтаксис)
		$result_code = 4;
	}
	else
	{
		my $timestamp;
		$result_code = -1; 
		$pay->connect || tmp_error();
		$timestamp = mktime(00,$5,$4,$1,$2-1,$3-1900,0,0,-1);
		( $r, $prv_txn ) = $pay->cancel( 'receipt'   => $txn_id );
		$result_code = 0 if (($r == 0)||($r == 12));
		$result_code = 2 if $r == -2;
		$result_code = 2 if $r == 2;
		$result_code = 7 if $r == 7;
		$result_code = 9 if $r == 9;
		$result_code = -1 if $r == 11;
		$result_code = -1 if !defined($prv_txn) || ($prv_txn eq '');
		$pay->disconnect;
	}
}
elsif ( $command eq 'status' )
{
	if ( $txn_id !~ /^\d+$/ ) {
		# Неверное значение номера платежа (синтаксис)
		$result_code = 4;
	}
	else
	{
		  my $timestamp;
		  $result_code = -1; 
		  $pay->connect || tmp_error();
		  $timestamp = mktime(00,$5,$4,$1,$2-1,$3-1900,0,0,-1);
		  ( $r, $prv_txn ) = $pay->status( 'receipt'   => $txn_id );
		  $result_code = 0 if (($r == 0)||($r == 12));
		  $result_code = 2 if $r == -2;
		  $result_code = 2 if $r == 2;
		  $result_code = 6 if $r == 6;
		  $result_code = 7 if $r == 7;
		  $result_code = -1 if $r == 11;
		  $result_code = -1 if !defined($prv_txn) || ($prv_txn eq '');
		  $pay->disconnect;
	}
}
else
{
	# Неизвестный тип запроса
	$result_code = 2;#1;
}

print_response($amount,$result_code, @params);

exit;

################################################################################

sub print_response
{
	my ($amount,$result) = @_;
	my $msg = $result_messages{$result};
	print "Content-type: text/xml\n\n";
	my $timestamp = POSIX::strftime( "%d.%m.%Y_%H:%M:%S", localtime());;
	print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	print "<response>\n";
	print "<CODE>$result</CODE>\n";
	print "<MESSAGE>$msg</MESSAGE>\n";
	if ( $command eq 'payment')
	{
# 		print "<authcode>$txn_id</authcode>\n";
		print "<REG_DATE>$timestamp</REG_DATE>\n";
# 		print "<prv_txn>$prv_txn</prv_txn>\n";
# 		print "<sum>$amount</sum>\n";
	}

	
	print "  <FIO>". @params[0] ."</FIO>\n" if defined( @params[0] ); #!!!!!
	print "  <ADDRESS>". @params[2] ."</ADDRESS>\n" if defined( @params[2] ); #!!!!!
	print "  <ACCOUNT_BALANCE>". @params[1] ."</ACCOUNT_BALANCE>\n" if defined( @params[1] ); #!!!!!
	
	print "</response>\n";
}

################################################################################

sub tmp_error
{
	print_response('',-1);
	die("Can't connect to LBcore\n");
}
