<?php
header('Content-Type: text/html;charset=UTF-8');

require_once('../system.php');
require_once(MCR_ROOT.'instruments/monitoring.class.php');
require_once(MCR_ROOT.'instruments/catalog.class.php');

$news = '';
$page_title = 'Новостная лента';

$news_manager = new NewsMenager($config['game_news'], MCR_STYLE, $config['s_root'].'index.php?');

if (isset($_GET['l'])) $curlist = (int) $_GET['l']; 
else                   $curlist = 1; 
	
if (isset($_GET['id'])) $spec_new = (int) $_GET['id']; 
else                    $spec_new = -1; 

$news = $news_manager->ShowNewsListing($curlist);

$servManager = new ServerMenager();
$server_state_html = $servManager->Show('game');
unset($servManager);
			  
include_once MCR_STYLE.'index.html';
?>