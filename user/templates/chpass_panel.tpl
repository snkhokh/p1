<html>
<title>Авторозация</title>
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
  function send_data(form)
  {
    if((form.pass.value == "") || (form.n_pass.value == "") || (form.pass1.value == "")) 
    {
      alert("Пароли не должны быть пустыми!");
      return 0;
    }
    if(!(form.n_pass.value == form.pass1.value)) 
    {
      alert("Новый пароль не подтвержден!");
      return 0;
    }
    
    wait_result();
    form.l_pass.value = hex_md5(form.pass.value+form.salt.value);
    form.pass.disabled = 1;
    form.salt.disabled = 1;
    form.submit();
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
   <form method="post" action="authorize.php">
   <input type="hidden" name="salt" value="{SALT}">
   <input type="hidden" name="l_pass" value="">
   <input type="hidden" name="mode" value="chpass">
   <table align="center" width="600px" border=0 cellpadding=0 cellspacing=0>
   <tr>
    <td height=18 style="border-bottom:1px solid #666666;"><img src="images/table_l.gif"></td>
    <td width=418 height=18 style="background: url(images/table_c.gif);border-bottom:1px solid #666666;">&nbsp;</td>
    <td height=18 style="border-bottom:1px solid #666666;"><img src="images/table_r.gif"></td>
   </tr>
   <tr><td colspan=3 style="border-right:1px solid #666666; border-left: 1px solid #666666;">
     <p class="simple_text">
       Изменение пароля для доступа к абонентскому интерфейсу управления 
     </p>
   </td></tr>
   <tr>
    <td colspan=3 style="border-right:1px solid #666666; border-left: 1px solid #666666;">
     <table align="center" cellpadding=3>
         <tr>
           <td class="auth_text">Старый пароль</td><td><input type="password" name="pass" class="input_text"></td>
         </tr>
         <tr>
           <td class="auth_text">Новый пароль</td><td><input type="password" name="n_pass" class="input_text"></td>
         </tr>
         <tr>
           <td class="auth_text">Подтвердите пароль</td><td><input type="password" id="pass1" class="input_text"></td>
         </tr>
         <tr>
           <td colspan="2" align="center"><input class="input_button" type="button" value="Продолжить" onclick="javascript:send_data(this.form)"></td>
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