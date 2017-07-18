<?php
namespace Lib\Api\Weixinapi;
class WeiChat{
    private $APPID;
    private $APPSECRET;

        //微信登录url
    const ACCESS_TOKEY_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code';
    const GETUSERINFOURL = 'https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN';
    const REFRESH_USER_ACCESS_TOKEY_URL = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=APPID&grant_type=refresh_token&refresh_token=REFRESH_TOKEN';
    const CKECK_ACCESS_TOKEY_URL = 'https://api.weixin.qq.com/sns/auth?access_token=ACCESS_TOKEN&openid=OPENID';
    //获取网页tokey
    const ACCESS_TOKEY_WEB_URL = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET';
    const JSAPI_TICKET_URL ='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi';
    //获取用户列表
    const USER_LIST_URL = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=ACCESS_TOKEN&next_openid=NEXT_OPENID';
    //获取用户信息
    const USER_INFO_URL = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN';
    //批量获取用户信息
    const BAT_GET_USER_INFO_URL = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=ACCESS_TOKEN';
    //创建二维码
    const GET_TICKET_URL='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=TOKEN';
    const GET_QRCODE_URL = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=TICKET';
    //创建自定义菜单
    const CREATE_MENU_URL = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN';
    //删除自定义菜单
    const DEL_MENU_URL = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=ACCESS_TOKEN';
    //获取线上自定义菜单
    const GET_MENU_URL = 'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=ACCESS_TOKEN';
    //长链接转短链接
    const LENG_TO_SHORT_URL = 'https://api.weixin.qq.com/cgi-bin/shorturl?access_token=ACCESS_TOKEN';
    //经纬度转地址
    const JINGWEI_TO_ADDR = 'http://apis.map.qq.com/ws/geocoder/v1?location=JING,WEI&key=KEY&get_poi=0';
    //获取设备ip，并获取经纬度
    const GET_IP_LAT_LEN = 'http://apis.map.qq.com/ws/location/v1/ip?key=KEY';




    public function __construct() {
        $aConfig = C('WEICHAT');
        $this->APPID = $aConfig['APPID'];
        $this->APPSECRET = $aConfig['APPSECRET'];
    }
    
	/*
     * 获取code
     * $REDIRECT_URI code 回调地址
     */
    public function login($REDIRECT_URI){		
		$LOGIN_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect';
		//$REDIRECT_URI = 'http://xxxx.com/index.php/Weixin/auto/authCallback.html';//回调地址		
        $loginUrl = str_replace(array('APPID','REDIRECT_URI'), array($this->APPID, $REDIRECT_URI), $LOGIN_URL);
        header("Location:".$loginUrl);
    }

    /*
     * 获取code
     * $REDIRECT_URI code 回调地址
     */
    public function login_base($REDIRECT_URI){
        $LOGIN_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=snsapi_base&state=1#wechat_redirect';
        //$REDIRECT_URI = 'http://xxxx.com/index.php/Weixin/auto/authCallback.html';//回调地址
        $loginUrl = str_replace(array('APPID','REDIRECT_URI'), array($this->APPID, $REDIRECT_URI), $LOGIN_URL);
        header("Location:".$loginUrl);
    }

    public function getOauthAccessTokeyByCode($code){ 
        $getUserAccessTokeyUrl= str_replace(array('APPID','SECRET','CODE'), array($this->APPID,$this->APPSECRET,$code), self::ACCESS_TOKEY_URL);
        return json_decode($this->https_request($getUserAccessTokeyUrl),true);
    }
    
    /**
     * 刷新tokey
     */
    public function getRefreshAccessTokey(){
        $getUserAccessTokeyUrl= str_replace(array('APPID','SECRET','CODE'), array($this->APPID,$this->APPSECRET,$code), self::REFRESH_USER_ACCESS_TOKEY_URL);
    }
    
    public function getUserInfoByAccessTokey($accessTokey,$openId){
        $getUserInfoUrl = str_replace(array('ACCESS_TOKEN','OPENID'), array($accessTokey,$openId), self::GETUSERINFOURL);
        return json_decode($this->https_request($getUserInfoUrl),true);
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
        $aJsonResult = json_decode($this->https_request($getWebAccessTokenUrl), true);
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
    
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    private function https_request($url, $data = null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}

