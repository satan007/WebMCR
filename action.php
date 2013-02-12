<?php
/* WEB-APP : WebMCR (С) 2013 NC22 */

require_once('./system.php');
require_once(MCR_ROOT.'instruments/ajax.php');

if (empty($_POST['method'])) exit;
$method = $_POST['method'];

function CaptchaTest($exit_mess = 2) { 

	if ( empty($_SESSION['code']) or 
         empty($_POST['antibot']) or 
         $_SESSION['code'] != (int)$_POST['antibot'] ) {
       
            if (isset($_SESSION['code'])) unset($_SESSION['code']);
            aExit($exit_mess, 'Защитный код введен не верно.');

    }
	unset($_SESSION['code']);
}

switch ($method) {
    case 'restore':   
    
        if (empty($_POST['login']) or empty($_POST['email'])) aExit(1,'Не все поля заполнены.'); 
    
        CaptchaTest(2); 

        $login = TextBase::MySQLCompatible($_POST['login']);
        $email = TextBase::MySQLCompatible($_POST['email']);  
	    
		$result = BD("SELECT `{$bd_users['id']}` FROM `{$bd_names['users']}` WHERE `{$bd_users['login']}`='$login' AND `{$bd_users['email']}`='$email'"); 
		if ( !mysql_num_rows($result) ) aExit(3, 'Пользователь с таким именем и почтовым адрессом не найден.');
		
		$line = mysql_fetch_array( $result, MYSQL_NUM );
        
		$restore_user = new User($line[0],$bd_users['id']);		
	     
		$new_pass = randString(8);
	   
	    $subject = 'Восстановление пароля';
	   
		$headers   = array();
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/plain; charset=utf-8";
		$headers[] = "From: Auto replay <noreplay@noreplay.ru>";
		$headers[] = "Subject: {$subject}";
		$headers[] = "X-Mailer: PHP/".phpversion();
		
		$message = 'Система восстановления пароля. Ваш новый пароль : '.$new_pass;
		
        $message = TextBase::WordWrap($message, 70);
		
		if ( !mail($email, $subject, $message, implode("\r\n", $headers)) ) aExit(4, 'Ошибка службы отправки сообщений.');
		
		if ( $restore_user->changePassword($new_pass) != 15 ) aExit(5, '');
		
		aExit(0, 'Новый пароль отправлен вам на Email.');	

    break;
	case 'comment': 
	
        if (empty($user) or empty($_POST['comment']) or empty($_POST['item_id']) or empty($_POST['antibot'])) aExit(1, 'Ошибка отправки сообщения.'); 

	    if ( !$user->canPostComment() ) aExit(1, 'Отправлять сообщения можно не чаще чем раз в минуту.'); 

	    CaptchaTest(3); 
			
	    require_once(MCR_ROOT.'instruments/catalog.class.php');
				
		$comments_item = new Comments_Item();				
		$rcode = $comments_item->Create($_POST['comment'],$_POST['item_id']);
        
            if ( $rcode == 1701 ) aExit(1, 'Сообщение слишком короткое.');       
        elseif ( $rcode == 1702 ) aExit(2, 'Комментируемая статья или новость не найдена.');       
        elseif ( $rcode == 1 )    aExit(0, 'Сообщение успешно отправлено ');          
        else                      aExit(3, 'Ошибка отправки сообщения.');  

    break;
    case 'del_com':

		if (empty($user) or empty($_POST['item_id'])) aExit(1);		
		
		require_once(MCR_ROOT.'instruments/catalog.class.php');
			
		$comments_item = new Comments_Item((int)$_POST['item_id']);
		
		if (!$user->getPermission('adm_comm') and $comments_item->GetAuthorID() != $user->id()) aExit(1); 
		
		if ($comments_item->Delete()) aExit(0);	else aExit(1);  

    break;
    case 'load_info':

        $ajax_message = array('code' => 0, 
		                      'message' => 'load_info',
 							  'name' => '',
							  'group' => '',
							  'skin' => 0, 
							  'cloak' => 0,
							  'comments_num' => 0,
							  'female' => 0,
							  'play_times' => 0,
							  'undress_times' => 0,
							  'create_time' => 0,
							  'active_last' => 0,
							  'play_last' => 0);

        if (empty($_POST['id'])) aExit(1, 'Поисковой индекс не задан.'); 
         
        $inf_user = new User((int) $_POST['id'],$bd_users['id']);
        if (!$inf_user->id()) aExit(2, 'Пользователь не найден.'); 
        
        $ajax_message['name']   = $inf_user->name();
        $ajax_message['group']  = $inf_user->getGroupName();
		$ajax_message['skin']   = ($inf_user->defaultSkinTrigger())? 1 : 0;
		$ajax_message['female'] = ($inf_user->isFemale())? 1 : 0;
		
		    $timeParam = $inf_user->gameLoginLast();
		if ($timeParam) $ajax_message['play_last'] = strtotime($timeParam);		
		    $timeParam = $inf_user->getStatisticTime('create_time');
		if ($timeParam) $ajax_message['create_time'] = strtotime($timeParam);			
	        $timeParam = $inf_user->getStatisticTime('active_last');
		if ($timeParam) $ajax_message['active_last'] = strtotime($timeParam);
		
		$statistic = $inf_user->getStatistic();		
		
		if ($statistic) {
		
		$ajax_message['comments_num']  = $statistic['comments_num'];
		$ajax_message['play_times']    = $statistic['play_times'];
		$ajax_message['undress_times'] = $statistic['undress_times'];
		}
		
        aExit(0);	

    break;
	case 'profile': 

        $ajax_message = array('code' => 0, 'message' => 'profile', 'name' => '', 'group' => '', 'id' => '', 'skin' => 0, 'cloak' => 0);

        $rcodes = null;        

        if (empty($user) or $user->lvl() <= 0) aExit(1); 

        $mod_user = $user;
		
        if ($user->lvl() >= 15 and !empty($_POST['user_id'])) 
        $mod_user = new User((int) $_POST['user_id'],$bd_users['id']);

        if (!$mod_user->id()) aExit(2, 'Пользователь не найден.'); 
		
	    if ($user->lvl() >= 15){
		
			if (isset($_POST['new_group'])) {
			
				$mod_user->changeGroup((int) $_POST['new_group']);
				$rcodes[] = 1;
			}			   
			if (!empty($_POST['new_email'])) $rcodes[] = $mod_user->changeEmail($_POST['new_email']);
			if (isset($_POST['new_gender'])) {
			
		        $newgender = (!(int)$_POST['new_gender'])? 0 : 1;
                $mod_user->changeGender($newgender);
				$rcodes[] = 1;
		    }		 
		}
		
 	    if (!empty($_POST['new_login'])) $rcodes[] = $mod_user->changeName($_POST['new_login']);
	    if (!empty($_POST['new_password'])) {

			$oldpass   = (!empty($_POST['old_password']))? TextBase::MySQLCompatible($_POST['old_password']) : '';
			$newpass   =  TextBase::MySQLCompatible($_POST['new_password']);
            $newrepass = (!empty($_POST['new_repassword']))? TextBase::MySQLCompatible($_POST['new_repassword']) : '';

            if ($user->lvl() >= 15 and !empty($_POST['user_id'])) $rcodes[] = $mod_user->changePassword($newpass);
            else                  	$rcodes[] = $mod_user->changePassword($newpass,$newrepass,$oldpass);
        }
		
        if (empty($_FILES['new_skin']['tmp_name'])  and !empty($_POST['new_delete_skin']) and $user->getPermission('change_skin')) 
           $rcodes[] = $mod_user->setDefaultSkin();

	    if (empty($_FILES['new_cloak']['tmp_name']) and !empty($_POST['new_delete_cloak']) and $user->getPermission('change_cloak')) { 
			$mod_user->deleteCloak();
			$rcodes[] = 1;
		}
	    if (!empty($_FILES['new_skin']['tmp_name']) ) 

            if ( POSTGood('new_skin') ) {

            $rcode_key = sizeof($rcodes);

                if (!$user->getPermission('change_skin')) $rcodes[$rcode_key] = 1605;

		        else {
                
                $rcodes[$rcode_key] = POSTUpload('new_skin', $mod_user->getSkinFName(), 64, 2);  

                    if (  $rcodes[$rcode_key] == 1 ) {

						if ( !strcmp($mod_user->defaultSkinMD5(),md5_file($mod_user->getSkinFName())) ) 
						  $mod_user->defaultSkinTrigger(true);
						else
						  $mod_user->defaultSkinTrigger(false); 
	 
						$mod_user->deleteBuffer();
	                }
                }

            } else $rcodes[] = 1604;

	    if (!empty($_FILES['new_cloak']['tmp_name']) ) 

            if ( POSTGood('new_cloak') ) {

            $rcode_key = sizeof($rcodes);

               if (!$user->getPermission('change_cloak')) $rcodes[$rcode_key] = 1606;

               else {

                       $rcodes[$rcode_key] = (int) POSTUpload('new_cloak', $mod_user->getCloakFName(), 22, 1.29).'1';
                  if ( $rcodes[$rcode_key] == 11 ) $mod_user->deleteBuffer(); 
               }

            } else $rcodes[] = 1603;

        
        $message = ''; 
        $rnum    = sizeof($rcodes);

        for ($i=0; $i < $rnum; $i++) {

            $modifed = true; 

			switch ((int) $rcodes[$i]) {
                case 0 : $message .= 'error'; break;
                case 1401 : $message .= 'Логин введен некорректно.'.$rnum  ; break;
				case 1402 : $message .= 'Пользователь с таким именем уже существует.'; break;
			    case 1403 : $message .= 'Логин должен содержать не меньше 4 символов и не больше 8.'; break;   
				case 1501 : $message .= 'Пароль введен некорректно.'; break;
                case 1502 : $message .= 'Текущий пароль неверен.'; break;
                case 1503 : $message .= 'Пароль должен содержать не меньше 4 символов и не больше 15.'; break;
                case 1504 : $message .= 'Пароли не совпадают.'; break;
                case 1601 : 
                $message .= "Файл больше ".$user->getPermission('max_fsize')." кб ( загрузка скина )"; 				  
				break;
                case 1602 : 
				$tmpm = $user->getPermission('max_ratio');
				$message .= "Размеры изображения заданы неверно. ( Рекомендуемое соотношение сторон для скина ".(62*$tmpm)."x".(32*$tmpm)." )"; 
				unset($tmpm);
				break;
                case 16011 : 
				$message .= "Файл больше ".$user->getPermission('max_fsize')." кб ( загрузка плаща )"; 
				break;
                case 16021 : 
				$tmpm = $user->getPermission('max_ratio');
				$message .= "Размеры изображения заданы неверно. ( Рекомендуемое соотношение сторон для плаща ".(22*$tmpm)."x".(17*$tmpm)." )";
                unset($tmpm);
				break;
                case 1604 : $message .= 'Ошибка при загрузке скина. ( Рекомендуемый формат файла .png )'; break;
                case 1603 : $message .= 'Ошибка при загрузке плаща. ( Рекомендуемый формат файла .png )'; break;
                case 1606 : $message .= 'Доступ к загрузке плащей ограничен.'; break;
                case 1605 : $message .= 'Доступ к загрузке скинов ограничен.'; break;
				case 1901 : $message .= 'Emai\'l введен некорректно.'; break;
				case 1902 : $message .= 'Почтовый ящик уже используется другим пользователем.'; break;
                default : $modifed = false; break; 
            }	

            if ($modifed) $message .= "\n";	
		}
    
        $ajax_message['name']  = $mod_user->name();
        $ajax_message['group'] = $mod_user->getGroupName();   
        $ajax_message['id']    = $mod_user->id();

        if (file_exists($mod_user->getCloakFName())) $ajax_message['cloak'] = 1; 
        if ($mod_user->defaultSkinTrigger())         $ajax_message['skin']  = 1; 

        	if ($message) aExit(2, $message ); // some bad news 
		elseif (!$rnum)  aExit(100, $message ); //nothing changed
        else aExit(0, 'Профиль успешно обновлен.');  

    break;
} 
?>