<?php
namespace Lib\Api\Weixinapi;

class WeixinPay{
    //微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
	private $APPID;
	//受理商ID，身份标识
	private $MCHID;
	//商户支付密钥Key。审核通过后，在微信发送的邮件中查看
	private $KEY;
	//JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
	private $APPSECRET;
    
    private $Prepay_id;
    
    private $BODY = '商品名称';
    
    private $Order_no = '';

    private $Price = 1;//
    
    private $Client_ip = '';
    
    private $NOTIFY_URL = '';
    
    private $Trade_type = 'JSAPI';
    
    private $Openid = '';
    
    private $CODE = '';
    
    private $JS_API_CALL_URL;//获取openid 过程中需要获取code ，此链接为获取code 回调地址
    
    private $CURL_TIMEOUT = 30;//CURL 超时 时长
    
    private $Notifydata ; //腾讯异步返回支付状态信息


    private $jssdktimestamp = '';//jssdk 配置中使用到时间戳，签名时的时间戳要跟输出的jssdk 配置中的时间戳一致
    private $jsnoncestr = '';//jssdk 配置中使用到随机字符串，签名时的随机字符串要跟输出的jssdk 配置中的随机字符串一致

    //获取网页tokey
    const ACCESS_TOKEY_WEB_URL = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET';
    const JSAPI_TICKET_URL ='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi';
    

    public function __construct($config) {
        $this->APPID = $config['APPID'];
        $this->MCHID = $config['MCHID'];
        $this->KEY = $config['KEY'];
        $this->APPSECRET = $config['APPSECRET'];
    }
    
    /**
	 * 	作用：通过curl向微信提交code，以获取openid
	 */
	public function getOpenid($callUrl){
        $callUrl = $callUrl?$callUrl:$this->JS_API_CALL_URL;
        if(!isset($_GET['code'])){
            $url = $this->createOauthUrlForCode($callUrl);
		    Header("Location: $url");
        } else {
            $code = $_GET['code'];
		    $this->setCode($code);
            $url = $this->createOauthUrlForOpenid();
            $data = $this->httpFun($url);
            $this->Openid = $data['openid'];
            return $this->Openid;
        }
        
    }
    
    
    public function unifiedOrder(){
               
        //设置接口链接
		$url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        //必须的参数
		$param['appid'] = $this->APPID;
        $param['mch_id'] = $this->MCHID;
        $param['nonce_str'] = $this->createNoncestr();
        $param['body'] = $this->BODY;
        $param['out_trade_no'] = $this->Order_no;
        $param['total_fee'] = $this->Price;
        $param['spbill_create_ip'] = $this->Client_ip;
        $param['notify_url'] = $this->NOTIFY_URL;
        $param['trade_type'] = $this->Trade_type;
        $param['openid'] = $this->Openid;
        
        $param['sign'] = $this->getSign($param);
//         print_r($param);
        $xml = $this->arrayToXml($param);

        $this->response = $this->postXmlCurl($xml,$url,$second=30);
//         print_r($this->response);
        $result = $this->xmlToArray($this->response);
        $this->Prepay_id = $result["prepay_id"];
        return $this->$result;
    }
    
    //获取H5唤起支付的jsapi 配置
    /** [WeixinJSBridge] Generate js config for payment.
     *
     * <pre>
     * WeixinJSBridge.invoke(
     *  'getBrandWCPayRequest',
     *  ...
     * );
     * </pre>
    */
    public function getParameters($json = true)
	{
		$jsApiObj["appId"] = "$this->APPID";
	    $jsApiObj["timeStamp"] = strval(time());
	    $jsApiObj["nonceStr"] = $this->createNoncestr();
		$jsApiObj["package"] = "prepay_id=$this->Prepay_id";//使用统一支付接口得到的预支付id
	    $jsApiObj["signType"] = 'MD5';
	    $jsApiObj["paySign"] = $this->getSign($jsApiObj);

		return $json?json_encode($jsApiObj):$jsApiObj;
	}
    
    /**
     * [JSSDK] Generate js config for payment.
     *
     * <pre>
     * wx.chooseWXPay({...});
     * </pre>
     *
     * @param string $prepayId
     *
     * @return array|string
     */
    public function configForJSSDKPayment()
    {
        $config = $this->getParameters(false);

        $config['timestamp'] = $config['timeStamp'];
        unset($config['timeStamp']);

        return $config;
    }
    
    
    /**获取 jssdk 配置
     * $url 调用jssdk 的具体页面地址
     */
    public function getJSSDKconfig($url){
        
        $jsapiTicket = S('weichat_jsapi_ticket');
        if (!$jsapiTicket) {
           $jsapiTicket = $this->getJsapi_ticket();
        }
        $jsparam['appId'] = $this->APPID;
        $jsparam['timestamp'] = time();
        $jsparam['noncestr'] = $this->createNoncestr();
        $jsparam['signature'] = $this->getSignature($jsapiTicket, $jsparam['noncestr'], $jsparam['timestamp'], $url);
        
        return $jsparam;
        
    }
    

    /**
     * Sign the params.
     *
     * @param string $ticket
     * @param string $nonce
     * @param int    $timestamp
     * @param string $url
     *
     * @return string
     */
    public function getSignature($ticket, $nonce, $timestamp, $url)
    {
        return sha1("jsapi_ticket=$ticket&noncestr=$nonce&timestamp=$timestamp&url=$url");
    }
    
    /**
     * Get current url.
     *
     * @return string
     */
    public static function current()
    {
        $protocol = (!empty($_SERVER['HTTPS'])
                        && $_SERVER['HTTPS'] !== 'off'
                        || $_SERVER['SERVER_PORT'] === 443) ? 'https://' : 'http://';

        return $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
    
    
    //获取 jssdk 微支付 配置
    public function getJssdkPayConfig(){
        
        
        
        
    }

    /**
     * 设置获取CODE的回调跳转地址
     * 该地址，正常都是在个地址调用 getopenid()这个方法，回调的地址路径就写当前这个方法地址路径
     */
    public function setJS_API_CALL_URL($url){
        $this->JS_API_CALL_URL = $url;
    }


    /**
	 * 	作用：设置code
	 */
	private function setCode($code_)
	{
		$this->CODE = $code_;
	}
    
    /**
     * 设置支付商品名称
     * @param type $title 支付商品标题
     */
    public function setGoodsTitle($title){
        $this->BODY = $title;
    }

    /**
     * 
     * @param int $price 支付金额，单位为分
     */
    public function setPayPrice($price){
        $this->Price = $price;
    }

    /**
     * 商家订单单号
     * @param type $orderno 商家订单单号，由商家自己生成
     */
    public function setOrderNo($orderno){
        $this->Order_no = $orderno;
    }

    /**
     * 设置客户ip
     * @param type $Clientip 客户端ip,
     */
    public function setClientIp($Clientip){
        $this->Client_ip = $Clientip;
    }

    /**
     * 异步回调地址
     * @param type $notifyUrl 异步回调地址
     */
    public function setNotifyUrl($notifyUrl){
        $this->NOTIFY_URL = $notifyUrl;
    }
    
    /**
     * 接口类型
     * @param type $TradeType 接口类型JSAPI，NATIVE，APP，默认是 JSAPI,
     */
    public function setTrade_type($TradeType){
        $this->Trade_type = $TradeType;
    }

    /**
     * 
     * @param type $Openid 唤起支付用户的微信openid
     */
    public function setOpenid($Openid){
        $this->Openid = $Openid;
    }

    

    /**
	 * 	作用：格式化参数，签名过程需要使用
	 */
	private function formatBizQueryParaMap($paraMap)
	{
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v)
		{
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar = "";
		if (strlen($buff) > 0) 
		{
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
	
	/**
	 * 	作用：生成签名
	 */
	private function getSign($Obj)
	{
		foreach ($Obj as $k => $v)
		{
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters);
		//echo '【string1】'.$String.'</br>';
		//签名步骤二：在string后加入KEY
		$String = $String."&key=".$this->KEY;
		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5($String);
		//echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtoupper($String);
		//echo "【result】 ".$result_."</br>";
		return $result_;
	}
    
    /**
	 * 	作用：array转xml
	 */
	private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
        	 if (is_numeric($val))
        	 {
        	 	$xml.="<".$key.">".$val."</".$key.">"; 

        	 }else{
        	 	$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
             }
        }
        $xml.="</xml>";
        file_put_contents("./testaaa.txt", $xml);
//         print_r($xml);
        return $xml; 
    }
	
	/**
	 * 	作用：将xml转为array
	 */
	private function xmlToArray($xml)
	{		
        //将XML转为array        
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $array_data;
	}

	/**
	 * 	作用：以post方式提交xml到对应的接口url
	 */
	private function postXmlCurl($xml,$url,$second=30)
	{		
        //初始化curl        
       	$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
        $data = curl_exec($ch);
		curl_close($ch);
		//返回结果
		if($data)
		{
		    print_r(9956);
			curl_close($ch);
			return $data;
		}
		else 
		{ 
		    print_r(8698);
			$error = curl_errno($ch);
			echo "curl出错，错误码:$error"."<br>"; 
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close($ch);
			return false;
		}
	}
    
    
    /**
	 * 	作用：产生随机字符串，不长于32位
	 */
	public function createNoncestr( $length = 32 ) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		}  
		return $str;
	}
    
    
     /**
     * 生成订单号
     * 可根据自身的业务需求更改
     */
    public function createOrderNo() {
        $year_code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        return $year_code[intval(date('Y')) - 2010] .
                strtoupper(dechex(date('m'))) . date('d') .
                substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('d', rand(0, 99));
    }
    
    
     //curl get方式
    private function httpFun($url){
        $ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $this->CURL_TIMEOUT);
		curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);
		curl_close($ch);
        return $data = json_decode($res,true);
    }
    
      /**
	 * 	作用：生成可以获得code的url
     * $redirectUrl 微信客户端弹出授权，客户选择是否授权后，需要带上code或者没有code 跳到的 地址
	 */
	function createOauthUrlForCode($redirectUrl)
	{
		$urlObj["appid"] = $this->APPID;
		$urlObj["redirect_uri"] = "$redirectUrl";
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_userinfo";
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap($urlObj);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}
    
    /**
	 * 	作用：生成可以获得openid的url
	 */
	function createOauthUrlForOpenid()
	{
		$urlObj["appid"] = $this->APPID;
		$urlObj["secret"] = $this->APPSECRET;
		$urlObj["code"] = $this->CODE;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->formatBizQueryParaMap($urlObj);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}
    
    
    /**
     * 获取网页基础access_token
     * @return type
     */
    public function getAccessToken() {
        $accessToken = S('weichat_access_token');
        if ($accessToken) {
           return $accessToken;
        }
        $getWebAccessTokenUrl = str_replace(array('APPID', 'APPSECRET'), array($this->APPID, $this->APPSECRET), self::ACCESS_TOKEY_WEB_URL);
        $aJsonResult = json_decode($this->httpFun($getWebAccessTokenUrl), true);
        if(isset($aJsonResult['errcode'])){
            \Think\Log::write('调用access_token次数达到上限');
            return false;
        }
        S('weichat_access_token', $aJsonResult['access_token'], $aJsonResult['expires_in']);
        return $aJsonResult['access_token'];
    }
    
    public function getJsapi_ticket() {
        $jsapiTicket = S('weichat_jsapi_ticket');
        if ($jsapiTicket) {
            return $jsapiTicket;
        }
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }
        $getJsapiTicketUrl = str_replace(array('ACCESS_TOKEN'), array($accessToken), self::JSAPI_TICKET_URL);
        $aJsonResult = json_decode($this->https_request($getJsapiTicketUrl), true);
        if (isset($aJsonResult['errmsg']) && $aJsonResult['errmsg'] == 'ok') {
            S('weichat_jsapi_ticket', $aJsonResult['ticket'], $aJsonResult['expires_in']);
            return $aJsonResult['ticket'];
        }
        \Think\Log::write('调用jsapi_ticket次数达到上限');
        return false;
    }
    
    
    /**----------------------- 异步返回支付状态信息相关 ----------------------------------*/
    /**
	 * 将微信的请求xml转换成关联数组，以方便数据处理
	 */
	function saveData($xml)
	{
		$this->Notifydata = $this->xmlToArray($xml);
	}
	
	function checkSign()
	{
		$tmpData = $this->Notifydata;
		unset($tmpData['sign']);
		$sign = $this->getSign($tmpData);//本地签名
		if ($this->Notifydata['sign'] == $sign) {
			return TRUE;
		}
		return FALSE;
	}
    
    /**
	 * 设置返回微信的xml数据
	 */
	function setReturnParameter($parameter, $parameterValue)
	{
		$this->returnParameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	}
	
	/**
	 * 生成接口参数xml
	 */
	function createXml()
	{
		return $this->arrayToXml($this->returnParameters);
	}
	
	/**
	 * 将xml数据返回微信
	 */
	function returnXml()
	{
		$returnXml = $this->createXml();
		return $returnXml;
	}
    
    function trimString($value)
	{
		$ret = null;
		if (null != $value) 
		{
			$ret = $value;
			if (strlen($ret) == 0) 
			{
				$ret = null;
			}
		}
		return $ret;
	}
}