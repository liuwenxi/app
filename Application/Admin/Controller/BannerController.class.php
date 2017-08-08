<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
class BannerController extends AdminController {
    
    public function index(){

		
		
    }
    
    //列表页
    public function lists(){
        $Banner = M('Banner');
        $Admin = M('Admin');
        $keyword=M('wishwall_keyword');
//         $count = $Banner->count();
//         $Page = new \Think\Page($count, 10);
//         $show = $Page->show();
//         $bdata = $Banner->order('id')->limit($Page->firstRow.','.$Page->listRows)->select();
        $bdata = $Banner->order(array('id desc'))->select();
        $status = array('显示','隐藏');
        foreach ($bdata as $key => $val){
            $bdata[$key]['posttime'] = date("Y-m-d H:i:s", $val['posttime']);
            $adata = $Admin->find($val['aid']);
            $bdata[$key]['admin'] = $adata['uname'];
            $bdata[$key]['is_no'] = $status[$val['is_no']];
        }
        $keywords=$keyword->field('id,keyword')->select();
        $this->assign('keywords',$keywords);
        $this->assign('data', $bdata);
        
        $this->display();
    }
    
    //添加
    public function add(){
        $Banner = M('Banner');
        
        if (IS_POST){

            $data = array(
                'kid'=>I('post.kid'),
                'img' => I('post.value'),
                'sort' => I('post.sort'),
                'posttime' => time(),
                'aid' => session('uid'),
                'jump_url' => I('post.url'),
                'is_no' => I('post.is_no'),
                'title' => I('post.title'),
            );
            if ($Banner->create()){
                $res = $Banner->data($data)->add();
                if ($res){
                    $insertId = $res;
                    $this->success('新增成功！');
                }else {
                    $this->error('添加失败！');
                }
                
            }else {
                $this->error('数据创建失败');
            }
        }
    }
    
    //修改
    public function edit(){
        $Banner = M('Banner');
        $Admin = M('Admin');
        if (IS_POST){
            $ajax = I('post.ajax',0,'int');
            if ($ajax === 1){    //编辑
                $id = I('post.id',0,'int');
                $redata = $Banner->find($id);
                if ($redata){
                    $redata['status'] = 1;
                }else {
                    $redata['status'] = 0;
                }
                echo json_encode($redata);
                exit();
            }else {
                $getid = I('post.id',0,'int');
                $data = array(
                    'img' => I('post.value'),
                    'sort' => I('post.sort'),
                    'kid'=>I('post.kid'),
                    'posttime' => time(),
                    'aid' => session('uid'),
                    'jump_url' => I('post.url'),
                    'is_no' => I('post.is_no'),
                    'title' => I('post.title'),
                );
                $res = $Banner->where(array('id' => $getid))->data($data)->save();
                if ($res != 0){
                    $this->success('编辑成功！');
                }else {
                    $this->error('编辑失败！');
                }
            }
            
            
        }
    }
    
    //删除
    public function del(){
        $Banner = M('Banner');
        $id = I('post.id',0,'int');
        if($id>0){
            $result = $Banner->delete($id);
            if($result){
                //成功
                echo 1;
            }else {
                //失败
                echo 2;
            }
        }
        
    }
    
    //ajax提交照片
    public function uploadPic(){
        if (IS_POST){
            if ($_FILES > 0){
                $f = $_FILES['pic'];
                if ($f['size'] < C('UPLOAD.maxSize')){
                    $filename = C('UPLOAD.rootPath') . 'Banner/' . date("Ymd", time()) . md5(uniqid(rand())) . '_' . $f['name'];
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