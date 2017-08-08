<?php
header("Content-type:text/html;charset=utf-8");
//后台会员登录验证
function is_login(){

    if (empty($_SESSION['adminid'])) {
        redirect(U('User/index'), 0, '用户还没登陆，请先登陆......');
    }
}
/*
 * 判断身份
 */
function checkUser(){
    if(session('uid')){
        return 1;
    }
    if(session('UserInfo')){
        return 2;
    }else{
        return 0;
    }
}
/**
 * 判断是否手机访问 by karl
 */
function is_mobile() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $mobile_agents = Array("240x320","acer","acoon","acs-","abacho","ahong","airness","alcatel","amoi","android","anywhereyougo.com","applewebkit/525","applewebkit/532","asus","audio","au-mic","avantogo","becker","benq","bilbo","bird","blackberry","blazer","bleu","cdm-","compal","coolpad","danger","dbtel","dopod","elaine","eric","etouch","fly ","fly_","fly-","go.web","goodaccess","gradiente","grundig","haier","hedy","hitachi","htc","huawei","hutchison","inno","ipaq","ipod","jbrowser","kddi","kgt","kwc","lenovo","lg ","lg2","lg3","lg4","lg5","lg7","lg8","lg9","lg-","lge-","lge9","longcos","maemo","mercator","meridian","micromax","midp","mini","mitsu","mmm","mmp","mot-","moto","nec-","netfront","newgen","nexian","nf-browser","nintendo","nitro","nokia","nook","novarra","obigo","palm","panasonic","pantech","philips","phone","pg-","playstation","pocket","pt-","qc-","qtek","rover","sagem","sama","samu","sanyo","samsung","sch-","scooter","sec-","sendo","sgh-","sharp","siemens","sie-","softbank","sony","spice","sprint","spv","symbian","tablet","talkabout","tcl-","teleca","telit","tianyu","tim-","toshiba","tsm","up.browser","utec","utstar","verykool","virgin","vk-","voda","voxtel","vx","wap","wellco","wig browser","wii","windows ce","wireless","xda","xde","zte");
    $is_mobile = false;
    foreach ($mobile_agents as $device) {
        if (stristr($user_agent, $device)) {
            $is_mobile = true;
            break;
        }
    }
    return $is_mobile;
}

/**
 * 判断是否微信访问 by karl
 */
function is_weixin(){
    if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
        return true;
    }else{
        return false;
    }
}

/**
 * 打印数据 by karl
 */
function bug($msg){
    echo "<pre style='color: #0000FF'>";
    echo "<meta charset='utf-8'  />";
    if(!$msg){
        echo "没有提取到数据";
    }else{
        print_r($msg);
    }
}

/** json 格式封装函数 by karl * */

function dexit($data = '') {

    if (is_array($data)) {

        echo json_encode($data);

    } else {

        echo $data;

    }

    exit();

}

/**
 * 接口返回json规范
 * @param int $status   //状态码 0成功 1失败
 * @param string $value  //数据别名
 * @param array $data   //数据
 * @param string $msg   //中文提示
 * @User Karl
 * @Time 2017-07-31 11:55:26
 */
function Json($status=0,$value='data',$data = array(),$msg='') {

    $datas['status']=$status;
    $datas[$value]=is_array($data)?$data:(int)$data;
    if(!empty($msg)){
        $datas['msg']=$msg;
    }
    echo json_encode($datas);
    exit();

}


/**
 * 生存随机数 by karl
 */
function random($length, $chars = '0123456789') {
    $hash = '';
    $max = strlen($chars) - 1;
    for($i = 0; $i < $length-1; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}

/**
 * 实例化阿里云oos
 * @return object 实例化得到的对象
 */
function new_oss(){
    vendor('Alioss.autoload');
    $config=C('ALIOSS_CONFIG');
    $oss=new \OSS\OssClient($config['KEY_ID'],$config['KEY_SECRET'],$config['END_POINT'],false);
    return $oss;
}

/**
 * 上传文件到oss并删除本地文件
 * @param  string $path 文件路径
 * @return bollear 是否上传
 */
function oss_upload($path){
    // 获取配置项
    $bucket=C('ALIOSS_CONFIG.BUCKET');
    // 先统一去除左侧的.或者/ 再添加./
    $oss_path=ltrim($path,'./');
    $path='./'.$oss_path;
    if (file_exists($path)) {
        // 实例化oss类
        $oss=new_oss();
        // 上传到oss
        $oss->uploadFile($bucket,$oss_path,$path);
        // 如需上传到oss后 自动删除本地的文件 则删除下面的注释
         @unlink($path);
        return true;
    }
    return false;
}

/**
 * 获取完整网络连接
 * @param  string $path 文件路径
 * @return string       http连接
 */
function get_url($path){
    // 如果是空；返回空
    if (empty($path)) {
        return '';
    }
    // 如果已经有http直接返回
    if (strpos($path, 'http://')!==false) {
        return $path;
    }
    // 获取bucket
    $bucket=C('ALIOSS_CONFIG.BUCKET');
    return 'http://'.$bucket.'.oss-cn-shenzhen.aliyuncs.com'.$path;
}

//把返回的数据集转换成Tree
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

//获取 user_token
function get_user_token(){
    $string = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789';
    $uid = time();
    $str = '';
    for ($i=0; $i < 10; $i++) {
        $str.= $string[rand(0,strlen($string)-1)];
    }
    return substr(md5($str.$uid),8,16);
}

/**
 * 通过jsapi_ticket 获取签名
* @param string jsapi_ticket
* @return type
*/
function getSignJsapiTicketSign($jsapi){
    $time = C('WEICHAT.TIME_TAMP');
    $noncestr = $time;
    $jsapi_ticket = $jsapi;
    $timestamp = $time;
    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $and = "jsapi_ticket=" . $jsapi_ticket . "&noncestr=" . $noncestr . "&timestamp=" . $timestamp . "&url=" . $url . "";
    return sha1($and);
}

//无极分类 显示
function get_category($datas, $pid = 0, $level=0){
    $list = M('Cate')->where("pid=".$pid)->order('pid')->select();
    $temp = '<font color="red">';
    for($i=0; $i<$level;$i++){
        $temp .='|-';   //|-|-
    }
    $temp .= "</font>";
    if($list){
        foreach($list as $k=>$v){
            $v['name'] = $temp.$v['name'];
            $datas[] = $v;   // 装载数据
            $datas = get_category($datas, $v['id'], $level+1);  //递归调用
        }
    }
    // print_r($datas);
    return $datas;

}

/*
 *截取字符串函数长度 函数
 *@params $str  要截取的字符串
 *@params $start  开始的位置（默认是0）
 *@params $length  截取的字符串长度
 *@params $charset  使用的编码格式 （默认utf-8）
 *@params $suffix  是否在截取后的字符串后面显示 省略号（默认true为显示）
*/
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true){

    if (function_exists("mb_substr")) {
        if ($suffix)
            return mb_substr($str, $start, $length, $charset)."...";
        else
            return mb_substr($str, $start, $length, $charset);

    }elseif (function_exists("iconv_substr")) {
        if ($suffix)
            return iconv_substr($str, $start, $length, $charset)."...";
        else
            return iconv_substr($str, $start, $length, $charset);

    }else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gbk2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";

        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    if ($suffix)
        return $slice."...";
    return $slice;
}

/*
*对象转数组 by karl
*/
function object2array($object) {
    $object=json_decode($object);
    if (is_object($object)) {
        foreach ($object as $key => $value) {
            $array[$key] = $value;
        }
    }
    else {
        $array = $object;
    }
    return $array;
}


/*
*多维数组 转换为 一维数组 方法
*/
function mult2arr($arr){

    static $result_arr = array();

    foreach ($arr as $k => $vo) {
        if (is_array($vo)) {
            mult2arr($vo);
        }else {
            $result_arr[$k] = $vo;
        }
    }
    return $result_arr;
}

/**
* 添加用户心愿行为记录
* @params $uid  用户id
* @params $type  1心愿墙，2心愿帮
* @params $wish_id  心愿id
* @params $class  1发布，2参与，3报名，4帮助，5取消报名，6取消帮助，7审核，8精选，9热门，10完成
* by karl 2017-04-06 11:10:08
**/
function addUseDynamic($uid,$type,$wish_id,$class){
    $userDynamic=M('userDynamic');
    $data['uid']=$uid;
    $data['type']=$type;
    $data['wish_id']=$wish_id;
    $data['class']=$class;
    $data['add_time']=time();
    return $userDynamic->add($data);
}

/*
$arr：数组
$group_count：分成几组
$group_num：每组多少个
*/
function array_group($arr,$group_count,$group_num){
    for($i=0;$i<$group_count;$i++){
        if($i == $group_count-1){
            $arrT[] = array_slice($arr, $i * $group_num );
        }else{
            $arrT[] = array_slice($arr, $i * $group_num ,$group_num);
        }
    }
    return $arrT;
}