<?php
require_once('../system.php');

function generateSessionId(){
    // generate rand num
    srand(time());
    $randNum = rand(1000000000, 2147483647).rand(1000000000, 2147483647).rand(0,9);
    return $randNum;
}

if (empty($_POST['user']) or empty($_POST['password']) or empty($_POST['version'])) {
  vtxtlog("[auth.php] login process [POST parameter empty] [ ".((empty($_POST['user']))? 'LOGIN ':'').((empty($_POST['password']))? 'PASSWORD ':'').((empty($_POST['version']))? 'VER ':'')."]");
  exit("Bad login");
}		
	require_once(MCR_ROOT.'instruments/password.php');

	$login 		= TextBase::MySQLCompatible($_POST['user']); 
	$password 	= TextBase::MySQLCompatible($_POST['password']); 
	$ver 		= TextBase::MySQLCompatible($_POST['version']);

	if (!preg_match("/^[a-zA-Z0-9_-]+$/", $login)    or
		!preg_match("/^[a-zA-Z0-9_-]+$/", $password) or
	    !preg_match("/^[0-9]+$/", $ver)) {		
		
		vtxtlog("[auth.php] error while login process [auth info login ".$login." pass ".$password." ver ".$ver."]");
		exit("Bad login"); 
	} 
    
	if ((int)sqlConfigGet('launcher-version') != (int)$ver) {
	    vtxtlog("[auth.php] login process [Old version] ver ".$ver);
		exit("Old version");
	}
	$result = BD("SELECT `{$bd_users['password']}`,`{$bd_users['login']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$login'"); 
    $line = mysql_fetch_array( $result, MYSQL_NUM );
 
    if ( !$result or !mysql_num_rows( $result ) ) {
		
			vtxtlog("[auth.php] login process [Unknown user] User [$login] Password [$password]");
			exit("Bad login");
	}
	
	$auth_user = new User($login,$bd_users['login']);	
	if ( $auth_user->lvl() <= 1 ) exit("Bad login");
	//unset($auth_user);
	
	if ( !checkPass($line[0], $password, $auth_user->id())) {
			
		vtxtlog("[auth.php] login process [Bad login] User [$login] Password [$password]");
		exit("Bad login");
	}

    $sessid = generateSessionId();
    BD("UPDATE `{$bd_names['users']}` SET `{$bd_users['session']}`='$sessid' WHERE `{$bd_users['login']}`='$login'");
    vtxtlog("[auth.php] login process [Success] User [$login] Session [$sessid]");			
		
exit(sqlConfigGet('latest-game-build').':'.md5($line[1]).':'.$line[1].':'.$sessid.':');
?>