<?php
require_once('./system.php');
require_once(MCR_ROOT.'instruments/ajax.php');

$rcodes  = array();  

function CanRegister() {
global $link,$bd_names;

	$ip = $_SERVER['REMOTE_ADDR']; //если нужен реальный адрес пользователя используйте GetRealIp
	$result = BD("SELECT ban_until FROM {$bd_names['ip_banning']} WHERE IP='$ip'"); 
	$line = mysql_fetch_array($result);
	
	if ( ($line != NULL) and ($line['ban_until']!='0000-00-00 00:00:00') )
	{
		mysql_close( $link );
		return false;
	}
	
	return true;					
}

function tryExit() {
global $rcodes;
    
    $message = '';
    $rnum    = sizeof($rcodes);
    if (!$rnum) return;

        for ($i=0; $i < $rnum; $i++) {

        $modifed = true;

			switch ($rcodes[$i]) {
                case 2 :  $message .= 'Логин введен некорректно.'; break;
                case 3 :  $message .= 'Пароль введен некорректно.'; break;
				case 4 :  $message .= 'Повтор пароля введен некорректно.'; break;
                case 12 : $message .= 'Emai\'l введен некорректно.'; break;
                case 6 :  $message .= 'Логин должен содержать не меньше 4 символов и не больше 8.'; break;
                case 7 :  $message .= 'Пароль должен содержать не меньше 4 символов и не больше 15.'; break;
                case 8 :  $message .= 'Повтор пароля должен содержать не меньше 4 символов и не больше 15.'; break;
                case 9 :  $message .= 'Пароли не совпадают.'; break;
                case 13 : $message .= 'Почтовый адресс должен содержать не больше 50 символов.'; break;
                default : $modifed = false; break;
            }	

        if ($modifed) $message .= "<br />";	

        }

    aExit(2, $message ); 
}

if (isset($_GET['verificate']) and isset($_GET['id'])) {

	$tmp_user = new User((int)$_GET['id'], $bd_users['id']);
	
	if ($tmp_user->id() and !strcmp($tmp_user->getVerificationStr(),$_GET['verificate'])) $tmp_user->changeGroup(1);
	
    header("Location: ".BASE_URL);
}

RefreshBans();

if (isset($_POST['login']) and isset($_POST['pass']) and isset($_POST['repass']) and isset($_POST['email']) and isset($_POST['female']) ) {
	
	$login  = TextBase::MySQLCompatible($_POST['login']);
	$pass   = TextBase::MySQLCompatible($_POST['pass']);
	$repass = TextBase::MySQLCompatible($_POST['repass']);	
	
    $female = (!(int)$_POST['female'])? 0 : 1;
	
	$email  = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL); 
	
    if (!CanRegister()) aExit(11,'Регистрация временно запрещена.');
	
    if (empty($login) || empty($pass) || empty($repass) || empty($_POST['email'])) aExit(1,'Не все поля заполнены.');
    
	if (!preg_match("/^[a-zA-Z0-9_-]+$/", $login))  $rcodes[] = 2; 
    if (!preg_match("/^[a-zA-Z0-9_-]+$/", $pass))   $rcodes[] = 3;
    if (!preg_match("/^[a-zA-Z0-9_-]+$/", $repass)) $rcodes[] = 4;
	if (!$email)                                    $rcodes[] = 12;      

    tryExit();
	
    $result = BD("SELECT COUNT(*) FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$login'");
	$line   = mysql_fetch_array($result, MYSQL_NUM );
	
	if ($line[0]) aExit(5, 'Пользователь с таким именем уже существует.');
	
    $result = BD("SELECT COUNT(*) FROM `{$bd_names['users']}` WHERE `{$bd_users['email']}`='$email'");
	$line   = mysql_fetch_array($result, MYSQL_NUM );	
	
	if ($line[0]) aExit(15, 'Почтовый адресс уже используется другим пользователем.');

	if ((strlen($login) < 4)  or (strlen($login) > 15))  $rcodes[] = 6;
	if ((strlen($pass) < 4)   or (strlen($pass) > 15))   $rcodes[] = 7;
	if ((strlen($repass) < 4) or (strlen($repass) > 15)) $rcodes[] = 8;
    if (strlen($email) > 50)   $rcodes[] = 13;
	if (strcmp($pass,$repass)) $rcodes[] = 9;			

    tryExit();		
	
	require_once(MCR_ROOT.'instruments/password.php');
	
	$verification = ((int)sqlConfigGet('email-verification'))? true : false;
	
	if ($verification) $group = 4;
	else $group = 1;
	
	if (!BD("INSERT INTO `{$bd_names['users']}` (`{$bd_users['login']}`,`{$bd_users['password']}`,`{$bd_users['ip']}`,`{$bd_users['female']}`,`{$bd_users['email']}`,`{$bd_users['ctime']}`,`{$bd_users['group']}`) VALUES('$login','".createPass($pass)."','".GetRealIp()."',$female,'$email',NOW(),'$group')"))
	  aExit(14);

	$tmp_user = new User(mysql_insert_id(), $bd_users['id']);
	$tmp_user->setDefaultSkin();	

    $next_reg = (int) sqlConfigGet('next-reg-time');	
	 
	if ($next_reg  > 0) 
	BD("INSERT INTO `{$bd_names['ip_banning']}` (`IP`,`time_start`,`ban_until`) VALUES ('".$_SERVER['REMOTE_ADDR']."',NOW(),NOW()+INTERVAL $next_reg HOUR)");
	
	if (!$verification)
		aExit(0,'Регистрация успешно завершена. <a href="#" class="btn" onclick="Login();">Войти</a>');						   			    
	else {	
		
		$subject = 'Подтверждение регистрации '.$_SERVER['SERVER_NAME'];
	   
		$headers   = array();
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/plain; charset=utf-8";
		$headers[] = "From: Registration confirmation <noreplay@noreplay.ru>";
		$headers[] = "Subject: {$subject}";
		$headers[] = "X-Mailer: PHP/".phpversion();
		
		$message = 'Для завершения регистрации необходимо пройти по ссылке. http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?id='.$tmp_user->id().'&verificate='.$tmp_user->getVerificationStr();
		
        $message = TextBase::WordWrap($message, 70);
		
		if ( !mail($email, $subject, $message, implode("\r\n", $headers)) ) aExit(14, 'Ошибка отправки подтверждения на почтовый ящик.');
	
	    aExit(0,'Аккаунт успешно создан. Перейдите по ссылке, отправленой на ваш почтовый ящик для завершения регистрации.');
	}
	unset($tmp_user);
}
?>