<?php
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");
Connect();
$itemsPerPage = 20;
$pageIdx = '';
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
if(@$_REQUEST['page']!="") {
   $pageDisp = intval($_REQUEST['page']); 
}
$result = mysql_query ("SELECT COUNT(*) FROM blocksites WHERE 1");
$row = mysql_fetch_array($result);
$sitesCount = $row[0];
$pageCnt = floor($sitesCount / $itemsPerPage)+1;

if(@$_REQUEST['page']!="") {
   $pageDisp = intval($_REQUEST['page']);
   if ($pageDisp > $pageCnt or $pageDisp < 1 ) $pageDisp = 1;
} else $pageDisp = 1;

if ($pageCnt > 1) {
    $firstRec = 0;
    $n = 0;
    while ($n < $pageCnt) {
        $sql = "SELECT (SELECT LEFT(site,2) FROM blocksites ORDER BY site LIMIT ".strval($firstRec).",1),".
            "(SELECT LEFT(site,2) FROM blocksites ORDER BY site LIMIT ".($firstRec+$itemsPerPage-1).",1)";
        $row = mysql_fetch_array(mysql_query ($sql));
        $n++;
        if ($n == $pageDisp) $pageIdx .= " ".$row[0]."-".$row[1];
        else $pageIdx .= " <a href=\"bsits.php?page=".strval($n)."\">".$row[0]."-".$row[1]."</a>";
        $firstRec += $itemsPerPage; 
    }
    $Templ->assign(array('PAGEIDX'=>"<p>Страницы: ".$pageIdx."</p>"));
     
  }   

 $Templ->define_dynamic('row','bsits');
$firstRec = ($pageDisp - 1)*$itemsPerPage;
$result=mysql_query("SELECT id,site FROM blocksites ORDER BY site LIMIT ".strval($firstRec).",".strval($itemsPerPage).";");
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
