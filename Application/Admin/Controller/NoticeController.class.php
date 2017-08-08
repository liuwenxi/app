<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class NoticeController extends AdminController{
    private $mode_table = 'notice';
    private $mode_user = 'user';
    private $mode_msg = 'message';
/**
     * 列表
     */
    public function lists(){
        $model = M($this->mode_table);

        if (IS_POST){
            $type = I('post.seltype',0,'int') ? I('post.seltype') : 0 ;           
            
            $mdata = $model_type->where(array('tpid' => $type))->select();
            if ($mdata){
                $redata['status'] = 1;
            }else {
                $redata['status'] = 0;
            }
            
            $redata['list'] = $mdata;
            
            echo json_encode($redata);
            exit();
        }else {
            $mdata = $model->order('id desc')->select();
            
            $this->assign('notice',$mdata);
            
            $this->display();
        }
        
        
        
//         $count      = $mode->where($where)->count();// 查询满足要求的总记录数
//         $Page       = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
//         $show       = $Page->show();// 分页显示输出
//         $list = $mode->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
//         $this->assign('page',$show);
//         $this->assign('data',$list);
//         $this->display();
    }
    
    /**
     * 编辑
     */
    public function add(){
        $model = M($this->mode_table);

        if (IS_POST){
            $adminid = session('adminid');
            $des = I('post.des','0','string');
            
            $tsetdata = array(
                'des' => $des, 
                'adminid' => $adminid,
                'posttime' => time(),
            );
//             if ($model_type->create()){
                $res = $model->data($tsetdata)->add();
                if ($res){
                    $insertId = $res;
                    $this->success('添加成功！');
                }else {
                    $this->error('添加失败！');
                }
//             }else {
//                 $this->error('模型创建失败！');
//             }
            
        }
    }
    
    
    /**
     * 编辑
     */
    public function edit(){
        $model = M($this->mode_table);
        
        if(IS_POST){
            $ajax = I('post.ajax',0,'int');
            if ($ajax === 1){  
                $id = I('post.id',0,'int');
                $redata = $model->find($id);
                if ($redata){
                    $redata['status'] = 1;
                }else {
                    $redata['status'] = 0;
                }
                
                echo json_encode($redata);
                exit();
            }else {
                $des = I('post.des','0','string');
                $nid = I('post.nid',0,'int');
                $data = array(
                    'des' => $des,
                );
                $model->where(array('id' => $nid))->data($data)->save()?$this->success('修改成功'):$this->error('修改失败');
            }
           
        }
    }
    
    /**
     * 发送通知
     */
    public function sendNtc(){
        $user = M($this->mode_user);
        $msg = M($this->mode_msg);
        $notice = M($this->mode_table);
        
        if(IS_POST){
            $nid = I('post.nid',0,'int');
            $ajax = I('post.ajax',0,'int');
            if ($ajax == 2){
                $ndata = $notice->find($nid);
                $udata = $user->select();
                foreach ($udata as $k=>$v){
                    $send = array(
                        'uid' => $v['id'],
                        'des' => $ndata['des'],
                        'posttime' => time(),
                        'releasetime' => time(),
                        'nid' => $ndata['id'],
                    );
                    $msg->data($send)->add();
                }
                $notice->where(array('id' => $nid))->data(array('releasetime' => time()))->save();
                $this->ajaxReturn(1);
            }else {
                $this->ajaxReturn(0);
            }
        }
    }
    
    /**
     * 删除
     */
    public function del(){
        $id = I('post.id',0,'int');
        if($id>0){
            $mode = M($this->$model_table_type);
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

