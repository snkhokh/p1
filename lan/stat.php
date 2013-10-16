<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");
// ******************************************************************************************
function ConstrCtl($DispIts) {
  $ctlmenu = array('Статистика по абонентам' => 'stat.php?mode=stat'
             ,'Статистика по абонентским пунктам абонента' => "stat.php?mode=user"
             ,'Суточные итоги за абонентский пункт' => "stat.php?mode=host"
             ,'Статистика по коллекторам (провайдерская)' => "stat.php?mode=collectors"
             ,'Переход к данным абонента' => "users.php?mode=user"
             ,'Переход к данным абонентских пунктов' => "users.php?mode=hosts"
             ,'Суточные итоги по коллектору' => "stat.php?mode=detailcoll"
             );
  foreach ($ctlmenu as $Name => $Url) $it[] = array($Name => $Url);
  $menu = '<table width="100%" cellpadding="2" cellspacing="1" border="0">
  <th>Доступные операции</th>';
  foreach ($DispIts as $n) {
    $item = $it[$n];
    $menu .='<tr><td class="row1"><a href="'.current($item).'">'.key($item).'</a></td></tr>';
  }
  $menu .= '</table>';
  return $menu;
}

function DoAllStat($Templ) {
  $_SESSION['mode'] = 'stat';
  unset($_SESSION['pid'],$_SESSION['host']);
  $t = ParsePeriod();
  $ft = $t['from'];
  $tt = $t['to'];
  $Templ->define(array('stat' => 'all_stat.html'));
  $Templ->define(array('period' => 'period.html'));
  $Templ->define_dynamic('row','stat');

  $Templ->assign(array('DATEBEG' => strftime("%d.%m.%Y",$ft)
                 ,'TIMEBEG' => strftime("%H:%M",$ft)
                 ,'DATEEND' => strftime("%d.%m.%Y",$tt)
                 ,'TIMEEND' => strftime("%H:%M",$tt)
                 ,'ACTION' => 'stat.php'
                 ,'FROM' => $ft
                 ,'TO' => $tt
                 ));
$Templ->assign(array('OTHERITEMS' => ConstrCtl(array(3))));
$Templ->parse('VIEWCTL','period');

  $fdate = strftime("%Y-%m-%d %H:%M",$ft);
  $tdate = strftime("%Y-%m-%d %H:%M",$tt);

  $where1_str = "WHERE ts > '$fdate' AND ts < '$tdate'";
  $where2_str = "WHERE ds > '$fdate' AND ds < '$tdate'";


  if (! mysql_query("create temporary table rez select SUM(Counter) as cnt_in, 0 as cnt_out,
       HostId from traf_in $where1_str group by HostId")) exit;
  mysql_query("insert into rez select SUM(Counter) as cnt_in, 0 as cnt_out, HostId
       from day_traf_in $where2_str group by HostId");

  mysql_query("insert into rez select 0 as cnt_in, SUM(Counter) as cnt_out,
       HostId from traf_out $where1_str group by HostId");
  mysql_query("insert into rez select 0 as cnt_in, SUM(Counter) as cnt_out, HostId
        from day_traf_out $where2_str group by HostId");

  $q = mysql_query("select sum(cnt_in), sum(cnt_out) from rez");
  $a = mysql_fetch_row($q);
  $tot_in = isset($a[0]) ? $a[0] : 0;
  $tot_out = isset($a[1]) ? $a[1] : 0;


  $Templ->assign(array('VIEWSTATUS' => '<TABLE><TR>
  <TD COLSPAN="2"><h1>Суммарный объем трафика за период с '.date('H:i d/m/Y',$ft)
  .' по '.date('H:i d/m/Y',$tt).'</h1></TD></TR>'
  .'<TR><TD ALIGN="CENTER"><h1>Входящего: '.GigaMega($tot_in).'</h1></TD>'
  .'<TD ALIGN="CENTER"><h1>Исходящего: '.GigaMega($tot_out).'</h1></TD></TR></TABLE>'
  ));


  if ($tot_in+$tot_out) {
  $q = mysql_query("select sum(cnt_in) as cin, sum(cnt_out) as cout, persons.Name as PName,
       persons.id as pid from rez left join hostip on (HostId = hostip.id), persons
       where PersonId = persons.id group by pid order by PName");

    while ($a=mysql_fetch_assoc($q)) {
      $Templ->assign(array('PNAME' => $a['PName'],
            'PID' => $a['pid'],
            'IN' => GigaMega($a['cin']),
            'OUT' => GigaMega($a['cout']),
            'REL_IN' => $tot_in ? sprintf('%.2f',$a['cin']/$tot_in*100) : 0,
            'REL_OUT' => $tot_out ? sprintf('%.2f',$a['cout']/$tot_out*100): 0));
      $Templ->parse('ROWS','.row');
    }
    mysql_free_result($q);

  } else $Templ->clear_dynamic("row");

  $Templ->parse('VIEW','stat');
  return $Templ;
}


// ******************************************************************************************

function DoUserStat($Templ) {
  $_SESSION['mode'] = 'user';
  if (isset($_GET['pid']) && is_numeric($_GET['pid'])) { $pid = $_GET['pid']; }
  elseif (isset($_SESSION['pid'])) {$pid = $_SESSION['pid'];}
  else {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не задан идентификатор абонента</h1>'));
    return $Templ;
  }

  $q = mysql_query("SELECT Name FROM persons WHERE id = ".$pid);
  $p=mysql_fetch_assoc($q);
  if (empty($p)) {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Абонент с заданным идентификатором не найден</h1>'));
    return $Templ;
  }

  $_SESSION['pid'] = $pid;
  $t = ParsePeriod();
  $from = $t['from'];
  $to = $t['to'];

  $Name = $p['Name'];

  $Templ->define(array('stat' => 'hosts_stat.html'));
  $Templ->define_dynamic('row','stat');

  $Templ->define(array('period' => 'period.html'));
  $Templ->assign(array('DATEBEG' => strftime("%d.%m.%Y",$from)
                 ,'TIMEBEG' => strftime("%H:%M",$from)
                 ,'DATEEND' => strftime("%d.%m.%Y",$to)
                 ,'TIMEEND' => strftime("%H:%M",$to)
                 ,'ACTION' => 'stat.php'
                 ));
  $Templ->assign(array('OTHERITEMS' => ConstrCtl(array(3,0,4,5))));
  $Templ->parse('VIEWCTL','period');

  mysql_query("CREATE TEMPORARY TABLE hosts SELECT id from hostip where PersonId=".$pid);

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

  $Templ->assign(array('VIEWSTATUS' => '<TABLE><TR>
  <TD COLSPAN="2">Суммарный объем трафика абонента <u><i>'.$Name.'</i></u> за период с '.date('H:i d/m/Y',$from)
  .' по '.date('H:i d/m/Y',$to).'</TD></TR>'
  .'<TR><TD ALIGN="CENTER">Входящего: '.GigaMega($tot_in).'</TD>'
  .'<TD ALIGN="CENTER">Исходящего: '.GigaMega($tot_out).'</TD></TR></TABLE>'
  ));

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

  $Templ->parse('VIEW','stat');
  return $Templ;

}
// ******************************************************************************************

function DoHostStat($Templ) {
  $_SESSION['mode']='host';

  if (isset($_GET['host']) && is_numeric($_GET['host'])) {$host=$_GET['host'];}
  elseif (isset($_SESSION['host'])) {$host = $_SESSION['host'];}
  else {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не задан идентификатор АП</h1>'));
    return $Templ;
  }
  $q = mysql_query("SELECT hostip.Name AS Name,INET_NTOA(int_ip) AS iip,
     persons.Name AS Pname, PersonId AS pid FROM hostip,persons  WHERE persons.id = PersonId
     and hostip.id = ".$host);
  $h=mysql_fetch_assoc($q);
  if (empty($h)) {
    $Templ->assign(array('VIEWSTATUS' => '<h1>АП с заданным идентификатором не найден</h1>'
        ));
    return $Templ;
  }

  $_SESSION['host']=$host;
  $t = ParsePeriod();
  $from = $t['from'];
  $to = $t['to'];

  $Templ->define(array('stat' => 'host_stat.html'));
  $Templ->define_dynamic('row','stat');

  $pid = $h['pid'];
  $Templ->define(array('period' => 'period.html'));
  $Templ->assign(array('DATEBEG' => strftime("%d.%m.%Y",$from)
                 ,'TIMEBEG' => strftime("%H:%M",$from)
                 ,'DATEEND' => strftime("%d.%m.%Y",$to)
                 ,'TIMEEND' => strftime("%H:%M",$to)
                 ,'ACTION' => 'stat.php'
                 ));
  $Templ->assign(array('OTHERITEMS' => ConstrCtl(array(3,0,1,4,5))));
  $Templ->parse('VIEWCTL','period');



  mysql_query("CREATE TEMPORARY TABLE rez SELECT ds,Counter AS cin, 0 AS cout FROM day_traf_in WHERE
    HostId=$host AND ds > FROM_UNIXTIME($from) AND ds <= FROM_UNIXTIME($to)");
  mysql_query("INSERT INTO rez SELECT DATE_FORMAT(ts,'%Y-%m-%d') AS ds,SUM(Counter) as cin,
    0 AS cout FROM traf_in WHERE HostId=$host AND ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)
    GROUP BY ds");
  mysql_query("INSERT INTO rez SELECT ds,0 AS cin, Counter AS cout FROM day_traf_out WHERE
    HostId=$host AND ds > FROM_UNIXTIME($from) AND ds <= FROM_UNIXTIME($to)");
  mysql_query("INSERT INTO rez SELECT DATE_FORMAT(ts,'%Y-%m-%d') AS ds,0 AS cin,
    SUM(Counter) as cout FROM traf_out WHERE HostId=$host AND ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)
    GROUP BY ds");



  $q = mysql_query("SELECT SUM(cin),SUM(cout) from rez");
  $a = mysql_fetch_row($q);
  $tot_in = isset($a[0]) ? $a[0] : 0;
  $tot_out = isset($a[1]) ? $a[1] : 0;

  $Name = $h['Name'];
  $Pname = $h['Pname'];
  $Ip = $h['iip'];
  $pid = $h['pid'];
  $Templ->assign(array('VIEWSTATUS' => '<TABLE><TR>
  <TD COLSPAN="2">Суммарный объем трафика АП <u><i>'.
  "$Name ($Ip)</i></u> абонента <u><i>$Pname</i></u> за период с ".date('H:i d/m/Y',$from)
  .' по '.date('H:i d/m/Y',$to).'</TD></TR>'
  .'<TR><TD ALIGN="CENTER">Входящего: '.GigaMega($tot_in).'</TD>'
  .'<TD ALIGN="CENTER">Исходящего: '.GigaMega($tot_out).'</TD></TR></TABLE>'
  ));
  if ($tot_in+$tot_out) {
    $q = mysql_query("SELECT DATE_FORMAT(ds,'%H:%i %e/%m/%Y') as d1,
                      DATE_FORMAT(ds,'%Y%m%d') as dat,
        DATE_FORMAT(DATE_ADD(ds,INTERVAL 1 DAY),'%H:%i %e/%m/%Y') as d2,
        SUM(cin) as cin,SUM(cout) as cout FROM rez GROUP BY ds ORDER BY ds");
    while ($a = mysql_fetch_assoc($q)) {

      $Templ->assign(array('FROM'=>"<a href='stat.php?mode=grhost&dat=".$a['dat']."'>".$a['d1'].'</a>',
              'TO' => $a['d2'],
              'IN' => GigaMega($a['cin']),
              'OUT' => GigaMega($a['cout']),
              'REL_IN' => $tot_in ? sprintf('%.2f',$a['cin']/$tot_in*100) : 0,
              'REL_OUT' => $tot_out ? sprintf('%.2f',$a['cout']/$tot_out*100): 0));
      $Templ->parse('ROWS','.row');
    }
    mysql_free_result($q);
  }

  $Templ->parse('VIEW','stat');
  return $Templ;

}
// ******************************************************************************************
function DoCollStat($Templ) {
  global $COL_NAME;

  $_SESSION['mode']='collectors';
  $t = ParsePeriod();
  $from = $t['from'];
  $to = $t['to'];

  $Templ->define(array('period' => 'period.html'));
  $Templ->assign(array('DATEBEG' => strftime("%d.%m.%Y",$from)
                 ,'TIMEBEG' => strftime("%H:%M",$from)
                 ,'DATEEND' => strftime("%d.%m.%Y",$to)
                 ,'TIMEEND' => strftime("%H:%M",$to)
                 ,'ACTION' => 'stat.php'
                 ));
  $Templ->assign(array('OTHERITEMS' => ConstrCtl(array(0))));
  $Templ->parse('VIEWCTL','period');

  $Templ->assign(array('VIEWSTATUS' => '<TABLE><TR>
  <TD COLSPAN="2">Значение коллекторов трафика за период с '.date('H:i d/m/Y',$from)
  .' по '.date('H:i d/m/Y',$to).'</TD></TR></TABLE>'
  ));
  $view = '<table border="1">
    <thead>
      <tr>
    <th width="20%"><small>Коллектор</small></TH>
    <th width="20%"><small>Поток коллектора</small></TH>
    <th colspan="2" width="60%"><small>Значение счетчиков</small></th>
      </tr>
    </thead>
    <tbody>';

  $q = mysql_query("SELECT col, item, SUM(Counter) AS bytes FROM col_traf WHERE
  		ts BETWEEN FROM_UNIXTIME($from) AND FROM_UNIXTIME($to) GROUP BY col,item ORDER BY col,item");

  while ($a = mysql_fetch_assoc($q)) {
    $col = isset($COL_NAME[$a['col']]) ? $COL_NAME[$a['col']]['Name'] : $a['col'];
    $item = isset($COL_NAME[$a['col']][$a['item']]) ? $COL_NAME[$a['col']][$a['item']] : $a['item'];
    $view .= '<TR><TD>'.$col.'</TD><TD>'.$item.'</TD><TD>'.GigaMega($a['bytes']).'</TD>';
    $view .= '<TD><a href="stat.php?mode=detailcoll&col='.$a['col'].'&item='.$a['item'].'">Детализировать</a></TD></TR>';
  }
  mysql_free_result($q);
  $view .= '</tbody></table>';

  $Templ->assign(array('VIEW'=>$view));
  return $Templ;

}
// ******************************************************************************************
function DoDetailCollStat($Templ) {
  global $COL_NAME;
  $_SESSION['mode']='detailcoll';

  if (isset($_GET['col']) && is_numeric($_GET['col'])) { $ncol = $_GET['col']; }
  elseif (isset($_SESSION['col'])) {$ncol = $_SESSION['col'];}
  if (isset($_GET['item']) && is_numeric($_GET['item'])) { $nitem = $_GET['item']; }
  elseif (isset($_SESSION['item'])) {$nitem = $_SESSION['item'];}
  if (!isset($ncol) || !isset($nitem)) {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не заданы параметры коллектора</h1>'
                     ,'VIEWCTL' => ConstrCtl(array(0,3))
					));
    return $Templ;
  }
  $_SESSION['col']=$ncol;
  $_SESSION['item']=$nitem;
  $t = ParsePeriod();
  $from = $t['from'];
  $to = $t['to'];

  $col = isset($COL_NAME[$ncol]) ? $COL_NAME[$ncol]['Name'] : $ncol;
  $item = isset($COL_NAME[$ncol][$nitem]) ? $COL_NAME[$ncol][$nitem] : $nitem;
  $Templ->define(array('period' => 'period.html'));
  $Templ->assign(array('DATEBEG' => strftime("%d.%m.%Y",$from)
                 ,'TIMEBEG' => strftime("%H:%M",$from)
                 ,'DATEEND' => strftime("%d.%m.%Y",$to)
                 ,'TIMEEND' => strftime("%H:%M",$to)
                 ,'ACTION' => 'stat.php'
                 ,'OTHERITEMS' => ConstrCtl(array(0,3))
                 ,'VIEWSTATUS' => '<TABLE><TR><TD COLSPAN="2">
                 				Суточное значение коллектора трафика "'.$col.'-'.$item.'" за период с '.date('H:i d/m/Y',$from)
                 				.' по '.date('H:i d/m/Y',$to).'</TD></TR></TABLE>'
                 ));
  $q = mysql_query("SELECT DATE_FORMAT(ts,'%Y%m%d') as dat,
  					DATE_FORMAT(ts,'%d/%m/%Y') as d1,
			        SUM(Counter) as bytes FROM col_traf
			        WHERE ts BETWEEN FROM_UNIXTIME($from) AND FROM_UNIXTIME($to)
			        AND col=$ncol AND item=$nitem
			        GROUP BY dat ORDER BY dat");
  if (mysql_num_rows($q)) {
    $view = '<table border="1">
      <thead>
        <tr>
          <th width="20%"><small>Дата</small></TH>
          <th colspan="2" width="80%"><small>Значение счетчикa</small></th>
        </tr>
      </thead>
      <tbody>';
    while ($a = mysql_fetch_assoc($q)) {
      $view .= '<TR><TD>'.$a['d1'].'</TD><TD ALIGN="RIGHT">'.GigaMega($a['bytes']).'</TD>';
      $view .= '<TD ALIGN="CENTER"><a href="stat.php?mode=grcoll&dat='.$a['dat'].'">График</a></TD></TR>';
    }
    mysql_free_result($q);
    $view .= '</tbody></table>';
    $Templ->assign(array('VIEW'=>$view));
  }
  $Templ->parse('VIEWCTL','period');
  return $Templ;
}
// ******************************************************************************************
function DoGraphCollStat($Templ) {
  global $COL_NAME;
  $_SESSION['mode']='grcoll';
  if (isset($_GET['dat']) && preg_match('/^\d{8}$/',$_GET['dat'])) $dat=$_GET['dat'];
  if (! isset($dat)) $dat = date('Ymd');
  $y=substr($dat,0,4);
  $m=substr($dat,4,2);
  $d=substr($dat,6,2);
  if (!checkdate($m,$d,$y)) {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не действительный формат даты</h1>'
                     ,'VIEWCTL' => ConstrCtl(array(0,3))
					));
    return $Templ;
  }
  if (isset($_GET['col']) && is_numeric($_GET['col'])) { $ncol = $_GET['col']; }
  elseif (isset($_SESSION['col'])) {$ncol = $_SESSION['col'];}
  if (isset($_GET['item']) && is_numeric($_GET['item'])) { $nitem = $_GET['item']; }
  elseif (isset($_SESSION['item'])) {$nitem = $_SESSION['item'];}
  if (!isset($ncol) || !isset($nitem)) {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не заданы параметры коллектора</h1>'
                     ,'VIEWCTL' => ConstrCtl(array(0,3))
					));
    return $Templ;
  }
  $_SESSION['col']=$ncol;
  $_SESSION['item']=$nitem;

  $now = mktime(0,0,0,$m,$d,$y);

  if (!isset($_GET['pic'])) {
    $col = isset($COL_NAME[$ncol]) ? $COL_NAME[$ncol]['Name'] : $ncol;
    $item = isset($COL_NAME[$ncol][$nitem]) ? $COL_NAME[$ncol][$nitem] : $nitem;

    $pday = strftime('%Y%m%d',strtotime('-1 day',$now));
    $nday = strftime('%Y%m%d',strtotime('+1 day',$now));

    $Templ->assign(array('VIEWCTL' => ConstrCtl(array(0,3,6))
         ,'VIEWSTATUS' => '<TABLE><TR><TD COLSPAN="2">Суточный график коллектора трафика "'
           .$col.'-'.$item.'" за '."$d/$m/$y".'</TD></TR></TABLE>'
         ,'VIEW' => '<TABLE><tr>
            <td><div><a href="stat.php?dat='.$pday.'"><span>&larr;</span>&nbsp;На сутки раньше</a></td>
            <td><img src="stat.php?dat='.$dat.'&pic=1" width="600" height="135" border="1"></td>
            <td><div><a href="stat.php?dat='.$nday.'">На сутки позже&nbsp;<span>&rarr;</span></a></td>
           </tr></table>'
         ));
   return $Templ;

  }
  else {

    $imx=600;
    $imy=135;
    $maxx=480;
    $maxy=100;
    $xstep=180;


    $im = @imagecreate ($imx,$imy) or die ("Cannot Initialize new GD image stream");

    $bg = imagecolorallocate ($im, 0xCC, 0xCC, 0xCC);
    $red = imagecolorallocate ($im, 0xFF,0x33,0);
    $green = imagecolorallocate ($im,0x33,0xCC,0x33);
    $yellow = imagecolorallocate ($im,0xFF,0xFF,0x33);
    $gray = imagecolorallocate ($im,0xFF,0xFF,0xFF);
    $black = imagecolorallocate ($im,0,0,0);
    imageinterlace($im, 1);


    $q = mysql_query("SELECT UNIX_TIMESTAMP(ts)-UNIX_TIMESTAMP('$dat') AS Delta,Counter FROM
			        col_traf WHERE col=$ncol AND item=$nitem
			        AND DATE_FORMAT(ts,'%Y%m%d') = '$dat' ORDER BY Delta");
    if (mysql_num_rows($q)) {

    for ($x = 0;$x < $maxx; $x++) $h[$x] = 0; // проинициализируем массив отсчетов
    // засуммируем в интервалах отображения по икс - 180 секунд на сегодня
      while ($a = mysql_fetch_row($q)) $h[floor($a[0]/$xstep)] += $a[1];
      $max_sp = 0; // максимальная скорость в интервалах будет найдена по вход. трафику
      for ($x = 1;$x < ($maxx-1); $x++) {
    // попробуем немножко сгладить график (уберем провалы до нуля)
        if ((! $h[$x]) && ($h[$x-1] && $h[$x+1])) {
          $h[$x] = round($h[$x+1] / 2);
          $h[$x+1] -= $h[$x];
        }
    // в полученном массиве найдем максимум скорости
        if ($max_sp < $h[$x]) $max_sp = $h[$x];
      }
    // поджимаем график сверху, т.к. не факт что максимумов много
      $max_sp = round(0.7 * $max_sp);
    // начнем визуализацию
      for ($x = 0;$x < $maxx; $x++) if ($h[$x]) {
        $my_x = $x+100;
        $my_y = 115 - round(100*$h[$x]/$max_sp);
        if ($my_y < 15) { // уберем выбросы за оси и подсветим максимум красненьким
          $my_y = 15;
          imageline($im,$my_x,15,$my_x,13,$red);
        }
    // трафик отображаем гистограмками
        imageline($im,$my_x,115,$my_x,$my_y,$yellow);
      }
    }

    // чертим оси
    $spmod=round($max_sp/$xstep/10);
    for ($y=15;$y<=115;$y+=20) {
      imageline($im,100,$y,580,$y,$gray);
      $spd=(115-$y)/10*$spmod*8;
      imagestring ($im,3,10,$y-5,"${spd} bit/s",$black);
    }
    for ($n=0;$n<=24;$n++) {
      $x=100+$n*20;
      imageline($im,$x,15,$x,115,$gray);
      imagestring($im,3,$x-2,120,"$n",$black);

    }

    header ("Content-type: image/png");
    imagepng ($im);




    exit;
  }
}


// ******************************************************************************************
function DoGraphHostStat($Templ) {
  $_SESSION['mode']='grhost';

  if (isset($_GET['host']) && is_numeric($_GET['host'])) {$host=$_GET['host'];}
  elseif (isset($_SESSION['host'])) {$host = $_SESSION['host'];}
  else {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не задан идентификатор АП</h1>'));
    return $Templ;
  }
  $q = mysql_query("SELECT hostip.Name AS Name,INET_NTOA(int_ip) AS iip,
     persons.Name AS Pname, PersonId AS pid FROM hostip,persons  WHERE persons.id = PersonId
     and hostip.id = ".$host);
  $h=mysql_fetch_assoc($q);
  if (empty($h)) {
    $Templ->assign(array('VIEWSTATUS' => '<h1>АП с заданным идентификатором не найден</h1>'
        ));
    return $Templ;
  }
  $_SESSION['host']=$host;

  if (isset($_GET['dat']) && preg_match('/^\d{8}$/',$_GET['dat'])) $dat=$_GET['dat'];
  if (! isset($dat)) $dat = date('Ymd');


  if (isset($_GET['pic'])) {

    $imx=600;
    $imy=135;
    $maxx=480;
    $maxy=100;
    $xstep=180;



    $im = imagecreate ($imx,$imy) or die ("Cannot Initialize new GD image stream");

    $bg = imagecolorallocate ($im, 0xCC, 0xCC, 0xCC);
    $red = imagecolorallocate ($im, 0xFF,0x33,0);
    $green = imagecolorallocate ($im,0x33,0xCC,0x33);
    $yellow = imagecolorallocate ($im,0xFF,0xFF,0x33);
    $gray = imagecolorallocate ($im,0xFF,0xFF,0xFF);
    $black = imagecolorallocate ($im,0,0,0);
    imageinterlace($im, 1);

    // расчет суммы входящего трафика
   // если имеется что показать, покажем это


    $q = mysql_query("SELECT UNIX_TIMESTAMP(ts)-UNIX_TIMESTAMP('$dat'),Counter FROM
                      traf_in WHERE HostId = $host AND DATE_FORMAT(ts,'%Y%m%d') = '$dat'");
    if (mysql_num_rows($q)) {

    for ($x = 0;$x < $maxx; $x++) $h[$x] = 0; // проинициализируем массив отсчетов
    // засуммируем в интервалах отображения по икс - 180 секунд на сегодня
      while ($a = mysql_fetch_row($q)) $h[floor($a[0]/$xstep)] += $a[1];
      $max_sp = 0; // максимальная скорость в интервалах будет найдена по вход. трафику
      for ($x = 1;$x < ($maxx-1); $x++) {
    // попробуем немножко сгладить график (уберем провалы до нуля)
        if ((! $h[$x]) && ($h[$x-1] && $h[$x+1])) {
          $h[$x] = round($h[$x+1] / 2);
          $h[$x+1] -= $h[$x];
        }
    // в полученном массиве найдем максимум скорости
        if ($max_sp < $h[$x]) $max_sp = $h[$x];
      }
    // поджимаем график сверху, т.к. не факт что максимумов много
      $max_sp = round(0.7 * $max_sp);
    // начнем визуализацию
      for ($x = 0;$x < $maxx; $x++) if ($h[$x]) {
        $my_x = $x+100;
        $my_y = 115 - round(100*$h[$x]/$max_sp);
        if ($my_y < 15) { // уберем выбросы за оси и подсветим максимум красненьким
          $my_y = 15;
          imageline($im,$my_x,15,$my_x,13,$red);
        }
    // входящий трафик отображаем гистограмками
        imageline($im,$my_x,115,$my_x,$my_y,$yellow);
      }
    }

    // также поступим и с исходящим трафиком, только не будем считать максимум
    $q = mysql_query("SELECT UNIX_TIMESTAMP(ts)-UNIX_TIMESTAMP('$dat'),Counter FROM
                      traf_out WHERE HostId = $host AND DATE_FORMAT(ts,'%Y%m%d') = '$dat'");
    if (mysql_num_rows($q)) {

      for ($x = 0;$x < $maxx; $x++) $h[$x] = 0;
      while ($a = mysql_fetch_row($q)) $h[floor($a[0]/$xstep)] += $a[1];
      for ($x = 1;$x < ($maxx-1); $x++) {
        if ((! $h[$x]) && ($h[$x-1] && $h[$x+1])) {
          $h[$x] = round($h[$x+1] / 2);
          $h[$x+1] -= $h[$x];
        }
      }
      for ($x = 1;$x < $maxx; $x++) {
        $my_x = $x+100;
        if (($my_y1 = 115 - round(100*$h[$x-1]/$max_sp)) < 15) {
          $my_y1 = 15;
          imageline($im,$my_x-1,15,$my_x-1,13,$red);
        }
        if (($my_y2 = 115 - round(100*$h[$x]/$max_sp)) < 15) {
          $my_y2 = 15;
          imageline($im,$my_x,15,$my_x,13,$red);
        }

    // исходящий трафик отобразим кривой линией

        imageline($im,$my_x-1,$my_y1,$my_x,$my_y2,$green);
      }
    }
    // чертим оси
    $spmod=round($max_sp/$xstep/10);
    for ($y=15;$y<=115;$y+=20) {
      imageline($im,100,$y,580,$y,$gray);
      $spd=(115-$y)/10*$spmod*8;
      imagestring ($im,3,10,$y-5,"${spd} bit/s",$black);
    }
    for ($n=0;$n<=24;$n++) {
      $x=100+$n*20;
      imageline($im,$x,15,$x,115,$gray);
      imagestring($im,3,$x-2,120,"$n",$black);

    }

    header ("Content-type: image/png");
    imagepng ($im);
    exit;
  } else {

    $Templ->define(array('stat' => 'host_grstat.html'));

    $Templ->assign(array('HNAME' => $h['Name'],'IP' => $h['iip'],
            'PNAME' => $h['Pname'],'PID' => $h['pid']));

    $q = mysql_query("SELECT SUM(Counter) FROM traf_out WHERE HostId = $host AND
                      DATE_FORMAT(ts,'%Y%m%d') = '$dat'");
    $a = mysql_fetch_row($q);
    $Templ->assign(array('OUT'=>GigaMega($a[0])));

    $q = mysql_query("SELECT SUM(Counter) FROM traf_in WHERE HostId = $host AND
                      DATE_FORMAT(ts,'%Y%m%d') = '$dat'");
    $a = mysql_fetch_row($q);
    $Templ->assign(array('IN'=>GigaMega($a[0])));

    $q = mysql_query("SELECT DATE_FORMAT('$dat','%d/%m/%Y'),
                      DATE_FORMAT(DATE_ADD('$dat', INTERVAL 1 DAY),'%Y%m%d'),
                      DATE_FORMAT(DATE_SUB('$dat', INTERVAL 1 DAY),'%Y%m%d')");
    $a = mysql_fetch_row($q);

    $Templ->assign(array('DAT' => $dat,'DATE' => $a[0],
            'NDAT' => $a[1],'PDAT' => $a[2]));

   $Templ->assign(array('VIEWCTL' => ConstrCtl(array(3,0,1,2,4,5))));

   $Templ->parse('VIEW','stat');
   return $Templ;

  }
}
// ******************************************************************************************
// MAIN ENTRY POINT
//session_id('12345');
Connect();
$Templ = new FastTemplate("./templates/");
$Templ->define(array('index' => 'index.html'));
$Templ->assign(array('MENU'=>MakeMenu($MAIN_MENU,2)));

if (isset($_GET['mode'])) $mode = $_GET['mode'];
elseif (isset($_POST['mode'])) $mode = $_POST['mode'];
elseif (isset($_SESSION['mode'])) $mode = $_SESSION['mode'];
else $mode = '';
if (is_string($mode)) {
  switch ($mode) {
    case 'user' :
      $Templ = DoUserStat($Templ);
      break;
    case 'host' :
      $Templ = DoHostStat($Templ);
      break;
    case 'grhost' :
      $Templ = DoGraphHostStat($Templ);
      break;
    case 'grcoll' :
      $Templ = DoGraphCollStat($Templ);
      break;
    case 'collectors' :
      $Templ = DoCollStat($Templ);
      break;
    case 'detailcoll' :
      $Templ = DoDetailCollStat($Templ);
      break;
    default :
      $Templ = DoAllStat($Templ);
  }
}
else $Templ = DoAllStat($Templ);
$Templ->parse('MAIN','index');
$Templ->FastPrint('MAIN');

?>
