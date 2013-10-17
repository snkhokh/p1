<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");

// ******************************************************************************************
function ConstrCtl($DispIts) {
  $ctlmenu = array('К списку абонентов' => 'users.php?mode=main'
             ,'Добавить абонента' => 'users.php?mode=add'
             ,'Удалить абонента' => "users.php?mode=del"
             ,'Редактировать данные абонента' => "users.php?mode=edit"
             ,'Журнал изменений данных абонента' => "users.php?mode=chlog"
             ,'Переход к данным абонентских пунктов' => "users.php?mode=hosts"
             ,'Добавить абонентский пункт' => "users.php?mode=newhost"
             ,'Удалить абонентский пункт' => "users.php?mode=delhost"
             ,'Статистика по абонентам' => 'stat.php?mode=stat'
             ,'Статистика по абонентским пунктам абонента' => "stat.php?mode=user"
             ,'Суточные итоги за абонентский пункт' => "stat.php?mode=host"
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
// ******************************************************************************************
// Select main USERS page
function DoMainPage($Templ) {
  unset($_SESSION['pid'],$_SESSION['host']);

  $Templ->define(array('users' => 'users.html'));
  $Templ->assign(array('VIEWSTATUS' => '<h1>Список абонентов по состоянию на '.date('H:i d/m/Y').'</h1>'
          ,'VIEWCTL' => ConstrCtl(array(1))
          ));

  $Templ->define_dynamic('row','users');
  $q = mysql_query("SELECT id,Name,FIO,Bill,PrePayedUnits FROM persons ORDER BY Name");
  while ($a=mysql_fetch_row($q)){
    $Pname = $a[1];
    $Templ->assign(array('PID'=>$a[0]
        ,'PNAME'=> $Pname
        ,'FIO'=>$a[2]
        ,'BILL'=>Rub($a[3])
        ,'UNITS'=>$a[4]
        ,'TITLE'=>"Переход к данным абонента $Pname"
        ));
    $Templ->parse('ROWS','.row');
  }
  $Templ->parse('VIEW','users');
  return $Templ;
}

// ******************************************************************************************
function DoViewUser($Templ) {

  if (isset($_GET['pid']) && is_numeric($_GET['pid'])) $pid = $_GET['pid'];
  elseif (isset($_SESSION['pid'])) $pid = $_SESSION['pid'];
  else {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не задан идентификатор абонента</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
        ));
    return $Templ;
  };

  $q = mysql_query("SELECT id,Name,FIO,Bill,PrePayedUnits,TaxRateId,EMail,
         UNIX_TIMESTAMP(BillCh) as UT,Opt FROM persons WHERE id = $pid");
  $ab=mysql_fetch_assoc($q);
  if (empty($ab)) {
      $Templ->assign(array('VIEWSTATUS' => '<h1>Абонент с заданным идентификатором не найден</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
        ));
    return $Templ;
  }
  $_SESSION['pid']=$pid;

  $Name = $ab['Name'];
  $Templ->assign(array('VIEWSTATUS' => "<h1>Данные абонента $Name по состоянию на ".date('H:i d/m/Y').'</h1>'
        ,'VIEWCTL' => ConstrCtl(array(0,1,2,3,4,5,9))
        ));
  $Templ->define(array('user' => 'user.html'));

  $d = getdate();
  $from = mktime(0,0,0,$d['mon'],1,$d['year']);
  $to = mktime(23,59,59,$d['mon'],$d['mday'],$d['year']);

  $q = mysql_query("SELECT SUM(count) FROM money_flow WHERE PersonId=$pid AND oper='add_cache' AND
                    ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)");
  $a = mysql_fetch_row($q);
  $add_nal = isset($a[0]) ? $a[0] : 0;

  $q = mysql_query("SELECT SUM(count) FROM money_flow WHERE PersonId=$pid AND oper='add_bank' AND
                    ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)");
  $a = mysql_fetch_row($q);
  $add_bank = isset($a[0]) ? $a[0] : 0;

  $q = mysql_query("SELECT SUM(count) FROM money_flow WHERE PersonId=$pid AND oper='add_credit'");
  // AND ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)");
  $a = mysql_fetch_row($q);
  $add_credit = isset($a[0]) ? $a[0] : 0;

  $q = mysql_query("SELECT SUM(count) FROM money_flow WHERE PersonId=$pid AND oper='sub_fee' AND
                    ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)");
  $a = mysql_fetch_row($q);
  $sub_fee = isset($a[0]) ? $a[0] : 0;

  $q = mysql_query("SELECT SUM(count) FROM money_flow WHERE PersonId=$pid AND oper='sub_traf' AND
                    ts > FROM_UNIXTIME($from) AND ts <= FROM_UNIXTIME($to)");
  $a = mysql_fetch_row($q);
  $sub_traf = isset($a[0]) ? $a[0] : 0;


  $tax = GetTarif($ab['TaxRateId'],time());
  $dir = '';
  if ( preg_match('/in/',$tax['dir']) ) $dir = 'ВХОДЯЩИЙ';
  if ( preg_match('/out/',$tax['dir']) ) $dir = empty($dir) ? 'ИСХОДЯЩИЙ' : $dir.' и ИСХОДЯЩИЙ';
  $flag='Режим учета : ';
  switch ($tax['flag']) {
    case 'day_limit':
      $flag.="Установлен дневной лимит трафика ".$tax['PrePayedUnits']." ед.";
      break;
    case 'mon_limit':
      $flag.="Установлен месячный лимит трафика ".$tax['PrePayedUnits']." ед.";
      break;
    case 'just_count':
      $flag.="Трафик не лимитируется (только учет)";
      break;
    default:
      $flag.="Снятие оплаты за трафик";
  }

  $Templ->assign(array('PID'=>$ab['id'],
                 'FIO'=>$ab['FIO'],
                 'TAXRATE'=>$tax['TaxName'],
                 'EMAIL'=>$ab['EMail'],
                 'BILL'=>Rub($ab['Bill']),
                 'UNITS'=>$ab['PrePayedUnits'],
                 'ABCHAR'=>Rub($tax['MonthCharge']),
                 'DAYCHAR'=>Rub($tax['ChargePerDay']),
                 'CHARIN' => Rub($tax['CurrentCharIn']),
                 'CHAROUT' => Rub($tax['CurrentCharOut']),
                 'INOUT' => $dir,
                 'MODE' => $flag,
                 'ADD_NAL' => Rub($add_nal),
                 'ADD_CREDIT' => Rub($add_credit),
                 'ADD_BANK' => Rub($add_bank),
                 'SUB_FEE' => Rub($sub_fee),
                 'SUB_TRAF' => Rub($sub_traf),
                 'SUM_ADD' => Rub($add_nal + $add_bank),
                 'SUM_SUB' => Rub($sub_fee + $sub_traf),
                 'OPT' => $ab['Opt']));
  $q = mysql_query("SELECT COUNT(id) FROM hostip WHERE PersonId=$pid");
  if ($a = mysql_fetch_row($q)) $Templ->assign(array('HOSTCOUNT' => $a[0]));
    else $Templ->assign(array('HOSTCOUNT' => 0));

  $Templ->parse('VIEW','user');
  return $Templ;
}

################################################################################
function DoEditUser($Templ) {

  if (isset($_SESSION['pid'])) $pid = $_SESSION['pid'];
  else {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Неправильный вызов процедуры редактирования данных абонента</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
        ));
    return $Templ;
  };
  $Person = GetPerson($pid);
  if (empty($Person)) {
      $Templ->assign(array('VIEWSTATUS' => '<h1>Абонент с заданным идентификатором не найден</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
        ));
    return $Templ;
  }
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $_SESSION['mode']='edit';
// Отображаем форму для редактирования абонента
    $Name = $Person['Name'];
    $q = mysql_query("SELECT id,Name FROM tax_rates");
    $tax = '';
    while ($t = mysql_fetch_row($q)) {
      if ($t[0] == $Person['TaxRateId']) $tax .= "<option selected>$t[1]</option>";
      else $tax .= "<option>$t[1]</option>";
    }

    $Templ->assign(array('VIEWSTATUS' => "<h1>Редактирование данных абонента <i><u>$Name</u></i></h1>"
            ,'VIEWCTL' => ConstrCtl(array(0,2,4,5))
            ,'PID'=> $Person['id']
            ,'PNAME' => $Name
            ,'FIO' => $Person['FIO']
            ,'EMAIL' => $Person['EMail']
            ,'PREP' => $Person['PrePayedUnits']
            ,'OPT' => $Person['Opt']
            ,'BILL' => Rub($Person['Bill'])
            ,'TAXRATES'=> $tax
            ,'CREDIT'=> (ereg('N',$Person['Flags'])?'checked':'')
            ,'AB_DAY'=> (ereg('D',$Person['Flags'])?'checked':'')
            ,'AB_MONTH'=> (ereg('D',$Person['Flags'])?'':'checked')
        ));
    $Templ->define(array('user' => 'user_upd.html'));
    $Templ->parse('VIEW','user');
    return $Templ;
  }
  elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $upd = ParseUser($Person);
    if ($upd) UpdPerson($upd,$pid);
    unset($_SESSION['mode']);
  }
return DoViewUser($Templ);
}
################################################################################
function DoAddUser($Templ) {
  $Person = GetPerson('new');
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $_SESSION['mode'] = 'add';
// Отображаем форму для редактирования абонента
    $Name = $Person['Name'];
    $q = mysql_query("SELECT id,Name FROM tax_rates");
    $tax = '';
    while ($t = mysql_fetch_row($q)) {
      if ($t[0] == $Person['TaxRateId']) $tax .= "<option selected>$t[1]</option>";
      else $tax .= "<option>$t[1]</option>";
    }
    $Templ->assign(array('VIEWSTATUS' => '<h1>Создание новой забиси абонента</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0))
            ,'PID'=> $Person['id']
            ,'PNAME' => $Name
            ,'FIO' => $Person['FIO']
            ,'EMAIL' => $Person['EMail']
            ,'PREP' => $Person['PrePayedUnits']
            ,'OPT' => $Person['Opt']
            ,'BILL' => Rub($Person['Bill'])
            ,'TAXRATES'=> $tax
        ));
    $Templ->define(array('user' => 'user_upd.html'));
    $Templ->parse('VIEW','user');
    return $Templ;
  }
  elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
// Создание или изменение записи пользователя
// Поиск измененных данных и формирование из них массива $upd

    unset($_SESSION['mode']);
    $upd = ParseUser($Person);
    if (array_key_exists('Name',$upd)) {
// Обязательно д.б. имя абонента а также проверим наличие тар. плана
      if (!array_key_exists('TaxRateId',$upd)) $upd['TaxRateId'] = $Person['TaxRateId'];
      $_SESSION['pid'] = NewPerson($upd);
    } else {
      $Templ->assign(array('VIEWSTATUS' => '<h1>Не определено имя или тариф для нового абонента</h1>'
              ,'VIEWCTL' => ConstrCtl(array(0,1))
          ));
      return $Templ;
    }
  }
return DoViewUser($Templ);

}
################################################################################
//
//  function: GetPerson
//  parametrs:  $pid - person identificator
//  returned:   $Person
//
function GetPerson($pid) {
  if ($pid === 'new') {
    $Person['id'] = 'new';
    $Person['Name'] = '';
    $Person['FIO'] = '';
    $Person['EMail'] = '';
    $Person['Opt'] = '';
    $Person['Flags'] = 'D';
    $Person['Bill'] = 0;
    $Person['PrePayedUnits'] = 0;
    $Person['UnitRem'] = 0;
    $q = mysql_query("SELECT id FROM tax_rates LIMIT 1");
    if ( !($a = mysql_fetch_row($q))) {
      PrintError('Не определены тарифные планы. Создание абонента не возможно.');
      exit;
    }
    $Person['TaxRateId'] = $a[0];
  } else {
    $q = mysql_query("SELECT * FROM persons WHERE id = $pid");
    if ( ! ($Person = mysql_fetch_assoc($q))) return null;
 }
 return $Person;
}
################################################################################
//
//  function:
//  parametrs:
//  returned:
//
function BankLog($pid,$bank,$bill) {
  mysql_query("INSERT INTO money_flow SET PersonId=$pid,oper='$bank',count=$bill");
}

################################################################################
//
//  function:
//  parametrs:
//  returned:
//
function GetCredit($pid) {
  $q = mysql_query("SELECT SUM(count) FROM money_flow WHERE PersonId=$pid AND oper='add_credit'");
  if ($a = mysql_fetch_row($q)) return $a[0];
  return 0;
}
################################################################################
//
//  function:
//  parametrs:
//  returned:
//
function AdmLog($oper,$data) {
  if (isset($_SESSION['pid'])) {
    if (isset($_SERVER['REMOTE_USER'])) $Operator=$_SERVER['REMOTE_USER'].':'.$_SERVER['REMOTE_ADDR'];
    else $Operator='Неизвестный:'.$_SERVER['REMOTE_ADDR'];
    $sql = "INSERT INTO admhist SET PersonId=".$_SESSION['pid'].",TypeOfOper='$oper',Operator='$Operator',";
    switch ($oper) {
      case 'AddPrepayed':
      case 'AddMoney':
        $sql .= "Value=$data";
        break;
      default:
        $sql .="Extra='".addslashes($data)."'";
    }
    mysql_query($sql);
  }
}
################################################################################
function ParseUser($p) {
  global $pname,$fio,$email,$taxrate,$prep,$opt,$bank,$addbill;
  if (isset($pname)) {
    $k_pname = trim($pname);
    if ($k_pname && ($p['Name'] !== $k_pname)) $u['Name'] = $k_pname;
  }
  if (isset($fio)) {
    $k_fio = trim($fio);
    if ($p['FIO'] !== $k_fio) $u['FIO'] = $k_fio;
  }
  if (isset($opt)) {
    $k_opt = trim($opt);
    if ($p['Opt'] !== $k_opt) $u['Opt'] = $k_opt;

  }
  if (isset($email) && ($p['EMail'] !== $email)) $u['EMail'] = trim($email);
  if (isset($prep) && is_numeric($prep) && ($p['PrePayedUnits'] !== $prep)) $u['PrePayedUnits'] = $prep;
  if (isset($addbill) && is_numeric($addbill) && $addbill) $u['addBill'] = 100*$addbill;
  if (isset($taxrate)) {
    $tax = $taxrate;
    $q = mysql_query("SELECT id FROM tax_rates WHERE Name='$tax'");
    if (($a = mysql_fetch_row($q)) && ($p['TaxRateId'] !== $a[0])) $u['TaxRateId'] = $a[0];
  }
  $fl = (isset($_REQUEST['credit']) ? 'N' : '').((isset($_REQUEST['ab_charge']) && $_REQUEST['ab_charge'] == 'D') ? 'D' : '');
  if ($p['Flags'] !== $fl) $u['Flags'] = $fl;
  if (isset($u)) return $u;
  return null;
}
################################################################################
function DoDelUser($Templ) {
  if (isset($_SESSION['pid'])) {
    $pid = $_SESSION['pid'];
    $q = mysql_query("SELECT id FROM hostip WHERE PersonId = $pid");
    if (mysql_num_rows($q) > 0) {
        $Templ->assign(array('VIEWSTATUS' => '<h1>Имеются абонентские пункты. Удаление невозможно.</h1>'
              ,'VIEWCTL' => ConstrCtl(array(0,1,3,4,5),$pid)
          ));
      return $Templ;
    }
    mysql_query("DELETE FROM persons WHERE id = $pid");
    mysql_query("DELETE FROM money_flow WHERE PersonId = $pid");
    AdmLog('DelPerson',$Person['Name']);
    ServerReload();
  }
  unset($_SESSION['pid']);
  return DoMainPage($Templ);
}
###############################################################################
//
//  function:
//  parametrs:
//  returned:
//
function UpdPerson($dat,$pid) {
  global $bank;

  if (array_key_exists('addBill',$dat)) {
    $bill = $dat['addBill'];
    if (isset($bank)) {
      BankLog($pid,$bank,$bill);
      if (($bank !== 'add_credit') && ($bill > 0) && ($credit=GetCredit($pid))) {
        if ($credit >= $bill) {
          BankLog($pid,'add_credit',-$bill);
          $bill=0;
        } else {
          BankLog($pid,'add_credit',-$credit);
          $bill -= $credit;
        }
      }
    }
    unset($dat['addBill']);
  }
  if (empty($bill)) $upd = 'Bill=Bill';
  else $upd = "Bill=Bill+($bill)";
  $needreload = 0;
  foreach ($dat as $field => $val) {
    $upd.=",$field=".(is_numeric($val) ? $val : "'$val'");
  }
  mysql_query("UPDATE persons SET $upd WHERE id = $pid");
  if (mysql_affected_rows() > 0) {
    if (array_key_exists('TaxRateId')) $needreload=1;
    if (isset($bill)) AdmLog('AddMoney',$bill);
    if (array_key_exists('PrePayedUnits',$dat)) AdmLog('AddPrepayed',$dat['PrePayedUnits']);
    AdmLog('ChData',$upd);
    if ($needreload) ServerReload();
  }
}

################################################################################
function NewPerson($dat) {
  global $bank;
  if (array_key_exists('addBill',$dat)) {
    $upd = "Name='".$dat['Name']."',Bill=".$dat['addBill'];
    $bill = $dat['addBill'];
    unset($dat['addBill']);
  } else $upd = "Name='".$dat['Name']."'";
  $Name = $dat['Name'];
  unset($dat['Name']);
  foreach ($dat as $field => $val) {$upd.=",$field=".(is_numeric($val) ? $val : "'$val'");}
  mysql_query("INSERT INTO persons SET $upd");
  if (mysql_affected_rows() > 0) {
    $pid = mysql_insert_id();
    AdmLog('CreatePerson',$Name);
    if (isset($bill)) {
      AdmLog('AddMoney',$bill);
      if (isset($bank)) BankLog($pid,$bank,$bill);
    }
    ServerReload();
    return $pid;
  }
  PrintError('Ошибка при создании нового абонента. Обновление не произведено.');
  exit;
}
// ******************************************************************************************
// ******************************************************************************************
function DoUserChLog($Templ) {

  if (isset($_SESSION['pid'])) {
  	$pid=$_SESSION['pid'];
    $Person = GetPerson($pid);
  }
  if (empty($Person)) {
      $Templ->assign(array('VIEWSTATUS' => '<h1>Абонент с заданным идентификатором не найден</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
        ));
    return $Templ;
  } else {
    $Name = $Person['Name'];
    $Templ->assign(array('VIEWSTATUS' => "<h1>Журнал изменения данных абонента <i><u>$Name</u></i></h1>"
            ,'VIEWCTL' => ConstrCtl(array(0,1,2,3,5),$pid)
        ));
  }
  $Templ->define(array('userlog' => 'user_upd_log.html'));
  $Templ->define_dynamic('row','userlog');

  $q = mysql_query("select date_format(Date,'%d-%m-%Y %T'),TypeOfOper,Operator,Value from admhist
                    where PersonId=$pid order by Date desc");
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
  $Templ->parse('VIEW','userlog');
  return $Templ;
}
// ******************************************************************************************

function DoHostData($Templ) {
  if (isset($_SESSION['pid'])) {
  	$pid=$_SESSION['pid'];
    $Person = GetPerson($pid);
  }
  if (empty($Person)) {
      $Templ->assign(array('VIEWSTATUS' => '<h1>Абонент с заданным идентификатором не найден</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
        ));
    return $Templ;
  }
  $Name = $Person['Name'];
  $Templ->assign(array('VIEWSTATUS' => "<h1>Абонентские пункты абонента <i><u>$Name</u></i></h1>"
            ,'VIEWCTL' => ConstrCtl(array(0,4,2,3,6),$pid)
            ,'PID' => $pid
        ));
  $Templ->define(array('hosts' => 'hosts.html'));
  $Templ->define_dynamic('row','hosts');

  $q = mysql_query("SELECT Name,INET_NTOA(int_ip) AS ip1,mask,INET_NTOA(ext_ip) AS ip2,"
     ."mac,flags,id FROM hostip WHERE PersonId = $pid ORDER BY int_ip");
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
  $Templ->parse('VIEW','hosts');
  return $Templ;
}
// ******************************************************************************************

function DoEditHost($Templ) {

  if (isset($_SESSION['pid'])) {
  	$pid=$_SESSION['pid'];
    $Person = GetPerson($pid);
  }
  if (empty($Person)) {
      $Templ->assign(array('VIEWSTATUS' => '<h1>Абонент с заданным идентификатором не найден</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
        ));
    return $Templ;
  }
  $Name = $Person['Name'];
// Хозяин имеется, определяемся с дальнейшим
  if (isset($_GET['mode'])) $_SESSION['mode'] = $_GET['mode'];

  if (isset($_SESSION['mode']) && ($_SESSION['mode']==='newhost')) $h = array('Name' => '','iip' => '','eip' => '',
        'mask' => 32,'mac' => '','flags' => '');
  elseif (isset($_GET['host']) && is_numeric($_GET['host'])) $host = $_GET['host'];
  elseif (isset($_SESSION['host'])) $host = $_SESSION['host'];
  else {
    $Templ->assign(array('VIEWSTATUS' => '<h1>Не задан идентификатор абонентского пункта</h1>'
            ,'VIEWCTL' => ConstrCtl(array(0,1))
        ));
    return $Templ;
  };
  if (empty($h)) {
    $q = mysql_query("SELECT Name,INET_NTOA(int_ip) as iip,INET_NTOA(ext_ip) as eip,mask,mac,
         flags,password FROM hostip WHERE id = $host");
    if ( ! ($h=mysql_fetch_assoc($q))) {
      $Templ->assign(array('VIEWSTATUS' => '<h1>Не найден абонентский пункт</h1>'
              ,'VIEWCTL' => ConstrCtl(array(0,1,2,3,4,5,6))
      ));
      return $Templ;
    }
    $_SESSION['host'] = $host;
  }

  // у абонента есть хост что дальше ?
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_SESSION['mode']) && ($_SESSION['mode'] === 'delhost')) {
// выбрали удаление хоста
      mysql_query("DELETE FROM hostip WHERE id = $host");
      mysql_query("DELETE FROM traf_in WHERE HostId = $host");
      mysql_query("DELETE FROM day_traf_in WHERE HostId = $host");
      mysql_query("DELETE FROM traf_out WHERE HostId = $host");
      mysql_query("DELETE FROM day_traf_out WHERE HostId = $host");

      AdmLog('DelHost',$h['iip'].'/'.$h['mask']);
      ServerReload();
      unset($_SESSION['mode'],$_SESSION['host']);
      return DoHostData($Templ); // удалить хост и показать что осталось
    }
// Просто отображаем заполненную форму хоста
    $HName =  $h['Name'];
    if (isset($_SESSION['mode']) && ($_SESSION['mode']==='newhost')) $Templ->assign(array('VIEWSTATUS' => "<h1>Создание нового АП для абонента <i><u>$Name</u></i></h1>"
            ,'VIEWCTL' => ConstrCtl(array(0,3,4,5))
        ));
    else $Templ->assign(array('VIEWSTATUS' => "<h1>Редактирование данных АП <i><u>$HName</u></i>
             абонента <i><u>$Name</u></i></h1>"
            ,'VIEWCTL' => ConstrCtl(array(0,3,4,5,7))
        ));

    $Templ->define(array('host' => 'host_upd.html'));
    $Templ->assign(array(
//    'HOST'=> $host,'PID' => $pid,
          'HNAME' => $HName,
          'INTIP' => $h['iip'],
          'EXTIP' => $h['eip'],
          'MASK' => $h['mask'],
          'PASS' => $h['password'],
          'MAC' => $h['mac']
            ));
    if (preg_match('/F/',$h['flags'])) $Templ->assign(array('CHFILTER'=> 'checked'));
    if (preg_match('/N/',$h['flags'])) $Templ->assign(array('CHNAT'=> 'checked'));
    if (preg_match('/E/',$h['flags'])) $Templ->assign(array('CHEXT'=> 'checked'));
    if (preg_match('/D/',$h['flags'])) $Templ->assign(array('CHDOWN'=> 'checked'));
    if (preg_match('/U/',$h['flags'])) $Templ->assign(array('CHUDOWN'=> 'checked'));
    if (preg_match('/I/',$h['flags'])) $Templ->assign(array('CHINACT'=> 'checked'));
    if (preg_match('/R/',$h['flags'])) $Templ->assign(array('CHRD'=> 'checked'));

    $Templ->parse('VIEW','host');
    return $Templ;
  }
  elseif (($_SERVER['REQUEST_METHOD'] == 'POST') &&  ($upd = ParseHost($h))) {
   $needreload=0;
   foreach ($upd as $field => $val) {
      $txt = "$field=";
      switch ($field) {
        case 'int_ip':
        case 'ext_ip':
          $txt .= "INET_ATON('$val')";
          $needreload=1;
          break;
        case 'mask':
          $txt .= "'$val'";
          $needreload=1;
          break;
        case 'flags':
           if (preg_match('/E/',$val) != preg_match('/E/',$h['flags'])) $needreload=1;
           if (preg_match('/N/',$val) != preg_match('/N/',$h['flags'])) $needreload=1;
           if (preg_match('/R/',$val) != preg_match('/R/',$h['flags'])) $needreload=1;
           if (preg_match('/F/',$val) != preg_match('/F/',$h['flags'])) mysql_query("INSERT IGNORE INTO flags SET Name='BHOST'");
        default:
          $txt .= "'$val'";
      }
      $sql = empty($sql) ? $txt : $sql.",$txt";
    }
    if (isset($_SESSION['mode']) && ($_SESSION['mode']==='newhost') && array_key_exists('Name',$upd) && array_key_exists('int_ip',$upd) && array_key_exists('mask',$upd)) {
      mysql_query("INSERT INTO hostip SET PersonId=$pid,$sql");
      if (mysql_affected_rows() > 0) {
        AdmLog('AddHost',$sql);
        ServerReload();
      }
      unset($_SESSION['mode']);
    }
    elseif (isset($host)) {
      mysql_query("UPDATE hostip SET $sql WHERE id = $host");
      if (mysql_affected_rows() > 0) {
        AdmLog('ChData',$sql);
        if ($needreload) ServerReload();
      }
      unset($_SESSION['mode']);
    }
  }
  return DoHostData($Templ);
}
################################################################################
function ParseHost($h) {
  global $hname,$intip,$extip,$mask,$mac;

  if (isset($hname)) {
    $k_hname = trim($hname);
    if (empty($h['Name']) || ($k_hname && ($h['Name'] !== $k_hname))) $u['Name'] = $k_hname;
  }
  if (isset($intip) && is_string($intip) && preg_match('/^((?:\d{1,3}\.){3}\d{1,3})/',$intip)) {
    if (empty($h['iip']) || ($h['iip'] !== trim($intip))) $u['int_ip'] = trim($intip);
  }
  if (isset($extip) && is_string($extip) && preg_match('/^((?:\d{1,3}\.){3}\d{1,3})/',$extip)) {
    if (empty($h['eip']) || ($h['eip'] !== trim($extip))) $u['ext_ip'] = trim($extip);
  }
  if (isset($mask) && is_numeric($mask)) {
    if (empty($h['mask']) || ($h['mask'] !== $mask)) $u['mask'] = $mask;
  }
  if (isset($mac) && is_string($mac)
      && preg_match('/^((?:[\d|A..F|a..f]{2}:){5}[\d|A..F|a..f]{2})/',$mac)) {
    if (empty($h['mac']) || ($h['mac'] !== trim($mac))) $u['mac'] = trim($mac);
  }
  if (isset($_REQUEST['pass'])) {
    $k_pass = trim($_REQUEST['pass']);
    if ($h['password'] !== $k_pass) $u['password'] = $k_pass;
  }
  $flags='';
  if (isset($_REQUEST['f_filter'])) $flags .= 'F';
  if (isset($_REQUEST['f_nat'])) $flags .= 'N';
  if (isset($_REQUEST['f_udown'])) $flags .= 'U';
  if (isset($_REQUEST['f_ext'])) $flags .= 'E';
  if (isset($_REQUEST['f_down'])) $flags .= 'D';
  if (isset($_REQUEST['f_inact'])) $flags .= 'I';
  if (isset($_REQUEST['f_redir'])) $flags .= 'R';
  if (empty($h['flags']) || ($flags !== $h['flags'])) $u['flags'] = $flags;
  if (isset($u)) return $u;
  return null;
}

// ******************************************************************************************
// MAIN ENTRY POINT

Connect();
$Templ = new FastTemplate("./templates/");
$Templ->define(array('index' => 'index.html'));
$Templ->assign(array('MENU'=>MakeMenu($MAIN_MENU,0)));

if (isset($_GET['mode'])) $mode = $_GET['mode'];
elseif (isset($_POST['mode'])) $mode = $_POST['mode'];
elseif (isset($_SESSION['mode'])) $mode = $_SESSION['mode'];
else $mode = '';
//MAIN JOB SELECTOR
  switch ($mode) {
    case 'user' :
      $Templ = DoViewUser($Templ);
      break;
    case 'edit' :
      $Templ = DoEditUser($Templ);
      break;
    case 'add' :
      $Templ = DoAddUser($Templ);
      break;
    case 'del' :
      $Templ = DoDelUser($Templ);
      break;
   case 'chlog' :
      $Templ = DoUserChLog($Templ);
      break;
    case 'hosts' :
      $Templ = DoHostData($Templ);
      break;
    case 'edhost' :
      $Templ = DoEditHost($Templ);
      break;
    case 'delhost' :
      $Templ = DoEditHost($Templ);
      break;
    case 'newhost' :
      $Templ = DoEditHost($Templ);
      break;
    default :
      $Templ = DoMainPage($Templ);
  }
$Templ->parse('MAIN','index');
$Templ->FastPrint('MAIN');

?>
