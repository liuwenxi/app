<?php

namespace Admin\Controller;

use Admin\Controller\CommonController;
use Lib\until\Category;

/**
 * Class WishwallController
 * @package Admin\Controller
 * @author jomlz
 */
class WishwallController extends CommentController
{

    /**
     * 心愿墙心愿列表
     */
    public function walllist()
    {
        $Wishwall = M('Wishwall');
        $WishwallCycle = M('WishwallCycle');
        $keyword = M('WishwallKeyword');
        $user = M('User');

        $WishwallCycle = $WishwallCycle->select();
        $keywords=$keyword->field('id,keyword')->select();
        $this->assign('WishwallCycle', $WishwallCycle);
        $this->assign('keywords',$keywords);
        $this->display();
    }

    /**
     * 赠送心愿豆
     */

    public function give()
    {
        $Wishwall = M('Wishwall');
        $WishwallGive = M('WishwallGive');
        $WishwallCycle = M('WishwallCycle');
        $user = M('User');
        $id = I('get.id');
        if ($_POST) {
            $givemon = I('post.give_mon', 0, 'int');
            $data = $Wishwall->find($id);
            $cycle = $WishwallCycle->find($data['cycle']);
            $wish = $Wishwall->where(array('id'=>$id))->find();
            $time = time();
            if ($time < $cycle['end_time']) {
                $this->error('还没到赠送的时间');
            }
            if ($givemon < 1) {
                $this->error('赠送心愿豆整数且不能少于1');
            }
            $give_mon = $givemon;
            $old_mon = $data['give_mon'];
            $new_mon = $give_mon + $old_mon;
            $m = M();
            $m->startTrans();
            $save = $Wishwall->where(array('id' => $id))->data(array('give_mon' => $new_mon))->save();
            $content = '不管你相不相信天上掉馅饼这回事，但是你心愿墙的心愿确实中奖了';
            $Message = $this->sendMsg($wish['set_user'], '1', $givemon .'心愿豆', $wish['id'], '2','2','', $content);
            $add = array(
                'user' => $data['set_user'],
                'xid' => $data['id'],
                'give_mon' => $give_mon,
                'give_admin' => $_SESSION['adminid'],
                'created_at' => time()
            );
            $addGiveMon = $WishwallGive->data($add)->add();
            if (!$save || !$Message || !$addGiveMon) {
                $m->rollback();
                $this->error('赠送失败');
                exit;
            } else {
                $m->commit();
                $this->success('赠送成功', U('Wishwall/walllist'));
            }
        } else {
            $data = $Wishwall->find($id);
            $user = $user->find($data['set_user']);
            $this->assign('data', $data);
            $this->assign('user', $user);
            $this->display();
        }
    }

    /**
     * 查看心愿
     */

    public function details()
    {

        if(IS_POST){
            $Wishwall = M('Wishwall');

            $id=$_POST['id'];
            $type=$_POST['type'];
            $data['light_count']=$_POST['light_count'];
            $data['type']=$type;

            $wish = $Wishwall->find($id);
            if($wish['type']==0 && $type==1){
                $content = "请注意！请注意！你有心愿被送往火星，它违反了地球心愿规则，你可以前往火星查看！";
                $this->sendMsg($wish['set_user'], '2', '', $wish['id'], '2','4','', $content);
            }

            $data['sort']=$_POST['sort'];
            $add=M('Wishwall')->where('id='.$id)->save($data);
            if($add){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else {

            $Wishwall = M('Wishwall');
            $keyword = M('WishwallKeyword');

            $user = M('User');
            $id = I('get.id');
            $data = $Wishwall->find($id);

            $keyname = $keyword->where(array('id' => $data['kid']))->find();
            $user = $user->find($data['set_user']);

            $this->assign('data', $data);
            $this->assign('user', $user);
            $this->assign('keyname', $keyname);

            $this->display();
        }
    }

    /**
     * 周期管理
     */
    public function cycle()
    {
        $WishwallCycle = M('WishwallCycle');
        $keyword = M('WishwallKeyword');

        $pagenum = 10; //每页显示记录数量
        $count = $WishwallCycle->count(); //符合查询的总数；
        $page = new \Think\Page($count, $pagenum); //实例分页类，传入总记录数 和每页显示 记录数
        $show = $page->show(); //分页显示输出
        $data = $WishwallCycle->limit($page->firstRow . ',' . $page->listRows)->select();

        foreach ($data as $k => $v) {
            $arr = explode(',', $v['kid']);
            foreach ($arr as $kk => $vv) {
                $key = $keyword->where(array('id' => $vv))->find();
                $data[$k]['seo_key'][] = $key['keyword'];
            }
        }
        foreach ($data as $key => $value) {
            $data[$key]['keyword'] = implode(',', $value['seo_key']);
        }
        //print_r($data);exit;
        $this->assign('page', $show);
        $this->assign('artlist', $data);
        $this->display();
    }


    /*
    * 添加周期
    */

    public function addcycle()
    {
        $mode = M('WishwallCycle');
        if (IS_POST) {
            $cycle = $_POST['cycle'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $kid = $_POST['keyword'];
            $countkid = count($kid);
            if (empty($cycle) || empty($start_time) || empty($end_time) || empty($kid)) {
                $this->error('信息不完整');
            }
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time);
            if ($start_time > $end_time) {
                $this->error('开始时间不能大于结束时间');
            }
            if ($end_time - $start_time != 604800) {
                $this->error('一个周期为7天');
            }
            if ($countkid < 3) {
                $this->error('关键字不能少于3个');
            }
            $maxcycle = $mode->order('end_time desc')->select();
            if ($start_time < $maxcycle[0]['end_time']) {
                $this->error('时间不能有重复');
            }
            $data = array(
                'cycle' => $cycle,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'kid' => is_array($kid) ? implode(',', $kid) : '',
                'created_at' => time(),
                'aid' => $_SESSION['adminid']
            );

            $mode->data($data)->add() ? $this->success('添加成功', U('Wishwall/cycle')) : $this->error('添加失败');
        } else {
            $keyword = M('WishwallKeyword');
            $mode = M('WishwallCycle');
            $cycle = $mode->select();
            $data = $keyword->select();

            $this->assign('data', $data);
            $this->assign('cycle', $cycle);
            $this->display();
        }
    }


    /**
     * 编辑周期
     */
    public function editcycle()
    {
        if (IS_POST) {
            $mode = M('WishwallCycle');
            $cycle = $_POST['cycle'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $kid = $_POST['keyword'];
            $countkid = count($kid);
            if (empty($cycle) || empty($start_time) || empty($end_time) || empty($kid)) {
                $this->error('信息不完整');
            }
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time);
            if ($start_time > $end_time) {
                $this->error('开始时间不能大于结束时间');
            }
            if ($end_time - $start_time != 604800) {
                $this->error('一个周期为7天');
            }
            if ($countkid < 3) {
                $this->error('关键字不能少于3个');
            }
            $where['id'] = array('neq',$_POST['id']);
            $cyclelist = $mode->where($where)->order('end_time desc')->select();
            foreach ($cyclelist as $k=>$v){
                if ($v['start_time'] < $start_time  && $start_time < $v['end_time']) {
                    $this->error('时间不能有重复');
                }
                if ($v['start_time'] < $end_time  && $end_time < $v['end_time']) {
                    $this->error('时间不能有重复');
                }
            }

            $data = array(
                'id' => $_POST['id'],
                'cycle' => $cycle,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'kid' => is_array($kid) ? implode(',', $kid) : '',
                'created_at' => time(),
                'aid' => $_SESSION['adminid']
            );

            $mode->data($data)->save() ? $this->success('修改成功', U('Wishwall/cycle')) : $this->error('修改失败');
        } else {
            $keyword = M('WishwallKeyword');
            $id = I('get.id');
            $cycle = M('WishwallCycle')->find($id);
            $cylist = M('WishwallCycle')->select();
            $data = $keyword->select();
            foreach ($data as $k => $v) {
                if ($cycle['kid']) {
                    $kid = explode(',', $cycle['kid']);
                    $data[$k]['kid'] = in_array($v['id'], $kid) ? 1 : 0;
                } else {
                    $v['kid'] = 0;
                }
            }
            $this->assign('data', $data);
            $this->assign('cycle', $cycle);
            $this->assign('cylist', $cylist);
            $this->display();
        }
    }

    /**
     * 活动管理
     */
    public function keyword()
    {
        $mode = M('WishwallKeyword');
        $pagenum = 10; //每页显示记录数量
        $count = $mode->where('type=0')->count(); //符合查询的总数；
        $page = new \Think\Page($count, $pagenum); //实例分页类，传入总记录数 和每页显示 记录数
        $show = $page->show(); //分页显示输出
        $data = $mode->limit($page->firstRow . ',' . $page->listRows)->where('type=0')->select();
        $this->assign('page', $show);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 活动是否在首页展示
     */
    public function activity(){
         $id=$_GET['id'];
         $data['reveal']=$_GET['type'];
         $info=M('Wishwall_keyword')->where('id='.$id)->save($data);

         if($info){
             $this->ajaxReturn(1);
         }else{
             $this->ajaxReturn(0);
         }

    }

    /**
     * 常规管理
     */
    public function rule(){
        $mode = M('WishwallKeyword');
        $pagenum = 10; //每页显示记录数量
        $count = $mode->where('type=1')->count(); //符合查询的总数；
        $page = new \Think\Page($count, $pagenum); //实例分页类，传入总记录数 和每页显示 记录数
        $show = $page->show(); //分页显示输出
        $data = $mode->limit($page->firstRow . ',' . $page->listRows)->where('type=1')->select();
        $this->assign('page', $show);
        $this->assign('data', $data);
        $this->display();
    }
    /**
     * 常规管理
     */
    public function ruleshow()
    {
        $id=$_GET['id'];
        $data['reveal']=$_GET['type'];

        $info=M('Wishwall_keyword')->where('id='.$id)->save($data);

        if($info){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(0);
        }
    }


    /**
     * 添加关键字
     */

    public function addkeyword()
    {
        //测试；
        $mode = M('WishwallKeyword');
        if ($_POST) {
            $keyword = $_POST['keyword'];
            $bg_color = $_POST['bg_color'];
            $content=$_POST['content'];
            $details=$_POST['details'];
            $big_img='http://php.17wxy.com'.$_POST['big_img'];
            $centre_img='http://php.17wxy.com'.$_POST['centre_img'];
            $small_img='http://php.17wxy.com'.$_POST['small_img'];
           if(empty($img)){$img='http://php.17wxy.com/Public/Admin/Images/wxy-logo.png';}
            $type=$_POST['type'];
            $sort=$_POST['sort'];
            if($_POST['subclass']){$subclass=$_POST['subclass'];}else{$subclass=0;}


            if (empty($keyword) || empty($content) || empty($bg_color)) {
                $this->error('请填写完信息');
            }

            $data = array(
                'keyword' => $keyword,
                'content'=>$content,
                'big_img' => $big_img,
                'centre_img' => $centre_img,
                'small_img' => $small_img,
                'bg_color' => $bg_color,
                'details' => $details,
                'aid' => $_SESSION['adminid'],
                'created_at' => time(),
                'type'=>$type,
                'sort'=>$sort,
                'subclass'=>$subclass,
            );

            $mode->data($data)->add() ? $this->success('添加成功') : $this->error('添加失败');

        } else {
            $type=$_GET['type'];
            $this->assign('type',$type);
            $this->display();
        }
    }

    /**
     * 编辑关键字
     */

    public function editkey()
    {
        $key = M('WishwallKeyword');
        if(IS_POST){
            $id=$_POST['id'];
            $data['keyword']=$_POST['keyword'];
            $data['bg_color']=$_POST['bg_color'];
            $res=$key->field('big_img,centre_img,small_img')->where('id='.$id)->find();
             if($res['big_img'] == $_POST['big_img'] ){
                $data['big_img']=$_POST['big_img'];
            }else{
                $data['big_img']='http://php.17wxy.com'.$_POST['big_img'];
            }
            if($res['centre_img'] == $_POST['centre_img'] ){
                $data['centre_img']=$_POST['centre_img'];
            }else{
                $data['centre_img']='http://php.17wxy.com'.$_POST['centre_img'];
            }
            if($res['small_img'] == $_POST['small_img'] ){
                $data['small_img']=$_POST['small_img'];
            }else{
                $data['small_img']='http://php.17wxy.com'.$_POST['small_img'];
            }
            $data['sort']=$_POST['sort'];
            $data['type']=$_POST['type'];
            $data['content']=$_POST['content'];
            $data['details']=$_POST['details'];
            $data['reveal']=$_POST['reveal'];
                if($_POST['subclass']){$data['subclass']=$_POST['subclass'];}else{$data['subclass']=0;}
            $info=$key->where('id='.$id)->save($data);
            if($info){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }

        }else{
            $id=I('get.id','',int);
            $data = $key->find($id);
            $this->assign('data', $data);
            $this->assign('id',$id);
            $this->display();
        }

    }

    /**
     * 删除心愿
     */

    public function delwish()
    {
        $id = I('get.id');
        $model = M('Wishwall');
        $wish = $model->where(array('id'=>$id))->find();
        $m=M();
        $m->startTrans();
        $content = "请注意！请注意！你有心愿被送往火星，它违反了地球心愿规则，你可以前往火星查看！";
        $Message = $this->sendMsg($wish['set_user'], '2', '', $wish['id'], '2','2','', $content);
        if(!$model->delete($id) || !$Message){
            $m->rollback();
            $this->error('删除失败');
        }else{
            $m->commit();
            $this->success('删除成功');
        }
    }

    /**
     * 删除周期
     */

    public function delcycle()
    {
        $id = I('get.id');
        $model = M('WishwallCycle');
        $model->delete($id) ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * 删除关键字
     */
    public function delkey()
    {
        $id = I('get.id');
        $key = M('WishwallKeyword');
        $key->delete($id) ? $this->success('删除成功') : $this->error('删除失败');

    }

    /**
     * 添加心愿
     */
    public function addwish()
    {
        $Wishwall = M('Wishwall');
        $keyword = M('WishwallKeyword');
        $background = M('WishwallBackground');
        $User = M('User');

        if ($_POST) {
            $uid1 = I('post.uid1', '', 'int');
            $uid2 = I('post.uid2', '', 'int');
            $uid3 = I('post.uid3', '', 'int');
            $uid4 = I('post.uid4', '', 'int');

            if (!empty($uid1)) {
                $uid = $uid1;
            } elseif (!empty($uid2)) {
                $uid = $uid2;
            } elseif (!empty($uid3)) {
                $uid = $uid3;
            } elseif (!empty($uid4)) {
                $uid = $uid4;
            }
            $kid = I('post.kid', '', 'int');
            $content = I('post.content');
            $posttime = I('post.posttime');
            $light_count = I('post.light_count', 0, 'int');

            $bid = I('post.background');
            if (!$uid  || !$kid || !$content) {
                $this->error('信息不完整');
            } else {
                $data = $Wishwall->create();
                $data['set_user'] = $uid;
                $data['background'] = $bid;
                $data['posttime'] = $posttime ? strtotime($posttime) : time();
                $m = M();
                $m->startTrans();
                $add = $Wishwall->add($data);
                $dynamic = addUseDynamic($uid, 1, $add, 1);
                if(!$add || !$dynamic){
                    $m->rollback();
                    $this->error('添加失败');
                }else{
                    $m->commit();
                    $this->success('添加成功');
                }
            }
        } else {
            $userlist[1] = $User->where(array('type' => 1))->select();
            $userlist[2] = $User->where(array('type' => 2))->select();
            $userlist[3] = $User->where(array('type' => 3))->select();
            $userlist[4] = $User->where(array('type' => 4))->select();
            $backgroundlist = $background->select();
            $keyword=M('wishwall_keyword')->field('id,keyword')->select();
            $this->assign('keyword',$keyword);

            $this->assign('userlist', $userlist);
            $this->assign('backgroundlist', $backgroundlist);
            $this->display();
        }

    }

    /*
   * 获取下拉关键字
   */
    public function get_keyname()
    {
        $WishwallCycle = M('WishwallCycle');
        $keyword = M('WishwallKeyword');

        $cid = I('get.cycle');
        $data = $WishwallCycle->where(array('id' => $cid))->field('kid')->select();

        foreach ($data as $k => $v) {
            $arr = explode(',', $v['kid']);
            foreach ($arr as $kk => $vv) {
                $key = $keyword->where(array('id' => $vv))->find();
                $data['keyword'][] = $key;
            }
        }
        foreach ($data['keyword'] as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['keyword']}</option>";
        exit($html);

    }

    public function ajaxlist()
    {
        $Wishwall = M('Wishwall');
        $WishwallCycle = M('WishwallCycle');
        $keyword = M('WishwallKeyword');
        $user = M('User');

        if (IS_GET) {
            $get = I('get.');
            if (!empty($get['cycle'])) {
                $where['cycle'] = $get['cycle'];
                if (!empty($get['kid'])) {
                    $where['kid'] = $get['kid'];
                }
            }
            if (!empty($get['sort'])) {
                if ($get['sort'] == 1) {
                    $order = 'posttime desc';
                } elseif ($get['sort'] == 2) {
                    $order = 'posttime asc';
                } elseif ($get['sort'] == 3) {
                    $order = 'light_count desc';
                } elseif ($get['sort'] == 4) {
                    $order = 'light_count asc';
                };
            }
            if ($_GET['kid']) {
                $where['kid']=$get['kid'];
              /*  $keywords = $get['keyword'];
                $where['title'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                $where['content'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                $where['_logic'] = 'OR';*/
            }
        } else {
            $where = array();
            $order = 'id asc';
        }
        $pagenum = 10; //每页显示记录数量
        $count = $Wishwall->where($where)->where('is_top=0')->order($order)->count(); //符合查询的总数；
        $page = new \Think\AjaxPage($count, $pagenum);
       //实例分页类，传入总记录数 和每页显示 记录数
        $show = $page->show(); //分页显示输出
        $data = $Wishwall->where($where)->where('is_top=0')->order($order)->limit($page->firstRow . ',' . $page->listRows)->select();

        foreach ($data as $k => $v) {
            $nickname = $user->find($v['set_user']);
            $cycle = $WishwallCycle->find($v['cycle']);
            $key = $keyword->where(array('id' => $v['kid']))->find();
            $data[$k]['nickname'] = $nickname['nickname'];

            $data[$k]['end_time'] = $cycle['end_time'];
            $data[$k]['keyword'] = $key['keyword'];
            $data[$k]['time'] = time();
        }

        $this->assign('page', $show);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 心愿置首
     */
    public function wishTop()
    {
        $Wishwall = M('Wishwall');
        $id = I('post.id');
        $is_top = I('post.is_top');
        $m=M();
        $m->startTrans();
        $wish = $Wishwall->where(array('id'=>$id))->find();
        $save = $Wishwall->where(array('id' => $id))->data(array('is_top' => $is_top))->save();
        $content = '最新鲜！最亮眼！最多赞！你的心愿登上每日最亮!';
        $this->sendMsg($wish['set_user'], '2', '', $wish['id'], '2','2','', $content);
        if(!$save){
            $m->rollback();
            echo 2;
        }else{
            $m->commit();
            echo 1;
        }
    }
    /**
     * 心愿评论列表
     */
      public function comment(){
           $id=$_GET['id'];
           $count=M('Comment as com')->join('wxy_user as user ON user.id=com.pub_user')->field('com.type,com.pub_des,com.pub_time,com.is_warn,com.warn_num,user.nickname,com.id')->where("com.xid=".$id)->count();
           $Page       = new \Think\Page($count,20);
           $show       = $Page->show();// 分页显示输出
           $data=M('Comment as com')->join('wxy_user as user ON user.id=com.pub_user')->field('com.type,com.pub_des,com.pub_time,com.is_warn,com.warn_num,user.nickname,com.id')->where("com.xid=".$id)->order('com.pub_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();;

           $this->assign('page',$show);// 赋值分页输出
           $this->assign('data',$data);
           $this->assign('id',$id);
           $this->display();
      }

    /**
     * 删除评论
     */
    public function    delcomment()
    {
         $id=$_GET['id'];
         $info=M('Comment')->delete($id);
         if($info){$this->success('删除成功');}else{$this->error('修改失败');}
    }

    /**
     * ajax显示或隐藏评论
     */
    public function show_hidden(){
         $id=$_GET['id'];
         $data['type']=$_GET['type'];
         $info=M('Comment')->where('id='.$id)->data($data)->save();
         if($info){
             $this->ajaxReturn(1);
         }else{
             $this->ajaxReturn(0);
         }

    }

    /**
     * 置顶列表
     */
     public function toplist(){

         $Wishwall = M('Wishwall');
         $WishwallCycle = M('WishwallCycle');
         $keyword = M('WishwallKeyword');
         $user = M('User');

         if (IS_GET) {
             $get = I('get.');
             if (!empty($get['cycle'])) {
                 $where['cycle'] = $get['cycle'];
                 if (!empty($get['kid'])) {
                     $where['kid'] = $get['kid'];
                 }
             }
             if (!empty($get['sort'])) {
                 if ($get['sort'] == 1) {
                     $order = 'posttime desc';
                 } elseif ($get['sort'] == 2) {
                     $order = 'posttime asc';
                 } elseif ($get['sort'] == 3) {
                     $order = 'light_count desc';
                 } elseif ($get['sort'] == 4) {
                     $order = 'light_count asc';
                 };
             }
             if (isset($get['keyword'])) {
                 $keywords = $get['keyword'];
                 $where['title'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                 $where['content'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                 $where['_logic'] = 'OR';
             }
         } else {
             $where = array();
             $order = 'id asc';
         }
         $pagenum = 10; //每页显示记录数量
         $count = $Wishwall->where($where)->where('is_top=1')->order($order)->count(); //符合查询的总数；
         $page = new \Think\Page($count, $pagenum);
         //实例分页类，传入总记录数 和每页显示 记录数
         $show = $page->show(); //分页显示输出
         $data = $Wishwall->where($where)->where('is_top=1')->order($order)->limit($page->firstRow . ',' . $page->listRows)->select();

         foreach ($data as $k => $v) {
             $nickname = $user->find($v['set_user']);
             $cycle = $WishwallCycle->find($v['cycle']);
             $key = $keyword->where(array('id' => $v['kid']))->find();
             $data[$k]['nickname'] = $nickname['nickname'];
             $data[$k]['end_time'] = $cycle['end_time'];
             $data[$k]['keyword'] = $key['keyword'];
             $data[$k]['time'] = time();
         }

         $this->assign('page', $show);
         $this->assign('data', $data);
         $this->display();
     }

     /**
      * 添加评论
      *
      */
     public  function  addcomment(){
         $id=$_GET['id'];
         if(IS_POST){
             $post_uid1 = I('post.post_uid1');
             $post_uid2 = I('post.post_uid2');
             $post_uid3 = I('post.post_uid3');
             $post_uid4 = I('post.post_uid4');
             $content = I('post.content');
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

             $gender=M('user')->field('gender')->where('id='.$post_uid)->find();

             if(!$post_uid || !$content){
                 $this->error('必填信息不能为空');
                 exit;
             }
              $data['pub_des']=$content;
              $data['pub_user']=$post_uid;
              $data['gender']=$gender['gender'];
              $data['xid']=$_POST['xid'];
              $data['pub_time']=time();
              $add=M('comment')->data($data)->add();
              if($add){
                  $this->success('评论成功','Wishwall/comment?id='.$data['xid']);
              }else{
                  $this->error('评论失败');
             }
         }
         $User = M('User');
         $userlist1=$User->field('id,nickname')->where('type = 1')->select();
         $userlist2=$User->field('id,nickname')->where('type = 2')->select();
         $userlist3=$User->field('id,nickname')->where('type = 3')->select();
         $userlist4=$User->field('id,nickname')->where('type = 4')->select();
         $userlist=$User->field('id,nickname')->where('type > 0')->select();

         $this->assign('userlist', $userlist);
         $this->assign('userlist1', $userlist1);
         $this->assign('userlist2', $userlist2);
         $this->assign('userlist3', $userlist3);
         $this->assign('userlist4', $userlist4);
         $this->assign('id',$id);
         $this->display();
     }
}
