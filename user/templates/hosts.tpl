<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
 <title>����� ��������������</title>
</head>

<body bgcolor="#CCCCCC">
<table width="100%" border="1">
  <caption><h2>����������� ������ �������� {PNAME} �� ��������� �� {DATE_TIME}</h2></caption>
  <thead>
    <tr>
      <th width="19%">�������������</TH>
      <th width="18%"><small>������� ����� ������ ����</small></TH>
          <th width="18%"><small>������� ������� �����</small></th>
          <th width="20%"><small>����� MAC �������� �����������</small></th>
          <th width="10%" align="center">�����</th>
          <th width="15%" align="center">����������</th>
    </tr>
  </thead>
  <tbody>
    <!-- BEGIN DYNAMIC BLOCK: row -->
    <tr><td>{HNAME}</td>
    <td>{INT_IP}</td>
    <td>{EXT_IP}</td>
    <td>{MAC}</td>
    <td>{FLAGS}</td>
    <td><a href=host_stat.php?id={HID}>�����</a>
    <a href=gr_stat.php?hid={HID}>����.</a>
    </td>
    <!-- END DYNAMIC BLOCK: row -->
  </tbody>
</table>
<p>
<table>
<tr>
<td><form action="hosts_stat.php" METHOD=post>
<input name="pid" type="hidden" value="{PID}">	   
<table><tr>
<td align=right> C <input name="fday" type="text" size=2 value=01>/</td>
<td><select name="fmon">
  <option>01<option>02<option>03<option>04<option>05<option>06<option>07<option>08
  <option>09<option>10<option>11<option>12
</select>/</td>
<td><input name="fy" type="text" size=4 value={YEAR}></td>
<td><input name="fhour" type="text" size=2 value=00>:</td>
<td><input name="fmin" type="text" size=2 value=00></td>
</tr>
<tr>
<td align=right>�� <input name="tday" type="text" size=2 value=01>/</td>
<td><select name="tmon">
  <option>01<option>02<option>03<option>04<option>05<option>06<option>07<option>08
  <option>09<option>10<option>11<option>12
</select>/</td>
<td><input name="ty" type="text" size=4 value={YEAR}></td>
<td><input name="thour" type="text" size=2 value=23>:</td>
<td><input name="tmin" type="text" size=2 value=59></td>
</tr>
<tr>
<td colspan=5 align=center><input type="submit" value='������ �� ������'></td>
</tr>
</table>
</form>
<td valign=top><form action="hosts_stat.php" METHOD=get>
<input name="pid" type="hidden" value="{PID}">	   
<input type="submit" value='������ �� ������� �����'>
</form></tr>
</table>
</p>
<p>
<a href="user.php">������� � ������ �������� {PNAME}</a><br>
</body>
</html>