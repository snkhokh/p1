<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
 <title>����� ������������</title>
</head>

<body bgcolor="#CCCCCC">
<strong><font face="lucida sans unicode"><center>�������:
{PNAME}</center></font></strong>
<table align=center>
<tr>
<td BGCOLOR=#CCFFFF>
<table>
<tr><td>���������� ����:</td><td>{FIO}</td></tr>
<tr><td>����� ����������� �����:</td><td><a href="mailto:{EMAIL}">{EMAIL}</a></td></tr>
<tr><td>�������������� ���������� </td><td>{OPT}</td></tr>
<tr><td>���������� ����������� �������: </td><td>{HOSTCOUNT}</td></tr>
<tr><td colspan=2>
<a href="hosts.php">������� � ������ ����������� �������</a></td></tr>
</table>
</td>
<td BGCOLOR=#FFCCFF>

<table>
<tr><td colspan=2>�� ��������� �� {DATE_TIME}</td></tr>
<tr><td>������� ������� �� �����</td><td>{BILL}</td>
</tr>
<tr><td>������� �������������� ������:</td><td>{UNITS}</td></tr>
</table>

</td>
</tr>
<tr><td colspan=2 BGCOLOR=#FFFF99>


<table>

<tr>
<td colspan=2 align=center>
�������� ����: <font face="lucida sans unicode" color="#ff6633">{TAXRATE}</font>
</td>
</tr>
<tr>
       <td>����������� ����� � �����</td>
       <td>{ABCHAR}</td>
</tr>
<tr>
       <td>� �����</td>
       <td>-</td>
</tr>
<tr><td>�� ������� ������� ������ �� ������� ������ ����������</td></tr>
<tr><td> �������� :</td><td>{CHARIN}</td></tr>
<tr><td> ��������� :</td><td>{CHAROUT}</td></tr>
</table>
</td></tr>
<tr><td colspan=2 align=center><a href="user.php?ChLog={PID}">������ ��������� ������ �������� {PNAME}</a></td></tr>
</table>
</body>
</html>