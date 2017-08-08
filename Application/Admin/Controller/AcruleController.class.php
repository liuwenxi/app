<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

/*
 * 动态分类控制器
 */
class AcruleController extends AdminController {
    
    public function index(){

		
		
    }
    
    //列表页
    public function lists(){
        $ActiveRule = M('ActiveRule');
        $Admin = M('Admin');
        
        $count = $ActiveRule->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $order = array('id' => 'DESC');
        $acdata = $ActiveRule->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($acdata as $k=>$v){
            $aid = $Admin->find($v['aid']);
            $acdata[$k]['aid'] = $aid['uname'];
            $acdata[$k]['type'] = C('TYPE.'.$v['type']);
        }
        
        //分页操作
        if (IS_AJAX){
            
        }
    
        $this->assign('list', $acdata);
        
        $this->display();
    }
    
    //添加
    public function add(){
        $ActiveRule = M('ActiveRule');
        if (IS_POST){
            $data = array(
                'aname' => I('post.name','0','string'),
                'type' => I('post.search',0,'int'),
                'aid' => session('uid'),
            );
            
//             if ($ActiveRule->create()){
                $res = $ActiveRule->data($data)->add();
                if ($res){
                    $insertId = $res;
                    $this->success('新增成功！',U('Acrule/lists'));
                }else {
                    $this->error('添加失败！');
                }
                
//             }else {
//                 $this->error($ActiveRule->getError());
//             }
        }else {
            $this->display();
        }
    }
    
    //修改
    public function edit(){
        $ActiveRule = M('ActiveRule');
        $Admin = M('Admin');
        
        if (IS_POST){
            $getid = I('get.id',0,'int');
            $data = array(
                'aname' => I('post.name','0','string'),
                'type' => I('post.search',0,'int'),
                'aid' => session('uid'),
            );
            $res = $ActiveRule->where(array('id' => $getid))->data($data)->save();
            if ($res != 0){
                $this->success('编辑成功！',U('Acrule/lists'));
            }else {
                $this->error($ActiveRule->getError());
            }
            
        }else {
            $id = I('get.id',0,'int');
            $acdata = $ActiveRule->find($id);
            
            $this->assign('acdata', $acdata);
            
            $this->display();
        } 
    }
    
    //删除
    public function del(){
        
        
    }
    
    //ajax提交照片
    public function uploadPic(){
        if (IS_POST){
            if ($_FILES > 0){
                $f = $_FILES['pic'];
                if ($f['size'] < C('UPLOAD.maxSize')){
                    $filename = C('UPLOAD.rootPath') . 'Banner/' . md5(uniqid(rand())) . '_' . $f['name'];
                    move_uploaded_file($f['tmp_name'], $filename);
                    //print_r($filename);
                    $redata['filename'] = $filename;
                    $redata['pic'] = "http://".$_SERVER['HTTP_HOST'].$filename;
                    $redata['wid'] = 12;
                    $redata['status'] = 1;
                    echo json_encode($redata);
                    exit();
                }else {
                    $redata['status'] = 2;  //超过可上传大小
                    echo json_encode($redata);
                    exit();
                }
                
            }else {
                echo '';
            }
        }
    }

    
}