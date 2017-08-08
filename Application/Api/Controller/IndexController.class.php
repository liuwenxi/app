<?php

namespace Api\Controller;

use Common\Controller\BaseController;
use Lib\Api\Weixinapi\WeiXin;

/**

 * 首页控制器

 * @author kar1

 *

 */
class IndexController extends BaseController {
     public  function _initialize(){
        parent::_initialize();
        header("Access-Control-Allow-Origin: *");
    }


    //首页推荐
    public function index(){
      $uid=I('GET.uid','',0);
      $map['sort']=array('neq',0);
      $map['is_top']=array('eq',1);
            $wishall=M('Wishwall')->field('id,content,light_count,background,set_user,kid,is_top,posttime')->where($map)->limit(5)->order('sort asc')->select();

           $bgcolor=M('bgcolor')->field('color')->limit(30,3)->select();

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach($wishall as $key=>$val){
            //用户信息
            $userInfo=M('User')->field('nickname,avatar,gender')->where('id='.$val['set_user'])->find();
             //背景信息
            $color=M('wishwall_background')->field('font_color')->where('id='.$val['background'])->find();
            //评论总人数
            $count=M('Comment')->where('xid='.$val['id'])->count();
            //是否点亮该条心愿
            if($uid) {
                //用户回访周记录 by karl
                $this->actionUserLog($uid);

                $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $uid))->find();
                if ($islight) {
                    $wishall[$key]['isLight'] = TRUE;
                } else {
                    $wishall[$key]['isLight'] = FALSE;
                }
            }else{
                $wishall[$key]['isLight'] =FALSE;
            }
            //判断是否是秘室
            $subclass=M('Wishwall_keyword')->field('subclass')->where('id='.$val['kid'])->find();
            if($subclass['subclass'] == '0'){
                $wishall[$key]['nickName']=$userInfo['nickname'];
                $wishall[$key]['avatar']=$userInfo['avatar'];
            }else{
                $font=mb_substr($userInfo['nickname'],0,1,'utf-8');
                $avatar=get_url("/Uploads/userAvatar/avatar.png");
                $wishall[$key]['nickName']= $font.'**';
                $wishall[$key]['avatar']=$avatar;
            }

            $wishall[$key]['color']=$color['font_color'];
            $wishall[$key]['gender']=(int)$userInfo['gender'];
            $wishall[$key]['background']=(int)$val['background'];
            $wishall[$key]['uid']=(int)$val['set_user'];
            $wishall[$key]['lightCount']=(int)$val['light_count'];
            $wishall[$key]['commentCount']=(int)$count;
            $wishall[$key]['kid']=(int)$val['kid'];
            $wishall[$key]['id']=(int)$val['id'];
            $wishall[$key]['top']=(int)$key+1;
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
        $thishot=M('Wishwall_keyword')->field('id,keyword,big_img,bg_color')->where('type=0 and reveal=0')->order('sort asc')->select();

        foreach($thishot as $key=>$val){
            $thishot[$key]['count']=(int)M('Wishwall')->where('kid='.$val['id'])->count();
            $thishot[$key]['kid']=(int)$val['id'];
            $thishot[$key]['bgColor']=$val['bg_color'];
            $thishot[$key]['img']=$val['big_img'];
            unset($thishot[$key]['bg_color']);
            unset($thishot[$key]['big_img']);
            unset($thishot[$key]['id']);
        }
        /*************************************************banner图信息**********************************************************/
          $banner=M('banner')->field('id,img,jump_url,kid')->select();
           foreach($banner as  $key =>$val){
                 $banner[$key]['img']='http://php.17wxy.com'.$val['img'];
                 $banner[$key]['page']['name']=$val['jump_url'];
                 $banner[$key]['page']['id']=$val['kid'];
                 unset($banner[$key]['jump_url']);
                 unset($banner[$key]['kid']);
           }

        $banner=array_reverse($banner);
        $banner[]=$banner[0];
        $banner[]=$banner[1];

        //微信js配置接口信息
        $WeiChat = new WeiXin();
        $JsConfig = $WeiChat->getSignPackage();
        $datas['jsConfig'] = $JsConfig;
        /*************************************************banner****************************************************************/
        $datas['banner']=$banner;
        $datas['lightestWish'] = $wishall;
        $datas['thishot'] = $thishot;
        Json(0,'data',$datas,'成功');
    }



}
