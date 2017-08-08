<?php
namespace Admin\Controller;

use Admin\Controller\AdminController;

/**
 * 投诉管理
 * @package Admin\Controller
 */
class ComplainController extends AdminController
{
    public function user()   //用户投诉
    {
        $user = M('User');
        $img = M('Img');
        $UserComplain = M('UserComplain');
        $wishhelp = M('Wishhelp');
        $wishwall = M('Wishwall');
        $wishcard = M('Wishcard');
        if ($_GET) {
            $get = I('get.');
            if (!empty($get['state'])) {
                if ($get['state'] == 3) {
                    $where['state'] = 0;
                } else {
                    $where['state'] = $get['state'];
                }
            }
            if (!empty($get['time'])) {
                if ($get['time'] == 1) {
                    $order = 'create_time desc';
                } elseif ($get['time'] == 2) {
                    $order = 'create_time asc';
                } elseif ($get['time'] == 3) {
                    $order = 'aid_time desc';
                } elseif ($get['time'] == 4) {
                    $order = 'aid_time asc';
                }
            }
            if (!empty($get['identity'])) {

            }
//            if (!empty($post['keyword'])) {
//                $keywords = $post['keyword'];
//                $where['uid'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
//            }

        } else {
            $where = array();
        }
        $listnum = 10;  //每页显示的数据量
        $count = $UserComplain->where($where)->count();
        $Page = new \Think\Page($count, $listnum);
        $show = $Page->show();
        $udata = $UserComplain->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($udata as $k => $v) {
            if ($v['type'] == 1) {                 //心愿帮的心愿
                $helpid = $wishhelp->find($v['xid']);
                $udata[$k]['xid'] = $helpid['title'];
            } elseif ($v['type'] == 2) {           //心愿墙的心愿
                $wallid = $wishwall->find($v['xid']);
                $udata[$k]['xid'] = $wallid['title'];
            } elseif ($v['type'] == 3) {           //心愿汇的心愿
                $cardid = $wishcard->find($v['xid']);
                $udata[$k]['xid'] = $cardid['title'];
            }
            $arr = explode(',', $v['imgs']);
            foreach ($arr as $kk => $vv) {
                $imgs = $img->where(array('id' => $vv['imgs']))->find();
                $udata[$k]['imgss'][]['img'] = $imgs['pic'];
            }
            $informant = $user->find($v['uid']);
            $informer = $user->find($v['ruid']);
            $udata[$k]['informant'] = $informant['nickname'];
            $udata[$k]['informer'] = $informer['nickname'];
        }
        $this->assign('list', $udata);
        $this->assign('page', $show);

        $this->display();
    }

    /**
     * 用户投诉详情
     */
    public function user_details()
    {
        $user = M('User');
        $img = M('Img');
        $UserComplain = M('UserComplain');
        $wishhelp = M('Wishhelp');
        $wishwall = M('Wishwall');
        $wishcard = M('Wishcard');
        $id = I('get.id', 0, 'int');

        $data['data'] = $UserComplain->find($id);

        if ($data['data']['type'] == 1) {                 //心愿帮的心愿
            $helpid = $wishhelp->find($data['data']['xid']);
            $data['data']['xid'] = $helpid['title'];
        } elseif ($data['data']['type'] == 2) {           //心愿墙的心愿
            $wallid = $wishwall->find($data['data']['xid']);
            $data['data']['xid'] = $wallid['title'];
        } elseif ($data['data']['type'] == 3) {           //心愿汇的心愿
            $cardid = $wishcard->find($data['data']['xid']);
            $data['data']['xid'] = $cardid['title'];
        }

        $informant = $user->find($data['data']['uid']);
        $informer = $user->find($data['data']['ruid']);
        $data['informant'] = $informant['nickname'];
        $data['$informer'] = $informer['nickname'];



        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 投诉审核
     */
    public function report_audit()
    {
        $user = M('User');
        $UserComplain = M('UserComplain');
        $ContenComplain = M('ContenComplain');

        $id = I('post.id', '', 'int');
        $class = I('post.class', '', 'int');
        $type = I('post.type', '', 'int');
        $aid = session('adminid');

        if ($_POST) {
            $user_review = $UserComplain->find($id);
            if($class == 1){
                if ($type == 1) {  //通过
                    $save = $UserComplain->where(array('id' => $id))->data(array('state' => 1, 'aid' => $aid, 'aid_time' => time()))->save();
                    if ($save) {
                        $content = '替天行道，为你点赞。你的举报已通过';
                        $Message = $this->sendMsg($user_review['uid'], '1', '', '', '1','','', $content);
                        echo 1;
                        exit;
                    } else {
                        echo 2;
                        exit;
                    }
                }
                if ($type == 2) { //驳回
                    $save = $UserComplain->where(array('id' => $id))->data(array('state' => 2, 'aid' => $aid, 'aid_time' => time()))->save();
                    if ($save) {
                        $content = '做人给人留点余地，日后好相处。你的举报不通过';
                        $Message = $this->sendMsg($user_review['uid'], '1', '', '', '1','','', $content);
                        echo 1;
                        exit;
                    } else {
                        echo 2;
                        exit;
                    }
                }
            }elseif($class == 2){
                $conten_review = $ContenComplain->find($id);
                if ($type == 1) {  //通过
                    $save = $ContenComplain->where(array('id' => $id))->data(array('state' => 1, 'aid' => $aid, 'aid_time' => time()))->save();
                    if ($save) {
                        $content = '替天行道，为你点赞。你的举报已通过';
                        $Message = $this->sendMsg($user_review['uid'], '1', '', '', '1','','', $content);
                        echo 1;
                        exit;
                    } else {
                        echo 2;
                        exit;
                    }
                }
                if ($type == 2) { //驳回
                    $save = $ContenComplain->where(array('id' => $id))->data(array('state' => 2, 'aid' => $aid, 'aid_time' => time()))->save();
                    if ($save) {
                        $content = '做人给人留点余地，日后好相处。你的举报不通过';
                        $Message = $this->sendMsg($user_review['uid'], '1', '', '', '1','','', $content);
                        echo 1;
                        exit;
                    } else {
                        echo 2;
                        exit;
                    }
                }
            }

        }
    }

    /**
     * 内容投诉
     */
    public function content()
    {
        $user = M('User');
        $ContenComplain = M('ContenComplain');
        $wishhelp = M('Wishhelp');
        $wishwall = M('Wishwall');
        $wishcard = M('Wishcard');
        if ($_GET) {
            $get = I('get.');
            if (!empty($get['state'])) {
                if ($get['state'] == 3) {
                    $where['state'] = 0;
                } else {
                    $where['state'] = $get['state'];
                }
            }
            if (!empty($get['time'])) {
                if ($get['time'] == 1) {
                    $order = 'create_time desc';
                } elseif ($get['time'] == 2) {
                    $order = 'create_time asc';
                } elseif ($get['time'] == 3) {
                    $order = 'aid_time desc';
                } elseif ($get['time'] == 4) {
                    $order = 'aid_time asc';
                }
            }
            if (!empty($get['identity'])) {

            }
//            if (!empty($post['keyword'])) {
//                $keywords = $post['keyword'];
//                $where['uid'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
//            }

        } else {
            $where = array();
        }

        $listnum = 10;  //每页显示的数据量
        $count = $ContenComplain->where($where)->count();
        $Page = new \Think\Page($count, $listnum);
        $show = $Page->show();
        $udata = $ContenComplain->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($udata as $k => $v) {
            if ($v['type'] == 1) {                 //心愿帮的心愿
                $helpid = $wishhelp->find($v['xid']);
                $udata[$k]['title'] = $helpid['content'];
            } elseif ($v['type'] == 2) {           //心愿墙的心愿
                $wallid = $wishwall->find($v['xid']);
                $udata[$k]['title'] = $wallid['content'];
            } elseif ($v['type'] == 3) {           //心愿汇的心愿
                $cardid = $wishcard->find($v['xid']);
                $udata[$k]['title'] = $cardid['title'];
            }
            $informant = $user->find($v['uid']);
            $udata[$k]['informant'] = $informant['nickname'];
        }
        $this->assign('list', $udata);
        $this->assign('page', $show);
        $this->display();
    }

    /**
     * 内容投诉详情
     */
    public function content_details()
    {
        $user = M('User');
        $ContenComplain = M('ContenComplain');
        $wishhelp = M('Wishhelp');
        $wishwall = M('Wishwall');
        $wishcard = M('Wishcard');
        $id = I('get.id', 0, 'int');

        $data['data'] = $ContenComplain->find($id);

        if ($data['data']['type'] == 1) {                 //心愿帮的心愿
            $helpid = $wishhelp->find($data['data']['xid']);
            $data['data']['content'] = $helpid['content'];
        } elseif ($data['data']['type'] == 2) {           //心愿墙的心愿
            $wallid = $wishwall->find($data['data']['xid']);
            $data['data']['content'] = $wallid['content'];
        } elseif ($data['data']['type'] == 3) {           //心愿汇的心愿
            $cardid = $wishcard->find($data['data']['xid']);
            $data['data']['content'] = $cardid['content'];
        }

        $informant = $user->find($data['data']['uid']);
        $data['informant'] = $informant['nickname'];


        $this->assign('data', $data);
        $this->display();
    }


}