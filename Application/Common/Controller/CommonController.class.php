<?php
namespace Common\Controller;
use Common\Controller\BaseController;

/**
 * 公共入口类
 * @author ludewei
 */
class CommonController extends BaseController{
    /* 空操作，用于输出404页面 */
	public function _empty(){
		$this->redirect('index/index');
	}
    
    
//    protected function _initialize(){
        //是否关闭站点
//         $WEB_SITE_CLOSE = M('webconfig')->where(array('codetag'=>'WEB_SITE_CLOSE'))->find();
//         if($WEB_SITE_CLOSE == 1){
//            die('站点已经关闭，请稍后访问');
//         }
        
        //记录访问
//        $Agent = $_SERVER['HTTP_USER_AGENT'];
//        $data['uid'] = session('uid')?0:0;
//        $data['browseaddres'] = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
//        $data['fromaddres'] = $_SERVER['HTTP_REFERER'];
//        $data['visitors_ver'] = $this->determineplatform ($Agent).'-'.$this->determinebrowser($Agent);
//        $data['ip'] = get_client_ip();
//        $data['visitors_time'] = time();
//        M('fangke_log')->add($data);
//
//    }
}
