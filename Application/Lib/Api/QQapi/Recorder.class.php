<?php
/* PHP SDK
 * @version 2.0.0
 * @author connect@qq.com
 * @copyright © 2013, Tencent Corporation. All rights reserved.
 */
namespace Lib\Api\QQapi;
use Lib\Api\QQapi\ErrorCase;

class Recorder{
    private static $data;
    private $inc;
    private $error;

    public function __construct(){
        $this->error = new ErrorCase();

        //-------读取配置        
        $this->inc->appid = C('QQ.APPID');
        $this->inc->appkey = C('QQ.APPSECRET');
        $this->inc->callback = 'http://'.$_SERVER['HTTP_HOST'].U('Public/qqCallback');
        $this->inc->scope = C('QQ.SCOPE');
        $this->inc->errorReport = C('QQ.ERRORREPORT');
        $this->inc->storageType = C('QQ.STORAGETYPE');
         
        if(empty($this->inc)){
            $this->error->showError("20001");
        }

        if(empty($_SESSION['QC_userData'])){
            self::$data = array();
        }else{
            self::$data = $_SESSION['QC_userData'];
        }
    }

    public function write($name,$value){
        self::$data[$name] = $value;
    }

    public function read($name){
        if(empty(self::$data[$name])){
            return null;
        }else{
            return self::$data[$name];
        }
    }

    public function readInc($name){
        if(empty($this->inc->$name)){
            return null;
        }else{
            return $this->inc->$name;
        }
    }

    public function delete($name){
        unset(self::$data[$name]);
    }

    function __destruct(){
        $_SESSION['QC_userData'] = self::$data;
    }
}
