<?php

namespace Admin\Controller;
use Admin\Controller\AdminController;
/**
 * 意见反馈管理类
 */
class TypeController extends AdminController{
    private $mode_table = "type";
    private $mode_user = "user";
    private $model_table_type = "listtype";
    /**
     * 列表
     */
    public function lists(){
        $model = M($this->mode_table);
        $model_type = M($this->model_table_type);
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
            $mdata = $model_type->where(array('tpid' => 1))->select();
            
            $this->assign('typedata',$mdata);
            
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
        $model_type = M($this->model_table_type);
        if (IS_POST){
            $type = I('post.type');
            $typename = I('post.typename','0','string');
            
            //$tdata = $model->where(array('type' => $type))->find();
            $tsetdata = array('name' => $typename, 'tpid' => $type);
//             if ($model_type->create()){
                $res = $model_type->data($tsetdata)->add();
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
        $model_type = M($this->model_table_type);
        
        if(IS_POST){
            $ajax = I('post.ajax',0,'int');
            if ($ajax === 1){  
                $id = I('post.id',0,'int');
                $redata = $model_type->find($id);
                if ($redata){
                    $redata['status'] = 1;
                }else {
                    $redata['status'] = 0;
                }
                
                echo json_encode($redata);
                exit();
            }else {
                $type = I('post.type',0,'int');
                $tpname = I('post.typename','0','string');
                $tpid = I('post.tpid',0,'int');
                $data = array(
                    'name' => $tpname,
                    'tpid' => $type,
                );
                $model_type->where(array('id' => $tpid))->data($data)->save()?$this->success('修改成功'):$this->error('修改失败');
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
