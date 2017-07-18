<?php
namespace Api\Controller;
use Common\Controller\BaseController;

class ChatroomController extends BaseController
{
    public function _initialize()
    {
        header("Access-Control-Allow-Origin: *");
    }

    /**
     * 检测关系
     * by karl 2017-04-06 10:56:19
     **/
    protected function check($wish_id,$send_user,$take_user){

        $Wishhelp=M('Wishhelp');
        $where['a.id']=$wish_id;
        $where['a.post_uid']=$send_user;
        $where['b.join_uid']=$take_user;

        return $Wishhelp->alias('a')->join('wxy_wishhelp_join as  b ON b.wish_id = a.id','LEFT')->where($where)->count();

    }

    /**
     * 初始用户聊天记录
     * by karl 2017-04-06 11:10:08
     **/
    public function queryChatLog(){

        $ChatLog=M('chat_log');

        $wish_id=I('post.wish_id');   //心愿id
        $send_user=I('post.send_user');   //发布者id
        $take_user=I('post.take_user');   //帮助者id
        $segment=I('post.segment');   //时间段

//        if(!$this->check($wish_id,$send_user,$take_user)){
//            $results['status']=1;
//            $results['errcode']=1;
//            $results['msg'] = '无法聊天';
//            dexit($results);exit();
//        }
        $where=array();
        $where['wish_id']=$wish_id;
        //查找最新的时间段
        $showSegment=$ChatLog->field('segment')->where($where)->order('id desc')->find();

//        $where['send_user']=$send_user;
//        $where['take_user']=$take_user;
        if($segment>0){//根据时间段请求数据
            $where['segment']=$segment;
            //获取最新时间段下的所有记录
            $logs=$ChatLog->field('send_user as sendId,message,timestamp')->where($where)->select();
            $data['segment'] = $segment-1;

        }else{
            //获取最新时间段下的所有记录
            $logs=$ChatLog->field('send_user as sendId,message,timestamp')->where(array('segment'=>$showSegment['segment']))->select();
            $data['segment'] = $showSegment['segment']-1;

        }
        $data['chatLog']=$logs;
        $data['timestamp']=$logs[0]['timestamp']*1000;

        dexit($data);exit();

    }

    /**
     * 获取聊天记录
     */
    public function getChatLog(){

        $ChatLog=M('chat_log');


        $wish_id=I('post.wish_id');   //心愿id
        $post_uid=I('post.send_user');   //发布者id
        $join_uid=I('post.take_user');   //帮助者id
        $segment=I('post.segment');   //时间段

        if(!$this->check($wish_id,$post_uid,$join_uid)){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '无法获取';
            dexit($results);exit();
        }

        $where=array();
        $where['wish_id']=$wish_id;
        $where['send_user']=$post_uid;
        $where['take_user']=$join_uid;
        $where['segment']=$segment;

        //获取最新时间段下的所有记录
        $logs=$ChatLog->where($where)->select();

        $results['status']=0;
        $results['data'] = $logs;
        $results['segment'] = $segment;
        dexit($results);exit();
    }

    /**
     * 添加用户聊天记录
     * by karl 2017-04-06 11:10:08
     **/
    public function addChatLog(){

        $ChatLog=M('chat_log');

        $wish_id=I('post.wish_id');   //心愿id
        $post_uid=I('post.post_uid');   //发布者id
        $join_uid=I('post.join_uid');   //帮助者id
        $message=I('post.message');   //发送信息

        if(!$message){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }

        if(!$this->check($wish_id,$post_uid,$join_uid)){
            $results['status']=1;
            $results['errcode']=2;
            $results['msg'] = '无法聊天';
            dexit($results);exit();
        }

        $where=array();
        $where['wish_id']=$wish_id;
        $where['post_uid']=$post_uid;
        $where['join_uid']=$join_uid;
        //查找最新的时间段
        $topLog=$ChatLog->where($where)->order('id desc')->find();
        $time=time();
        $segment=(floor(($time-$topLog['addtime'])/60)) >5 ? $topLog['segment']+1 : $topLog['segment'];

        $data=array();
        $data['wish_id']=$wish_id;
        $data['post_uid']=$post_uid;
        $data['join_uid']=$join_uid;
        $data['addtime']=$time;
        $data['segment']=$segment;

        if($ChatLog->add($data)){
            $results['status']=0;
            $results['msg'] = '添加成功';
            dexit($results);exit();
        }

        $results['status']=1;
        $results['errcode']=3;
        $results['msg'] = '添加失败';
        dexit($results);exit();

    }

}
