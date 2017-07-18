<?php
namespace Api\Controller;
use Common\Controller\BaseController;

class AboutsController extends BaseController
{

    public function _initialize()
    {
        header('Content-type:text/html;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
    }

   public function submitFeedback(){
        $feedback = M('Feedback');
        $content = I('content');
        if(!$content){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '内容不能为空';
            dexit($results);
            exit;
        }
        $data =array(
            'content'=>$content,
            'posttime'=>time(),
        );
        $add = $feedback->add($data);
        if($add){
            $results['status'] = 0;
            $results['msg'] = '反馈成功';
            dexit($results);
        }else{
            $results['status'] = 1;
            $results['errcode'] = 2;
            $results['msg'] = '反馈失败';
            dexit($results);
        }
   }

    public function queryUserGuide()
    {
        $qa = M('Qa');
        $type = I('post.module');
        if(!$type){
            $results['status'] = 1;
            $results['errcode'] = 1;
            $results['msg'] = '参数不全';
            dexit($results);
            exit;
        }
        $data = $qa->where(array('type'=>$type))->select();
        $results['status'] = 0;
        $results['data'] = $data;
        dexit($results);
    }

    public function queryAbout()
    {
        $setting = M('Setting');
        $data = $setting->field('about_des')->find();
        $results['status'] = 0;
        $results['data'] = $data;
        dexit($results);

    }

    public function queryTerms(){
        $setting = M('Setting');
        $data = $setting->field('tk_des')->find();
        $results['status'] = 0;
        $results['data'] = $data;
        dexit($results);
    }

    public function queryContact(){
        $setting = M('Setting');
        $datas = $setting->field('about_des,tk_des',true)->find();
        $QQ = array('name'=>'QQ','content'=>$datas['qq']);
        $email = array('name'=>'邮箱','content'=>$datas['email']);
        $coop_email = array('name'=>'品牌合作','content'=>$datas['coop_email']);
        $coop_qq = array('name'=>'媒体合作','content'=>$datas['coop_qq']);
        $tg_email = array('name'=>'渠道推广','content'=>$datas['tg_email']);
        $data = array($QQ,$email,$coop_email,$coop_qq,$tg_email);
        $results['status'] = 0;
        $results['data'] = $data;
        dexit($results);
    }

}
