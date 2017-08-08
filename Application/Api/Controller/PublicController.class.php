<?php
namespace Api\Controller;
use Common\Controller\BaseController;
//use Api\Controller\WebauthController;
use Lib\Alidayu\TopSdk;
use Lib\Alidayu\top\TopClient;
use Lib\Alidayu\top\request\AlibabaAliqinFcSmsNumSendRequest;
use Lib\Api\Weixinapi\WeiChat;
use Lib\Api\QQapi\Oauth;
use Lib\Api\QQapi\QC;
use Lib\Api\Weiboapi\SaeTOAuthV2;
use Lib\Api\Weiboapi\SaeTClientV2;

/**
 * 基础类，登录，注册等功能模块控制器
 * @author karl 2017-07-03 12:02:32
 *
 */
class PublicController extends BaseController {

    public function _initialize()
    {
        header('Content-type:text/html;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
    }

    /***    手机号码登录 by karl  2017-07-03 11:16:43     ***/
    public function login(){

        $User = M('User');
        $LoginLog = M('LoginUserLog');
        $Sms = M('Sms');

        if(IS_POST){
            $phone = I('post.userPhone');   //手机号码
            $sms = I('post.validateCode');  //短信验证码
            $type = I('post.type'); //验证码类别

            //查询最新一条短信记录
            $smsdata = $Sms->order('posttime desc')->where(array('phone' => $phone, 'type' => $type))->find();
            $time = time();
            $timediff = $time - $smsdata['posttime'];

            //计算时间
            $days = intval($timediff/86400);
            $remain = $timediff%86400;
            $hours = intval($remain/3600);
            $remain = $remain%3600;
            $mins = intval($remain/60);
            $secs = $remain%60;

            //短信验证码3分钟失效
            if ($mins > 3){
                $results = array(
                    'status' => 1,
                    'errcode' => 1,
                    'msg' => '短信超时！',
                );
                echo dexit($results);
                exit;
            }

            //判断验证码是否正确
            if ($smsdata['code'] != $sms) {
                $results = array(
                    'status' => 1,
                    'errcode' => 2,
                    'msg' => '验证码不正确！',
                );
                echo dexit($results);
                exit;
            }
            //查找用户信息
            $field = 'password,pay_pwd,true_msg,ext_msg';
            $userInfo = $User->field($field,true)->where(array('phone' => $phone))->find();

            if ($userInfo){ //已存在用户信息

                $this->userLoginLog($userInfo['id'], 0, '', 1); //用户登录日志

                //用户信息
                $user=array();
                $user['uid'] =$userInfo['id'];
                $user['nickName'] =$userInfo['nickname'];
                $user['avatar'] =$userInfo['avatar'];
                $user['hasModifyGender'] =!$userInfo['modify_gender']?false:true;
                $user['totalMon'] =$userInfo['totalmon'];
                $user['regTime'] =(int)$userInfo['reg_time'];
                $user['isAuth'] =!$userInfo['is_auth']?false:true;
                $user['phone'] =$userInfo['phone']?$userInfo['phone']:null;
                $user['signature'] =$userInfo['signature'];
                $user['payPwd'] = !$userInfo['pay_pwd']?false:true;
                $user['wechatOpenid'] = !$userInfo['wechat_openid']?false:$userInfo['wechat_openid'];
                $user['wechatNickname'] = !$userInfo['wechat_nickname']?null:$userInfo['wechat_nickname'];
                $user['qqOpenid'] = !$userInfo['qq_openid']?false:$userInfo['qq_openid'];
                $user['weiboOpenid'] = !$userInfo['weibo_openid']?false:$userInfo['weibo_openid'];
                $user['bindFlag'] = !$userInfo['bind_flag']?false:true;
                $user['isRegister'] = true;

                //返回用户信息
                Json(0,'user',$user,'登录成功');
                exit;
            }else { //无用户信息

                $data=array();
                $totalmon=10;
                $data['phone'] =$phone;
                $data['nickname'] =mb_substr($phone,0,3)."****".mb_substr($phone,-4);
                $data['avatar'] =get_url("/Uploads/userAvatar/avatar.png");
                $data['totalmon'] =$totalmon;
                $data['reg_time'] =$time;
                $data['status'] =1;
                $data['is_auth'] =0;
                $data['bind_flag'] =1;

                //开启事务
                $m=M();
                $m->startTrans();

                $add_id=$User->add($data);
                if(!$add_id){ //添加用户信息失败

                    $m->rollback();//事务回滚

                    Json(1,'errcode',3,'用户信息保存失败!');
                    exit;
                }

                $userInfo = $User->field($field,true)->where(array('id' => $add_id))->find();

                $user=array();
                $user['uid'] =$userInfo['id'];
                $user['nickName'] =$userInfo['nickname'];
                $user['avatar'] =$userInfo['avatar'];
                $user['hasModifyGender'] =!$userInfo['modify_gender']?false:true;
                $user['totalMon'] =(int)$userInfo['totalmon'];
                $user['regTime'] =(int)$userInfo['reg_time'];
                $user['isAuth'] =!$userInfo['is_auth']?false:true;
                $user['phone'] =$userInfo['phone']?$userInfo['phone']:null;
                $user['signature'] =$userInfo['signature'];
                $user['payPwd'] = !$userInfo['pay_pwd']?false:true;
                $user['wechatOpenid'] = !$userInfo['wechat_openid']?false:$userInfo['wechat_openid'];
                $user['wechatNickname'] = !$userInfo['wechat_nickname']?null:$userInfo['wechat_nickname'];
                $user['qqOpenid'] = !$userInfo['qq_openid']?false:$userInfo['qq_openid'];
                $user['weiboOpenid'] = !$userInfo['weibo_openid']?false:$userInfo['weibo_openid'];
                $user['bindFlag'] = !$userInfo['bind_flag']?false:true;
                $user['isRegister'] = false;

                //更新登录时间
                if($User->where(array('id' => $userInfo['id']))->save(array('last_time'=>$time))){
                    $content = '你的心愿之旅即将开启，我是你的全能客服小微！再小的心愿都不要放弃去实现它哦，让你的心愿一点一点实现就是我最大的心愿！';
                    $content2 = '微心愿已经在你的仓库里装入了心愿豆，一定要好好保管它哦，因为我们将会有好多兑换活动会一波儿接一波儿的带你飞！';
                    $content3 = '恭喜你获得微心愿赠送的'.$totalmon.'颗心愿豆';
                    $this->sendMsg($add_id, '1', '', '', '1','','', $content);
                    $this->sendMsg($add_id, '1', '', '', '1','','', $content2);
                    $this->sendMsg($add_id, '3', '', '', '3','','', $content3);
                    $this->userLoginLog($userInfo['id'], 0, '', 2);   //用户登录日志

                    //添加到账单
                    $bill=array(
                        'uid'=>$add_id,
                        'type'=>10,
                        'wishType'=>0,
                        'mon'=>$totalmon,
                        'posttime'=>time(),
                        'is_finish'=>1,
                    );
                    M('bill')->add($bill);

                    $m->commit();//提交事务
                }

                //返回用户信息
                Json(0,'user',$user,'注册成功!');
                exit;
            }
        }
    }
    
    /***    绑定手机号 by karl 2017-07-03 12:02:46   ***/
    public function phoneBinding(){
        $User = M('User');
        $Sms = M('Sms');

        if(IS_POST){

            $uid = I('post.uid');   //用户id
            $phone = I('post.userPhone');   //手机号码
            $sms = I('post.validateCode');  //短信验证码
            $type = I('post.type'); //短信类型

            if(!$uid || !$phone || !$sms){  //判断信息是否完整
                $results = array(
                    'status' => 1,
                    'errcode' => 1,
                    'msg' => '信息不完整！',
                );
                echo dexit($results);
                exit;
            }
            //查找用户
            $userInfo=$User->where(array('id' => $uid))->find();
            if(!$userInfo){ //判断用户是否存在
                $results = array(
                    'status' => 1,
                    'errcode' => 2,
                    'msg' => '找不到用户信息！',
                );
                echo dexit($results);
                exit;
            }

            if($userInfo['phone']){ //判断用户是否已经绑定手机号码
                $results = array(
                    'status' => 1,
                    'errcode' => 3,
                    'msg' => '您已绑定手机号码！',
                );
                echo dexit($results);
                exit;
            }else{
                $user=$User->where(array('phone' => $phone))->find();
                if($user){
                    $results = array(   //判断用手机号码是否已经被绑定
                        'status' => 1,
                        'errcode' => 4,
                        'msg' => '该手机号码已绑定用户！',
                    );
                    echo dexit($results);
                    exit;
                }
            }

            //查询最新一条短信记录
            $smsdata = $Sms->order('posttime desc')->where(array('phone' => $phone, 'type' => $type))->find();
            $time = time();
            $timediff = $time - $smsdata['posttime'];

            //计算时间
            $days = intval($timediff/86400);
            $remain = $timediff%86400;
            $hours = intval($remain/3600);
            $remain = $remain%3600;
            $mins = intval($remain/60);
            $secs = $remain%60;

            //短信验证码3分钟失效
            if ($mins > 3){
                $results = array(
                    'status' => 1,
                    'errcode' => 5,
                    'msg' => '短信超时！',
                );
                echo dexit($results);
                exit;
            }else {
                $code = $smsdata['code'];
            }

            //判断验证码是否正确
            if ($code != $sms){
                $results = array(
                    'status' => 1,
                    'errcode' => 6,
                    'msg' => '验证码不正确！',
                );
                echo dexit($results);
                exit;
            }
            //绑定
            if($User->where(array('id'=>$uid))->save(array('phone'=>$phone))){

                $user=array();
                $user['uid'] =$userInfo['id'];
                $user['nickName'] =$userInfo['nickname'];
                $user['avatar'] =$userInfo['avatar'];
                $user['hasModifyGender'] =!$userInfo['modify_gender']?false:true;
                $user['totalMon'] =$userInfo['totalmon'];
                $user['regTime'] =(int)$userInfo['reg_time'];
                $user['isAuth'] =!$userInfo['is_auth']?false:true;
                $user['phone'] =$phone;
                $user['signature'] =$userInfo['signature'];
                $user['payPwd'] = !$userInfo['pay_pwd']?false:true;
                $user['wechatOpenid'] = !$userInfo['wechat_openid']?false:$userInfo['wechat_openid'];
                $user['wechatNickname'] = !$userInfo['wechat_nickname']?null:$userInfo['wechat_nickname'];
                $user['qqOpenid'] = !$userInfo['qq_openid']?false:$userInfo['qq_openid'];
                $user['weiboOpenid'] = !$userInfo['weibo_openid']?false:$userInfo['weibo_openid'];
                $user['bindFlag'] = !$userInfo['bind_flag']?false:true;
                $results = array(
                    'status' => 0,
                    'msg' => '绑定成功！',
                    'user' => $user,
                );
                echo dexit($results);
                exit;
            }
            $results = array(
                'status' => 1,
                'errcode' => 7,
                'msg' => '绑定失败！',
            );
            echo dexit($results);
            exit;
        }
    }



    /***    返回微信登录用户信息 by karl 2017-07-03 11:51:40  ***/
    public function returnWechatUserinfo(){
        $aData = (array) I('get.');
        if (!isset($aData['code'])) {
            $results = array(
                'status' => 1,
                'errcode' => 1,
                'msg' => '非法操作！',
            );
            echo dexit($results);
            exit;
        }
        //验证code信息
        $oWeiChat = new WeiChat();
        $aAccessTokey = $oWeiChat->getOauthAccessTokeyByCode($aData['code']);
        if (isset($aAccessTokey['errcode']) && $aAccessTokey['errcode'] == 40029) {
            $aAccessTokey = $oWeiChat->getRefreshAccessTokey();
        }
        //查询微信用户信息
        $aUserInfo = $oWeiChat->getUserInfoByAccessTokey($aAccessTokey['access_token'], $aAccessTokey['openid']);
        if (isset($aUserInfo['errcode']) && $aUserInfo['errcode'] == 40003) {
            $results = array(
                'status' => 1,
                'errcode' => 2,
                'msg' => 'WeChat errcode:'.$aUserInfo['errcode'],
            );
            echo dexit($results);
            exit;
        }

        if (!empty($aData['type']) && !empty($aData['uid'])) {

            $User = M('user');
            $userInfo = $User->where(array('id' => $aData['uid']))->find();
            if (empty($userInfo)) {
                $results = array(
                    'status' => 1,
                    'errcode' => 3,
                    'msg' => '获取用户信息失败！',
                );
                echo dexit($results);
                exit;
            }
            $userinfo = $User->where(array('wechat_openid' => $aUserInfo['openid']))->find();
            if($userinfo){
                $results = array(
                    'status' => 1,
                    'errcode' => 3,
                    'msg' => '该微信号已绑定用户！',
                );
                echo dexit($results);
                exit;
            }

            $data=array();
            $data['wechat_openid']=$aUserInfo['openid'];
            $data['wechat_nickname'] = $aUserInfo['nickname'];
            $User->where(array('id' => $aData['uid']))->save($data);

            $user=array();
            $user['uid'] =$userInfo['id'];
            $user['nickName'] =$userInfo['nickname'];
            $user['avatar'] =$userInfo['avatar'];
            $user['totalMon'] =$userInfo['totalmon'];
            $user['regTime'] =(int)$userInfo['reg_time'];
            $user['isAuth'] =!$userInfo['is_auth']?false:true;
            $user['phone'] =$userInfo['phone']?$userInfo['phone']:null;
            $user['signature'] =$userInfo['signature'];
            $user['payPwd'] = !$userInfo['pay_pwd']?false:true;
            $user['wechatOpenid'] = !$aUserInfo['openid']?false:$aUserInfo['openid'];
            $user['wechatNickname'] = !$aUserInfo['nickname']?null:$aUserInfo['nickname'];
            $user['qqOpenid'] = !$userInfo['qq_openid']?false:$userInfo['qq_openid'];
            $user['weiboOpenid'] = !$userInfo['weibo_openid']?false:$userInfo['weibo_openid'];
            $user['bindFlag'] = !$userInfo['bind_flag']?false:true;
            $user['isRegister'] = true;

            //返回用户信息
            $results = array(
                'status' => 0,
                'msg' => '绑定成功！',
                'user' => $user,
            );
            echo dexit($results);
            exit;

        }

        //用户信息操作
        if ($this->_doLogin($aUserInfo)) {

            $User = M('user');
            $userInfo = $User->where(array('wechat_openid' => $aUserInfo['openid']))->find();
            if(!$userInfo){
                $results = array(
                    'status' => 1,
                    'errcode' => 3,
                    'msg' => '保存用户信息失败！',
                );
                echo dexit($results);
                exit;
            }
            $user=array();
            $user['uid'] =$userInfo['id'];
            $user['nickName'] =$userInfo['nickname'];
            $user['avatar'] =$userInfo['avatar'];
            $user['totalMon'] =$userInfo['totalmon'];
            $user['regTime'] =(int)$userInfo['reg_time'];
            $user['isAuth'] =!$userInfo['is_auth']?false:true;
            $user['phone'] =$userInfo['phone']?$userInfo['phone']:null;
            $user['signature'] =$userInfo['signature'];
            $user['payPwd'] = !$userInfo['pay_pwd']?false:true;
            $user['wechatOpenid'] = !$userInfo['wechat_openid']?false:$userInfo['wechat_openid'];
            $user['wechatNickname'] = !$userInfo['wechat_nickname']?null:$userInfo['wechat_nickname'];
            $user['qqOpenid'] = !$userInfo['qq_openid']?false:$userInfo['qq_openid'];
            $user['weiboOpenid'] = !$userInfo['weibo_openid']?false:$userInfo['weibo_openid'];
            $user['bindFlag'] = !$userInfo['bind_flag']?false:true;
            $user['isRegister'] = true;
            $this->userLoginLog($userInfo['id'], 0, '', 2);   //用户登录日志
            //返回用户信息
            $results = array(
                'status' => 0,
                'msg' => '登录成功！',
                'user' => $user,
            );
            echo dexit($results);
            exit;
        }
        $results = array(
            'status' => 1,
            'errcode' => 4,
            'msg' => '操作失败！',
        );
        echo dexit($results);
        exit;
    }

    /***    微信用户信息操作 by karl 2017-07-03 12:04:35  ***/
    private function _doLogin($aLoginData) {
        $mTable = M('User');//查询用户信息
        $aResult = $mTable->where(array('wechat_openid' => $aLoginData['openid']))->find();
        if ($aResult) { //已存在用户信息
            $result = $this->_updateUserInfo($aResult['id'], $aLoginData);
        } else {    //无用户信息
            $result = $this->_addUser($aLoginData);
//            $this->sendMsg($result,'','','','1',"我们总算等到你了！欢迎来到微心愿，现在是公测期间，您的心愿豆已到账，希望您在这里分享心愿故事，结交一群朋友，助您心愿达成。");
        }
        $aUserInfo = $mTable->where(array('wechat_openid' => $aLoginData['openid']))->find();
        if (!$aUserInfo) {
            return false;
        }

        return true;
    }

    /***    修改微信用户信息 by karl 2017-07-03 12:04:35  ***/
    private function _updateUserInfo($id, $aUserInfo) {

        $data['last_time'] = time();
//        if ($aUserInfo['sex'] == 2){
//            $data['gender'] = 1;
//        }else{
//            $data['gender'] = 0;
//        }
//        $data['nickname']=$aUserInfo['nickname'];
        return M('User')->where(array('id' => $id))->save($data);
    }

    /***    添加微信用户信息 by karl 2017-07-03 12:04:35  ***/
    private function _addUser($aUserInfo) {
        if(empty($aUserInfo)){
            return false;
        }
        if ($aUserInfo['sex'] == 2){
            $data['gender'] = 1;
        }else{
            $data['gender'] = 0;
        }

        $time=time();
        $totalmon=10;
        $data['totalmon'] =$totalmon;
        $data['status'] =1;
        $data['is_auth'] =0;
        $data['bind_flag'] =0;
        $data['avatar'] = $aUserInfo['headimgurl'];
        $data['wechat_openid'] = $aUserInfo['openid'];
        $data['nickname'] = $aUserInfo['nickname'];
        $data['wechat_nickname'] = $aUserInfo['nickname'];
        $data['reg_time'] = $time;
        $data['last_time'] = $time;
        $id=M('User')->add($data);
        $content = '你的心愿之旅即将开启，我是你的全能客服小微！再小的心愿都不要放弃去实现它哦，让你的心愿一点一点实现就是我最大的心愿！';
        $content2 = '微心愿已经在你的仓库里装入了心愿豆，一定要好好保管它哦，因为我们将会有好多兑换活动会一波儿接一波儿的带你飞！';
        $content3 = '恭喜你获得微心愿赠送的'.$totalmon.'颗心愿豆';
         //添加到账单
        $bill=array(
            'uid'=>$id,
            'type'=>10,
            'wishType'=>0,
            'mon'=>$totalmon,
            'posttime'=>time(),
            'is_finish'=>1,
        );
        $bills=M('bill')->add($bill);
        
        $this->sendMsg($id, '1', '', '', '1','','', $content);
        $this->sendMsg($id, '1', '', '', '1','','', $content2);
        $this->sendMsg($id, '1', '', '', '3','','', $content3);

        return $id;

    }

    /********************************************* 微信登录部分 start ************************************************************/
    /**
     * 微信登录
     *
     */
    public function weiLogin(){
        $oWeiChat = new WeiChat();
        $referer = 'http://' . $_SERVER['HTTP_HOST'] . (!empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
        $ref_url=explode('ref_url/',$referer);
        //获取回调地址
        $authCallbackURL = urlencode('http://'.$_SERVER['HTTP_HOST'].U('Public/authCallback',array("ref_url"=>$ref_url[1])));
        //进行获取用户信息授权申请
        $oWeiChat->login($authCallbackURL);

    }

    /**
     * 公众号获取微信用户授权 回调方法
     *
     * code 回调至此，继续其他业务操作
     */
    public function authCallback(){
        $aData = (array) I('get.');
        if (!isset($aData['state'])) {
            $this->error('非法操作');
        }
        //用户取消
        if ($aData['state'] != 1) {
            $this->redirect('public/login');
        }
        $oWeiChat = new WeiChat();
        $aAccessTokey = $oWeiChat->getOauthAccessTokeyByCode($aData['code']);
        if (isset($aAccessTokey['errcode']) && $aAccessTokey['errcode'] == 40029) {
            $aAccessTokey = $oWeiChat->getRefreshAccessTokey();
        }
        $aUserInfo = $oWeiChat->getUserInfoByAccessTokey($aAccessTokey['access_token'], $aAccessTokey['openid']);
        if (isset($aUserInfo['errcode']) && $aUserInfo['errcode'] == 40003) {
            $this->redirect('public/login');
        }
        if ($this->_doLogin($aUserInfo)) {
            if(session('islogin')){
                redirect(U('App/Setting/accountSecurity'));return;
            }
            $this->redirect('App/Index/index');
            //跳转至来源地址
            return;
        }
        $this->error('登录失败', U('public/login'));

    }

    /**************************************************** 微信登录end *****************************************************************/

    /********************************************* QQ登录部分 start ************************************************************/
    /**
     * QQ登录
     *
     */
    public function qqlogin(){
        $qqoauth = new QC();
        //获取回调地址
        
        $authCallbackURL = urlencode('http://'.$_SERVER['HTTP_HOST'].U('App/Public/qqCallback'));
        //进行获取用户信息授权申请
        $qqoauth->qq_login($authCallbackURL);
    }
    
    /**
     * 公众号获取微信用户授权 回调方法
     *
     * code 回调至此，继续其他业务操作
     */
    public function qqCallback(){   
        $qqback = new QC();
        $access_token = $qqback->qq_callback();
        $openid = $qqback->get_openid();
        $oauth = new QC($access_token,$openid);
        $uinfo = $oauth->get_user_info();
        $uinfo['openid'] = $openid;
        $tt = json_encode($uinfo);
//        file_put_contents('./qquinfo.txt', $tt);
        if (isset($uinfo['ret']) && $uinfo['ret'] == 100030){
            redirect(U('App/Public/login'));
        }
        if ($this->_doqqLogin($uinfo)){
            if(session('islogin')){
                redirect(U('App/Setting/accountSecurity'));return;
            }
            redirect(U('App/Index/index'));
            //跳转至来源地址
            return;
        }
        $this->error('登录失败！',U('App/Public/login'));
    }
    
    private function _doqqLogin($aLoginData) {
        $mTable = M('user_open');
        $aResult = $mTable->where(array('openid' => $aLoginData['openid'],'login_type'=>2))->find();
        if ($aResult) {
            $result = $this->_updateQQuInfo($aResult['id'], $aLoginData);
    
        } else {
            $result = $this->_addQQUser($aLoginData);
        }
        $aUserInfo = $mTable->where(array('openid' => $aLoginData['openid'],'login_type'=>2))->find();
        if (!$aUserInfo) {
            return false;
        }
        if($aUserInfo['uid']){
            session('uid', $aUserInfo['uid']);
        }
        session('UserInfo', $aUserInfo);
        //记录登录
        $this->userLoginLog($aUserInfo['id'],0,'',2);
    
        return true;
    }
    
    private function _updateQQuInfo($id, $aUserInfo) {
        $aUserInfo['last_time'] = time();
        if(session('islogin')){
            $aUserInfo['uid'] = session('uid');
        }
        return M('user_open')->where(array('id' => $id))->save($aUserInfo);
    }
    
    private function _addQQUser($aUserInfo) {
        if ($aUserInfo['gender'] == "男"){
            $aUserInfo['sex'] = 0;
        }else {
            $aUserInfo['sex'] = 1;
        }
        if(session('islogin')){
            $aUserInfo['uid'] = session('uid');
        }
        $aUserInfo['login_type'] = 2;
        $aUserInfo['toupic'] = $aUserInfo['figureurl_qq_1'];
        $aUserInfo['openid'] = $aUserInfo['openid'];
        $aUserInfo['nickname'] = $aUserInfo['nickname'];
        $aUserInfo['reg_time'] = time();
        $aUserInfo['last_time'] = time();
        return M('user_open')->add($aUserInfo);
    }
    
    
    /**************************************************** QQ登录end *****************************************************************/
    
    /********************************************* 微博登录部分 start ************************************************************/
    /**
     * 微博登录
     *
     */
    public function wblogin(){
        $wboauth = new SaeTOAuthV2(C('WEIBO.APPID'),C('WEIBO.APPSECRET'));
        //获取回调地址
        $authCallbackURL = $wboauth->getAuthorizeURL(C('WEIBO.CALLBACK'));

        //进行获取用户信息授权申请
        header("Location:$authCallbackURL");

    }
    
    /**
     * 公众号获取微博用户授权 回调方法
     *
     * code 回调至此，继续其他业务操作
     */
    public function wbCallback(){        
        $wbback = new SaeTOAuthV2(C('WEIBO.APPID'),C('WEIBO.APPSECRET'));
        if (isset($_REQUEST['code'])) {
            $keys = array();
            $keys['code'] = $_REQUEST['code'];
            $keys['redirect_uri'] = C('WEIBO.CALLBACK');
            try {
                $token = $wbback->getAccessToken( 'code', $keys ) ;
            } catch (OAuthException $e) {
            }
        }
        
        if ($token) {
            $_SESSION['token'] = $token;
            setcookie( 'weibojs_'.$wbback->client_id, http_build_query($token) );
            
            $c = new SaeTClientV2( C('WEIBO.APPID') , C('WEIBO.APPSECRET') , $_SESSION['token']['access_token'] );
            $ms  = $c->home_timeline(); // done
            $uid_get = $c->get_uid();
            $uid = $uid_get['uid'];
            $user_message = $c->show_user_by_id( $uid);//根据ID获取用户等基本信息
            $wbdata['weibo'] = $user_message['id'];
            $wbdata['nickname'] = $user_message['name'];
            $wbdata['wb_headimg'] = $user_message['profile_image_url'];
            if ($this->_dowbLogin($wbdata)){
                if(session('islogin')){
                    redirect(U('App/Setting/accountSecurity'));return;
                }
                redirect(U('App/Index/index'));
                //跳转至来源地址
                return;
            }
            
        }else {
            redirect(U('App/Public/login'));
        }

        $this->error('登录失败！',U('App/Public/login'));
    }
    
    private function _dowbLogin($aLoginData) {
        $mTable = M('user_open');
        $aResult = $mTable->where(array('openid' => $aLoginData['weibo'],'login_type'=>3))->find();
        if ($aResult) {
            $result = $this->_updateWBuInfo($aResult['id'], $aLoginData);
    
        } else {
            $result = $this->_addWBUser($aLoginData);
        }
        $aUserInfo = $mTable->where(array('openid' => $aLoginData['weibo'],'login_type'=>3))->find();
        if (!$aUserInfo) {
            return false;
        }
        if($aUserInfo['uid']){
            session('uid', $aUserInfo['uid']);
        }
        session('UserInfo', $aUserInfo);
        //记录登录
        $this->userLoginLog($aUserInfo['id'],0,'',2);
    
        return true;
    }
    
    private function _updateWBuInfo($id, $aUserInfo) {
        $aUserInfo['last_time'] = time();
        if(session('islogin')){
            $aUserInfo['uid'] = session('uid');
        }
        return M('user_open')->where(array('id' => $id))->save($aUserInfo);
    }
    
    private function _addWBUser($aUserInfo) {
        if ($aUserInfo['gender'] == "m"){
            $aUserInfo['sex'] = 1;
        }else {
            $aUserInfo['sex'] = 0;
        }
        if(session('islogin')){
            $aUserInfo['uid'] = session('uid');
        }
        $aUserInfo['login_type'] = 3;
        $aUserInfo['toupic'] = $aUserInfo['wb_headimg'];
        $aUserInfo['openid'] = $aUserInfo['weibo'];
        $aUserInfo['nickname'] = $aUserInfo['nickname'];
        $aUserInfo['reg_time'] = time();
        $aUserInfo['last_time'] = time();
        return M('user_open')->add($aUserInfo);
         
    }

    public function verify_c(){
        ob_clean();
        $Verify = new \Think\Verify();
        $Verify->codeSet = '0123456789';
        $Verify->length = 4;
        $Verify->entry();
    }
    
    /**************************************************** 微博登录end *****************************************************************/

    /***    发送短信操作 by karl 2017-07-03 12:04:35  ***/
    public function sendSms(){
        $Sms = M('Sms');

        if (IS_GET){
            $phone =I('get.userPhone',0,'string');
            $type = I('get.type',-1,'int');

//            cookie('phone',$phone,array('expire' => 180, 'prefix'=>'wxy_'));
            if($phone == 0){
                $results['status'] = 1;
                $results['errcode'] = 1;
                $results['msg'] = '号码非法';
                dexit($results);exit;
            }
            if ($type == -1){
                $results['status'] = 1;
                $results['errcode'] = 2;
                $results['msg'] = 'type为空';
                dexit($results);exit;
            }

            $sms = $Sms->order('posttime desc')->where(array('phone' => $phone, 'type' => $type))->select();

            if ($sms){
                $time = time();
                $timediff = $time - $sms[0]['posttime'];
                
                $days = intval($timediff/86400);
                $remain = $timediff%86400;
                $hours = intval($remain/3600);
                $remain = $remain%3600;
                $mins = intval($remain/60);
                $secs = $remain%60;
                
                if($mins < 1){  //时间未到，不能重复发送
                    $results['status'] = 1;
                    $results['errcode'] = 3;
                    $results['msg'] = '时间未到，不能重复发送';
                    dexit($results);exit;
                }else {
                    //生成验证码
                    $randnum = mt_rand(100000, 999999);
                    
                    $phonecode = $randnum;
                }
            }else {
                //生成验证码
                $randnum = mt_rand(100000, 999999);
                
                $phonecode = $randnum;
            }
            
            //发送短信操作        
            
            //将下载到的SDK里面的TopClient.php的$gatewayUrl的值改为沙箱地址:http://gw.api.tbsandbox.com/router/rest
            //正式环境时需要将该地址设置为：http://gw.api.taobao.com/router/rest
            
            $arr = array();
            
            $js = '{"code":"'.$phonecode.'","product":"微心愿"}';
            
            $c = new TopClient;
            $c->appkey = "23407975";
            $c->secretKey = "6fca5e6ec9e1184a563ce59ab7e6076b";
            
            $req = new AlibabaAliqinFcSmsNumSendRequest;
            $req->setExtend("1111111");
            $req->setSmsType("normal");
            $req->setSmsFreeSignName("微心愿");
            $req->setSmsParam($js);
            $req->setRecNum($phone);
            $req->setSmsTemplateCode("SMS_11685091");
//            $req->setSmsTemplateCode($this->smsTemplateCode($code));
            $resp = $c->execute($req);
            
            $res = $this->XML2Array($resp);
            
            if($res['result']['err_code'] == 0 && $res["result"]["success"] == "true"){
                $data = array(
                    'phone' => $phone,
                    'code' => $phonecode,
                    'posttime' => time(),
                    'type' => $type,
                );
                $Sms->data($data)->add();
                $results['status'] = 0;
                $results['msg'] = '短信发送成功';
                dexit($results);exit;
            }else {
                $results['status'] = 1;
                $results['errcode'] = 4;
                $results['msg'] = '短信发送失败';
                dexit($results);exit;
            }
        
        }
    }

    /***    短信模板 by karl 2017-07-03 12:04:35  ***/
    public function smsTemplateCode($code){

        switch($code){
            //注册
            case 1:
                return "SMS_11685091";
                break;
            //修改密码
            case 2:
                return "SMS_11685089";
                break;
            //修改支付密码
            case 3:
                return "SMS_30280098";
                break;
            //设置支付密码
            case 4:
                return "SMS_42125051";
                break;
            //绑定手机号码
            case 5:
                return "SMS_42050022";
                break;
        }
    }

    /***    XML转Array by karl 2017-07-03 12:04:35  ***/
    public function XML2Array($xml) {
        function normalizeSimpleXML($obj, &$result) {
            $data = $obj;
            if (is_object($data)) {
                $data = get_object_vars($data);
            }
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $res = null;
                    normalizeSimpleXML($value, $res);
                    if (($key == '@attributes') && ($key)) {
                        $result = $res;
                    } else {
                        $result[$key] = $res;
                    }
                }
            } else {
                $result = $data;
            }
        }
        normalizeSimpleXML($xml, $result);
        return $result;
    }
    /**
     * 会员登录日志
     * @param int $uid
     * @param int $status 登录状态 0是登录成功，1为登录失败，2注册成功，3注册失败，4修改密码成功，5修改密码失败
     * @param string $doquery 登录失败时记录登录消息
     * @param int $terminal 登录终端 ,array('wap','weixin')
     */
    protected function userLoginLog($uid,$status,$doquery,$terminal){
    
        $model = M('login_user_log');
        $model->uid = $uid;
        $model->status = $status;
        $model->doquery = $doquery;
        $model->dotime = time();
        $model->ip = get_client_ip();
        $model->terminal = $terminal;
        $model->add();
        return TRUE;
    }

}