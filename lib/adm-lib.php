<?php

function ParsePeriod() {
  $tt = localtime(time(),true);
  $ye = $tt['tm_year'];
  $ye = ($ye < 1900) ? $ye+1900 : $ye;
    $mon = $tt['tm_mon']+1;
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['ufrom']) && is_numeric($_GET['ufrom']))  $t['from'] = $_GET['ufrom'];
    elseif (isset($_SESSION['ufrom']))  $t['from'] = $_SESSION['ufrom'];
    else $t['from'] = mktime(0,0,0,$mon,1,$ye);

    if (isset($_GET['uto']) && is_numeric($_GET['uto'])) $t['to'] = $_GET['uto'];
    elseif (isset($_SESSION['uto']))  $t['to'] = $_SESSION['uto'];
    else $t['to'] = mktime(0,0,0,$mon+1,1,$ye);
#var_dump($_GET,$_SESSION);

  } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['datebeg']) && isset($_POST['timebeg'])
        && is_string($_POST['datebeg']) &&  is_string($_POST['timebeg'])
        && (sscanf($_POST['datebeg'],'%u.%u.%u',$d,$m,$y) == 3)
        && (sscanf($_POST['timebeg'],'%u:%u',$h,$mi) == 2)) $t['from'] = mktime($h,$mi,0,$m,$d,$y);
    elseif (isset($_SESSION['ufrom']))  $t['from'] = $_SESSION['ufrom'];
    else $t['from'] = mktime(0,0,0,$mon,1,$ye);

    if (isset($_POST['dateend']) && isset($_POST['timeend'])
        && is_string($_POST['dateend']) &&  is_string($_POST['timeend'])
        && (sscanf($_POST['dateend'],'%u.%u.%u',$d,$m,$y) == 3)
        && (sscanf($_POST['timeend'],'%u:%u',$h,$mi) == 2)) $t['to'] = mktime($h,$mi,0,$m,$d,$y);
    elseif (isset($_SESSION['uto']))  $t['to'] = $_SESSION['uto'];
    else $t['to'] = mktime(0,0,0,$mon+1,1,$ye);
  }
  $_SESSION['ufrom']= $t['from'];
  $_SESSION['uto']= $t['to'];
  return $t;
}
//
//  function: PrintChDataLog
//  parametrs: Person Id
//  returned:  none
//  additional: used template user_upd_log.tpl in folder ./templates/
//

function PrintChDataLog ($id) {
  $Templ = new FastTemplate("./templates/");
  $Templ->define(array('userlog' => 'user_upd_log.tpl'));
  $Templ->define_dynamic('row','userlog');

  $q = mysql_query("select date_format(Date,'%d-%m-%Y %T'),TypeOfOper,Operator,Value from admhist
                    where PersonId=$id order by Date desc");
  while ($a = mysql_fetch_row($q)) {
    switch ($a[1]) {
      case 'AddMoney':
        $act='Добавлено на счет '.Rub($a[3]);
        break;
      case 'ChData':
        $act='Изменены личные данные или тариф';
        break;
      case 'AddHost':
        $act='Добавлен абонентский пункт';
        break;
      case 'DelHost':
        $act='Удален абонентский пункт';
        break;
      case 'CreatePerson':
        $act='Создана учетная запись абонента';
        break;
      case 'AddPrepayed':
        $act=sprintf('Установлено предоплаченных ед. %u',$a[3]);
        break;
      }
    $Templ->assign(array('DATE'=>$a[0],'ACTION'=>$act,
                 'OPER'=>$a[2]));
    $Templ->parse('ROWS','.row');
  }
  $Templ->assign(array('PID'=>$id));
  $Templ->parse('MAIN','userlog');
  $Templ->FastPrint('MAIN');

}


function Connect()
{
  global $DBUser,$DBName,$DBPass,$DBHost;

  $link = mysql_connect($DBHost,$DBUser,$DBPass)
  or die ("Could not connect to MySQL");
  mysql_select_db ($DBName) or die ("Could not select database");
  mysql_query("SET NAMES cp1251");

  session_name('TrafAdmID');
  session_start();

}


//
//  function: MakeMenu
//  parametrs: Items - массив с ключами названиями пунктов и элементами -
//             URL по которым надо уходить
//             CurIt - индекс начиная с 0 активного пункта меню
//  returned: $s - строка готового меню в HTML
//

function MakeMenu($Items,$CurIt) {
  if (! is_array($Items)) return null;
  $menu = '<table><tr>';
  $i = 0;
  foreach ($Items as $Name => $Url) {
    $menu .='<td class="menuitem">';
    if ($i == $CurIt) $menu .= $Name;
    else $menu .= "<a href=\"$Url\">$Name</a>";
    $menu .='</td>';
    $i++;
  }
  $menu .= '</tr></table>';
  return $menu;
}

// ################################################################################
function ServerReload() {
 mysql_query("UPDATE flags SET Data=NOW() WHERE Name='RECON'");
}
// ################################################################################
function GigaMega($n){
//   if ($n>=(1024*1024*1024)) {
//     $r=sprintf("%.2fGb",$n/(1024*1024*1024));
//   }
//   elseif ($n>=(1024*1024)) {
  $r=sprintf("%.2f Mb",$n/(1024*1024));
//   }
//   elseif ($n>=1024) {
//     $r=sprintf("%.2fKb",$n/1024);
//   } else { $r="${n}b"; }
  return preg_replace('/\.00/','',$r);
}


// ################################################################################
function Rub($n) {
  if ($n < 0) {
    return sprintf('-%u руб. %02u коп.',intval(abs($n)/100), abs($n) % 100);
  } else {
    return sprintf('%u руб. %02u коп.',intval($n/100), $n % 100);
  }
}
//
//       получить тариф на основе времени $utime и идентификатора тарифа $id
//       результат - array($TaxName,$MonthCharge,$TrafUnit,$PrePayedUnits,
//                         $CurrentCharge,$ChargePerDay)
//

// ################################################################################
function GetTarif($id,$utime){
  $q = mysql_query("SELECT * FROM tax_rates WHERE id=$id");
  if ($tax=mysql_fetch_assoc($q)) {
    $ret = array('TaxName'=>$tax['Name'],
           'MonthCharge'=>$tax['AbonCharge'],
           'TrafUnit'=>$tax['TrafUnit'],
           'PrePayedUnits'=>$tax['PrePayedUnits'],
           'dir'=>$tax['dir'],
           'flag' => $tax['flag']);
    if ($tax['AbonCharge']) {
      $DaysInMon = array(1=>31,2=>28,3=>31,4=>30,5=>31,6=>30,
      7=>31,8=>31,9=>30,10=>31,11=>30,12=>31);
      $m = date('n',$utime);
      $days = $DaysInMon[$m];
      if (($days == 28) && (checkdate($m,29,date('Y',$utime)))) { $days = 29; }
      $ret['ChargePerDay'] = $tax['AbonCharge'] / $days;
    } else { $ret['ChargePerDay'] = 0; }
    $arrT = localtime($utime,1);
    $curT = $arrT['tm_hour']*3600+$arrT['tm_min']*60+$arrT['tm_sec'];
    $ret['CurrentCharge'] = 0;
    for ($i=1; $i<=5; $i++) {
      if (preg_match('/^(\d\d):(\d\d):(\d\d)$/',$tax["fr_$i"],$t)) $from=$t[1]*3600+$t[2]*60+$t[3];
        else $from=0;
      if (preg_match('/^(\d\d):(\d\d):(\d\d)$/',$tax["to_$i"],$t)) $to=$t[1]*3600+$t[2]*60+$t[3];
        else $to=0;
      if ((!$from && !$to) || (($curT >= $from) && ($curT <= $to))) {
        $ret['CurrentCharIn']=$tax["in_ch$i"];
        $ret['CurrentCharOut']=$tax["out_ch$i"];
        break;
      }
    }
  }
  return $ret;
}


// ################################################################################
//
//  function:
//  parametrs:
//  returned:
//
function PrintError($text) {
  $ErrTpl = new FastTemplate("./templates/");
  $ErrTpl->define(array('error' => 'error.tpl'));
  $ErrTpl->assign(array('TEXT'=>$text));
  $ErrTpl->parse('MAIN','error');
  $ErrTpl->FastPrint('MAIN');
}

//     echo bin2hex(substr($ltr,$i,4))."<br>";
//     foreach ($unp as $k => $v) echo $k." => ".$v."<br>";



// ################################################################################
function CountTraf($tr,$ltr,$from,$to) {
   $sum = 0;
   if (!($from < $to)) return $sum;
   $hidiv = 4294967296;
   $max_t = 0;
   for ($i=0; $i<strlen($ltr);$i = $i+10) {
     $unp = unpack('It',substr($ltr,$i,4));
     if (($unp['t'] > $from) && ($unp['t'] <= $to)) {
       if ($max_t < $unp['t']) $max_t = $unp['t'];
       $unp = unpack('Sa/Ib',substr($ltr,$i+4,6));
       $sum += ($unp['a'] * $hidiv + $unp['b']);
     }
   }
   if (! $max_t) $max_t = $from;
   if ($max_t == $to) return $sum;
   for ($i=0; $i<strlen($tr);$i = $i+8) {
     $unp = unpack('It',substr($tr,$i,4));
     if (($unp['t'] > $max_t) && ($unp['t'] <= $to)) {
       $unp = unpack('Ia',substr($tr,$i+4,4));
       $sum += $unp['a'];
     }
   }
   return $sum;
}
// ################################################################################
//
//  function: GetHostId
//  parametrs: IP Address
//  returned: $a[0] - Person Id
//            $a[1] - Host Id
//            $a[2] - mask length (IP)
//
function GetHostId($Addr) {

  if (($ip = ip2long($Addr))) {
    $mask=0xfffffffe;
    for ($masklen=32;$masklen >=16;--$masklen) {
      $sql=sprintf('SELECT PersonId,id,mask FROM hostip WHERE int_ip=%u',$ip);
      $q = mysql_query($sql);
      if (mysql_num_rows($q)) {
         $a = mysql_fetch_row ($q);
         if ($a[2] == $masklen) {
           return $a;
         }
      }
      $ip &=$mask;
      $mask <<=1;
    }
  }
}

//


?>
