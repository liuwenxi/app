<?php
header("Content-type:text/html;charset=utf-8");

/**
 * 检测是否已经登陆
 * 
 */

function is_user_login(){
    if(session('uid')>0){
        return TRUE;
    }else{
        return FALSE;
    }
}

/**
 * 过滤表情 by karl
 * @param $str
 * @return mixed
 */
function filter_emoji($str) {
    $regex = '/(\\\u[ed][0-9a-f]{3})/i';
    $str = json_encode($str);
    $str = preg_replace($regex, '', $str);
    return json_decode($str);
}



