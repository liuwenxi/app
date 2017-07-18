<?php
namespace App\Controller;
use App\Controller\WebauthController;
use Common\Controller\BaseController;
use Lib\Api\AlipayApi\AlipaySubmit;
use Lib\Api\AlipayApi\AlipayNotify;


/**
 * 首页控制器
 * @author ludewei
 *
 */
class PaymentController extends BaseController {
    
    public function _initialize(){
        parent::_initialize();
    }
    
    //买心元
    public function heartExchange(){

    	$this->display();
    }
    
    public function doPay(){
        $Order = M('Order');
        $OrderCz = M('OrderCz');
        $User = M('User');
        $UserOpen = M('User_open');

        $uid = session('uid');
        
        vendor('pingpp.init');
        
        $udata = $User->find($uid);
		$uinfo=$UserOpen->where(array('uid'=>$uid,'login_type'=>1))->find();

            // 此处为 Content-Type 是 application/json 时获取 POST 参数的示例
            $input_data = json_decode(file_get_contents('php://input'), true);

            file_put_contents('./input.txt', json_encode($input_data));
            if (empty($input_data['channel']) || empty($input_data['amount'])) {
            	echo 'channel or amount is empty';
            	exit();
            }
            $channel = strtolower($input_data['channel']);
//			$input_data['amount']=0.01;
            $amount = $input_data['amount'] * 100;     //ping++上  价格单位是分
           // $amount = 1;    //测试价格定义为1分钱



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


            //微信支付
            if ($channel == 'wx_pub' && !empty($uinfo['openid']) && strpos($_SERVER['HTTP_USER_AGENT'], "MicroMessenger") !== false){
            	
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
            	

            }else {
                $retdata = array(
                    'status' => "notweichat",
                    'msg' => "您还没有登录微信，请重新使用微信登录，才可以使用微支付！",
                );
                $this->ajaxReturn($retdata);
            }
            
            //支付宝支付
            
            
        
    }
    
    public function cancelOrder(){
    	$Order = M('Order');
    	$OrderCz = M('OrderCz');
    	
    	if (IS_POST){
    		$order_no = I('post.ord_no');
    		
    		$o = $Order->where(array('out_trade_no' => $order_no))->find();

    		$OrderCz->where(array('oid' => $o['id']))->data(array('is_pay' => 2))->save();
    	}
    }
    
    public function doAliPay(){
    	$Order = M('Order');
    	$OrderCz = M('OrderCz');
    	$User = M('User');
    	
    	$uid = session('uid');
    	$udata = $User->find($uid);
    	$count = $_GET['m'];
    	
    	/**************************请求参数**************************/
    	
    	//商户订单号，商户网站订单系统中唯一订单号，必填
    	$out_trade_no = $this->create_orderid();
    	
    	//订单名称，必填
    	$subject = "测试支付宝支付";
    	
    	//付款金额，必填
     	$total_fee = 0.01;
//   	  $total_fee = 0.01;
    	//商品描述，可空
    	$body = "测试支付宝支付";
    	
    	$paytype = 2;

    	/************************************************************/
    	
    	//构造要请求的参数数组，无需改动
    	$parameter = array(
    			"service"       => "alipay.wap.create.direct.pay.by.user",   // 产品类型，无需修改
    			"partner"       => C('payment.alipay')['partner'],
    			"seller_id"  => C('payment.alipay')['partner'],
    			"payment_type"	=> 1,   // 支付类型 ，无需修改
    			"notify_url"	=> C('payment.alipay')['notify_url'],   // 服务器异步通知页面路径  
    			"return_url"	=> C('payment.alipay')['return_url'],   // 页面跳转同步通知页面路径
    			// "return_url"	=>  $return_url,   // 页面跳转同步通知页面路径
    			"_input_charset"	=> trim(strtolower('utf-8')),  //字符编码格式 目前支持utf-8
    			"out_trade_no"	=> $out_trade_no,
    			"subject"	=> $subject,
    			"total_fee"	=> $total_fee,
    			"show_url"	=> U('Payment/returnurl'),
    			//"app_pay"	=> "Y",//启用此参数能唤起钱包APP支付宝
    			"body"	=> $body,
    			//其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.2Z6TSk&treeId=60&articleId=103693&docType=1
    			//如"参数名"	=> "参数值"   注：上一个参数末尾需要“,”逗号。
    	
    	);
    	
    	$data = array(
    			'out_trade_no' => $out_trade_no,
    			'posttime' => time(),
    			'user' => $uid,
    			'price' => $total_fee,
    			'order_type' => 1,  // 1充值，2提现
    	);
    	
    	$res = $Order->data($data)->add();
    	if ($res){
    		$insertId = $res;
    		$czdata = array(
    				'oid' => $insertId,
    				'total_price' => $total_fee,
    				'posttime' => time(),
    				'order_user' => $uid,
    				'pay_type' => $paytype,
    				'out_trade_no' => $out_trade_no,
    		);
    		$OrderCz->data($czdata)->add();
    	}
//    	file_put_contents('./ali.txt', json_encode($parameter));
    	
    	//建立请求
    	$alipaySubmit = new AlipaySubmit();
    	$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
		echo $html_text;die;
    	$this->assign('html_text',$html_text);
    	$this->display();
    	
    	

    	
    	
    	
    }
    //同步跳转
    public function returnurl(){
	    //计算得出通知验证结果
		$alipayNotify = new AlipayNotify();
		$verify_result = $alipayNotify->verifyReturn();
		if($verify_result) {//验证成功
			echo 111;die;
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代码
			
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
		    //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
		
			//商户订单号
			$out_trade_no = $_GET['out_trade_no'];
		
			//支付宝交易号
		
			$trade_no = $_GET['trade_no'];
		
			//交易状态
			$trade_status = $_GET['trade_status'];
		

		    if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
				//判断该笔订单是否在商户网站中已经做过处理
					//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					//如果有做过处理，不执行商户的业务程序
				$oinfo=M('Order')->where(array(array('out_trade_no' => $out_trade_no)))->find();
				if($oinfo['user'] != session('uid')){
					$this->error('错误1');
				}
				 $order = M('OrderCz');
				 $User = M('User');
				 $row = $order->where(array('out_trade_no' => $out_trade_no))->find();

			 if($row){
				 $User->where(array('id'=>$row['order_user']))->setInc('totalmon',10000);
				 if($order->where(array('oid'=>$row['oid']))->save(array('is_pay' => 1,'paytime'=>time(),'pay_price'=>$oinfo['price']))){
					 $this->assign('row',$row);
					 $this->display();
				 }else{
					$this->error('错误2');
				 }
			 }else{
				 $this->error('错误3');
			 }
		    }
		    else {
		      echo "trade_status=".$_GET['trade_status'];
		    }

			echo "验证成功<br />";
		
			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
			
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}else {
		    //验证失败
		    //如要调试，请看alipay_notify.php页面的verifyReturn函数
		    echo "验证失败";
		}
    }
	public function notify(){
		log(json_encode($_POST).'******************'.json_encode($_GET));
	}
    
    
}