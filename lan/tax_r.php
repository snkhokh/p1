<?php
include("../lib/adm-lib.php");
include("../lib/config.php");
include("../lib/class.FastTemplate.php3");

// ******************************************************************************************
function ConstrCtl($DispIts,$tarif = 0) {
  $ctlmenu = array('К списку тарифов' => ''
             ,'Добавить тариф' => '?mode=add'
             ,'Удалить тариф' => "?mode=del&tarif=$tarif"
             );
  foreach ($ctlmenu as $Name => $Url) $it[] = array($Name => $Url);
  $menu = '<table width="100%" cellpadding="2" cellspacing="1" border="0">
  <th>Доступные операции</th>';
  foreach ($DispIts as $n) {
    $item = $it[$n];
    $menu .='<tr><td class="row1"><a href="tax_r.php'.current($item).'">'.key($item).'</a></td></tr>';
  }
  $menu .= '</table>';
  return $menu;
}
// ******************************************************************************************
function DoEditTarif($Templ){
  global $tarif,$action;
//var_dump($Templ);
  if (isset($tarif) && (is_numeric($tarif) or ($tarif === 'new'))) {
    if ($tarif === 'new') $res = array (0=>'new','',0,1024*1024,0,'in',
        "00:00:00","23:59:59",0,0,
        "00:00:00","00:00:00",0,0,
        "00:00:00","00:00:00",0,0,
        "00:00:00","00:00:00",0,0,
        "00:00:00","00:00:00",0,0,'dir' => 'in','flag' => 'norm');
    else {
    $q = mysql_query ("SELECT * FROM tax_rates WHERE id=$tarif");
      $res = mysql_fetch_array($q);
    }
    if (empty($res)) {
      $Templ->assign(array('VIEWSTATUS' => '<h1>Не найден тариф с таким идентификатором</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
      ));
      return $Templ;
    }
  } else {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не задан тариф</h1>'
          ,'VIEWCTL' => ConstrCtl(array(0,1))
      ));
    return $Templ;
  }
// имеется запись тарифа в $res
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
// печатаем форму для редактирования тарифа
    $Templ->define(array('tform' => 'tax_form.html'));
    $Templ->define_dynamic('row','tform');

    if ( preg_match('/in/',$res['dir']) ) { $Templ->assign(array('CHECK_IN' => 'CHECKED')); };
    if ( preg_match('/out/',$res['dir']) ) { $Templ->assign(array('CHECK_OUT' => 'CHECKED')); };
    if ( preg_match('/norm/',$res['flag']) ) { $Templ->assign(array('SEL_NORM' => 'CHECKED')); };
    if ( preg_match('/day/',$res['flag']) ) { $Templ->assign(array('SEL_DAY_LIMIT' => 'CHECKED')); };
    if ( preg_match('/mon/',$res['flag']) ) { $Templ->assign(array('SEL_MON_LIMIT' => 'CHECKED')); };
    if ( preg_match('/just/',$res['flag']) ) { $Templ->assign(array('SEL_JUST_COUNT' => 'CHECKED')); };

    $Templ->assign(array('NAME' => $res[1]
            ,'ABCH' => sprintf('%u.%02u руб.',floor($res[2]/100),$res[2]%100)
            ,'TUNIT' => GigaMega($res[3])
            ,'PREP' => $res[4]
            ,'TID' => $tarif
            ));
    for ($i = 6;$i <= 22; $i += 4 ) {
      $Templ->assign(array('FROM' => preg_replace('/:\d\d$/','',$res[$i],1)
                     ,'TO' => preg_replace('/:\d\d$/','',$res[$i+1],1)
                     ,'PRICE_IN' => sprintf('%u.%02u руб.',floor($res[$i+2]/100),$res[$i+2]%100)
                     ,'PRICE_OUT' => sprintf('%u.%02u руб.',floor($res[$i+3]/100),$res[$i+3]%100)
                     ));
      $Templ->parse('ROWS','.row');
    }
    $Templ->parse('VIEW','tform');
    return $Templ;

  } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($upd = ParseTarifForm() and array_key_exists('Name',$upd)) {
      $sql='';
      foreach ($upd as $field => $val)
        $sql.= (empty($sql) ? '' : ',') . "$field=" . (is_numeric($val) ? $val : "'$val'");
      if (is_numeric($tarif)) $q = mysql_query("UPDATE tax_rates SET $sql WHERE id=$tarif");
      elseif ($tarif === 'new') $q = mysql_query("INSERT INTO tax_rates SET $sql");
    }
    return DoAllTarif($Templ);
  }
}
################################################################################
//
//  function: ParseTarifForm()
//  parametrs: none
//  returned:
//
function ParseTarifForm() {
  global $Name,$AbonCharge,$TrafUnit,$PrePayedUnits,$period;

// ******************************************************************************************
  function TestTime($str){
    if (! empty($str) and is_string($str) and ereg('([0-2]*[0-9]):([[0-5][0-9])',$str,$a))
      if (! ($a[1]>23)) return "$a[1]:$a[2]:00";
  }

// ******************************************************************************************
  function TestPrice($str) {
    if (! empty($str) and preg_match('/^\d{1,9}(?:[\.,]\d{0,2})?/',$str,$a))
      return preg_replace('/,/','.',$a[0])*100;
    return 0;
  }
// ******************************************************************************************
// ******************************************************************************************


  if (is_string($Name)) {
    if ($Name = addslashes(trim($Name))) $upd['Name'] = $Name;
  }
  if (isset($_REQUEST['CHECK_IN']))  $dir='in';
  if (isset($_REQUEST['CHECK_OUT'])) $dir= isset($dir) ? $dir.',out' : 'out';
  if (isset($dir)) $upd['dir'] = $dir;

  if (isset($_REQUEST['flag'])) {
    switch ($_REQUEST['flag']) {
      case 'day_limit':
        $upd['flag']='day_limit';
        break;
      case 'mon_limit':
        $upd['flag']='mon_limit';
        break;
      case 'just_count':
        $upd['flag']='just_count';
        break;
      default:
        $upd['flag']='norm';
    }
  }


  if (isset($AbonCharge)) $upd['AbonCharge'] = TestPrice($AbonCharge);

  if (isset($TrafUnit) and preg_match('/^(\d+[\.,]?\d*) ([kmg]?)/i',$TrafUnit,$m)) {
    switch (strtolower($m[2])) {
      case 'k':
        $mul=1024;
        break;
      case 'm':
        $mul=1024*1024;
        break;
      case 'g':
        $mul=1024*1024*1024;
        break;
      default:
        $mul=1;
    }
    $upd['TrafUnit'] = (int) preg_replace('/,/','.',$m[1]) * $mul;
  } else $upd['TrafUnit'] = 1024*1024;

  if (isset($PrePayedUnits) and preg_match('/^\d+/',$_POST['PrePayedUnits'],$m)) $upd['PrePayedUnits'] = $m[0];
  else $upd['PrePayedUnits'] = 0;

  if (is_array($period)) {
    for ($i = 1; $i <= 5; $i++) {
      $upd['fr_'.$i] = TestTime($period[($i-1)*4]);
      $upd['to_'.$i] = TestTime($period[($i-1)*4+1]);
      $upd['in_ch'.$i] = TestPrice($period[($i-1)*4+2]);
      $upd['out_ch'.$i] = TestPrice($period[($i-1)*4+3]);
    }
  }
  if (isset($upd)) return $upd;
}
// ******************************************************************************************
function DoAllTarif ($Templ) {
  $req=mysql_query ("SELECT * FROM tax_rates");
  $tab = "<TABLE CELLSPACING=0 WIDTH='100%' BORDER=1>";
  while ($a=mysql_fetch_array($req)){
    $AbCh=sprintf('%u р. %02u коп.',floor($a['AbonCharge']/100),$a['AbonCharge']%100);

   switch ($a['flag']) {
      case 'day_limit':
        $flag="Установлен дневной лимит трафика $a[4] ед.";
        break;
      case 'mon_limit':
        $flag="Установлен месячный лимит трафика $a[4] ед.";
        break;
      case 'just_count':
        $flag="Трафик не лимитируется (только учет)";
        break;
      default:
        $flag="Снятие оплаты за трафик";
    }
    $dir = '';
    if ( preg_match('/in/',$a['dir']) ) $dir = 'ВХОДЯЩИЙ';
    if ( preg_match('/out/',$a['dir']) ) $dir = empty($dir) ? 'ИСХОДЯЩИЙ' : $dir.' и ИСХОДЯЩИЙ';

    $tab .= "<TR><TD>Название: <FONT COLOR=#FF0000>".$a[1]."</FONT><br>
    Режим учета : $flag <br>
    Абонентская плата : $AbCh<br>Единица трафика : ".GigaMega($a[3])."<br>
    Предоплаченный трафик : $a[4] ед.<br>
    Оплачивается: $dir (для абонента) трафик <br>
    <a href='tax_r.php?mode=edit&tarif=$a[0]'><u>Изменить данный тариф</u></a>
    <TD><TABLE WIDTH='100%'><TR><TH ROWSPAN=2>Временной интервал
    <TH COLSPAN=2>Оплата за трафик</TR>
    <TR><TH>Входящий<TH>Исходящий</TR>
    <TBODY ALIGN=RIGHT><TR BGCOLOR=\"#FFFF00\">";
        for ($i=6; $i<25; $i+=4) {
          if ($a[$i+2]+$a[$i+3]) { $tab .= '<TR BGCOLOR="#FFFF00">'; }
          else { $tab .= '<TR>'; };
          $tab .= "<TD ALIGN=CENTER>с $a[$i] по ".$a[$i+1].
            "<TD>".Rub($a[$i+2]).
            "<TD>".Rub($a[$i+3])."</TR>";
        }
    $tab .= "</TBODY> </TABLE></tr>";
  }
  $tab .= "</TABLE>";
  $Templ->assign(array('VIEWCTL' => ConstrCtl(array(1)),'VIEW'=>$tab));

  return $Templ;

}

// ******************************************************************************************
// MAIN ENTRY POINT

Connect();
$Templ = new FastTemplate("./templates/");
$Templ->define(array('index' => 'index.html'));
$Templ->assign(array('MENU'=>MakeMenu($MAIN_MENU,1)));

unset($_SESSION['mode']);

if (empty($mode)) $Templ = DoAllTarif($Templ);
elseif (is_string($mode)) {
  switch ($mode) {
    case 'edit' :
      $Templ = DoEditTarif($Templ);
      break;
    case 'add' :
      $tarif = 'new';
      $Templ = DoEditTarif($Templ);
      break;
    default :
      $Templ = DoAllTarif($Templ);
  }
}
$Templ->parse('MAIN','index');
$Templ->FastPrint('MAIN');

?>
