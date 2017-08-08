<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;
use Lib\until\Category;

/**
 * 心愿帮
 */
class WishHelpController extends CommentController
{

    /**
     * 心愿帮列表
     */
    public function helplist()
    {
        $User = M('User');
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');
        if (IS_POST) {
            $post = I('post.');
            if (!empty($post['state'])) {
                $where['status'] = $post['state'];
                $this->assign('status', $post['state']);
            }
            if(!empty($post['time_sort'])){
                $this->assign('time_sort', $post['time_sort']);
                if ($post['time_sort'] == 1){
                        $order = 'post_time desc';
                    }elseif ($post['sort'] == 2){
                        $order = 'post_time asc';
                    };
            }
        } else {
            $where = array();
            $order = 'id asc';
        }
        $pagenum = 10; //每页显示记录数量
        $count = $Wishhelp->where($where)->order($order)->count(); //符合查询的总数；
        $page = new \Think\Page($count, $pagenum); //实例分页类，传入总记录数 和每页显示 记录数
        $show = $page->show(); //分页显示输出
        $data = $Wishhelp->where($where)->order($order)->limit($page->firstRow . ',' . $page->listRows)->select();
        foreach ($data as $k => $v) {
            $udata = $User->find($v['post_uid']);
           $data[$k]['nickname'] = $udata['nickname'];
        }


        $this->assign('page', $show);
        $this->assign('data', $data);
        $this->display();
    }
    public function joinlist(){
        $User = M('User');
        $WishhelpJoin=M('WishhelpJoin');
        if(IS_GET){
            $wish_id = I('get.wish_id');
            $joinList=$WishhelpJoin->where(array('wish_id'=>$wish_id))->select();
            foreach ($joinList as $key => $val){
                $userInfo=$User->find($val['join_uid']);
                $joinList[$key]['avatar']=$userInfo['avatar'];
                $joinList[$key]['nickname']=$userInfo['nickname'];
                $joinList[$key]['credit']=$userInfo['credit'];
            }
            $this->assign('wish_id', $wish_id);
            $this->assign('data', $joinList);
        }
        if (IS_POST) {
            $post = I('post.');
            $wish_id = $post['wish_id'];
            $where['wish_id'] = $wish_id;
            if (!empty($post['state'])) {
                $where['status'] = $post['state'];
                $this->assign('status', $post['state']);
            }
            if(!empty($post['time_sort'])){
                $this->assign('time_sort', $post['time_sort']);
                if ($post['time_sort'] == 1){
                    $order = 'join_time desc';
                }elseif ($post['sort'] == 2){
                    $order = 'join_time asc';
                };
            }

            $joinList=$WishhelpJoin->where($where)->order($order)->select();
            foreach ($joinList as $key => $val){
                $userInfo=$User->find($val['join_uid']);
                $joinList[$key]['avatar']=$userInfo['avatar'];
                $joinList[$key]['nickname']=$userInfo['nickname'];
                $joinList[$key]['credit']=$userInfo['credit'];
            }
            $this->assign('wish_id', $wish_id);
            $this->assign('data', $joinList);
        }
        $this->display();
    }

    /**
     * 查看心愿
     */

    public function details(){
        $User = M('User');
        $Wishhelp=M('Wishhelp');
        $id = I('get.wish_id');

        $data = $Wishhelp->find($id);
        $user = $User->find($data['post_uid']);
        $this->assign('data', $data);
        $this->assign('user', $user);

        $this->display();
    }

    /**
     * 删除心愿
     */

    public function delwish(){
        $Wishhelp=M('Wishhelp');
        $wish_id = I('get.wish_id');
        $where['wish_id'] = $wish_id;
        if($Wishhelp->delete($wish_id)){
            $this->success('删除成功');exit();
        }
        $this->error('删除失败');exit();
    }

    /**
     * 选择帮助
     */

    public function CheckedUser(){

        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');

        $id = I('get.id');

        $where['id'] = $id;
        $info=$WishhelpJoin->find($id);

        $newwhere['wish_id']=$info['wish_id'];
        $newwhere['status']=1;
        $newinfo=$WishhelpJoin->where($newwhere)->find();
        if(!empty($newinfo)){
            $this->error('该心愿已有人帮助');exit();
        }

        $wishinfo=$Wishhelp->find($info['wish_id']);
        if($wishinfo['status']!=0){
            $this->error('该心愿不是待选择状态');exit();
        }
        $m=M();
        $m->startTrans();
        $data['status']=1;
        if(!$Wishhelp->where(array('id'=>$info['wish_id']))->save($data)){
            $m->rollback();
            $this->error('更新数据失败1');exit();
        }
        $newdata['status']=1;
        $newdata['select_time']=time();
        if(!$WishhelpJoin->where($where)->save($newdata)){
            $m->rollback();
            $this->error('更新数据失败2');exit();
        }
        if(!$this->addUseDynamic($info['join_uid'],3,$id,11)){
            $m->rollback();
            $this->error('更新数据失败3');exit();
        }
        $m->commit();
        $this->success('操作成功');exit();
    }

    //取消报名
    public function canselSignUp()
    {
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');

        $id=I('get.id');   //心愿id

        //判断是否参与
        $where=array();
        $where['id']=$id;
        $newWish=$WishhelpJoin->where($where)->find();
        if($newWish['status']>=1){
            $this->error('不是待选择状态，无法取消报名');exit();
        }

        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$newWish['wish_id']))->find();
        if($wish['status']>=1){
            $this->error('心愿进行中无法取消报名');exit();
        }

        //修改参与数据并判断
        $data=array();
        $data['cancel_sign_time']=time();
        $data['status']=2;

        if($WishhelpJoin->where(array('id'=>$newWish['id']))->save($data)){
            $this->addUseDynamic($newWish['join_uid'],3,$id,5);
            $this->success('操作成功');exit();
        }
        $this->error('操作失败');exit();

    }

    //取消帮助
    public function canselHelp()
    {
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');

        $id=I('get.id');   //心愿id

        //判断是否被选中
        $where=array();
        $where['id']=$id;
        $newWish=$WishhelpJoin->where($where)->find();
        if($newWish['status']!=1){
            $this->error('不是选中状态，无法取消帮助');exit();
        }

        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$newWish['wish_id']))->find();
        if($wish['status']!=1){
            $this->error('不是待选择状态，无法取消帮助');exit();
        }


        //修改参与数据并判断
        $data=array();
        $data['cancel_help_time']=time();
        $data['status']=3;

        $data2=array();
        $data2['status']=0;

        if($WishhelpJoin->where(array('id'=>$id))->save($data) && $Wishhelp->where(array('id'=>$newWish['wish_id']))->save($data2)){
            $this->addUseDynamic($newWish['join_uid'],3,$newWish['wish_id'],6);
            $this->success('操作成功');exit();
        }
        $this->error('操作失败');exit();

    }

    /**
     * 添加心愿
     */
    public function createWishHelp(){
        $User = M('User');
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');

        $userlist1=$User->field('id,nickname')->where('type = 1')->select();
        $userlist2=$User->field('id,nickname')->where('type = 2')->select();
        $userlist3=$User->field('id,nickname')->where('type = 3')->select();
        $userlist4=$User->field('id,nickname')->where('type = 4')->select();
        $userlist=$User->field('id,nickname')->where('type > 0')->select();
        if(IS_POST){
            $post_uid1 = I('post.post_uid1');
            $post_uid2 = I('post.post_uid2');
            $post_uid3 = I('post.post_uid3');
            $post_uid4 = I('post.post_uid4');
            $content = I('post.content');
            $join_id = I('post.join_id');
            $post_uid=0;
            if($post_uid4>0){
                $post_uid=$post_uid4;
            }else if($post_uid3>0){
                $post_uid=$post_uid3;
            }else if($post_uid2>0){
                $post_uid=$post_uid2;
            }else if($post_uid1>0){
                $post_uid=$post_uid1;
            }


            if(!$post_uid || !$content){
                $this->error('必填信息不能为空');
                exit;
            }

            if (!empty($_FILES['img']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/wishhelp/'; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');

                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $img_url = get_url('/Uploads/wishhelp/' . $info['img']['savepath'] . $info['img']['savename']);
                }
            }
            $time=time();
            $data=array();
            $data['post_uid']=$post_uid;
            $data['content']=$content;
            $data['img']=$img_url;
            $data['post_time']=$time;
            $data['status']=0;
            $data['set_gender']=2;
            $data['set_count']=20;
            $data['wish_bean']=0;
            $data['check_time']=$time;
            $data['type']=0;

            //开启事务
            $m=M();
            $m->startTrans();
            $add_id=$Wishhelp->add($data);
            if(!$add_id){
                $m->rollback();
                $this->error('添加失败1');
                exit;
            }
            $data=array();
            $data['wish_id']=$add_id;
            $data['join_time']=$time;
            $data['massge']='i can help you!';
            $data['status']=0;
            foreach ($join_id as $key => $value){
                $data['join_uid']=$value;
                if(!$WishhelpJoin->add($data)){
                    $m->rollback();
                    $this->error('添加失败2');
                    exit;
                }
            }
            $m->commit();
            $this->success('添加成功');exit();
        }
        $this->assign('userlist', $userlist);
        $this->assign('userlist1', $userlist1);
        $this->assign('userlist2', $userlist2);
        $this->assign('userlist3', $userlist3);
        $this->assign('userlist4', $userlist4);
        $this->display();
    }
    /**
     * 添加用户心愿行为记录
     * @params $uid  用户id
     * @params $type  1心愿汇，2心愿墙，3心愿帮
     * @params $wish_id  心愿id
     * @params $class  1发布，2参与，3报名，4帮助，5取消报名，6取消帮助，7审核，8精选，9热门，10完成
     * by karl 2017-04-06 11:10:08
     **/
    public function addUseDynamic($uid,$type,$wish_id,$class){
        $userDynamic=M('userDynamic');
        $data['uid']=$uid;
        $data['type']=$type;
        $data['wish_id']=$wish_id;
        $data['class']=$class;
        $data['add_time']=time();
        return $userDynamic->add($data);
    }

    /*
     * 心愿帮热点
     */
    public function hotlist(){
        $WishhelpHot=M('WishhelpHot');
        $hotlist=$WishhelpHot->field(true)->select();
        $this->assign('data', $hotlist);
        $this->display();
    }

    /*
    * 添加心愿帮热点
    */
    public function addhot(){
        $WishhelpHot=M('WishhelpHot');
        if(IS_POST){
            $post = I('post.');

            if (!empty($_FILES['img']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/wishhelp/'; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');

                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $img_url = get_url('/Uploads/wishhelp/' . $info['img']['savepath'] . $info['img']['savename']);
                }
            }
            $time=time();
            $data=array();
            $data['name']=$post['name'];
            $data['introduce']=$post['introduce'];
            $data['img']=$img_url;
            $data['state']=$post['state'];
            $data['sort']=!$post['sort']?0:$post['sort'];
            $data['created_at']=$time;

            $add_id=$WishhelpHot->add($data);
            if(!$add_id){
                $this->error('添加失败');
                exit;
            }
            $this->success('添加成功');exit();
        }
        $this->display();
    }

    public function delkey(){
        if(IS_GET){
            $id = I('get.id');
            $WishhelpHot=M('WishhelpHot');
            if($WishhelpHot->delete($id)){
                $this->success('删除成功');exit();
            }
        }
        $this->error('删除失败');exit();
    }
}
