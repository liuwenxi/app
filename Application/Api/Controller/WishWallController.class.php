<?php

namespace Api\Controller;

use Common\Controller\BaseController;
use Lib\Api\Weixinapi\WeiXin;

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
    public function publicKeyword()
    {
        $datas = M('Wishwall_keyword')->field('id,keyword')->order('type')->select();
        foreach ($datas as $key => $val) {
            $datas[$key]['kid'] = (int)$val['id'];
            unset($datas[$key]['id']);
        }
        Json(0, 'data', $datas);

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
            Json(1, 'errcode', 2, '信息不完整');
            exit;
        }
        $is_user = $User->where(array('id' => $uid))->find();
        if (!$is_user) {
            Json(1, 'errcode', 3, '没有该用户');
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
            Json(1, 'errcode', 4, '发布失败');
        } else {
            $m->commit();
            Json(0, 'wishId', $add, '发布成功');
        }
    }

    /**
     * 心愿墙首页
     */

    public function newlight()
    {
        /*************************************************************最新心愿*******************************************************************************/
        $uid = I('get.uid', '', int);
        //分页
        $page = I('get.page', '', int);
        $page = $page < 1 ? 1 : $page;
        $pageSize = 10;
        $pre = ($page - 1) * $pageSize;
        $count = M('Wishwall')->field('max(posttime) as posttime,set_user as uid')->where('type=0')->order('posttime desc')->group('set_user')->select();
        $countPage = (int)ceil(count($count) / $pageSize);
        $newListIds = M('Wishwall')->field('max(posttime) as posttime,set_user as uid')->limit($pre, $pageSize)->order('posttime desc')->group('set_user')->select();
        //查找背景颜色
        $bgcolor=M('bgcolor')->field('color')->limit(30,3)->select();

        foreach ($newListIds as $key => $val) {
            $newlight[] = M('Wishwall')->where(array('set_user' => $val['uid'], 'posttime' => $val['posttime']))->field('id,content,light_count,background,set_user,kid,is_top,posttime')->find();
        }

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach ($newlight as $key => $val) {
            //用户信息
            $userInfo = M('User')->field('nickname,avatar,gender')->where('id=' . $val['set_user'])->find();
            //背景信息
            $bg = M('wishwall_background')->field('font_color,large_img')->where('id=' . $val['background'])->find();

            $count = M('Comment')->where('xid=' . $val['id'])->count();
            //是否点亮该条心愿
            if ($uid) {
                $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $uid))->find();
                if ($islight) {
                    $newlight[$key]['isLight'] = TRUE;
                } else {
                    $newlight[$key]['isLight'] = FALSE;
                }
            } else {
                $newlight[$key]['isLight'] = FALSE;
            }
            //判断是否是秘室
            $subclass = M('Wishwall_keyword')->field('subclass')->where('id=' . $val['kid'])->find();
            if ($subclass['subclass'] == '0') {
                $newlight[$key]['nickName'] = $userInfo['nickname'];
                $newlight[$key]['avatar'] = $userInfo['avatar'];
            } else {
                $font = mb_substr($userInfo['nickname'], 0, 1, 'utf-8');
                $avatar = get_url("/Uploads/userAvatar/avatar.png");
                $newlight[$key]['nickName'] = $font . '**';
                $newlight[$key]['avatar'] = $avatar;
            }

            $newlight[$key]['fontColor'] = $bg['font_color'];
            $newlight[$key]['bgImg'] = $bg['large_img'];
            $newlight[$key]['background'] = (int)$val['background'];
            $newlight[$key]['gender'] = $userInfo['gender'];
            $newlight[$key]['uid'] = $val['set_user'];
            $newlight[$key]['lightCount'] = $val['light_count'];
            $newlight[$key]['posttime'] = $val['posttime'];
            $newlight[$key]['commentCount'] = (int)$count;
            $newlight[$key]['kid'] = $val['kid'];
            $newlight[$key]['id'] = (int)$val['id'];
            if($key%3== 0){
                $newlight[$key]['bgColor']=$bgcolor[0];
            }else if($key%3==1){
                $newlight[$key]['bgColor']=$bgcolor[1];
            }else if($key%3 == 2){
                $newlight[$key]['bgColor']=$bgcolor[2];
            }
            unset($newlight[$key]['light_count']);
            unset($newlight[$key]['set_user']);
            unset($newlight[$key]['is_top']);
        }
        //微信js配置接口信息
        $WeiChat = new WeiXin();
        $JsConfig = $WeiChat->getSignPackage();
        $results['jsConfig'] = $JsConfig;
        $results['newestWish']['totalPage'] = (int)$countPage;
        $results['newestWish']['wish'] = $newlight;
        Json(0, 'data', $results);
        /********************************************************************************************************************************************/

    }

    public function wishWallIndex()
    {
        //用户id
        $uid = I('GET.uid', '', int);
        /*************************************************************最亮心愿*******************************************************************************/
        $where['sort']=array('neq',0);
        $where['is_top']=array('eq',1);
        $wishall = M('Wishwall')->field('id,content,light_count,background,set_user,kid,is_top,posttime')->where($map)->limit(10)->order('sort asc')->select();

        $bgcolor=M('bgcolor')->field('color')->limit(30,3)->select();

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach ($wishall as $key => $val) {

            //用户信息
            $userInfo = M('User')->field('nickname,avatar,gender')->where('id=' . $val['set_user'])->find();
            //背景字体
            $color = M('wishwall_background')->field('font_color')->where('id=' . $val['background'])->find();
            //心愿评论总人数
            $count = M('Comment')->where('xid=' . $val['id'])->count();
            //是否点亮该条心愿
            if ($uid) {
                $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $uid))->find();
                if ($islight) {
                    $wishall[$key]['isLight'] = TRUE;
                } else {
                    $wishall[$key]['isLight'] = FALSE;
                }
            } else {
                $wishall[$key]['isLight'] = FALSE;
            }
            //判断是否是秘室
            $subclass = M('Wishwall_keyword')->field('subclass')->where('id=' . $val['kid'])->find();
            if ($subclass['subclass'] == '0') {
                $wishall[$key]['nickName'] = $userInfo['nickname'];
                $wishall[$key]['avatar'] = $userInfo['avatar'];
            } else {
                $font = mb_substr($userInfo['nickname'], 0, 1, 'utf-8');
                $avatar = get_url("/Uploads/userAvatar/avatar.png");
                $wishall[$key]['nickName'] = $font . '**';
                $wishall[$key]['avatar'] = $avatar;
            }
            $wishall[$key]['color'] = $color['font_color'];
            $wishall[$key]['gender'] = $userInfo['gender'];
            $wishall[$key]['background'] = $val['background'];
            $wishall[$key]['uid'] = $val['set_user'];
            $wishall[$key]['lightCount'] = $val['light_count'];
            $wishall[$key]['commentCount'] = (int)$count;
            $wishall[$key]['kid'] = (int)$val['kid'];
            $wishall[$key]['id'] = (int)$val['id'];
            $wishall[$key]['top'] = (int)$key + 1;
            if($key%3== 0){
                $wishall[$key]['bgColor']=$bgcolor[0]['color'];
            }else if($key%3==1){
                $wishall[$key]['bgColor']=$bgcolor[1]['color'];
            }else if($key%3 == 2){
                $wishall[$key]['bgColor']=$bgcolor[2]['color'];
            }
            unset($wishall[$key]['light_count']);
            unset($wishall[$key]['set_user']);
            unset($wishall[$key]['is_top']);
        }
        /*************************************************************最亮心愿*******************************************************************************/

        /*************************************************************心愿列表*******************************************************************************/
        //常规

        $normal = M('Wishwall_keyword')->field('id,keyword,centre_img,bg_color')->where('type=1')->order('sort asc')->limit(3)->select();


        foreach ($normal as $key => $val) {

            $arr = M('wishwall')->field('set_user')->where('kid=' . $val['id'])->group('set_user')->select();
            $count = count($arr);
            $normal[$key]['count'] = (int)$count;
            $normal[$key]['kid'] = (int)$val['id'];
            $normal[$key]['bgColor'] = $val['bg_color'];
            $normal[$key]['img'] = $val['centre_img'];
            unset($normal[$key]['bg_color']);
            unset($normal[$key]['centre_img']);
            unset($normal[$key]['id']);

        }

        //活动
        $activity = M('Wishwall_keyword')->field('id,keyword,centre_img,bg_color')->where('type=0')->order('sort asc')->limit(3)->select();
        foreach ($activity as $key => $val) {
            $arr = M('wishwall')->field('set_user')->where('kid=' . $val['id'])->group('set_user')->select();
            $count = count($arr);
            $activity[$key]['count'] = (int)$count;
            $activity[$key]['kid'] = $val['id'];
            $activity[$key]['bgColor'] = $val['bg_color'];
            $activity[$key]['img'] = $val['centre_img'];
            unset($activity[$key]['bg_color']);
            unset($activity[$key]['centre_img']);
            unset($activity[$key]['id']);
        }
        $map['kid'] = array('neq', 1);
        /*************************************************************最新心愿*******************************************************************************/
        $count = M('Wishwall')->field('max(posttime) as posttime,set_user as uid')->where($map)->order('posttime desc')->group('set_user')->select();
        $countPage = ceil(count($count) / 10);
        $newListIds = M('Wishwall')->field('max(posttime) as posttime,set_user as uid')->where($map)->limit(10)->order('posttime desc')->group('set_user')->select();
        foreach ($newListIds as $key => $val) {
            $newlight[] = M('Wishwall')->where(array('set_user' => $val['uid'], 'posttime' => $val['posttime']))->field('id,content,light_count,background,set_user,kid,is_top,posttime')->find();
        }

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach ($newlight as $key => $val) {
            //用户信息
            $userInfo = M('User')->field('nickname,avatar,gender')->where('id=' . $val['set_user'])->find();
            //背景信息
            $bg = M('wishwall_background')->field('font_color,large_img')->where('id=' . $val['background'])->find();

            $count = M('Comment')->where('xid=' . $val['id'])->count();
            //是否点亮该条心愿
            if ($uid) {
                $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $uid))->find();
                if ($islight) {
                    $newlight[$key]['isLight'] = TRUE;
                } else {
                    $newlight[$key]['isLight'] = FALSE;
                }
            } else {
                $newlight[$key]['isLight'] = FALSE;
            }
            //判断是否是秘室
            $subclass = M('Wishwall_keyword')->field('subclass')->where('id=' . $val['kid'])->find();
            if ($subclass['subclass'] == '0') {
                $newlight[$key]['nickName'] = $userInfo['nickname'];
                $newlight[$key]['avatar'] = $userInfo['avatar'];
            } else {
                $font = mb_substr($userInfo['nickname'], 0, 1, 'utf-8');
                $avatar = get_url("/Uploads/userAvatar/avatar.png");
                $newlight[$key]['nickName'] = $font . '**';
                $newlight[$key]['avatar'] = $avatar;
            }

            $newlight[$key]['fontColor'] = $bg['font_color'];
            $newlight[$key]['bgImg'] = $bg['large_img'];
            $newlight[$key]['background'] = $val['background'];
            $newlight[$key]['gender'] = $userInfo['gender'];
            $newlight[$key]['uid'] = $val['set_user'];
            $newlight[$key]['lightCount'] = (int)$val['light_count'];
            $newlight[$key]['posttime'] = (int)$val['posttime'];
            $newlight[$key]['commentCount'] = (int)$count;
            $newlight[$key]['kid'] = (int)$val['kid'];
            $newlight[$key]['id'] = (int)$val['id'];
            if($key%3== 0){
                $newlight[$key]['bgColor']=$bgcolor[0]['color'];
            }else if($key%3==1){
                $newlight[$key]['bgColor']=$bgcolor[1]['color'];
            }else if($key%3 == 2){
                $newlight[$key]['bgColor']=$bgcolor[2]['color'];
            }

            unset($newlight[$key]['light_count']);
            unset($newlight[$key]['set_user']);
            unset($newlight[$key]['is_top']);
        }

        //微信js配置接口信息
        $WeiChat = new WeiXin();
        $JsConfig = $WeiChat->getSignPackage();
        $results['jsConfig'] = $JsConfig;
        $results['lightestWish'] = $wishall;
        $results['thishot']['normal'] = $normal;
        $results['thishot']['activity'] = $activity;
        $results['newestWish']['wish'] = $newlight;
        $results['newestWish'] ['totalPage'] = (int)$countPage;
        Json(0, 'data', $results);

    }

    /**
     * 所有的关键字
     */

    public function allshot()
    {
        //typeID为0表示活动，1表示类别
        $activity = M('Wishwall_keyword')->field('id,keyword,big_img,bg_color')->where('type=0')->select();
        foreach ($activity as $key => $val) {
            $arr = M('wishwall')->field('set_user')->where('kid=' . $val['id'])->group('set_user')->select();
            $count = count($arr);
            $activity[$key]['count'] = (int)$count;
            $activity[$key]['kid'] = (int)$val['id'];
            $activity[$key]['bgColor'] = $val['bg_color'];
            $activity[$key]['img'] = $val['big_img'];
            unset($activity[$key]['bg_color']);
            unset($activity[$key]['id']);
        }
        $normal = M('Wishwall_keyword')->field('id,keyword,big_img,bg_color')->where('type=1')->select();
        foreach ($normal as $key => $val) {
            $arr = M('wishwall')->field('set_user')->where('kid=' . $val['id'])->group('set_user')->select();
            $count = count($arr);
            $normal[$key]['count'] = (int)$count;
            $normal[$key]['kid'] = (int)$val['id'];
            $normal[$key]['bgColor'] = $val['bg_color'];
            $normal[$key]['img'] = $val['big_img'];
            unset($normal[$key]['bg_color']);
            unset($normal[$key]['id']);
        }


        $results['activity'] = $activity;
        $results['normal'] = $normal;
        Json(0, 'data', $results);

    }

    /**
     * 分类心愿列表
     */
    public function ClassWishList()
    {
        $uid = I('get.uid', '', 'int');
        $kid = I('get.kid', '', 'int');

        $bgcolor=M('bgcolor')->field('color')->limit(30,3)->select();
        if (!$kid) {
            Json(1, 'errcode', 1, '参数不完整');
        }

        /*************************心愿详情**************************************/
        $wishkey = M('Wishwall_keyword')->field('id,keyword,small_img,content,details,subclass')->where('id=' . $kid)->find();
        $wishkey['count'] = (int)M('Wishwall')->where('kid=' . $wishkey['id'])->count();
        $wishkey['kid'] = (int)$wishkey['id'];
        $wishkey['img'] =$wishkey['small_img'];
        unset($wishkey['id']);
        unset($wishkey['small_img']);
        /****************************************************************************/

        /*******************************最新参与心愿的20个用户***********************/
        $count = M('Wishwall')->field('max(posttime) as posttime,set_user as uid')->where('kid=' . $kid)->order('posttime desc')->group('set_user')->select();
        $countPage = (int)ceil(count($count) / 20);
        $newListIds = M('Wishwall')->field('max(posttime) as posttime,set_user as uid')->where('kid=' . $kid)->order('posttime desc')->limit(20)->group('set_user')->select();
        foreach ($newListIds as $key => $val) {
            $newlight[] = M('Wishwall')->where(array('set_user' => $val['uid'], 'posttime' => $val['posttime']))->field('id,content,light_count,background,set_user,kid,is_top,posttime')->find();

        }

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach ($newlight as $key => $val) {
            //用户信息
            $userInfo = M('User')->field('nickname,avatar,gender')->where('id=' . $val['set_user'])->find();
            //是否点亮该条心愿
            if ($uid) {
                $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $uid))->find();
                if ($islight) {
                    $newlight[$key]['isLight'] = TRUE;
                } else {
                    $newlight[$key]['isLight'] = FALSE;
                }
            } else {
                $newlight[$key]['isLight'] = FALSE;
            }

            //是否是无秘室，如果是用户头像会默认
            if ($wishkey['subclass'] == '0') {
                $newlight[$key]['nickName'] = $userInfo['nickname'];
                $newlight[$key]['avatar'] = $userInfo['avatar'];
            } else {
                $font = mb_substr($userInfo['nickname'], 0, 1, 'utf-8');
                $avatar = get_url("/Uploads/userAvatar/avatar.png");
                $newlight[$key]['nickName'] = $font . '**';
                $newlight[$key]['avatar'] = $avatar;
            }
            $countcomment = M('Comment')->where('xid=' . $val['id'])->count();
            $newlight[$key]['gender'] = $userInfo['gender'];
            $newlight[$key]['uid'] = $val['set_user'];
            $newlight[$key]['lightCount'] = $val['light_count'];
            $newlight[$key]['commentCount'] = (int)$countcomment;
            $newlight[$key]['gender'] = $val['gender'];
            $newlight[$key]['kid'] = $val['kid'];
            $newlight[$key]['id'] = $val['id'];
            $newlight[$key]['posttime'] = (int)$val['posttime'];
            if($key%3== 0){
                $newlight[$key]['bgColor']=$bgcolor[0]['color'];
            }else if($key%3==1){
                $newlight[$key]['bgColor']=$bgcolor[1]['color'];
            }else if($key%3 == 2){
                $newlight[$key]['bgColor']=$bgcolor[2]['color'];
            }
            unset($newlight[$key]['light_count']);
            unset($newlight[$key]['read_count']);
            unset($newlight[$key]['gender']);
            unset($newlight[$key]['set_user']);
            unset($newlight[$key]['is_top']);
        }
        /***************************************************************************/
        //微信js配置接口信息
        $WeiChat = new WeiXin();
        $JsConfig = $WeiChat->getSignPackage();
        $results['jsConfig'] = $JsConfig;
        $results['wishKey'] = $wishkey;
        $results['newestWish']['totalPage'] = $countPage;
        $results['newestWish']['wish'] = $newlight;
        Json(0, 'data', $results);
    }

    /**
     * 分类心愿分页
     */
    public function ClassWishListpage()
    {
        $uid = I('get.uid', '', int);
        $kid = I('get.kid', '', int);

        if (!$kid) {
            Json(1, 'error', 1, '参数不完整');
        }
        $page = I('get.page', '', int);
        $page = $page < 1 ? 1 : $page;


        $bgcolor=M('bgcolor')->field('color')->limit(30,3)->select();
        /*if($page%3 == 1){
              $bgcolor=array_slice($color,0,20);
        }else if($page%3 ==2 ){
              $a=array_slice($color,20,30);
              $b=array_slice($color,0,10);
              $bgcolor=array_merge($a,$b);
        }else if($page%3==0){
             $bgcolor=array_slice($color,10,30);
        }*/

        $pageSize = 20;
        $pre = ($page - 1) * $pageSize;

        $count = M('Wishwall')->field('max(posttime) as posttime,set_user as uid')->where('kid=' . $kid)->order('posttime desc')->group('set_user')->select();

        $countPage = (int)ceil(count($count) / $pageSize);
        /*******************************最新参与心愿的20个用户***********************/
        $newListIds = M('Wishwall')->field('max(posttime) as posttime,set_user as uid')->where('kid=' . $kid)->limit($pre, $pageSize)->order('posttime desc')->group('set_user')->select();

        foreach ($newListIds as $key => $val) {
            $newlight[] = M('Wishwall')->where(array('set_user' => $val['uid'], 'posttime' => $val['posttime']))->field('id,content,light_count,background,set_user,kid,is_top,posttime')->find();

        }

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach ($newlight as $key => $val) {
            //用户信息
            $userInfo = M('User')->field('nickname,avatar,gender')->where('id=' . $val['set_user'])->find();
            //是否点亮该条心愿
            if ($uid) {
                $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $uid))->find();
                if ($islight) {
                    $newlight[$key]['isLight'] = TRUE;
                } else {
                    $newlight[$key]['isLight'] = FALSE;
                }
            } else {
                $newlight[$key]['isLight'] = FALSE;
            }

            //是否是无秘室，如果是用户头像会默认
            if ($kid == 1) {
                $font = mb_substr($userInfo['nickname'], 0, 1, 'utf-8');
                $avatar = get_url("/Uploads/userAvatar/avatar.png");
                $newlight[$key]['nickName'] = $font . '**';
                $newlight[$key]['avatar'] = $avatar;
            } else {
                $newlight[$key]['nickName'] = $userInfo['nickname'];
                $newlight[$key]['avatar'] = $userInfo['avatar'];
            }
            $countcomment = M('Comment')->where('xid=' . $val['id'])->count();
            $newlight[$key]['gender'] = $userInfo['gender'];
            $newlight[$key]['uid'] = $val['set_user'];
            $newlight[$key]['lightCount'] = $val['light_count'];
            $newlight[$key]['commentCount'] = (int)$countcomment;
            $newlight[$key]['gender'] = $val['gender'];
            $newlight[$key]['kid'] = $val['kid'];
            $newlight[$key]['id'] = $val['id'];
            $newlight[$key]['posttime'] = (int)$val['posttime'];
            if($key%3== 0){
                $newlight[$key]['bgColor']=$bgcolor[0]['color'];
            }else if($key%3==1){
                $newlight[$key]['bgColor']=$bgcolor[1]['color'];
            }else if($key%3 == 2){
                $newlight[$key]['bgColor']=$bgcolor[2]['color'];
            }
            unset($newlight[$key]['light_count']);
            unset($newlight[$key]['read_count']);
            unset($newlight[$key]['gender']);
            unset($newlight[$key]['set_user']);
            unset($newlight[$key]['is_top']);
        }

        /***************************************************************************/

        $results['newestWish']['totalPage'] = (int)$countPage;
        $results['newestWish']['wish'] = $newlight;
        Json(0, 'data', $results);

    }

    /**
     * 点亮
     */
    public function light()
    {
        $User = M('User');
        $Wishwall = M('Wishwall');
        $Join = M('WishwallJoin');

        $uid = I('post.uid', '', 'int');
        $wish_id = I('post.wishId', '', 'int');


        if (!$uid || !$wish_id) {
            Json(1, 'errcode', 1, '参数不完整');
        }

        $user = $User->where(array('id' => $uid))->find();
        if (!$user) {
            Json(1, 'errcode', 2, '没有该用户');
        }

        $is_wish = $Wishwall->field('kid,set_user,id')->where(array('id' => $wish_id))->find();
        if (!$is_wish) {
            Json(1, 'errcode', 3, '该心愿不存在');
        }

        $is_join = $Join->where(array('join_uid' => $user['id'], 'wish_id' => $wish_id))->find();
        if ($is_join) {
            Json(1, 'errcode', 4, '你已经点亮过了');
        }
        $data['wish_id'] = $wish_id;
        $data['join_uid'] = $user['id'];
        $data['status'] = 1;
        $data['join_time'] = time();
        $data['kid'] = $is_wish['kid'];
        $m = M();
        $m->startTrans();
        if (!$Join->data($data)->add()) {
            $m->rollback();
            Json(1, 'errcode', 5, '更新数据失败1');
        }
        $content = '你心愿墙的心愿又亮瞎了个无辜，赶紧去查看点亮数';
        $Message = $this->sendMsg($is_wish['set_user'], '1', '', $is_wish['id'], '2', '2', '', $content);
        if (!$Wishwall->where(array('id' => $is_wish['id']))->setInc('light_count') || !$Message) {
            $m->rollback();
            Json(1, 'errcode', 6, '更新数据失败2');
        } else {
            $m->commit();
            Json(0, 'data', 0, '点亮成功');
        }
    }

    //背景信息
    public function background()
    {
        $background = M('WishwallBackground');
        $data = $background->field('id,font_color,large_img,small_img')->where('')->select();
        foreach ($data as $key => $val) {
            $data[$key]['id'] = $val['id'];
            $data[$key]['fontColor'] = $val['font_color'];
            $data[$key]['largeImg'] = $val['large_img'];
            $data[$key]['smallImg'] = $val['small_img'];
            unset($data[$key]['font_color']);
            unset($data[$key]['large_img']);
            unset($data[$key]['small_img']);
        }
        Json(0, 'data', $data);
    }

//发表评论
    public function comment()
    {
        //用户id
        $data['pub_user'] = I('post.uid', '', 'int');
        //评论内容
        $data['pub_des'] = I('post.comment', '', 'htmlspecialchars');
        //心愿id
        $data['xid'] = I('post.wishId', '', 'int');
        //评论时间
        $data['pub_time'] = time();

        if (!$data['pub_user'] || !$data['pub_des'] || !$data['xid']) {
            Json(1, 'errcode', 1, '参数不完整');
        }

        //用户信息
        $userInfo = M('User')->field('gender')->where('id=' . $data['pub_user'])->find();

        if (!$userInfo) {
            Json(1, 'errcode', 2, '没有该用户');
        }
        $data['gender'] = $userInfo['gender'];

        //心愿信息
        $xid = M('Wishwall')->field('id')->where('id=' . $data['xid'])->find();
        if (!$xid) {
            Json(1, 'errcode', 3, '没有该心愿');
        }


        $info = M('Comment')->data($data)->add();
        if ($info) {
            Json(0, 'data', 0, '评论成功');
        } else {
            Json(1, 'errcode', 4, '评论失败');
        }


    }

    //评论详情页面
    public function detail()
    {
        //心愿Id
        $wishid = I('get.wishId', '', 'int');
        $url = I('get.url');
//        $url = "http://m.17wxy.com/#/detail/1212";

        //评论页数
        $page = I('get.page', '', int);
        if (!$page) {
            $page = 0;
        }
        if (!$wishid) {
            Json(1, 'errcode', 2, '参数不完整');
        }
        //心愿信息
        $datas = M('wishwall')->where(array('id' => $wishid))->field('id,content,light_count,background,set_user,kid,posttime')->find();
        if (!$datas) {
            Json(1, 'errcode', 3, '没有该心愿');
        }

        //用户信息
        $userInfo = M('User')->where(array('id' => $datas['set_user']))->find();
        //背景信息
        $ground = M('WishwallBackground')->field('font_color,large_img')->where(array('id' => $datas['background']))->find();
        //评论总人数
        $count = M('Comment')->where('xid=' . $datas['id'])->count();
        //判断是否是无秘室
        $subclass = M('Wishwall_keyword')->field('subclass')->where('id=' . $datas['kid'])->find();


        if ($subclass['subclass'] == '0') {
            $datas['nickName'] = $userInfo['nickname'];
            $datas['avatar'] = $userInfo['avatar'];
        } else {
            $font = mb_substr($userInfo['nickname'], 0, 1, 'utf-8');
            $avatar = get_url("/Uploads/userAvatar/avatar.png");
            $datas['nickName'] = $font . '**';
            $datas['avatar'] = $avatar;
        }
        $datas['bgImg'] = $ground['large_img'];
        $datas['fontColor'] = $ground['font_color'];
        $datas['gender'] = $userInfo['gender'];
        $datas['uid'] = (int)$datas['set_user'];
        $datas['lightCount'] = $datas['light_count'];
        $datas['commentCount'] = (int)$count;
        $datas['kid'] = $datas['kid'];
        $datas['id'] = $datas['id'];
        $datas['posttime'] = (int)$datas['posttime'];
        $datas['background'] = $datas['background'];
        unset($datas['light_count']);
        unset($datas['read_count']);

        unset($datas['set_user']);

        //心愿评论信息
        $count = M('Comment')->field('pub_des,pub_user,pub_time')->where('type=0 and xid=' . $wishid)->order('pub_time desc')->count();
        $countPage = (int)ceil($count / 10);
        $info = M('Comment')->field('pub_des,pub_user,pub_time,id')->where('type=0 and xid=' . $wishid)->limit(10)->order('pub_time desc')->select();

        foreach ($info as $key => $val) {
            //评论用户信息
            //判断用户是否存在
            $userinfo = M('User')->field('nickname,avatar,gender')->where('id=' . $val['pub_user'])->find();

            if ($subclass['subclass'] == '0') {
                $info[$key]['nickName'] = $userinfo['nickname'];
                $info[$key]['avatar'] = $userinfo['avatar'];
            } else {
                $font = mb_substr($userinfo['nickname'], 0, 1, 'utf-8');
                $avatar = get_url("/Uploads/userAvatar/avatar.png");
                $info[$key]['nickName'] = $font . '**';
                $info[$key]['avatar'] = $avatar;
            }
            $info[$key]['uid'] = $val['pub_user'];
            $info[$key]['comment'] = $val['pub_des'];
            $info[$key]['posttime'] = (int)$val['pub_time'];
            $info[$key]['gender'] = $userinfo['gender'];
            unset($info[$key]['pub_user']);
            unset($info[$key]['pub_des']);
            unset($info[$key]['pub_time']);
        }


        //微信js配置接口信息
        $WeiChat = new WeiXin($url);
        $JsConfig = $WeiChat->getSignPackage();
        $results['jsConfig'] = $JsConfig;
        $results['wish'] = $datas;
        $results['commentList']['comment'] = $info;
        $results['commentList']['totalPage'] = $countPage;
        Json(0, 'data', $results);


    }

    //评论详情页面分页加载

    public function detailpage()
    {
        //心愿Id
        $wishid = I('get.wishId', '', 'int');
        //评论页数
        $page = I('get.page', '', int);
        $page = $page < 1 ? 1 : $page;
        $pageSize = 10;
        $pre = ($page - 1) * $pageSize;
        if (!$wishid) {
            Json(1, 'errcode', 2, '参数不完整');
        }
        $count = M('Comment')->field('pub_des,pub_user,pub_time')->where('type=0 and xid=' . $wishid)->order('pub_time desc')->count();
        $countPage = (int)ceil($count / $pageSize);

        $info = M('Comment')->field('pub_des,pub_user,pub_time,id')->where('type=0 and xid=' . $wishid)->limit($pre, $pageSize)->order('pub_time desc')->select();

        foreach ($info as $key => $val) {
            //评论用户信息
            //判断用户是否存在
            $userinfo = M('User')->field('nickname,avatar,gender')->where('id=' . $val['pub_user'])->find();
            if ($wishid == 1) {
                $font = mb_substr($userinfo['nickname'], 0, 1, 'utf-8');
                $avatar = get_url("/Uploads/userAvatar/avatar.png");
                $info[$key]['nickName'] = $font . '**';
                $info[$key]['avatar'] = $avatar;
            } else {
                $info[$key]['nickName'] = $userinfo['nickname'];
                $info[$key]['avatar'] = $userinfo['avatar'];
            }
            $info[$key]['uid'] = $val['pub_user'];
            $info[$key]['comment'] = $val['pub_des'];
            $info[$key]['posttime'] = (int)$val['pub_time'];
            $info[$key]['gender'] = $userinfo['gender'];
            unset($info[$key]['pub_user']);
            unset($info[$key]['pub_des']);
            unset($info[$key]['pub_time']);
        }
        $results['commentList']['totalPage'] = $countPage;
        $results['commentList']['comment'] = $info;
        Json(0, 'data', $results);
    }

    public function hotShare()
    {
        $Wishwall = M('Wishwall');
        $WishShare = M('Wishshare');
        $get = I('get.');
        if (empty($get)) {
            Json(1, 'errcode', 1, '参数有误');
        }
        $wish = $Wishwall->field('id,kid')->where(array('id' => $get['wishId']))->find();
        if (!$wish) {
            Json(1, 'errcode', 2, '参数有误');
        }
        $data['wish_id'] = $wish['id'];
        $data['kid'] = $wish['kid'];
        $data['create_at'] = time();
        $id = $WishShare->add($data);
        if (!$id) {
            Json(1, 'errcode', '3', '添加记录出错');
        }
        Json(0, 'logId', $id, '分享成功');
    }

}
