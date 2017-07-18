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

    public function queryBindingInfo()
    {
        $User = M('User');
        $UserOpen = M('UserOpen');
        $uid = I('post.uid');

        $is_user = $User->find($uid);
        if (!$is_user) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '没有该用户';
            dexit($results);
        }
        $field = 'id,nickname,avatar,login_type,reg_time';
        $userOpen = $UserOpen->where(array('uid' => $uid))->select();
        if(!empty($userOpen)){
            $weChat = null;
            $QQ = null;
            $weiBo = null;
            foreach ($userOpen as $key => $val){
                if($val['login_type']==1){
                    $weChat=$val;
                }elseif($val['login_type']==2){
                    $QQ=$val;
                }elseif($val['login_type']==3){
                    $weiBo=$val;
                }
            }
        }
        $data['weChat']=$weChat;
        $data['QQ']=$QQ ;
        $data['weiBo']=$weiBo;
        $data['loginPassword'] = !$is_user['password'] ? false : true;
        $data['payPassword'] = !$is_user['pay_pwd'] ? false : true;

        $results['status'] = 0;
        $results['data'] = $data;
        dexit($results);

    }

    public function removeBinding()
    {
        $User = M('User');
        $UserOpen = M('UserOpen');
        $uid = I('post.uid');
        $type = I('post.type');

        $is_userOpen = $UserOpen->where(array('uid' => $uid, 'login_type' => $type))->find();
        if (!$is_userOpen) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '非法操作';
            dexit($results);
        } else {
            $use = $UserOpen->where(array('uid' => $uid, 'login_type' => $type))->data(array('is_use' => 2))->save();
            if ($use) {
                $results['status'] = 0;
                $results['msg'] = '解除成功';
                dexit($results);
            }
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '解除失败';
            dexit($results);
        }

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
        echo json_encode($c);exit;
    }


}
