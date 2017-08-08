<?php
namespace Admin\Controller;
use Common\Controller\BaseController;
//公共类
class CommonController extends BaseController{
    
    /* 空操作，用于输出404页面 */
	public function _empty(){
		$this->redirect('Index/index');
	}


    protected function _initialize(){
        
        
    }
    
    //查网站配置
    protected function config($name){
        $config = M('webconfig')->where(array('codetag'=>$name))->select();
        return $config[0]['value'];
    }

    //查管理员username
    public function get_admin_uname($uid){
        $Admin = M('Admin');
        $adata = $Admin->where(array('id' => $uid))->find();
        return $adata['username'];
    }
    
    //查每日任务是否存在
//     public function chk_task_exit($data){
//         $Task = M('Task');
//         $EvdayTask = M('EvdayTask');
        
//         $nowtime = date("Ymd",time());
//         if ($data){
//             foreach ($data as $k=>$v){
//                 $sqltime = date("Ymd", $v['posttime']);                
//                 if ($sqltime == $nowtime){  //存在数据库
//                     $flag = 1;
//                 }
//             }
//         }else {
//             $flag = 0;
//         }
//         if ($flag != 1){  //不存在数据库，需要生成
//             $tdata = $Task->where(array('is_special' => 0))->field('id,num,title')->select();
//             $ydtdtime = $nowtime - 1 ;
//             $evtemp = $EvdayTask->select();
//             $ids = array();
//             foreach ($evtemp as $k=>$v){
//                 $bijiao = date("Ymd",$v['posttime']);
//                 if ($bijiao == $ydtdtime){
//                     $id = $v['tkid'];
//                     array_push($ids, $id);
//                 }
//             }
//             foreach ($tdata as $kk=>$vv){
//                 if (in_array($vv['id'], $ids)){
//                     unset($tdata[$kk]);
//                 }
//             }
//             $temp = array_rand($tdata,3);
//             $randnum = count($temp);
//             for ($i=0;$i<$randnum;$i++){
//                 $newdata = array(
//                     'tkid' => $tdata[$temp[$i]]['id'],
//                     'posttime' => time(),
//                     'title' => $tdata[$temp[$i]]['title'],
//                     'tknum' => $tdata[$temp[$i]]['num'],
//                 );
//                 $EvdayTask->data($newdata)->add();
//             }
//             return FALSE;
//         }else {
//             return TRUE;
//         }
//     }

    //判断是否已登录
    public function is_login(){
        if(session('?adminid')){
            return true;
        }else{
            $this->redirect('Public/login','', 0, '页面跳转中...');
        }
    }
    
    //验证码
    public function verify(){
        $config =    array(
            'fontSize'    =>    18,    // 验证码字体大小
            'length'      =>    3,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
            'imageW'      =>    140,    //验证码宽度
            'imageH'      =>    35,    //验证码宽度
            'bg'          =>    array(243, 251, 254),    //验证码背景颜色 rgb数组设置，例如 array(243, 251, 254)
        );
        $Verify =     new \Think\Verify($config);
        $Verify->entry();
    }
    
     //获取手机验证码
    public function get_phone_verify($phone='',$content=''){
        
        $ch = curl_init();
        $url = 'http://apis.baidu.com/hunanlehuotechnologyco/sms/api?phone=$phone&content=$content';
        $header = array(
            'apikey: $key',
         );
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);

        var_dump(json_decode($res));

    }
    
    //检验 验证码
    function check_verify($code, $id = ''){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
    }
    
    //检测是否存在账号
    protected function check_admin($name) {
      $res =  M('admin')->where(array('name'=>$name))->count();
      if($res>0){
          return TRUE;
      }else{
          return FALSE;
      }
    }
    
    //检测邮箱是否被占用
    public function checkemail($email){
        $model = M("user");
        $result = $model->where(array('email'=>$email))->count();
        if($result>0){
           $this->error($this->showRegError(-8));
        } else {
            return true;
        }
        
    }
    
    //密码加密
    protected function md5pw($password){
        $str = 'yjldfeWSC644!';
        $password = $str.$password;
        return substr(md5("$password"),8,16);
    }
    
    /**
	 * 获取用户注册错误信息
	 * @param  integer $code 错误编码
	 * @return string        错误信息
	 */
	private function showRegError($code = 0){
		switch ($code) {
			case -1:  $error = '用户名长度必须在16个字符以内！'; break;
			case -2:  $error = '用户名被禁止注册！'; break;
			case -3:  $error = '用户名被占用！'; break;
			case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
			case -5:  $error = '邮箱格式不正确！'; break;
			case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
			case -7:  $error = '邮箱被禁止注册！'; break;
			case -8:  $error = '邮箱被占用！'; break;
			case -9:  $error = '手机格式不正确！'; break;
			case -10: $error = '手机被禁止注册！'; break;
			case -11: $error = '手机号被占用！'; break;
			default:  $error = '未知错误';
		}
		return $error;
	}
    
    
    
    //操作记录
    /**
     * 
     * @param type $uid //操作用户id
     * @param type $category //操作类型，0为登录，1为访问操作，2为更新，3为增加，4为删除
     * @param type $query //操作内容，
     * @param type $status //操作结果，0为成功，1为失败，2为未知
     */
    protected function log($uid = 0,$category = 0,$query = '',$status = 0) {
        $data['uid'] = $uid;
        $data['do_category'] = $category;
        $data['do_query'] = $query;
        $data['status'] = $status;
        $data['do_time'] = time();
        $res = M('log')->add($data);
        if($res){
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    //根据code查询地址中文名
    /**
     * 
     * @param type $code 省份、市、区（县）对应的代码
     */
    protected function code_area($code){
       $area = M("regions")->where(array('code'=>$code))->select();
       return $area[0]['name'];
    }
    
    
     /**
    * 是否移动端访问访问
    *
    * @return bool
    */
   function isMobile()
   { 
       // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
       if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
       {
           return true;
       } 
       // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
       if (isset ($_SERVER['HTTP_VIA']))
       { 
           // 找不到为flase,否则为true
           return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
       } 
       // 脑残法，判断手机发送的客户端标志,兼容性有待提高
       if (isset ($_SERVER['HTTP_USER_AGENT']))
       {
           $clientkeywords = array ('nokia',
               'sony',
               'ericsson',
               'mot',
               'samsung',
               'htc',
               'sgh',
               'lg',
               'sharp',
               'sie-',
               'philips',
               'panasonic',
               'alcatel',
               'lenovo',
               'iphone',
               'ipod',
               'blackberry',
               'meizu',
               'android',
               'netfront',
               'symbian',
               'ucweb',
               'windowsce',
               'palm',
               'operamini',
               'operamobi',
               'openwave',
               'nexusone',
               'cldc',
               'midp',
               'wap',
               'mobile'
               ); 
           // 从HTTP_USER_AGENT中查找手机浏览器的关键字
           if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
           {
               return true;
           } 
       } 
       // 协议法，因为有可能不准确，放到最后判断
       if (isset ($_SERVER['HTTP_ACCEPT']))
       { 
           // 如果只支持wml并且不支持html那一定是移动设备
           // 如果支持wml和html但是wml在html之前则是移动设备
           if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
           {
               return true;
           } 
       } 
       return false;
   }
   
   //获取访问者浏览器
   function determinebrowser ($Agent) {
        $browseragent="";   //浏览器
        $browserversion=""; //浏览器的版本
        if (ereg('MSIE ([0-9].[0-9]{1,2})',$Agent,$version)) {
         $browserversion=$version[1];
         $browseragent="Internet Explorer";
        }else if (ereg( 'Edge/([0-9]{1,2}.[0-9]{1,2})',$Agent,$version)) {
         $browserversion=$version[1];
         $browseragent="Edge";
        }else if (ereg( 'Opera/([0-9]{1,2}.[0-9]{1,2})',$Agent,$version)) {
         $browserversion=$version[1];
         $browseragent="Opera";
        } else if (ereg( 'Firefox/([0-9.]{1,5})',$Agent,$version)) {
         $browserversion=$version[1];
         $browseragent="Firefox";
        }else if (ereg( 'Chrome/([0-9.]{1,3})',$Agent,$version)) {
         $browserversion=$version[1];
         $browseragent="Chrome";
        }
        else if (ereg( 'Safari/([0-9.]{1,3})',$Agent,$version)) {
         $browseragent="Safari";
         $browserversion="";
        }
        else {
        $browserversion="";
        $browseragent="Unknown";
        }
        return $browseragent." ".$browserversion;
    }

    // 同理获取访问用户的系统的信息
    function determineplatform ($Agent) {
        $browserplatform=='';
        if (eregi('win',$Agent) && strpos($Agent, '95')) {
        $browserplatform="Windows 95";
        }
        elseif (eregi('win 9x',$Agent) && strpos($Agent, '4.90')) {
        $browserplatform="Windows ME";
        }
        elseif (eregi('win',$Agent) && ereg('98',$Agent)) {
        $browserplatform="Windows 98";
        }
        elseif (eregi('win',$Agent) && eregi('nt 5.0',$Agent)) {
        $browserplatform="Windows 2000";
        }
        elseif (eregi('win',$Agent) && eregi('nt 5.1',$Agent)) {
        $browserplatform="Windows XP";
        }
        elseif (eregi('win',$Agent) && eregi('nt 6.0',$Agent)) {
        $browserplatform="Windows Vista";
        }
        elseif (eregi('win',$Agent) && eregi('nt 6.1',$Agent)) {
        $browserplatform="Windows 7";
        }
        elseif (eregi('Win',$Agent) && eregi('nt 6.2',$Agent)) {
        $browserplatform="Windows 8";
        }
        elseif (eregi('Win',$Agent) && eregi('nt 6.3',$Agent)) {
        $browserplatform="Windows 8.1";
        }
        elseif (eregi('Win',$Agent) && eregi('nt 10.0',$Agent)) {
        $browserplatform="Windows 10";
        }
        elseif (eregi('win',$Agent) && ereg('32',$Agent)) {
        $browserplatform="Windows 32";
        }
        elseif (eregi('win',$Agent) && eregi('nt',$Agent)) {
        $browserplatform="Windows NT";
        }
        elseif (eregi('Mac OS',$Agent)) {
        $browserplatform="Mac OS";
        }
        elseif (eregi('linux',$Agent)) {
        $browserplatform="Linux";
        }
        elseif (eregi('unix',$Agent)) {
        $browserplatform="Unix";
        }
        elseif (eregi('sun',$Agent) && eregi('os',$Agent)) {
        $browserplatform="SunOS";
        }
        elseif (eregi('ibm',$Agent) && eregi('os',$Agent)) {
        $browserplatform="IBM OS/2";
        }
        elseif (eregi('Mac',$Agent) && eregi('PC',$Agent)) {
        $browserplatform="Macintosh";
        }
        elseif (eregi('PowerPC',$Agent)) {
        $browserplatform="PowerPC";
        }
        elseif (eregi('AIX',$Agent)) {
        $browserplatform="AIX";
        }
        elseif (eregi('HPUX',$Agent)) {
        $browserplatform="HPUX";
        }
        elseif (eregi('NetBSD',$Agent)) {
        $browserplatform="NetBSD";
        }
        elseif (eregi('BSD',$Agent)) {
        $browserplatform="BSD";
        }
        elseif (ereg('OSF1',$Agent)) {
        $browserplatform="OSF1";
        }
        elseif (ereg('IRIX',$Agent)) {
        $browserplatform="IRIX";
        }
        elseif (eregi('FreeBSD',$Agent)) {
        $browserplatform="FreeBSD";
        }
        if ($browserplatform=='') {$browserplatform = "Unknown"; }
        return $browserplatform;
    }
    
}
