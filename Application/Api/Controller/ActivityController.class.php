<?php
namespace App\Controller;
use Common\Controller\BaseController;
use Lib\Api\Weixinapi\WeiXin;

/**
 * 动态类控制器
 * @author ludewei
 *
 */
class ActivityController extends BaseController {

    public function _initialize()
    {

        if (!is_user_login()) {

            redirect(U('/App/Public/login'));
        }
        $WeiChat=new WeiXin();
        $JsConfig = $WeiChat->getSignPackage();
        $this->assign('JsConfig',$JsConfig);
    }

    //列表信息
    public function exchange(){
        $Activity=M('Activity');
        $User=M('User');
        $uid=session('uid');

        //查找当前自己的兑换信息
        $where['uid']=$uid;
        $user=$User->find($uid);

        $where['rank']=1;
        $userAct=$Activity->where($where)->find();
        $sgn=0;
        if($userAct){
            $sgn=1;
        }

        //查询所有兑换记录
        $where2['rank']=1;
        $where2['uid']=array('gt',0);
        $actList=$Activity->where($where2)->order('ch_time desc')->select();
        if($actList){
            //查找中奖人信息
            foreach($actList as $key => $val){
                $actList[$key]['nickname']=$this->get_nickname($val['uid']);
                $actList[$key]['toupic']=$this->get_toupic($val['uid']);
                if($val['money']==492){
                    $actList[$key]['level']="豪华奖";
                }elseif($val['money']==117 || $val['money']==100 || $val['money']==194){
                    $actList[$key]['level']="特等奖";
                }else{
                    $actList[$key]['level']="惊喜奖";
                }
            }
        }
        $count=ceil(count($actList)/3);
        $datas=array_chunk($actList,$count,true);
        foreach ($datas as $key => $value) {
            $this->assign('actList'.$key,$value);
        }

        $this->assign('sgn',$sgn);
        // $this->assign('actList',$actList);
        $this->assign('user',$user);
        $this->display();

    }

    //我的奖券
    public function myCoupon(){

        $Activity=M('Activity');
        $User=M('User');
        $uid=session('uid');

        //查找当前自己的兑换信息
        $where['uid']=$uid;
        $user=$User->find($uid);

        $where['rank']=1;
        $where['status']=1;
        $userAct=$Activity->where($where)->find();
        if($userAct['money']==5){
            $userAct['type']=1;
        }else{
            $userAct['type']=2;
        }
        if($userAct['money']==492){
            $userAct['level']="豪华奖";
        }elseif($userAct['money']==117 || $userAct['money']==100 || $userAct['money']==194){
            $userAct['level']="特等奖";
        }else{
            $userAct['level']="惊喜奖";
        }
        $userAct['prize_img'] = substr($userAct['prize_img'],1);
        $this->assign('userAct',$userAct);
        $this->assign('user',$user);
        $this->display();
    }

    //查询礼品详情
    public function ajax_getInfo(){
        $Activity=M('Activity');
        $post = I('post.','0','htmlspecialchars');
        if(empty($post['id'])){
            echo 1;exit();
        }
        //查看信息
        $info=$Activity->where(array('id'=>$post['id']))->find();
        //没有信息
        if(empty($info)){
            echo 2;exit();
        }
        //查找中奖人信息
        $info['nickname']=$this->get_nickname($info['uid']);
        $info['toupic']=$this->get_toupic($info['uid']);
        if($info['money']==492){
            $info['level']="豪华奖";
        }elseif($info['money']==117 || $info['money']==100 || $info['money']==194){
            $info['level']="特等奖";
        }else{
            $info['level']="惊喜奖";
        }
        $info['time']=date("Y-m-d",$info['ch_time']);
        echo json_encode($info);
    }

    //兑换
    public function ajax_uploads(){

        $Activity=M('Activity');
        $User=M('User');

        $uid=session('uid');

        $post = I('post.','0','htmlspecialchars');
        if(empty($post['cdkey']) || empty($post['image'])){
            echo json_encode(array("status"=>1));
            exit();
        }
        //查找当前自己的兑换信息
        $where['uid']=$uid;
        $where['rank']=1;
        $where['status']=1;
        $userAct=$Activity->where($where)->find();
        if(!empty($userAct)){
            echo json_encode(array("status"=>3));
            exit();
        }
        //查看信息
        $info=$Activity->where(array('rank'=>1,'cdkey'=>$post['cdkey']))->find();
        //没有信息
        if(empty($info)){
            echo json_encode(array("status"=>2));
            exit();
        }
        //已兑换
        if($info['status']==1){
            echo json_encode(array("status"=>3));
            exit();
        }
        $ex_time=$info['ex_time'];
        $time=time();
        //已过期
        if($time >= $ex_time){
            echo json_encode(array("status"=>4));
            exit();
        }
        //保存路径
        $savepath = "./Uploads/activity/" . Date("Y") . '/' . Date("m") . '/' . Date("d") . '/';
        $uploadtit=$post['image'];
        //截取信息
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $uploadtit, $result)) {

            //随机名字
            $filName = uniqid();
            //判断路径是否存在
            if (!is_dir($savepath)) {
                //创建文件夹
                mkdir($savepath, 0777, true);
            }
            $savePath = "/Uploads/activity/" . Date("Y") . '/' . Date("m") . '/' . Date("d") .'/';
            //将图片源写入保存
            if (file_put_contents($savepath . $filName . '.jpeg', base64_decode(str_replace($result[1], '', $uploadtit)))) {

                //保存
                oss_upload($savepath . $filName . '.jpeg');
                $imgPath = get_url($savePath. $filName . '.jpeg');//这个是图片数据库保存的路径
                $data['user_img']=$imgPath;
            }
        }
        $data['uid']=$uid;
        $data['status']=1;
        $data['ch_time']=$time;
        $res=$Activity->where(array('id'=>$info['id']))->save($data);
        if($info['totalmon']>0){
            $User->where(array('id' => $uid))->setInc('totalmon',$info['totalmon']);
            $this->sendMsg($uid,'','','','1',"感谢你参与心愿锦囊活动，微心愿赠送你".$info['totalmon']."个心愿豆作为额外奖励，拿去不谢！");
        }
        if($res){
            echo json_encode(array("status"=>0,"prize"=>$info['money']));
            exit();
        }
        echo json_encode(array("status"=>5));
        exit();
    }
}