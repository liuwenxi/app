<?php
header("Content-type: text/html; charset=utf-8");
include "TopSdk.php";
 
//将下载到的SDK里面的TopClient.php的$gatewayUrl的值改为沙箱地址:http://gw.api.tbsandbox.com/router/rest
//正式环境时需要将该地址设置为：http://gw.api.taobao.com/router/rest
$arr=array();
$code=rand(1000,9999);
$js='{"code":"'.$code.'"}';

$c = new TopClient;
$c->appkey = "23324179";
$c->secretKey = "ae041264e41c90e7bf8a79263e33122c"; 
$req = new AlibabaAliqinFcSmsNumSendRequest;
$req->setExtend("123456");
$req->setSmsType("normal");
$req->setSmsFreeSignName("注册验证534534");
$req->setSmsParam($js);
$req->setRecNum("15017524533");
$req->setSmsTemplateCode("SMS_5570206");
$resp = $c->execute($req);

print_r(XML2Array($resp));



function XML2Array($xml) {
  function normalizeSimpleXML($obj, &$result) {
    $data = $obj;
    if (is_object($data)) {
      $data = get_object_vars($data);
    }
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        $res = null;
        normalizeSimpleXML($value, $res);
        if (($key == '@attributes') && ($key)) {
          $result = $res;
        } else {
          $result[$key] = $res;
        }
      }
    } else {
      $result = $data;
    }
  }
  normalizeSimpleXML($xml, $result);
  return $result;
 }
?>