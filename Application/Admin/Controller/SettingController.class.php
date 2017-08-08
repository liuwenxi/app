<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class SettingController extends AdminController{
    private $mode_table = 'setting';
    public function index(){
        $setting = M($this->mode_table)->find();
        
        $this->assign('data',$setting);
        $this->display();
    }
    
    public function edit(){
        if(IS_POST){
            $data = I('post.');
            if(empty($data)){
                $this->error = '数据创建失败！';
                return false;
            }
            
            foreach ($data as $k=>$v){
                $currentData = array(
                    $k => $v,
                );
                M($this->mode_table)->data($currentData)->where(array('id' => 1))->save();
            }           
                
            $this->success('修改成功');
        }
    }
    
    public function testSendEmail(){
        $toemail = I('post.toemail');
        if(!empty($toemail)){
           $result = $this->send_mail($toemail, $toemail, $subject = '邮箱配置测试', $body = '这是邮箱测试邮件，如果您收到了该邮件，说明您的邮箱配置信息已经完全，现在可以配置您的管理员邮箱了', $attachment = null); 
           if($result){
               echo json_encode(1);
           }
        }else{
            echo json_encode(0);
        }
        
    }
}

