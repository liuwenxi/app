<?php
namespace Api\Controller;
use Common\Controller\BaseController;

/**
 * Class MessageManageController
 * @package Api\Controller
 */
class MessageManageController extends BaseController {
    public function _initialize()
    {
        header('Content-type:text/html;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
    }

    public function queryMessageCategory(){
        $Message = M('Message');
        $User = M('User');
        $uid = I('post.uid','','int');

        $type[1] = $Message->where(array('uid'=>$uid,'is_read'=>0,'type'=>1))->count();
        $type[2] = $Message->where(array('uid'=>$uid,'is_read'=>0,'type'=>2))->count();
        $type[3] = $Message->where(array('uid'=>$uid,'is_read'=>0,'type'=>3))->count();
        $notice = array('name'=>'通知信息','count'=>$type[1],'id'=>1);
        $wish = array('name'=>'心愿信息','count'=>$type[2],'id'=>2);
        $wishBean = array('name'=>'心愿豆信息','count'=>$type[3],'id'=>3);
        $arr =array($notice,$wish,$wishBean);

        $results['status'] = 0;
        $results['data'] = $arr;
        dexit($results);exit();
    }

    public function queryMessage(){
        $Message = M('Message');
        $User = M('User');
        $navy = M('Navy');
        //获取通知
            $mtype =I('post.category');
            $uid =I('post.uid');
            $page=I('post.page');
            if(empty($mtype) || empty($uid)){
                $results['status'] = 1;
                $results['errcode'] = 1;
                $results['msg'] = '参数不完整';
                dexit($results);
                exit;
            }
            $where = array('uid'=>$uid,'hide'=>0,'type'=>$mtype);
            $count = $Message->where($where)->count();
            $countPage=ceil($count/5);
            $fields = 'id,xid,nid,type,wish_type,keyword,title,content,img,posttime';
            $messages = $Message->where($where)->page($page,5)->order('posttime desc')->select();
            if(empty($messages)){
                $navyInfo = $navy->where(array('state'=>1))->find();
                $message['timestamp'] = time();
                $message['style'] = 0;
                $message['banner'] = array('img'=>'','title'=>'','actionName'=>'');
                $message['user'] = array('username'=>$navyInfo['username'],'id'=>'','avatar'=>$navyInfo['img']);
                $message['content'] = array('main'=>'暂无消息...','keyword'=>'');
                $message = array($message);
            }
            foreach($messages as $kk=>$vv){
                $mid = $vv['id'];
                $Message->where(array('id' =>$mid))->data(array('is_read'=> 1))->save();
                if(!empty($vv['nid'])){
                    $nid = $navy->find($vv['nid']);
                    $username = $nid['username'];
                    $userImg = $nid['img'];
                }
                $message[$kk]['id'] = $vv['id'];
                $message[$kk]['timestamp'] = $vv['posttime'];
                if($vv['img']){
                    $message[$kk]['style'] = 1;
                }else{
                    $message[$kk]['style'] = 0;
                }
                $message[$kk]['content'] = array('main'=>$vv['content'],'keyword'=>$vv['keyword']);
                $message[$kk]['banner'] = array('img'=>$vv['img'],'title'=>$vv['title'],'actionName'=>'');
                $message[$kk]['user'] = array('username'=>$username,'id'=>$vv['nid'],'avatar'=>$userImg);
                unset($vv['xid'],$vv['nid'],$vv['wish_type'],$vv['keyword'],$vv['title'],$vv['posttime'],$vv['img']);
            }
            $results['status'] = 0;
            $results['msg'] = '请求成功';
            $results['page'] = $countPage;
            $results['message'] = $message;
            dexit($results);
            exit;
    }

    /**
     * 删除消息
     */
    public function deleteMessage()
    {
        $Message = M('Message');
        $id = I('post.id');
        $uid = I('post.uid');
        if (!$id || !$uid) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不完整';
            dexit($results);
            exit;
        }
        $info = $Message->where(array('id' => $id, 'uid' => $uid))->find();
        if (!$info) {
            $save = $Message->where(array('id' => $id, 'uid' => $uid))->data(array('hide' => 1))->save();
            $results['status'] = 0;
            $results['msg'] = '删除成功';
            dexit($results);
            exit();
        }else{
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '删除失败';
            dexit($results);
            exit();
        }

    }

}

