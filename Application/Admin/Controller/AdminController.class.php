<?php
namespace Admin\Controller;
use Common\Controller\BaseController;
class AdminController extends BaseController {

    public function _initialize(){
        $Admin = M('Admin');


        //判断用户是否登陆后台
        is_login();

    }

	//管理员列表
    public function index(){
        $mode = M('Admin');
        $AdminRole = M('AdminRole');
        $count      = $mode->count();// 查询满足要求的总记录数
        $Page       = new \Think\Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $list = $mode->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($list as $key => $val){
            $roleid = $val['role_id'];
            $role = $AdminRole->where(array('role_id' => $roleid))->find();
            $list[$key]['role_name'] = $role['role_name'];
        }

        $rolelist = $AdminRole->select();

        $this->assign('page',$show);
        $this->assign('data',$list);
        $this->assign('rolelist',$rolelist);
        $this->display();
    }


    //角色列表
     public function role(){
    	$list = D('admin_role')->select();
    	$this->assign('list',$list);


    	$this->display();
    }

    //角色权限
    public function role_info(){
    	$role_id = I('get.role_id');
    	$tree = $detail = array();
    	if($role_id){
    		$detail = D('admin_role')->where("role_id=$role_id")->find();
    		$this->assign('detail',$detail);
    	}

    	$res = D('system_module')->order('mod_id ASC')->select();
    	if($res){
    		foreach($res as $k=>$v){
    			if($detail['act_list']){
    				$act_list = explode(',', $detail['act_list']);
    				$v['enable'] = in_array($v['mod_id'], $act_list) ? 1 : 0;
    			}else{
    				$v['enable'] = 0 ;
    			}
    			$modules[$v['mod_id']] = $v;
    		}

    		if($modules){
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'top'){
    					$tree[$k] = $v;
    				}
    			}
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'menu'){
    					$tree[$v['parent_id']]['menu'][$k] = $v;
    				}
    			}
    			foreach($modules as $k=>$v){
    				if($v['module'] == 'module'){
    					$ppk = $modules[$v['parent_id']]['parent_id'];
    					$tree[$ppk]['menu'][$v['parent_id']]['menu'][$k] = $v;
    				}
    			}
    		}
    	}

    	$this->assign('menu_tree',$tree);
    	$this->display();
    }

    //添加、编辑角色
     public function roleSave(){
    	$data = I('post.');
    	$res = $data['data'];
    	$res['act_list'] = is_array($data['menu']) ? implode(',', $data['menu']) : '';
    	if(empty($data['role_id'])){
    		$r = D('admin_role')->add($res);
    	}else{
    		$r = D('admin_role')->where('role_id='.$data['role_id'])->save($res);
    	}
		if($r){
//			adminLog('管理角色',__ACTION__);  记录管理员操作日志
			$this->success("操作成功!",U('Admin/Admin/role',array('role_id'=>$data['role_id'])));
		}else{
			$this->success("操作失败!",U('Admin/Admin/role_info'));
		}
    }

	public function delRole(){
		$role_id = I('post.id',0,'int');
		if(!empty($_SESSION['adminid']) && $role_id > 0){
			$mode = M('AdminRole');
			$result = $mode->delete($role_id);
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