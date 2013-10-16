<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  if (isset($_GET['period']) && preg_match('/^F(\d+)T(\d+)$/',$_GET['period'],$t)) {
    $from = $t[1];
    $to = $t[2];
  } else  {
    $t = getdate();
    $from = mktime(0,0,0,$t['mon'],1,$t['year']);
    $to = mktime(23,59,59,$t['mon'],$t['mday'],$t['year']);
  }
}
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $fdate = isset($_POST['fy']) ? $_POST['fy'] : $d['year'];
  $fdate .= '-'.(isset($_POST['fmon']) ? $_POST['fmon'] : $d['mon']);
  $fdate .= '-'.(isset($_POST['fday']) ? $_POST['fday'] : '01');
  $fdate .= ' '.(isset($_POST['fhour']) ? $_POST['fhour'] : '00');
  $fdate .= ':'.(isset($_POST['fmin']) ? $_POST['fmin'] : '00');
  if (preg_match('/^(\d{4})-(\d{2})-(\d{1,2}) (\d{1,2}):(\d{1,2})$/',$fdate,$matches)) {
    $fdate = sprintf('%04d-%02d-%02d %02d:%02d:00',$matches[1],$matches[2],$matches[3],
      $matches[4],$matches[5]);
  }  else unset($fdate);
  $tdate = isset($_POST['ty']) ? $_POST['ty'] : $d['year'];
  $tdate .= '-'.(isset($_POST['tmon']) ? $_POST['tmon'] : '12');
  $tdate .= '-'.(isset($_POST['tday']) ? $_POST['tday'] : '01');
  $tdate .= ' '.(isset($_POST['thour']) ? $_POST['thour'] : '23');
  $tdate .= ':'.(isset($_POST['tmin']) ? $_POST['tmin'] : '59');
  if (preg_match('/^(\d{4})-(\d{2})-(\d{1,2}) (\d{1,2}):(\d{1,2})$/',$tdate,$matches)) {
    $tdate = sprintf('%04d-%02d-%02d %02d:%02d:00',$matches[1],$matches[2],$matches[3],
      $matches[4],$matches[5]);
  } else unset($tdate);
  if (isset($fdate,$tdate)) {
    $from = strtotime ($fdate);
    $to = strtotime ($tdate);
  }
}
Connect();
$id = GetHostId($_SERVER['REMOTE_ADDR']);

if (isset($from,$to,$id)) {

  $Templ = new FastTemplate("./templates/");
  $Templ->define(array('stat' => 'hosts_stat.tpl'));
  $Templ->define_dynamic('row','stat');

  $q = mysql_query("SELECT Name FROM persons WHERE id = ".$id[0]);
  if (! $p=mysql_fetch_assoc($q)) {
    PrintError('Отсутствует пользователь с таким идентификатором');
    exit;
  }

  $Templ->assign(array('DATE_PERIOD'=>date('H:i d/m/Y',$from)." по ".date('H:i d/m/Y',$to),
          'FROM' => $from, 'TO' => $to, 'PNAME' => $p['Name']));

  mysql_query("CREATE TEMPORARY TABLE hosts SELECT id from hostip where PersonId=".$id[0]);

  mysql_query("CREATE TEMPORARY TABLE rez SELECT HostId,sum(Counter) as cin, 0 as cout from traf_in,hosts
    where id=HostId and ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to) group by HostId");
  mysql_query("INSERT INTO rez SELECT HostId,sum(Counter) as cin, 0 as cout from day_traf_in,hosts
    where id=HostId and ds > FROM_UNIXTIME($from) AND ds <= FROM_UNIXTIME($to) group by HostId");
  mysql_query("INSERT INTO rez SELECT HostId, 0  as cin, sum(Counter) as cout from traf_out,hosts
    where id=HostId and ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to) group by HostId");
  mysql_query("INSERT INTO rez SELECT HostId, 0 as cin, sum(Counter) as cout from day_traf_out,hosts
    where id=HostId and ds > FROM_UNIXTIME($from) AND ds <= FROM_UNIXTIME($to) group by HostId");


  $q = mysql_query("SELECT SUM(cin),SUM(cout) from rez");
  $a = mysql_fetch_row($q);
  $tot_in = isset($a[0]) ? $a[0] : 0;
  $tot_out = isset($a[1]) ? $a[1] : 0;

  $Templ->assign(array('ALL_IN' => GigaMega($tot_in),
          'ALL_OUT' => GigaMega($tot_out)));

  if ($tot_in+$tot_out) {
    $q = mysql_query("SELECT Name, INET_NTOA(int_ip) as ip, mask, id, SUM(cin) AS cin,
         SUM(cout) AS cout from rez,hostip where id=HostId group by HostId order by int_ip");
    while ($a = mysql_fetch_assoc($q)) {
      $Templ->assign(array('HID'=>$a['id'],
              'HNAME'=>$a['Name'],
              'IN' => GigaMega($a['cin']),
              'OUT' => GigaMega($a['cout']),
              'IP' => $a['ip'].'/'.$a['mask'],
              'REL_IN' => $tot_in ? sprintf('%.2f',$a['cin']/$tot_in*100) : 0,
              'REL_OUT' => $tot_out ? sprintf('%.2f',$a['cout']/$tot_out*100): 0));
      $Templ->parse('ROWS','.row');
    }
    mysql_free_result($q);
  } else $Templ->clear_dynamic("row");

  $Templ->parse('MAIN','stat');
  $Templ->FastPrint('MAIN');
} else PrintError("Неправильный вызов процедуры hosts_stat.php !");
?>