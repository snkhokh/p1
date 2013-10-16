<html>
<title>Авторизация</title>
<style>
.input_text {
  border-style:solid;
  border: 1px solid #666666;
  background-color: #f8f8f8;
  font-family: Arial;
}
.auth_text {
  font-family: Arial;
  font-size: 10pt;
  text-align: right;
}
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
<script language="javascript" src="/adm/js/md5.js"></script>
<script language="javascript">
  function validate()
  {
    form = document.forms(0);
    if((form.l_login.value == "") || (form.pass.value == "")) 
    {
      alert("Не заполнены поля Логин и Пароль!");
      return false;
    }
    wait_result();
    form.l_pass.value = hex_md5(form.pass.value+form.salt.value);
    form.pass.disabled = 1;
    form.salt.disabled = 1;
    return true;
  }
  function wait_result()
  {
    if (document.getElementById)
    {
      document.getElementById('loading').style.visibility='visible';
    }
    else
    {
    if (document.layers)
    {
      document.loading.visibility = 'visible';
    }
    else
    {
      document.all.loading.style.visibility = 'visible';
    }}
  }
</script>
<body>
   <form method="post" action="authorize.php" onsubmit="javascript:return validate();">
   <input type="hidden" name="salt" value="{SALT}">
   <input type="hidden" name="l_pass" value="">
   <table align="center" width="600px" border=0 cellpadding=0 cellspacing=0>
   <tr>
    <td height=18 style="border-bottom:1px solid #666666;"><img src="images/table_l.gif"></td>
    <td width=418 height=18 style="background: url(images/table_c.gif);border-bottom:1px solid #666666;">&nbsp;</td>
    <td height=18 style="border-bottom:1px solid #666666;"><img src="images/table_r.gif"></td>
   </tr>
   <tr><td colspan=3 style="border-right:1px solid #666666; border-left: 1px solid #666666;">
     <p class="simple_text">
       Для того, чтобы получить доступ в Интернет Вам нужно авторизоваться.<br>
       Введите Ваш логин и пароль.
     </p>
   </td></tr>
   <tr>
    <td colspan=3 style="border-right:1px solid #666666; border-left: 1px solid #666666;">
     <table align="center" cellpadding=3>
         <tr>
           <td class="auth_text">Логин</td><td><input type="text" name="l_login" class="input_text"></td>
         </tr>
         <tr>
           <td class="auth_text">Пароль</td><td><input type="password" name="pass" class="input_text"></td>
         </tr>
         <tr>
           <td colspan="2" align="center"><input class="input_button" type="submit" value="Продолжить"></td>
         </tr>
      </table>
    </td>
   </tr>
   <tr><td colspan=3 style="border:1px solid #666666; border-top: none;">
     <div id='loading' style="visibility: hidden; font-weight: bold; color: red" class="simple_text">ПОДОЖДИТЕ...</div>
   </td></tr>
   </table>
   </form>
</body>
</html>