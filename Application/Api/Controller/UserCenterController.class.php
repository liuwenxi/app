<?php
namespace Api\Controller;

use Common\Controller\BaseController;
use Lib\Api\Weixinapi\WeiXin;
/**
 * 个人中心类控制器
 * @package Api\Controller
 */
class UserCenterController extends BaseController
{
    public function _initialize()
    {
        header('Content-type:text/html;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
    }

    public function index()
    {
       $User = M('User');
       $UserRelation = M('UserRelation');

       //自己的id
        $myid = I('get.uid', '', 'int');

        //用户id
        $oid=I('get.oid','','int');

        $results['status'] = 0;

        //如果$oid为真才去查找下面的信息
        if ($oid) {
            $fields = "id,nickName,avatar,signature,is_auth as isAuth,credit,totalmon,gender,reg_time";
            $results['data']['userInfo'] = $User->field($fields)->find($oid);
            $results['data']['userInfo']['id']=(int)$results['data']['userInfo']['id'];
            $results['data']['userInfo']['isAuth']=(int)$results['data']['userInfo']['isAuth'];
            $results['data']['userInfo']['creadit']=(int)$results['data']['userInfo']['credit'];
            $results['data']['userInfo']['totalmon']=(int)$results['data']['userInfo']['totalmon'];
            $results['data']['userInfo']['gender']=(int)$results['data']['userInfo']['gender'];
            $results['data']['userInfo']['reg_time']=(int)$results['data']['userInfo']['reg_time'];
            $isFan = $UserRelation->where(array('fan_uid' => $myid, 'uid' => $oid, 'status' => 1))->count();
            $results['data']['isFan'] = !$isFan ? false : true;

        }

        //$oid为空的话就把$myid赋值给$oid
        if(empty($oid)){$oid =$myid;}
        if(!$oid){
             $results['status']=1;
             $results['error']=1;
             $results['msg']='参数不完整';
             dexit($results);
        }

        $inteaction['follow'] =(int) $UserRelation->where(array('fan_uid' => $oid, 'status' => 1))->count();
        $inteaction['fans'] = (int)$UserRelation->where(array('uid' => $oid, 'status' => 1))->count();
        $results['data']['interaction'] = $inteaction;
        //如果有别的用户来访问无秘室的心愿则不显示
        if($_GET['oid']){
            $count=M('Wishwall')->where('kid <>1 and  set_user='.$oid)->field('id,content,light_count,background,set_user,kid,is_top,posttime')->order('posttime desc')->count();
            $countPage=(int)ceil($count/10);
            $newlight=M('Wishwall')->where('kid <>1 and  set_user='.$oid)->field('id,content,light_count,background,set_user,kid,is_top,posttime')->limit(10)->order('posttime desc')->select();

        }else{
            $count=M('Wishwall')->where('set_user='.$oid)->field('id,content,light_count,background,set_user,kid,is_top,posttime')->order('posttime desc')->count();
            $countPage=(int)ceil($count/10);
            $newlight=M('Wishwall')->where('set_user='.$oid)->field('id,content,light_count,background,set_user,kid,is_top,posttime')->limit(10)->order('posttime desc')->select();
        }

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach($newlight as $key=>$val){
            //用户信息
            $userInfo=M('User')->field('nickname,avatar,gender')->where('id='.$val['set_user'])->find();

            //背景信息
            $bg=M('wishwall_background')->field('font_color,large_img')->where('id='.$val['background'])->find();

            $count=M('Comment')->where('xid='.$val['id'])->count();
            //是否点亮该条心愿
            if($myid) {
                $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $myid))->find();
                if ($islight) {
                    $newlight[$key]['isLight'] = TRUE;
                } else {
                    $newlight[$key]['isLight'] = FALSE;
                }
            }else{
                $newlight[$key]['isLight'] =FALSE;
            }
            //是否是无秘室
            $subclass=M('Wishwall_keyword')->field('subclass')->where('id='.$val['kid'])->find();
            if($subclass['subclass'] == '0'){
                $newlight[$key]['nickName']=$userInfo['nickname'];
                $newlight[$key]['avatar']=$userInfo['avatar'];
            }else{
                $font=mb_substr($userInfo['nickname'],0,1,'utf-8');
                $avatar=get_url("/Uploads/userAvatar/avatar.png");
                $newlight[$key]['nickName']= $font.'**';
                $newlight[$key]['avatar']=$avatar;
            }
            //$newlight[$key]['nickName']=$userInfo['nickname'];
            $newlight[$key]['fontColor']=$bg['font_color'];
            $newlight[$key]['bgImg']=$bg['large_img'];
            $newlight[$key]['background']=(int)$val['background'];
           // $newlight[$key]['avatar']=$userInfo['avatar'];
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

        //微信js配置接口信息
        $WeiChat = new WeiXin();
        $JsConfig = $WeiChat->getSignPackage();
        $results['data']['jsConfig'] = $JsConfig;
        $results['data']['dynamic']['wish']= $newlight;
        $results['data']['dynamic']['totalPage']=$countPage;


        dexit($results);
    }

    //个人中心加载分页
    public function indexPage(){
        //自己的id
        $myid = I('get.uid', '', 'int');
        //用户id
        $oid=I('get.oid','','int');

        $page=I('get.page','',int);
        $page = $page<1?1:$page;
        $pageSize=10;
        $pre = ($page-1)*$pageSize;
        if(empty($oid)){$oid =$myid;}

        if(!$oid){
            $results['status']=1;
            $results['error']=1;
            $results['msg']='参数不完整';
            dexit($results);
        }
        //如果有别的用户来访问无秘室的心愿则不显示

        if($_GET['oid']){
            $count=M('Wishwall')->where('kid <>1 and  set_user='.$oid)->field('id,content,light_count,background,set_user,kid,is_top,posttime')->where('')->order('posttime desc')->count();
            $countPage=(int)ceil(count($count)/$pageSize);
            $newlight=M('Wishwall')->where('kid <>1 and  set_user='.$oid)->field('id,content,light_count,background,set_user,kid,is_top,posttime')->limit($pre,$pageSize)->order('posttime desc')->select();
        }else{
            $count=M('Wishwall')->where('set_user='.$oid)->field('id,content,light_count,background,set_user,kid,is_top,posttime')->order('posttime desc')->select();
            $countPage=(int)ceil(count($count)/$pageSize);
            $newlight=M('Wishwall')->where('set_user='.$oid)->field('id,content,light_count,background,set_user,kid,is_top,posttime')->limit($pre,$pageSize)->order('posttime desc')->select();
        }

        //根据set_user获取用户的性别，头像，昵称,背景图片
        foreach($newlight as $key=>$val){
            //用户信息
            $userInfo=M('User')->field('nickname,avatar,gender')->where('id='.$val['set_user'])->find();

            //背景信息
            $bg=M('wishwall_background')->field('font_color,large_img')->where('id='.$val['background'])->find();

            $count=M('Comment')->where('xid='.$val['id'])->count();
            //是否点亮该条心愿
            if($myid) {
                $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $myid))->find();
                if ($islight) {
                    $newlight[$key]['isLight'] = TRUE;
                } else {
                    $newlight[$key]['isLight'] = FALSE;
                }
            }else{
                $newlight[$key]['isLight'] =FALSE;
            }
            //是否是无秘室
            $subclass=M('Wishwall_keyword')->field('subclass')->where('id='.$val['kid'])->find();
            if($subclass['subclass'] == '0'){
                $newlight[$key]['nickName']=$userInfo['nickname'];
                $newlight[$key]['avatar']=$userInfo['avatar'];
            }else{
                $font=mb_substr($userInfo['nickname'],0,1,'utf-8');
                $avatar=get_url("/Uploads/userAvatar/avatar.png");
                $newlight[$key]['nickName']= $font.'**';
                $newlight[$key]['avatar']=$avatar;
            }
            //$newlight[$key]['nickName']=$userInfo['nickname'];
            $newlight[$key]['fontColor']=$bg['font_color'];
            $newlight[$key]['bgImg']=$bg['large_img'];
            $newlight[$key]['background']=(int)$val['background'];
            // $newlight[$key]['avatar']=$userInfo['avatar'];
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
        $results['status']=0;
        $results['data']['dynamic']['totalPage']=$countPage;
        $results['data']['dynamic']['wish'] = $newlight;
        dexit($results);

    }

    public function dynamic($userid)
    {
        $User = M('User');
        $dynamic = M('UserDynamic');
        $Wishwall = M('Wishwall');
        $background = M('WishwallBackground');
        $Wishhelp = M('Wishhelp');
        $WishhelpComplain = M('WishhelpComplain');
        $UserRelation = M('UserRelation');

        $fields = "id,nickname,avatar,gender";
        $userinfo = $User->field($fields)->find($userid);
        $attentions = $UserRelation->where(array('fan_uid' => $userid, 'status' => 1))->count();
        $fan = $UserRelation->where(array('uid' => $userid, 'status' => 1))->count();
        $results['follow'] = $attentions;
        $results['fans'] = $fan;

        $where['uid'] = $userid;
        $where['type'] = array(1,2,'or');
        $data = $dynamic->where($where)->order('add_time desc')->select();
        foreach ($data as $key => $val) {
            if ($val['type'] == 1) {  //心愿墙
                $where['set_user'] = $val['uid'];
                $where['id'] = $val['wish_id'];
                $wall = $Wishwall->where($where)->field('is_recom,kid,chk_status,is_hot,chk_admin,chk_time,give_mon,set_user,cycle', true)->find();
                $ground = $background->where(array('id'=>$wall['background']))->find();
                switch ($val['class']) {
                    case "1"; //发布
                        $data[$key] = $wall;
                        $data[$key]['nickName'] = $userinfo['nickname'];
                        $data[$key]['gender'] = $userinfo['gender'];
                        $data[$key]['avatar'] = $userinfo['avatar'];
                        $data[$key]['font_color'] = $ground['font_color'];
                        $data[$key]['large_img'] = $ground['large_img'];
                        $data[$key]['status'] = '发布心愿';
                        break;
                }
                $data[$key]['type'] = 0;
            } elseif ($val['type'] == 2) {
                $where['post_uid'] = $userid;
                $where['id'] = $val['wish_id'];
                $help = $Wishhelp->field('post_uid,set_gender',true)->where($where)->find();
                switch ($val['class']) {
                    case "1"; //发布
                        $data[$key] = $help;
                        $data[$key]['nickName'] = $userinfo['nickname'];
                        $data[$key]['gender'] = $userinfo['gender'];
                        $data[$key]['avatar'] = $userinfo['avatar'];
                        $data[$key]['status'] = '发布心愿';
                        break;
                    case "3"; //报名
                        $data[$key] = $help;
                        $data[$key]['posttime'] =$help['post_time'];
                        $data[$key]['nickName'] = $userinfo['nickname'];
                        $data[$key]['gender'] = $userinfo['gender'];
                        $data[$key]['avatar'] = $userinfo['avatar'];
                        $data[$key]['status'] = '报名心愿';
                        break;
                }
                $data[$key]['type'] = 1;
            }
        }
        $results['dynamic'] = $data;
        return ($results);

    }



    //设置支付密码 by kar1
    public function setPayPassword()
    {
        if (IS_POST) {
            $User = M('User');
            $uid = session('uid');
            $pwd = I('post.pwd');
            $againPwd = I('post.againPwd');
            if (empty($pwd)) {
                $redata = array(
                    'status' => 3,
                    'msg' => '请输入密码！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }
            if ($pwd != $againPwd) {
                $redata = array(
                    'status' => 4,
                    'msg' => '两次密码不一致！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            } elseif (strlen($pwd) < 6 || strlen($pwd) > 16) {
                $redata = array(
                    'status' => 5,
                    'msg' => '密码长度必须在6~16位长度！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }
            $user = $User->where(array('id' => $uid))->find();
            if ($user['pay_pwd']) {
                $redata = array(
                    'status' => 1,
                    'msg' => '已有支付密码！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }
            $save = array(
                'pay_pwd' => $this->md5pw($pwd),
            );
            $res = $User->where(array('id' => $uid))->data($save)->save();
            if ($res) {
                $redata = array(
                    'status' => 0,
                    'msg' => '修改成功！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            } else {
                $redata = array(
                    'status' => 2,
                    'msg' => '修改失败！',
                    'list' => '',
                );
                echo json_encode($redata);
                exit();
            }
        }
        $this->display();
    }

    //个人信息
    public function userInfo()
    {
        $User = M('User');
        $uid = I('post.userId', '', 'int');

        $udata = $User->find($uid);
        if (empty($udata)) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '没有该用户';
            dexit($results);
            exit;
        }
        //接受到图片的base64码 描述
        $img = I('post.userAvatar');
        $img=str_ireplace(' ','+',$img);   //图片base64码
        $nickname = I('post.nickName', '0', 'htmlspecialchars');
        $gender = I('post.gender');
        $signature = I('post.signature');
        $age = I('post.age');
        //判断是否有数据
        if ($img) {
            //截取信息
            if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)) {
                //随机名字
                $filName = uniqid();
                //保存路径
                $savepath = "./Uploads/userAvatar/" . Date("Y") . '/' . Date("m") . '/' . Date("d") . '/';
                //判断路径是否存在
                if (!is_dir($savepath)) {
                    //创建文件夹
                    mkdir($savepath, 0777, true);
                }
                //图片完整路径
                $savePath = "/Uploads/userAvatar/" . Date("Y") . '/' . Date("m") . '/' . Date("d") . '/';
                //将图片源写入保存
                if (file_put_contents($savepath . $filName . '.jpeg', base64_decode(str_replace($result[1], '', $img)))) {

                    oss_upload($savepath . $filName . '.jpeg');
                    //保存头像
                    $data['avatar'] = get_url($savePath . $filName . '.jpeg');//这个是图片数据库保存的路径
                }
            }
        }
        if ($nickname) {
            $data['nickname'] = $nickname;
        }
        if ($signature) {
            $data['signature'] = $signature;
        }
        $data['gender'] = $gender;
        $data['age'] = $age;
        $data['modify_gender'] = 1;
        if(!$data){
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '没有数据';
            dexit($results);
        }
        $res = $User->where(array('id' => $uid))->data($data)->save();
        if ($res) {
            //查找用户信息
            $field = 'password,pay_pwd,true_msg,ext_msg';
            $userInfo = $User->field($field,true)->where(array('id' => $uid))->find();

            $user=array();
            $user['uid'] =(int)$userInfo['id'];
            $user['nickName'] =$userInfo['nickname'];
            $user['avatar'] =$userInfo['avatar'];
            $user['gender'] =(int)$userInfo['gender'];
            $user['age'] =(int)$userInfo['age'];
            $user['hasModifyGender'] =!$userInfo['modify_gender']?false:true;
            $user['credit'] =(int)$userInfo['credit'];
            $user['totalMon'] =(int)$userInfo['totalmon'];
            $user['regTime'] =(int)$userInfo['reg_time'];
            $user['isAuth'] =!$userInfo['is_auth']?false:true;
            $user['phone'] =$userInfo['phone']?$userInfo['phone']:null;
            $user['signature'] =$userInfo['signature'];
            $user['payPwd'] = !$userInfo['pay_pwd']?false:true;
            $user['wechatOpenid'] = !$userInfo['wechat_openid']?false:$userInfo['wechat_openid'];
            $user['wechatNickname'] = !$userInfo['wechat_nickname']?null:$userInfo['wechat_nickname'];
            $user['qqOpenid'] = !$userInfo['qq_openid']?false:$userInfo['qq_openid'];
            $user['weiboOpenid'] = !$userInfo['weibo_openid']?false:$userInfo['weibo_openid'];
            $user['bindFlag'] = !$userInfo['bind_flag']?false:true;
            $user['isRegister']=true;

            $results['status'] = 0;
            $results['user'] = $user;
            $results['msg'] = '设置成功';
            dexit($results);
            exit;
        } else {
            $results['status'] = 0;
            $results['msg'] = '保存成功';
            dexit($results);
            exit;
        }

    }


    //实名认证页面

    public function nameVerify()
    {
        $User = M('User');
        $UserReal = M('UserReal');
        $uid = I('get.uid', '', 'int');


        if(!$uid){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不完整';
            dexit($results);
            exit;
        }
        $field = 'realname,idcard,full_photo,back_photo,hand_photo,is_auth';
        $real = $UserReal->where(array('uid' => $uid))->field($field)->find();

        if (!empty($real)) {
            $results['status'] = 0;
            $real['fullPhoto']=$real['full_photo'];
            $real['backPhoto']=$real['back_photo'];
            $real['handPhoto']=$real['hand_photo'];
            $real['realName']=$real['realname'];
            $real['isAuth']=(int)$real['is_auth'];
            $real['idCard']=(int)$real['idcard'];
            unset($real['full_photo']);
            unset($real['back_photo']);
            unset($real['hand_photo']);
            unset($real['is_auth']);
            unset($real['idcard']);
            unset($real['realname']);
            $results['data'] = $real;
            dexit($results);
            exit;
        } else {
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '还没进行实名认证';
            dexit($results);
            exit;
        }
    }

    /**
     * 提交实名资料
     */
    public function post_nameVerify()
    {
        $User = M('User');
        $UserReal = M('UserReal');

       $uid = I('post.uid', '', 'int');
        $realname = I('post.realName');
        $idcard = I('post.idCard');
        $full = I('post.fullPhoto', 'htmlspecialchars');
        $full = str_ireplace(' ', '+', $full);   //图片base64码
        $back = I('post.backPhoto', 'htmlspecialchars');
        $back = str_ireplace(' ', '+', $back);   //图片base64码
        $hand = I('post.handPhoto', 'htmlspecialchars');
        $hand = str_ireplace(' ', '+', $hand);   //图片base64码*/

        if (empty($uid) || empty($realname) || empty($idcard) || empty($full) || empty($back) || empty($hand)) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不完整';
            dexit($results);
            exit;
        }

        $user = $User->where(array('id' => $uid))->find();
        $real = $UserReal->where(array('uid' => $uid))->find();

        if (empty($user)) {
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '没有该用户';
            dexit($results);
            exit;
        }
        if($real['is_auth'] == 3){
            $results['status'] = 1;
            $results['errcode'] = 3;
            $results['msg'] = '实名认证正在审核中...';
            dexit($results);
            exit;
        }
        if($real['is_auth'] == 1){
            $results['status'] = 1;
            $results['errcode'] = 4;
            $results['msg'] = '实名认证已通过，不能再提交信息';
            dexit($results);
            exit;
        }

        $full_photo = $this->VerifyPhoto($full);
        $hand_photo = $this->VerifyPhoto($back);
        $back_photo = $this->VerifyPhoto($hand);
        if (!$full_photo || !$hand_photo || !$back_photo) {
            $results['status'] = 1;
            $results['errcode'] = 5;
            $results['msg'] = '图片上传失败';
            dexit($results);
            exit();
        }


        $data = array(
            'uid' => $user['id'],
            'realname' => $realname,
            'full_photo' => $full_photo,
            'back_photo' => $back_photo,
            'hand_photo' => $hand_photo,
            'idcard' => $idcard,
            'is_auth' => 3,
            'create_time' => time()
        );
        $m=M();
        $m->startTrans();
        if (!empty($real)) {
            $result = $UserReal->where(array('id' => $real['id']))->data($data)->save();
        } else {
            $result = $UserReal->data($data)->add();
        }

       // $saveUser = $User->where(array('id' => $real['id']))->data(array('is_auth'=>3))->save();

        if (!$result) {
            $m->rollback();
            $results['status'] = 1;
            $results['errcode'] = 6;
            $results['msg'] = '保存数据失败';
            dexit($results);
            exit;
        } else {
            $m->commit();
            $results['status'] = 0;
            $results['data'] = $real;
            $results['msg'] = '提交成功，等待审核';
            dexit($results);
            exit;
        }
    }

    /**
     * @param $photo
     * @return string
     */
    public function VerifyPhoto($photo){
        $savepath = "./Uploads/nameVerify/" . Date("Y") . '/' . Date("m") . '/' . Date("d") . '/';
        //截取信息
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $photo, $result)) {  //正面

            //随机名字
            $filName = uniqid();
            //判断路径是否存在
            if (!is_dir($savepath)) {
                //创建文件夹
                mkdir($savepath, 0777, true);
            }
            $savePath = "/Uploads/nameVerify/" . Date("Y") . '/' . Date("m") . '/' . Date("d") . '/';

            //将图片源写入保存
            if (file_put_contents($savepath . $filName . '.jpeg', base64_decode(str_replace($result[1], '', $photo)))) {

                //保存
                oss_upload($savepath . $filName . '.jpeg');
                $photoAddress = get_url($savePath . $filName . '.jpeg');//这个是图片数据库保存的路径
            }
        }
        return($photoAddress);
    }

    /*
     * 关注
     * by karl
     * 2017-03-30 17:36:42
     */
    public function attention()
    {

        $UserRelation = M('UserRelation');

        $uid = I('post.uid', '0', 'int');
        $fan_uid = I('post.fan_uid', '0', 'int');

        //判断提交信息
        if (!$uid || !$fan_uid) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '信息不完整';
            dexit($results);
            exit();
        }

        //判断是否已关注
        $where = array();
        $where['uid'] = $fan_uid;
        $where['fan_uid'] = $uid;
        $relation = $UserRelation->where($where)->find();
        if ($relation['status'] == 1) {
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '已关注';
            dexit($results);
            exit();
        }

        //根据数据写入或者更新
        $time = time();
        if ($relation) {
            $data = array();
            $data['status'] = 1;
            $data['attention_time'] = $time;
            if ($UserRelation->where(array('id' => $relation['id']))->save($data)) {//更新
                $results['status'] = 0;
                $results['msg'] = '关注成功';
                dexit($results);
                exit();
            }
            $results['status'] = 1;
            $results['errcode'] = 3;
            $results['msg'] = '更新失败';
            dexit($results);
            exit();
        } else {
            $data = array();
            $data['uid'] = $fan_uid;
            $data['fan_uid'] = $uid;
            $data['status'] = 1;
            $data['attention_time'] = $time;
            if ($UserRelation->add($data)) {//添加
                $results['status'] = 0;
                $results['msg'] = '关注成功';
                dexit($results);
                exit();
            }
            $results['status'] = 1;
            $results['errcode'] = 4;
            $results['msg'] = '添加失败';
            dexit($results);
            exit();
        }

        $results['status'] = 1;
        $results['errcode'] = 5;
        $results['msg'] = '执行失败';
        dexit($results);
        exit();

    }

    /*
    * 取消关注
    * by karl
    * 2017-03-30 17:36:42
    */
    public function unAttention()
    {

        $UserRelation = M('UserRelation');

        $uid = I('post.uid', '0', 'int');
        $fan_uid = I('post.fan_uid', '0', 'int');

        //判断提交信息
        if (!$uid || !$fan_uid) {
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '信息不完整';
            dexit($results);
            exit();
        }

        //判断是否已关注
        $where = array();
        $where['uid'] = $fan_uid;
        $where['fan_uid'] = $uid;
        $relation = $UserRelation->where($where)->find();
        if (!$relation) {
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '未关注';
            dexit($results);
            exit();
        }
        if ($relation['status'] == 0) {
            $results['status'] = 1;
            $results['errcode'] = 3;
            $results['msg'] = '已取消关注';
            dexit($results);
            exit();
        }

        //更新
        $time = time();
        $data = array();
        $data['status'] = 0;
        $data['unattention_time'] = $time;
        if ($UserRelation->where(array('id' => $relation['id']))->save($data)) {//更新
            $results['status'] = 0;
            dexit($results);
            exit();
        }
        $results['status'] = 1;
        $results['errcode'] = 4;
        $results['msg'] = '操作失败';
        dexit($results);
        exit();
    }

    /*
     * 粉丝 关注 列表
     * by karl
     * time:2017-03-30 18:13:56
     */
    public function fansList()
    {
        $UserRelation = M('UserRelation');
        $uid = I('get.uid', '0', 'int');
        $oid = I('get.oid', '0', 'int');
        $page = I('get.page', '0', 'int');
        $type = I('get.type', '0', 'int');//0关注，1粉丝



        if (!$uid && !$oid) {
            $results['status'] = 1;
            $results['errcode'] = 1;//信息不完整
            dexit($results);
            exit();
        }
        if (!empty($oid) && !empty($uid)) {
            if ($type == 0) {
                $fans = $UserRelation->alias('a')->field('a.status,b.id,b.nickName,b.avatar,b.signature,b.is_auth as isAuth,b.credit,b.totalmon,b.gender,b.phone')->join('wxy_user as b ON a.uid = b.id', 'LEFT')->where(array('fan_uid' => $oid, 'a.status' => 1))->page($page, 10)->select();
                foreach ($fans as $k => $v) {
                    $fans[$k]['gender']=(int)$v['gender'];
                    $fans[$k]['uid']=(int)$v['id'];
                    $fans[$k]['isAuth']=(int)$v['isAuth'];
                    $fans[$k]['phone']=(int)$v['phone'];
                    $fans[$k]['status']=(int)$v['status'];
                    unset($fans[$k]['id']);
                    $is_attention = $UserRelation->where(array('fan_uid' => $uid, 'uid' => $v['id'], 'status' => 1))->find();
                    if($uid == $v['id']){
                        $fans[$k]['isFollow'] = true;
                    }
                    $fans[$k]['isFollow'] = !$is_attention ? false : true;
                }
                $count = $UserRelation->alias('a')->join('wxy_user as b ON a.uid = b.id', 'LEFT')->where(array('fan_uid' => $oid, 'a.status' => 1))->count();
                $countPage = ceil($count / 30);
            } elseif ($type == 1) {
                $fans = $UserRelation->alias('a')->field('a.status,b.id,b.nickName,b.avatar,b.signature,b.is_auth as isAuth,b.credit,b.totalmon,b.gender,b.phone')->join('wxy_user as b ON a.fan_uid = b.id', 'LEFT')->where(array('uid' => $oid, 'a.status' => 1))->page($page, 10)->select();
                foreach ($fans as $k => $v) {
                    $fans[$k]['gender']=(int)$v['gender'];
                    $fans[$k]['uid']=(int)$v['id'];
                    $fans[$k]['isAuth']=(int)$v['isAuth'];
                    $fans[$k]['phone']=(int)$v['phone'];
                    $fans[$k]['status']=(int)$v['status'];
                    unset($fans[$k]['id']);
                    if($uid == $v['id']){
                        $fans[$k]['isFollow'] = true;
                    }
                    $is_attention = $UserRelation->where(array('fan_uid' => $uid, 'uid' => $v['id'], 'status' => 1))->find();
                    $fans[$k]['isFollow'] = !$is_attention ? false : true;
                }
                $count = $UserRelation->alias('a')->join('wxy_user as b ON a.fan_uid = b.id', 'LEFT')->where(array('uid' => $oid, 'a.status' => 1))->count();
                $countPage = ceil($count / 30);
            }
        } elseif (!empty($uid)) {
            if ($type == 0) {
                $fans = $UserRelation->alias('a')->field('a.status,b.id,b.nickName,b.avatar,b.signature,b.is_auth as isAuth,b.gender,b.phone')->join('wxy_user as b ON a.uid = b.id', 'LEFT')->where(array('fan_uid' => $uid, 'a.status' => 1))->page($page, 10)->select();
                foreach ($fans as $k => $v) {
                    $fans[$k]['gender']=(int)$v['gender'];
                    $fans[$k]['uid']=(int)$v['id'];
                    $fans[$k]['isAuth']=(int)$v['isAuth'];
                    $fans[$k]['phone']=(int)$v['phone'];
                    $fans[$k]['status']=(int)$v['status'];
                    unset($fans[$k]['id']);
                    $fans[$k]['isFollow'] = true;
                }
                $count = $UserRelation->alias('a')->join('wxy_user as b ON a.uid = b.id', 'LEFT')->where(array('fan_uid' => $uid, 'a.status' => 1))->count();
                $countPage = ceil($count / 30);
            } elseif ($type == 1) {
                $fans = $UserRelation->alias('a')->field('a.status,b.id,b.nickName,b.avatar,b.signature,b.is_auth as isAuth,b.gender,b.phone')->join('wxy_user as b ON a.fan_uid = b.id', 'LEFT')->where(array('uid' => $uid, 'a.status' => 1))->page($page, 10)->select();
                foreach ($fans as $k => $v) {
                    $fans[$k]['gender']=(int)$v['gender'];
                    $fans[$k]['uid']=(int)$v['id'];
                    $fans[$k]['isAuth']=(int)$v['isAuth'];
                    $fans[$k]['phone']=(int)$v['phone'];
                    $fans[$k]['status']=(int)$v['status'];
                    unset($fans[$k]['id']);
                    $is_attention = $UserRelation->where(array('fan_uid' => $uid, 'uid' => $v['id'], 'status' => 1))->count();
                    $fans[$k]['isFollow'] = !$is_attention ? false : true;
                }
                $count = $UserRelation->alias('a')->join('wxy_user as b ON a.fan_uid = b.id', 'LEFT')->where(array('uid' => $uid, 'a.status' => 1))->count();
                $countPage = ceil($count / 30);
            }
        }

        $results['status'] = 0;
        $results['msg'] = '请求成功';
        $results['type'] = $type;
        $results['countPage'] = $countPage;
        $results['data'] = $fans;
        dexit($results);
    }

    public function allWishWall(){
        $user = M('User');
        $model = M('Wishwall');
        $background = M('WishwallBackground');

        //自己的id
        $myid = I('post.uid', '', 'int');

        //用户id
        $oid=I('post.oid','','int');


        //$oid为空的话就把$myid赋值给$oid
        if(empty($oid)){$oid =$myid;}
       if(!$oid){
            $results['status']=1;
            $results['error']=1;
            $results['msg']='参数不完整';
            dexit($results);
        }

        if($_POST['oid']){
            $datas =$model->where(array('set_user'=>$oid,'kid <> 1'))->field('id,content,light_count,background,set_user,kid,posttime,FROM_UNIXTIME(posttime,"%Y") as year,FROM_UNIXTIME(posttime,"%m") as month')->order('posttime desc')->select();

        }else{
            $datas =$model->where(array('set_user'=>$oid))->field('id,content,light_count,background,set_user,kid,posttime,FROM_UNIXTIME(posttime,"%Y") as year,FROM_UNIXTIME(posttime,"%m") as month')->order('posttime desc')->select();
        }

        foreach ($datas as $key=>$val){
            //用户信息
            $userInfo = $user->where(array('id'=>$val['set_user']))->find();
            //背景信息
            $ground = $background->field('font_color,large_img')->where(array('id'=>$val['background']))->find();
            //评论总人数
            $count=M('Comment')->where('xid='.$val['id'])->count();
            //是否点亮
            $islight = M('Wishwall_join')->field('join_uid')->where(array('wish_id=' . $val['id'], 'join_uid=' . $oid))->find();
            if ($islight) {
                $datas[$key]['isLight'] = TRUE;
            } else {
                $datas[$key]['isLight'] = FALSE;
            }
            $subclass=M('Wishwall_keyword')->field('subclass')->where('id='.$val['kid'])->find();
            if($subclass['subclass'] == '0'){
                $newlight[$key]['nickName']=$userInfo['nickname'];
                $newlight[$key]['avatar']=$userInfo['avatar'];
            }else{
                $font=mb_substr($userInfo['nickname'],0,1,'utf-8');
                $avatar=get_url("/Uploads/userAvatar/avatar.png");
                $newlight[$key]['nickName']= $font.'**';
                $newlight[$key]['avatar']=$avatar;
            }
            $datas[$key]['bgImg']=$ground['large_img'];
            $datas[$key]['fontColor']=$ground['font_color'];
            $datas[$key]['gender']=$userInfo['gender'];
            $datas[$key]['uid']=(int)$val['set_user'];
            $datas[$key]['lightCount']=(int)$val['light_count'];
            $datas[$key]['commentCount']=(int)$count;
            $datas[$key]['gender']=(int)$val['gender'];
            $datas[$key]['kid']=(int)$val['kid'];
            $datas[$key]['id']=(int)$val['id'];
            $datas[$key]['posttime']=(int)$val['posttime'];
            $datas[$key]['month']=(int)$val['month'];
            $datas[$key]['year']=(int)$val['year'];
            unset($datas[$key]['light_count']);
            unset($datas[$key]['read_count']);
            unset($datas[$key]['gender']);
            unset($datas[$key]['set_user']);
        }

        foreach ($datas as $k=>$v){
            $data[$v['year']]['month'][$v['month']]['wishList'][] = $v;
            $data[$v['year']]['year']=$v['year'];
            $data[$v['year']]['month'][$v['month']]['text']=$v['month'];
            switch ($v['month']){
                case 12 :
                    $englishText='Dec';
                    $color='#CD4F4D';
                    break;
                case 11 :
                    $englishText='Nov';
                    $color='#F0646A';
                    break;
                case 10 :
                    $englishText='Oct';
                    $color='#DF3D9A';
                    break;
                case 9 :
                    $englishText='Sep';
                    $color='#CA348D';
                    break;
                case 8 :
                    $englishText='Aug';
                    $color='#B83BB7';
                    break;
                case 7 :
                    $englishText='Jul';
                    $color='#C55EF1';
                    break;
                case 6 :
                    $englishText='Jun';
                    $color='#9061D9';
                    break;
                case 5 :
                    $englishText='May';
                    $color='#5661DC';
                    break;
                case 4 :
                    $englishText='Apr';
                    $color='#60ceff';
                    break;
                case 3 :
                    $englishText='Mar';
                    $color='#6E9749';
                    break;
                case 2 :
                    $englishText='Feb';
                    $color='#FA6D49';
                    break;
                case 1 :
                    $englishText='Jan';
                    $color='#EC5A63';
                    break;
            }
            $data[$v['year']]['month'][$v['month']]['englishText']=$englishText;
            $data[$v['year']]['month'][$v['month']]['color']=$color;
        }
        foreach ($data as $key => $val){
            $Data[]=$val;
        }
        foreach ($Data as $key => $val){
            foreach ($val['month'] as $k=>$v){
                $newData[$key]['month'][]=$v;
                $newData[$key]['year']=$Data[$key]['year'];
            }
        }
        $results['status'] = 0;
        $results['msg'] = '请求成功';
        $results['data'] = $data;
        dexit($newData);
    }

}

