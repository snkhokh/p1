<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  if (isset($_GET['id'])) $id = $_GET['id'];
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
  if (isset($_POST['id'])) $id = $_POST['id'];
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
if (isset($from,$to) && isset($id) && preg_match('/^\d+$/',$id)) {

  Connect();
  $Templ = new FastTemplate("./templates/");
  $Templ->define(array('stat' => 'host_stat.tpl'));
  $Templ->define_dynamic('row','stat');

  $q = mysql_query("SELECT hostip.Name,INET_NTOA(int_ip),persons.Name,PersonId FROM hostip,persons
     WHERE persons.id = PersonId and hostip.id = ".$id);
  if (! $h=mysql_fetch_row($q)) {
    PrintError('Отсутствует хост с таким идентификатором');
    exit;
  }
  $a = GetHostId($_SERVER['REMOTE_ADDR']);
  if ((!isset($a)) || ($h[3] != $a[0])) {
    PrintError('Отказано в доступе к данным');
    exit;
  }

  $Templ->assign(array('DATE_PERIOD'=>date('H:i d/m/Y',$from)." по ".date('H:i d/m/Y',$to),
          'HNAME' => $h[0],'IP' => $h[1],
          'PNAME' => $h[2]));

  mysql_query("CREATE TEMPORARY TABLE rez SELECT ds,Counter AS cin, 0 AS cout FROM day_traf_in WHERE
    HostId=$id AND ds > FROM_UNIXTIME($from) AND ds <= FROM_UNIXTIME($to)");
  mysql_query("INSERT INTO rez SELECT DATE_FORMAT(ts,'%Y-%m-%d') AS ds,SUM(Counter) as cin,
    0 AS cout FROM traf_in WHERE HostId=$id AND ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)
    GROUP BY ds");
  mysql_query("INSERT INTO rez SELECT ds,0 AS cin, Counter AS cout FROM day_traf_out WHERE
    HostId=$id AND ds > FROM_UNIXTIME($from) AND ds <= FROM_UNIXTIME($to)");
  mysql_query("INSERT INTO rez SELECT DATE_FORMAT(ts,'%Y-%m-%d') AS ds,0 AS cin,
    SUM(Counter) as cout FROM traf_out WHERE HostId=$id AND ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)
    GROUP BY ds");



  $q = mysql_query("SELECT SUM(cin),SUM(cout) from rez");
  $a = mysql_fetch_row($q);
  $tot_in = isset($a[0]) ? $a[0] : 0;
  $tot_out = isset($a[1]) ? $a[1] : 0;

  $Templ->assign(array('ALL_IN' => GigaMega($tot_in),
          'ALL_OUT' => GigaMega($tot_out)));

  if ($tot_in+$tot_out) {
    $q = mysql_query("SELECT DATE_FORMAT(ds,'%H:%i %e/%m/%Y') as d1,
                      DATE_FORMAT(ds,'%Y%m%d') as dat,
        DATE_FORMAT(DATE_ADD(ds,INTERVAL 1 DAY),'%H:%i %e/%m/%Y') as d2,
        SUM(cin) as cin,SUM(cout) as cout FROM rez GROUP BY ds ORDER BY ds");
    while ($a = mysql_fetch_assoc($q)) {

      $Templ->assign(array('FROM'=>"<a href='gr_stat.php?hid=$id&dat=".$a['dat']."'>".$a['d1'].'</a>',
              'TO' => $a['d2'],
              'IN' => GigaMega($a['cin']),
              'OUT' => GigaMega($a['cout']),
              'REL_IN' => $tot_in ? sprintf('%.2f',$a['cin']/$tot_in*100) : 0,
              'REL_OUT' => $tot_out ? sprintf('%.2f',$a['cout']/$tot_out*100): 0));
      $Templ->parse('ROWS','.row');
    }
    mysql_free_result($q);
  }

  $Templ->parse('MAIN','stat');
  $Templ->FastPrint('MAIN');
} else PrintError("Неправильный вызов процедуры hosts_stat.php !");
?>