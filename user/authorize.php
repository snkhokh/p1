<?

ini_set("display_errors", true);
error_reporting(E_ALL^E_NOTICE);

if (!ereg('^'.quotemeta($_SERVER['PHP_SELF']),$_SERVER['REQUEST_URI'])) {
  header("Location: /adm/user/authorize.php");
  exit;
}
//print_r($_SERVER);
include("../lib/config.php");
include("../lib/adm-lib.php");
include("../lib/class.FastTemplate.php3");

$Templ = new FastTemplate("./templates/");

Connect();

$a = GetHostId($_SERVER['REMOTE_ADDR']);
if (isset($a)) {
  $id = $a[1];
  if (!isset($_SESSION['auth'])) {
    if (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['l_login']) && isset($_POST['l_pass']) && isset($_SESSION['salt'])) {
     $query = "SELECT Name,password FROM hostip WHERE id = '$id'";
      $r = mysql_fetch_array(mysql_query($query));
      if (isset($r['password'])) {
      	$hash=md5($r['password'].$_SESSION['salt']);
        if (isset($r['Name']) && $_POST['l_login']===$r['Name'] && $_POST['l_pass']===$hash) {
          unset($_SESSION['salt']);
          $_SESSION['auth'] = 1;
          UserContr($id);
        }
      }
    }
    $Templ->define(array('page' => "login_panel.tpl"));
    $_SESSION['salt']=mt_rand();
    $Templ->assign(array('SALT'=>$_SESSION['salt']));
    $Templ->parse('MAIN','page');
    $Templ->FastPrint('MAIN');
    exit;
  } else UserContr($id);
}
PrintError("Доступ только для абонентов !");
exit;


function UserContr($id) {
  $mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    switch ($mode) {
      case 'chpass':
        $mode = '';
        if (isset($_POST['l_pass']) && isset($_POST['n_pass']) && isset($_SESSION['salt'])) {
          $query = "SELECT password FROM hostip WHERE id = '$id'";
          $r = mysql_fetch_array(mysql_query($query));
          if (isset($r['password'])) {
      	    $hash=md5($r['password'].$_SESSION['salt']);
            unset($_SESSION['salt']);
            if ($_POST['l_pass']===$hash) {
              mysql_query("UPDATE hostip SET password = '".addslashes($_POST['n_pass'])."' WHERE id = '$id'");
            } else {
          	  unset($_SESSION['auth']);
              header("Location: /adm/user/authorize.php");
              exit;
            }
          }
        }
        break;
      default:
	    $query = "SELECT flags,inact_timeout FROM hostip WHERE id = '$id'";
	    $r = mysql_fetch_array(mysql_query($query));
	    if (isset($_POST['locked'])) {
	      if ($_POST['locked'] === '1') {
	        SetFlag('U',$r['flags']);
	        unset($_SESSION['auth']);
	      }
	      else UnsetFlag('U',$r['flags']);
	    }
	    if (isset($_POST['inact_fl'])) {
	      if ($_POST['inact_fl'] === '1') SetFlag('I',$r['flags']);
	      else UnsetFlag('I',$r['flags']);
	    }

	    $query = sprintf("UPDATE hostip SET flags = '%s' WHERE id = '%s'",$r['flags'],$id);
	    mysql_query($query);
	    if (isset($_POST['inact_timeout']) && is_numeric($_POST['inact_timeout'])) {
	      $query = sprintf("UPDATE hostip SET inact_timeout = '%s' WHERE id = '%s'",$_POST['inact_timeout'],$id);
	      mysql_query($query);
	    }
    }
  }
// DebugBreak();
  global $Templ;
  switch ($mode) {
    case 'chpass':
      $Templ->define(array('page' => "chpass_panel.tpl"));
      $_SESSION['salt']=mt_rand();
      $Templ->assign(array('SALT'=>$_SESSION['salt']));
      $Templ->parse('MAIN','page');
      $Templ->FastPrint('MAIN');
      break;
    default:
      $Templ->define(array('page' => "control_panel.tpl"));
      $query = "SELECT persons.flags as pflags,hostip.flags as hflags,inact_timeout,Bill
        FROM hostip LEFT JOIN persons on hostip.PersonId = persons.id WHERE hostip.id = '$id'";
      $r = mysql_fetch_array(mysql_query($query));

      $bill = isset($r['Bill']) ? $r['Bill']:0;
      $pflags = isset($r['pflags']) ? $r['pflags'] : '';
      $hflags = isset($r['hflags']) ? $r['hflags'] : '';
      $msg = "На счете ".(($bill>0) ? "<font color=\"#00A510\">" : "<font color=\"#F00000\">" ).rub($bill)."</font>";
      if (ereg('N',$pflags)) $msg .=" Вы можете работать в кредит";
      if (ereg('D',$hflags)) $msg .="<br><font color=\"#F00000\">Установлена административная блокировка !</font>" ;
      $Templ->assign(array('MESSAGE'=>$msg));
      if (ereg('[UD]',$hflags)) $Templ->assign(array("LOCKED"=>'1'));
      else $Templ->assign(array("LOCKED"=>'0'));
      if (ereg('I',$hflags)) $Templ->assign(array('INACT'=>'1'));
      else $Templ->assign(array('INACT'=>'0'));
      if (isset($r['inact_timeout'])) $Templ->assign(array('SEL'.$r['inact_timeout'] => 'selected'));

      $Templ->parse('MAIN','page');
      $Templ->FastPrint('MAIN');
  }
  exit;
}

function SetFlag($a,&$b) {
  if (ereg($a,$b)) return;
  $b.=$a;
}
function UnsetFlag($a,&$b) {
  $b = ereg_replace($a,'',$b);
}


//DebugBreak();


?>