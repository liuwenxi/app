<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Lib\until\Category;
/**
 * 任务控制器
 */
class TaskController extends CommonController {
    
    /**
     * 任务列表
     */
    public function lists(){
        $Task = M('Task');
        $Admin = M('Admin');

        $count = $Task->order('id desc')->count();
        $Page = new \Think\Page($count,10);
        $show = $Page->show();
        $data = $Task->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach ($data as $k=>$v){
            $data[$k]['aid'] = $this->get_admin_uname($v['aid']);
            $data[$k]['up_aid'] = $this->get_admin_uname($v['up_aid']);
            if (!empty($v['update_time'])){
                $data[$k]['update_time'] = date("Y-m-d H:i:s", $v['update_time']);
            }
        }

        $this->assign('page',$show);
        $this->assign('list', $data);
        $this->display();
    }
    
    /**
     * 每日任务列表
     */
    public function evdlists(){
        $EvdayTask = M('EvdayTask');

        $data = $EvdayTask->lock(true)->order('id desc')->select();
        //$flag = $this->chk_task_exit($data);

//         if ($flag === TRUE){     //true代表今天的每日任务存在
//             $this->assign('list', $data);
//             $this->display();
//         }else {
//             $showdata = $EvdayTask->order('id desc')->select();
            
//             $this->assign('list', $showdata);
            $this->display();
//         }
    }
    
    /**
     * 添加文章
     */
    public function add(){
         if(IS_POST){
//              print_r($_POST);exit();
            $mode = M('Task');
            $mode->create();
            $pubtime = $mode->posttime;
            if(empty($pubtime)){
                $mode->posttime = time();
            }else{
                $mode->posttime = strtotime($pubtime);
            }
            $mode->aid = session('uid');
            $mode->add()?$this->success('添加成功'):$this->error('添加失败');
        }else{
            $normal = C('NORMOAL_TASK');
//             $mode=M('news_cate');
//             $data = $mode->select();
//             $wcate = new Category();
//             $catedata = $wcate->unlimitedForLevel($data, '--', 0, 0);
//             $this->assign('cate',$catedata);

            $this->assign('task', $normal);
            $this->display();
        }
        
    }
    
     /**
     * 编辑文章
     */
    public function edit(){
         if(IS_POST){
            $mode=M('Task');
            $mode->create();
            $mode->update_time = time();
            $mode->up_aid = session('adminid');
//             $mode->posttime = strtotime(I('post.posttime'));
            $mode->save()?$this->success('修改成功'):$this->error('修改失败');
        }else{
            $id = I('get.id');
            $artdata = M('Task')->find($id);
            
            $normal = C('NORMOAL_TASK');
//             $mode=M('news_cate');
//             $data = $mode->select();
//             $wcate = new Category();
//             $catedata = $wcate->unlimitedForLevel($data, '--', 0, 0);
            $this->assign('data',$artdata);
            $this->assign('task', $normal);
            $this->assign('cate',$catedata);
            $this->display();
        }
        
    }

        /**
     * 文章分类
     */
    public function artCate(){
        $mode=M('news_cate');
        $data = $mode->select();
		$wcate = new Category();
        $catedata = $wcate->unlimitedForLevel($data, '---', 0, 0);
		$this->assign('cate',$catedata);// 赋值数据集
		$this->display();
    }
    
    /*
     * 增加分类
     */
    public function addCate(){
        if(IS_POST){
            $mode=M('news_cate');
            $mode->create();
            $mode->add()?$this->success('添加成功'):$this->error('添加失败');
        }else{
            $mode=M('news_cate');
            $data = $mode->select();
            $wcate = new Category();
            $catedata = $wcate->unlimitedForLevel($data, '--', 0, 0);
            $this->assign('cate',$catedata);
            $this->display();
        }
    }
    
     /*
     * 编辑分类
     */
    public function editCate(){
        if(IS_POST){
            $mode=M('news_cate');
            $mode->create();
            $mode->save()?$this->success('修改成功'):$this->error('修改失败');
        }else{
            $id = I('get.id');
            $mode=M('news_cate');
            $data = $mode->select();
            $catedata = $mode->find($id);
            $wcate = new Category();
            $cate = $wcate->unlimitedForLevel($data, '--', 0, 0);
            
            $this->assign('data',$catedata);
            $this->assign('cate',$cate);
            $this->display();
        }
    }
    
    /**
     * 删除分类
     */
    public function delCate(){
        $id = I('get.id');
        $mode=M('news_cate');
        $mode->delete($id)?$this->success('删除成功'):$this->error('删除失败');
    }
    
    /**
     * 删除每日任务
     */
    public function ekdel(){
        $id = I('get.id');
        $mode=M('EvdayTask');
        $mode->delete($id)?$this->success('删除成功'):$this->error('删除失败');
    }
    
}
