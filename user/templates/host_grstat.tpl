<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=windows-1251"> 
 <title>����� ��������������</title>

<style>
<!--
div a {color: #333333}
div span {font-size: 130%}
div {padding: 0.2em 40px; font-size: 70%; color: #333333}
body {font-family: Arial, Geneva CY, Sans-Serif; margin:0; padding:0;}
div.links a {color: #FF9933}
-->
 </style>
</head>
<body>
 
<table>
<tr>
<td colspan=3 align=center>���������� �� <em>{HNAME}</em> (ip:{IP})<br>
�������� <em>{PNAME}</em> �� ����� {DATE}</td>
</tr>
<tr>
<td>
<div>
<a href="gr_stat.php?hid={HID}&dat={PDAT}">
<span>&larr;</span>&nbsp;������</a></td>
<td><img src="gr_stat.php?hid={HID}&dat={DAT}&mode=gr" width="600" height="135" border="1"></td>
<td><div>
<a href="gr_stat.php?hid={HID}&dat={NDAT}">
�����&nbsp;<span>&rarr;</span></a></td>
</tr>
<tr><td colspan=3 align=center>
<I>����� �� �������� ������:</I><BR>
<font color="#f0f000">��������� �������: </font>{IN}<BR>
<font color="#00cc00">���������� �������:</font> {OUT}
</tr>
<tr><td colspan=3><div class="links">
<a href="hosts.php">� ������ �� �������� {PNAME}</a></td></tr>
</table>

</body>
</html>
