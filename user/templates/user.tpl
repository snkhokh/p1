<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
 <title>Режим пользователя</title>
</head>

<body bgcolor="#CCCCCC">
<strong><font face="lucida sans unicode"><center>Абонент:
{PNAME}</center></font></strong>
<table align=center>
<tr>
<td BGCOLOR=#CCFFFF>
<table>
<tr><td>Контактное лицо:</td><td>{FIO}</td></tr>
<tr><td>Адрес электронной почты:</td><td><a href="mailto:{EMAIL}">{EMAIL}</a></td></tr>
<tr><td>Дополнительная информация </td><td>{OPT}</td></tr>
<tr><td>Количество абонентских пунктов: </td><td>{HOSTCOUNT}</td></tr>
<tr><td colspan=2>
<a href="hosts.php">Переход к данным абонентских пунктов</a></td></tr>
</table>
</td>
<td BGCOLOR=#FFCCFF>

<table>
<tr><td colspan=2>По состоянию на {DATE_TIME}</td></tr>
<tr><td>остаток средств на счете</td><td>{BILL}</td>
</tr>
<tr><td>Остаток предоплаченных едениц:</td><td>{UNITS}</td></tr>
</table>

</td>
</tr>
<tr><td colspan=2 BGCOLOR=#FFFF99>


<table>

<tr>
<td colspan=2 align=center>
Тарифный план: <font face="lucida sans unicode" color="#ff6633">{TAXRATE}</font>
</td>
</tr>
<tr>
       <td>Абонентская плата в месяц</td>
       <td>{ABCHAR}</td>
</tr>
<tr>
       <td>в сутки</td>
       <td>-</td>
</tr>
<tr><td>За единицу трафика оплата на текущий момент составляет</td></tr>
<tr><td> Входящий :</td><td>{CHARIN}</td></tr>
<tr><td> Исходящий :</td><td>{CHAROUT}</td></tr>
</table>
</td></tr>
<tr><td colspan=2 align=center><a href="user.php?ChLog={PID}">Журнал изменений данных абонента {PNAME}</a></td></tr>
</table>
</body>
</html>