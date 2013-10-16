<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");

Connect();
$a = GetHostId($_SERVER['REMOTE_ADDR']);
if (isset($a)) {
  $id = $a[0];
  $hid = $a[1];

  $Templ = new FastTemplate("./templates/");
  $Templ->define(array('hosts' => 'hosts.tpl'));
  $Templ->define_dynamic('row','hosts');
  $Templ->assign(array('DATE_TIME'=>date('H:i d/m/Y'),
          'YEAR' => date('Y')));

  $q = mysql_query("SELECT Name FROM persons WHERE id = $id");
  $a=mysql_fetch_assoc($q);
  $Templ->assign(array('PID'=>$id,
                 'PNAME'=>$a['Name']));
  $q = mysql_query("SELECT Name,INET_NTOA(int_ip) AS ip1,mask,INET_NTOA(ext_ip) AS ip2,"
     ."mac,flags,id FROM hostip WHERE PersonId = $id ORDER BY int_ip");
  if (! mysql_num_rows($q)) $Templ->clear_dynamic("row");
  else while ($a = mysql_fetch_assoc($q)) {
    $Templ->assign(array('HNAME'=>$a['Name'],
                 'INT_IP'=>$a['ip1'].'/'.$a['mask'],
                 'EXT_IP'=> $a['ip2'] ? $a['ip2'] : '-',
                 'MAC'=>$a['mac'] ? $a['mac'] : '-',
                 'FLAGS'=>$a['flags'],
                 'HID'=>$a['id']));
    $Templ->parse('ROWS','.row');
  }
  $Templ->parse('MAIN','hosts');
  $Templ->FastPrint('MAIN');
} else PrintError("Неправильный вызов процедуры hosts.php !");
?>
