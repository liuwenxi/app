<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 微心愿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Api\Controller;

use Think\Controller;
use Com\Wechat;
use Com\WechatAuth;

class WechatController extends Controller{
    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     */
    public function index($id = ''){
        //调试
        try{
            $appid = 'wx585451d6d3864526'; //AppID(应用ID)
            $token = 'E9E050245F5AR65e09d2B5554A8F34CE'; //微信后台填写的TOKEN
            $crypt = 'ha7kGxHdQAvNexOsd415TiqP0f9EirNMhVNtTTfNpjF'; //消息加密KEY（EncodingAESKey）
            
            /* 加载微信SDK */
            $wechat = new Wechat($token, $appid, $crypt);
            
            /* 获取请求信息 */
            $data = $wechat->request();

            if($data && is_array($data)){
                /**
                 * 你可以在这里分析数据，决定要返回给用户什么样的信息
                 * 接受到的信息类型有10种，分别使用下面10个常量标识
                 * Wechat::MSG_TYPE_TEXT       //文本消息
                 * Wechat::MSG_TYPE_IMAGE      //图片消息
                 * Wechat::MSG_TYPE_VOICE      //音频消息
                 * Wechat::MSG_TYPE_VIDEO      //视频消息
                 * Wechat::MSG_TYPE_SHORTVIDEO //视频消息
                 * Wechat::MSG_TYPE_MUSIC      //音乐消息
                 * Wechat::MSG_TYPE_NEWS       //图文消息（推送过来的应该不存在这种类型，但是可以给用户回复该类型消息）
                 * Wechat::MSG_TYPE_LOCATION   //位置消息
                 * Wechat::MSG_TYPE_LINK       //连接消息
                 * Wechat::MSG_TYPE_EVENT      //事件消息
                 *
                 * 事件消息又分为下面五种
                 * Wechat::MSG_EVENT_SUBSCRIBE    //订阅
                 * Wechat::MSG_EVENT_UNSUBSCRIBE  //取消订阅
                 * Wechat::MSG_EVENT_SCAN         //二维码扫描
                 * Wechat::MSG_EVENT_LOCATION     //报告位置
                 * Wechat::MSG_EVENT_CLICK        //菜单点击
                 */

                //记录微信推送过来的数据
                file_put_contents('./data.json', json_encode($data));

                /* 响应当前请求(自动回复) */
                //$wechat->response($content, $type);

                /**
                 * 响应当前请求还有以下方法可以使用
                 * 具体参数格式说明请参考文档
                 * 
                 * $wechat->replyText($text); //回复文本消息
                 * $wechat->replyImage($media_id); //回复图片消息
                 * $wechat->replyVoice($media_id); //回复音频消息
                 * $wechat->replyVideo($media_id, $title, $discription); //回复视频消息
                 * $wechat->replyMusic($title, $discription, $musicurl, $hqmusicurl, $thumb_media_id); //回复音乐消息
                 * $wechat->replyNews($news, $news1, $news2, $news3); //回复多条图文消息
                 * $wechat->replyNewsOnce($title, $discription, $url, $picurl); //回复单条图文消息
                 * 
                 */
                
                //执行Demo
                $this->wechat($wechat, $data);
            }
        } catch(\Exception $e){
            file_put_contents('./error.json', json_encode($e->getMessage()));
        }
        
    }

    /**
     * DEMO
     * @param  Object $wechat Wechat对象
     * @param  array  $data   接受到微信推送的消息
     */
    private function wechat($wechat, $data){
        switch ($data['MsgType']) {
            case Wechat::MSG_TYPE_EVENT:
                switch ($data['Event']) {
                    case Wechat::MSG_EVENT_SUBSCRIBE:
                        $wechat->replyText("你好呀，我的有愿人/示爱\n如果你有心愿，就说出来吧→<a href='http://m.17wxy.com/#/index'>【微心愿主页】</a>");
                        break;

                    case Wechat::MSG_EVENT_UNSUBSCRIBE:
                        //取消关注，记录日志
                        break;

                    case Wechat::MSG_EVENT_CLICK:
                        //关键字，记录日志
                        $platforms = M('Platform')->field(true)->where(array('key' => $data['EventKey']))->find();
                        if($platforms['type']==0){
                            $wechat->replyText($platforms['info']);
                        }elseif($platforms['type']==1){
                            $wechat->replyImage($platforms['url']);
                        }elseif($platforms['type']==2){
                            $platforms = M('Platform')->field(true)->where(array('key' => $data['EventKey']))->limit('0,8')->select();
//                        $platforms = M('Platform')->field(true)->where(array('key' => $data['EventKey']))->find();
                            $array=array();
                            if($platforms){
                                foreach($platforms as $key => $val){
                                    $array[]=array($val['title'],$val['info'],$val['url'],$val['pic']);
                                }
                            }
                            $wechat->replyNewsV2($array);
                        }


                        break;

                    default:
                        $wechat->replyText("欢迎访问微心愿公众平台！您的事件类型：{$data['Event']}，EventKey：{$data['EventKey']}");
                        break;
                }
                break;

            case Wechat::MSG_TYPE_TEXT:

                //关键字，记录日志
                $platforms = M('Keyword')->field(true)->where(array('key' => $data['Content']))->find();
                if($platforms){
                    if($platforms['type']==0){
                        $wechat->replyText($platforms['info']);
                    }elseif($platforms['type']==1){
                        $wechat->replyImage($platforms['url']);
                    }elseif($platforms['type']==2){
                        $platforms = M('Keyword')->field(true)->where(array('key' => $data['Content']))->limit('0,8')->select();
//                        $platforms = M('Platform')->field(true)->where(array('key' => $data['EventKey']))->find();
                        $array=array();
                        if($platforms){
                            foreach($platforms as $key => $val){
                                $array[]=array($val['title'],$val['info'],$val['url'],$val['pic']);
                            }
                        }
                        $wechat->replyNewsV2($array);
                    }
                }
                $count=mb_strlen($data['Content'],"UTF8");
                if($count==2){
                    $map['key'] =array('like',array("%".$data['Content'],$data['Content'].'%'),'OR');
                    $platforms=M('Keyword')->field(true)->where($map)->find();
                    if($platforms){
                        if($platforms['type']==0){
                            $wechat->replyText($platforms['info']);
                        }elseif($platforms['type']==1){
                            $wechat->replyImage($platforms['url']);
                        }elseif($platforms['type']==2){
                            $platforms = M('Keyword')->field(true)->where($map)->limit('0,8')->select();
//                        $platforms = M('Platform')->field(true)->where(array('key' => $data['EventKey']))->find();
                            $array=array();
                            if($platforms){
                                foreach($platforms as $key => $val){
                                    $array[]=array($val['title'],$val['info'],$val['url'],$val['pic']);
                                }
                            }
                            $wechat->replyNewsV2($array);
                        }
                    }
                }
                $wechat->replyText("欢迎访问微心愿公众平台！");
                break;
            
            default:
                # code...
                break;
        }
    }

    /**
     * 资源文件上传方法
     * @param  string $type 上传的资源类型
     * @return string       媒体资源ID
     */
    private function upload($type){
        $appid     = 'wx585451d6d3864526';
        $appsecret = 'ha7kGxHdQAvNexOsd415TiqP0f9EirNMhVNtTTfNpjF';

        $token = session("token");

        if($token){
            $auth = new WechatAuth($appid, $appsecret, $token);
        } else {
            $auth  = new WechatAuth($appid, $appsecret);
            $token = $auth->getAccessToken();

            session(array('expire' => $token['expires_in']));
            session("token", $token['access_token']);
        }

        switch ($type) {
            case 'image':
                $filename = './Public/image.jpg';
                $media    = $auth->materialAddMaterial($filename, $type);
                break;

            case 'voice':
                $filename = './Public/voice.mp3';
                $media    = $auth->materialAddMaterial($filename, $type);
                break;

            case 'video':
                $filename    = './Public/video.mp4';
                $discription = array('title' => '视频标题', 'introduction' => '视频描述');
                $media       = $auth->materialAddMaterial($filename, $type, $discription);
                break;

            case 'thumb':
                $filename = './Public/music.jpg';
                $media    = $auth->materialAddMaterial($filename, $type);
                break;
            
            default:
                return '';
        }

        if($media["errcode"] == 42001){ //access_token expired
            session("token", null);
            $this->upload($type);
        }

        return $media['media_id'];
    }
}
