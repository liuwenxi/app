<?php
namespace Lib\Api\AlipayApi;
use Lib\Api\AlipayApi\AlipayFun;
/* *
 * 类名：AlipaySubmit
 * 功能：支付宝各接口请求提交类
 * 详细：构造支付宝各接口表单HTML文本，获取远程HTTP数据
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
 */
// require_once("alipay_core.function.php");
// require_once("alipay_rsa.function.php");

class AlipaySubmit extends AlipayFun{

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
		file_put_contents('./rsa.txt', json_encode($this->alipay_config));
		//$this->alipay_config = $alipay_config;
	}
    public function AlipaySubmit() {
    	
    	
    	$this->__construct();
    }
	
	/**
	 * 生成签名结果
	 * @param $para_sort 已排序要签名的数组
	 * return 签名结果字符串
	 */
	private function buildRequestMysign($para_sort) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);
		
		$mysign = "";
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "RSA" :
				$mysign = $this->rsaSign($prestr, $this->alipay_config['private_key']);
				break;
			default :
				$mysign = "";
		}
		
		return $mysign;
	}

	/**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
	private function buildRequestPara($para_temp) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//生成签名结果
		$mysign = $this->buildRequestMysign($para_sort);
		
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
		
		return $para_sort;
	}

	/**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组字符串
     */
	private function buildRequestParaToString($para_temp) {
		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);
		
		//把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
		$request_data = $this->createLinkstringUrlencode($para);
		
		return $request_data;
	}
	
    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
	public function buildRequestForm($para_temp, $method, $button_name) {
		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);
		
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset=".trim(strtolower($this->alipay_config['input_charset']))."' method='".$method."'>";
		while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit'  value='".$button_name."' style='display:none;'></form>";
		
		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
		
		return $sHtml;
	}
	
	
	/**
     * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
	 * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
     * return 时间戳字符串
	 */
	private function query_timestamp() {
		$url = $this->alipay_gateway_new."service=query_timestamp&partner=".trim(strtolower($this->alipay_config['partner']))."&_input_charset=".trim(strtolower($this->alipay_config['input_charset']));
		$encrypt_key = "";		

		$doc = new DOMDocument();
		$doc->load($url);
		$itemEncrypt_key = $doc->getElementsByTagName( "encrypt_key" );
		$encrypt_key = $itemEncrypt_key->item(0)->nodeValue;
		
		return $encrypt_key;
	}
}
?>