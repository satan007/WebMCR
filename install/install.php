<?php
if (file_exists('../config.php')) include '../config.php'; else include './config/config.php';
if (!$config['install']) { header("Location: ../index.php"); exit; }

// error_reporting(E_ALL);
define('MCR', 1);  
define('MCR_ROOT', '../');
define('MCR_STYLE', '../'.$site_ways['style']);
define('STYLE_URL', '../'.$site_ways['style']);
define('BASE_URL', '');

require_once(MCR_ROOT.'instruments/base.class.php');

$page = 'Настройка '.PROGNAME;
$content_advice = 'Заполните форму для завершения установки '.PROGNAME;
$content_servers = '';
$content_side = Menager::ShowStaticPage('./style/install_side.html');

$addition_events = '';
$info = '';  $cErr = '';
$info_color = 'alert-error'; //alert-success

$menu = new Menu(MCR_STYLE);
$menu->AddItem($page,'install.php',true); 

$content_menu = $menu->Show();

if (!empty($_POST['step'])) $step = (int) $_POST['step']; else $step = 1;

function BD( $query ) {
    $result = mysql_query( $query );
    return $result;
}

function Root_url(){
	$root_url = str_replace('\\', '/', $_SERVER['PHP_SELF']); 
	$root_url = explode("install/install.php", $root_url, -1);
	if (sizeof($root_url)) return $root_url[0];
	else return '/';
}

function Mode_rewrite(){
	
	if (function_exists('apache_get_modules')) {
	  
	  $modules = apache_get_modules();
	  return in_array('mod_rewrite', $modules);
	  
	} else return getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;	
	return false;
}

function SaveOptions() {
global $config,$bd_names,$bd_money,$bd_users,$site_ways,$info;

$txt  = '<?php'.PHP_EOL;
$txt .= '$config = '.var_export($config, true).';'.PHP_EOL;
$txt .= '$bd_names = '.var_export($bd_names, true).';'.PHP_EOL;
$txt .= '$bd_users = '.var_export($bd_users, true).';'.PHP_EOL;
$txt .= '/* iconomy or some other plugin, just check names */'.PHP_EOL;
$txt .= '$bd_money = '.var_export($bd_money, true).';'.PHP_EOL;
$txt .= '$site_ways = '.var_export($site_ways, true).';'.PHP_EOL;
$txt .= '/* Put all new config additions here */'.PHP_EOL;
$txt .= '?>';

$result = file_put_contents("../config.php", $txt);

if (is_bool($result) and $result == false) {

$info = 'Файл '.MCR_ROOT.'config.php (корневая дирректория сайта) не существует \ защищен от записи \ папка содержащая файл не доступна для записи. Настройки не были сохранены.';	
return false;
}

return true;
}

function DBConnect() {
global $link,$config;

$link = mysql_connect($config['db_host'].':'.$config['db_port'], $config['db_login'],  $config['db_passw'] );
if (!$link) return 1;
if (!mysql_select_db($config['db_name'],$link)) return 2;

mysql_query ("set character_set_client = 'utf8'"); 
mysql_query ("set character_set_results = 'utf8'"); 
mysql_query ("set collation_connection = 'utf8_general_ci'");  

return false;
}

function ConfigPostStr($postKey){
 return (isset( $_POST[$postKey]))? TextBase::HTMLDestruct($_POST[$postKey]) : '';
}

function ConfigPostInt($postKey){
 return (isset( $_POST[$postKey]))? (int)$_POST[$postKey] : 0;
}

if (isset($_POST['step']))

switch ($step) {
	case 1:     
	$mysql_port     = ConfigPostInt('mysql_port');
	$mysql_adress   = ConfigPostStr('mysql_adress');
	$mysql_bd       = ConfigPostStr('mysql_bd');
	$mysql_user     = ConfigPostStr('mysql_user');
	$mysql_password = ConfigPostStr('mysql_password');
	$mysql_rewrite  = (empty($_POST['mysql_rewrite']))? false : true;
	
		if ( !$mysql_port ) $info = 'Укажите порт для подключения к БД.';
	elseif ( !TextBase::StringLen($mysql_adress) ) $info = 'Укажите адресс сервера MySQL.';
	elseif ( !TextBase::StringLen($mysql_user) )   $info = 'Укажите пользователя для подключения к MySQL серверу.';
	else {
		
		$config['db_host']  = $mysql_adress   ; 
		$config['db_port']  = $mysql_port     ;
		$config['db_name']  = $mysql_bd       ; 
		$config['db_login'] = $mysql_user     ;
		$config['db_passw'] = $mysql_password ;
		
				$connect_result = DBConnect();	
			if ($connect_result == 1) $info = 'Данные для подключения к БД не верны. Возможно не правильно указан логин и пароль.';
		elseif ($connect_result == 2) $info = 'Не найдена база данных с именем '.$mysql_bd;
		else {
		    
			$config['rewrite'] = Mode_rewrite();
			$config['s_root']  = Root_url();
			
			if (SaveOptions()) $step = 2; 		
			include './sql/sql.php';	
			include './18_fix.php';
		}		
	}	
	break;
	
	case 2:
	$cms_hash		 	 = ConfigPostStr('cms_hash');
	$config['c_hash']	  = $cms_hash  ;
	if (SaveOptions())if ( $cms_hash=='webmcr') $step = 21;
	elseif ( $cms_hash=='wordpress'||$cms_hash=='dle') $step = 22;
	elseif ( $cms_hash=='ipb') $step = 23;
	include "./config/config_$cms_hash.php";
	break;
	
	case 21:
	if (SaveOptions()) $step = 3;
	include './sql/sql_webmcr.php';
	break;

	
	case 22:
	$cms_users  		 = ConfigPostStr('cms_users');
	$cms_id  		 	 = ConfigPostStr('cms_id');
	$cms_login  		 = ConfigPostStr('cms_login');
	$cms_email  		 = ConfigPostStr('cms_email');
	$cms_password  		 = ConfigPostStr('cms_password');
	$bd_names['users']	  = $cms_users  ;
	$bd_users['id']	 	  = $cms_id  ;
	$bd_users['login']	  = $cms_login  ;
	$bd_users['email']	  = $cms_email  ;
	$bd_users['password'] = $cms_password  ;
	if (SaveOptions()) $step = 31;
	include '../install/sql/sql_wordpress.php';
	break;
	
		
	case 23:
	$cms_users  		 = ConfigPostStr('cms_users');
	$cms_id  		 	 = ConfigPostStr('cms_id');
	$cms_login  		 = ConfigPostStr('cms_login');
	$cms_email  		 = ConfigPostStr('cms_email');
	$cms_password  		 = ConfigPostStr('cms_password');
	$cms_salt  		 	 = ConfigPostStr('cms_salt');
	$bd_names['users']	  = $cms_users  ;
	$bd_users['id']	 	  = $cms_id  ;
	$bd_users['login']	  = $cms_login  ;
	$bd_users['email']	  = $cms_email  ;
	$bd_users['password'] = $cms_password  ;
	$config['c_salt']   = $cms_salt;
	if (SaveOptions()) $step = 31;
				include "./sql/sql_$cms_hash.php";	
	break;
	
	case 3:
	$site_user       = ConfigPostStr('site_user');
	$site_password   = ConfigPostStr('site_password');
	$site_repassword = ConfigPostStr('site_repassword');
	
	    if ( !TextBase::StringLen($site_user)		) $info = 'Укажите имя пользователя.';
	elseif ( !TextBase::StringLen($site_password)	) $info = 'Введите пароль.';
	elseif ( !TextBase::StringLen($site_repassword)	) $info = 'Введите повтор пароля.';
	elseif ( strcmp($site_password,$site_repassword)) $info = 'Пароли не совпадают.';
	else {
		      $connect_result = DBConnect();
		if (  $connect_result ) { $info = 'Ошибка настройки соединения с БД.'; break; }

			  $result = BD("SELECT `{$bd_users['login']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$site_user'");
			  
		if ( mysql_num_rows($result) ) BD("DELETE FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$site_user'");
		
		require_once(MCR_ROOT.'instruments/password.php');
	
		BD("INSERT INTO `{$bd_names['users']}` (`{$bd_users['login']}`,`{$bd_users['password']}`,`{$bd_users['ip']}`,`{$bd_users['group']}`) VALUES('$site_user','".createPass($site_password)."','127.0.0.1',3)");
		$step = 4; 			
	}	
	break;
	
	case 31:
	if (SaveOptions()) $step = 4;
	break;
	
	case 4:
	$site_name   = ConfigPostStr('site_name' );
	$site_about  = ConfigPostStr('site_about');
	$keywords    = ConfigPostStr('site_keyword');
	$theme    = ConfigPostStr('theme');	
	$sbuffer     = (!empty($_POST['sbuffer']))? true : false;	
	
	if ( TextBase::StringLen($keywords) > 200 ) $info = 'Ключевые слова занимают больше 200 символов ('.TextBase::StringLen($keywords).').';
    else {
	
	$site_ways['style']     = $theme  ;
	$config['s_name']     = $site_name  ;
	$config['s_about']    = $site_about ; 	
	$config['s_keywords'] = $keywords   ;	
	$config['sbuffer']    = $sbuffer    ;		

	$config['install']    = false; 
	
	if (SaveOptions()) $step = 5; 		
	}
	break;
}

if ( !extension_loaded('gd')      ) $cErr  = 'Библиотека GD не подключена, пользователь не сможет увидеть загруженый скин \ плащ в профиле.<br/>';
if ( ini_get('register_globals')  ) $cErr .= 'Нарушение безопасности. Файл php.ini настроек PHP [Опция] <b>register_globals = On</b>. Привести в значение <b>Off</b><br/>';
if ( !function_exists('fsockopen')) $cErr .= 'Функция fsockopen недоступна. Подключиться и проверить состояние игрового сервера будет невозможно.<br/>';
if ( !function_exists('json_encode')) $cErr .= 'Функция json_encode недоступна. Авторизация на сайте будет недоступна.<br/>'; 

ob_start(); 

if ($info) include './style/info.html'; 
if ($cErr) {
	$info = $cErr;
	$info_color = 'alert-error';
	include './style/info.html'; 
}

switch ($step) {
	case 1: include './style/install.html'; break;
	case 2: include './style/install_cms.html'; break;
	case 21: include './style/install_cms_webmcr.html'; break;
	case 22: include './style/install_cms_other.html'; break;
	case 23: include './style/install_cms_ipb.html'; break;
	case 3: include './style/install_user.html'; break;
	case 31: include './style/install_user_cms.html'; break;
	case 4: include './style/install_constants.html'; break;
	default: include './style/other.html'; break;
}


$content_main = ob_get_clean();

include_once MCR_STYLE.'index.html';
?>