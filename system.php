<?php
define('MCR', 1);  
define('MCR_ROOT', dirname(__FILE__).'/');

if (!file_exists(MCR_ROOT.'config.php')) { header("Location: install/install.php"); exit; }

require(MCR_ROOT.'config.php');
require(MCR_ROOT.'instruments/group.class.php');

define('MCRAFT', MCR_ROOT.$site_ways['mcraft']);
define('MCR_STYLE', './'.$site_ways['style']);

define('STYLE_URL', $site_ways['style']);
define('BASE_URL', $config['s_root']);

/* Файл - system.php

Системные функции - генерация пароля, проверка пароля и др.
Класс пользователя сайта, авторизация пользователя */

//error_reporting(E_ALL);

function BD( $query ) {
    return mysql_query( $query );
}

$link = mysql_connect($config['db_host'].':'.$config['db_port'], $config['db_login'], $config['db_passw']) or die("ОШИБКА MySQL Базы данных. Сервер не отвечает или не удается пройти авторизацию");
        mysql_select_db($config['db_name'],$link) or die("ОШИБКА MySQL Базы данных. Не найдена база данных с именем ".$config['db_name']);
		BD("set character_set_client='utf8'"); 
		BD("set character_set_results='utf8'"); 
		BD("set collation_connection='utf8_general_ci'");  

Class User {
private $id;

private $tmp;
private $permissions;

private $ip;				
private $name;
private $email;	

private $lvl;
private $group;	

private $gender;	
private $female;

	public function User($input,$method) {
	global $bd_users,$bd_names;	
	
		$input  = TextBase::MySQLCompatible($input);
		$method = TextBase::MySQLCompatible($method);
	
	    $sql = "SELECT `{$bd_users['login']}`,
		               `{$bd_users['id']}`,
					   `{$bd_users['tmp']}`,
					   `{$bd_users['ip']}`,
					   `{$bd_users['email']}`,
					   `{$bd_users['female']}`,
					   `{$bd_users['group']}` FROM {$bd_names['users']} WHERE `$method`='$input'";
						   
		$result = BD($sql);			
		if ( !$result or mysql_num_rows( $result ) != 1 ) { $this->id = false; return false; }
			
		$this->permissions = null;	
					 
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
			
		$this->id     = $line[$bd_users['id']];			
		$this->name   = $line[$bd_users['login']];
        $this->group  = $line[$bd_users['group']];
		$this->lvl    = $this->getPermission('lvl');
			
		$this->tmp    = $line[$bd_users['tmp']];
		$this->ip     = $line[$bd_users['ip']];
            
		$this->email  = $line[$bd_users['email']];
			
		/* Пол персонажа */
		$this->gender = (int)$line[$bd_users['female']]; 
        $this->female = ($line[$bd_users['female']])? true : false;
			
	return true;			
	}
	
	public function canPostComment() {
	
	   if (!$this->getPermission('add_comm')) return false;
	    
	/* Интервал по времени 1 минута */
	
		$result = BD("SELECT id FROM comments WHERE user_id='".$this->id."' AND time>NOW()-INTERVAL 1 MINUTE");
		if ( mysql_num_rows( $result ) ) return false;	
		
		return true;
	
	}
	
	public function gameLoginConfirm() {
	global $bd_users,$bd_names;	
	
		if (!$this->id) return false;
	
		BD("UPDATE `{$bd_names['users']}` SET gameplay_last=NOW(),play_times=play_times+1 WHERE `{$bd_users['id']}`='".$this->id."'"); 
		
		return true;
	}
	
	public function gameLogoutConfirm() {
	global $bd_users,$bd_names;
	
		if (!$this->id) return false;
	
		$result = BD("SELECT `{$bd_users['id']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['server']}` IS NOT NULL and `{$bd_users['id']}`='".$this->id."'");
		
		if (mysql_num_rows( $result ) == 1)
				BD("UPDATE `{$bd_names['users']}` SET `{$bd_users['server']}`=NULL WHERE `{$bd_users['id']}`='".$this->id."'"); 
		
		return true;
	}
	
	public function gameLoginLast() {
	global $bd_users,$bd_names;
	
		if (!$this->id) return false;
	
		$result = BD("SELECT gameplay_last FROM `{$bd_names['users']}` WHERE gameplay_last<>'0000-00-00 00:00:00' and `{$bd_users['id']}`='".$this->id."'");
		
		if (mysql_num_rows( $result ) == 1) {
		
			$line = mysql_fetch_array($result);
			
			return $line['gameplay_last'];
			
		} else return false;		

	}
	
	public function getStatisticTime($param) {
	global $bd_users,$bd_names;	
	
		switch ($param) {
		case 'gameplay_last':
		case 'create_time':  
		case 'active_last':    break;
		default: return false; break;
		} 
		
		if (!$this->id) return false;
	
		$result = BD("SELECT $param FROM {$bd_names['users']} WHERE $param<>'0000-00-00 00:00:00' and {$bd_users['id']}='".$this->id."'");
		
		if (mysql_num_rows( $result ) == 1) {
		
			$line = mysql_fetch_array($result);
			
			return $line[$param];
			
		} else return false;		

	}
	
	public function getStatistic() {
	global $bd_users,$bd_names;	
	
		if (!$this->id) return false;
	
		$result = BD("SELECT comments_num,play_times,undress_times FROM `{$bd_names['users']}` WHERE `{$bd_users['id']}`='".$this->id."'");
		
		if (mysql_num_rows( $result ) == 1) {
		
			$line = mysql_fetch_array($result);
			
			return $line;
			
		} else return false;		
	}
	
	public function login($tmp,$ip) {
	global $bd_users,$bd_names;

		if (!$this->id) return false;
	
		BD("UPDATE `{$bd_names['users']}` SET `{$bd_users['tmp']}`='$tmp' WHERE `{$bd_users['id']}`='".$this->id."'");
		BD("UPDATE `{$bd_names['users']}` SET `{$bd_users['ip']}`='".GetRealIp()."' WHERE `{$bd_users['id']}`='".$this->id."'"); 
	
		$this->tmp = $tmp;
		
		return true;
	}
	
	public function logout() {
	global $bd_users,$bd_names;
	
	  if (!isset($_SESSION)) session_start();
	  if (isset($_SESSION)) session_destroy();
	  
	  if (isset($_COOKIE['PRTCookie1'])) { 
	  
		  $cook=TextBase::MySQLCompatible($_COOKIE['PRTCookie1']);
		  
		  BD("UPDATE `{$bd_names['users']}` SET `{$bd_users['tmp']}`='0' WHERE `{$bd_users['tmp']}`='$cook'");
		  
		  setcookie("PRTCookie1","",time()-3600);
			
		  $this->tmp = 0;
		  
	  }
	
	}
	
	/* Ввести валюту на сайте. Обмен данными с сервером по RCON */	
	
    public function getMoney() {
    global $bd_names,$bd_money;
	
    if (!$this->id) return 0;
	
		if ($bd_names['iconomy']) {
		
			$res  = BD("SELECT {$bd_money['money']} FROM {$bd_names['iconomy']} WHERE {$bd_money['login']}='{$this->name()}'");
			$line = mysql_fetch_array($res, MYSQL_NUM);
			
		return $line[0];			
		} 
       
    return 0;
    }	

	public function getSkinFName() {
	global $site_ways;
		return MCRAFT.$site_ways['skins'].$this->name.'.png';
	}
	
	public function getCloakFName() {
	global $site_ways;
		return MCRAFT.$site_ways['cloaks'].$this->name.'.png';
	}

    public function getGroupName() {
	global $bd_names;
	  if (!$this->id) return false; 
	  
	  $result = BD("SELECT `name` FROM `{$bd_names['groups']}` WHERE `id`='{$this->group}'");

      if (!mysql_num_rows( $result )) return 'unnamed';
	  $line   = mysql_fetch_array($result, MYSQL_NUM);

	  return $line[0];
    }
	
	public function deleteSkin() {
	   if (file_exists($this->getSkinFName())) {	   
	    unlink($this->getSkinFName());
	    $this->deleteBuffer();
       }	   
	}
	
	public function deleteCloak() {
	   if (file_exists($this->getCloakFName())) { 
	     unlink($this->getCloakFName());
		 $this->deleteBuffer();
	   }
	}
	
	public function defaultSkinMD5() {

		if (!$this->id) return false;
		
		$def_dir = MCRAFT.'tmp/default_skins/';
		
		if ( $this->isFemale() ) $default_skin_md5 = $def_dir.'md5_female.md5';
		else                     $default_skin_md5 = $def_dir.'md5.md5';

		if ( file_exists($default_skin_md5) ) {

			   $md5 = @file($default_skin_md5); 
		  if ( $md5[0] ) return $md5[0];
		  else { vtxtlog( '[action.php] error while READING md5 cache file. '.$default_skin_md5 ); return false; }		 
		}

		if ( $this->isFemale() ) $default_skin = $def_dir.'Char_female.png';
		else                  	 $default_skin = $def_dir.'Char.png';
		 
		if ( file_exists($default_skin) ) {

				$md5 = md5_file($default_skin);  
		  if ( !$md5 ) { vtxtlog( '[action.php] md5 generate error. '.$default_skin ); return false; }
		  
		  if ( $fp = fopen($default_skin_md5, 'w') ) {    
			if ( !fwrite($fp, $md5) ) vtxtlog( '[action.php] error while SAVE cache file. '.$default_skin_md5 );
			fclose($fp);
		  } else  vtxtlog( '[action.php] error while CREATE cache file. '.$default_skin_md5 );
		  
		  return $md5;  
		  
		} else { vtxtlog( '[action.php] default skin file missing. '.$default_skin ); return false; }
	 
	}	
	
	public function defaultSkinTrigger($new_value = -1) { /* is player use unique skin */
	global $bd_users,$bd_names;
	
	  if (!$this->id) return false;
	
	  if ( $new_value < 0 ) {
	  
	    $result = BD("SELECT default_skin FROM `{$bd_names['users']}` WHERE `{$bd_users['id']}`='{$this->id()}'");
	    $line   = mysql_fetch_array($result, MYSQL_NUM);
		
		$trigger = (int)$line[0];
		
		if ($trigger == 2) {
			
				if ( !file_exists($this->getSkinFName())) $trigger = 1;
			elseif ( !strcmp($this->defaultSkinMD5(),md5_file($this->getSkinFName())) ) $trigger = 1;
			else $trigger = 0;
			
			BD("UPDATE `{$bd_names['users']}` SET default_skin='$trigger' WHERE `{$bd_users['id']}`='{$this->id()}'");
		}
		return ($trigger)? true : false;		
	  }
	  
	  $new_value = ($new_value)? 1 : 0;
      
	  BD("UPDATE `{$bd_names['users']}` SET default_skin='$new_value' WHERE `{$bd_users['id']}`='{$this->id()}'");
	  
	  return ($new_value)? true : false;
	  
	}
	
	public function deleteBuffer() {

	$mini = MCRAFT.'tmp/skin_buffer/'.$this->name.'_Mini.png';
	$skin = MCRAFT.'tmp/skin_buffer/'.$this->name.'.png';

	 if (file_exists($mini)) unlink($mini); 
	 if (file_exists($skin)) unlink($skin); 
	}

    public function setDefaultSkin() {
	
	if (!$this->id) return 0;
	
    $this->deleteSkin(); 
    
	$default_skin = MCRAFT.'tmp/default_skins/Char'.(($this->isFemale())? '_female':'').'.png';

	if ( !copy ( $default_skin , $this->getSkinFName()) ) vtxtlog('[SetDefaultSkin] error while COPY default skin for new user.');
    else $this->defaultSkinTrigger(true);
	
	return 1;
    }
	
	public function changeName($newname) {
	global $bd_names,$bd_users,$site_ways;
	
		if (!$this->id) return 0;
	
		$newname = TextBase::MySQLCompatible(trim($newname));
		
		if (!preg_match("/^[a-zA-Z0-9_-]+$/", $newname)) return 1401;
		
		$result = BD("SELECT `{$bd_users['login']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$newname'");
		
		if (mysql_num_rows($result)) return 1402;
		
		if ((strlen($newname) < 4) or (strlen($newname) > 15)) return 1403;
		
		BD("UPDATE `{$bd_names['users']}` SET `{$bd_users['login']}`='$newname' WHERE `{$bd_users['login']}`='".$this->name."'");
		
		if (!empty($_SESSION['user_name']) and $_SESSION['user_name'] == $this->name) $_SESSION['user_name'] = $newname;
			
		/* Переименование файла скина и плаща */
		
		$way_tmp_old = $this->getSkinFName();
		$way_tmp_new = MCRAFT.$site_ways['skins'].$newname.'.png';
		
		if (file_exists($way_tmp_old) and !file_exists($way_tmp_new)) rename($way_tmp_old, $way_tmp_new);

		$way_tmp_old = $this->getCloakFName();
		$way_tmp_new = MCRAFT.$site_ways['cloaks'].$newname.'.png';
		
		if (file_exists($way_tmp_old) and !file_exists($way_tmp_new)) rename($way_tmp_old, $way_tmp_new);
		
		
		
		$buff_mini     = MCRAFT.'tmp/skin_buffer/'.$this->name.'_Mini.png';
		$buff_mini_new = MCRAFT.'tmp/skin_buffer/'.$newname.'.png';
	    $buff_skin     = MCRAFT.'tmp/skin_buffer/'.$this->name.'.png';
        $buff_skin_new = MCRAFT.'tmp/skin_buffer/'.$newname.'.png';
		
	    if (file_exists($buff_mini)) rename($buff_mini, $buff_mini_new);
	    if (file_exists($buff_skin)) rename($buff_skin, $buff_skin_new);
		
		$this->name = $newname;
		
		return 1;
	
	}
	
	public function changePassword($newpass,$repass = false, $pass = false) {
	global $bd_names,$bd_users;
	
		require_once(MCR_ROOT.'instruments/password.php');
	
		if (!$this->id) return 0;
		
		if (!is_bool($repass)) {

            if (strcmp($repass,$newpass)) return 1504;
		
			$regular = "/^[a-zA-Z0-9_-]+$/";
			
			if (!preg_match($regular, $pass) or !preg_match($regular, $newpass)) return 1501;
			
			$result = BD("SELECT `{$bd_users['password']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='".$this->name."'"); 
			$line   = mysql_fetch_array( $result, MYSQL_NUM );
			 
			if ($line == NULL or !checkPass($line[0], $pass, $this->id)) return 1502;
		
		}
			
		$minlen = 4; $maxlen = 15; $len = strlen($newpass);
		
		if (($len < $minlen) or ($len > $maxlen)) return 1503;
			 
		BD("UPDATE `{$bd_names['users']}` SET `{$bd_users['password']}`='".createPass($newpass)."' WHERE `{$bd_users['login']}`='".$this->name."'"); 
		
		return 1;
	}
	
	public function changeGroup($newgroup) {
	global $bd_users,$bd_names;	
	
		    $newgroup = (int) $newgroup;		
		if ($newgroup < 0) return false;
		
		$result = BD("SELECT `id` FROM {$bd_names['groups']} WHERE `id`='$newgroup'");
		
		if ( !mysql_num_rows( $result ) ) return false;
		
		BD("UPDATE {$bd_names['users']} SET `{$bd_users['group']}`='$newgroup' WHERE `{$bd_users['id']}`='".$this->id."'"); 
		
		$this->group = $newgroup;
		$this->permissions['lvl'] = null;
		$this->lvl   = $this->getPermission('lvl');
		
		return true;
	}
	
	public function changeGender($female) {
	global $bd_users,$bd_names;	
	
		$isFemale = ($female)? 1 : 0;	
	
		BD("UPDATE {$bd_names['users']} SET `{$bd_users['female']}`='$isFemale' WHERE `{$bd_users['id']}`='".$this->id."'"); 
		
		$this->gender = $female;
		$this->female = ($female)? true : false;
		return true;
	}
	
	public function changeEmail($email) {
	global $bd_users,$bd_names;	
	
		     $email = filter_var($email, FILTER_VALIDATE_EMAIL); 	
		if (!$email) return 1901;
		
		$result = BD("SELECT `id` FROM {$bd_names['users']} WHERE `{$bd_users['email']}`='$email'");		
		if ( mysql_num_rows( $result ) ) return 1902;
				
		BD("UPDATE {$bd_names['users']} SET `{$bd_users['email']}`='$email' WHERE `{$bd_users['id']}`='".$this->id."'"); 
		
		$this->email = $email;
		return 1;
	}
	
	public function Delete() {
	global $bd_users,$bd_names;	
	
	    if (!$this->id) return false;
		
		if (!class_exists('Comments_Item')) require_once(MCR_ROOT.'instruments/catalog.class.php');
		
	    $this->deleteCloak();
		$this->deleteSkin();
		$this->deleteBuffer();			
					
		$result = BD("SELECT `id` FROM `{$bd_names['comments']}` WHERE `user_id`='".$this->id."'"); 
		if ( mysql_num_rows( $result ) != 0 ) {
	  
		  while ( $line = mysql_fetch_array( $result, MYSQL_NUM ) ) {
		  		
				$comment_del = new Comments_Item($line[0]);
				$comment_del->Delete(); 
				unset($comment_del);
		  }
		}
		
		BD("DELETE FROM `{$bd_names['users']}` WHERE `{$bd_users['id']}`= '".$this->id()."'");

        $this->id = false;		
		return true;		
	}
	
	public function getVerificationStr() {	
	if (!$this->id) return false;
	
    $salt = sqlConfigGet('email-verification-salt');
	
	if (!$salt) {		
		$salt = randString();
		sqlConfigSet('email-verification-salt',$salt); 	
		}
	
	return md5($this->id().$salt);	
	}
	
	public function getPermission($param) {
	global $bd_names;	
	
	if (isset($this->permissions[$param])) return $this->permissions[$param];
	if (!$this->id) return false;
	
	$group = new Group($this->group);
	$value = $group->GetPermission($param);
	
	unset($group);
	
	if ((int)$value == -1) return false;

	$this->permissions[$param] = $value;
	
	return $value;		
	}

    public function isFemale() {
        return $this->female;
    }	
	
	public function gender() {
		return $this->gender;
	}
	
	public function id() {
		return $this->id;
	}
	
	public function lvl() {
		return $this->lvl;
	}	
	
	public function tmp() {
		return $this->tmp;
	}
	
	public function ip() {
		return $this->ip;
	}	
	
	public function email() {
		return $this->email;
	}
	
	public function group() {
		return $this->group;
	}
	
	public function name() {
		return $this->name;
	}
}

/* Системные функции */

function tmp_name($folder, $pre = '', $ext = 'tmp'){
      $name  = $pre.time().'_';
	  
      for ($i=0;$i<8;$i++) $name .= chr(rand(97,121));
	  
      $name .= '.'.$ext;
	  
      return (file_exists($folder.$name))? tmp_name($folder,$pre,$ext):$name;
}

function ratio($file, $baze = 64, $prop = 2) {
$input_size = @getimagesize($file);

if (empty($input_size)) return false;

if (round($input_size[0] / $input_size[1], 2) != round($prop,2)) return false;
else if ($input_size[0] < $baze) return false;

$mp = $input_size[0] / $baze;

return $mp;
}

function POSTGood($post_name, $format = 'png') {

if ( empty($_FILES[$post_name]['tmp_name']) or 
     $_FILES[$post_name]['error'] != UPLOAD_ERR_OK or
	 !is_uploaded_file($_FILES[$post_name]['tmp_name']) or 
	 substr($_FILES[$post_name]['name'], 1 + strrpos($_FILES[$post_name]['name'], ".")) != $format
   ) return false;
   
return true;
}

function POSTUpload($post_name, $final_way, $baze = 64, $prop = 2) {
global $user;

if (empty($user) or $user->lvl() <= 0) return 1;

$tmp_dir = MCRAFT.'tmp/';

if (!is_dir($tmp_dir)) mkdir($tmp_dir, 0777); 

$tmp_file = $tmp_dir.tmp_name($tmp_dir);
if (!move_uploaded_file( $_FILES[$post_name]['tmp_name'], $tmp_file )) { 

vtxtlog('[Ошибка модуля загрузки] Убедитесь, что папка "'.$tmp_dir.'" доступна для ЗАПИСИ.');
exit; 
}

$fsize = round($_FILES[$post_name]['size'] / 1024);
	
if ( (int) $user->getPermission('max_fsize') < $fsize ) 
	
	{ unlink($tmp_file); return 1601; }
  
$input_ratio = ratio($tmp_file, $baze, $prop);
		
if (!$input_ratio or $input_ratio > (int) $user->getPermission('max_ratio')) 	

    { unlink($tmp_file); return 1602; }
	
if (file_exists($final_way)) unlink($final_way);

if (rename( $tmp_file, $final_way )) chmod($final_way,0777);
else { vtxtlog('[Ошибка модуля загрузки] Убедитесь, что папка "'.$tmp_dir.'" доступна для ЧТЕНИЯ.');  exit; }

return 1;

}

function randString( $pass_len = 50 ) {
    $allchars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $string = "";
    
    mt_srand( (double) microtime() * 1000000 );
    
    for ( $i=0; $i<$pass_len; $i++ )
	$string .= $allchars{ mt_rand( 0, strlen( $allchars )-1 ) };
	
    return $string;
}

function sqlConfigGet($type){
global $bd_names;
	
    switch($type){
	case 'latest-game-build':
	case 'rcon-port':
	case 'rcon-serv':	
	case 'rcon-pass':
	case 'next-reg-time':
	case 'email-verification':
	case 'email-verification-salt':
	case 'launcher-version':  break;
	default : return false;
	}
	
    $result = BD("SELECT `value` FROM `{$bd_names['data']}` WHERE `property`='$type'");   

    if ( mysql_num_rows( $result ) != 1 ) return false;
	
	$line = mysql_fetch_array($result, MYSQL_NUM );
	
	return $line[0];		
}

function sqlConfigSet($type,$value) {
global $bd_names;

    switch($type){
	case 'latest-game-build':
	case 'rcon-port':
	case 'rcon-pass':
	case 'rcon-serv':
    case 'next-reg-time':	
	case 'email-verification':
	case 'email-verification-salt':
	case 'launcher-version': break;
	default : return false;
	}
	
	$value = TextBase::MySQLCompatible($value);
	
    $result = BD("UPDATE `{$bd_names['data']}` SET `value`='$value' WHERE `property`='$type'"); 
	if (mysql_affected_rows()) return true;
	else {
		$result = BD("INSERT INTO `{$bd_names['data']}` (value,property) VALUES ('$value','$type')");
		if (mysql_affected_rows()) return true;
	}
	
	return false;
}

function GetRealIp(){
 if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip=$_SERVER['HTTP_CLIENT_IP']; 
 elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
 else $ip=$_SERVER['REMOTE_ADDR'];
 return $ip;
}

function RefreshBans() {
global $bd_names;

	BD("DELETE FROM {$bd_names['ip_banning']} WHERE (ban_until='0000-00-00 00:00:00') AND (time_start<NOW()-INTERVAL ".((int) sqlConfigGet('next-reg-time'))." HOUR)");
	BD("DELETE FROM {$bd_names['ip_banning']} WHERE (ban_until<>'0000-00-00 00:00:00') AND (ban_until<NOW())");					
}

function vtxtlog($string) {
global $config;

/* Если ajax - то выводить в message */

if (!$config['log']) return;

$log_file = MCR_ROOT.'log.txt';

	if (file_exists($log_file) and round(filesize ($log_file) / 1048576) >= 50) unlink($log_file);
	
	if ( !$fp = fopen($log_file,'a') ) exit('[system.php] Ошибка открытия файла '.$log_file.' убедитесь, что файл доступен для ЗАПИСИ');
	
	fwrite($fp,date("H:i:s d-m-Y").' < '.$string.PHP_EOL); 
	fclose($fp);	
}

/* Проверяем сессию на сайте */

$user = false;

if (!session_id() and isset($_GET['sid'])) session_id($_GET['sid']);
			
if (!isset($_SESSION)) session_start();

if (isset($_SESSION['user_name']) AND $_SESSION['ip'] == $_SERVER['REMOTE_ADDR']) {
     $user = new User($_SESSION['user_name'],$bd_users['login']);
if (!$user->id()) $user = false;
} 

if (isset($_COOKIE['PRTCookie1']) and empty($user)) { 
	
  $user = new User($_COOKIE['PRTCookie1'],'tmp');
  
	if (!$user->id()) {
		$user = false;
		setcookie("PRTCookie1","",time(), '/');
	} else {
		if (!isset($_SESSION)) session_start();
		$_SESSION['user_name'] = $user->name();
		$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
	}	
}

if (!empty($user)) {
	
	if (!strncmp($user->tmp(),'ipcheck_',8) and GetRealIp() != $user->ip()) $user = false;
	elseif ($user->lvl() <= 0) {$user->logout(); $user = false;}

}
?>