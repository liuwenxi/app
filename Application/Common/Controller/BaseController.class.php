<?php
namespace Common\Controller;
use Think\Controller;
use Lib\Api\Weixinapi\WeiChat;

/**
 * @BaseController 公共类
 */
class BaseController extends Controller{

    public function _initialize(){

        $UserInfo=session('UserInfo');
        if($UserInfo){
            $UserInfo=session('UserInfo');
            session('uid',$UserInfo['uid']);
        }
        if(is_weixin() && empty($UserInfo) && ACTION_NAME != 'login' ){
            $oWeiChat = new WeiChat();
            $authCallbackURL = urlencode('http://'.$_SERVER['HTTP_HOST'].U('Public/authCallback'));
            //进行获取用户信息授权申请
            $oWeiChat->login_base($authCallbackURL);
        }

    }
    
    /**
     * 获取心愿分类2级name
     * @param type $typeid 心愿类型id
     */
    protected function get_xytype_name($typeid){
        $data = M('listtype')->find($typeid);
        return $data['name'];
    }
    
    /**
     * 获取心愿的标题name
     * @param type $id 心愿id
     */
    protected function get_xy_title($id){
        $data = M('Xinyuan')->find($id);
        return $data['title'];
    }
    
    /**
     * 获取心愿分类父级id
     * @param type $typeid 心愿类型id
     */
    protected function get_xytype_pid($typeid){
        $data = M('listtype')->find($typeid);
        return $data['tpid'];
    }

    /**
     * 用户等级处理
     * @param type $jingyan 当前经验值
     * @param type $uid 用户id
     * @param type $n 当前等级
     */
    protected function chk_level($jingyan, $n, $uid){
        $User = M('User');
        //升级经验公式
        $sj = pow(2, $n)*1000;
        
        if ($jingyan >= $sj){
            $cha = $jingyan - $sj;
            $n += 1;
            $data = array(
                'jingyan' => $cha,
                'level' => $n,
            );
            $User->where(array('id' => $uid))->data($data)->save();
            $udata = $User->where(array('id' => $uid))->find();
            $this->chk_level($udata['jingyan'], $udata['level'], $uid);
        
        }else {
            
            return TRUE;
        }
    }
    
    
    /**
     * 获取用户昵称
     * @param type $typeid 心愿类型id
     */
    public function get_nickname($uid){
        $User = M('User');
        
        $udata = $User->find($uid);
        return $udata['nickname'];
    }
    
    /**
     * 获取用户头像
     * @param type $typeid 心愿类型id
     */
    public function get_avatar($uid){
        $User = M('User');
    
        $udata = $User->find($uid);
        return $udata['avatar'];
    }
    
    /**
     * 推送消息给用户
     * @param int $uid
     * @param int $des 消息内容
     */
    protected function sendMsg($uid,$title,$xid,$url,$type,$des){
        $Message = M('Message');
    
        $data = array(
            'uid' => $uid,
            'xid' =>$xid,
            'type' =>$type,
            'url' =>$url,
            'title' => $title,
            'des' => $des,
            'posttime' => time(),
            'releasetime' => time(),
        );
        $Message->data($data)->add();
        return TRUE;
    }
    
    /**
     * 获取列表图片
     * @param type $picid 图片id
     */
    public function get_listpic($picid){
        $Img = M('Img');
    
        $img = $Img->find($picid);
        return $img['pic'];
    }
    
    /**
     * 获取心元id对应mon
     * @param type $monid 心元id
     */
    public function get_monval($monid){
        $MonGear = M('MonGear');
    
        $mon = $MonGear->find($monid);
        return $mon['mon'];
    }
    
    /**
     * 处理特殊任务
     * @param type $dealdata 个人特殊任务数据
     * @param type $tkid 任务的类型id
     */
    public function dealTask($dealdata,$tkid){
        $MyTask = M('Myevdtask');
    
        foreach ($dealdata as $ki=>$vi){
            if ($vi['tkid'] == $tkid && $vi['donum'] == 0){
                $data = array(
                    'status' => 2,
                    'donum' => 1,
                    'is_finish' => 1,
                    'finishtime' => time(),
                );
                $MyTask->where(array('id' => $vi['id']))->data($data)->save();
            }
        }
    }
    
    public function sharesuccess(){
        $data['info'] = session('uid');
        if(session('uid')){
            //检测现在是否有送优惠券 活动
            $num = M('coupon')->where(array('starttime'=>array('lt',time()),'endtime'=>array('gt',time()),'type'=>0))->count();
            if($num>0){
                $coupon = M('coupon')->where(array('starttime'=>array('lt',time()),'endtime'=>array('gt',time()),'type'=>0))->find();
                //查看是否已经领取过该优惠券
                $isreceive = M('receive_coupon')->where(array('uid'=>  session('uid'),'coupon_id'=>$coupon['id']))->count();
                if($isreceive>0){
                    $data['status'] = 3;
                    $data['info'] = '分享成功，您已经领取过该优惠券了';
                }else{
                    $data['status'] = 1;
                    $ndata['coupon_id'] = $coupon['id'];
                    $ndata['uid'] = session('uid');
                    $ndata['ctime'] = time();
                    $ndata['starttime'] = $coupon['starttime'];
                    $ndata['endtime'] = $coupon['endtime'];
                    $ndata['price'] = $coupon['price'];
                    $ndata['status'] = 0;
                    $res = M('receive_coupon')->add($ndata);
                    M('coupon')->where(array('id'=>$coupon['id']))->setInc('receivenum');
                    $res && $data['info'] = '赠送成功';
                }
    
            }else{
                //没有正在 赠送优惠券活动
                $data['status'] = 2;
                $data['info'] = '没有正在举行的优惠券活动';
            }
        }else{
            $data['status'] = 0;
            $data['info'] = '尚未没有登录';
        }
        echo json_encode($data);
    }
    
    
    
    /****************************************** 商品 订单 ***********************************************/
    /**
     * 生成订单编号
     */
    protected function create_orderid(){
       
        return $ordersn = date('YmdHis',time()).rand(100,999);

    }
    
    


    /***************************************** 其他 ***************************************************/
    
    /**
     * 会员支付操作日志
     * @param int $uid
     * @param int $status 登录状态 0是支付成功，1为支付失败，2为支付密码错误，3为支付余额不足
     * @param string $doquery 失败时记录登录消息
     * @param int $zhongduan 登录终端 ,array('pc','wap','weixin')
     */
    protected function userPayLog($uid,$status,$doquery,$zhongduan){
    
    	$model = M('pay_log');
    	$model->uid = $uid;
    	$model->status = $status;
    	$model->do_query = $doquery;
    	$model->dotime = time();
    	$model->ip = get_client_ip();
    	$model->zhongduan = $zhongduan;
    	$model->add();
    	return TRUE;
    }
    

    /**
     * 
     * @return type生成带毫秒的时间戳
     */
    protected  function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }
    
    
      //密码加密
    protected function md5pw($password){
        $str = 'yjldfeWSC644!';
        $password = $str.$password;
        return substr(md5("$password"),8,16);
    }
    
    
        //字符过滤
    protected function safe_replace($string) {
        if(is_array($string)){ 
           $string=implode('，',$string);
           $string=htmlspecialchars(str_shuffle($string));
        } else{
            $string=htmlspecialchars($string);
        }
        $string = str_replace('%20','',$string);
        $string = str_replace('%27','',$string);
        $string = str_replace('%2527','',$string);
        $string = str_replace('*','',$string);
        $string = str_replace('"','&quot;',$string);
        $string = str_replace("'",'',$string);
        $string = str_replace('"','',$string);
        $string = str_replace(';','',$string);
        $string = str_replace('<','&lt;',$string);
        $string = str_replace('>','&gt;',$string);
        $string = str_replace("{",'',$string);
        $string = str_replace('}','',$string);
        return $string;
    }
    
    /**
     * 截取中文字符串
     * @param type $str
     * @param type $start
     * @param type $length
     * @param type $charset
     * @param type $suffix
     * @return type
     */
    protected function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true){
        if(function_exists("mb_substr")){
            $slice= mb_substr($str, $start, $length, $charset);
        }elseif(function_exists('iconv_substr')) {
            $slice= iconv_substr($str,$start,$length,$charset);
        }else{
            $re['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
            $re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
            $re['gbk'] = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
            $re['big5'] = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("",array_slice($match[0], $start, $length));
        }    
            $fix='';
            if(strlen($slice) < strlen($str)){
                $fix='...';
            }
            return $suffix ? $slice.$fix : $slice;
    }

    
   
    
    
    
    
    /**
    * 系统邮件发送函数
    * @param string $to    接收邮件者邮箱
    * @param string $name  接收邮件者名称
    * @param string $subject 邮件主题 
    * @param string $body    邮件内容
    * @param string $attachment 附件列表
    * @return boolean 
    */
   protected function send_mail($to,$to2='', $name, $subject = '', $body = '', $attachment = null){
       $tmpconfig = M('webconfig')->select();
       foreach ($tmpconfig as $k=>$v){
           $config[$v['codetag']] = $v['value'];
       }
       vendor('PHPMailer.PHPMailerAutoload'); //从PHPMailer目录导class.phpmailer.php类文件
       $mail             = new \PHPMailer(); //PHPMailer对象
       $mail->CharSet    = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
       $mail->IsSMTP();  // 设定使用SMTP服务
       $mail->SMTPDebug  = 0;                     // 关闭SMTP调试功能
                                                  // 1 = errors and messages
                                                  // 2 = messages only
       $mail->SMTPAuth   = true;                  // 启用 SMTP 验证功能
       $mail->SMTPSecure = 'ssl';                 // 使用安全协议
       $mail->Host       = $config['SMTP_HOST'];  // SMTP 服务器
       $mail->Port       = $config['SMTP_PORT'];  // SMTP服务器的端口号
       $mail->Username   = $config['SERVER_EMAIL_NAME'];  // SMTP服务器用户名
       $mail->Password   = $config['SERVER_EMAIL_PASSWD'];  // SMTP服务器密码
       $mail->SetFrom($config['SERVER_EMAIL_NAME'], $config['SERVER_EMAIL_NICK']);
       $replyEmail       = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];
       $replyName        = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];
       $mail->AddReplyTo($replyEmail, $replyName);
       $mail->Subject    = $subject;
       $mail->MsgHTML($body);
       $mail->AddAddress($to, $name);
       if(!empty($to2)){
          $mail->addAddress("$to2","$to2"); 
       }
       if(is_array($attachment)){ // 添加附件
           foreach ($attachment as $file){
               is_file($file) && $mail->AddAttachment($file);
           }
       }
       return $mail->Send() ? true : $mail->ErrorInfo;
    }

    /**
     * 发送邮箱给管理员
     * @param type $subject
     * @param type $body
     * @param type $attachment
     */
    protected function send_to_admin($subject = '', $body = '', $attachment = null){
        $tmpconfig = M('webconfig')->select();
        foreach ($tmpconfig as $v){
            $config[$v['codetag']] = $v['value'];
        }
        return $this->send_mail($config['ADMIN_EMAL_NAME'],$config['ADMIN_EMAL_NAME2'],$config['ADMIN_EMAL_NAME'],$subject,$body,$attachment);
    }
    
    /**
     * 提交生成订单
     * @param type $price 订单总金额
     * @param type $tag 支付信息id
     * @param type $uid 下单用户
     * @param type $wuliu 物流id
     * @param type $shipping_address 收货地址 id
     * @param type $payid 支付方式id
     * @param type $referer 来源地址
     * @param type $pay_type 付款类型，余额支付或者其他在线支付
     */
    protected function add_order($price = 0,$tag=0,$uid= '',$wuliu=0,$shipping_address=0,$payid =0,$referer='',$pay_type=0){
        $order = M("order");
        $order->onid = $this->create_orderid();
        $order->uid = $uid;
        $order->tag = $tag;
        $order->count_price = $price;
        $order->express_id = $wuliu;
        $order->express_name = $this->get_wuliu_name($wuliu);
        $order->cod_price = $this->get_cod_price($wuliu);
        $order->moblie = $this->get_shipping_moblie($shipping_address);
        $order->shipping_address = $this->get_shipping_address($shipping_address);
        $order->shipping_consignee =  $this->get_shipping_name($shipping_address);
        $order->pay_name = $this->get_pay_name($payid);
        $order->pay_id = $payid;
        $order->referer = $referer;
        $order->pay_type = $pay_type;
        $order->posttime = time();
        return $order->add();
    
        //
    }
    
    /**
     * 提交订单商品详细
     * @param type $oid  由add_order() 生成的订单时返回的 id
     * @param type $is_tuiguang 是否是推广链接购买的
     * @param type $goods 本次所购买的商品id 数组
     * @param type $price 本次所购买的商品价格 数组
     * @param type $num 本次所购买的商品 数量 数组
     * @param type $color 本次所购买的商品 颜色属性 数组
     * @param type $size 本次所购买的商品 规格大小 数组
     */
    protected function add_goods_order($oid=0,$is_tuiguang=0,$goods=array(),$price=array(),$num=array(),$color=array(),$size=array()){
        $order_goods = M("order_goods");
        foreach ($goods as $k=>$v){
            $order_goods->oid = $oid;
            $order_goods->good_id = $v;
            $order_goods->goods_name = $this->get_goods_name($v);
            $order_goods->goods_img = $this->get_goods_listimg($v);
            $order_goods->goods_num = $num[$k];
            $order_goods->goods_price = $price[$k];
            if($this->get_goods_is_point($v)){
                $order_goods->goods_point = $this->get_goods_point($v);
            }else{
                $order_goods->goods_point = 0;
            }
            $order_goods->goods_color = $color[$k];
            $order_goods->goods_size = $size[$k];
            $order_goods->is_fenxiao = $is_tuiguang;
            if($is_tuiguang >0){
                $order_goods->fencheng = $this->get_goods_price($v,$price[$k]);
            }
            $order_goods->create_time = time();
            $order_goods->add();
        }
        return TRUE;
    }
    
    /**
     * 添加推广订单信息
     * @param int $uid 购物者id
     * @param string $referfer_url 购物订单来源
     * @param int $orderid 订单id
     * @param float $count_price 订单金额
     * @param float $count_get_price 订单总分成金额
     */
    protected function add_fangke_order($uid=0,$referfer_url='',$orderid=0,$count_price=0,$count_get_price=0){
    
        $order = M('fangke_order');
        $order->uid = $uid;
        $order->referfer_url = $referfer_url;
        $order->orderid = $orderid;
        $order->count_price = $count_price;
        $order->count_get_price = $count_get_price;
        $fangke_user = M('fangke_user')->where(array('uid'=>$uid))->find();
        $order->pid1 = $fangke_user['pid'];
        $order->pid2 = $fangke_user['pid2'];
        $order->pid3 = $fangke_user['pid3'];
        $order->pid4 = $fangke_user['pid4'];
        $order->pid5 = $fangke_user['pid5'];
        $tmpconfig = M('webconfig')->select();
        foreach ($tmpconfig as $k => $v) {
            $config[$v['codetag']] = $v['value'];
        }
        $pidNum = $this->get_user_pid_num($uid);
        switch ($pidNum){
            case 1:
                $order->get_price1 = $count_get_price;
                break;
            case 2:
                $order->get_price1 = $config['GOODS_INCOME_PROP1']*0.01*$count_get_price;
                $order->get_price2 = ($config['GOODS_INCOME_PROP2']+$config['GOODS_INCOME_PROP3'])*$count_get_price;
                break;
            case 3:
                $order->get_price1 = $config['GOODS_INCOME_PROP1']*0.01*$count_get_price;
                $order->get_price2 = $config['GOODS_INCOME_PROP2']*0.01*$count_get_price;
                $order->get_price3 = $config['GOODS_INCOME_PROP3']*0.01*$count_get_price;
            case 4:
                $order->get_price1 = $config['GOODS_INCOME_PROP1']*0.01*$count_get_price;
                $order->get_price2 = $config['GOODS_INCOME_PROP2']*0.01*$count_get_price;
                $order->get_price3 = $config['GOODS_INCOME_PROP3']*0.01*$count_get_price;
                $order->get_price4 = $config['GOODS_INCOME_PROP4']*0.01*$count_get_price;
                break;
            case 5:
                $order->get_price1 = $config['GOODS_INCOME_PROP1']*0.01*$count_get_price;
                $order->get_price2 = $config['GOODS_INCOME_PROP2']*0.01*$count_get_price;
                $order->get_price3 = $config['GOODS_INCOME_PROP3']*0.01*$count_get_price;
                $order->get_price4 = $config['GOODS_INCOME_PROP4']*0.01*$count_get_price;
                $order->get_price5 = $config['GOODS_INCOME_PROP5']*0.01*$count_get_price;
                break;
            default :
                $order->get_price1 = 0;
                $order->get_price2 = 0;
                $order->get_price3 = 0;
                $order->get_price4 = 0;
                $order->get_price5 = 0;
                break;
        }
    
        $order->order_status = 0;
        $order->time = time();
    
        $result = $order->add();
        if($result){
            return $result;
        }else{
            return false;
        }
    }


/*************************************** 会员 *************************************************/    

    /**
     * 获取用户手机
     * @param type $uid 用户id
     */
    protected function get_user_phone($uid){
        $mode = M("User");
        $data = $mode->find($uid);
        return $data['phone'];
    }

    /**
     * 获取用户昵称
     * @param type $uid 用户id
     */
    protected function get_user_nickname($uid){
        $mode = M("User");
        $data = $mode->find($uid);
        return $data['nickname'];
    }
    
    
    
    
    /**
     * 操作记录
     * @param type $uid //操作管理员id
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
    
    
    /**
     * 浏览器类型
     * @param type $Agent
     * @return string
     */
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

    
}