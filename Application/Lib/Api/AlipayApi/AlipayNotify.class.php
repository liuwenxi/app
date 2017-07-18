<?php
namespace Lib\Api\AlipayApi;
use Lib\Api\AlipayApi\AlipayFun;
/* *
 * 类名：AlipayNotify
 * 功能：支付宝通知处理类
 * 详细：处理支付宝各接口通知返回
 * 版本：3.2
 * 日期：2011-03-25
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考

 *************************注意*************************
 * 调试通知返回时，可查看或改写log日志的写入TXT里的数据，来检查通知返回是否正常
 */

// require_once("alipay_core.function.php");
// require_once("alipay_rsa.function.php");

class AlipayNotify extends AlipayFun{
    /**
     * HTTPS形式消息验证地址
     */
	protected $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
	/**
     * HTTP形式消息验证地址
     */
	protected $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
	protected $alipay_config = array();
	
	
	//合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
	protected $partner	= '';
	
	//收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
	protected $seller_id = '';
	//$alipay_config['seller_id']	= $alipay_config['partner'];
	
	//商户的私钥,此处填写原始私钥去头去尾，RSA公私钥生成：https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.nBDxfy&treeId=58&articleId=103242&docType=1
	protected $private_key = '';
// 	$alipay_config['private_key']	= '';
	
	
	//支付宝的公钥，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
	protected $alipay_public_key = '';
// 	$alipay_config['alipay_public_key']= 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB';
	
	// 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
	protected $notify_url = '';
// 	$alipay_config['notify_url'] = "http://商户网关网址/alipay.wap.create.direct.pay.by.user-PHPUTF-8/notify_url.php";
	
	// 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
	protected $return_url = '';
// 	$alipay_config['return_url'] = "http://商户网址/alipay.wap.create.direct.pay.by.user-PHP-UTF-8/return_url.php";
	
	//签名方式
	protected $sign_type = '';
// 	$alipay_config['sign_type']    = strtoupper('RSA');
	
	//字符编码格式 目前支持utf-8
	protected $input_charset = '';
// 	$alipay_config['input_charset']= strtolower('utf-8');
	
	//ca证书路径地址，用于curl中ssl校验
	//请保证cacert.pem文件在当前文件夹目录中
	protected $cacert = '';
// 	$alipay_config['cacert']    = getcwd().'\\cacert.pem';
	
	//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
	protected $transport = 'http';
// 	$alipay_config['transport']    = 'http';
	
	// 支付类型 ，无需修改
	protected $payment_type = "1";
// 	$alipay_config['payment_type'] = "1";
	
	// 产品类型，无需修改
	protected $service = "alipay.wap.create.direct.pay.by.user";
// 	$alipay_config['service'] = "alipay.wap.create.direct.pay.by.user";
	
	
	
	/**
	 *支付宝网关地址（新）
	 */
	protected $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';

	public function __construct(){
		$this->alipay_config = array(
				'partner' => C('payment.alipay')['partner'],
				'seller_id' => C('payment.alipay')['partner'],
				'private_key' => C('payment.alipay')['private_key'],
				'alipay_public_key' => C('payment.alipay')['alipay_public_key'],
				'notify_url' => C('payment.alipay')['notify_url'],
				'return_url' => C('payment.alipay')['return_url'],
				'sign_type' => strtoupper('RSA'),
				'input_charset' => strtolower('utf-8'),
				'cacert' => getcwd().'\\cacert.pem',
				'transport' => $this->transport,
				'payment_type' => $this->payment_type,
				'service' => $this->service,
		);
	}
    public function AlipayNotify() {
    	$this->__construct();
    }
    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	private function verifyNotify(){
		file_put_contents('./alireq.txt', json_encode($_POST));
		if(empty($_POST)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'false';
			if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}
			
			//写日志记录
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_POST);
			//logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}
	
    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	public function verifyReturn(){
		if(empty($_GET)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_GET, $_GET["sign"]);

			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'false';
			if (! empty($_GET["notify_id"])) {$responseTxt = $this->getResponse($_GET["notify_id"]);}
			
			//写日志记录
//			if ($isSign) {
//				$isSignStr = 'true';
//			}
//			else {
//				$isSignStr = 'false';
//			}
//			$log_text = "responseTxt=".$responseTxt."\n return_url_log:isSign=".$isSignStr.",";
//			$log_text = $log_text.$this->createLinkString(htmlspecialchars($_GET));
//			$this->logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}
	
    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
	private function getSignVeryfy($para_temp, $sign) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);
		$prestr = htmlspecialchars($prestr);
		$isSgin = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "RSA" :
				$isSgin = $this->rsaVerify($prestr, trim($this->alipay_config['alipay_public_key']), $sign);
				break;
			default :
				$isSgin = false;
		}
		
		return $isSgin;
	}

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
	private function getResponse($notify_id) {
		$transport = strtolower(trim($this->alipay_config['transport']));
		$partner = trim($this->alipay_config['partner']);
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = $this->https_verify_url;
		}
		else {
			$veryfy_url = $this->http_verify_url;
		}
		$veryfy_url = htmlspecialchars($veryfy_url."partner=" . $partner . "&notify_id=" . urlencode($notify_id));
		$responseTxt = $this->getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);

		$this->logResult($veryfy_url.'***************'.$responseTxt);
		return $responseTxt;
	}
}
?>
