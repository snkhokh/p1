<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=windows-1251"> 
 <title>����� ��������������</title>
</head>

<body bgcolor="#CCCCCC">
<table border="1" width="100%">
  <caption>
  �������� ���������� �� <em>{HNAME}</em> (ip:{IP})<br>
  �������� <em>{PNAME}</em> �� ������ {DATE_PERIOD}
  </caption>
  <thead>
    <tr>
  <th width="24%" COLSPAN=2><small>������</small></TH>
  <th width="25%" COLSPAN=2><small>�������� �������</small></TH>
  <th ROWSPAN=2><small>������������� ����������</small></th>
  </tr>
  <th width="12%"><small>������</small></TH>
  <th width="12%"><small>���������</small></th>
  <th width="12%"><small>���������</small></TH>
  <th width="13%"><small>����������</small></th>
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
<P><I>����� �� �������� ������:</I><BR>
��������� �������: {ALL_IN}<BR>
���������� �������: {ALL_OUT} </P>

</body>
</html>
