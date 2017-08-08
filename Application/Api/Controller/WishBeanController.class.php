<?php
namespace Api\Controller;
use Common\Controller\BaseController;
//use App\Controller\WebauthController;
use Lib\Api\Weixinapi\WeiChat;
use Lib\Api\Weixinapi\WeiXin;
use Lib\Api\AlipayApi\AlipaySubmit;
use Lib\Api\AlipayApi\AlipayNotify;

/**
 * 动态类控制器,我的心愿豆
 * @author
 *
 */

class WishBeanController extends BaseController
{
    public function _initialize($name)
    {
        header("Access-Control-Allow-Origin: *");
        $WeiChat = new WeiXin();
        $JsConfig = $WeiChat->getSignPackage();
        $this->assign('JsConfig', $JsConfig);
    }

    public function getWishBean(){
        $User = M('User');
        $uid=$_POST['uid'];
        $info=$User->field('totalmon')->find($uid);
        $results['status'] = 0;
        $results['wishBean'] = $info['totalmon'];
        dexit($results);exit;

    }
    //我的心愿豆页面
    public function index(){
        $User = M('User');
        $XinyuanList = M('WishcardJoin');
        $Bill = M('Bill');
        $uid = I('post.uid');
       
        //$uid = 4;
        $udata = $User->find($uid);

//        $rele['type'] = array(array('eq',0),array('eq',4),array('eq',5),array('eq',6),'OR');
//        $increase = $Bill->where(array('is_finish' => 1, 'uid' => $uid,$rele))->sum("mon"); //增加的

        //by kar1  曲线图数据 start
        $nowtime = time();
        $todaystartime = strtotime(date('Y-m-d'));
        $startime = $todaystartime - (7 * 24 * 3600);

        $where['type'] =array(array('eq',1),array('eq',3),'OR');
        $where['dotime'] =array(array('gt',$startime),array('elt',$nowtime),'AND');
        $reduce   = $Bill->where(array('is_finish' => 1, 'uid' => $uid,$where))->sum("mon"); //减少的

        $newdatas = array();
        for ($d = 0; $d < 8; $d++) {
            $datekey = date('m-d', $startime + $d * 24 * 3600);
            $newdatas[$datekey] = 0;
        }
        $mdatas = $Bill->where("dotime>$startime and dotime<=$nowtime AND type!=1 && type!=3 ")->field('count(*) as num,sum(mon) as percount,FROM_UNIXTIME(dotime,"%m-%d") as perdate')->group('perdate')->select();
        foreach ($mdatas as $mvv) {
            $newdatas[$mvv['perdate']] = $mvv['percount'];
        }
        $list['date']=implode(",", array_keys($newdatas));
        $list['mon']=implode(",", $newdatas);
        dexit($list);exit;
        $this->assign('listdata', json_encode($list['data']));
        $this->assign('listmon', json_encode($list['mon']));

        $Task = M('Task');
        $Mytask = M('Myevdtask');

        //$uid = 4;
//        $tasklist = $Mytask->where('status=1 AND is_finish =1 AND uid=' . $udata['id'] . ' AND awardtime >' . $startime . ' AND awardtime <=' . $nowtime)->field('sum(mon) as percount,FROM_UNIXTIME(dotime,"%m-%d") as perdate')->select();
//        foreach($tasklist as $k =>$v){
//            $whereid = $v['tkid'];
//            $alltask = $Task->where(array('id' => $whereid))->find();
//            $tasklist[$k]['title'] = $alltask['title'];
//            $tasklist[$k]['xinmon'] = $alltask['xinmon'];
//        }
//        $bill = $Bill->where(array('uid' => $uid,'type' => 0))->order('posttime desc')->select();
//        foreach ($bill as $kk => $vv){
//            $xid = $vv['xid'];
//            $xy = $Xy->where(array('id' =>$xid))->find();
//            $bill[$kk]['title'] =$xy['title'];
//        }
//        bug($tasklist);die;
//        $this->assign('tasklist',$tasklist);
        //by kar1  曲线图数据 end
        $mon = (string) $udata['totalmon'];
        $tmon = floor($mon / 1050) * 100;
        $tmon = round($tmon, 2);
        $this->assign('mon', $mon);
        $this->assign('tmon', $tmon);
        $this->assign('conmon',$reduce?$reduce:0);
        $this->display();
    }

    /**
     * 银行列表
     */
    public function listBank(){
        $Bankname = M('Bankname');
        $data = $Bankname->select();
        foreach ($data as $k=>$v){
            $data[$k]['bankName'] = $v['bankname'];
            unset($data[$k]['bankname']);
        }

        $array = range('a','z');
        $aa =array();
        foreach ($array as $k=>$v){
            $bb = $Bankname->where(array('bank'=>$v))->select();
            $aa[$v] = $bb;
        }

        $results['status'] = 0;
        $results['data'] = $data;
        dexit($results);exit;
    }

    /**
     * 我的银行卡
     */
    public function queryBankCards(){
        $User = M('User');
        $Withdrawals = M('Withdrawals');
        $Bankcard = M('Bankcard');
        $Bankname = M('Bankname');

        $uid = I('post.uid');
        $userinfo = $User->where(array('id'=>$uid))->find();
        if(!$userinfo){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '没有该用户';
            dexit($results);exit;
        }
        $userbank = $Bankcard->where(array('uid'=>$uid))->field('id,account_name,card_num,use,bankid')->select();
        foreach ($userbank as $k=>$v){
            $bank = $Bankname->where(array('id' =>$v['bankid']))->find();
            $userbank[$k]['bankName'] = $bank['bankname'];
            $userbank[$k]['accountName'] = $v['account_name'];
            $userbank[$k]['cardNum'] = $v['card_num'];
            $userbank[$k]['icon'] = $bank['img'];
            unset($userbank[$k]['account_name'],$userbank[$k]['card_num'],$userbank[$k]['bankid']);
        }
        $data['is_bankCard'] = !$userbank?false:true;
        $data['bankcards'] = $userbank;
        $results['status'] = 0;
        $results['data'] = $data;
        dexit($results);exit;
    }
    
     /**
     * 提交信息
     */
    public function withdraw()
    {
        $user = M('User');
        $UserReal = M('UserReal');
        $bankcard = M('Bankcard');
        $bankname = M('Bankname');
        $withdrawals = M('Withdrawals');
        $PayLog = M('PayLog');
        $Bill = M('Bill');

        if (IS_POST) {
            $pwd = I('post.payPwd',0,'int');
            $pwd = $this->md5pw($pwd);
            $mon = I('post.mon',0,'int');
            $uid = I('post.uid',0,'int');
            $bankid = I('post.bankCardId',0,'int');

            if (!$pwd || !$mon || !$uid || !$bankid) {
                $results['status'] = 1;
                $results['errcode'] = 1;
                $results['msg'] = '参数不全';
                dexit($results);
                exit;
            }
            $use = $user->where(array('id' => $uid))->find();
            if (!$use) {
                $results['status'] = 1;
                $results['errcode'] = 2;
                $results['msg'] = '用户不存在';
                dexit($results);
                exit;
            }
            if($use['is_auth'] != 1){
                $results['status'] = 1;
                $results['errcode'] = 3;
                $results['msg'] = '你还没通过实名认证';
                dexit($results);
                exit;
            }
            $bean = $mon * 10.5;
            if ($use['totalmon'] < $bean || $use['totalmon'] < 210 || $mon  > 5000 || $bean < 210) {
                $results['status'] = 1;
                $results['errcode'] = 4;
                $results['msg'] = '赎回金额不合法';
                dexit($results);
                exit;
            }
            $day = time() - 60 * 60 *24;
            $now_time = time();
            $time_difference['app_time'] = array('between', "$day,$now_time");
            $with = $withdrawals->where(array('uid' => $uid,$time_difference))->sum('mon');
            $day_mon = $with + $mon;
            if($day_mon  > 5000){
                $results['status'] = 1;
                $results['errcode'] = 5;
                $results['msg'] = '一天内提现金额不能超过5000元，你今天已申请了'.$with.'元';
                dexit($results);
                exit;
            }
            $card = $bankcard->where(array('uid'=>$uid,'id' => $bankid))->find();
            if (!$card) {
                $results['status'] = 1;
                $results['errcode'] = 6;
                $results['msg'] = '没有该银行卡信息';
                dexit($results);
                exit;
            }
            if ($uid != $card['uid']) {
                $results['status'] = 1;
                $results['errcode'] = 7;
                $results['msg'] = '非法参数';
                dexit($results);
                exit;
            }
            if(!$use['pay_pwd']){
                $results['status'] = 1;
                $results['errcode'] = 8;
                $results['msg'] = '还没设置支付密码';
                dexit($results);
                exit;
            }
            $now_time = time();
            $limited_time = time() - 60 * 60 * 3;
            $where['dotime'] = array('between',"$limited_time,$now_time");
            $where = array('status'=> 1,'uid'=>$uid,$where);
            $log = $PayLog->where($where)->order('dotime desc')->select();  //查询密码错误次数
            $wnum = count($log);
            if ($pwd != $use['pay_pwd']) {
                if ($wnum == 1) {
                    $this->userPayLog($uid, 1, '输入支付密码错误', 2);
                    $results['status'] = 1;
                    $results['errcode'] = 10;
                    $results['num'] = $wnum + 1;
                    $results['msg'] = '密码输错2次，是否修改支付密码';
                    dexit($results);
                    exit;
                }elseif ($wnum > 1 && $wnum < 3){
                    $this->userPayLog($uid, 1, '输入支付密码错误', 2);
                    $results['status'] = 1;
                    $results['errcode'] = 11;
                    $results['num'] = $wnum + 1;
                    $results['msg'] = '密码输错3次，支付密码被锁3小时';
                    dexit($results);
                    exit;
                }elseif ($wnum > 2){
                    $results['status'] = 1;
                    $results['errcode'] = 12;
                    $results['msg'] = '支付密码未解锁';
                    dexit($results);
                    exit;
                }else {
                    $this->userPayLog($uid, 1, '输入支付密码错误', 2);
                    $results['status'] = 1;
                    $results['errcode'] = 9;
                    $results['num'] = $wnum + 1;
                    $results['msg'] = '密码错误';
                    dexit($results);
                    exit;
                }
            } else {         //密码正确执行
                if ($wnum >= 3) {
                    $time = time() - 60 * 60 * 3;
                    if ($time < $log[0]['dotime']) {
                        $results['status'] = 1;
                        $results['errcode'] = 13;
                        $results['msg'] = '支付密码未解锁';
                        dexit($results);
                        exit;
                    }
                }
                //开启事务
                $m=M();
                $m->startTrans();

                $totalmon = $use['totalmon'];
                $totalmon = $totalmon - $mon * 10.5;
                $save = $user->where(array('id' => $uid))->data(array('totalmon' => $totalmon))->save();
                $del = $PayLog->where(array('uid' => $uid))->delete();
                $real = $UserReal->where(array('uid' => $uid))->find();
                $bank_name = $bankname->where(array('id'=>$card['bankid']))->find();
                $data = array(
                    'uid' => $card['uid'],
                    'nickname' => $use['nickname'],
                    'name' => $card['account_name'],
                    'idcard' => $real['idcard'],
                    'mon' => $mon,
                    'bank' => $bank_name['bankname'],
                    'card' => $card['card_num'],
                    'app_time' => time(),
                );
                $addwithdrawals = $withdrawals->data($data)->add();
                if (!empty($addwithdrawals)) {
                    $content = '心愿豆赎回申请提交成功啦！你所拥有的心愿豆将很快帮你实现心愿！';
                    $this->sendMsg($uid, '1','', '', '3', '', '', $content);
                    $bill = array(
                        'uid' => $uid,
                        'type' => 1,
                        'mon' => $mon * 10.5,
                        'posttime' => time(),
                        'is_finish' => 1,
                    );
                    $bb = $Bill->data($bill)->add();  //加入账单表
                }

                if(!$save || !$addwithdrawals || !$bb){
                    $m->rollback();
                    $results['status'] = 1;
                    $results['errcode'] = 14;
                    $results['msg'] = '提现申请失败';
                    dexit($results);
                    exit;
                }else{
                    $m->commit();
                    $results['status'] = 0;
                    $results['msg'] = '提现申请成功';
                    dexit($results);
                    exit;
                }

            }
        }

    }


    /**
     * 选择银行卡
     */
    public function choiceBankCard()
    {
        $user = M('User');
        $bankcard = M('Bankcard');
        $bankname = M('Bankname');

        $uid = I('post.uid', '', 'int');
        $id = I('post.id', '', 'int');
        $is_user = $user->where(array('id' => $uid))->find();
        if(!$is_user){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '用户不存在';
            dexit($results);
            exit;
        }
        $nocard = $bankcard->where(array('uid' => $uid))->data(array('use' => 0))->save();
        $usecard = $bankcard->where(array('uid' => $uid,'id' => $id))->data(array('use' => 1))->save();
        if(!$usecard){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '选取失败';
            dexit($results);
            exit;
        }else{
            $results['status'] = 0;
            $results['msg'] = '选取成功';
            dexit($results);
            exit;
        }
    }

    //添加银行卡
    public function addBankCard()
    {
        $user = M('User');
        $bankcard = M('Bankcard');
        $bankname = M('Bankname');

        $uid = I('post.uid');
        $account_name = I('post.accountName');
        $bankid = I('post.bankId','','int');
        $cardnum = I('post.cardNum',0,'int');

        if (!$uid || !$account_name || !$bankid || !$cardnum) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不全';
            dexit($results);
            exit;
        }
        $is_user = $user->where(array('id'=>$uid))->find();
        if(!$is_user){
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '没有该用户';
            dexit($results);
            exit;
        }
        $nocard = $bankcard->where(array('uid' => $uid))->data(array('use' => 0))->save();
        $data = array(
            'uid' => $uid,
            'account_name' => $account_name,
            'bankid' => $bankid,
            'card_num' => $cardnum,
            'use' => 1,
        );

        $addcard = $bankcard->data($data)->add();
        if (!$addcard) {
            $results['status'] = 1;
            $results['errcode'] = 3;
            $results['msg'] = '添加失败';
            dexit($results);
            exit;
        }else{
            $results['status'] = 0;
            $results['msg'] = '添加成功';
            dexit($results);
            exit;
        }

    }

    //删除银行卡
    public function delBankCard()
    {
        $user = M('User');
        $bankcard = M('Bankcard');
        $uid = I('post.uid');
        $id = I('post.id');

        if(!$uid || !$id){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不全';
            dexit($results);
            exit;
        }
        $is_user = $user->where(array('id'=>$uid))->find();
        if(!$is_user){
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '没有该用户';
            dexit($results);
            exit;
        }
        $del = $bankcard->where(array('id' => $id,'uid'=>$uid))->delete();
        if (!$del) {
            $results['status'] = 0;
            $results['msg'] = '删除失败';
            dexit($results);
            exit;
        } else {
            $results['status'] = 0;
            $results['msg'] = '删除成功';
            dexit($results);
            exit;
        }
    }

    //账单
    public function listBill() {
        $User = M('User');
        $Bill = M('Bill');
        $Task = M('Task');
        $Wishcard = M('Wishcard');
        $Wishwall = M('Wishwall');
        $Wishhelp = M('Wishhelp');
        $Mytask = M('Myevdtask');
        $Withdrawals = M('Withdrawals');

        $uid = I('get.uid');
        $user = $User->find($uid);
        if(!$user){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '没有该用户';
            dexit($results);
            exit;
        }
        //账单表
        $bill = $Bill->where(array('uid' => $uid,'is_finish'=>1))->field('id,uid,type,wishType,tkid,mon,xid,posttime')->order('posttime desc')->select();
        foreach ($bill as $k=>$v){
            switch($v['type']){
                case "0";
                    $bill[$k]['operator'] = 1;
                break;
                case "1";
                    $bill[$k]['operator'] = 0;
                    break;
                case "9":
                    $bill[$k]['operator'] = 1;
                    break;
                case "10":
                    $bill[$k]['operator'] = 1;
                    break;
                case "8";
                    if($v['wishType'] == 3){
                        $help = $Wishhelp->where(array('id' =>$v['xid']))->find();
                        $bill[$k]['title'] = $help['content'];
                        $bill[$k]['operator'] = 0;
                    }
                    break;
                case "3";
                    if($v['wishType'] == 1){
                        $card = $Wishcard->where(array('id' =>$v['xid']))->find();
                        $bill[$k]['title'] = $card['title'];
                        $bill[$k]['operator'] = 0;
                    }
                    if($v['wishType'] == 2){
                        $wall = $Wishwall->where(array('id' =>$v['xid']))->find();
                        $bill[$k]['title'] = $wall['content'];
                        $bill[$k]['operator'] = 0;
                    }
                    if($v['wishType'] == 3){
                        $help = $Wishhelp->where(array('id' =>$v['xid']))->find();
                        $bill[$k]['title'] = $help['content'];
                        $bill[$k]['operator'] = 0;
                    }
                    break;
                case "4";
                    $xy = $Wishcard->where(array('id' =>$v['xid']))->find();
                    $bill[$k]['title'] = $xy['title'];
                    $bill[$k]['operator'] = 1;
                    break;
                case "5";
                    $bill[$k]['operator'] = 1;
                    break;
                case "6";
                    $bill[$k]['operator'] = 1;
                    break;
                case "7";
                    $task = $Task->where(array('id' => $v['tkid']))->find();
                    $bill[$k]['title'] = $task['title'];
                    $bill[$k]['operator'] = 1;
                    break;

            }
        }
        $results['status'] = 0;
        $results['data'] = $bill;
        dexit($results);
        exit;

    }

    public function recharge(){
        $Order = M('Order');
        $OrderCz = M('OrderCz');
        $User = M('User');
        $UserOpen = M('User_open');

        $uid = I('post.uid');
        $rechargeCount = I('post.rechargeCount');

        vendor('pingpp.init');

        $udata = $User->find($uid);
        $uinfo=$UserOpen->where(array('uid'=>$uid,'login_type'=>1))->find();

//        if (empty($input_data['channel']) || empty($input_data['amount'])) {
//            echo 'channel or amount is empty';
//            exit();
//        }
        $channel = 'wx_pub';
//			$input_data['amount']=0.01;
        $amount = $rechargeCount * 100;     //ping++上  价格单位是分
        // $amount = 1;    //测试价格定义为1分钱

//        echo json_encode($_POST);die;

        /**
         * $extra 在使用某些渠道的时候，需要填入相应的参数，其它渠道则是 array()。
         * 以下 channel 仅为部分示例，未列出的 channel 请查看文档 https://pingxx.com/document/api#api-c-new；
         * 或直接查看开发者中心：https://www.pingxx.com/docs/server/charge；包含了所有渠道的 extra 参数的示例；
         */
        $extra = array();
        switch ($channel) {
            case 'wx_pub':
                $extra = array(
                    'open_id' => $uinfo['openid'],  // 用户在商户微信公众号下的唯一标识
                );
                $paytype = 1;

                break;

        }

        $data = array(
            'out_trade_no' => $this->create_orderid(),
            'posttime' => time(),
            'user' => $uid,
            'price' => $amount / 100,
            'order_type' => 1,  // 1充值，2提现
        );
        $res = $Order->data($data)->add();
        if ($res){
            $insertId = $res;
            $czdata = array(
                'oid' => $insertId,
                'total_price' => $amount / 100,
                'posttime' => time(),
                'order_user' => $uid,
                'pay_type' => $paytype,
                'out_trade_no' => $data['out_trade_no'],
            );
            $OrderCz->data($czdata)->add();
        }

        $odata = $Order->find($insertId);
        $order_no = $odata['out_trade_no'];
        //session('mypayordersn',$order_no);

//        $ch = array(
//                //请求参数字段
//                'subject' => '充值心愿豆',
//                'body'    => '充值心愿豆',
//                'amount'  => $amount,   //
//                'order_no' => $order_no,  //
//                'currency' => 'cny',   //三位 ISO 货币代码，目前仅支持人民币 cny。
//                'extra'   => $extra,
//                'channel' => $channel,  //支付使用的第三方支付渠道取值
//                'client_ip' => $_SERVER['REMOTE_ADDR'],  //发起支付请求客户端的 IP 地址，格式为 IPV4，如: 127.0.0.1
//                'app'     => array('id' => C('PINGPP.APPID')),
//                'metadata' => array('key' => 'value')
//                );
//        echo $ch;  //输出 Ping++ 返回的支付凭据 Charge



        \Pingpp\Pingpp::setApiKey(C('PINGPP.SKLIVEKEY'));

        \Pingpp\Pingpp::setPrivateKeyPath(C('PINGPP.PRIKEY'));
//             	$channel = 'wx_pub';

        try {
            $ch = \Pingpp\Charge::create(
                array(
                    //请求参数字段
                    'subject' => '充值心愿豆',
                    'body'    => '充值心愿豆',
                    'amount'  => $amount,   //
                    'order_no' => $order_no,  //
                    'currency' => 'cny',   //三位 ISO 货币代码，目前仅支持人民币 cny。
                    'extra'   => $extra,
                    'channel' => $channel,  //支付使用的第三方支付渠道取值
                    'client_ip' => $_SERVER['REMOTE_ADDR'],  //发起支付请求客户端的 IP 地址，格式为 IPV4，如: 127.0.0.1
                    'app'     => array('id' => C('PINGPP.APPID')),
                    'metadata' => array('key' => 'value')
                )
            );
            echo $ch;  //输出 Ping++ 返回的支付凭据 Charge
        }catch (\Pingpp\Error\Base $e){
            //捕获错误信息
            if ($e->getHttpStatus() != null){
                header('Status: ' . $e->getHttpStatus());
                file_put_contents('./err.txt', $e->getHttpBody());
                echo $e->getHttpBody();
            }else {
                file_put_contents('./msg.txt', $e->getMessage());
                echo $e->getMessage();
            }
        }

    }
}