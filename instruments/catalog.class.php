<?php
if (!defined('MCR')) exit;

/* Классы
 
 - Менеджер каталогизатора новостей
 - Новость
 - Комментарий
 - Категории новостей */

Class Category {
private $db;

private $id;
private $name;
private $priority;

    public function Category($id = false) {
	global $bd_names;
	
		$this->db = $bd_names['news_categorys']; 
		$this->id = $id; if (!$this->id) return false;

		$result = BD("SELECT `name`,`priority` FROM `".$this->db."` WHERE `id`='".$this->id."'"); 
		if ( mysql_num_rows( $result ) != 1 ) { $this->id = false; return false; }
		
        $line = mysql_fetch_array( $result, MYSQL_NUM );
		
		$this->name 	= $line[0];
		$this->priority = (int)$line[1];
	}

	public function Exist() {
		if (!$this->id) return false;	
		else return true;
	}
	
	public function Create($name, $priority = 1, $description = '') {
		
	if ($this->Exist()) return false; 
		
		$name = TextBase::MySQLCompatible($name);        
		if (!$name) return false;
		
		$result = BD("SELECT COUNT(*) FROM `".$this->db."` WHERE `name`='$name'");
		$num    = mysql_fetch_array($result, MYSQL_NUM);
		if ($num[0]) return false;				
		
		$description = TextBase::MySQLCompatible($description);	
		$priority    = (int) $priority;

		if (BD("INSERT INTO `".$this->db."` ( `name`, `priority`, `description`) values ( '$name', '$priority','$description' )"))		
		 $this->id = mysql_insert_id();
		else return false;
		
	return true; 
	}
	
	public function IsSystem() {
       if ($this->id == 1) return true; else return false;
    }	
	
	public function GetName() {
		if (!$this->Exist()) return false;
		return $this->name;		
    }
	
	public function GetPriority() {
		if (!$this->Exist()) return false;
		return $this->priority;				
	}
	
	public function GetDescription() {
		$result = BD("SELECT `description` FROM `".$this->db."` WHERE id='".$this->id."'"); 

		if ( mysql_num_rows( $result ) != 1 ) return false;
        $line = mysql_fetch_array( $result, MYSQL_NUM );
		  
		return $line[0];		
	}
	
	public function Edit($name, $priority = 1, $description = '') {
		
		if (!$this->Exist()) return false; 
		
		$name = TextBase::MySQLCompatible($name);
		if (!$name) return false;
		
		$result = BD("SELECT COUNT(*) FROM `".$this->db."` WHERE `name`='$name' and `id`!='".$this->id."'");
		$num  	= mysql_fetch_array($result, MYSQL_NUM);
		if ($num[0]) return false;	
		
		$description = TextBase::MySQLCompatible($description);		
		$priority    = (int) $priority; 
		
		BD("UPDATE `".$this->db."` SET `name`='$name',`priority`='$priority',`description`='$description' WHERE `id`='".$this->id."'"); 		
		
		$this->name 	= $name;
		$this->priority = $priority;
		return true; 
	}	
	
	public function Delete() {
	global $bd_names;	

		if (!$this->Exist() or $this->IsSystem()) return false;
		
		$result = BD("SELECT `id` FROM `{$bd_names['news']}` WHERE `category_id`='".$this->id."'"); 
		if ( mysql_num_rows( $result ) != 0 ) {
	  
		  while ( $line = mysql_fetch_array( $result, MYSQL_NUM ) ) {
		  		
				$news_item = new News_Item($line[0]);
				$news_item->Delete(); 
				unset($news_item);
		  }
		}
		
		BD("DELETE FROM `".$this->db."` WHERE `id`='".$this->id."'");
		$this->id = false;
		return true; 
	}	
}

Class CategoryMenager {

	public static function GetList($selected = 1) {
	global $bd_names;
	
		$result = BD("SELECT `id`,`name` FROM {$bd_names['news_categorys']} ORDER BY `priority` DESC LIMIT 0,90");  
		$cat_list = '';
							
		while ( $line = mysql_fetch_array( $result, MYSQL_ASSOC ) ) 
		 $cat_list .= '<option value="'.$line['id'].'" '.(($selected == $line['id'])?'selected':'').'>'.$line['name'].'</option>';
	
    return $cat_list;	
	}
	
	public static function GetNameByID($id) {

        if (!$id or $id < 0) return 'Без категории';
		
		$cat_item = new Category($id);
		$category_name = $cat_item->GetName();
		
		unset($cat_item);
		
		if (!$category_name) return 'Без категории';
		                else return $category_name;
	}
	
	public static function ExistByID($id) {
		
		$cat_id   = (int) $id;		
		$cat_item = new Category($id);
		
	return $cat_item->Exist();
	}
}

Class Comments_Item extends Message { 
private $db;
private $style;

private $id;
private $user_id;

	public function Comments_Item($id = false, $style = false) {
	global $bd_names;	
	
		$this->db    = $bd_names['comments'];
		$this->style = (!$style)? MCR_STYLE : $style;
		
		$this->id = $id; if (!$this->id) return false;
		
		$result = BD("SELECT `user_id` FROM `{$this->db}` WHERE `id`='".$this->id."'"); 
		if ( mysql_num_rows( $result ) != 1 ) {	$this->id = false; return false; }		
	
	    $line = mysql_fetch_array($result, MYSQL_NUM);        
        $this->user_id = $line[0];		
	}
	
	public function Create($message, $item_id) {
	global $user,$bd_names,$bd_users;	
	
		if (empty($user) or !$user->canPostComment()) return 0; 
		
		$item_id = (int)$item_id;
		$message = $this->Comment($message);
		if ( TextBase::StringLen($message) < 2 ) return 1701;

		$result = BD("SELECT `id` FROM `{$bd_names['news']}` WHERE `id`='".$item_id."'"); 
		if ( mysql_num_rows( $result ) != 1 ) return 1702;			
			
		if ( BD("INSERT INTO `{$this->db}` ( `message`, `time` , `item_id`, `user_id`) values ('$message', NOW(), '$item_id' , '".$user->id()."')") ) {
			
		$this->id = mysql_insert_id();
		BD("UPDATE {$bd_names['users']} SET comments_num=comments_num+1 WHERE {$bd_users['id']}='".$user->id()."'"); 		
		}
	return 1; 
	}
	
	public function Exist() {
		if ($this->id) return true;
		return false;
	}
	
	public function Show() {
	global $user, $bd_users;
	
		if (!$this->Exist()) return Menager::ShowStaticPage($this->style.'comment_not_found.html');
			
		$result = BD("SELECT DATE_FORMAT(time,'%d.%m.%Y %H:%i:%S') AS time,message,item_id FROM `{$this->db}` WHERE id='".$this->id."'"); 
		if (!mysql_num_rows( $result )) return ''; 
		
		$line = mysql_fetch_array( $result, MYSQL_ASSOC );
			
		$admin_buttons 	= '';
		$female_mark	= '';
		$text 			= $this->BBDecode($line['message']);
		$date 			= $line['time'];
			
		$id		 = $this->id;		
		$item_id = $line['item_id'];		
		$user_id = $this->user_id;
			
		$user_post = new User($user_id,$bd_users['id']);
				
		$user_name   = ($user_post->id())? $user_post->name() : 'Удаленный пользователь';
		$user_female = ($user_post->id())? $user_post->isFemale() : false;	
		unset($user_post);
			
		if ( !empty($user) and ( $user->getPermission('adm_comm') or $user->id() == $user_id ) ) { 

			ob_start(); include $this->style.'comments_admin.html';			  
			$admin_buttons = ob_get_clean();
		}

		if ( $user_female ) $female_mark = Menager::ShowStaticPage($this->style.'comments_girl.html');
		
		ob_start();	
		if ( !empty($user) ) include $this->style.'comments.html';
		else 	             include $this->style.'comments_unauth.html';
	 
	return ob_get_clean();		
	}

    public function GetAuthorID() {
		if (!$this->Exist()) return false;		
        return $this->user_id;
    }

	public function Edit($message) {
		$message = $this->Comment($message);				
		BD("UPDATE `{$this->db}` SET message='$message' WHERE id='".$this->id."'");
		
		return true; 
	}	
		
	public function Delete() {
		if (!$this->Exist()) return false;
		
		BD("DELETE FROM `{$this->db}` WHERE id='".$this->id."'");	
		$this->id = false;
		return true; 
	}
}

/* Класс записи в каталоге */

Class News_Item {
private $db;
private $style;

private $id;
private $category_id;
private $title;

	public function News_Item($id = false, $style = false) {
	global $bd_names;
	
		$this->style = (!$style)? MCR_STYLE : $style;
		$this->db = $bd_names['news'];	
		
	    $this->id = $id; if (!$this->id) return false;
	
		$result = BD("SELECT `category_id`,`title` FROM `{$this->db}` WHERE `id`='".$this->id."'"); 
		if ( mysql_num_rows( $result ) != 1 ) { $this->id = false; return false; }
		
		$line = mysql_fetch_array( $result, MYSQL_NUM );
		$this->category_id = $line[0];	
		$this->title = $line[1];	
		
	return true;
	}
	
	public function Create($cat_id, $title, $message, $message_full = false) {
	global $user;	
		
		if ($this->Exist() or empty($user) or !$user->getPermission('add_news')) return false; 
					
	    $sql = ''; $sql2 = '';		
	    if ( $message_full ) { $sql = '`message_full`, '; $sql2 = "'$message_full', "; }
		
		$cat_id = (int)$cat_id;
		if (!CategoryMenager::ExistByID($cat_id)) return false; 
			
		$title   = TextBase::MySQLCompatible($title);
		$message = TextBase::MySQLCompatible($message);
		
		if ( $message_full )
		$message_full = TextBase::MySQLCompatible($message_full);

		BD("insert into `{$this->db}` ( `title`, `message`, ".$sql."`time`, `category_id`) values ( '$title', '$message', ".$sql2."NOW(), '$cat_id' )");
		
		$this->id = mysql_insert_id();
		$this->category_id = $cat_id;
		$this->title = $title;
		
	return true; 
	}
	
	public function GetCategoryID() {
		if (!$this->Exist()) return false;		
        return $this->category_id;		
	}
	
	public function GetTitle() {
		if (!$this->Exist()) return false;		
        return $this->title;		
	}
	
	public function Exist() {
		if ($this->id) return true;
		return false;
	} 

	public function Show($full_text = false) {
    global $config, $user;
	
	if (!$this->Exist()) return Menager::ShowStaticPage($this->style.'news_not_found.html');
		
		$result = BD("SELECT COUNT(*) FROM comments WHERE item_id='".$this->id."'");
		$line   = mysql_fetch_array($result, MYSQL_NUM);	  
		$comments = $line[0];	
		
		$sql = ( $full_text )? '`message_full`,' : ''; //:%S
		
		$result = BD("SELECT DATE_FORMAT(time,'%d.%m.%Y') AS date,DATE_FORMAT(time,'%H:%i') AS time,".$sql."`message` FROM `{$this->db}` WHERE `id`='".$this->id."'"); 
		if (!mysql_num_rows( $result )) return ''; 
		
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		
		if ($full_text) $line['message_full'] = TextBase::CutWordWrap($line['message_full']);
		$line['message'] = TextBase::CutWordWrap($line['message']);
		
		$text = ( $full_text and TextBase::StringLen($line['message_full']) )? $line['message_full'] : $line['message'];		
		
		$id    = $this->id;
		$title = $this->title;
		$date  = $line['date'];
		$time  = $line['time'];
		
		// можно добавить work_script по аналогии
		$link  = ($config['rewrite'])? 'news/'.$id : 'index.php?id='.$id;
		
		$category_id = $this->category_id;		
        $category    = CategoryMenager::GetNameByID($category_id);
		
		$category_link = ($config['rewrite'])? 'category/'.$category_id : 'index.php?cid='.$category_id;
		
		$admin_buttons = '';
		
		if (!empty($user) and $user->getPermission('add_news')) { 

		ob_start();	include $this->style.'news_admin.html';			  
		$admin_buttons =  ob_get_clean();
		} 

		ob_start();	

		if ( $full_text )
		 include $this->style.'news_full.html';
		else
		 include $this->style.'news.html';	
		 
		return ob_get_clean();				
	}
	
	public function Edit($cat_id, $title, $message, $message_full = false) {
	global $user;
		
		if (!$this->Exist() or empty($user) or !$user->getPermission('add_news')) return false; 
		
		$cat_id = (int)$cat_id;
		if (!CategoryMenager::ExistByID($cat_id)) return false; 
		
		$title   = TextBase::MySQLCompatible($title);
		$message = TextBase::MySQLCompatible($message);	
		
		$message_full = ($message_full)? TextBase::MySQLCompatible($message_full) : '';
		
		BD("UPDATE `{$this->db}` SET `message`='$message',`title`='$title',`message_full`='$message_full',`category_id`='$cat_id' WHERE `id`='".$this->id."'"); 		
		
		$this->category_id = $cat_id;
		$this->title = $title;
		return true; 
	}	
		
	public function Delete() {
	global $user,$bd_names;
	
		if (empty($user) or 
		    !$user->getPermission('add_news') or 
			!$this->Exist()) return false; 
		
		$result = BD("SELECT id FROM `{$bd_names['comments']}` WHERE `item_id`='".$this->id."'"); 
		if ( mysql_num_rows( $result ) != 0 ) {
	  
		  while ( $line = mysql_fetch_array( $result, MYSQL_NUM ) ) {
		  		
				$comments_item = new Comments_Item($line[0]);
				$comments_item->Delete(); 
				unset($comments_item);
		  }
		}
		
		BD("DELETE FROM `{$this->db}` WHERE `id`='".$this->id."'");
		$this->id = false;
		return true; 
	}	
}

/* Менеджер вывода записей из каталога */

Class NewsMenager extends Menager {
private $style;
private $work_skript;
private $category_id;

    public function NewsMenager($category = 1, $style = false, $work_skript = 'index.php?') { // category = -1 -- all last news
	
		$this->style = (!$style)? MCR_STYLE : $style;

		parent::Menager($this->style);
	
		if ((int) $category <= 0) $category = 0;
		
		$this->category_id = (int)$category; 
		$this->work_skript = $work_skript;
	}
	
	public function destroy() {
	
	  unset($this->style); 
	  unset($this->work_skript); 
	  unset($this->category_id);   
	}

    public function ShowCategorySelect() {
	$cat_list = '<option value="0">Последние новости</option>';	
	$cat_list .= CategoryMenager::GetList($this->category_id); 

	ob_start();
	include $this->style.'categorys.html';					  

	return ob_get_clean();	
	}	
	
	public function ShowNewsEditor() { /* Перевести на AJAX как в случае с комментариями */
	global $user,$bd_names;

	if (empty($user) or !$user->getPermission('add_news')) return '';
    
	$editorTitle  = 'Добавить новость';
	$editorButton = 'Добавить';
	
	$editCategory = 0;
	$editMode     = 0;
	$editTitle    = '';
	$editMessage  = '';
	$editMessage_Full  = '';
	$error        = '';

		if (isset($_POST['title']) and isset($_POST['message']) and isset($_POST['cid'])) {

             ob_start();
			 $state = 'error';
			
			if (empty($_POST['title']) or empty($_POST['message']) or empty($_POST['cid'])) 
			
			    $text_str = 'Заполните необходимые поля.';
			
			else {
			
			    $mesFull = (!empty($_POST['message_full']))? $_POST['message_full'] : false;
			
				$editMode = (int) $_POST['editMode'];
				$editCategory = (int) $_POST['cid'];
				
				if ($editMode > 0) {
				
				    $news_item = new News_Item($editMode, $this->style);
				
				if ($news_item->Edit($editCategory, $_POST['title'], $_POST["message"], $mesFull)) {
				
				    $state       = 'success';
				    $text_str    = 'Новость обновлена.';
					
				} else
				
				    $text_str    = 'Недостаточно прав.';
				
				$editMode = 0;
				
				} else {
				
				$news_item = new News_Item();				
				$news_item->Create($editCategory, $_POST['title'],$_POST["message"],$mesFull);
				
				$state     = 'success';
				$text_str = 'Новость добавлена';
				
				}				
			}
			
			include $this->style.'news_admin_mess.html';
			$error = ob_get_clean();
			
		} elseif (isset($_GET['delete'])) {

		    $news_item = new News_Item((int)$_GET['delete']);
			$news_item->Delete();
			
			header("Location: ".$this->work_skript."ok");
			
		} elseif (isset($_GET['edit'])) {
			
			$editorTitle  = 'Обновить новость';
			$editorButton = 'Изменить';
			
			$mesid  = (int)$_GET['edit']; 
			
			$result = BD("SELECT * FROM `{$bd_names['news']}` WHERE `id`='$mesid'");
			$line   = mysql_fetch_array($result, MYSQL_ASSOC);
			
			$editMode         = $mesid;
			$editCategory     = $line['category_id'];
			$editTitle        = TextBase::HTMLDestruct($line['title']);
			$editMessage      = TextBase::HTMLDestruct($line['message']);
			$editMessage_Full = TextBase::HTMLDestruct($line['message_full']);
			
		}

	ob_start();
	
	$cat_list = CategoryMenager::GetList($editCategory);
 	
	include $this->style.'news_add.html';
				  
	return ob_get_clean();

	}

	public function ShowNewsListing($list = 1) {
	global $bd_names,$config;

    $sql = '';
    if ( $this->category_id > 0 ) $sql = ' WHERE category_id='.$this->category_id.' ';	

	$list = (int) $list;
	
	if ( $list <= 0 ) $list = 1; 
 	
	if ( $this->category_id > 0 )
	   $category = CategoryMenager::GetNameByID($this->category_id);
	else
	   $category = 'Последние новости';
     
	$category_id = $this->category_id;
	$category_link = ($config['rewrite'])? 'category/'.$category_id : 'index.php?cid='.$category_id;
	
	ob_start(); include $this->style.'news_header.html';					  
	$html_news = ob_get_clean();
    $news_pnum  = $config['news_by_page'];
	
		$result = BD("SELECT id FROM `{$bd_names['news']}`".$sql."ORDER by time DESC LIMIT ".($news_pnum*($list-1)).",".$news_pnum);

		if ( mysql_num_rows( $result ) != 0 ) {

		  while ( $line = mysql_fetch_array( $result , MYSQL_NUM ) ) {
		  
		         $news_item = new News_Item($line[0],$this->style);
				 
		         $html_news.= $news_item->Show(); 
				 unset($news_item);				 
		  }

		  $result = BD("SELECT COUNT(*) FROM `{$bd_names['news']}`".$sql);
		  $line = mysql_fetch_array($result, MYSQL_NUM );
		  
		  $newsnum = $line[0];
		  $arrows  = $this->arrowsGenerator($this->work_skript,$list,$newsnum,$news_pnum);
		  
		  if ( $arrows ) {
		  
			  ob_start(); include $this->style.'news_list.html';					  
			  $html_news .= ob_get_clean();
			  
		  }
		  
		} else $html_news .= Menager::ShowStaticPage($this->style.'news_empty.html');
		
	return $html_news;	
	}
	
	public function ShowCommentForm($id) {
	global $user;

	if (empty($user) or !$user->getPermission('add_comm')) return '';
	
         $news_item = new News_Item($id,$this->style);
	if (!$news_item->Exist()) return '';
	
	$postTitle  = 'Добавить комментарий';
	$postButton = 'Добавить';
	$postCaptchaRefresh = '?refresh='.rand(1337,31337);
	
	$editMode     = 0;
	$editTitle    = '';
	$editMessage  = '';
	$error        = '';

	ob_start();
		
	include $this->style.'comments_add.html';
				  
	return ob_get_clean();
	}
	
	public function ShowFullById($id,$list = false) { 
	global $config,$bd_names;

	$id   = (int) $id;
	$link  = ($config['rewrite'])? 'news/'.$id : 'index.php?id='.$id;
    
		$news_item  = new News_Item($id,$this->style); // можно определять некоторые переменные на этапе инициализации н. заглавие
		$item_exist = $news_item->Exist();
		
		$title      	= ($item_exist)? $news_item->GetTitle() : 'Новость не найдена'; 			
		$category_id 	= ($item_exist)? $news_item->GetCategoryID() : 0;
		$category 		= ($item_exist)? CategoryMenager::GetNameByID($category_id) : 'Без категории';
		$category_link 	= ($config['rewrite'])? 'category/'.$category_id : 'index.php?cid='.$category_id;
		
	    ob_start(); include $this->style.'news_full_header.html';					  
	    $html_news = ob_get_clean();

		$html_news .= $news_item->Show(1);			
		unset($news_item);
		
		if (!$item_exist) return $html_news;	

		$result = BD("SELECT COUNT(*) FROM `{$bd_names['comments']}` WHERE item_id='$id'");
		$line = mysql_fetch_array($result);
		  
		$commentnum = $line[0];
		if (!$commentnum) return $html_news;
		
		$comm_pnum = $config['comm_by_page']; 
		$comm_order = ($config['comm_revers'])? 'ASC' : 'DESC';	
		
		$list_def = ($config['comm_revers'])? ceil($commentnum / $comm_pnum) : 1;	
		$list = ($list <= 0)? $list_def : (int)$list;
		
		$result = BD("SELECT id FROM `{$bd_names['comments']}` WHERE item_id='$id' ORDER by time $comm_order LIMIT ".($comm_pnum*($list-1)).",".$comm_pnum); 
		if ( mysql_num_rows( $result ) != 0 ) {
		
	      ob_start(); include $this->style.'comments_header.html';					  
	      $html_news .= ob_get_clean();
		  
		  while ( $line = mysql_fetch_array( $result, MYSQL_NUM ) ) {
		  
		         $comments_item = new Comments_Item($line[0],$this->style);
				 
		         $html_news.= $comments_item->Show(); 
				 unset($comments_item);
		  }

			  $arrows  = $this->arrowsGenerator($this->work_skript,$list,$commentnum,$comm_pnum);
		  if ($arrows) {
		  
			  ob_start(); include $this->style.'comments_list.html';					  
			  $html_news .= ob_get_clean();			  
		  }
		  
		} else $html_news .= Menager::ShowStaticPage($this->style.'comments_empty.html');
		
	return $html_news;	
	}	
}
?>