<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

/**
 *    充值管理
 * @package Admin\Controller
 */
class RechargeController extends AdminController{

    public $begin;
    public $end;
    public function _initialize(){
        parent::_initialize();
        $timegap = I('timegap');
        $gap = I('gap',7);
        if($timegap){
            $gap = explode(' - ', $timegap);
            $begin = $gap[0];
            $end = $gap[1];
        }else{
            $lastweek = date('Y-m-d',strtotime("-1 month"));//30天前
            $begin = I('begin',$lastweek);
            $end =  I('end',date('Y-m-d'));
        }
        $this->begin = strtotime($begin);
        $this->end = strtotime($end);

        $this->assign('timegap',date('Y-m-d',$this->begin).' - '.date('Y-m-d',$this->end));
        $this->begin = strtotime($begin);
        $this->end = strtotime($end);
    }


    public function lists(){
        $Recharge = M('OrderCz');
        $User = M('User');
        if (IS_GET) {
            $get = I('get.');
            if (!empty($get['is_pay'])) {
                    $where['is_pay'] = $get['is_pay'];
            }
            if (!empty($get['pay_type'])) {
                    $where['pay_type'] = $get['pay_type'];
            }
            if (!empty($get['paytime'])) {
                if ($get['paytime'] == 1) {
                    $order = 'paytime desc';
                } elseif ($get['paytime'] == 2) {
                    $order = 'paytime asc';
                }
            }
            if (!empty($get['total_price'])) {
                if ($get['total_price'] == 1) {
                    $order = 'total_price desc';
                } elseif ($get['total_price'] == 2) {
                    $order = 'total_price asc';
                }
            }
            if (!empty($get['posttime'])) {
                $gap = explode('-', $get['posttime']);
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['posttime'] = array('between', "$begin,$end");
                $where['is_pay'] = 1;
            }

            if (!empty($get['keyword'])) {
                $keywords = $get['keyword'];
                $uwhere['nickname'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                $userwhere = $User->where($uwhere)->select();
                foreach ($userwhere as $key => $val){
                    $where['order_user'] = $val['id'];
                }
            }
        } else {
            $where = array();
        }
        $listnum = 10;  //每页显示的数据量
        $count = $Recharge->where($where)->count();
        $Page = new \Think\Page($count, $listnum);
        $show = $Page->show();
        $udata = $Recharge->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($udata as $k => $v){
            $u = $User->find($v['order_user']);
            $udata[$k]['nickname'] = $u['nickname'];
            $udata[$k]['toupic'] = $u['toupic'];
        }

        $this->assign('list', $udata);
        $this->assign('page', $show);

        $this->display();
    }

    public function statistics()
    {
        $ordercz =  M('OrderCz');
        $now = strtotime(date('Y-m-d'));
        $today['today_amount'] = $ordercz->where("paytime>$now AND is_pay=1")->sum('pay_price');//今日充值总额
        $today['today_order'] = $ordercz->where("paytime>$now AND is_pay=1")->count();//今日充值成功订单数
        $today['cancel_order'] = $ordercz->where("paytime>$now AND is_pay=2")->count();//今日取消订单
        $today['unpaid_order'] = $ordercz->where("paytime>$now AND is_pay=0")->count();//今日未支付
        $today['sign'] = round($today['today_amount']/$today['today_order'],2); //平均每单金额
        $today['all_price_order'] = $ordercz->where("is_pay=1")->sum('pay_price');//充值总额
        $today['all_ssuccess_order'] = $ordercz->where("is_pay=1")->count();//成功订单总数
        $today['all_cancel_order'] = $ordercz->where("is_pay=2")->count();//取消订单总数
        $wechat_ssuccess = $ordercz->where("is_pay=1 and pay_type=1")->count();//微信成功订单总数
        $wechat_cancel = $ordercz->where("is_pay=2 and pay_type=1")->count();//微信取消订单总数
        $alipay_ssuccess = $ordercz->where("is_pay=1 and pay_type=2")->count();//支付宝成功订单总数
        $alipay_cancel = $ordercz->where("is_pay=2 and pay_type=2")->count();//支付宝取消订单总数
        $gear = I('post.gear','',int);
        if(!empty($gear)){
            $where['pay_price'] = $gear;
            $today['gear_all_order'] = $ordercz->where($where)->count();
            $today['gear_fail_order'] = $ordercz->where(array('is_pay in(0,2)',$where))->count();
            $today['gear_ssuccess_order'] = $ordercz->where(array('is_pay=1',$where))->count();
            $today['gear'] = $gear;
        }

        $this->assign('today',$today);
        $this->assign('wechat_ssuccess',$wechat_ssuccess);
        $this->assign('wechat_cancel',$wechat_cancel);
        $this->assign('alipay_ssuccess',$alipay_ssuccess);
        $this->assign('alipay_cancel',$alipay_cancel);
        $sql = "SELECT COUNT(*) as tnum,sum(pay_price) as amount, FROM_UNIXTIME(paytime,'%Y-%m-%d') as gap from  __PREFIX__order_cz";
        $sql .= " where paytime>$this->begin and paytime<$this->end AND is_pay=1  group by gap";
        $res = M()->query($sql);
//var_dump($res);exit;
        foreach ($res as $val){
            $arr[$val['gap']] = $val['tnum'];
            $brr[$val['gap']] = $val['amount'];
        }

        for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
            $tmp_num = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
            $tmp_amount = empty($brr[date('Y-m-d',$i)]) ? 0 : $brr[date('Y-m-d',$i)];
            $tmp_sign = empty($tmp_num) ? 0 : round($tmp_amount/$tmp_num,2);
            $order_arr[] = $tmp_num;
            $amount_arr[] = $tmp_amount;
            $sign_arr[] = $tmp_sign;
            $date = date('Y-m-d',$i);
            $list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'end'=>date('Y-m-d',$i+24*60*60));
            $day[] = $date;
        }

        $this->assign('list',$list);
        $result = array('order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'time'=>$day);
        $this->assign('result',json_encode($result));
        $this->display();
    }
    
 
}

