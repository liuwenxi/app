<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;
use Lib\until\Category;

/**
 * Class WishwallBackground
 * @package Admin\Controller
 * @author karl
 */
class WishwallBackgroundController extends CommentController
{

    /**
     * 心愿墙心愿列表
     */
    public function backgroundList()
    {
        $Model = M('wishwall_background');
        $data=$Model->select();

        $this->assign('data', $data);
        $this->display();
    }

    /*
     * classAdd
     */
    public function add()
    {
        $qaClass=M('wishwall_background');
        if (IS_POST){
            $post=$_POST;
            $post['large_img']='http://php.17wxy.com'.$_POST['large_img'];
            $post['small_img']='http://php.17wxy.com'.$_POST['small_img'];
            $qaClass->add($post)?$this->success('添加成功'):$this->error('添加失败');
        }
    }

    /**
     * 编辑
     */
    public function edit(){
        $Model = M('wishwall_background');

        if(IS_POST){
            $ajax = I('post.ajax',0,'int');
            if ($ajax == 1){
                $id = I('post.id',0,'int');
                $redata = $Model->find($id);
                if ($redata){
                    $redata['status'] = 1;
                }else {
                    $redata['status'] = 0;
                }

                echo json_encode($redata);
                exit();
            }else {
                $data=$_POST;
                $data['large_img']='http://php.17wxy.com'.$_POST['large_img'];
                $data['small_img']='http://php.17wxy.com'.$_POST['small_img'];
                $Model->where(array('id'=>$data['id']))->save($data)?$this->success('修改成功'):$this->error('修改失败');
            }

        }
    }

    /**
     * 删除
     */
    public function del(){
        $id = I('post.id',0,'int');
        if($id>0){
            $qaClass=M('wishwall_background');
            $result = $qaClass->delete($id);
            if($result){
                //成功
                echo 1;
            }else {
                //失败
                echo 2;
            }
        }
    }

}
