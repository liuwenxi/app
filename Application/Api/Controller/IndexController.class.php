<?php

namespace Api\Controller;

use Common\Controller\BaseController;

/**

 * 首页控制器

 * @author kar1

 *

 */
class IndexController extends BaseController {

     public  function _initialize(){
//        parent::_initialize();
        header("Access-Control-Allow-Origin: *");
    }

    //首页推荐
    public function index(){
      /*  $User = M('User');
        $Wishwall = M('Wishwall');
        $WishwallKeyword = M('WishwallKeyword');
        $wishwalls=$Wishwall->field('count(*) as count,kid')->group('kid')->select();
        $keywords=$WishwallKeyword->field(true)->select();
        foreach ($keywords as $key => $val){
            foreach ($wishwalls as $k => $v){
                if($v['kid']==$val['id']){
                    $keywords[$key]['count']=$v['count'];
                }
            }
        }
        $users=$User->limit(0,16)->where('type > 0')->select();
        $data['hotMans']=$users;
        $data['hotSpots']=$keywords;
        dexit($data);die;*/
      $wishall='';
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
        $thishot=M('Wishwall_keyword')->field('id,keyword,img,bg_color')->where('id BETWEEN  0 and 2')->select();
        foreach($thishot as $key=>$val){
            $thishot[$key]['count']=(int)M('Wishwall')->where('kid='.$val['id'])->count();
            $thishot[$key]['kid']=(int)$val['id'];
            $thishot[$key]['bgColor']=$val['bg_color'];
            unset($thishot[$key]['bg_color']);
            unset($thishot[$key]['id']);

        }
        $results['status'] = 0;
        $results['data']['lightesWish'] = $wishall;
        $results['data']['thishot'] = $thishot;
        dexit($results);exit;
    }



}
