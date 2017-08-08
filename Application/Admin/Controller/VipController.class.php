<?php
namespace Admin\Controller;

use Admin\Controller\AdminController;

/**
 * 用户管理
 * @package Admin\Controller
 */

class VipController extends AdminController
{
    public function lists()
    {
        $User = M('User');
        if (IS_POST) {
            $post = I('post.');
            if (!empty($post['auth'])) {
                if ($post['auth'] == 3) {
                    $where['is_auth'] = 0;
                } else {
                    $where['is_auth'] = $post['auth'];
                }
            }
            if (!empty($post['sex'])) {
                if ($post['sex'] == 2) {
                    $where['gender'] = 0;
                } else {
                    $where['gender'] = 1;
                }
            }
            if (!empty($post['time'])) {
                if ($post['time'] == 1) {
                    $order = 'reg_time desc';
                } elseif ($post['time'] == 2) {
                    $order = 'reg_time asc';
                } elseif ($post['time'] == 3) {
                    $order = 'last_time desc';
                } elseif ($post['time'] == 4) {
                    $order = 'last_time asc';
                }
            }
            if (!empty($post['credit'])) {
                if ($post['credit'] == 1) {
                    $order = 'credit desc';
                } elseif ($post['credit'] == 2) {
                    $order = 'credit asc';
                }
            }
            if (!empty($post['reg_time'])) {
                $gap = explode('-', $post['reg_time']);
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['reg_time'] = array('between', array($begin,$end));
            }
            if (!empty($post['last_time'])) {
                $gap = explode('-', $post['last_time']);
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['last_time'] = array('between', array($begin,$end));
            }
            if (!empty($post['keyword'])) {
                $keywords = $post['keyword'];
                $where['nickname'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
            }
        } else {
            $where = array();
        }
        $listnum = 10;  //每页显示的数据量
        $count = $User->where("type=0")->where($where)->count();
        $Page = new \Think\Page($count, $listnum);
        $show = $Page->show();
        $udata = $User->where("type=0")->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($udata as $k => $v) {
            $true = unserialize($v['true_msg']);
            $udata[$k]['name'] = $true['name'];
            $udata[$k]['idcard'] = $true['id'];
        }

        $this->assign('list', $udata);
        $this->assign('page', $show);

        $this->display();
    }
    /**
     * 发送通知
     */
    public function notice(){
        $message = M('Message');
        $user = M('User');
        $uid = I('post.id','','int');
        if(IS_POST){
            if($_POST['type'] == '发送站内信息'){

            }
            if($_POST['type'] == '发送短信'){
                echo 12;exit;
            }
            var_dump($_POST);exit;
        }
    }

    /**
     *用户详情
     */
    public function details()
    {
        $user = M('User');
        $id = I('get.id', 0, 'int');
        $data = $user->find($id);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 实名认证列表
     */
    public function real()
    {
        $user = M('User');
        $userreal = M('UserReal');
        if ($_POST) {
            $post = I('post.');
            if (!empty($post['auth'])) {
                $where['is_auth'] = $post['auth'];
            }
            if (!empty($post['time'])) {
                if ($post['time'] == 1) {
                    $order = 'create_time desc';
                } else {
                    $order = 'create_time asc';
                }
            }
            if (!empty($post['keyword'])) {
                $keywords = $post['keyword'];
                $where['realname'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
            }

        } else {
            $where = array();
        }
        $listnum = 10;  //每页显示的数据量
        $count = $userreal->where($where)->count();
        $Page = new \Think\Page($count, $listnum);
        $show = $Page->show();
        $udata = $userreal->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($udata as $k => $v) {
            $u = $user->find($v['uid']);
            $udata[$k]['nickname'] = $u['nickname'];
        };
        $this->assign('list', $udata);
        $this->assign('page', $show);

        $this->display();
    }

    /**
     * 实名认证详情
     */
    public function realDetail(){
        $user = M('User');
        $userreal = M('UserReal');
        $id = I('get.id');

        $data = $userreal->find($id);
        $username = $user->where(array('id'=>$data['uid']))->find();
        $this->assign('data',$data);
        $this->assign('username',$username);
        $this->display();
    }

    /**
     * 实名认证审核
     */
    public function ajax_auth()
    {
        $user = M('User');
        $userreal = M('UserReal');
        $id = I('post.id', '', 'int');
        $type = I('post.type', '', 'int');
        $aid = session('adminid');
        if ($_POST) {
            $real = $userreal->find($id);
            if ($type == 1) {  //通过
                $save = $user->where(array('id' => $real['uid']))->data(array('is_auth' => 1))->save();
                if ($save) {
                    $reals = $userreal->where(array('id' => $id))->data(array('is_auth' => 1, 'aid' => $aid, 'aid_time' => time()))->save();
                    $content = '恭喜实名认证通过！童鞋，你果然是个有身份的人';
                    $this->sendMsg($real['uid'], '1', '', '', '1','','', $content);
                    echo 1;
                    exit;
                } else {
                    echo 2;
                    exit;
                }
            }
            if ($type == 2) { //驳回
                $save = $user->where(array('id' => $real['uid']))->data(array('is_auth' => 2))->save();
                if ($save) {
                    $reals = $userreal->where(array('id' => $id))->data(array('is_auth' => 2, 'aid' => $aid, 'aid_time' => time()))->save();
                    $content = '实名认证不通过！别急，你肯定是某项资料填错了，快点我去修改。';
                    $this->sendMsg($real['uid'], '1', '', '', '1','','实名认证', $content);
                    echo 1;
                    exit;
                } else {
                    echo 2;
                    exit;
                }
            }
        }
    }

    /**
     * 删除用户
     */

    public function del()
    {
        $user = M('User');
        $id = I('post.id', 0, 'int');
        if ($id > 0) {
            $result = $user->delete($id);
            if ($result) {
                //成功
                echo 1;
            } else {
                //失败
                echo 2;
            }
        }
    }

    /**
     * 水军列表
     */
    public function navy()
    {
        $navys = M('Navy');
        if ($_POST) {
            $post = I('post.');
            if (empty($post['username']) || empty($_FILES)) {
                $this->error('信息不完整');
            }
            if (!empty($_FILES['img']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/image/navy/'; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');
                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $file_url = $info['img']['savepath'] . $info['img']['savename'];
                    $img_url = get_url('/Uploads/image/navy/' . $file_url);
                }
            }
            $data = array(
                'username' => $post['username'],
                'img' => $img_url,
                'create_time' => time(),
                'aid' => $_SESSION['adminid']
            );
            $navys->data($data)->add() ? $this->success('添加成功', U('Vip/navy')) : $this->error('添加失败');
        } else {
            $count = $navys->count();// 查询满足要求的总记录数
            $Page = new \Think\Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show = $Page->show();// 分页显示输出
            $list = $navys->limit($Page->firstRow . ',' . $Page->listRows)->select();

            $this->assign('page', $show);
            $this->assign('data', $list);
            $this->display();
        }
    }

    /**
     * 编辑水军
     */
    public function edit_navy()
    {
        $navys = M('Navy');
        if (IS_POST) {
            $ajax = I('post.ajax', 0, 'int');
            if ($ajax === 1) {
                $id = I('post.id');
                $data = $navys->find($id);
                echo json_encode($data);
                exit();
            } else {
                $post = I('post.');
                if (empty($post['username'])) {
                    $this->error('信息不完整');
                }else{
                    if (!empty($_FILES['img']['name'])) {
                        $upload = new \Think\Upload(); // 实例化上传类
                        $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                        $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                        $upload->rootPath = './Uploads/image/navy/'; // 设置附件上传根目录
                        $upload->subName = array('date', 'Ymd');

                        !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                        $info = $upload->upload();
                        if (!$info) {// 上传错误提示错误信息
                            $this->error($upload->getError());
                            exit;
                        } else {
                            $file_url = $info['img']['savepath'] . $info['img']['savename'];
                            $img_url = get_url('/Uploads/image/navy/' . $file_url);
                        }
                    }

                    $data = array(
                        'id' => $post['id'],
                        'username' => $post['username'],
                        'updata_time' => time(),
                        'aid' => $_SESSION['adminid']
                    );
                    $acdata = $navys->find($post['id']);

                    if (!empty($img_url)) {
                        $data['img'] = $img_url;
                        $img = $acdata['img'];
                        is_file($img) && @unlink($img);
                    } else {
                        $data['img'] = $acdata['img'];
                    }

                    $navys->data($data)->save() ? $this->success('修改成功') : $this->error('修改失败');
                }
            }
        }
    }

    /*
     * 删除水军
     */
    public  function del_navy(){
        $navys = M('Navy');
        $id = I('post.id','','int');
        if($id > 0){
            $result = $navys->delete($id);
            if ($result) {
                //成功
                echo 1;
            } else {
                //失败
                echo 2;
            }
        }

    }

    public function state()
    {
        $navys = M('Navy');
        $id = I('post.id');
        $m = M();
        $m->startTrans();
        $result = $navys->where(array('id' => $id))->data(array('state' => 1))->save();
        $where['id'] = array('neq',$id);
        $save = $navys->where($where)->data(array('state'=>0))->save();
        if ($result && $save) {
            $m->commit();
            echo 1;
        } else {
            $m->rollback();
            echo 2;
        }
    }


    /**
     * 假用户
     */
    public function fakeuser(){
        $User = M('User');

        $where['type'] = array('NEQ',0);
        if($_GET['keyword']){
            $keywords = $_GET['keyword'];
            $where['nickname'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
            $this->assign('keyword', $keywords);
        }
        if($_GET['type']){
            $where['type']=$_GET['type'];
            $this->assign('type', $_GET['type']);
        }

        $listnum = 10;  //每页显示的数据量
        $count = $User->where($where)->count();
        $Page = new \Think\Page($count, $listnum);
        $show = $Page->show();
        $udata = $User->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($udata as $k => $v) {
            $true = unserialize($v['true_msg']);
            $udata[$k]['name'] = $true['name'];
            $udata[$k]['idcard'] = $true['id'];
        }

        $this->assign('list', $udata);
        $this->assign('page', $show);

        $this->display();
    }

    /**
     * 赠送心愿豆
     */

    public function give()
    {
        $WishwallGive = M('WishwallGive');
        $user = M('User');
        $id = I('get.id');
        if ($_POST) {
            $givemon = I('post.give_mon', 0, 'int');
            $time = time();
            if ($givemon < 1) {
                $this->error('赠送心愿豆整数且不能少于1');
            }
            $data=M('user')->field('totalmon')->where('id='.$id)->find();
            $old_mon = $data['totalmon'];
            $new_mon = $givemon + $old_mon;
            $m = M();
            $m->startTrans();
            $save = M('user')->where(array('id' => $id))->data(array('totalmon' => $new_mon))->save();
            $content = '666……你的心愿上公众号了，赶紧去看看吧，小编说她要以豆相许，心愿豆子已入库！';
            $dou="恭喜你获得微心愿赠送的".$givemon."颗心愿豆";
            $this->sendMsg($id, '1', $givemon .'心愿豆', $id, '2','2','', $content);
            $this->sendMsg($id, '3', $givemon .'心愿豆', $id, '2','2','', $dou);
            $add = array(
                'user' => $id,
                'xid' => $id,
                'give_mon' => $givemon,
                'give_admin' => $_SESSION['adminid'],
                'created_at' => time()
            );
            $addGiveMon = $WishwallGive->data($add)->add();
            if (!$save  || !$addGiveMon) {
                $m->rollback();
                $this->error('赠送失败');
                exit;
            } else {
                $m->commit();
                $data=array(
                  'uid'=>$id,
                    'type'=>9,
                    'wishType'=>2,
                  'tkid'=>0,
                'mon'=>$givemon,
                'xid'=>0,
                'pay_type'=>0,
                    'posttime'=>time(),
                    'is_finish'=>1,
                    'out_trade_no'=>0,
                );
                M('bill')->data($data)->add();
                $this->success('赠送成功');
            }
        } else {
            $user=M('user')->field('nickname')->where('id='.$id)->find();
            $data=M('WishwallGive')->field('sum(give_mon) as counts')->where('user='.$id)->find();
            $this->assign('data', $data);
            $this->assign('user', $user);
            $this->display();
        }
    }

}