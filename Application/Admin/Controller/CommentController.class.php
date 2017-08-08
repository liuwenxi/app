<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class CommentController extends AdminController{
    private $mode_table = "comment";
    private $mode_detail = "CommDetail";
    private $mode_user = "User";
    private $mode_xy = "Wishcard";
    private $mode_jb = "Warn";
    
    public function lists(){
        $mode = M($this->mode_table);
        $order='pub_time desc,id desc';
        $count      = $mode->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $mode->where($where)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();

        $status = array('禁止','正常');
        foreach ($list as $k=>$v){
            $udata = M($this->mode_user)->field('nickname')->find($v['pub_user']);
            $list[$k]['xyname'] = $this->get_xy_title($v['xid']);
            $list[$k]['detail'] = M($this->mode_detail)->where(array('cid' => $v['id']))->select();
            $list[$k]['uname'] = $udata['nickname'];
            //$list[$k]['show'] = $status[$v['show']];
        }

        $this->assign('page',$show);
        $this->assign('data',$list);
        $this->display();
    }
	
    public function read(){
        $id = I('get.id');
        $mode = M($this->mode_table);
        $data = $mode->find($id);
        $data['commont_img'] = unserialize($data['commont_img']);
        $this->assign('data',$data);
        $this->display();
    }
    
    public function chkJB(){
        $id = I('get.id');
        $mode = M($this->mode_jb);
        $data = $mode->where(array('cid' => $id))->select();
        
        $this->assign('data',$data);
        $this->display();
    }


    public function edit(){
        if(IS_POST){
            //修改
            $mode = M($this->mode_table);
            $id = I('post.id');
            $ajax = I('post.ajax');
            if ($ajax == 1){
                $data = $mode->find($id);
                $data['xyname'] = $this->get_xy_title($data['xid']);
                $data['pub_user'] = $this->get_nickname($data['pub_user']);
                $data['status'] = 1;
                $this->ajaxReturn($data);
            }else {
                !$mode->create()&&$this->error('数据创建失败');
                $mode->save()?$this->success('修改成功'):$this->error('修改失败');
            }
            
        }
        
    }
    
    public function editJB(){
        if(IS_POST){
            //修改
            $mode = M($this->mode_jb);
            $id = I('post.id');
            $ajax = I('post.ajax');
            if ($ajax == 1){
                $data = $mode->find($id);
                $data['warn_user'] = $this->get_nickname($data['warn_user']);
                $data['status'] = 1;
                $this->ajaxReturn($data);
            }else {
                !$mode->create()&&$this->error('数据创建失败');
                $mode->save()?$this->success('修改成功'):$this->error('修改失败');
            }
    
        }
    
    }
	
	public function del(){
        $id = I('post.id',0,'int');
        if($id>0){
            $mode = M($this->mode_table);
            $result = $mode->delete($id);
            if($result){
                //成功
                M($this->mode_detail)->where(array('cid' => $id))->delete();
                echo 1;
            }else {
                //失败
                echo 2;
            }
        }
        
    }

	
    
    
}