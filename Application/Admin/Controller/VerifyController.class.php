<?php
namespace Admin\Controller;
use Think\Controller;

/**
* 
*/
class VerifyController extends Controller{
	
	public function auto(){
		$config =    array(
            'fontSize'    =>    18,    // 验证码字体大小
            'length'      =>    3,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
            'imageW'      =>    140,    //验证码宽度
            'imageH'      =>    35,    //验证码宽度
            'bg'          =>    array(243, 251, 254),    //验证码背景颜色 rgb数组设置，例如 array(243, 251, 254)
        );
		//生成验证码
		$Verify = new \Think\Verify($config);
		$Verify->entry();
	}
}


?>