<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=windows-1251"> 
 <title>Режим администратора</title>

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
<td colspan=3 align=center>Активность АП <em>{HNAME}</em> (ip:{IP})<br>
абонента <em>{PNAME}</em> за сутки {DATE}</td>
</tr>
<tr>
<td>
<div>
<a href="gr_stat.php?hid={HID}&dat={PDAT}">
<span>&larr;</span>&nbsp;раньше</a></td>
<td><img src="gr_stat.php?hid={HID}&dat={DAT}&mode=gr" width="600" height="135" border="1"></td>
<td><div>
<a href="gr_stat.php?hid={HID}&dat={NDAT}">
позже&nbsp;<span>&rarr;</span></a></td>
</tr>
<tr><td colspan=3 align=center>
<I>ВСЕГО за заданный период:</I><BR>
<font color="#f0f000">Входящего трафика: </font>{IN}<BR>
<font color="#00cc00">Исходящего трафика:</font> {OUT}
</tr>
<tr><td colspan=3><div class="links">
<a href="hosts.php">К списку АП абонента {PNAME}</a></td></tr>
</table>

</body>
</html>
