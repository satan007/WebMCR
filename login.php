<?php
require_once('./system.php');
require_once(MCR_ROOT.'instruments/ajax.php');

if (isset($_GET['out'])) {

  header("Location: ".BASE_URL);
  if (empty($user)) exit;
  
  $user->logout();  
  exit;
} elseif (isset($_POST['login'])) {

 $name = TextBase::MySQLCompatible($_POST['login']);
 $pass = TextBase::MySQLCompatible($_POST['pass']);   
if ($config['c_hash'] == 'webmcr' || $config['c_hash'] == 'wordpress' || $config['c_hash'] == 'dle')
{
 $result = BD("SELECT `{$bd_users['password']}`,`{$bd_users['id']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$name' OR `{$bd_users['email']}`='$name'"); 
 }
if ($config['c_hash'] == 'ipb')
{
 $result = BD("SELECT `{$bd_users['password']}`,`{$bd_users['id']}`,`{$config['c_salt']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$name' OR `{$bd_users['email']}`='$name'"); 
}
 if ( !$result or !mysql_num_rows( $result ) ) { mysql_close( $link ); aExit(4,'Пользователь с таким именем или e-mail\'ом не существует.'); } 
 require_once(MCR_ROOT.'instruments/password.php');
 
 $line = mysql_fetch_array( $result, MYSQL_NUM);
 
  
 if ( !$config['c_hash']($line[0], $pass, $line[1]) ) {  mysql_close( $link ); aExit(1,'Неверный пароль.<br /> <a href="#" style="color: #656565;" onclick="RestoreStart(); return false;">Восстановить пароль ?</a>'); }
 
 $user = new User($line[1],$bd_users['id']);
 
 if ($user->lvl() <= 0) {
 
	unset($user);
	mysql_close( $link );
    aExit(4,'Ваш аккаунт заблокирован.');	
 }

 if (!isset($_SESSION)) session_start();
 
 $tmpID = randString( 15 );
 
 if (isset($_POST['ipcheck'])) $tmpID = 'ipcheck_'.$tmpID;
 
 setcookie( "PRTCookie1", "$tmpID", time() + 60 * 60 * 24 * 30 * 12, '/');
  
 $user->login($tmpID,GetRealIp());
 
 $_SESSION['user_id']   = $user->id();
 $_SESSION['user_name'] = $user->name();
 $_SESSION['ip']        = $user->ip();
  
 aExit(0);	  
}
?>