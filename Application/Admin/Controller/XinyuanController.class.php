<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class XinyuanController extends AdminController {
    
    public function index(){

		
		
    }
    
    //列表页
    public function lists(){
        $Wishcard = M('Wishcard');
        $Admin = M('Admin');
        $Listtype = M('Listtype');
        
        if (IS_GET){
            $get = I('get.');
            $ischeck = isset($get['chk_status']) ? $get['chk_status'] : 9 ;
            $status = isset($get['status']) ? $get['status'] : 9 ;
            if ($ischeck != 9){
                $where = array('chk_status' => $ischeck);
            }elseif ($status != 9){
                $where = array('status' => $status,'chk_status' => 1);
            }
            if(isset($get['keyword'])){
                $keywords=$get['keyword'];
                $where['title'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                $this->assign('title', $keywords);
            }
        }else {
            $where = array();
        }
        
        $order = array(
            'hot_sort' => 'desc',
            'recom_sort' => 'desc',
            'jx_sort' => 'desc',
            'id' => 'desc',
        );
        $count = $Wishcard->order('id desc')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Wishcard->order($order)->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach ($xydata as $k=>$v){
            $ltdata = $Listtype->field('name')->find($v['tid']);
            $xydata[$k]['tid'] = $ltdata['name'];
        }
        $this->assign('page', $show);
        $this->assign('data', $xydata);
        
        $this->display();
    }
  /**
   * 已达成心愿表
   * outh jomlz
   */  
       
    public function Reach(){
        $Wishcard = M('Wishcard');
        $Admin = M('Admin');
        $Listtype = M('Listtype');        
        
        $where = array('status' =>1,);
        $count = $Wishcard->order('id desc')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Wishcard->order('id desc')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
          foreach ($xydata as $k=>$v){
            $ltdata = $Listtype->field('name')->find($v['tid']);
            $xydata[$k]['tid'] = $ltdata['name'];
        }
        
        $this->assign('page', $show);
        $this->assign('data', $xydata);
        
        $this->display();
    } 
    
    //未达成心愿列表
    public function notReach(){
        $Wishcard = M('Wishcard');
        $Admin = M('Admin');
        $Listtype = M('Listtype');
        
        $where = array('status' => 2);
        $count = $Wishcard->order('id desc')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xydata = $Wishcard->order('id desc')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($xydata as $k=>$v){
            $ltdata = $Listtype->field('name')->find($v['tid']);
            $xydata[$k]['tid'] = $ltdata['name'];
        }
        
        $this->assign('page', $show);
        $this->assign('data', $xydata);
        
        $this->display();
    }
    //已达成心愿的所有参与人员
    public function chkReh(){
        $Wishcard = M('Wishcard');
        $WishcardJoin = M('WishcardJoin');
        $Listtype = M('Listtype');
        
        $get = I('get.');
        $xyid = isset($get['xyid']) ? $get['xyid'] : 0 ;
        
        $where = array('xid' => $xyid, 'is_involve' => 1);    //要有参与金额并且没退心元才能查找出来
        $count = $WishcardJoin->order('id desc')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xyList = $WishcardJoin->order('id desc')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($xyList as $k=>$v){
            $xy = $Wishcard->field('tid,status,is_finish')->find($v['xid']);
            $xyList[$k]['type'] = $this->get_xytype_name($xy['tid']);
            $xyList[$k]['status'] = $xy['status'];
            $xyList[$k]['is_finish'] = $xy['is_finish'];
        }
        $this->assign('page', $show);
        $this->assign('data', $xyList);
        
        $this->display();
    }
    
    
    
    //未达成心愿的所有参与人员
    public function chkNotReh(){
        $Wishcard = M('Wishcard');
        $WishcardJoin = M('WishcardJoin');
        $Listtype = M('Listtype');
        
        $get = I('get.');
        $xyid = isset($get['xyid']) ? $get['xyid'] : 0 ;
        
        $where = array('xid' => $xyid, 'is_involve' => 1);    //要有参与金额并且没退心元才能查找出来
        $count = $WishcardJoin->order('id desc')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $xyList = $WishcardJoin->order('id desc')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        
        foreach ($xyList as $k=>$v){
            $xy = $Wishcard->field('tid,status')->find($v['xid']);
            
            $xyList[$k]['type'] = $this->get_xytype_name($xy['tid']);
            $xyList[$k]['status'] = $xy['status'];
        }
        
        $this->assign('page', $show);
        $this->assign('data', $xyList);
        
        $this->display();
    }
    //完成心愿且给发布者添加心愿豆
    public function complete(){
      $User = M('User');
      $WishcardJoin = M('WishcardJoin');
      $Wishcard = M('Wishcard');
      
      $xid = I('get.xid');
      $complete = $Wishcard->field('set_user,have_mon,set_mon')->find($xid);
      $old_mon = $User->field('totalmon')->where(array('id' =>$complete['set_user']))->find();
      $remon =array('totalmon' => $old_mon['totalmon']+$complete['set_mon']);
      $res = $User->where(array('id' => $complete['set_user']))->data($remon)->save();
      $where = array('is_finish'=>1,'get_mon' =>$complete['set_mon']);
      $db = $Wishcard ->where(array('id' =>$xid))->data($where)->save();
      if(empty($db) && ($res)){
         $this->error('操作失败');exit; 
      }else{
          $this->success('操作成功',U('Xinyuan/Reach'));exit;
      }
    }
    
    
    
    //退还心元
    public function refund(){
        $User = M('User');
        $WishcardJoin = M('WishcardJoin');
        $Wishcard = M('Wishcard');
        
        $xid = I('get.xid');
        $count = count($xid);
        if ($count == 1){    //如果只有一个xid就执行单个
            $refund = $WishcardJoin->field('invo_uid,invo_mon,xid')->find($xid);
            $old_mon = $User->field('totalmon')->where(array('id' => $refund['invo_uid']))->find();
            $remon = array(
                'totalmon' => $old_mon['totalmon']+$refund['invo_mon'],
            );
            $res = $User->where(array('id' => $refund['invo_uid']))->data($remon)->save();
            if ($res){
                $flag = 1;
                $WishcardJoin->where(array('id' => $xid))->data(array('is_refund' => 1))->save();
                //查询是否还有未退款的单，没有则把父单标记为全部退还完毕
                $existnum = $WishcardJoin->where(array('xid' => $refund['xid'],'is_refund'=>0,'is_involve'=>1))->count();
                
                if ($existnum == 0){
                    $Wishcard->where(array('id' => $refund['xid']))->data(array('is_refund' => 1))->save();
                }
            }else {
                $flag = 0;
            }
        }else {     //如果多个传过来xid就执行这里。批量操作
            for ($i=0;$i<$count;$i++){
                $refund = $WishcardJoin->field('invo_uid,invo_mon,xid')->find($xid[$i]);
                $old_mon = $User->field('totalmon')->where(array('id' => $refund['invo_uid']))->find();
                $remon = array(
                    'totalmon' => $old_mon['totalmon']+$refund['invo_mon'],
                );
                $res = $User->where(array('id' => $refund['invo_uid']))->data($remon)->save();
                if ($res){
                    $flag = 1;
                    $WishcardJoin->where(array('id' => $xid[$i]))->data(array('is_refund' => 1))->save();
                    //查询是否还有未退款的单，没有则把父单标记为全部退还完毕
                    $existnum = $WishcardJoin->where(array('xid' => $refund['xid'],'is_refund'=>0,'is_involve'=>1))->count();
                    if ($existnum == 0){
                        $Wishcard->where(array('id' => $refund['xid']))->data(array('is_refund' => 1))->save();
                    }
                }else {
                    $flag = 0;
                }
            }
        }        
        
        if($flag == 1){
            $this->success('退还成功');
        }else {
            $this->error('退还心元失败！请重新退还！');
        }
    }
    
    
    //修改
    public function edit(){
        $Xinyuan = M('Wishcard');
        $Admin = M('Admin');
        $Listtype = M('Listtype');
        $Message = M('Message');
        $Img = M('Img');
        $MyTask = M('Myevdtask');
        
        $admin = $Admin->where(array('id' => session('adminid')))->find();
        if(IS_POST){
            $check = I('post.chk_status',0,'int');
            $jx = I('post.is_jx',0,'int');
            $id = I('post.id');
            $query = $Xinyuan->where(array('id' => $id))->find();
            $Xinyuan->create();

            if ($check == 2){
                 $Xinyuan->chk_status = 2;
                 $Xinyuan->chk_time = time();
                 $Xinyuan->chk_admin = $admin['username'];
                 $des = '尊敬的用户，你的心愿审核不通过，请前往|我的心愿|栏目查看修改再发布。';
                 $this->sendMsg($query['set_user'],'','','UserCenter/index.html','1',$des);
            }
            
            if($query['is_jx'] == 0){
               if($jx == 1){
                $Xinyuan->jx_time = time();
                $Xinyuan->is_jx = 1;
               $des = '鬼知道经历了什么，你的心愿被我们加入|精选|了！';
               $this->sendMsg($query['set_user'], '','','Index/choice.html','1',$des);
               }
             }
            if($query['chk_status'] == 0 || $query['chk_status'] == 2){
                if ($check == 1){
                 $Xinyuan->chk_status = 1;
                 $des = '尊敬的用户，你的心愿已通过审核，请前往|我的心愿|栏目查看。';
                 $this->sendMsg($query['set_user'],'','','UserCenter/index.html','1',$des);
                 $Xinyuan->chk_time = time();
                 $Xinyuan->chk_admin = $admin['username']; 
                 //审核通过  检测用户发布的心愿的条数，判断是否完成特殊任务
                 $num = $Xinyuan->where(array('set_user' => $query['set_user'], 'chk_status' => 1))->count();
                 $evdtask = $MyTask->where(array('is_special' => 1, 'uid' => $query['set_user']))->select();
                if ($evdtask){
                    if ($num >= 1){
                        $this->dealTask($evdtask, 1);
                    }
                    if ($num >= 3){
                        $this->dealTask($evdtask, 10);
                    }
                    if ($num >= 5){
                        $this->dealTask($evdtask, 11);
                    }
                 }
                }
              }

            $Xinyuan->chk_time = time();
            $Xinyuan->chk_admin = $admin['username'];
            $Xinyuan->save()?$this->success('修改成功'):$this->error('修改失败');exit;
        }else{
            $id = I('get.id');
            $xydata = $Xinyuan->find($id);
            $ldata = $Listtype->field('name')->find($xydata['tid']);
            $xydata['tid'] = $ldata['name'];
            $iid = explode(',', $xydata['iid']);
            
            $listimg = array();
            foreach ($iid as $k=>$v){
                $img = $Img->find($v);
                array_push($listimg, $img);
            }
            
//             $mode=M('news_cate');
//             $data = $mode->select();
//             $wcate = new Category();
//             $catedata = $wcate->unlimitedForLevel($data, '--', 0, 0);
            $this->assign('list',$listimg);
            $this->assign('data',$xydata);
            $this->display();
        }
    }
    
    //删除
    public function del(){
        $Wishcard = M('Wishcard');
        $WishcardJoin = M('WishcardJoin');
        
        $xyl = $WishcardJoin->select();
        $id = I('post.id',0,'int');
        if($id>0){
            $WishcardJoin->where(array('xid' => $id))->delete();
            $result = $Wishcard->delete($id);
            if($result){
                //成功
                echo 1;
            }else {
                //失败
                echo 2;
            }
        }
        
    }
    
    //检测是否超过4条置顶
    public function chkTop(){
        $Wishcard = M('Wishcard');
        
        if (IS_POST){
            $type = I('post.type',0,'int');
            //1为热门，2为推荐，3为精选
            if ($type == 1){
                $num = $Wishcard->where(array('is_hot_top' => 1))->count();
                if ($num < 4){
                    $this->ajaxReturn(1);  //可选择
                }else {
                    $this->ajaxReturn(0);
                }
            }elseif ($type == 2){
                $num = $Wishcard->where(array('is_recom_top' => 1))->count();
                if ($num < 4){
                    $this->ajaxReturn(1);  //可选择
                }else {
                    $this->ajaxReturn(0);
                }
            }elseif ($type == 3){
                $num = $Wishcard->where(array('is_jx_top' => 1))->count();
                if ($num < 4){
                    $this->ajaxReturn(1);  //可选择
                }else {
                    $this->ajaxReturn(0);
                }
            }
        }
    }
    
    


    
}