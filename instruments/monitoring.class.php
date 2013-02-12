<?php
if (!defined('MCR')) exit;

Class Server {
private $db;
private $style;

private $id;

private $address;
private $port; // Порт не игровой, а тот что используется для сбора статистики
private $method; 
			
private $name;			
private $slots;			
private $info;
private $numpl;
private $online;

private $refresh;	
private $rcon;  

	public function Server($id = false, $style = false) {
	global $bd_names;
	
		$this->db    = $bd_names['servers'];
		$this->style = (!$style)? MCR_STYLE : $style;
		
	    $this->id = $id; if (!$this->id) return false;
		
		$result = BD("SELECT online,address,port,name,numpl,slots,info,refresh_time,method,rcon FROM `".$this->db."` WHERE id='$id'");
		if ( mysql_num_rows( $result ) != 1 ) { $this->id = false; return false; }
			
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
			
		$this->id      = $id;

		$this->address = $line['address'];
		$this->port    = (int)$line['port']; // Порт не игровой, а тот что используется для сбора статистики
		$this->method  = (int)$line['method']; 
			
		$this->name    = $line['name'];			
		$this->slots   = $line['slots'];			
		$this->info    = $line['info'];
		$this->numpl   = $line['numpl'];
		$this->online  = ($line['online'])? true : false;

		$this->refresh = (int)$line['refresh_time'];
		$this->rcon    = (!strlen($line['rcon']))? false : $line['rcon'];  
			
	return true;			
	}
	
	public function Create($address, $port, $method = 0, $rcon = false, $name = '', $info = '') {
	
		if ($this->Exist()) return 0; 

		$name    = ($name)? TextBase::MySQLCompatible($name) : '';
		$info    = ($info)? TextBase::MySQLCompatible($info) : '';
		$address = ($address)? TextBase::MySQLCompatible($address) : '';
		
		if (!$address) return 3;
		
			$method = (int)$method;
		if ($method < 0 or $method > 2) $method = 0;
		
		$rcon = ($rcon and $method == 2)? TextBase::MySQLCompatible($rcon) : '';
		
		if ($method == 2 and !$rcon) return 2;
		
		$port = (int) $port;
		if (!$port) $port = 25565;
		
		//if (!preg_match("/[0-9.]+$/", $address) or strlen($address) < 8) return 1901;		
		
		if ( BD("insert into `".$this->db."` ( address, port, info, name, method, rcon ) values ('$address', '$port', '$info' , '$name', '$method', '$rcon' )") ) 
          $this->id = mysql_insert_id();
		else return 4;
		
		$this->address = $address;
		$this->port    = $port; 
		$this->method  = $method; 
		$this->info    = $info;			
		$this->name    = $name;			
		$this->rcon    = $rcon;  		
		
		return 1; 
	}
	
   public function SetConnectMethod($method = 0, $rcon = false) {
	
	if (!$this->id) return false;
		    
		$method = (int)$method;
	if ($method < 0 or $method > 2) $method = 0;
	
	$rcon = ($rcon and $method == 2)? TextBase::MySQLCompatible($rcon) : '';
	
	if ($method == 2 and !$rcon) return false;
	
	BD("UPDATE `".$this->db."` SET `method`='$method',`rcon`='$rcon' WHERE `id`='".$this->id."'"); 	
	
	$this->method = $method;
	$thid->rcon   = $rcon;
   }   
   
   public function SetConnectWay($address, $port) {
	
	if (!$this->id) return false;	
	
	$address = ($address)? TextBase::MySQLCompatible($address) : '';
	if (!$address) return false;
	
	$port = (int) $port;
	if (!$port) $port = 25565;	
	
	BD("UPDATE `".$this->db."` SET `address`='$address',`port`='$port' WHERE `id`='".$this->id."'"); 
	
	$this->address = $address;
	$thid->port    = $port;
	return true;
   }
   
   public function SetText($var, $field = 'name') {
	
	if (!$this->Exist()) return false;
	else if (!$field == 'name' and !$field == 'info') return false;
	
	$var = ($var)? TextBase::MySQLCompatible($var) : '';
	
	BD("UPDATE `".$this->db."` SET `$field`='$var' WHERE `id`='".$this->id."'"); 
	
	if ($field == 'name') $this->name = $var;
	else  $this->info = $var;
   }  
   	
	private function IsTimeToUpdate() {

	if (!$this->Exist()) return false;
	
		$result = BD("SELECT last_update FROM `".$this->db."` WHERE id='".$this->id."' AND last_update<NOW()-INTERVAL ".$this->refresh." MINUTE"); 

	    if ( mysql_num_rows( $result ) == 1 ) return true;
		else return false;
		
	}
	
	public function UpdateState($extra = false) {
    
	if ((!$extra and !$this->IsTimeToUpdate()) or !$this->Exist()) return;
	
	$this->online = false;
	$users_list = NULL;
	
	if (empty($this->address)) {	
	 BD("UPDATE `".$this->db."` SET online='0',last_update=NOW() WHERE id='".$this->id."'"); 
	 return;
    }
	
	if ($this->method == 2) {
	
		require_once(MCR_ROOT.'instruments/rcon.class.php');
		
		try	{
		
			$rcon = new MinecraftRcon;
			$rcon->Connect( $this->address, $this->port, $this->rcon);
			$str = $rcon->Command('list');
			
		} catch( MinecraftRconException $e ){
		
			if ($e->getMessage() == 'Server offline') {
			   BD("UPDATE `".$this->db."` SET online='0',last_update=NOW() WHERE id='".$this->id."'"); 
			   return;
			}
		}

		$str = str_replace(array("\r\n", "\n", "\r"),'', $str);
		$names = explode(', ',substr($str, 19)); 
		
		if (!empty($names)) for($i=0;$i<sizeof($names);$i++) trim($names[$i]); 
		if (!$names[0]=='') $users_list = $names;  
		
	} else {
	
		 require_once(MCR_ROOT.'instruments/query.function.php');
		 
		 $full_state = ($this->method == 1)? mcraftQuery($this->address, $this->port ) : mcraftQuery_SE($this->address, $this->port );		 
		 
		 if (empty($full_state)) {
		   BD("UPDATE `".$this->db."` SET online='0',last_update=NOW() WHERE id='".$this->id."'"); 
		   return;
		 } else if (!empty($full_state['players'])) $users_list = $full_state['players']; 
		
	}
	
	$this->online = true;	
	$system_users = '';
	$numpl = (!empty($full_state['numpl']))? $full_state['numpl'] : 0;
	
	if ($users_list) {
		
		$numpl = sizeof($users_list);
		
	    if ($numpl == 1) $system_users = $users_list[0];
	    else {
		
			for($i=0; $i < $numpl; $i++) {
				  if ($i == 0) $system_users .= $users_list[$i];
				 $system_users .= ','.$users_list[$i];
			}		
		}			 
	}
	
	if (!empty($full_state))	// name='".$full_state['hostname']."'
	  BD("UPDATE `".$this->db."` SET numpl='$numpl',slots='".$full_state['maxplayers']."',players='".$system_users."',online='1',last_update=NOW() WHERE id='".$this->id."'"); 		 
    else
  	  BD("UPDATE `".$this->db."` SET numpl='$numpl',slots='-1',players='".$system_users."',online='1',last_update=NOW() WHERE id='".$this->id."'"); 		 
	
	}	
	
	public function GetPlayers() {
	
	if (!$this->Exist()) return false;
	
			$result = BD("SELECT players, numpl FROM `".$this->db."` WHERE id='".$this->id."'");
			$players = mysql_fetch_array($result, MYSQL_ASSOC);
			$list    = $players['players'];
			$numpl   = (int)$players['numpl'];
			
			if (!strlen($list) and !$numpl) return array("Сервер пуст", 0);
			
			if (!sizeof(explode(',',$list)) and !$numpl) return array("Сервер пуст", 0);
						                           else  return array($list, $numpl);
    }
	
	public function SetVisible($page,$state) {
	
	if (!$this->Exist()) return false;
	
	    $page = ServerMenager::getPageName($page);
		if (!$page) return false;
		
		$state = ($state)? 1 : 0;
		
		BD("UPDATE `".$this->db."` SET `$page`='$state' WHERE `id`='".$this->id."'"); 
	}
   
   public function GetVisible($param) {

		if (!$this->Exist()) return -1;	
		
		     $param = ServerMenager::getPageName($param);
		if (!$param) return false;
		
		$result = BD("SELECT `$param` FROM `".$this->db."` WHERE `id`='".$this->id."'");
		
		if (mysql_num_rows( $result ) == 1) {
		
			$line  = mysql_fetch_array($result, MYSQL_NUM );
			$value = ((int)$line[0])? true : false;
			
			return $value;
			
		} else return -1;		
   }
   
   public function SetRefreshTime($newTimeout) {
	
	if (!$this->Exist()) return false;
	
	    $newTimeout = (int)$newTimeout;
		if ($newTimeout < 0) $newTimeout = 0;
		
		BD("UPDATE `".$this->db."` SET `refresh_time`='$newTimeout' WHERE `id`='".$this->id."'"); 
		
	$this->refresh = $newTimeout;	
   return true;		
   }
   
   public function SetPriority($new) {
	
	if (!$this->Exist()) return false;
	
	    $new = (int)$new;
		if ($new < 0) $new = 0;
		
		BD("UPDATE `".$this->db."` SET `priority`='$new' WHERE `id`='".$this->id."'"); 
		
	return true;
   }  
   
   public function GetPriority() {
   
        if (!$this->Exist()) return false;

		$result = BD("SELECT `priority` FROM `".$this->db."` WHERE `id`='".$this->id."'");
		
		if (mysql_num_rows( $result ) == 1) {
		
			$line  = mysql_fetch_array($result, MYSQL_NUM );
		    return (int)$line[0];
			
		} else return false;		
   }
   
	public function ShowHolder($type = 'side', $server_prefix = '') {
	
		if (!ServerMenager::getPageName($type)) return false;
	
        ob_start();	
		
		$server_name   = $this->name;
		$server_info   = $this->info; //$this->address; фактический адресс нужен только система
		$server_id     = $this->id;
		$server_pid    = $server_prefix.$server_id;
		$server_numpl  = $this->numpl;
		$server_slots  = $this->slots;
		
		if ((int)$this->slots != -1)
			$server_pl_inf = $this->numpl.'/'.$this->slots;
		else 
			$server_pl_inf = $this->numpl;		
				
    	switch ($type) {		
		case 'mon':            
		case 'side': include $this->style.'serverstate_'.$type.'.html';	break;
		case 'game':
		
		if ( $this->online ) include $this->style.'serverstate_'.$type.'_online.html';  
		else include $this->style.'serverstate_'.$type.'_offline.html';	
		
        break;		
		default: return false; break;
		}
        
		return ob_get_clean();	
    }
	
	public function ShowInfo() {
	global $ajax_message;
	
	  $ajax_message = array('code' => 0, 
	                        'message' => '',
							'info' => '',
							'address' => '',
							'port' => 0,
 							'online' => 0,
							'numpl' => 0,
							'pl_array' => '',
							'slots' => 0,
							'name' => '',
							'id' => 0,
							);

      if (!$this->id) aExit(1,'server_state');	
	  
	  $ajax_message['id']      = $this->id;		
	  $ajax_message['name']    = $this->name;					
	  $ajax_message['online']  = ($this->online)? 1 : 0;
	  $ajax_message['info']    = $this->info;
	  $ajax_message['address'] = $this->address;
	  $ajax_message['port']    = $this->port;
	  	  
	  if (!$this->online) aExit(0,'server_state'); 
	  
	  $players = $this->GetPlayers();
		
	  $ajax_message['numpl']    = $players[1];
	  $ajax_message['slots']    = $this->slots;
	  $ajax_message['pl_array'] = $players[0];
	 
	  aExit(0,'server_state');
   }
   
   public function Delete() {
	
		if (!$this->Exist()) return false; 
	
		BD("DELETE FROM `".$this->db."` WHERE `id`='".$this->id."'");
		
		return true; 
	}
	
   public function Exist() {
   if ($this->id) return true;
   return false;
   }  
   
   public function address() {
   return $this->address;	
   } 
   
   public function refresh() {
   return $this->refresh;	
   }  
   
   public function port() {
   return $this->port;	
   }  
   
   public function method() {
   return $this->method;	
   }  
   
   public function info() {
   return $this->info;	
   }   
   
   public function name() {
   return $this->name;	
   }   
}

Class ServerMenager {
private $style;

	public function ServerMenager($style = false) { 
	global $site_ways;
	
	   $this->style = (!$style)? MCR_STYLE : $style;
	}
	
	public function Show($type = 'side', $update = false) {
	global $bd_names;
	
	         $page = self::getPageName($type);
        if (!$page) return false;
		
		$html_serv = Menager::ShowStaticPage($this->style.'serverstate_'.$type.'_header.html'); 
		
		$result = BD("SELECT `id` FROM `{$bd_names['servers']}` WHERE `$page`=1 ORDER BY priority DESC LIMIT 0,10"); 
			
		if ( mysql_num_rows( $result ) ) { 

		   while ( $line = mysql_fetch_array( $result, MYSQL_NUM ) ) {
					
			$server = new Server($line[0],$this->style);
			if ($update) $server->UpdateState();
            $html_serv .= $server->ShowHolder($type);
			
			unset($server);
		   }
		   
		} else $html_serv .= Menager::ShowStaticPage($this->style.'serverstate_'.$type.'_empty.html');
		
		$html_serv .= Menager::ShowStaticPage($this->style.'serverstate_'.$type.'_footer.html');	
		
        return $html_serv;		
	}	
	
	public static function getPageName($page) {
	    switch ($page) {
		case 'side': return 'main_page'; break;
		case 'game': return 'news_page'; break; 
		case 'mon':  return 'stat_page'; break;
		default: 	 return false; 		 break;
		}	
	}
}
?>