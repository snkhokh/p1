<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");

// ******************************************************************************************
function ConstrCtl($DispIts) {
  $ctlmenu = array('Управление трафиком' => ''
             );
  foreach ($ctlmenu as $Name => $Url) $it[] = array($Name => $Url);
  $menu = '<table width="100%" cellpadding="2" cellspacing="1" border="0">
  <th>Доступные операции</th>';
  foreach ($DispIts as $n) {
    $item = $it[$n];
    $menu .='<tr><td class="row1"><a href="control.php'.current($item).'">'.key($item).'</a></td></tr>';
  }
  $menu .= '</table>';
  return $menu;
}
// ******************************************************************************************
// Select main USERS page
function DoTrafPage($Templ) {
$r = mysql_query ("SELECT Data FROM flags WHERE Name = 'ROUTE'") or die ("Query failed");
if ( mysql_num_rows($r) > 0 ) {
  $row = mysql_fetch_assoc($r);
  $route = $row['Data'];
} else {
 $route = '1';
  mysql_query("INSERT INTO flags SET Data = '1', Name = 'ROUTE'");
}
if (isset($_POST['route'])) {
  switch ($_POST['route']) {
    case '1': $arg = '1';
      break;
    case '2': $arg = '2';
      break;
    case '3': $arg = '3';
      break;
    default: $arg='2';
  }
  if ($arg <> $route) {
     mysql_query("UPDATE flags SET Data = '{$arg}' WHERE Name = 'ROUTE'");
     if (! mysql_affected_rows())
       mysql_query("INSERT INTO flags SET Data = '{$arg}', Name = 'ROUTE'");
     $route = $arg;
  }
}

$r1 = $r2 = $r3 = '';
switch ($route) {
  case '1': $r1 = 'checked';
    break;
  case '2': $r2 = 'checked';
    break;
  case '3': $r3 = 'checked';
    break;
  default: $r2='checked';
}
$v = <<<EOD
<form name="Form1" action="control.php" method="post">
<table>
<tr><td><input name="route" type="radio" value="1" {$r1}></td>
<td>Маршрут через ЮТК</td></tr>
<tr><td><input name="route" type="radio" value="2" {$r2}></td>
<td>Маршрут через Киберком</td></tr>
<tr><td colspan="2"><input type="submit" value="Применить"></td></tr>
</table>
</form>
EOD;

$Templ->assign(array('VIEWCTL' => ConstrCtl(array(0)),
        'VIEW' => $v
        ));
return $Templ;
}

// ******************************************************************************************
// MAIN ENTRY POINT

Connect();
$Templ = new FastTemplate("./templates/");
$Templ->define(array('index' => 'index.html'));
$Templ->assign(array('MENU'=>MakeMenu($MAIN_MENU,4)));

if (empty($mode)) $Templ = DoTrafPage($Templ);
//MAIN JOB SELECTOR
elseif (is_string($mode)) {
  switch ($mode) {
    default :
      $Templ = DoTrafPage($Templ);
  }
}
$Templ->parse('MAIN','index');
$Templ->FastPrint('MAIN');

?>
