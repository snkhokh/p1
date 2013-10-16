<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=windows-1251"> 
 <title>Режим администратора</title>
</head>

<body bgcolor="#CCCCCC">
<table border="1" width="100%">
  <caption>
  Суточная активность АП <em>{HNAME}</em> (ip:{IP})<br>
  абонента <em>{PNAME}</em> за период {DATE_PERIOD}
  </caption>
  <thead>
    <tr>
  <th width="24%" COLSPAN=2><small>Период</small></TH>
  <th width="25%" COLSPAN=2><small>Счетчики трафика</small></TH>
  <th ROWSPAN=2><small>Относительная активность</small></th>
  </tr>
  <th width="12%"><small>Начало</small></TH>
  <th width="12%"><small>Окончание</small></th>
  <th width="12%"><small>Входящего</small></TH>
  <th width="13%"><small>Исходящего</small></th>
    </tr>
  </thead>
  <tbody>
 <!-- BEGIN DYNAMIC BLOCK: row -->
<TR><TD>{FROM}</TD>
<td>{TO}</td>
<td ALIGN=right BGCOLOR=#FFFF99>{IN}</td>
<td ALIGN=right BGCOLOR=#00FF00>{OUT}</td>
<TD>
<img src="bar-y.gif" width="{REL_IN}%" height="10" border="0"><small>{REL_IN}%</small><br>
<img src="bar-g.gif" width="{REL_OUT}%" height="10" border="0"><small>{REL_OUT}%</small></td></TR>
 <!-- END DYNAMIC BLOCK: row -->
  </tbody>
</table>
<P><I>ВСЕГО за заданный период:</I><BR>
Входящего трафика: {ALL_IN}<BR>
Исходящего трафика: {ALL_OUT} </P>

</body>
</html>
