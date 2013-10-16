<?php
include("../lib/config.php");
include("../lib/adm-lib.php");

if (isset($_GET['hid']) && is_numeric($_GET['hid'])) $hid = $_GET['hid'];
if (isset($_GET['dat']) && preg_match('/^\d{8}$/',$_GET['dat'])) $dat=$_GET['dat'];
if (! isset($dat)) $dat = date('Ymd');
if (isset($hid)) {
  Connect();
  $q = mysql_query("SELECT hostip.Name,INET_NTOA(int_ip),persons.Name,PersonId FROM hostip,persons
                    WHERE persons.id = PersonId and hostip.id = ".$hid);
  if ( ! mysql_num_rows($q)) die ("Host $hid not found");

  $h=mysql_fetch_row($q);
  $a = GetHostId($_SERVER['REMOTE_ADDR']);
  if ((!isset($a)) || ($h[3] != $a[0])) {
    PrintError('Отказано в доступе к данным');
    exit;
  }

} else die ("Parametrs missing");
if (isset($_GET['mode']) && preg_match('/^gr$/',$_GET['mode'])) {

// Запросили визуализацию трафика для хоста

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

  // расчет суммы входящего трафика
 // если имеется что показать, покажем это


  $q = mysql_query("SELECT UNIX_TIMESTAMP(ts)-UNIX_TIMESTAMP('$dat'),Counter FROM
                    traf_in WHERE HostId = $hid AND DATE_FORMAT(ts,'%Y%m%d') = '$dat'");
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
                    traf_out WHERE HostId = $hid AND DATE_FORMAT(ts,'%Y%m%d') = '$dat'");
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
    $spd=(115-$y)/10*$spmod;
    imagestring ($im,3,30,$y-5,"${spd} b/s",$black);
  }
  for ($n=0;$n<=24;$n++) {
    $x=100+$n*20;
    imageline($im,$x,15,$x,115,$gray);
    imagestring($im,3,$x-2,120,"$n",$black);

  }

  header ("Content-type: image/png");
  imagepng ($im);
} else {

  include("../lib/class.FastTemplate.php3");
  $Templ = new FastTemplate("./templates/");
  $Templ->define(array('stat' => 'host_grstat.tpl'));

  $Templ->assign(array('HNAME' => $h[0],'IP' => $h[1],
          'PNAME' => $h[2],'PID' => $h[3]));

  $q = mysql_query("SELECT SUM(Counter) FROM traf_out WHERE HostId = $hid AND
                    DATE_FORMAT(ts,'%Y%m%d') = '$dat'");
  $a = mysql_fetch_row($q);
  $Templ->assign(array('OUT'=>GigaMega($a[0])));

  $q = mysql_query("SELECT SUM(Counter) FROM traf_in WHERE HostId = $hid AND
                    DATE_FORMAT(ts,'%Y%m%d') = '$dat'");
  $a = mysql_fetch_row($q);
  $Templ->assign(array('IN'=>GigaMega($a[0])));

  $q = mysql_query("SELECT DATE_FORMAT('$dat','%d/%m/%Y'),
                    DATE_FORMAT(DATE_ADD('$dat', INTERVAL 1 DAY),'%Y%m%d'),
                    DATE_FORMAT(DATE_SUB('$dat', INTERVAL 1 DAY),'%Y%m%d')");
  $a = mysql_fetch_row($q);

  $Templ->assign(array('HID'=>$hid,'DAT' => $dat,'DATE' => $a[0],
          'NDAT' => $a[1],'PDAT' => $a[2]));

  $Templ->parse('MAIN','stat');
  $Templ->FastPrint('MAIN');



}

?>
