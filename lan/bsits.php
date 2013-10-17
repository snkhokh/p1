<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");
Connect();
function doPageIdx() {
    
}
$itemsPerPage = 20;
$result = db_query ("SELECT COUNT(*) FROM blocksites WHERE 1");
$row = db_row ($result['result']);
$sitesCount == $row[0];
if ($sitesCount > $itemsPerPage) doPageIdx();
    
if(@$_REQUEST['site']!="") {
    $site=mysql_real_escape_string($_REQUEST['site']);
    mysql_query("INSERT INTO blocksites (site) VALUES('$site');");
    
}
if(@$_REQUEST['action']=="del")	{
    mysql_query("DELETE FROM blocksites WHERE id=".round($_REQUEST['id']));
    
}
$Templ = new FastTemplate("./templates/");
$Templ->define(array('index' => 'index.html'));
$Templ->assign(array('MENU'=>MakeMenu($MAIN_MENU,3)));
$Templ->define(array('bsits' => 'bl_sites.html'));
$Templ->define_dynamic('row','bsits');

$result=mysql_query("SELECT id,site FROM blocksites ORDER BY site;");
$i=0;
while( $row=mysql_fetch_array($result) ) {
    if($i>0) $Templ->assign(array('STRICHEL'=>"<tr valign=bottom>
            <td bgcolor=#ffffff background='../img/strichel.gif' colspan=3>
            <img src=../img/blank.gif width=1 height=1></td></tr>"));
    else $Templ->assign(array('STRICHEL'=>''));
    
    $Templ->assign(array('SITE'=>htmlspecialchars($row['site']),
        'SID'=>$row['id']));
    $i++;
    $Templ->parse('ROWS','.row');
    
}
$Templ->parse('VIEW','bsits');
$Templ->parse('MAIN','index');
$Templ->FastPrint('MAIN');

?>
