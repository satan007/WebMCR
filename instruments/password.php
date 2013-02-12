<?php

function createPass($password) { //шифрует пароль при регистрации 
	
	return md5($password);

}

function webmcr($realPass, $password, $pl_id = false) { //проверяет пароль при авторизации $realPass - то что хранится в БД , $password - то что ввел пользователь
	
	if ($realPass==md5($password)) return true;
			
	else return false;
			
}

function dle($realPass, $password, $pl_id = false) 
{	
  	if ( $realPass == md5( md5($password) ) ) return true;
			
	else return false;
}

function ipb($realPass, $password, $salt, $pl_id = false)
{
	
	$cryptPass = false;
	$cryptPass = md5(md5($salt).md5($password));
	
 if ($realPass == $cryptPass) return true;
	else return false;
}

function wordpress($realPass, $password, $pl_id = false) 
{
	
    $cryptPass = false;
    $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $count_log2 = strpos($itoa64, $realPass[3]);
    $count = 1 << $count_log2;
    $salt = substr($realPass, 4, 8);
    $input = md5($salt . $password, TRUE);
    do
    {
        $input = md5($input . $password, TRUE);
    }
    while (--$count);
               
    $output = substr($realPass, 0, 12);
               
    $count = 16;
    $i = 0;
    do
    {
        $value = ord($input[$i++]);
        $cryptPass .= $itoa64[$value & 0x3f];
        if ($i < $count)
            $value |= ord($input[$i]) << 8;
        $cryptPass .= $itoa64[($value >> 6) & 0x3f];
        if ($i++ >= $count)
            break;
        if ($i < $count)
            $value |= ord($input[$i]) << 16;
        $cryptPass .= $itoa64[($value >> 12) & 0x3f];
        if ($i++ >= $count)
            break;
        $cryptPass .= $itoa64[($value >> 18) & 0x3f];
    }
        while ($i < $count);
               
    $cryptPass = $output . $cryptPass;
 
    if ($realPass == $cryptPass) return true;
	else return false;
}

?>