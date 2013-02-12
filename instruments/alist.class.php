<?php
if (!defined('MCR')) exit;

Class ControlMenager extends Menager {
var $style;
var $work_skript;
var $category_id;

    function ControlMenager($style = false, $work_skript = '?mode=control') { 
	
		$this->style = (!$style)? MCR_STYLE : $style;
		
		parent::Menager($this->style);
		
		$this->work_skript = $work_skript;	
	}

	function ShowUserListing($list = 1, $search_by = 'name', $input = false) {
	global $bd_users,$bd_names;

	    if ($input == 'banned') $input = 0;
	
	    if ($search_by == 'name') $result = BD("SELECT {$bd_users['id']} FROM {$bd_names['users']} WHERE {$bd_users['login']} LIKE '%$input%' ORDER BY {$bd_users['login']} LIMIT ".(10*($list-1)).",10"); 
    elseif ($search_by == 'none') $result = BD("SELECT {$bd_users['id']} FROM {$bd_names['users']} ORDER BY {$bd_users['login']} LIMIT ".(10*($list-1)).",10"); 
	elseif ($search_by == 'ip'  ) $result = BD("SELECT {$bd_users['id']} FROM {$bd_names['users']} WHERE {$bd_users['ip']} LIKE '%$input%' ORDER BY {$bd_users['login']} LIMIT ".(10*($list-1)).",10"); 
	elseif ($search_by == 'lvl' ) {
	
		$result = BD("SELECT `id` FROM {$bd_names['groups']} WHERE lvl='$input'");
		
		$id_group  = mysql_fetch_array( $result, MYSQL_NUM );    
	    $input = $id_group[0];
		
	    $result = BD("SELECT {$bd_users['id']} FROM {$bd_names['users']} WHERE `{$bd_users['group']}` = '$input' ORDER BY {$bd_users['login']} LIMIT ".(10*($list-1)).",10"); 
	}
        
		ob_start(); 		

	          $resnum =  mysql_num_rows( $result );	
	    if ( !$resnum ) { include $this->style.'admin/user_not_found.html'; return ob_get_clean(); }  
		
        include $this->style.'admin/user_find_header.html'; 
  
		while ( $line = mysql_fetch_array( $result, MYSQL_NUM ) ) {
		
            $inf_user = new User($line[0],$bd_users['id']);
			
            $user_name = $inf_user->name();
            $user_id   = $inf_user->id();
            $user_ip   = $inf_user->ip();
            $user_lvl  = $inf_user->getGroupName();
			$user_lvl_id = $inf_user->group();
			
            unset($inf_user);
			
            include $this->style.'admin/user_find_string.html'; 
        } 
		
		include $this->style.'admin/user_find_footer.html'; 

        $html = ob_get_clean();

	    if ($search_by == 'name') $result = BD("SELECT COUNT(*) FROM {$bd_names['users']} WHERE {$bd_users['login']} LIKE '%$input%'");
	elseif ($search_by == 'none') $result = BD("SELECT COUNT(*) FROM {$bd_names['users']}");
	elseif ($search_by == 'ip'  ) $result = BD("SELECT COUNT(*) FROM {$bd_names['users']} WHERE {$bd_users['ip']} LIKE '%$input%'");
	elseif ($search_by == 'lvl' ) $result = BD("SELECT COUNT(*) FROM {$bd_names['users']} WHERE `{$bd_users['group']}`='$input'");
		
		$line = mysql_fetch_array($result);
		$arrows = $this->arrowsGenerator($this->work_skript,$list,$line[0],10,'admin/userf');
		
        if ( $arrows ) {		  
			  ob_start(); include $this->style.'admin/user_find_list.html'; 				  
			  $html .= ob_get_clean();			  
		}
      
     return $html;
	}	
	
    function ShowServers($list) { /* �������� */
    global $bd_names;

    ob_start(); 	
	
    include $this->style.'admin/servers_caption.html';
	
	// TODO increase priority by votes
	
    $result = BD("SELECT * FROM {$bd_names['servers']} ORDER BY priority DESC LIMIT ".(10*($list-1)).",10");  
    $resnum = mysql_num_rows( $result );
	
	if ( !$resnum ) { include $this->style.'admin/servers_not_found.html'; return ob_get_clean(); }  
		
	include $this->style.'admin/servers_header.html'; 
		
		while ( $line = mysql_fetch_array( $result ) ) {
		
            $server_name     = $line['name'];
			$server_address  = $line['address'];
			$server_info     = $line['info'];
			$server_port     = $line['port'];
	        $server_method   = '';
			
			switch ((int)$line['method']) {
			case 0: $server_method = 'Simple query'; break;
			case 1: $server_method = 'Query'; break; 
			case 2:  $server_method = 'RCON'; break;
			}			
			$server_id       = $line['id'];
		
		include $this->style.'admin/servers_string.html';         
        }
        
	include $this->style.'admin/servers_footer.html'; 
	$html = ob_get_clean();
	
		$result = BD("SELECT COUNT(*) FROM {$bd_names['servers']}");
		$line = mysql_fetch_array($result); 
		$resnum = $line[0];
					  		  
		$arrows = $this->arrowsGenerator($this->work_skript,$list,$line[0],10);
	    if ( $arrows ) {		  
			  ob_start(); include $this->style.'admin/servers_list.html'; 				  
			  $html .= ob_get_clean();			  
		}	

    return $html;
    }
	
    function ShowIpBans($list) {
    global $bd_names;

    RefreshBans();

    ob_start(); 	
	
    include $this->style.'admin/ban_ip_caption.html';
	
    $result = BD("SELECT * FROM {$bd_names['ip_banning']} ORDER BY ban_until DESC LIMIT ".(10*($list-1)).",10");  
    $resnum = mysql_num_rows( $result );
	
	if ( !$resnum ) { include $this->style.'admin/ban_ip_not_found.html'; return ob_get_clean(); }  
		
	include $this->style.'admin/ban_ip_header.html'; 
		
		while ( $line = mysql_fetch_array( $result ) ) {
		
             $ban_ip    = $line['IP'];
             $ban_start = $line['time_start'];
             $ban_end   = $line['ban_until'];

		     include $this->style.'admin/ban_ip_string.html'; 
        
        }
        
	include $this->style.'admin/ban_ip_footer.html'; 
	$html = ob_get_clean();
	
		$result = BD("SELECT COUNT(*) FROM {$bd_names['ip_banning']}");
		$line = mysql_fetch_array($result); 
		$resnum = $line[0];
					  		  
		$arrows = $this->arrowsGenerator($this->work_skript,$list,$line[0],10);
	    if ( $arrows ) {		  
			  ob_start(); include $this->style.'admin/ban_ip_list.html'; 				  
			  $html .= ob_get_clean();			  
		}	

    return $html;
    }
}
?>