<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;
use Lib\until\Category;

/**
 * 周刊管理 @author jomlz
 */
class CmsController extends CommentController
{

    /**
     * 所有文章列表
     */
    public function artlist()
    {
        $Newsweek = M('Newsweek');
        $pid = I('get.id', 0, 'int') ? I('get.id') : 0;

        if (IS_GET) {
            $get = I('get.');
            if (isset($get['posttime'])) {
                $posttime = I('posttime');         //发布时间查询
                $gap = explode('-', $posttime);
                $begin = strtotime($gap[0]);
                $end = strtotime($gap[1]) + 86400;
                $where['posttime'] = array('between', "$begin,$end");
            } else
                if (isset($get['keyword'])) {           //模糊字查询
                    $keywords = $get['keyword'];
                    $where['title'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                    $where['des'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                    $where['_logic'] = 'OR';
                }
        } else {
            $where = array();
        }
        $pagenum = 10; //每页显示记录数量
        $order = 'id asc';
        $count = $Newsweek->where($where)->order($order)->count(); //符合查询的总数；
        $page = new \Think\Page($count, $pagenum); //实例分页类，传入总记录数 和每页显示 记录数
        $show = $page->show(); //分页显示输出
        $data = $Newsweek->where($where)->order($order)->limit($page->firstRow . ',' . $page->listRows)->select();


        $this->assign('page', $show);
        $this->assign('pid', $pid);
        $this->assign('artlist', $data);
        $this->display();
    }

    /**
     * 周刊管理
     */
    public function weekly()
    {
        $Newsweek = M('Newsweek');
        $NewsCate = M('NewsCate');

        $pagenum = 10; //每页显示记录数量
        $count = $NewsCate->count(); //符合查询的总数；
        $page = new \Think\Page($count, $pagenum); //实例分页类，传入总记录数 和每页显示 记录数
        $show = $page->show(); //分页显示输出
        $data = $NewsCate->limit($page->firstRow . ',' . $page->listRows)->select();

        foreach ($data as $k => $v) {
            $catid = array($v['id']);
            foreach ($catid as $kk => $vv) {
                $number = $Newsweek->where(array('news_cate_id' => $vv))->count();
                $data[$k]['num'] = $number;
            }
        }

        $this->assign('page', $show);
        $this->assign('artlist', $data);
        $this->display();
    }

    /**
     * 添加文章
     */
    public function addart()
    {
        $mode = M('Newsweek');
        $NewsCate = M('NewsCate');
        $cid = $_GET['cid'];
        if (IS_POST) {
            $cate = $_POST['news_cate_id'];
            $title = $_POST['title'];
            $subtitle = $_POST['subtitle'];
            $des = $_POST['des'];
            $author = $_POST['author'];
            $content = $_POST['content'];

            if (empty($title) || empty($des) || empty($author) || empty($content) || empty($_FILES['touimg']['name'])) {
                $this->error('必填信息不能为空');
                exit;
            }


            if (!empty($_FILES['touimg']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/image/cms/'; // 设置附件上传根目录
                $rootPath='/Uploads/image/cms/';
                $upload->subName = array('date', 'Ymd');

                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $img_url = get_url($rootPath. $info['touimg']['savepath'] . $info['touimg']['savename']);
                }
            }

            $data = array(
                'news_cate_id' => $cate,
                'title' => $title,
                'subtitle' => $subtitle,
                'des' => $des,
                'author' => $author,
                'content' => $content,
                'posttime' => time(),
                'aid' => $_SESSION['adminid'],
                'touimg' => $img_url,
            );

            if (!empty($cid)) {
                $mode->data($data)->add() ? $this->success('添加成功', U('Cms/weekly')) : $this->error('添加失败');
            } else {
                $mode->data($data)->add() ? $this->success('添加成功', U('Cms/artlist')) : $this->error('添加失败');
            }
        } else {
            $catenumber = $NewsCate->where(array('id' => $cid))->find();

            $data = $NewsCate->select();
            $this->assign('cate', $data);
            $this->assign('catenumber', $catenumber);
            $this->display();
        }
    }

    /**
     * 编辑文章
     */
    public function editart()
    {
        if (IS_POST) {
            $mode = M('Newsweek');

            if (empty($_POST['title']) || empty($_POST['des']) || empty($_POST['author']) || empty($_POST['content'])) {
                $this->error('必填信息不能为空');
                exit;
            }

            if (!empty($_FILES['touimg']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/image/cms/'; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');

                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $img_url = $upload->rootPath . $info['touimg']['savepath'] . $info['touimg']['savename'];
                }
            }
            $data = array(
                'id' => $_POST['id'],
                'news_cate_id' => $_POST['news_cate_id'],
                'title' => $_POST['title'],
                'subtitle' => $_POST['subtitle'],
                'des' => $_POST['des'],
                'author' => $_POST['author'],
                'content' => $_POST['content'],
                'update_time' => time(),
                'aid' => $_SESSION['adminid'],
            );

            $acdata = $mode->find($_POST['id']);
            if (!empty($img_url)) {
                $data['touimg'] = $img_url;
                $touimg = $acdata['touimg'];
                is_file($touimg) && @unlink($touimg);
            } else {
                $data['touimg'] = $acdata['touimg'];
            }

            $mode->data($data)->save() ? $this->success('修改成功', U('Cms/artlist')) : $this->error('修改失败');
        } else {
            $id = I('get.id');
            $artdata = M('Newsweek')->find($id);
            $mode = M('news_cate');
            $data = $mode->select();
            $this->assign('cate', $data);
            $this->assign('data', $artdata);
            $this->display();
        }
    }

    /*
    * 添加周刊
    */

    public function addWeekly()
    {
        if (IS_POST) {
            $number = $_POST['number'];
            $title = $_POST['title'];
            $content = $_POST['content'];
            $subtitle = $_POST['subtitle'];
            if (empty($number) || empty($title) || empty($content) || empty($_FILES)) {
                $this->error('信息不完整');
            }

            if (!empty($_FILES['img']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/image/cms/'; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');

                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();

                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $img_url = $upload->rootPath . $info['img']['savepath'] . $info['img']['savename'];
                }
            }

            $data = array(
                'number' => $number,
                'title' => $title,
                'subtitle' => $subtitle,
                'content' => $content,
                'img' => $img_url,
                'created_at' => time(),
                'aid' => $_SESSION['adminid']
            );

            $mode = M('news_cate');
            $mode->data($data)->add() ? $this->success('添加成功', U('Cms/weekly')) : $this->error('添加失败');
        } else {

            $mode = M('news_cate');
            $data = $mode->select();
            $this->assign('cate', $data);
            $this->display('addWeekly');
        }
    }


    /**
     * 编辑周刊
     */
    public function editWeekly()
    {
        if (IS_POST) {
            $mode = M('NewsCate');
            $content = $_POST['content'];
            if (empty($_POST['title']) || empty($content)) {
                $this->error('信息不完整');
            };
            if (!empty($_FILES['img']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/image/cms/'; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');

                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $img_url = $upload->rootPath . $info['img']['savepath'] . $info['img']['savename'];
                }
            }
            $data = array(
                'id' => $_POST['id'],
                'title' => $_POST['title'],
                'subtitle' => $_POST['subtitle'],
                'content' => $content,
                'updated_at' => time(),
                'aid' => $_SESSION['adminid'],
            );

            $acdata = $mode->find($_POST['id']);
            if (!empty($img_url)) {
                $data['img'] = $img_url;
                $img = $acdata['img'];
                is_file($img) && @unlink($img);
            } else {
                $data['img'] = $acdata['img'];
            }


            $mode->data($data)->save() ? $this->success('修改成功', U('Cms/weekly')) : $this->error('修改失败');
        } else {

            $id = I('get.id');
            $data = M('news_cate')->find($id);
            $this->assign('data', $data);
            $this->display();
        }
    }


    /**
     * 删除文章
     */

    public function delart()
    {
        $id = I('get.id');
        $model = M('Newsweek');
        $model->delete($id) ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * 删除周刊
     */

    public function delweekly()
    {
        $id = I('get.id');
        $model = M('NewsCate');
        $model->delete($id) ? $this->success('删除成功') : $this->error('删除失败');
    }


    /**
     * 周刊所属期数文章列表
     */

    public function weekarticle()
    {
        $Newsweek = M('Newsweek');
        $NewsCate = M('NewsCate');

        $cid = I('get.id', 0, 'int') ? I('get.id') : 0;
        $cate = $NewsCate->find($cid);


        $pagenum = 10; //每页显示记录数量
        $where = array('news_cate_id' => $cid);
        $count = $Newsweek->where($where)->count(); //符合查询的总数；
        $page = new \Think\Page($count, $pagenum); //实例分页类，传入总记录数 和每页显示 记录数
        $show = $page->show(); //分页显示输出
        $data = $Newsweek->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();

        $this->assign('page', $show);
        $this->assign('cate', $cate);
        $this->assign('artlist', $data);
        $this->display();
    }

    /**
     * 选取文章
     */

    public function selectarticle()
    {
        $Newsweek = M('Newsweek');
        $NewsCate = M('NewsCate');
        $cid = I('get.cid', 0, 'int');
        $cate = $NewsCate->find($cid);
        if(IS_POST){
           $articleId = I('post.id');
           if(!empty($articleId)){
                foreach($articleId  as $k => $v){
                    $save = $Newsweek->where(array('id' => $v))->data(array('news_cate_id' => $cid))->save();
                }
                if($save){
                    $this->success('选取成功', U('Cms/weekly'));
                }else{
                    $this->error('不知名的悲伤，请重新选取！');
                }
            }
        }else{
            $pagenum = 10; //每页显示记录数量
            $where['news_cate_id'] = array('neq', $cid);
            $count = $Newsweek->where($where)->count(); //符合查询的总数；
            $page = new \Think\Page($count, $pagenum); //实例分页类，传入总记录数 和每页显示 记录数
            $show = $page->show(); //分页显示输出
            $data = $Newsweek->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
            $this->assign('page', $show);
            $this->assign('cate', $cate);
            $this->assign('artlist', $data);
            $this->display();
        }
    }

    /**
     * 文章分类
     */
//    public function artCate() {
//        $mode = M('news_cate');
//        $data = $mode->select();
//        $wcate = new Category();
//        $catedata = $wcate->unlimitedForLevel($data, '---', 0, 0);
//        $this->assign('cate', $catedata); // 赋值数据集
//        $this->display();
//    }


    /*
     * 编辑分类
     */

//    public function editCate() {
//        if (IS_POST) {
//            $mode = M('news_cate');
//            $mode->create();
//            $mode->save() ? $this->success('修改成功') : $this->error('修改失败');
//        } else {
//            $id = I('get.id');
//            $mode = M('news_cate');
//            $data = $mode->select();
//            $catedata = $mode->find($id);
//            $wcate = new Category();
//            $cate = $wcate->unlimitedForLevel($data, '--', 0, 0);
//
//            $this->assign('data', $catedata);
//            $this->assign('cate', $cate);
//            $this->display();
//        }
//    }

    /**
     * 删除分类
     */
//    public function delCate() {
//        $id = I('get.id');
//        $mode = M('news_cate');
//        $mode->delete($id) ? $this->success('删除成功') : $this->error('删除失败');
//    }

}
