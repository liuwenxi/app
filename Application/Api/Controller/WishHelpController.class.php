<?php
namespace Api\Controller;
use Common\Controller\BaseController;

class WishHelpController extends BaseController
{
    public function _initialize()
    {
        header('Content-type:text/html;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
    }
    public function createWishHelp(){

        $Wishhelp=M('Wishhelp');
        $User=M('user');
        $Bill=M('bill');
        $Message=M('message');
        $Navy=M('navy');


        $uploadtit=I('post.banner','htmlspecialchars');   //图片base64码
        $uploadtit=str_ireplace(' ','+',$uploadtit);   //图片base64码
        $uid=I('post.uid');   //图片base64码
        $gender=I('post.gender');   //性别
        $count=I('post.count');   //人数
        $content=I('post.content');   //内容
        $data['banner']=$uploadtit;

        if(empty($uploadtit) || empty($count)|| empty($content)){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }
        if($count==20){
            $wishBean=0;
        }elseif($count==30){
            $wishBean=10;
        }elseif($count==40){
            $wishBean=20;
        }elseif($count==50){
            $wishBean=30;
        }else{
            $results['status']=1;
            $results['errcode']=2;
            $results['msg'] = '人数设置有误';
            dexit($results);exit();
        }

        $totalmon=$User->field('totalmon')->where(array('id'=>$uid))->find();

        if($wishBean>$totalmon){
            $results['status']=1;
            $results['errcode']=3;
            $results['msg'] = '豆子不足';
            dexit($results);exit();
        }
        $imgPath='';

        $savepath = "./Uploads/wishhelp/" . Date("Y") . '/' . Date("m") . '/' . Date("d") .'/';
        $savePath = "/Uploads/wishhelp/" . Date("Y") . '/' . Date("m") . '/' . Date("d") .'/';
        //截取信息
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $uploadtit, $result)) {

            //随机名字
            $filName = uniqid();
            //判断路径是否存在
            if (!is_dir($savepath)) {
                //创建文件夹
                mkdir($savepath, 0777, true);
            }
            //将图片源写入保存
            if (file_put_contents($savepath . $filName . '.jpeg', base64_decode(str_replace($result[1], '', $uploadtit)))) {

                //保存
                oss_upload($savepath . $filName . '.jpeg');
                $imgPath = get_url($savePath. $filName . '.jpeg');//这个是图片数据库保存的路径
            }
        }
        if(!$imgPath){
            $results['status']=1;
            $results['errcode']=4;
            $results['msg'] = '图片上传失败';
            dexit($results);exit();
        }
        //开启事务
        $m=M();
        $m->startTrans();
        $data=array();
        $data['post_uid']=$uid;
        $data['content']=$content;
        $data['img']=$imgPath;
        $data['post_time']=time();
        $data['status']=0;
        $data['set_gender']=$gender;
        $data['set_count']=$count;
        $data['wish_bean']=$wishBean;
        $data['type']=0;
        $add_id=$Wishhelp->add($data);
        if(!$add_id){
            $m->rollback();
            $results['status']=1;
            $results['errcode']=5;
            $results['msg'] = '添加数据失败';
            dexit($results);exit();
        }
        if($wishBean!=0){
            if(!$User->where(array('id'=>$uid))->setDec('totalmon',$wishBean)){
                $m->rollback();
                $results['status']=1;
                $results['errcode']=6;
                $results['msg'] = '更新数据失败';
                dexit($results);exit();
            }
            $data=array();
            $data['uid']=$uid;
            $data['type']=8;
            $data['wishType']=3;
            $data['mon']=$wishBean;
            $data['posttime']=time();
            $data['dotime']=time();
            $data['xid']=$add_id;
            $data['is_finish']=1;
            if(!$Bill->add($data)){
                $m->rollback();
                $results['status']=1;
                $results['errcode']=7;
                $results['msg'] = '更新数据失败';
                dexit($results);exit();
            }
            $data=array();
//            $navyInfo = $Navy->where(array('state'=>1))->find();
            $data['uid']=$uid;
            $data['nid']=2;
            $data['type']=3;
            $data['wishType']=1;
            $data['content']='你在心愿帮发布了一个心愿，共消耗'.$wishBean.'心愿豆。';
            $data['title']='发布心愿';
            $data['posttime']=time();
            $data['releasetime']=time();
            $data['xid']=$add_id;
            if(!$Message->add($data)){
                $m->rollback();
                $results['status']=1;
                $results['errcode']=7;
                $results['msg'] = '更新数据失败';
                dexit($results);exit();
            }
        }

        if(!addUseDynamic($uid,2,$add_id,1)){
            $m->rollback();
            $results['status']=1;
            $results['errcode']=7;
            $results['msg'] = '更新数据失败';
            dexit($results);exit();
        }
        $m->commit();
        $results['status']=0;
        $results['wish_id']=$add_id;
        $results['msg'] = '成功';
        dexit($results);exit();

    }

    //获取心愿对象
    public function wishHelpList()
    {
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');
        $User=M('User');

        $gender=I('get.gender');   //性别
        $page=I('get.page');   //分页
        $status=I('get.status');   //状态
        $uid=I('get.uid');   //用户
        $type=I('get.type');   //分类

        if($type==1){   //心愿广场

            $where=array();
            $where['set_gender']=$gender;
            $where['status']=$status;

            $count=$Wishhelp->where($where)->count();
//            echo $Wishhelp->getLastSql();die;
            $countPage=ceil($count/10);
//            $list=$Wishhelp->where($where)->page($page,10)->select();
            $list=$Wishhelp->where($where)->page($page,10)->order('id desc')->select();
//            echo $Wishhelp->getLastSql();die;
            foreach ($list as $key => $val){
                $list[$key]['help_users']=$WishhelpJoin->where(array('wish_id'=>$val['id']))->select();
                $useravatar = $User->where(array('id'=>$val['post_uid']))->find();
                $list[$key]['avatar'] =  "http://php.17wxy.com".$useravatar['avatar'];
            }
            foreach ($list as $key => $val){
                $list[$key]['userInfo']=$User->field('password,pay_pwd',true)->where(array('id'=>$val['post_uid']))->find();
                if($val['help_users']){
                    foreach ($val['help_users'] as $k => $v){
                        $list[$key]['help_users'][$k]['userInfo']=$User->field('password,pay_pwd',true)->where(array('id'=>$v['join_uid']))->find();
                    }
                }
            }

            $results['status']=0;
            $results['totalPage']=$countPage;
            $results['data']=$list;
            dexit($results);exit();

        }else if($type==2){ //我的帮助

            $where=array();
            $where['join_uid']=$uid;

            if($status==-1){
                $where['a.status']=array('in','4,5');
//                $where['b.status']=array('in','4,5');
//                $where['_logic'] = 'or';
            }else{
                $where['a.status']=$status;
            }

            $count=$WishhelpJoin->alias('a')->field('b.*,a.join_uid,a.join_time,a.massge,a.status as join_status,a.select_time,a.cancel_sign_time,a.cancel_help_time,a.over_time as join_over_time')->join('wxy_wishhelp as  b ON a.wish_id = b.id','LEFT')->where($where)->count();
            $countPage=ceil($count/10);
            $list=$WishhelpJoin->alias('a')->field('b.*,a.join_uid,a.join_time,a.massge,a.status as join_status,a.select_time,a.cancel_sign_time,a.cancel_help_time,a.over_time as join_over_time')->join('wxy_wishhelp as  b ON a.wish_id = b.id','LEFT')->where($where)->page($page,10)->select();

            foreach ($list as $key => $val){
                $list[$key]['post_userInfo']=$User->field('password,pay_pwd',true)->where(array('id'=>$val['post_uid']))->find();
                $list[$key]['join_userInfo']=$User->field('password,pay_pwd',true)->where(array('id'=>$val['join_uid']))->find();
            }

            $results['status']=0;
            $results['totalPage']=$countPage;
            $results['data']=$list;
            dexit($results);exit();

        }else if($type==3){ //我的心愿

            $where=array();
            $where['post_uid']=$uid;
            $where['type']=0;
            if($status==-1){
                $where['status']=array('in','2,3,4');
            }else{
                $where['status']=$status;
            }

            $count=$Wishhelp->where($where)->count();
            $countPage=ceil($count/10);
            $list=$Wishhelp->where($where)->page($page,10)->select();

            foreach ($list as $key => $val){
                $list[$key]['help_users']=$WishhelpJoin->where(array('wish_id'=>$val['id']))->select();
                if($val['status']==1){
                    $join_user=$WishhelpJoin->where(array('wish_id'=>$val['id'],'status'=>1))->find();
                    $list[$key]['checkedUser']=$User->field('password,pay_pwd',true)->where(array('id'=>$join_user['join_uid']))->find();
                }
            }
            foreach ($list as $key => $val){
                $list[$key]['userInfo']=$User->field('password,pay_pwd',true)->where(array('id'=>$val['post_uid']))->find();
                if($val['help_users']){
                    foreach ($val['help_users'] as $k => $v){
                        $list[$key]['help_users'][$k]['userInfo']=$User->field('password,pay_pwd',true)->where(array('id'=>$v['join_uid']))->find();
                        if($v['status']==1){
                            $list[$key]['selected_user']=$v;
                            $list[$key]['selected_user']['nickname']=$this->get_nickname($v['join_uid']);
                            $list[$key]['selected_user']['avatar']=$this->get_avatar($v['join_uid']);
                        }
                    }
                }
            }

            $results['status']=0;
            $results['totalPage']=$countPage;
            $results['data']=$list;
            dexit($results);exit();
        }else{
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }

    }

    //报名
    public function signUp()
    {
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');
        $User=M('User');

        $wish_id=I('post.wish_id');   //心愿id
        $uid=I('post.uid');   //用户
        $massge=I('post.massge');   //留言信息

        if(!$wish_id || !$uid || !$massge){
            $results['status']=1;
            $results['errcode']=8;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }

        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$wish_id))->find();
        if($wish['status']>0){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '心愿无法参与';
            dexit($results);exit();
        }

        if($wish['post_uid']==$uid){
            $results['status']=1;
            $results['errcode']=8;
            $results['msg'] = '无法报名自己发布的心愿';
            dexit($results);exit();
        }

        //判断性别限制
        if($wish['set_gender']==1){
            $userInfo=$User->where(array('id'=>$uid))->find();
            if($userInfo['sex']!=0){
                $results['status']=1;
                $results['errcode']=2;
                $results['msg'] = '只限制男生能参与';
                dexit($results);exit();
            }
        }elseif($wish['set_gender']==2){
            $userInfo=$User->where(array('id'=>$uid))->find();
            if($userInfo['sex']!=0){
                $results['status']=1;
                $results['errcode']=3;
                $results['msg'] = '只限制女生能参与';
                dexit($results);exit();
            }
        }


        //判断是否已参加
        $where=array();
        $where['wish_id']=$wish_id;
        $where['join_uid']=$uid;
        $where['status']=0;
        $count=$WishhelpJoin->where($where)->count();
        if($count){
            $results['status']=1;
            $results['errcode']=4;
            $results['msg'] = '已参加';
            dexit($results);exit();
        }

        //判断人数
        $where=array();
        $where['wish_id']=$wish_id;
        $where['status']=0;
        $count=$WishhelpJoin->where($where)->count();
        if($count>$wish['set_count']){
            $results['status']=1;
            $results['errcode']=5;
            $results['msg'] = '名额已满';
            dexit($results);exit();
        }

        //判断参加记录
        $where=array();
        $where['wish_id']=$wish_id;
        $where['join_uid']=$uid;
        $newWish=$WishhelpJoin->where($where)->find();

        if($newWish){//有参加记录

            if($newWish['status']==3){
                $results['status']=1;
                $results['errcode']=6;
                $results['msg'] = '取消过该心愿的帮助，无法再次申请';
                dexit($results);exit();
            }

            //修改参与数据并判断
            $data=array();
            $data['join_time']=time();
            $data['massge']=$massge;
            $data['status']=0;

            //修改纪录和用户行为
            if($WishhelpJoin->where(array('id'=>$newWish['id']))->save($data)){
                addUseDynamic($uid,2,$wish_id,3);
                $results['status']=0;
                $results['userInfo']=$userInfo;
                $results['set_gender']=$wish['set_gender'];
                dexit($results);exit();
            }
            $results['status']=1;
            $results['errcode']=7;
            $results['msg'] = '报名失败';
            dexit($results);exit();
        }else{
            //添加参与数据并判断
            $data=array();
            $data['wish_id']=$wish_id;
            $data['join_uid']=$uid;
            $data['join_time']=time();
            $data['massge']=$massge;
            $data['status']=0;

            //添加纪录和用户行为
            if($WishhelpJoin->add($data)){
                addUseDynamic($uid,2,$wish_id,3);
                $results['status']=0;
                $results['userInfo']=$userInfo;
                $results['set_gender']=$wish['set_gender'];
                dexit($results);exit();
            }
        }

        $results['status']=1;
        $results['errcode']=7;
        $results['msg'] = '报名失败';
        dexit($results);exit();

    }

    //取消报名
    public function canselSignUp()
    {
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');

        $wish_id=I('post.wish_id');   //心愿id
        $uid=I('post.uid');   //用户

        if(!$wish_id || !$uid ){
            $results['status']=1;
            $results['errcode']=3;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }

        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$wish_id))->find();
        if($wish['status']>=1){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '心愿进行中无法取消报名';
            dexit($results);exit();
        }

        //判断是否参与
        $where=array();
        $where['wish_id']=$wish_id;
        $where['join_uid']=$uid;
        $newWish=$WishhelpJoin->where($where)->find();
        if($newWish['status']>=1){
            $results['status']=1;
            $results['errcode']=2;
            $results['msg'] = '不是待选择状态，无法取消报名';
            dexit($results);exit();
        }

        //修改参与数据并判断
        $data=array();
        $data['cancel_sign_time']=time();
        $data['status']=2;

        if($WishhelpJoin->where(array('id'=>$newWish['id']))->save($data)){
            addUseDynamic($uid,2,$wish_id,5);
            $results['status']=0;
            dexit($results);exit();
        }
        $results['status']=1;
        $results['errcode']=4;
        $results['msg'] = '取消报名失败';
        dexit($results);exit();

    }

    //选中
    public function checkedUser(){
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');

        $wish_id=I('post.wish_id');   //心愿id
        $post_uid=I('post.post_uid');   //发布用户
        $join_uid=I('post.join_uid');   //参与用户

        if(!$wish_id || !$post_uid || !$join_uid){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }

        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$wish_id,'post_uid'=>$post_uid))->find();
        if(empty($wish)){
            $results['status']=1;
            $results['errcode']=2;
            $results['msg'] = '没有找到心愿';
            dexit($results);exit();
        }
        $wishJoin=$WishhelpJoin->where(array('wish_id'=>$wish_id,'join_uid'=>$join_uid))->find();
        if(empty($wishJoin)){
            $results['status']=1;
            $results['errcode']=3;
            $results['msg'] = '没有找到心愿';
            dexit($results);exit();
        }

        if($wish['status']>0){
            $results['status']=1;
            $results['errcode']=3;
            $results['msg'] = '心愿非带选择';
            dexit($results);exit();
        }
        $m=M();
        $m->startTrans();
        $data=array();
        $data['status']=1;
        if(!$Wishhelp->where(array('id'=>$wish_id,'post_uid'=>$post_uid))->save($data)){
            $m->rollback();
            $results['status']=1;
            $results['errcode']=4;
            $results['msg'] = '更新数据失败1';
            dexit($results);exit();
        }
        $newData=array();
        $newData['status']=1;
        $newData['select_time']=time();
        if(!$WishhelpJoin->where(array('wish_id'=>$wish_id,'join_uid'=>$join_uid))->save($newData)){
            $m->rollback();
            $results['status']=1;
            $results['errcode']=5;
            $results['msg'] = '更新数据失败2';
            dexit($results);exit();
        }
        if(!addUseDynamic($join_uid,2,$wish_id,11)){
            $m->rollback();
            $results['status']=1;
            $results['errcode']=6;
            $results['msg'] = '更新数据失败3';
            dexit($results);exit();
        }
        $m->commit();
        $results['status']=0;
        $results['msg'] = '成功';
        dexit($results);exit();

    }

    //取消帮助
    public function canselHelp()
    {
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');

        $wish_id=I('post.wish_id');   //心愿id
        $uid=I('post.uid');   //用户

        if(!$wish_id || !$uid ){
            $results['status']=1;
            $results['errcode']=3;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }
        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$wish_id))->find();
        if($wish['status']!=1){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '不是待选择状态，无法取消帮助';
            dexit($results);exit();
        }

        //判断是否被选中
        $where=array();
        $where['wish_id']=$wish_id;
        $where['join_uid']=$uid;
        $newWish=$WishhelpJoin->where($where)->find();
        if($newWish['status']!=1){
            $results['status']=1;
            $results['errcode']=2;
            $results['msg'] = '不是选中状态，无法取消帮助';
            dexit($results);exit();
        }

        //修改参与数据并判断
        $data=array();
        $data['cancel_help_time']=time();
        $data['status']=3;

        $data2=array();
        $data2['status']=0;

        if($WishhelpJoin->where(array('id'=>$newWish['id']))->save($data) && $Wishhelp->where(array('id'=>$wish_id))->save($data2)){
            addUseDynamic($uid,2,$wish_id,6);
            $results['status']=0;
            dexit($results);exit();
        }
        $results['status']=1;
        $results['errcode']=4;
        $results['msg'] = '取消帮助失败';
        dexit($results);exit();

    }

    //下架
    public function offTheShelf()
    {
        $Wishhelp=M('Wishhelp');

        $wish_id=I('post.wish_id');   //心愿id
        $uid=I('post.uid');   //用户

        if(!$wish_id || !$uid ){
            $results['status']=1;
            $results['errcode']=2;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }

        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$wish_id,'post_uid'=>$uid))->find();
        if(!$wish){
            $results['status']=1;
            $results['errcode']=4;
            $results['msg'] = '非法操作';
            dexit($results);exit();
        }
        if($wish['status']!=0){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '心愿非待选择状态无法下架';
            dexit($results);exit();
        }

        //修改参与数据并判断
        $data=array();
        $data['out_time']=time();
        $data['status']=4;

        if($Wishhelp->where(array('id'=>$wish_id))->save($data)){
            $results['status']=0;
            dexit($results);exit();
        }
        $results['status']=1;
        $results['errcode']=3;
        $results['msg'] = '下架帮助失败';
        dexit($results);exit();

    }

    //达成心愿
    public function reachWish()
    {
        $Wishhelp=M('Wishhelp');
        $WishhelpJoin=M('WishhelpJoin');

        $wish_id=I('post.wish_id');   //心愿id
        $uid=I('post.uid');   //用户

        if(!$wish_id || !$uid ){
            $results['status']=1;
            $results['errcode']=2;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }

        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$wish_id,'post_uid'=>$uid))->find();
        if($wish['status']!=1){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '心愿非进行中无法达成';
            dexit($results);exit();
        }

        $where=array();
        $where['wish_id']=$wish_id;
        $where['status']=1;
        $newWish=$WishhelpJoin->where($where)->find();

        //修改参与数据并判断
        $data=array();
        $data['over_time']=time();
        $data['status']=4;

        $data2=array();
        $data2['status']=3;
        $data2['over_time']=time();

        if($WishhelpJoin->where(array('id'=>$newWish['id']))->save($data) && $Wishhelp->where(array('id'=>$wish_id))->save($data2)){
            addUseDynamic($uid,2,$wish_id,10);
            $results['status']=0;
            dexit($results);exit();
        }
        $results['status']=1;
        $results['errcode']=3;
        $results['msg'] = '完结失败';
        dexit($results);exit();

    }
    //删除心愿
    public function delWish()
    {
        $Wishhelp=M('Wishhelp');

        $wish_id=I('post.wish_id');   //心愿id
        $uid=I('post.uid');   //用户

        if(!$wish_id || !$uid ){
            $results['status']=1;
            $results['errcode']=2;
            $results['msg'] = '请检查参数';
            dexit($results);exit();
        }

        //判断心愿状态
        $wish=$Wishhelp->where(array('id'=>$wish_id,'post_uid'=>$uid))->find();
        if($wish['status']!=4){
            $results['status']=1;
            $results['errcode']=1;
            $results['msg'] = '心愿非下架无法删除';
            dexit($results);exit();
        }

        //修改参与数据并判断
        $data=array();
        $data['type']=1;
        if($Wishhelp->where(array('id'=>$wish_id))->save($data)){
            $results['status']=0;
            dexit($results);exit();
        }
        $results['status']=1;
        $results['errcode']=3;
        $results['msg'] = '删除失败';
        dexit($results);exit();
    }
}
