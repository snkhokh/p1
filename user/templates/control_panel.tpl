<head>
<style>
.input_text {
  border-style:solid;
  border: 1px solid #666666;
  background-color: #f8f8f8;
  font-family: Arial;
}
A {font-family: Arial;  font-size: 10pt;}
A:link {color: #8080ff; text-decoration: none;}
A:visited {color: #8080ff; text-decoration: none;}
A:hover {color: #8080ff; text-decoration: underline;}

.simple_text {
  font-family: Arial;
  font-size: 10pt;
  text-align: center;
  margin: 10px;
}
.input_button {
  border-style:solid;
  border: 1px solid #666666;
  background-color: #e8e8e8;
  font-family: Arial;
  font-weight: bold;
}
</style>
</head>
<body>
   <form method="post" action="authorize.php" name="user_contr">
   <input type=hidden name="locked" value="{LOCKED}">
   <input type=hidden name="inact_fl" value="{INACT}">
   <table align="center" width="600px" border=0 cellpadding=0 cellspacing=0>
   <tr>
    <td height=18 style="border-bottom:1px solid #666666;"><img src="images/table_l.gif"></td>
    <td width=418 height=18 style="background: url(images/table_c.gif);border-bottom:1px solid #666666;">&nbsp;</td>
    <td height=18 style="border-bottom:1px solid #666666;"><img src="images/table_r.gif"></td>
   </tr>
   <tr><td colspan=3 style="border-right:1px solid #666666; border-left: 1px solid #666666;">
      <p class="simple_text">
        {MESSAGE}
     </p>
   </td></tr>
   <tr><td colspan=3 style="border:1px solid #666666; border-top: none; border-bottom: none;">
   <div align="center"><span class="simple_text">Доступ к сети:</span><input class="input_button" type=button name="unlock" value="Открыть" onclick="LockUnlock()"> <input class="input_button" type=button name="lock" value="Закрыть" onclick="LockUnlock()"></div>
   <p class="simple_text"><input type=checkbox name="inact" value="1" onclick="TstChBx();this.form.submit()" class="input_text"> <span id="inact_t">Отключить при неактивности</span></p>
   <p  class="simple_text">
     <select name="inact_timeout" onchange="this.form.submit()" class="input_text"">
     <option {SEL5}>5</option>
     <option {SEL10}>10</option>
     <option {SEL15}>15</option>
     <option {SEL20}>20</option>
     <option {SEL30}>30</option>
     </select> <span id="inact_timeout_t">Период неактивности (минут)</span>
   </p>
   </td></tr>
   <tr>
   <td style="border:1px solid #666666; border-top: none; border-right: none;"><a href="user.php">Статистика и другое...</a> </td>
   <td colspan=2 style="border:1px solid #666666; border-top: none; border-left: none; text-align: right;">
     <a href="authorize.php?mode=chpass">Сменить пароль</a>
   </td></tr>
   </table>
   </form>
<script>
function TstChBx() {
  if (!user_contr.inact.checked) {
    user_contr.inact_fl.value = '0';
    user_contr.inact_timeout.disabled = 1;
    document.all('inact_timeout_t').disabled = 1;
  } else {
    user_contr.inact_fl.value = '1';
    user_contr.inact_timeout.disabled = 0;
    document.all('inact_timeout_t').disabled = 0;
  }
}
function LockUnlock() {
  user_contr.locked.value++;
  user_contr.locked.value %=2;
  DisableEnable();
  user_contr.submit();
}
function DisableEnable() {
  if (user_contr.locked.value == 0) {
    user_contr.unlock.disabled = 1;
    user_contr.lock.disabled = 0;
    user_contr.inact.disabled = 0;
    document.all('inact_t').disabled = 0;
    TstChBx();
  } else {
    user_contr.unlock.disabled = 0;
    user_contr.lock.disabled = 1;
    user_contr.inact.disabled = 1;
    document.all('inact_t').disabled = 1;
    user_contr.inact_timeout.disabled = 1;
    document.all('inact_timeout_t').disabled = 1;
  }
}
if (user_contr.inact_fl.value == '0') {
  user_contr.inact.checked = 0;
} else {
  user_contr.inact.checked = 1;
} 
DisableEnable();
 
</script>

</body>

