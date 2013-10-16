<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");

Connect();

$a = GetHostId($_SERVER['REMOTE_ADDR']);
if (isset($a)) {
  $id = $a[0];

  if (isset($_GET['ChLog'])) {
    PrintChDataLog($id);
    exit;
  }

  $Templ = new FastTemplate("./templates/");
  $Templ->define(array('user' => 'user.tpl'));
  $Templ->assign(array('DATE_TIME'=>date('H:i d/m/Y')));
  $q = mysql_query("SELECT id,Name,FIO,Bill,PrePayedUnits,TaxRateId,EMail,
       UNIX_TIMESTAMP(BillCh) as UT,Opt FROM persons WHERE id = $id");
  if (! ($a=mysql_fetch_assoc($q))) {
    PrintError('Абонент с заданным идентификатором не найден');
    exit;
  }
  $tax = GetTarif($a['TaxRateId'],time());
  $Templ->assign(array('PID'=>$a['id'],
                 'PNAME'=>$a['Name'],
                 'FIO'=>$a['FIO'],
                 'TAXRATE'=>$tax['TaxName'],
                 'EMAIL'=>$a['EMail'],
                 'BILL'=>Rub($a['Bill']),
                 'UNITS'=>$a['PrePayedUnits'],
                 'ABCHAR'=>Rub($tax['MonthCharge']),
                 'DAYCHAR'=>Rub($tax['ChargePerDay']),
                 'CHARIN' => Rub($tax['CurrentCharIn']),
                 'CHAROUT' => Rub($tax['CurrentCharOut']),
                 'OPT' => $a['Opt']));
  $q = mysql_query("SELECT COUNT(id) FROM hostip WHERE PersonId=$id");
  if ($a = mysql_fetch_row($q)) $Templ->assign(array('HOSTCOUNT' => $a[0]));
    else $Templ->assign(array('HOSTCOUNT' => 0));

  $Templ->parse('MAIN','user');
  $Templ->FastPrint('MAIN');
} else PrintError("Доступ только для абонентов !");
?>
