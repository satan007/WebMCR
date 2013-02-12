<?php
if (!defined('MCR')) exit;
require_once(MCR_ROOT.'instruments/base.class.php');

Class Group extends TextBase {
private $id;
private $pavailable;

    public function Group($id = false) {
		
		$this->id = $id;
		$this->pavailable = array("change_skin", 
		                          "change_pass",
								  "lvl",
								  "change_cloak",
								  "change_login",
								  "max_fsize",
								  "max_ratio",
								  "add_news",
								  "adm_comm",
								  "add_comm");								  
	}
	
	public function GetPermission($param) {
	global $bd_names;	

		if (!$this->id) return -1;		
		if (!in_array($param, $this->pavailable)) return -1;

		$result = BD("SELECT `$param` FROM `{$bd_names['groups']}` WHERE `id`='".$this->id."'");
		
		if (mysql_num_rows( $result ) == 1) {
		
			$line  = mysql_fetch_array($result, MYSQL_NUM );
			$value = (int)$line[0];
			
			if ($param != 'max_fsize' and
                $param != 'max_ratio' and
				$param != 'lvl' )
				 
			$value = ($line[0])? true : false;
			return $value;
			
		} else return -1;		
	}
	
	public function GetAllPermissions() {
    global $bd_names;
	
	$sql_names = null; 
	
	for ($i=0;$i < sizeof($this->pavailable);$i++) 
		if ($sql_names) $sql_names .= ",`{$this->pavailable[$i]}`"; 
		else            $sql_names .= "`{$this->pavailable[$i]}`"; 	
   	
	$result = BD("SELECT $sql_names FROM {$bd_names['groups']} WHERE `id`='".$this->id."'");  
	return mysql_fetch_array( $result, MYSQL_ASSOC );	
	}
	
	public function Exist() {
	global $bd_names;	
		
		if (!$this->id) return false;
		
		$result = BD("SELECT `id` FROM {$bd_names['groups']} WHERE `id`='".$this->id."'"); 

		if ( mysql_num_rows( $result ) == 1 ) return true;
		else return false;
		
	}
	
	public function Create($name, &$permissions) {
	global $bd_names,$user;	
		
		if ($this->id or empty($user) or $user->lvl() < 15) return false; 
			 
		$name  = $this->MySQLCompatible($name);
		
		$result = BD("SELECT COUNT(*) FROM `{$bd_names['groups']}` WHERE `name`='$name'");
		$num   = mysql_fetch_array($result, MYSQL_NUM);
		if ($num[0]) return false;	
		
		$sql_names = null; $sql_vars = null;
		
		  foreach ($permissions as $key=>$value) {
		  
		    if (!in_array($key, $this->pavailable)) continue;
				
			if ($key != 'max_fsize' and
                $key != 'max_ratio' and
                $key != 'lvl')	$value = ($value)? 1 : 0;				
            else                $value = (int) $value;
			
			if ($sql_names) $sql_names .= ",`$key`"; else $sql_names .= "`$key`"; 
			if ($sql_vars)  $sql_vars  .= ",'$value'"; else $sql_vars .= "'$value'"; 
		  }

		$result = BD("INSERT INTO `{$bd_names['groups']}` (`name`,$sql_names) values ('$name',$sql_vars)");	
		if ($result and mysql_affected_rows()) $this->id = mysql_insert_id();
		else return false;
	 
		return true; 
	}
	
	public function GetName() {
    global $bd_names;

		$result = BD("SELECT `name` FROM {$bd_names['groups']} WHERE id='".$this->id."'"); 

		if ( mysql_num_rows( $result ) != 1 ) return false;
        $line = mysql_fetch_array( $result, MYSQL_NUM );
		  
		return $line[0];		
    }
	
	public function IsSystem() {
    global $bd_names;

		$result = BD("SELECT `system` FROM {$bd_names['groups']} WHERE id='".$this->id."'"); 

		if ( mysql_num_rows( $result ) != 1 ) return false;
        $line = mysql_fetch_array( $result, MYSQL_NUM );
		  
		return ($line[0])? true : false;		
    }	
	
	public function Edit($name, &$permissions) {
	global $bd_names,$user;
		
		if (!$this->id or empty($user) or $user->lvl() < 15) return false; 
	
		$name  = $this->MySQLCompatible($name);
		if (!$name) return false;
		
		$result = BD("SELECT COUNT(*) FROM `{$bd_names['groups']}` WHERE `name`='$name' and `id`!='".$this->id."'");
		$num   = mysql_fetch_array($result, MYSQL_NUM);
		if ($num[0]) return false;	
		
		$sql = null;
		
		for ($i=0;$i < sizeof($this->pavailable);$i++) {
		
		$key = $this->pavailable[$i];
		
			if (isset($permissions[$key])){	
		
				if ($key != 'max_fsize' and
					$key != 'max_ratio' and
					$key != 'lvl')	$value = ($permissions[$key])? 1 : 0;				
				else				$value = (int) $permissions[$key];				
				
			} else $value = 0;
			
		$sql .= ",`$key`='$value'";
		}
		
		if (!$sql) $sql = '';
		
		    $result = BD("UPDATE {$bd_names['groups']} SET `name`='$name'$sql WHERE `id`='".$this->id."'"); 
		if ($result and mysql_affected_rows()) return true;
		
		return true; 
	}	
	
	public function Delete() {
	global $bd_names,$user;	

		if (!$this->id or empty($user) or $user->lvl() < 15) return false; 
		if ($this->IsSystem()) return false;
		
		$result = BD("SELECT `id` FROM `{$bd_names['users']}` WHERE `group`='".$this->id."'"); 
		if ( mysql_num_rows( $result ) != 0 ) {
	  
		  while ( $line = mysql_fetch_array( $result, MYSQL_NUM ) ) {
		  		
				$user_del = new User($line[0],'id');
				$user_del->Delete(); 
				unset($user_del);
		  }
		}
		
		$result = BD("DELETE FROM `{$bd_names['groups']}` WHERE `id` = '".$this->id."' and `system` = '0'");
		
		$this->id = false;		
		if ($result and mysql_affected_rows()) return true;
		
		return false; 
	}	
}

Class GroupMenager {

	public static function GetList($selected) {
	global $bd_names;
		
		$result = BD("SELECT id,name FROM {$bd_names['groups']} ORDER BY name DESC LIMIT 0,90");  
		$group_list = '';
							
		while ( $line = mysql_fetch_array( $result, MYSQL_ASSOC ) ) 
		 $group_list .= '<option value="'.$line['id'].'" '.(($selected == $line['id'])?'selected':'').'>'.$line['name'].'</option>';
		 
		return $group_list;
	}
	
	public static function GetNameByID($id) {

        if (!$id or $id < 0) return 'Удаленный';
		
		$grp_item = new Group($id);
		$grp_name = $grp_item->GetName();
		
		unset($grp_item);
		
		if (!$grp_name) return 'Удаленный';
		           else return $grp_name;
	}
}
?>