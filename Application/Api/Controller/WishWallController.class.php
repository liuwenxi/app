<?php
namespace Api\Controller;
use Com\Wechat;
use Common\Controller\BaseController;

class WishWallController extends BaseController
{
    public function _initialize()
    {
        header('Content-type:text/html;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
    }



    /**
     * 发布页面的关键字
     */
    public function publicKeyword(){
        $datas=M('Wishwall_keyword')->field('id,keyword')->where('id !=4')->select();
        foreach($datas as $key=>$val){
            $datas[$key]['kid']=(int)$val['id'];
            unset($datas[$key]['id']);
        }

        $results['status'] = 0;
        $results['data'] = $datas;
        dexit($results);

    }

    /**
     * 发布心愿
     */
    public function Publish()
    {
        $User = M('User');
        $Wishwall = M('Wishwall');


        $uid = I('post.uid', '', 'int');
        $content = I('post.content');
        $kid = I('post.kid', '', 'int');
        $bid = I('post.bid', '', 'int');





        if (!$uid || !$content || !$kid) {
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '信息不完整';
            dexit($results);
            exit;
        }
        $is_user = $User->where(array('id' => $uid))->find();
        if (!$is_user) {
            $results['status'] = 1;
            $results['errcode'] = 3;
            $results['msg'] = '没有该用户';
            dexit($results);
            exit;
        }
        $data = array(
            'set_user' => $is_user['id'],
            'content' => $content,
            'kid' => $kid,
            'background' => $bid,
            'posttime' => time()
        );
        $m = M();
        $m->startTrans();
        $add = $Wishwall->data($data)->add();
        $dynamic = addUseDynamic($uid, 1, $add, 1);
        if (!$add || !$dynamic) {
            $m->rollback();
            $results['status'] = 1;
            $results['errcode'] = 4;
            $results['msg'] = '发布失败';
            dexit($results);
        } else {
            $m->commit();
            $results['status'] = 0;
            $results['msg'] = '发布成功';
            dexit($results);

        }
    }

    /**
     * 心愿墙首页
     */
    public function wishWallIndex()
    {

        /*************************************************************最亮心愿*******************************************************************************/
         
        if(S('data')){
             $wishall=S('data');
        }else{
            $wishall=M('Wishwall')->field('id,content,light_count,background,set_user,kid,is_top,posttime')->limit(20)->order('light_count desc')->select();
            S('data',$wishall,3600);
        }

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach($wishall as $key=>$val){
            //用户信息
            $userInfo=M('User')->field('nickname,avatar,gender')->where('id='.$val['set_user'])->find();
            $color=M('wishwall_background')->field('font_color')->where('id='.$val['background'])->find();
            $count=M('Comment')->where('xid='.$val['id'])->count();
            $wishall[$key]['color']=$color['font_color'];
            $wishall[$key]['nickName']=$userInfo['nickname'];
            $wishall[$key]['avatar']=$userInfo['avatar'];
            $wishall[$key]['gender']=(int)$userInfo['gender'];
            $wishall[$key]['background']=(int)$val['background'];
            $wishall[$key]['uid']=(int)$val['set_user'];
            $wishall[$key]['lightCount']=(int)$val['light_count'];
            $wishall[$key]['commentCount']=(int)$count;
            $wishall[$key]['kid']=(int)$val['kid'];
            $wishall[$key]['id']=(int)$val['id'];
            $wishall[$key]['top']=(int)$key+1;
            unset($wishall[$key]['light_count']);
            unset($wishall[$key]['set_user']);
            unset($wishall[$key]['is_top']);
        }


        /*************************************************************最亮心愿*******************************************************************************/

        /*************************************************************心愿列表*******************************************************************************/
        $thishot=M('Wishwall_keyword')->field('id,keyword,img,bg_color')->where('id BETWEEN  0 and 3')->select();
        foreach($thishot as $key=>$val){
              $thishot[$key]['count']=(int)M('Wishwall')->where('kid='.$val['id'])->count();
              $thishot[$key]['kid']=(int)$val['id'];
              $thishot[$key]['bgColor']=$val['bg_color'];
              unset($thishot[$key]['bg_color']);
              unset($thishot[$key]['id']);

        }

        /*************************************************************心愿列表*******************************************************************************/
        /*************************************************************最新心愿*******************************************************************************/

        $newlight=M('Wishwall')->field('id,content,light_count,background,set_user,kid,is_top,max(posttime) as posttime')->group('set_user')->select();


        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach($newlight as $key=>$val){
            //用户信息
            $userInfo=M('User')->field('nickname,avatar,gender')->where('id='.$val['set_user'])->find();
            $bg=M('wishwall_background')->field('font_color,large_img')->where('id='.$val['background'])->find();
            $count=M('Comment')->where('xid='.$val['id'])->count();
            $newlight[$key]['nickName']=$userInfo['nickname'];
            $newlight[$key]['fontColor']=$bg['font_color'];
            $newlight[$key]['bgImg']=$bg['large_img'];
            $newlight[$key]['background']=(int)$val['background'];
            $newlight[$key]['avatar']=$userInfo['avatar'];
            $newlight[$key]['gender']=(int)$userInfo['gender'];
            $newlight[$key]['uid']=(int)$val['set_user'];
            $newlight[$key]['lightCount']=(int)$val['light_count'];
            $newlight[$key]['posttime']=(int)$val['posttime'];
            $newlight[$key]['commentCount']=(int)$count;
            $newlight[$key]['kid']=(int)$val['kid'];
            $newlight[$key]['id']=(int)$val['id'];
            unset($newlight[$key]['light_count']);
            unset($newlight[$key]['set_user']);
            unset($newlight[$key]['is_top']);
        }

        /********************************************************************************************************************************************/

        $results['status'] = 0;
        $results['data']['lightesWish'] = $wishall;
        $results['data']['thishot'] = $thishot;
        $results['data']['newestWish'] = $newlight;
        dexit($results);exit;

    }

    /**
     * 本周热榜全部
     */

    public function allshot(){
        $User = M('User');
        $Wishwall = M('Wishwall');
        $WishwallCycle = M('WishwallCycle');
        $WishwallKeyword = M('WishwallKeyword');

        $nowtime = time();
        $time['start_time'] = array('ELT', $nowtime);
        $time['end_time'] = array('GT', $nowtime);
        $thisweek = $WishwallCycle->where($time)->find();
        $kid = explode(',', $thisweek['kid']);
        foreach ($kid as $k => $v) {
            $key = $WishwallKeyword->where(array('id' => $v))->find();
            $usercount = $Wishwall->where(array('kid' => $v,'cycle'=>$thisweek['id']))->count('DISTINCT set_user');
            $data[$k]['id'] = $key['id'];
            $data[$k]['keyword'] = $key['keyword'];
            $data[$k]['img'] = $key['img'];
            $data[$k]['bg_color'] = $key['bg_color'];
            $data[$k]['count'] = $usercount;
        }
        $count = count($data);
        $group_count = ceil($count/7);
        $datas = array_group($data,$group_count,7);

        $results['status'] = 0;
        $results['group_count'] = $group_count;
        $results['data'] = $datas;
        dexit($results);exit;
    }
    /**
     * 分类心愿列表
     */
    public function ClassWishList(){

        $kid = I('get.kid');
       // $page = I('get.page');

        if(!$kid){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不完整';
            dexit($results);
        }

        /*************************心愿详情**************************************/
        $wishkey=M('Wishwall_keyword')->field('id,keyword,img,content')->where('id='.$kid)->find();

        $wishkey['count']=(int)M('Wishwall')->where('kid='.$wishkey['id'])->count();
        $wishkey['kid']=(int)$wishkey['id'];
        unset($wishkey['id']);

      /****************************************************************************/

      /*******************************最新参与心愿的10个用户***********************/
        $newlight=M('Wishwall')->field('id,content,light_count,read_count,background,set_user,kid,is_top,max(posttime) as posttime')->where('kid='.$kid)->group('set_user')->select();
      //echo  M('Wishwall')->getLastSql();exit;
        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach($newlight as $key=>$val){
            //用户信息
            $userInfo=M('User')->field('nickname,avatar,gender')->where('id='.$val['set_user'])->find();

            $newlight[$key]['nickName']=$userInfo['nickname'];
            $newlight[$key]['avatar']=$userInfo['avatar'];
            $newlight[$key]['gender']=$userInfo['gender'];
            $newlight[$key]['uid']=(int)$val['set_user'];
            $newlight[$key]['lightCount']=(int)$val['light_count'];
            $newlight[$key]['commentCount']=(int)$val['read_count'];
            $newlight[$key]['gender']=(int)$val['gender'];
            $newlight[$key]['kid']=(int)$val['kid'];
            $newlight[$key]['id']=(int)$val['id'];
            $newlight[$key]['postTime']=(int)$val['posttime'];
            unset($newlight[$key]['light_count']);
            unset($newlight[$key]['read_count']);
            unset($newlight[$key]['gender']);
            unset($newlight[$key]['set_user']);
            unset($newlight[$key]['is_top']);
        }

      /***************************************************************************/

         $results['status'] = 0;
         $results['data'] ['wishKey']=$wishkey;
         $results['data']['newestWish'] =$newlight;

         dexit($results);

    }

    /**
     * 点亮
     */
    public function light(){
        $User = M('User');
        $Wishwall = M('Wishwall');
        $Join = M('WishwallJoin');

        $uid = I('post.uid','','int');
        $wish_id = I('post.wishId','','int');
        $status = I('post.status','','int');




        if(!$uid || !$wish_id || !$status){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不完整';
            dexit($results);
        }
        $user = $User->where(array('id'=>$uid))->find();
        if (!$user){
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '没有该用户';
            dexit($results);exit;
        }
        $is_wish = $Wishwall->where(array('id' =>$wish_id))->find();
        if(!$is_wish){
            $results['status'] = 1;
            $results['errcode'] = 3;
            $results['msg'] = '该心愿不存在';
            dexit($results);exit;
        }
        $is_join = $Join->where(array('join_uid' =>$user['id'],'wish_id'=>$wish_id))->find();
        if($is_join){
            $results['status'] = 1;
            $results['errcode'] = 4;
            $results['msg'] = '你已经点亮过了';
            dexit($results);exit;
        }
        $data['wish_id'] = $wish_id;
        $data['join_uid'] = $user['id'];
        $data['status'] = 1;
        $data['join_time'] = time();
        $m=M();
        $m->startTrans();
        if(!$Join->data($data)->add()){
            $m->rollback();
            $results['status'] = 1;
            $results['errcode'] = 5;
            $results['msg'] = '更新数据失败1';
            dexit($results);
        }
        $content = '你心愿墙的心愿又亮瞎了个无辜，赶紧去查看点亮数';
        $Message = $this->sendMsg($is_wish['set_user'], '1', '', $is_wish['id'], '2','2','', $content);
        if(!$Wishwall->where(array('id'=>$is_wish['id']))->setInc('light_count') || !$Message){
            $m->rollback();
            $results['status'] = 1;
            $results['errcode'] = 6;
            $results['msg'] = '更新数据失败2';
            dexit($results);
        }else{
            $m->commit();
            $results['status'] = 0;
            $results['msg'] = '点亮成功';
            dexit($results);
        }
    }

    //背景信息
    public function background(){
        $background = M('WishwallBackground');

        $data = $background->field('id,font_color,large_img,small_img')->select();

        foreach($data as $key=>$val){
            $data[$key]['id']=(int)$val['id'];
            $data[$key]['fontColor']=$val['font_color'];
            $data[$key]['largeImg']=$val['large_img'];
            $data[$key]['smallImg']=$val['small_img'];
            unset($data[$key]['font_color']);
            unset($data[$key]['large_img']);
            unset($data[$key]['small_img']);
        }

        $results['status'] = 0;
        $results['data'] = $data;
        dexit($results);
    }

//发表评论
   public function comment(){
        //用户id
                    $data['pub_user']=I('post.uid','','int');
       //评论内容
                    $data['pub_des']=I('post.comment','','htmlspecialchars');
       //心愿id
                    $data['xid']=I('post.wishId','','int');
       //评论时间
                    $data['pub_time']=time();

         if(!$data['pub_user'] || !$data['pub_des'] || !$data['xid']){
               $results['status']=1;
               $results['errcode'] = 1;
               $results['msg']='参数不完整';
               dexit($results);
         }
              $we=new Wechat();
       //用户信息
       $userInfo=M('User')->field('nickname')->where('id='.$data['pub_user'])->find();
         if(!$userInfo){
             $results['status']=1;
             $results['errcode'] = 2;
             $results['msg']='没有该用户';
             dexit($results);
         }


       //心愿信息
       $xid=M('Wishwall')->field('id')->where('id='.$data['xid'])->find();
         if(!$xid){
             $results['status']=1;
             $results['errcode'] = 3;
             $results['msg']='没有该心愿';
             dexit($results);
         }


       $info=M('Comment')->data($data)->add();
         if($info){
             $results['status']=0;
             $results['data']='评论成功';
             dexit($results);
         }else{
             $results['status']=1;
             $results['errcode'] = 4;
             $results['msg']='评论失败';
             dexit($results);
         }


    }

    //热门评论
    public  function  commenthot(){

       //心愿Id
             $wishid=I('get.wishId','','int');

      //心愿评论信息
             $info=M('Comment')->field('pub_des,pub_user,pub_time')->where('xid='.$wishid)->order('pub_time desc')->limit(1)->select();

             if(!$info){
                  $results['status']=1;
                  $results['errorde']=1;
                  $results['msg']='没有该心愿';
                  dexit($results);
             }


             foreach($info as $key=>$val){
                 //评论用户信息
                  $userInfo=M('User')->field('nickname,avatar,gender')->where('id='.$val['pub_user'])->find();
                   $info[$key]['uid']=(int)$val['pub_user'];
                   $info[$key]['comment']=$val['pub_des'];
                   $info[$key]['posttime']=(int)$val['pub_time'];
                   $info[$key]['nickName']=$userInfo['nickname'];
                   $info[$key]['avatar']=$userInfo['avatar'];
                   $info[$key]['gender']=(int)$userInfo['gender'];
                   unset($info[$key]['pub_user']);
                   unset($info[$key]['pub_des']);
                   unset($info[$key]['pub_time']);
             }

             $results['status']=0;
             $results['data']=$info;
             dexit($results);

    }



}
