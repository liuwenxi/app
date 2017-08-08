<?php
namespace Admin\Controller;

use Admin\Controller\AdminController;
use Lib\Api\Weixinapi\WeiXin;
class WechatController extends AdminController {
    
    public function index(){

    }

    //微信菜单列表
    public function menu(){

        $Wechat_menu = M('wechat_menu');

        $data = $Wechat_menu->order('sort asc')->select();

        if(!empty($data)){
            foreach($data as $key => $val){
                if($val['pid']==0){
                    foreach($data as $k => $v){
                        if($v['pid']==$val['id']){
                            $data[$key]['son'][]=$v;
                        }
                    }
                }else{
                    unset($data[$key]);
                }
            }
        }
        $this->assign('class',$data);
        $this->display();
    }

    //添加微信菜单
    public function menu_add(){

        $Wechat_menu = M('wechat_menu');

        if($_POST){

            $data=array();
            $post = I('post.');

            if($post['pid']==0){
                $count= $Wechat_menu->where(array('pid'=>0))->count();
                if($count==3){
                    $this->error("父类已经大于3个");exit();
                }
            }
            if($post['pid']>0){
                $count= $Wechat_menu->where(array('pid'=>$post['pid']))->count();
                if($count==5){
                    $this->error("子类已经大于5个");exit();
                }
            }


            $data['title']=$post['title'];
            $data['menu_type']=$post['menu_type'];
            $data['keyword']=$post['keyword'];
            $data['url']=$post['url'];
            if($data['url']){
                if(!strstr($data['url'],"http://")){
                    $data['url']="http://".$data['url'];
                }
            }
            $data['pid']=$post['pid'];
            $data['sort']=$post['sort'];

            if($data['menu_type']==1){
                $data['url']='';
            }elseif($data['menu_type']==2){
                $data['keyword']='';
            }

            if($Wechat_menu->add($data)){
                $this->success("添加成功");exit();
            }
            $this->error("添加失败");exit();

        }
        $class= $Wechat_menu->where(array('pid'=>0))->select();

        $this->assign('class',$class);
        $this->display();
    }

    //删除微信菜单
    public function menu_del(){
        $Wechat_menu = M('wechat_menu');

        if($_POST){

            $post = I('post.');

            if($post['pid']==0){
                $count= $Wechat_menu->where(array('pid'=>$post['id']))->count();
                if($count>0){
                    dexit(array('error' => 0,'msg' => '该类有子类，请先删除子类'));exit();
                }
            }
            if($Wechat_menu->where(array("id"=>$post['id']))->delete()){
                dexit(array('error' => 0,'msg' => '操作成功'));exit();
            }

            dexit(array('error' => 1,'msg' => '操作失败'));exit();

        }

    }

    //修改微信菜单
    public function menu_edit(){

        $Wechat_menu = M('wechat_menu');

        if($_POST){

            $data=array();
            $post = I('post.');
            $id=$post['id'];

            $data['title']=$post['title'];
            $data['menu_type']=$post['menu_type'];
            $data['keyword']=$post['keyword'];
            $data['url']=$post['url'];
            if($data['url']){
                if(!strstr($data['url'],"http://")){
                    $data['url']="http://".$data['url'];
                }
            }
            $data['sort']=$post['sort'];

            if($data['menu_type']==1){
                $data['url']='';
            }elseif($data['menu_type']==2){
                $data['keyword']='';
            }

            if($Wechat_menu->where(array('id'=>$id))->save($data)){
                $this->success("修改成功");exit();
            }
            $this->error("修改失败");exit();

        }
        $id = I('get.id');
        $data = $Wechat_menu->where(array('id'=>$id))->find();
        $class= $Wechat_menu->where(array('pid'=>0))->select();

        $this->assign('show',$data);
        $this->assign('type',$data['menu_type']);
        $this->assign('class',$class);
        $this->display();
    }


    public function menu_send()

    {

        if(IS_GET){

            //微信js配置接口信息
            $WeiChat=new WeiXin();
            $access_token=$WeiChat->openGetAccessToken();

            if (!$access_token) {

                $this->error('获取access_token发生错误');

            }



            $data = '{"button":[';



            $class=M('wechat_menu')->where(array('pid'=>0))->limit(3)->order('sort asc')->select();//dump($class);

            $kcount=M('wechat_menu')->where(array('pid'=>0))->count();

            $k=1;



            foreach($class as $key=>$vo){

                //主菜单

                $data.='{"name":"'.$vo['title'].'",';

                $c=M('wechat_menu')->where(array('pid'=>$vo['id']))->limit(5)->order('sort asc')->select();

                $count=M('wechat_menu')->where(array('pid'=>$vo['id']))->count();

                //子菜单

// 				$vo['url']=str_replace(array('{siteUrl}','&amp;','&wecha_id={wechat_id}'),array($this->siteUrl,'&','&diymenu=1'),$vo['url']);

                if($c!=false){

                    $data.='"sub_button":[';

                }else{

                    if($vo['keyword']){

                        $data.='"type":"click","key":"'.$vo['keyword'].'"';

                    }else if($vo['url']){

                        $data.='"type":"view","url":"'.$vo['url'].'"';

                    }else if($vo['wxsys']){

                        $data.='"type":"'.$this->_get_sys('send',$vo['wxsys']).'","key":"'.$vo['wxsys'].'"';

                    }

                }



                $i=1;

                foreach($c as $voo){

// 					$voo['url']=str_replace(array('{siteUrl}','&amp;','&wecha_id={wechat_id}'),array($this->siteUrl,'&','&diymenu=1'),$voo['url']);

                    if($i==$count){

                        if($voo['keyword']){

                            $data.='{"type":"click","name":"'.$voo['title'].'","key":"'.$voo['keyword'].'"}';

                        }else if($voo['url']){

                            $data.='{"type":"view","name":"'.$voo['title'].'","url":"'.$voo['url'].'"}';

                        }else if($voo['wxsys']){

                            $data.='{"type":"'.$this->_get_sys('send',$voo['wxsys']).'","name":"'.$voo['title'].'","key":"'.$voo['wxsys'].'"}';

                        }

                    }else{

                        if($voo['keyword']){

                            $data.='{"type":"click","name":"'.$voo['title'].'","key":"'.$voo['keyword'].'"},';

                        }else if($voo['url']){

                            $data.='{"type":"view","name":"'.$voo['title'].'","url":"'.$voo['url'].'"},';

                        }else if($voo['wxsys']){

                            $data.='{"type":"'.$this->_get_sys('send',$voo['wxsys']).'","name":"'.$voo['title'].'","key":"'.$voo['wxsys'].'"},';

                        }

                    }

                    $i++;

                }

                if($c!=false){

                    $data.=']';

                }



                if($k==$kcount){

                    $data.='}';

                }else{

                    $data.='},';

                }

                $k++;

            }

            $data.=']}';


// 			show_bug($data);die;


            file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$access_token);

            $url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;


            $rt = $this->apiPost($url,$data);


            if ($rt['rt'] == false) {

                $this->error('操作失败,微信返回错误编号：'.$rt['errorno']);

            }else{

                $this->success('操作成功');

            }

            exit;

        }else{

            $this->error('非法操作');

        }

    }
    function apiPost($url, $data){

        $ch = curl_init();

        $header[] = "Accept-Charset: utf-8";

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $tmpInfo = curl_exec($ch);

        $errorno=curl_errno($ch);

        if ($errorno) {

            return array('rt'=>false,'errorno'=>$errorno);

        }else{

            $js=json_decode($tmpInfo,1);

            if ($js['errcode']=='0'){

                return array('rt'=>true,'errorno'=>0);

            }else {

                $this->error('发生错误：微信返回错误信息：'.$js['errcode']);

            }

        }

    }

    public function curlPost($url, $data)
    {
        $ch = curl_init();
        $headers[] = 'Accept-Charset: utf-8';
        if (class_exists ( '/CURLFile' )) {//php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
            curl_setopt ( $ch, CURLOPT_SAFE_UPLOAD, true );
        } else {
            if (defined ( 'CURLOPT_SAFE_UPLOAD' )) {
                curl_setopt ( $ch, CURLOPT_SAFE_UPLOAD, false );
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);

        if (isset($result['errcode'])) {
            $this->error('发生错误：微信返回错误信息：'.$result['errcode']);

        }else {
            return $result;
        }
    }

    function curlGet($url){

        $ch = curl_init();

        $header = "Accept-Charset: utf-8";

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $temp = curl_exec($ch);

        return $temp;

    }


    //关键词 by karl
    public function other(){

        $list = M("Platform")->select();

        $this->assign('list', $list);

        $this->display();
    }

    //添加关键词
    public function other_add(){

        if($_POST){

            $data = array();

            $data['key'] = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
            $data['type'] = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : '';
            $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
            $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
            $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
            if($data['url'] && $data['type']==2){
                if(!strstr($data['url'],"http://")){
                    $data['url']="http://".$data['url'];
                }
            }
            $data['api_url'] = isset($_POST['api_url']) ? htmlspecialchars_decode($_POST['api_url']) : '';

            if (empty($data['key'])) {
                $this->error('关键词不能为空');
            }
            $date=Date("Y").'/'.Date("m").'/'.Date("d").'/';
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     3145728 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->autoSub      =     false;//不要使用子目录
            $upload->savePath  =      'other/'.$date; // 设置附件上传目录
            $info   =   $upload->upload();
            $path='/Uploads/'.$info['pic']['savepath'].$info['pic']['savename'];
            @unlink('.' .$path);
            $pic_path=get_url($path);

            $data['pic']=$pic_path;

            if(M("Platform")->add($data)){
                $this->success('添加成功');exit();
            }
            $this->error('添加失败');exit();
        }
        $this->display();
    }
    //编辑关键词
    public function other_edit(){

        if($_POST){

            $data = array();

            $data['key'] = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
            $data['type'] = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : '';
            $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
            $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
            $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
            if($data['url'] && $data['type']==2){
                if(!strstr($data['url'],"http://")){
                    $data['url']="http://".$data['url'];
                }
            }
            $data['api_url'] = isset($_POST['api_url']) ? htmlspecialchars_decode($_POST['api_url']) : '';

            if (empty($data['key'])) {
                $this->error('关键词不能为空');
            }
            if(!empty($_FILES['pic']['name'])){
                $date=Date("Y").'/'.Date("m").'/'.Date("d").'/';
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     3145728 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->autoSub      =     false;//不要使用子目录
                $upload->savePath  =      'other/'.$date; // 设置附件上传目录
                $info   =   $upload->upload();
                $path='/Uploads/'.$info['pic']['savepath'].$info['pic']['savename'];
                @unlink('.' .$path);
                $pic_path=get_url($path);

                $data['pic']=$pic_path;
            }


            if(M("Platform")->where(array('id'=>$_POST['id']))->save($data)){
                $this->success('修改成功');exit();
            }
            $this->error('修改失败');exit();
        }
        $id = I('get.id');
        $data = M("Platform")->where(array('id'=>$id))->find();

        $this->assign('info',$data);
        $this->assign('type',$data['type']);
        $this->display();
    }

    //删除关键词
    public function other_del(){
        $Platform = M('Platform');

        if($_POST){

            $post = I('post.');

            if($Platform->where(array("id"=>$post['id']))->delete()){
                dexit(array('error' => 0,'msg' => '操作成功'));exit();
            }

            dexit(array('error' => 1,'msg' => '操作失败'));exit();

        }

    }

    //素材
    public function material(){
        $wechat_img=M('wechat_img');
        //微信js配置接口信息
        $WeiChat=new WeiXin();
        $access_token=$WeiChat->openGetAccessToken();
        $url='https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$access_token;
        $data='{"type":"image","offset":380,"count":20}';
        $rt = $this->curlPost($url,$data);
        if($rt['item']){
            foreach($rt['item'] as $key => $val){
                $dataS['media_id'] = $val['media_id'];
                $dataS['name'] = $val['name'];
                $dataS['update_time'] = $val['update_time'];
                $dataS['url'] = $val['url'];
                $wechat_img->add($dataS);
//                $img=file_get_contents($val['url']);
//                $name=$val['media_id'].".jpg";
//                file_put_contents("./Uploads/material/".$name,$img);
            }
        }
        bug($rt['item']);die;
        $this->display();
    }

    //素材
    public function material_list(){
        $wechat_img=M('wechat_img');
        $list=$wechat_img->select();
        $this->assign('list',$list);
        $this->display();
    }

    //新增素材
    public function material_add(){
        $wechat_img=M('wechat_img');
        if($_POST){

            $data = array();

            $data['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';

            if (empty($data['name'])) {
                $this->error('名称不能为空');
            }
            $date=Date("Y").'/'.Date("m").'/'.Date("d").'/';
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     3145728 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->autoSub      =     false;//不要使用子目录
            $upload->savePath  =      'material/'.$date; // 设置附件上传目录
            $info   =   $upload->uploadLoc();
            $path='/Uploads/'.$info['pic']['savepath'].$info['pic']['savename'];

            $data['path']=$path;
            $data['update_time']=time();

            $img_id=$wechat_img->add($data);
            if($img_id){
                $img_info = getimagesize($_SERVER['DOCUMENT_ROOT'].$path);
                $img_size = ceil(filesize($_SERVER['DOCUMENT_ROOT'].$path)); //获取文件大小
                $file_info=array(
                    'filename'=>$path,  //国片相对于网站根目录的路径
                    'content-type'=>$img_info['mime'],  //文件类型
                    'filelength'=>$img_size        //图文大小
                );
                $WeiChat=new WeiXin();
                $access_token=$WeiChat->openGetAccessToken();
                $url="https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$access_token}&type=image";
                $real_path="{$_SERVER['DOCUMENT_ROOT']}{$file_info['filename']}";
                //$real_path=str_replace("/", "\\", $real_path);
                $data= array("media"=>"@{$real_path}",'form-data'=>$file_info);
                $result=$this->curlPost($url,$data);
                if($result['media_id']){
                    $wechat_img->where(array('id'=>$img_id))->save(array("url"=>$result['url'],'media_id'=>$result['media_id']));
                    $this->success('添加成功');exit();
                }
                $wechat_img->where(array('id'=>$img_id))->delete();
                $this->error('添加失败');exit();
            }
            $this->error('添加失败');exit();
        }
        $this->display();
    }

    //文本关键字
    public function text(){
        $list = M("Keyword")->select();

        $this->assign('list', $list);

        $this->display();
    }

    //添加关键字
    public function text_add(){

        if($_POST){

            $data = array();

            $data['key'] = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
            $data['type'] = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : '';
            $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
            $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
            $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
            if($data['url'] && $data['type']==2){
                if(!strstr($data['url'],"http://")){
                    $data['url']="http://".$data['url'];
                }
            }
            $data['api_url'] = isset($_POST['api_url']) ? htmlspecialchars_decode($_POST['api_url']) : '';

            if (empty($data['key'])) {
                $this->error('关键词不能为空');
            }
            $date=Date("Y").'/'.Date("m").'/'.Date("d").'/';
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize   =     3145728 ;// 设置附件上传大小
            $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->autoSub      =     false;//不要使用子目录
            $upload->savePath  =      'other/'.$date; // 设置附件上传目录
            $info   =   $upload->upload();
            $path='/Uploads/'.$info['pic']['savepath'].$info['pic']['savename'];
            @unlink('.' .$path);
            $pic_path=get_url($path);

            $data['pic']=$pic_path;

            if(M("Keyword")->add($data)){
                $this->success('添加成功');exit();
            }
            $this->error('添加失败');exit();
        }
        $this->display();
    }

    //编辑关键词
    public function text_edit(){

        if($_POST){

            $data = array();

            $data['key'] = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
            $data['type'] = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : '';
            $data['title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
            $data['info'] = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : '';
            $data['content'] = isset($_POST['content']) ? $_POST['content'] : '';
            $data['url'] = isset($_POST['url']) ? htmlspecialchars_decode($_POST['url']) : '';
            if($data['url'] && $data['type']==2){
                if(!strstr($data['url'],"http://")){
                    $data['url']="http://".$data['url'];
                }
            }
            $data['api_url'] = isset($_POST['api_url']) ? htmlspecialchars_decode($_POST['api_url']) : '';

            if (empty($data['key'])) {
                $this->error('关键词不能为空');
            }
            if(!empty($_FILES['pic']['name'])){
                $date=Date("Y").'/'.Date("m").'/'.Date("d").'/';
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     3145728 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->autoSub      =     false;//不要使用子目录
                $upload->savePath  =      'other/'.$date; // 设置附件上传目录
                $info   =   $upload->upload();
                $path='/Uploads/'.$info['pic']['savepath'].$info['pic']['savename'];
                @unlink('.' .$path);
                $pic_path=get_url($path);

                $data['pic']=$pic_path;
            }


            if(M("Keyword")->where(array('id'=>$_POST['id']))->save($data)){
                $this->success('修改成功');exit();
            }
            $this->error('修改失败');exit();
        }
        $id = I('get.id');
        $data = M("Keyword")->where(array('id'=>$id))->find();

        $this->assign('info',$data);
        $this->assign('type',$data['type']);
        $this->display();
    }

    //删除关键词
    public function text_del(){
        $Platform = M('keyword');

        if($_POST){

            $post = I('post.');

            if($Platform->where(array("id"=>$post['id']))->delete()){
                dexit(array('error' => 0,'msg' => '操作成功'));exit();
            }

            dexit(array('error' => 1,'msg' => '操作失败'));exit();

        }

    }
    //关注回复
    public function subscribe(){
        $info=M('config')->find();
        if($_POST){
            $content = I('post.content');
            $data['value']=$content;
            M('config')->where('id=1')->save($data);
            $this->success('操作成功');exit();
        }
        $this->assign('info',$info);
        $this->display();
    }
}