<?php

namespace Admin\Controller;

class WithdrawalsController extends AdminController
{

    //申请列表页
    public function lists()
    {
        $Withdrawals = M('Withdrawals');
        $User = M('User');

        if (IS_GET) {
            $get = I('get.');
            $chk_time = I('chk_time');
            if (!empty($get['status'])) {
                if ($get['status'] == 5) {
                    $get['status'] = 0;
                }
                $where = array('status' => $get['status']);
            }
            if (!empty($get['card'])) {
                $where = array('card' => $get['card']);
            }
            if (!empty($chk_time)) {
                $gap = explode('-', $chk_time);   //将有 - 的进行分开
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['app_time'] = array('between', "$begin,$end");
            }
        } else {
            $where = array();
        }
        $count = $Withdrawals->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Withdrawals->order('id desc')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('data', $xydata);
        $this->display();
    }


    //审核操作页
    public function edit()
    {
        $Withdrawals = M('Withdrawals');
        $Admin = M('Admin');
        $User = M('User');
        $Message = M('Message');
        $Bill = M('Bill');

        $admin = $Admin->where(array('id' => session('adminid')))->find();
        if (IS_POST) {
            $check = I('post.status', 0, 'int');
            $id = I('post.id');
            if ($check == 1) {
                $xy = $Withdrawals->find($id);
                $des = '亲爱的用户，你的心愿豆赎回申请已经通过审核了，我们将在7个工作日内对你的银行账户进行转账处理';
                $this->sendMsg($xy['uid'], '1', '', '', '1', '', '', $des);
                $Withdrawals->create();
                $Withdrawals->status = $check;
                $Withdrawals->chk_time = time();
                $Withdrawals->chk_admin = $admin['username'];
                $Withdrawals->save() ? $this->success('操作成功', U('Admin/Withdrawals/transfer')) : $this->error('操作成功');
            } elseif ($check == 4) {
                $xy = $Withdrawals->find($id);
                $des = '亲爱的用户，你的心愿赎回申请不通过！快去检查下哪一步出了问题';
                $this->sendMsg($xy['uid'], '1', '', '', '1', '', '赎回心愿豆', $des);
                $username = $User->find($xy['uid']);
                $mon = $xy['mon'] * 10.5;
                $userid = $username['id'];
                $totalmon = $username['totalmon'];
                $totalmon = $totalmon + $mon;
                $U = $User->where("id = $userid")->setField('totalmon', $totalmon);
                $bill = array(
                    'uid' => $userid,
                    'type' => 5,
                    'mon' => $mon,
                    'posttime' => time(),
                    'dotime' => time(),
                    'is_finish' => 1,
                );
                $bb = $Bill->data($bill)->add();  //加入账单表

                $Withdrawals->create();
                $Withdrawals->status = $check;
                $Withdrawals->chk_time = time();
                $Withdrawals->chk_admin = $admin['username'];
                $Withdrawals->save() ? $this->success('操作成功', U('Admin/Withdrawals/reject')) : $this->error('修改失败');
            }
        } else {
            $id = I('get.id');
            $xydata = $Withdrawals->find($id);
            $this->assign('data', $xydata);
            $this->display();
        }
    }

    //转账操作页
    public function traedit()
    {
        $Withdrawals = M('Withdrawals');
        $Admin = M('Admin');
        $User = M('User');
        $Message = M('Message');
        $Bill = M('Bill');

        $admin = $Admin->where(array('id' => session('adminid')))->find();

        if (IS_POST) {
            $check = I('post.status', 0, 'int');
            $id = I('post.id');
            if ($check == 2) {
                $xy = $Withdrawals->field('uid')->find($id);
                $des = '心愿豆赎回成功！还不赶快去查查银行余额';
                $this->sendMsg($xy['uid'], '1', '', '', '1', '', '', $des);

                $Withdrawals->create();
                $Withdrawals->status = $check;
                $Withdrawals->tra_time = time();
                $Withdrawals->chk_admin = $admin['username'];
                $Withdrawals->save() ? $this->success('操作成功', U('Admin/Withdrawals/transfers')) : $this->error('修改失败');
            } elseif ($check == 3) {
                $xy = $Withdrawals->find($id);
                $des = '亲爱的用户，你的心愿赎回转账失败！快去检查下哪一步出了问题';
                $this->sendMsg($xy['uid'], '1', '', '', '1', '', '赎回心愿豆', $des);
                $username = $User->find($xy['uid']);
                $mon = $xy['mon'] * 10.5;
                $userid = $username['id'];
                $totalmon = $username['totalmon'];
                $totalmon = $totalmon + $mon;
                $U = $User->where("id = $userid")->setField('totalmon', $totalmon);
                $bill = array(
                    'uid' => $userid,
                    'type' => 6,
                    'mon' => $mon,
                    'posttime' => time(),
                    'dotime' => time(),
                    'is_finish' => 1,
                );
                $bb = $Bill->data($bill)->add();  //加入账单表

                $Withdrawals->create();
                $Withdrawals->status = $check;
                $Withdrawals->tra_time = time();
                $Withdrawals->chk_admin = $admin['username'];
                $Withdrawals->save() ? $this->success('操作成功', U('Admin/Withdrawals/transferf')) : $this->error('修改失败');
            }
        } else {
            $id = I('get.id');
            $xydata = $Withdrawals->find($id);
            $this->assign('data', $xydata);
            $this->display();
        }
    }

    //所有申请详情页
    public function traeditsf()
    {
        $Withdrawals = M('Withdrawals');
        $Admin = M('Admin');
        $User = M('User');
        $Message = M('Message');
        $Bill = M('Bill');

        $admin = $Admin->where(array('id' => session('adminid')))->find();
        if (IS_POST) {
            $check = I('post.status', 0, 'int');
            $id = I('post.id');

            switch ($check) {
                case "1":
                    $xy = $Withdrawals->find($id);
                    $des = '亲爱的用户，你的心愿豆赎回申请已经通过审核了，我们将在7个工作日内对你的银行账户进行转账处理';
                    $this->sendMsg($xy['uid'], '1', '', '', '1', '', '', $des);
                    $Withdrawals->create();
                    $Withdrawals->status = $check;
                    $Withdrawals->chk_time = time();
                    $Withdrawals->chk_admin = $admin['username'];
                    $Withdrawals->save() ? $this->success('操作成功', U('Admin/Withdrawals/transfer')) : $this->error('修改失败');
                    break;
                case "2":
                    $xy = $Withdrawals->find($id);
                    $des = '心愿豆赎回成功！还不赶快去查查银行余额，顺便去实现你的心愿！';
                    $this->sendMsg($xy['uid'], '1', '', '', '1', '', '', $des);
                    $Withdrawals->create();
                    $Withdrawals->status = $check;
                    $Withdrawals->tra_time = time();
                    $Withdrawals->chk_admin = $admin['username'];
                    $Withdrawals->save() ? $this->success('操作成功', U('Admin/Withdrawals/transfers')) : $this->error('修改失败');
                    break;
                case "3":
                    $xy = $Withdrawals->find($id);
                    $des = '心愿豆赎回转账失败！快去检查下哪一步出了问题。';
                    $this->sendMsg($xy['uid'], '1', '', '', '1', '', '赎回心愿豆', $des);
                    $username = $User->find($xy['uid']);
                    $mon = $xy['mon'] * 10.5;
                    $userid = $username['id'];
                    $totalmon = $username['totalmon'];
                    $totalmon = $totalmon + $mon;
                    $U = $User->where("id = $userid")->setField('totalmon', $totalmon);
                    $bill = array(
                        'uid' => $userid,
                        'type' => 6,
                        'mon' => $mon,
                        'posttime' => time(),
                        'dotime' => time(),
                        'is_finish' => 1,
                    );
                    $bb = $Bill->data($bill)->add();  //加入账单表
                    $Withdrawals->create();
                    $Withdrawals->status = $check;
                    $Withdrawals->tra_time = time();
                    $Withdrawals->chk_admin = $admin['username'];
                    $Withdrawals->save() ? $this->success('操作成功', U('Admin/Withdrawals/transferf')) : $this->error('修改失败');
                    break;
                case "4":
                    $xy = $Withdrawals->field('uid')->find($id);
                    $des = '心愿豆赎回申请失败！快去检查下哪一步出了问题。';
                    $this->sendMsg($xy['uid'], '1', '', '', '1', '', '赎回心愿豆', $des);
                    $username = $User->find($xy['uid']);
                    $mon = $xy['mon'] * 10.5;
                    $userid = $username['id'];
                    $totalmon = $username['totalmon'];
                    $totalmon = $totalmon + $mon;
                    $U = $User->where("id = $userid")->setField('totalmon', $totalmon);
                    $bill = array(
                        'uid' => $userid,
                        'type' => 5,
                        'mon' => $mon,
                        'posttime' => time(),
                        'dotime' => time(),
                        'is_finish' => 1,
                    );
                    $bb = $Bill->data($bill)->add();  //加入账单表
                    $Withdrawals->create();
                    $Withdrawals->status = $check;
                    $Withdrawals->chk_time = time();
                    $Withdrawals->chk_admin = $admin['username'];
                    $Withdrawals->save() ? $this->success('操作成功', U('Admin/Withdrawals/reject')) : $this->error('修改失败');
                    break;
                default:
                    echo "这页面不需要操作";
            }
        }
        $id = I('get.id');
        $xydata = $Withdrawals->find($id);
        $this->assign('data', $xydata);
        $this->display();
    }

    //待审核列表
    public function pendingaudit()
    {
        $Withdrawals = M('Withdrawals');
        $User = M('User');

        if ($_GET) {
            $get = I('get.');
            $chk_time = I('chk_time');
            if (!empty($get['card'])) {
                $where = array('card' => $get['card']);
            }
            if (!empty($chk_time)) {
                $gap = explode('-', $chk_time);   //将有 - 的进行分开
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['app_time'] = array('between', "$begin,$end");
            }
        }
        if ($where == NULL) {
            $where['status'] = 0;
        }
        $count = $Withdrawals->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Withdrawals->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('page', $show);
        $this->assign('data', $xydata);


        $this->display();
    }

    //驳回列表   
    public function reject()
    {
        $Withdrawals = M('Withdrawals');
        $User = M('User');
        if ($_GET) {
            $get = I('get.');
            $chk_time = I('chk_time');
            if (!empty($get['card'])) {
                $where = array('card' => $get['card']);
            }
            if (!empty($chk_time)) {
                $gap = explode('-', $chk_time);   //将有 - 的进行分开
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['chk_time'] = array('between', "$begin,$end");
            }
        }
        if ($where == NULL) {
            $where['status'] = 4;
        }
        $count = $Withdrawals->order('id desc')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Withdrawals->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('page', $show);
        $this->assign('data', $xydata);

        $this->display();
    }

    //待转账列表
    public function transfer()
    {
        $Withdrawals = M('Withdrawals');
        $User = M('User');

        if ($_GET) {
            $get = I('get.');
            $chk_time = I('chk_time');
            if (!empty($get['card'])) {
                $where = array('card' => $get['card']);
            }
            if (!empty($chk_time)) {
                $gap = explode('-', $chk_time);   //将有 - 的进行分开
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['chk_time'] = array('between', "$begin,$end");
            }
        }
        if ($where == NULL) {
            $where['status'] = 1;
        }
        $count = $Withdrawals->order('id desc')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Withdrawals->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('data', $xydata);

        $this->display();
    }

    //转账成功列表
    public function transfers()
    {
        $Withdrawals = M('Withdrawals');
        $User = M('User');

        if ($_GET) {
            $get = I('get.');
            $chk_time = I('chk_time');
            if (!empty($get['card'])) {
                $where = array('card' => $get['card']);
            }
            if (!empty($chk_time)) {
                $gap = explode('-', $chk_time);   //将有 - 的进行分开
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['chk_time'] = array('between', "$begin,$end");
            }
        }
        if ($where == NULL) {
            $where['status'] = 2;
        }
        $count = $Withdrawals->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Withdrawals->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();


        $this->assign('page', $show);
        $this->assign('data', $xydata);

        $this->display();
    }

    //转账失败列表
    public function transferf()
    {
        $Withdrawals = M('Withdrawals');
        $User = M('User');
        if ($_GET) {
            $get = I('get.');
            $chk_time = I('chk_time');
            if (!empty($get['card'])) {
                $where = array('card' => $get['card']);
            }
            if (!empty($chk_time)) {
                $gap = explode('-', $chk_time);   //将有 - 的进行分开
                $begin = strtotime($gap[0]);      //开始时间
                $end = strtotime($gap[1]);        //结束时间
                $where['chk_time'] = array('between', "$begin,$end");
            }
        }
        if ($where == NULL) {
            $where['status'] = 3;
        }

        $count = $Withdrawals->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Withdrawals->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();


        $this->assign('page', $show);
        $this->assign('data', $xydata);

        $this->display();
    }

    //ajax审核 
    public function test()
    {

        $Withdrawals = M('Withdrawals');
        $Admin = M('Admin');
        $User = M('User');
        $Message = M('Message');
        $Bill = M('Bill');
        $admin = $Admin->where(array('id' => session('adminid')))->find();

        if (IS_POST) {
            $check = I('post.status', 0, 'int');
            $id = I('post.id');
            $Withdrawals->create();
            $Withdrawals->status = $check;
            $Withdrawals->chk_time = time();
            $Withdrawals->chk_admin = $admin['username'];
            $Withdrawals->save();

            if ($check == 1) {
                $xy = $Withdrawals->field('uid')->find($id);
                $des = '亲爱的用户，你的心愿豆赎回申请已经通过审核了，我们将在7个工作日内对你的银行账户进行转账处理';
                $this->sendMsg($xy['uid'], '1', '', '', '1', '', '', $des);

            } elseif ($check == 4) {
                $xy = $Withdrawals->field('uid')->find($id);
                $des = '心愿豆赎回申请失败！快去检查下哪一步出了问题。';
                $this->sendMsg($xy['uid'], '1', '', '', '1', '', '赎回心愿豆', $des);
                $username = $User->where(array('id' => $xy['uid']))->find();
                $xydata = $Withdrawals->find($id);
                $mon = $xydata['mon'] * 10.5;
                $userid = $username['id'];
                $totalmon = $username['totalmon'];
                $totalmon = $totalmon + $mon;
                $U = $User->where("id = $userid")->setField('totalmon', $totalmon);
                $bill = array(
                    'uid' => $userid,
                    'type' => 5,
                    'mon' => $mon,
                    'posttime' => time(),
                    'is_finish' => 1,
                );
                $bb = $Bill->data($bill)->add();  //加入账单表
            }

            if ($Withdrawals) {
                echo 1;
            } else {
                echo 2;
            }
        }
    }

    //ajax转账 
    public function test1()
    {

        $Withdrawals = M('Withdrawals');
        $Admin = M('Admin');
        $User = M('User');
        $Message = M('Message');
        $Bill = M('Bill');
        $admin = $Admin->where(array('id' => session('adminid')))->find();
        if (IS_POST) {
            $check = I('post.status', 0, 'int');
            $id = I('post.id');
            $Withdrawals->create();
            $Withdrawals->status = $check;
            $Withdrawals->tra_time = time();
            $Withdrawals->chk_admin = $admin['username'];
            $Withdrawals->save();
            if ($check == 2) {
                $xy = $Withdrawals->find($id);
                $des = '心愿豆赎回成功！还不赶快去查查银行余额，顺便去实现你的心愿！';
                $this->sendMsg($xy['uid'], '1', '', '', '1', '', '', $des);
            } elseif ($check == 3) {
                $xy = $Withdrawals->find($id);
                $des = '心愿豆赎回转账失败！快去检查下哪一步出了问题。';
                $this->sendMsg($xy['uid'], '1', '', '', '1', '', '赎回心愿豆', $des);
                $username = $User->where('id')->find($xy['uid']);
                $mon = $xy['mon'] * 10.5;
                $userid = $username['id'];
                $totalmon = $username['totalmon'];
                $totalmon = $totalmon + $mon;
                $U = $User->where("id = $userid")->setField('totalmon', $totalmon);
                $bill = array(
                    'uid' => $userid,
                    'type' => 6,
                    'mon' => $mon,
                    'posttime' => time(),
                    'dotime' => time(),
                    'is_finish' => 1,
                );
                $bb = $Bill->data($bill)->add();  //加入账单表
            }
            if ($Withdrawals) {
                echo 1;
            } else {
                echo 2;
            }
        }
    }


    public function bankLists()
    {
        $bank = M('bankname');
        $count = $bank->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $bank->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('data', $xydata);
        $this->display();
    }

    public function addBank()
    {
        $bank = M('bankname');
        if ($_POST) {
            $bankname = $_POST['bankname'];
            if (empty($bankname) || empty($_FILES)) {
                $this->error('请填写完信息');
            }
            if (!empty($_FILES['img']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/image/bankIcon/'; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');
                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $file_url = $info['img']['savepath'] . $info['img']['savename'];
                    $img_url = get_url('/Uploads/image/bankIcon/' . $file_url);
                }
            }
            $data = array(
                'bankname' => $bankname,
                'img' => $img_url,
                'created_at' => time(),
            );
            $bank->data($data)->add() ? $this->success('添加成功', U('Withdrawals/bankLists')) : $this->error('添加失败');

        } else {
            $this->display();
        }

    }

    public function editBank()
    {
        $bank = M('bankname');

        if ($_POST) {
            $bankname = $_POST['bankname'];
            if (empty($bankname)) {
                $this->error('信息不完整');
            }
            if (!empty($_FILES['img']['name'])) {
                $upload = new \Think\Upload(); // 实例化上传类
                $upload->maxSize = 1 * 1024 * 1024; // 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
                $upload->rootPath = './Uploads/image/bankIcon/'; // 设置附件上传根目录
                $upload->subName = array('date', 'Ymd');
                !is_dir($upload->rootPath) && @mkdir($upload->rootPath, 0777, true); //创建文件夹
                $info = $upload->upload();
                if (!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                    exit;
                } else {
                    $file_url = $info['img']['savepath'] . $info['img']['savename'];
                    $img_url = get_url('/Uploads/image/bankIcon/' . $file_url);
                }
            }
            $data = array(
                'id' => $_POST['id'],
                'bankname' => $bankname,
                'updated_at' => time(),
            );
            $bankdata = $bank->find($_POST['id']);
            if (!empty($img_url)) {
                $data['img'] = $img_url;
            } else {
                $data['img'] = $bankdata['img'];
            }
            $bank->data($data)->save() ? $this->success('修改成功', U('Withdrawals/bankLists')) : $this->error('修改失败');
        } else {
            $id = I('get.id');
            $data = $bank->find($id);
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function deletebank()
    {
        $bank = M('bankname');
        $id = I('post.id');
        $delete = $bank->delete($id);
        if ($delete) {
            echo 1;
            exit;
        } else {
            echo 2;
            exit;
        }
    }

}
