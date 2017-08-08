<?php

namespace Admin\Controller;
use Admin\Controller\AdminController;
/**
 * 意见反馈管理类
 */
class AdviseController extends AdminController{
    private $mode_table = "feedback";
    private $mode_user = "user";
    /**
     * 列表
     */
    public function lists(){
        $mode = M($this->mode_table);
        $count      = $mode->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $mode->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page',$show);
        $this->assign('data',$list);
        $this->display();
    }
    
    
    /**
     * 编辑
     */
    public function edit(){
        if(IS_POST){
            $mode = M($this->mode_table);
           !$mode->create()&&$this->error('数据创建失败');
           $mode->save()?$this->success('修改成功'):$this->error('修改失败');
        }else{
            $id = I('get.id',0,'int');
            $data = M($this->mode_table)->find($id);
            $data['user'] = M($this->mode_user)->where(array('id'=>$data['uid']))->getField('nickname');
            $status = array('未读','已读');
            $data['status'] = $status[$data['is_reply']];
            //更改为已读
            M($this->mode_table)->where(array('id'=>$id))->save(array('is_reply'=>1));
            $this->assign('data',$data);
            $this->display();
        }
    }
    
    /**
     * 删除
     */
    public function del(){
        $id = I('post.id',0,'int');
        if($id>0){
            $mode = M($this->mode_table);
            $result = $mode->delete($id);
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
