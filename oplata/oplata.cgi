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

############## ! fix me ! ###############
my $lbcore_host = '127.0.0.1';          # IP сервера LBcore
my $manager_login = 'oplata';             # логин менеджера АСР
my $manager_pass = '7318uSpX';          # пароль менеджера АСР
my $type = TYPE_AGRM_NUM;               # Тип идентификатора
#########################################

# Список примерных расшифровок ответов для некоторых кодов возврата
my %result_messages = (
  -3  => 'Внутренняя ошибка провайдера услуг',
  -2  => 'Неверное значение типа платежа (type)',
  -1  => 'Неверный формат дополнительного параметра',
  0   => 'OK',
  1   => 'Неизвестный тип запроса',
  2   => 'Абонент не найден',
  3   => 'Неверная сумма платежа',
  4   => 'Неверное значение номера платежа',
  5   => 'Неверное значение даты',
  6   => 'Успешный платеж с таким номером не найден',
  7   => 'Платеж с таким номером отменен',
  8   => 'Состояние платежа неопределенно',
  9   => 'Платеж не может быть отменен',

  11  => 'Временная ошибка. Повторите запрос позже.',
  100 => 'Неизвестная ошибка'
);

# Некоторые входные параметры скрипта
my $command          = CGI::param ( 'action' )  || '';      # Тип запроса
my $account          = CGI::param ( 'number' )  || '';      # Номер абонента
my $txn_id           = CGI::param ( 'receipt' ) || '';      # Номер платежа (уник. для внешней системы)
my $amount           = CGI::param ( 'amount' ) || '';		    # Сумма платежа

# Информация, возвращаемая скриптом
my $result_code     = 0;       # Код ответа
my $prv_txn = '';	       # Номер платежа в системе провайдера

my $pay = new LB(host => $lbcore_host, user => $manager_login, pass => $manager_pass);
my $r;

if ( $command eq 'check' )
{
	# Тип запроса - проверка номера абонента для платежа
	if ( $account !~ /^\d+$/  )
	{
		# Неверное значение № абонента (синтаксис)
		$result_code = 2;
	}
	else
	{
		$pay->connect || tmp_error();
		$result_code = -3; 
		$r = $pay->check( 'number' => $account, 'type' => $type );
		$result_code = 0 if $r == 0;
		$result_code = 100 if $r == -2;
		$result_code = 2 if $r == 2;
		$result_code = 11 if $r == 11;
		$pay->disconnect;
	}
}
elsif ( $command eq 'payment' )
{
	# Тип запроса - проведение платежа
	my $date    = CGI::param ( 'date' );     # Дата и время платежа (внешней системы)

	if ( $account !~ /^\d+$/ )
	{
		# Неверное значение № абонента (синтаксис)
		$result_code = 2;
	}
	elsif ( $amount !~ /^\d+\.\d{0,2}$/ ) {
		# Неверное значение суммы (синтаксис)
		$result_code = 3;
	}
	elsif ( $amount <=0 ) {
		$result_code = 3;
	}
	elsif ( $txn_id !~ /^\d+$/ ) {
		# Неверное значение номера платежа (синтаксис)
		$result_code = 4;
	}
	else
	{
		my $timestamp;
		my $amountcurr;
		if ($date =~ /(\d{4}).(\d{2}).(\d{2}).(\d{2}).(\d{2}).(\d{2})/)
		{
		  $result_code = -3;
		  $pay->connect || tmp_error();
		  $timestamp = mktime(00,$5,$4,$3,$2-1,$1-1900,0,0,-1);
#		  print "Content-type: text/xml\n\n";
#		  print $timestamp;
		  ( $r, $prv_txn, $amountcurr )
			= $pay->payment (
				'number'    => $account,
				'type'      => $type,
				'amount'    => $amount,
				'receipt'   => $txn_id,
				'date'      => $timestamp,
			);
		  $amount = $amountcurr if $r == 12;
		  $result_code = 0 if (($r == 0)||($r == 12));
		  $result_code = -3 if $r == -2;
		  $result_code = 2 if $r == 2;
		  $result_code = 11 if $r == 11;
		  $result_code = 2 if !defined($prv_txn) || ($prv_txn eq '');
		  $pay->disconnect;
		}
		else
		{
			# Неверное значение даты (синтаксис)
			$result_code = 5;
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
		$result_code = -3; 
		$pay->connect || tmp_error();
		$timestamp = mktime(00,$5,$4,$1,$2-1,$3-1900,0,0,-1);
		( $r, $prv_txn ) = $pay->cancel( 'receipt'   => $txn_id );
		$result_code = 0 if (($r == 0)||($r == 12));
		$result_code = 2 if $r == -2;
		$result_code = 2 if $r == 2;
		$result_code = 7 if $r == 7;
		$result_code = 9 if $r == 9;
		$result_code = 11 if $r == 11;
		$result_code = 11 if !defined($prv_txn) || ($prv_txn eq '');
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
		  $result_code = -3; 
		  $pay->connect || tmp_error();
		  $timestamp = mktime(00,$5,$4,$1,$2-1,$3-1900,0,0,-1);
		  ( $r, $prv_txn ) = $pay->status( 'receipt'   => $txn_id );
		  $result_code = 0 if (($r == 0)||($r == 12));
		  $result_code = 2 if $r == -2;
		  $result_code = 2 if $r == 2;
		  $result_code = 6 if $r == 6;
		  $result_code = 7 if $r == 7;
		  $result_code = 11 if $r == 11;
		  $result_code = 11 if !defined($prv_txn) || ($prv_txn eq '');
		  $pay->disconnect;
	}
}
else
{
	# Неизвестный тип запроса
	$result_code = 1;
}

print_response($amount,$result_code);

exit;

################################################################################

sub print_response
{
	my ($amount,$result) = @_;
	my $msg = $result_messages{$result};
	print "Content-type: text/xml\n\n";
	my $timestamp = POSIX::strftime( "%Y-%m-%dT%H:%M:%S", localtime());;
	print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	print "<response>\n";
	print "<code>$result</code>\n";
	if ( $command eq 'payment')
	{
		print "<authcode>$txn_id</authcode>\n";
		print "<date>$timestamp</date>\n";
		print "<prv_txn>$prv_txn</prv_txn>\n";
		print "<sum>$amount</sum>\n";
	}
	print "<message>$msg</message>\n";
	print "</response>\n";
}

################################################################################

sub tmp_error
{
	print_response('',-3);
	die("Can't connect to LBcore\n");
}
