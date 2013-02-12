<?php
if (!defined('FEEDBACK') or empty($user)) exit;

$page = 'Добавить новость';    

require_once(MCR_ROOT.'instruments/catalog.class.php');
$news_manager = new NewsMenager(null,MCR_STYLE.'news/');

$menu->SetItemActive($menu_items['add_news']);
$content_main = $news_manager->ShowNewsEditor();
?>