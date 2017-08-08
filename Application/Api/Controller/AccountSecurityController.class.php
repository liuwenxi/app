<?php

namespace Api\Controller;

use Common\Controller\BaseController;

/**
 * 账户安全
 */
class AccountSecurityController extends BaseController
{

    public function _initialize()
    {
        header('Content-type:text/html;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
    }

    public function removeBinding()
    {
        $User = M('User');
        $uid = I('get.uid');
        $type = I('get.type');

        $is_user = $User->where(array('id' => $uid))->find();
        $flag=false;

        if ($is_user){
            if ($type==1) {
                $flag = $User->where(array('uid' => $uid))->data(array('wechat_openid' => '', 'wechat_nickname' => ''))->save();
            } elseif ($type==2) {
                $flag = $User->where(array('uid' => $uid))->data(array('qq_openid' => '', 'qq_nickname' => ''))->save();
            } elseif ($type==3) {
                $flag = $User->where(array('uid' => $uid))->data(array('weibo_openid' => '', 'weibo_nickname' => ''))->save();
            }
        }
        $userInfo = $User->where(array('id' => $uid))->find();
        $user=array();
        $user['uid'] =(int)$userInfo['id'];
        $user['nickName'] =$userInfo['nickname'];
        $user['avatar'] =$userInfo['avatar'];
        $user['gender'] =(int)$userInfo['gender'];
        $user['credit'] =(int)$userInfo['credit'];
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
        $user['isRegister'] = true;
        if ($flag) {
            Json(0,'user',$user,'解除成功');
        }
        Json(1,'errcode',2,'解除失败');


    }

    public function setPassword()
    {
        $User = M('User');
        $UserOpen = M('UserOpen');
        $uid = I('post.uid');

    }

    public function modifyPassword()
    {
        $User = M('User');
        $UserOpen = M('UserOpen');
        $uid = I('post.uid');

    }

    public function setPayPassword()
    {
        $User = M('User');
        $UserOpen = M('UserOpen');
        $uid = I('post.uid');
        $payPassword = I('post.payPassword');

        if (!$uid || !$payPassword) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不全';
            dexit($results);
        }
        $user = $User->where(array('id' => $uid))->find();
        if ($user['pay_pwd']) {
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '已有支付密码';
            dexit($results);
        }
        $save = array(
            'pay_pwd' => $this->md5pw($payPassword),
        );
        $res = $User->where(array('id' => $uid))->data($save)->save();
        if ($res) {
            $results['status'] = 0;
            $results['msg'] = '设置成功';
            dexit($results);
        } else {
            $results['status'] = 1;
            $results['errcode'] = 3;
            $results['msg'] = '设置失败';
            dexit($results);
        }

    }

    public function modifyPayPassword()
    {
        $User = M('User');
        $UserOpen = M('UserOpen');
        $uid = I('post.uid');
        $payPassword = I('post.payPassword');

        if (!$uid || !$payPassword) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不全';
            dexit($results);
        }
        $save = array(
            'pay_pwd' => $this->md5pw($payPassword),
        );
        $res = $User->where(array('id' => $uid))->data($save)->save();
        if ($res) {
            $results['status'] = 0;
            $results['msg'] = '修改成功';
            dexit($results);
        } else {
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '修改失败';
            dexit($results);
        }

    }

    public function test(){
        $a = M('Setting');
        $b = $a->find();
        $c = $b['tk_des'];
        $data['html']=htmlspecialchars_decode($c);
        echo json_encode($data);exit;
    }


}
