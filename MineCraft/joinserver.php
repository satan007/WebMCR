<?php
require_once('../system.php');

if (empty($_GET['sessionId']) or empty($_GET['user']) or empty($_GET['serverId'])) {

vtxtlog("[joinserver.php] join process [GET parameter empty] [ ".((empty($_GET['sessionId']))? 'SESSIONID ':'').((empty($_GET['user']))? 'USER ':'').((empty($_GET['serverId']))? 'SERVERID ':'')."]");
exit('Bad login');
}	 

$login 		= TextBase::MySQLCompatible($_GET['user']); 
$serverid	= TextBase::MySQLCompatible($_GET['serverId']);
$sessionid	= TextBase::MySQLCompatible($_GET['sessionId']);

if (!preg_match("/^[a-zA-Z0-9_-]+$/", $login) or 
	!preg_match("/^[0-9]+$/", $sessionid) or
	!preg_match("/^[a-z0-9_-]+$/", $serverid)) {
		
	vtxtlog("[joinserver.php] error while login process [input login ".$login." sessionid ".$sessionid." serverid ".$serverid."]");
	exit('Bad login'); 		
}	

$result = BD("SELECT `{$bd_users['login']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['session']}`='$sessionid' AND `{$bd_users['login']}`='$login' AND `{$bd_users['server']}`='$serverid'");

if( mysql_num_rows($result) == 1 ){
	vtxtlog('[joinserver.php] join Server [Result] Relogin OK'); 
	exit('OK');
} 

$result = BD("UPDATE `{$bd_names['users']}` SET `{$bd_users['server']}`='$serverid' WHERE `{$bd_users['session']}`='$sessionid' AND `{$bd_users['login']}`='$login'");

if(mysql_affected_rows() == 1){
	vtxtlog('[joinserver.php] join Server [Result] login OK'); 
	exit('OK');
}

vtxtlog("[joinserver.php] join Server [Result] Bad Login - input Session [$sessionid] User [$login] Server [$serverid]");
exit('Bad login');
?>