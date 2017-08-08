<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class QAController extends AdminController{
    private $mode_table = 'qa';
    private $mode_user = 'user';
    private $mode_msg = 'message';
/**
     * 列表
     */
    public function lists(){
        $model = M($this->mode_table);

        if (IS_POST){
            $class_id = I('post.class_id',0,'int') ? I('post.class_id') : 0 ;
            
            $mdata = $model->where(array('class_id' => $class_id))->order('sort desc')->select();
            foreach ($mdata as $k=>$v){
                $mdata[$k]['ans'] = $this->subtext($v['ans'], 25);
            }
            if ($mdata){
                $redata['status'] = 1;
            }else {
                $redata['status'] = 0;
            }
            
            $redata['list'] = $mdata;
            
            echo json_encode($redata);
            exit();
        }else {
            $qaclass= M('qa_class')->where(array('is_show'=>1))->find();
            $qa = $model->where(array('class_id' => $qaclass['id']))->order('sort desc')->select();
            $qaClass= M('qa_class')->order('sort desc')->select();

            $this->assign('qa',$qa);
            $this->assign('qaClass',$qaClass);
            $this->assign('class_id',$qaclass['id']);

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
     * 添加
     */
    public function add(){
        $model = M('qa');

        if (IS_POST){
            $post=$_POST;
            $post['updatetime'] = time();
            
            $model->add($post)?$this->success('添加成功'):$this->error('添加失败');
        }
    }
    
    
    /**
     * 编辑
     */
    public function edit(){
        $model = M($this->mode_table);
        
        if(IS_POST){
            $ajax = I('post.ajax',0,'int');
            if ($ajax == 1){  
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
                $post=$_POST;
                $post['updatetime'] = time();

                $model->save($post)?$this->success('修改成功'):$this->error('修改失败');
            }
           
        }
    }
    
    /**
     * 删除
     */
    public function del(){
        $id = I('post.id',0,'int');
        if($id>0){
            $mode = M('qa');
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
    
    /**
     * 截取字符串
     */
    public function subtext($text, $length)
    {
        if(mb_strlen($text, 'utf8') > $length)
            return mb_substr($text, 0, $length, 'utf8').'...';
        return $text;
    }

    /*
     * Qa分类
     */
    public function qaClass()
    {
        $qaClass=M('qa_class');

        $qa = $qaClass->order('sort desc')->select();

        $this->assign('data',$qa);

        $this->display();
    }

    /*
     * classAdd
     */
    public function classAdd()
    {
        $qaClass=M('qa_class');
        if (IS_POST){
            $post=$_POST;
            $qaClass->add($post)?$this->success('添加成功'):$this->error('添加失败');
        }
    }

    /**
     * 编辑
     */
    public function classEdit(){
        $qaClass=M('qa_class');

        if(IS_POST){
            $ajax = I('post.ajax',0,'int');
            if ($ajax == 1){
                $id = I('post.id',0,'int');
                $redata = $qaClass->find($id);
                if ($redata){
                    $redata['status'] = 1;
                }else {
                    $redata['status'] = 0;
                }

                echo json_encode($redata);
                exit();
            }else {
                $data=$_POST;
                $qaClass->where(array('id'=>$data['id']))->save($data)?$this->success('修改成功'):$this->error('修改失败');
            }

        }
    }

    /**
     * 删除
     */
    public function classDel(){
        $id = I('post.id',0,'int');
         if($id>0){
             $qaClass=M('qa_class');
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

