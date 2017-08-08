<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class OrderController extends AdminController {
    public function index(){
        $Order = M('Order');
        $OrderTx = M('OrderTx');
        $OrderCz = M('OrderCz');
        
        $get = I('get.');
        if ($get){        //订单类型1：充值，2：提现；如果没有get到默认显示充值订单
            $order_type = intval($get['otype']) ? $get['otype'] : 1 ;
            $chk = isset($get['chk']) ? $get['chk'] : 3 ;
            $do = isset($get['pass']) ? $get['pass'] : 3 ;
        }else {
            $order_type = 1;
        }
        
        $count = $Order->where(array('order_type' => $order_type))->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $odata = $Order->where(array('order_type' => $order_type))->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        if ($order_type == 1){   //取充值订单数据
            $list = array();
            foreach ($odata as $k=>$v){
                $czdata = $OrderCz->where(array('oid' => $v['id']))->find();
                $temp = array(
                    'id' => $v['id'],
                    'out_trade_no' => $v['out_trade_no'],
                    'posttime' => $czdata['posttime'],
                    'total_price' => $czdata['total_price'],
                    'pay_price' => $czdata['pay_price'],
                    'paytime' => $czdata['paytime'],
                    'is_pay' => $czdata['is_pay'],
                    'pay_user' => $czdata['order_user'],
                    'pay_type' => $czdata['pay_type'],
                );
                array_push($list, $temp);
            }
        }else {           
            $list = array();
            foreach ($odata as $k=>$v){
                if ($chk == 1 && $do == 0){   //已审核未通过
                    $where = array(
                        'oid' => $v['id'],
                        'is_check' => 1,
                        'is_do' => 0,
                    );
                    
                }elseif ($do == 1){   //已通过
                    $where = array(
                        'oid' => $v['id'],
                        'is_do' => 1,
                    );
                }elseif ($chk == 0){    //未审核
                    $where = array(
                        'oid' => $v['id'],
                        'is_check' => 0,
                    );
                }else {     //默认取全部
                    $where = array('oid' => $v['id']);
                }
                $txdata = $OrderTx->where($where)->find();
                if ($txdata){
                    $temp = array(
                        'id' => $v['id'],
                        'out_trade_no' => $v['out_trade_no'],
                        'posttime' => $txdata['posttime'],
                        'tx_price' => $txdata['tx_price'],
                        'is_check' => $txdata['is_check'],
                        'tx_user' => $txdata['order_user'],
                        'is_do' => $txdata['is_do'],
                    );
                    array_push($list, $temp);
                }else {
                    unset($v);
                }
                
            }
        }
        
        $this->assign('page', $show);
        $this->assign('order_type', $order_type);
        $this->assign('data', $list);
        
        $this->display();
    	
    }
    
    public function lists(){
        
    }
    
    public function edit(){
        $Order = M('Order');
        $Admin = M('Admin');
        $OrderTx = M('OrderTx');
        $OrderCz = M('OrderCz');
        
        $uid = session('uid');
        $gid = $Admin->field('gid')->where(array('id' => $uid))->find();
        
        $id = I('get.id',0,'int');
        $odata = $Order->find($id);
        
        if ($odata['order_type'] == 1){   //充值
            $list = $OrderCz->where(array('oid' => $odata['id']))->find();
        }else {     //提现
            $list = $OrderTx->where(array('oid' => $odata['id']))->find();    //tx_msg是序列化用户信息到数据库的，所以要反序列化后再显示出来
        }
        
        $this->assign('list', $list);
        $this->assign('otype', $odata['order_type']);
        $this->assign('data', $odata);
        $this->assign('gid', $gid['gid']);
        $this->display();
    }
    
    //手机端下订单接口
    public function payment(){
        //date('YmdHis',time()).rand(100,999);
        
        echo json_encode("success");
    }
    

    
}