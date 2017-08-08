<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

/*
 * 管理员控制器
 */
class ManagerController extends AdminController {
    public function index(){

    	
    }
    
    public function lists(){
        $Admin = M('Admin');
        
        $adata = $Admin->select();
        foreach ($adata as $k => $v){
            $adata[$k]['regtime'] = date("Y-m-d H:i:s", $v['regtime']);
        }
        
        $this->assign('list', $adata);
        $this->display();
    }
    
    public function add(){
        $Rule = M('Rule');
        $Admin = M('Admin');
        
        if (IS_POST){
            $ruleid = I('post.ruleid');
            if (is_array($ruleid)){
                foreach ($ruleid as $k=>$v){
                    $temp[] = $v;
                }
                $tt = implode(',', $temp);
            }
            $data = array(
                'uname' => I('post.uname','0','string'),
                'admin_cate' => serialize($tt),
                'password' => md5(I('post.pwd','0','string')),
                'regtime' => time(),
            );
            if ($Admin->create()){
                $res = $Admin->data($data)->add();
                if ($res){
                    $insertId = $res;
                    $this->success('添加成功！',U('Manager/lists'));
                }else {
                    $this->error('添加失败!');
                }
            }else {
                $this->error($Admin->getError());
            }
        }else {
            $rdata = $Rule->where(array('is_show' => 1))->select();
            
            $this->assign('rule', $rdata);
            
            $this->display();
        }
    }
    
    public function edit(){
        $Rule = M('Rule');
        $Admin = M('Admin');
        
        if (IS_POST){
            $getid = I('get.id',0,'int');
            $ruleid = I('post.ruleid');
            if (is_array($ruleid)){
                foreach ($ruleid as $k=>$v){
                    $temp[] = $v;
                }
                $tt = implode(',', $temp);
            }
            $data = array(
                'admin_cate' => serialize($tt),
                'regtime' => time(),
            );
            $res = $Admin->where(array('id' => $getid))->data($data)->save();
            if ($res != 0){
                $this->success('编辑成功！',U('Manager/lists'));
            }else {
                $this->error($Admin->getError());
            }
        }else {
            $id = I('get.id',0,'int');
            $adata = $Admin->find($id);
            $adata['admin_cate'] = unserialize($adata['admin_cate']);

            $rdata = $Rule->select();
            
            $this->assign('rule', $rdata);
            $this->assign('list', $adata);
            
            $this->display();
        }
    }
    
    public function del(){
        
        
    }
    

    
}