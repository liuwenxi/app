<?php
namespace Admin\Controller;
use Think\Controller;
class NewsController extends Controller {
    public function index(){

    	//判断是否登陆后台
    	is_login();

    	$News = M('News');

    	$count = $News->count();
    	
    	$Page = new \Think\Page($count,5);
    	$show = $Page->show();
    	$temp = $News->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach ($temp as $key => $value) {
            $list[$key] = array(
                    'id' => $value['id'],
                    'title' => $value['title'],
                    'content' => htmlspecialchars_decode(msubstr($value['content'],0,50,"utf-8",$suffix=true)),
                    'create_time' => $value['create_time'],
                    'class_name' => $value['class_name'],
                );
        }

    	//分页操作 无刷新Ajax
    	if (IS_AJAX) {
    		$data['page'] = $show;
    		$data['list'] = $list;
    		echo json_encode($data);
    		exit;
    	}

    	//注册变量
    	$this->assign('page',$show);
    	$this->assign('list',$list);
        
    	//加载视图
        $this->display();
    }

    public function add(){

    	//判断是否登陆后台
    	is_login();

        $News = M('News');
        if (IS_POST) {
            
            //获取选择de数据
            $sea = $_POST['search'];

            //接收POST过来的News数据
            $data['title'] = $_POST['title'];
            $data['class_name'] = $sea;
            $data['content'] = htmlspecialchars($_POST['editor']);
            $data['create_time'] = date("Y-m-d H:i:s");
            print_r($data);
            // 根据表单提交的POST数据创建数据对象
            if($News->create()){
                $result = $News->data($data)->add(); // 写入数据到数据库 
                if($result){
                    // 如果主键是自动增长型 成功后返回值就是最新插入的值
                    $insertId = $result;
                }
            }

        }

        //注册变量
        $this->assign('menu',$menu);
        $this->assign('info',$info);

        

    	//加载视图
    	$this->display();
    }

    public function update(){

    	//判断是否登陆后台
    	is_login();

        $News = M('News');
        if (IS_POST) {

            //获取选择de数据
            $sea = $_GET['search'];

            $data['id'] = $_POST['id'];
            $data['class_name'] = $sea;
            $data['content'] = htmlspecialchars($_POST['editor']);
            $data['create_time'] = date("Y-m-d H:i:s");
            $result = $News->save($data);

            if (empty($result)) {
                $this->error('更新失败！');exit;
            }else {
                $this->success('更新成功！', U('News/index'));exit;
            }
        }

        //get到更新的id
        $id = $_GET['id'];

        $list = $News->find($id);

        
        //注册变量
        $this->assign('menu',$menu);
        $this->assign('list',$list);
        $this->assign('result',$result);

    	//加载视图
    	$this->display();
    }

    public function del(){

    	//判断是否登陆后台
    	is_login();


    	$getid = $_REQUEST['id'];
        //$id = I('get.id',htmlspecialchars);

        if (!$getid)
            $this->error('未选择任何数据！');
        $getids = implode(',', $getid);
        $id = is_array($getid)?$getids:$getid;
        //print_r($id);exit;
        $result = M('News')->delete($id);
        if (empty($result)) {
            $this->error('del失败');

        }else{
            $this->success("{$result}条数据del成功",U('News/index'));

        }
    }

    
}