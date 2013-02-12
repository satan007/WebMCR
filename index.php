<?php
header('Content-Type: text/html; charset=UTF-8');

// $start_time = microtime(true); $mem_use = memory_get_usage();
require_once('./system.php');

function GetRandomAdvice() {
	return ($quotes = @file(MCR_STYLE.'sovet.txt'))? $quotes[rand(0, sizeof($quotes)-1)] : "Советов нет"; 
}

$menu = new Menu();
$menu_items['main'] = $menu->AddItem('Главная','',true);

if (!empty($user)) {

  if ($user->getPermission('add_news')) $menu_items['add_news'] = $menu->AddItem('Добавить новость', '?mode=news_add');
  if ($user->lvl() >= 15)               $menu_items['admin']    = $menu->AddItem('Управление', '?mode=control');
  if ($user->lvl() > 0)                 $menu_items['options']  = $menu->AddItem('Настройки', '?mode=options');
  
  $menu_items['exit'] = $menu->AddItem('Выход','login.php?out=1');
}
/* Player base html info init */

if (!empty($user)) {

$player       = $user->name();
$player_id    = $user->id();
$player_lvl   = $user->lvl();
$player_email = $user->email(); if (empty($player_email)) $player_email = 'Отсутствует'; 
$player_group = $user->getGroupName();
$player_money = $user->getMoney();
}

$content_main = ''; $content_side = ''; $addition_events = ''; $content_advice = GetRandomAdvice(); $mode = null;

if ( isset($_GET["mode"]) ) $mode = $_GET["mode"]; 
if ( isset($_GET["id"]) )   $mode = "news_full";

if ( empty($user) and in_array($mode, array("options", "news_add", "control"))) $mode = $config['s_dpage']; 	

/* Загрузка контента ( content_main )*/
switch ($mode) {
    case 'start': $page = 'Начать игру'; $content_main = Menager::ShowStaticPage(STYLE_URL.'start-game.html');  break;
	case '404':   $page = 'Страница не найдена'; $content_main = Menager::ShowStaticPage(STYLE_URL.'404.html'); break;
	case 'register':  include('./location/news.php');			  break;
	case 'news_full': include('./location/news_full.php');        break;
    case 'options':   include('./location/options.php');          break;
	case 'news_add':  include('./location/news_add.php');         break;
    case 'control':   include('./location/admin.php');            break; 
    default: $mode = $config['s_dpage']; include('./location/'.$config['s_dpage'].'.php');  break;
} 
/* Загрузка контента ( content_side )*/
include('./location/side.php'); 

$content_menu = $menu->Show();

/* Загрузка списка серверов */

$servManager = new ServerMenager();
$content_servers = $servManager->Show('side');
unset($servManager);

include MCR_STYLE.'index.html';
//$exec_time = microtime(true) - $start_time; echo (microtime(true) - $start_time).'<br />'.(memory_get_usage() - $mem_use);
?>