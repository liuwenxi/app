<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class FilterController extends AdminController{
    public function index(){
    	$strarr = file_get_contents('./Data/filter.txt');


        $this->assign('des',$strarr);
        
        $this->display();
    }
    
    public function edit(){
        if(IS_POST){
            $data = I('post.');

            if(empty($data)){
                $this->error = '数据创建失败！';
                return false;
            }
            $data = str_replace("", "\n", $data);
            $res = file_put_contents('./Data/filter.txt', $data);
            
            if ($res > 0){
            	$this->success('修改成功');
            }else {
            	$this->error('修改失败');
            }
            
        }
    }
    
}

