<?php

namespace Api\Controller;

use Common\Controller\BaseController;

/**

 * banner控制器

 * @author kar1

 *

 */
class BannerController extends BaseController {

    public  function _initialize(){
        parent::_initialize();
        header("Access-Control-Allow-Origin: *");
    }
    public  function  recordbanner()
    {
        $id = $_POST['id'];
        if ($id) {
            $PV = M('bannerpv');
            $time = time();
            //获得当日凌晨的时间戳
            $today = strtotime(date("Y-m-d"), $time);
            //当天的24点时间戳
            $end = $today + 60 * 60 * 24;
            //ip地址
            $ip = get_client_ip();
            $map['create_at'] = array('between', array($today, $end));
            $map['ip'] = $ip;
            $map['bid']=array('eq',$id);
            $info = $PV->where($map)->find();
            if (!$info) {
                $data['create_at'] = $time;
                $data['ip'] = $ip;
                $data['bid'] = $id;
                $PV->add($data);
            }
        }
        echo  $id;
    }

    public function  recordwish()
    {

       $id=$_GET["wishId"];
        if ($id) {
            $PV = M('lightpv');
            $time = time();
            //获得当日凌晨的时间戳
            $today = strtotime(date("Y-m-d"), $time);
            //当天的24点时间戳
            $end = $today + 60 * 60 * 24;
            //ip地址
            $ip = get_client_ip();
            $map['create_at'] = array('between', array($today, $end));
            $map['ip'] = $ip;
            $map['wishid']=array('eq',$id);
            $info = $PV->where($map)->find();
            if (!$info) {
                $data['create_at'] = $time;
                $data['ip'] = $ip;
                $data['wishid'] = $id;
                $PV->add($data);
            }
        }
        echo  $id;
    }
}