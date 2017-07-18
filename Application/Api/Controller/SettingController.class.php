<?php
namespace App\Controller;
use Common\Controller\BaseController;
//use App\Controller\WebauthController;
use Lib\Api\Weixinapi\WeiChat;
use Lib\Api\Weixinapi\WeiXin;

/**
 * 动态类控制器 ，设置
 * @author
 *
 */
class SettingController extends BaseController
{

    public function index(){
       $uid = session('uid');
       $this->assign('uid', $uid);
       $this->display();
    }

    //账号与安全
    public function accountSecurity(){
        $User = M('User');
        $UserOpen = M('User_open');

        $uid = session('uid');
        //$uid = 4;
        cookie('jump_redeem', NULL);

        $user = $User->find($uid);
        if(!$user){
            $this->redirect('Index/index');exit();
        }
        session('islogin',1);
        $user['wechat']=$UserOpen->where(array('uid'=>session('uid'),'login_type'=>1))->find();
        $user['qq']=$UserOpen->where(array('uid'=>session('uid'),'login_type'=>2))->find();
        $user['weibo']=$UserOpen->where(array('uid'=>session('uid'),'login_type'=>3))->find();
        $this->assign('user', $user);

        $this->display();
    }

    //绑定信息 by karl
    public function bindingDetails(){

        $User = M('User');
        $UserOpen = M('User_open');
        $type=I('get.type',0,'int');
        switch($type){
            case 1:
                $this->assign('user',$User->where(array('uid'=>session('uid')))->find());
                $this->assign('ngs',1);
                $this->assign('type',4);
                break;
            case 2:
                $this->assign('user',$UserOpen->where(array('uid'=>session('uid'),'login_type'=>1))->find());
                $this->assign('type',1);
                break;
            case 3:
                $this->assign('user',$UserOpen->where(array('uid'=>session('uid'),'login_type'=>2))->find());
                $this->assign('type',2);
                break;
            case 4:
                $this->assign('user',$UserOpen->where(array('uid'=>session('uid'),'login_type'=>3))->find());
                $this->assign('type',3);
                break;
            default:
                $this->redirect('Setting/accountSecurity');
                break;
        }
        $this->display();
    }

    //解除绑定 by karl
    public function unbound(){
        $User = M('User');
        $UserOpen = M('User_open');
        $uid=session('uid');
        $type = I('post.type');
        $upinfo=$UserOpen->where(array('uid'=>$uid,'login_type'=>$type))->find();
        if(!$upinfo){
            $redata = array(
                'status' => 1,
                'msg' => '未找到绑定信息！',
            );
            echo json_encode($redata);exit();
        }

        $UserOpen->where(array('id'=>$upinfo['id'],'login_type'=>$type))->save(array('uid'=>0));
        $redata = array(
            'status' => 0,
            'msg' => '取消成功！',
        );
        echo json_encode($redata);exit();
    }

    //解除绑定 by karl
    public function unboundPhone(){
        $UserOpen = M('User_open');
        $uid=session('uid');
        $upList=$UserOpen->where(array('uid'=>$uid))->select();
        foreach($upList as $key => $val){
            $UserOpen->where(array('id'=>$val['id']))->save(array('uid'=>0));
        }
        $redata = array(
            'status' => 0,
            'msg' => '取消成功！',
        );
        echo json_encode($redata);exit();
    }

    //设置密码 by karl
    public function setPassword(){
        $User = M('User');
        $Sms = M('Sms');

        $uid = session('uid');
        //$uid = 4;

        if (IS_POST){
            $phone = I('post.phone');
            $sms = I('post.sms');
            $pwd = I('post.pwd');
            $type = I('post.type');

            $smsdata = $Sms->order('posttime desc')->where(array('phone' => $phone, 'type' => $type))->select();
            $time = time();
            $timediff = $time - $smsdata[0]['posttime'];


            $days = intval($timediff/86400);
            $remain = $timediff%86400;
            $hours = intval($remain/3600);
            $remain = $remain%3600;
            $mins = intval($remain/60);
            $secs = $remain%60;

            if ($mins > 3){
                $redata = array(
                    'status' => 4,
                    'msg' => '短信超时！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $code = $smsdata[0]['code'];
            }

            $user = $User->where(array('phone' => $phone, 'id' => $uid))->find();
            $pp = $this->md5pw($pwd);
            if ($code != $sms){
                $redata = array(
                    'status' => 1,
                    'msg' => '验证码不正确！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }elseif (empty($user)){
                $redata = array(
                    'status' => 2,
                    'msg' => '该用户号码不存在！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }elseif ($pp == $user['password']){
                $redata = array(
                    'status' => 4,
                    'msg' => '密码必须与原密码不一致！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $save = array(
                    'password' => $this->md5pw($pwd),
                );
                $res = $User->where(array('username' => $phone, 'id' => $uid))->data($save)->save();
                if ($res){
                    $redata = array(
                        'status' => 0,
                        'msg' => '设置成功！',
                        'list' => '',
                    );
                    echo json_encode($redata);
                    exit();
                }else {
                    $redata = array(
                        'status' => 3,
                        'msg' => '设置失败！',
                        'list' => '',
                    );
                    echo json_encode($redata);
                    exit();
                }
            }
        }else {
            $phone = $this->get_user_phone($uid);
            $tsms = $Sms->order('posttime desc')->where(array('type' => 1,'phone' => $phone))->select();
            $t = time();
            $timediff = $t - $tsms[0]['posttime'];
            $secs = 180 - $timediff;
            if ($secs > 0){
                $dstime = $secs;
            }else {
                $dstime = 0;
            }

            $this->assign('ds',$dstime);
            $this->assign('phone',$phone);
            $this->display();
        }

    }

    //修改登录密码 by karl
    public function modifyPassword(){
        $User = M('User');
        $Sms = M('Sms');

        $uid = session('uid');
        //$uid = 4;

        if (IS_POST){
            $phone = I('post.phone');
            $sms = I('post.sms');
            $pwd = I('post.pwd');
            $type = I('post.type');

            $smsdata = $Sms->order('posttime desc')->where(array('phone' => $phone, 'type' => $type))->select();
            $time = time();
            $timediff = $time - $smsdata[0]['posttime'];


            $days = intval($timediff/86400);
            $remain = $timediff%86400;
            $hours = intval($remain/3600);
            $remain = $remain%3600;
            $mins = intval($remain/60);
            $secs = $remain%60;

            if ($mins > 3){
                $redata = array(
                    'status' => 4,
                    'msg' => '短信超时！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $code = $smsdata[0]['code'];
            }

            $user = $User->where(array('phone' => $phone, 'id' => $uid))->find();
            $pp = $this->md5pw($pwd);
            if ($code != $sms){
                $redata = array(
                    'status' => 1,
                    'msg' => '验证码不正确！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }elseif (empty($user)){
                $redata = array(
                    'status' => 2,
                    'msg' => '该用户号码不存在！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }elseif ($pp == $user['password']){
                $redata = array(
                    'status' => 4,
                    'msg' => '密码必须与原密码不一致！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $save = array(
                    'password' => $this->md5pw($pwd),
                );
                $res = $User->where(array('username' => $phone, 'id' => $uid))->data($save)->save();
                if ($res){
                    $redata = array(
                        'status' => 0,
                        'msg' => '修改成功！',
                        'list' => '',
                    );
                    echo json_encode($redata);
                    exit();
                }else {
                    $redata = array(
                        'status' => 3,
                        'msg' => '修改失败！',
                        'list' => '',
                    );
                    echo json_encode($redata);
                    exit();
                }
            }
        }else {
            $phone = $this->get_user_phone($uid);
            $tsms = $Sms->order('posttime desc')->where(array('type' => 1,'phone' => $phone))->select();
            $t = time();
            $timediff = $t - $tsms[0]['posttime'];
            $secs = 180 - $timediff;
            if ($secs > 0){
                $dstime = $secs;
            }else {
                $dstime = 0;
            }

            $this->assign('ds',$dstime);
            $this->assign('phone',$phone);
            $this->display();
        }

    }


    //设置支付密码 by karl
    public function setPayPassword(){
        $User = M('User');
        $Sms = M('Sms');

        $uid = session('uid');
        //$uid = 4;
        $wishid = I('get.wishid',0,'int');
        $wish = isset($wishid) ? $wishid : 0 ;

        if (IS_POST){
            $phone = I('post.phone');
            $sms = I('post.sms');
            $pwd = I('post.pwd');
            $wish = I('post.wishid');
            $type = I('post.type');

            $smsdata = $Sms->order('posttime desc')->where(array('phone' => $phone, 'type' => $type))->select();
            $time = time();
            $timediff = $time - $smsdata[0]['posttime'];


            $days = intval($timediff/86400);
            $remain = $timediff%86400;
            $hours = intval($remain/3600);
            $remain = $remain%3600;
            $mins = intval($remain/60);
            $secs = $remain%60;

            if ($mins > 3){
                $redata = array(
                    'status' => 5,
                    'msg' => '短信超时！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $code = $smsdata[0]['code'];
            }

            $user = $User->where(array('phone'=>$phone,'id' => $uid))->find();
            if ($code != $sms){
                $redata = array(
                    'status' => 1,
                    'msg' => '验证码不正确！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }elseif (empty($user)){
                $redata = array(
                    'status' => 2,
                    'msg' => '该用户号码不存在！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $save = array(
                    'pay_pwd' => $this->md5pw($pwd),
                );
                $res = $User->where(array('id' => $uid))->data($save)->save();
                if ($res){
                    $wishdata = array(
                        'wishid' => session('wxy_wishid'),
                    );
                    file_put_contents('./wishdata.txt',$wishdata['wishid']);
                    $redata = array(
                        'status' => 0,
                        'msg' => '设置成功！',
                        'list' => $wishdata,
                    );
                    echo json_encode($redata);
                    exit();
                }else {
                    $redata = array(
                        'status' => 3,
                        'msg' => '设置失败！',
                        'list' => '',
                    );
                    echo json_encode($redata);
                    exit();
                }
            }
        }else {
            $phone = $this->get_user_phone($uid);
            $tsms = $Sms->order('posttime desc')->where(array('type' => 4,'phone' => $phone))->select();
            $t = time();
            $timediff = $t - $tsms[0]['posttime'];
            $secs = 180 - $timediff;
            if ($secs > 0){
                $dstime = $secs;
            }else {
                $dstime = 0;
            }

            $this->assign('ds',$dstime);
            $this->assign('phone',$phone);
            $this->assign('wish',$wish);
            $this->display();
        }

    }

    //修改支付密码 by karl
    public function modifyPayPassword(){
        $User = M('User');
        $Sms = M('Sms');

        $uid = session('uid');
        //$uid = 4;
        $wishid = I('get.wishid',0,'int');
        $wish = isset($wishid) ? $wishid : 0 ;

        if (IS_POST){
            $phone = I('post.phone');
            $sms = I('post.sms');
            $pwd = I('post.pwd');
            $wish = I('post.wishid');
            $type = I('post.type');

            $smsdata = $Sms->order('posttime desc')->where(array('phone' => $phone, 'type' => $type))->select();
            $time = time();
            $timediff = $time - $smsdata[0]['posttime'];


            $days = intval($timediff/86400);
            $remain = $timediff%86400;
            $hours = intval($remain/3600);
            $remain = $remain%3600;
            $mins = intval($remain/60);
            $secs = $remain%60;

            if ($mins > 3){
                $redata = array(
                    'status' => 5,
                    'msg' => '短信超时！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $code = $smsdata[0]['code'];
            }

            $user = $User->where(array('phone'=>$phone,'id' => $uid))->find();
            if ($code != $sms){
                $redata = array(
                    'status' => 1,
                    'msg' => '验证码不正确！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }elseif (empty($user)){
                $redata = array(
                    'status' => 2,
                    'msg' => '该用户号码不存在！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $save = array(
                    'pay_pwd' => $this->md5pw($pwd),
                );
                $res = $User->where(array('id' => $uid))->data($save)->save();
                if ($res){
                    $wishdata = array(
                        'wishid' => session('wxy_wishid'),
                    );
                    file_put_contents('./wishdata.txt',$wishdata['wishid']);
                    $redata = array(
                        'status' => 0,
                        'msg' => '设置成功！',
                        'list' => $wishdata,
                    );
                    echo json_encode($redata);
                    exit();
                }else {
                    $redata = array(
                        'status' => 3,
                        'msg' => '设置失败！',
                        'list' => '',
                    );
                    echo json_encode($redata);
                    exit();
                }
            }
        }else {
            $phone = $this->get_user_phone($uid);
            $tsms = $Sms->order('posttime desc')->where(array('type' => 4,'phone' => $phone))->select();
            $t = time();
            $timediff = $t - $tsms[0]['posttime'];
            $secs = 180 - $timediff;
            if ($secs > 0){
                $dstime = $secs;
            }else {
                $dstime = 0;
            }

            $this->assign('ds',$dstime);
            $this->assign('phone',$phone);
            $this->assign('wish',$wish);
            $this->display();
        }

    }

     //判断是否  登录 密码 == 支付 密码
    public function chkPayLog(){
        $User = M('User');
        $uid = session('uid');
        if (IS_POST){
            $pwd = I('post.pwd');
            $user = $User->where(array('id' => $uid))->find();
            $pp = $this->md5pw($pwd);

            if ($pp == $user['password']){
                $this->ajaxReturn(1);
            }else {
                $this->ajaxReturn(0);
            }
        }
    }


    //修改绑定的手机号 by karl
    public function modifyPhoneBd(){
        $User = M('User');
        $Sms = M('Sms');
        $uid = session('uid');

        if (IS_POST){
            $phone = I('post.phone');
            $sms = I('post.sms');
            $newphone = I('post.newphone');
            $type = I('post.type');

            $smsdata = $Sms->order('posttime desc')->where(array('phone' => $phone, 'type' => $type))->select();
            $time = time();
            $timediff = $time - $smsdata[0]['posttime'];


            $days = intval($timediff/86400);
            $remain = $timediff%86400;
            $hours = intval($remain/3600);
            $remain = $remain%3600;
            $mins = intval($remain/60);
            $secs = $remain%60;

            if ($mins > 3){
                $redata = array(
                    'status' => 5,
                    'msg' => '短信超时！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $code = $smsdata[0]['code'];
            }

            $user = $User->where(array('phone'=>$phone,'id' => $uid))->find();
            if ($code != $sms){
                $redata = array(
                    'status' => 1,
                    'msg' => '验证码不正确！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }elseif (empty($user)){
                $redata = array(
                    'status' => 2,
                    'msg' => '该用户号码不存在！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }else {
                $save = array(
                    'phone' => $newphone,
                );
                $res = $User->where(array('id' => $uid))->data($save)->save();
                if ($res){
                    $wishdata = array(
                        'wishid' => session('wxy_wishid'),
                    );
                    file_put_contents('./wishdata.txt',$wishdata['wishid']);
                    $redata = array(
                        'status' => 0,
                        'msg' => '修改成功！',
                        'list' => $wishdata,
                    );
                    echo json_encode($redata);
                    exit();
                }else {
                    $redata = array(
                        'status' => 3,
                        'msg' => '修改失败！',
                        'list' => '',
                    );
                    echo json_encode($redata);
                    exit();
                }
            }
        }else {
            $phone = $this->get_user_phone($uid);
            $tsms = $Sms->order('posttime desc')->where(array('type' => 4,'phone' => $phone))->select();
            $t = time();
            $timediff = $t - $tsms[0]['posttime'];
            $secs = 180 - $timediff;
            if ($secs > 0){
                $dstime = $secs;
            }else {
                $dstime = 0;
            }

            $this->assign('ds',$dstime);
            $this->assign('phone',$phone);
            $this->display();
        }

    }


    //意见反馈
    public function feedback(){
        $Feedback = M('Feedback');
        $QA = M('Qa');

        $uid = session('uid');
        //$uid = 4;
        if (IS_POST){
            $keystr = file_get_contents('./Data/filter.txt');
            $keystr = str_replace("\n", "|", $keystr);
//         	$strarr = explode(",", $keystr);


            $content = trim(I('post.des','0','string'));
            $title = trim(I('post.title','鎰忚鍙嶉','string'));

            if ($content == '0' || $title == '0')
                $this->error('非法输入！');

            $pattern = "/".$keystr."/i";
            if (preg_match($pattern, $title, $matchs) || preg_match($pattern, $content, $matcon)){
                $this->ajaxReturn(2);
            }

            $data = array(
                'uid' => $uid,
                'des' => $content,
                'title' => $title,
                'posttime' => time(),
            );
            $res = $Feedback->data($data)->add();
            if ($res){
                $this->ajaxReturn(1);
            }else {
                $this->ajaxReturn(0);
            }
        }else {
            $qtype = C('QUES_TYPE');

            $this->assign('qa',$qtype);
            $this->display();
        }
    }

    public function wishQA(){
        $Qa = M('Qa');

        $title = C('QUES_TYPE');

        $type = I('get.type',0,'int');

        if ($type == 0)
            $this->error('非法请求！');

        $qa = $Qa->where(array('type' => $type))->select();

        $this->assign('qa',$qa);
        $this->assign('title',$title[$type]);

        $this->display();
    }

    //关于微心愿
    public function about(){
        $Setting = M('Setting');

        $set = $Setting->select();

        $about = htmlspecialchars_decode($set[0]['about_des']);

        $this->assign('about',$about);

        $this->display();
    }

    //服务条款
    public function terms(){
        $Setting = M('Setting');

        $set = $Setting->select();

        $term = htmlspecialchars_decode($set[0]['tk_des']);

        $this->assign('term',$term);

        $this->display();
    }

    //联系我们
    public function contact(){
        $Setting = M('Setting');

        $set = $Setting->select();

        $this->assign('set',$set[0]);

        $this->display();
    }

}