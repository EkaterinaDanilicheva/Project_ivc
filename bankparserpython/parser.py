#coding: utf8
import MySQLdb
from urllib import urlopen
import xmlrpclib
import pycurl
from StringIO import StringIO
import xml.dom.minidom

class Payment :
    contract_number = 0
    date = ''
    number = ''
    sum = ''
    acount = ''
    inn = ''
    kpp = ''
    payer1 = ''
    check_ac = ''
    bank1	= ''
    bik = ''
    cor_ac = ''
    payment_type = ''
    purpose_of_payment = ''
    def show(self):
        print "contract_number = ", self.contract_number
        print "date = ", self.date
        print "number = ", self.number
        print "sum = ", self.sum
        print "acount = ", self.acount
        print "inn = ", self.inn
        print "kpp = ", self.kpp
        print "payer1 = ", self.payer1
        print "check_ac = ", self.check_ac
        print "bank1	= ", self.bank1
        print "bik = ", self.bik
        print "cor_ac = ", self.cor_ac
        print "payment_type = ", self.payment_type
        print "purpose_of_payment = ", self.purpose_of_payment
    def query(self, Mail):
        db = MySQLdb.connect(host="81.19.128.73", user="tariff", passwd="TrubKakuRa", db="billing19_002")
        cursor = db.cursor()
        sql = "SELECT ag.number FROM accounts=a, agreements=ag WHERE a.inn="+ MPayment.inn +" and ag.uid=a.uid and a.archive=0;"
        cursor.execute(sql)
        data =  cursor.fetchall()
        if data:
            for rec in data:
                self.contract_number = rec[0]
                self.action_check(mMail)
        else:
            Mail.message_query(MPayment)
        db.close()
    def action_check(self, Mail):
        storage = StringIO()
        c = pycurl.Curl()
        c.setopt(c.HTTPGET, 1)
        c.setopt(c.URL, 'https://stat.ivc.nnov.ru/oplata/clbank.cgi?action=check&number=' + str(self.contract_number))
        c.setopt(c.SSL_VERIFYPEER, 0)
        c.setopt(c.WRITEFUNCTION, storage.write)
        c.perform()
        c.close()
        content = storage.getvalue()
        par1 = xml.dom.minidom.parseString(content)
        data = par1.getElementsByTagName('code')
        for e in data:
            for t in e.childNodes:
                if t.data == "0":
                    Mail.message(MPayment)
                else:
                    Mail.message_check(MPayment)
class Mail :
    mail = []
    mail_query = []
    mail_check = []
    str = """
	<table border='1'>
	<caption>
		Таблица
	</caption>
	<tr>
		<th colspan='6'><font color = FF0000>Платеж не прошел (ИНН не найден)</font></th>
	</tr>
	<tr>
		<td>Дата</td>
		<td>Номер договора</td>
		<td>Плательщик</td>
		<td>Назначение платежа</td>
		<td>Сумма</td>
		<td>ИНН плательщика</td>
	</tr>"""
    def message(self, Payment):
        self.mail.append(Payment)
    def message_query(self, Payment):
        self.mail_query.append(Payment)
    def message_check(self, Payment):
        self.mail_check .append(Payment)
    def message_str(self):
        for j in self.mail_query:
                 self.str = self.str + """<tr>
					<td>"""+ str(j.date) +"""</td>
					<td bgcolor = FFA6A6>"""+ str(j.contract_number) +"""</td>
					<td bgcolor = FFA6A6>"""+ str(j.payer1) +"""</td>
					<td>"""+ str(j.purpose_of_payment) +"""</td>
					<td>"""+ str(j.sum) +"""</td>
					<td>"""+ str(j.inn) + """</td>
				</tr>"""
        self.str = self.str + """<tr>
            <th colspan='6'><font color = FF0000>Платеж не прошел (Номер договора не найден)</font></th>
        </tr>
        <tr>
            <td>Дата</td>
            <td>Номер договора</td>
            <td>Плательщик</td>
            <td>Назначение платежа</td>
            <td>Сумма</td>
            <td>ИНН плательщика</td>
        </tr>"""
        for j in self.mail_check:
                 self.str = self.str + """<tr>
					<td>"""+ str(j.date) +"""</td>
					<td bgcolor = FFA6A6>"""+ str(j.contract_number) +"""</td>
					<td bgcolor = FFA6A6>"""+ str(j.payer1) +"""</td>
					<td>"""+ str(j.purpose_of_payment) +"""</td>
					<td>"""+ str(j.sum) +"""</td>
					<td>"""+ str(j.inn) + """</td>
				</tr>"""
        self.str = self.str + """<tr>
            <th colspan='13'><font color = 5DE900>Успешные платежи</font></th>
        </tr>
        <tr>
            <td>Дата</td>
            <td>Номер договора</td>
            <td>Плательщик</td>
            <td>Назначение платежа</td>
            <td>Сумма</td>
            <td>ИНН плательщика</td>
        </tr>"""
        for j in self.mail:
                self.str = self.str + """<tr>
					<td>"""+ str(j.date) +"""</td>
					<td bgcolor = D5FA8F>"""+ str(j.contract_number) +"""</td>
					<td bgcolor = D5FA8F>"""+ str(j.payer1) +"""</td>
					<td>"""+ str(j.purpose_of_payment) +"""</td>
					<td>"""+ str(j.sum )+"""</td>
					<td>"""+ str(j.inn) + """</td>
				</tr>"""
        return self.str
    def send_mail(self):
        import smtplib
        from email.mime.text import MIMEText

        me = 'service@ivc.nnov.ru'
        you = 'danilicheva@ivc.nnov.ru'
        smtp_server = '192.168.1.1'
        port = 25
        msstr = self.message_str()
        msg = MIMEText(msstr, "html", "UTF-8")
        msg['Subject'] = 'Parser from python'
        msg['From'] = me
        msg['To'] = you
        s = smtplib.SMTP(smtp_server, port)
        s.sendmail(me, [you], msg.as_string())
        print "sended"
        s.quit()
    def show(self):
        print "mail"
        for j in self.mail:
                print j.payer1
        print "mail_query"
        for j in self.mail_query:
                print j.payer1
        print "mail_check"
        for j in self.mail_check:
                print j.payer1


f = open('kl_to_1c.txt')
i = 0
spisok = f.readlines()
mMail = Mail()
for line in spisok:
    line = line.decode('WINDOWS-1251').encode('UTF-8')
    if line.find('Получатель=ИНН 5262067308')!=-1:
        MPayment = Payment()

        MPayment.date = spisok[i-14][spisok[i-14].find('=')+1:-1].decode('WINDOWS-1251').encode('UTF-8')
        MPayment.number = spisok[i-13][spisok[i-13].find('=')+1:-1].decode('WINDOWS-1251').encode('UTF-8')
        MPayment.sum = spisok[i-12][spisok[i-12].find('=')+1:-1].decode('WINDOWS-1251').encode('UTF-8')
        MPayment.acount = spisok[i-11][spisok[i-11].find('=')+1:-1].decode('WINDOWS-1251').encode('UTF-8')
        MPayment.inn = spisok[i-8][spisok[i-8].find('=')+1:-1].decode('WINDOWS-1251').encode('UTF-8')
        MPayment.kpp = spisok[i-7][spisok[i-7].find('=')+1:-1].decode('WINDOWS-1251').encode('UTF-8')
        MPayment.payer1 = spisok[i-6][spisok[i-6].find('=')+1:-1].decode('WINDOWS-1251').encode('UTF-8')
        MPayment.check_ac = spisok[i-5][spisok[i-5].find('=')+1:-1].decode('WINDOWS-1251').encode('UTF-8')

        MPayment.query(mMail)
    i += 1
#mMail.show()
mMail.send_mail()
f.close()