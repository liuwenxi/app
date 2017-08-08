<?php

namespace Admin\Controller;

use Admin\Controller\AdminController;

class ActivityController extends AdminController {

    //列表页
    public function lists() {
        $Activity = M('Activity');

        if (IS_GET) {
            $get = I('get.');
            $status = isset($get['status']) ? $get['status'] : 9;
            $cancel = isset($get['cancel']) ? $get['cancel'] : 9;
            if($cancel != 9){
                $where['cancel'] = $cancel;
            }
            if ($status != 9) {
                $where['status'] =  $status;
            }

            if(!empty($get['cdkey'])){
                $cdkey=$get['cdkey'];
                $where['_string'] = '(cdkey = "'.$cdkey.'")  OR ( code = "'.$cdkey.'") ';
                $this->assign('cdkey', $cdkey);
            }
        }
        $count = $Activity->order('id desc')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $acdata = $Activity->order('id desc')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('data', $acdata);

        $this->display();
    }

    public function add() {
        $Activity = M('Activity');

        if (IS_POST) {
            $rank = $_POST['rank'];
            $code = $_POST['code'];
            $prize = $_POST['prize'];
            $store = $_POST['store'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            $ex_time = $_POST['ex_time'];
            $remark = $_POST['remark'];
            $money = $_POST['money'];
            $url = $_POST['url'];
            $totalmon = $_POST['totalmon'];
            if (empty($rank) || empty($code) || empty($prize) || empty($store) || empty($phone) || empty($address) || empty($ex_time) || empty($money) || empty($_FILES['prize_img']['name'])) {
                $this->error('必填信息不能为空');
                exit;
            }
            $Activity->create();
            $readme = random(17);
            if(!empty($_FILES['prize_img']['name'])){
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/activity/' ; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');

                !is_dir($upload->rootPath) && @mkdir($upload->rootPath , 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError()); exit;
                } else {
                    $img_url = $upload->rootPath . $info['prize_img']['savepath'] . $info['prize_img']['savename'];
                }
            }
            $data = array(
                'rank'  => $rank,
                'code' => $code,
                'prize' => $prize,
                'store' => $store,
                'ex_time' =>strtotime($ex_time),
                'phone' => $phone,
                'address' => $address,
                'remark' => $remark,
                'money' => $money,
                'url' => $url,
                'cancel' => 0,
                'totalmon' => $totalmon,
            );
            $data['prize_img'] = $img_url;
            $add_id=$Activity->data($data)->add();
            if($add_id){
                $Activity->data(array('cdkey'=>$readme))->where(array('id'=>$add_id))->save();
                $this->success('添加成功',U('Admin/Activity/lists'));exit();
            }
            $this->error('添加失败');exit();
        }
        $this->display();

    }

    public function edit($id = '') {
        $Activity = M('Activity');

        if (IS_POST) {
            $rank = $_POST['rank'];
            $code = $_POST['code'];
            $prize = $_POST['prize'];
            $store = $_POST['store'];
            $phone = $_POST['phone'];
            $address = $_POST['address'];
            $ex_time = $_POST['ex_time'];
            $remark = $_POST['remark'];
            $money = $_POST['money'];
            $url = $_POST['url'];
            $totalmon = $_POST['totalmon'];
            if (empty($rank) || empty($code) || empty($prize) || empty($store) || empty($phone) || empty($money) || empty($address) || empty($ex_time)) {
                $this->error('必填信息不能为空');
                exit;
            }
            $Activity->create();
            if(!empty($_FILES['prize_img']['name'])){
            $upload = new \Think\Upload(); // 实例化上传类
            $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            $upload->rootPath = './Uploads/activity/' ; // 设置附件上传根目录
            $upload->subName = array('date', 'Ymd');
           
            !is_dir($upload->rootPath) && @mkdir($upload->rootPath , 0777, true); //创建文件夹
            $info = $upload->upload();
            if (!$info) {// 上传错误提示错误信息
                $this->error($upload->getError()); exit;
            } else {
                $img_url = $upload->rootPath . $info['prize_img']['savepath'] . $info['prize_img']['savename'];
            }
          }
            $data = array(
                'rank'  => $rank,
                'code' => $code,
                'prize' => $prize,
                'store' => $store,
                'ex_time' =>strtotime($ex_time),
                'phone' => $phone,
                'address' => $address,
                'remark' => $remark,
                'money' => $money,
                'url' => $url,
                'totalmon' => $totalmon,
            );
           if (!empty($id)){
              $data['id'] = $id;
              $acdata = $Activity->find($id);
              $data['cdkey'] = $acdata['cdkey'];
              if(!empty($img_url)){
                $data['prize_img'] = $img_url; 
                $prize_img = $acdata['prize_img'];
                is_file($prize_img) && @unlink($prize_img);
              }else{
                  $data['prize_img'] = $acdata['prize_img'];
              } 
            $Activity->data($data)->save() ? $this->success('修改成功', U('Admin/Activity/lists')) : $this->error('修改失败');          
           }
        }else {

            $id = I('get.id');
            $acdata = $Activity->find($id);
            $this->assign('data', $acdata);
            $this->display();
        }
    }
    
    public function del(){
        $Activity = M('Activity');
        $id = I('post.id',0,'int');
        if($id>0){
            $info=$Activity->find($id);
            $prize_img = $info['prize_img'];
            $result = $Activity->delete($id);
            if($result){
                is_file($prize_img) && @unlink($prize_img);
                //成功
                echo 1;
            }else {
                //失败
                echo 2;
            }
        }
    }
    public function cancel(){
        $Activity = M('Activity');
        $id = I('post.id',0,'int');
        if($id>0){
            $info=$Activity->find($id);
            //找不到
            if(empty($info)){echo 2;exit();};
            //已核销
            if($info['cancel']==1){echo 3;exit();};
            $data['cancel']=1;
            $data['cancel_time']=time();
            $res=$Activity->where(array("id"=>$id))->save($data);
            if($res){
                echo 1;exit();
            }
            echo 4;exit();
        }
        echo 2;exit();
    }

    public function nums(){
        $Nums= M('Nums');
        for($i=0;$i<270;$i++){
            $readme = random(17);
            $data['nums']=$readme;
            $Nums->add($data);
        }
    }
    
}
