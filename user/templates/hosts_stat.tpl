<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=windows-1251"> 
 <title>����� ��������������</title>
</head>

<body bgcolor="#CCCCCC">
<table border="1" width="100%">
  <caption>
  ����������� �������� ��������� {PNAME} �� ������ {DATE_PERIOD}
  </caption>
  <thead>
    <tr>
  <th rowspan=2 width="15%"><small>������������� ��</small></TH>
  <th rowspan=2 width="19%"><small>������� �����</small></TH>
  <th colspan=2 width="13%"><small>�������� ���������</small></th>
  <th rowspan=2 width="53%"><small>������������� ����������</small></th>
    </tr>
	<TR>
	  <th width="7%" align="center">���������</TH>
      <th width="6%" align="center">����������</TH>
</TR>
  </thead>
  <tbody>
   <!-- BEGIN DYNAMIC BLOCK: row -->
<TR><TD>{HNAME}</TD>
<TD><a href=host_stat.php?period=F{FROM}T{TO}&id={HID}>{IP}</a></TD>
<td align=right BGCOLOR=#ffff80>{IN}</td>
<td align=right BGCOLOR=#80ff00>{OUT}</td>
<td><font face="arial" size="1"><img src="bar-y.gif" width="{REL_IN}%" height="10" border="0"> {REL_IN}%<br>
	<img src="bar-g.gif" width="{REL_OUT}%" height="10" border="0"> {REL_OUT}%</font></td>
   <!-- END DYNAMIC BLOCK: row -->
  </tbody>
</table>
<P><I>����� �� �������� ������:</I><BR>
��������� �������: {ALL_IN}<BR>
���������� �������: {ALL_OUT} </P>
</body>
</html>
