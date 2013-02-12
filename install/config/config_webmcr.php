<?php 
$config = array (

/* MySQL connection */

  'db_host' => 'localhost',
  'db_port' => 3306,
  'db_login' => 'root',
  'db_passw' => '',
  'db_name' => 'mcraft',

/* site constants */
  
  's_name' => 'MCR 2.0c',
  's_about' => 'Личный кабинет для онлайн сервера игры Minecraft',
  's_keywords' => 'сервер игра онлайн NC22 Minecraft',
  's_dpage' => 'news',  
  's_root'  => '/',
  's_llink_win' => 'launcher_win.zip',
  's_llink_lin' => 'launcher_lin.zip',
  
  'news_by_page' => 5,
  'comm_by_page' => 5,  
  'comm_revers' => false,
  'game_news'	 => 1,
  
/* system */

  'sbuffer' => true,  
  'rewrite' => true,
  'log' => false,  
  
  'install' => true,
);
  
$site_ways = array (
  'style' => 'style/Original/',
  'mcraft' => 'MineCraft/',
  'skins' => 'MinecraftSkins/',
  'cloaks' => 'MinecraftCloaks/',
  'distrib' => 'MinecraftDownload/',
);
  
$bd_names = array (
  'users' => 'accounts',
  'ip_banning' => 'ip_banning',
  'news' => 'news',
  'news_categorys' => 'news_categorys',
  'groups' => 'groups',
  'data' => 'data',
  'comments' => 'comments', 
  'servers' => 'servers',
  'iconomy' => false,
);

$bd_money = array ( /* iconomy or some other plugin, just check names */
  'login' => 'username',
  'money' => 'balance',
);

$bd_users = array (
  'login' => 'login',
  'id' => 'id',
  'password' => 'password',
  'ip' => 'ip',
  'email' => 'email',
  'female' => 'female',
  'group' => 'group',
  'lvl' => 'lvl',
  'tmp' => 'tmp',
  'session' => 'session',
  'server' => 'server',
  'ctime' => 'create_time',
);

/* Put all new config additions here */
?>