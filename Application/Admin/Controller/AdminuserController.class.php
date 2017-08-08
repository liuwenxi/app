<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class AdminuserController extends AdminController{
    private $mode_table = "admin";
    
    public function lists(){
        $mode = M($this->mode_table);
        $count      = $mode->where($where)->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $mode->where($where)->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('page',$show);
        $this->assign('data',$list);
        $this->display();
    }
	
	public function add(){
         if(IS_POST){
           $mode = M($this->mode_table);
           $username = I('post.username');
           $password = I('post.passwd');
           if(empty($username) || empty($password)){
              $this->error('信息需要填写完整'); 
           }
           !$mode->create()&&$this->error('数据创建失败');
           $mode->password = md5(I('post.passwd'));
           $mode->reg_time = time();
           $result = $mode->add();
           if($result){
               $this->success('添加成功');
           }else{
               $this->error('添加失败');
           }
        }
	}
	
	public function edit() {
        if (IS_POST) {
            $ajax = I('post.ajax', 0, 'int');

            if ($ajax === 1) {
                $id = I('post.id');
                $data = M($this->mode_table)->find($id);
                $data['userstatus'] = $data['status'];
                unset($data['status']);
                if ($data) {
                    $data['status'] = 1;
                } else {
                    $data['status'] = 0;
                }
                echo json_encode($data);
                exit();
            } else {
                //修改
                $mode = M($this->mode_table);
                $pass = I('post.passwd');
                !$mode->create() && $this->error('数据创建失败');
                if (!empty($pass)) {
                    $mode->password = $this->md5pw(I('post.passwd'));
                }
                $mode->save() ? $this->success('修改成功') : $this->error('修改失败');
            }
        }
    }

    public function del(){
        $id = I('post.id',0,'int');
        if(!empty($_SESSION['adminid']) && $id>0){
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