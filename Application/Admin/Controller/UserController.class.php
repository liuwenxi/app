<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 登录页面的视图加载与登录验证，登出方法与验证码生成的方法
 */
class UserController extends Controller
{

    public function index()
    {
        // code...
        $this->display();
    }

    public function login()
    {
        $Admin = M('Admin');
        
        $uname = I('post.uname');
        $pwd = md5(I('post.password'));
        $remember = empty($_POST['remember']) ? '' : $_POST['remember'];
        
        // 验证验证码是否正确
        $code = $_POST['code'];
        $verify = new \Think\Verify();
        $vfresult = $verify->check($code);
        // if ($vfresult != true) {
            // $this->error('验证码有误，请重新输入');
        // }
        
        $map = array('username'=>$uname, 'password'=>$pwd );
        $result = $Admin->where($map)->find();

        if (empty($result)) {
            $this->error('Username或者Password错误，请重新输入......', U('Index/index'));
        }
        $_SESSION['adminid'] = $result['id'];
        $_SESSION['uname'] = $result['uname'];
        
        $data = array(
            'last_time' => time(),
            'last_ip' => get_client_ip(),
        );
        
        $Admin->where(array('id' => $result['id']))->save($data);
        
        if ($remember) {
            cookie('username', $map['name'], 60 * 60 * 24 * 7);
            cookie('password', $map['password'], 60 * 60 * 24 * 7);
            cookie('remember', $remember, 60 * 60 * 24 * 7);
        } else {
            cookie('username', null);
            cookie('password', null);
            cookie('remember', null);
        }
        $this->success($map['username'] . '成功登陆，正在跳转......', U('Index/index'));
    }

    public function logout()
    {
        unset($_SESSION['adminid']);
//         unset($_SESSION['uid']);
        $this->success('用户安全退出中......', U('Index/index'));
    }
    
    public function chgPwd(){
        $Admin = M('Admin');
        $Rule = M('Rule');
        
        $uid = session('adminid');
        $adata = $Admin->find($uid);
        
        if (IS_POST){
            $old_pwd = I('post.oldpwd','0','string');
            $new_pwd = I('post.newpwd','0','string');
            if ($adata['password'] != md5($old_pwd)){
                $this->error('原密码有误！请重新输入！');
            }else {
                $data = array(
                    'password' => md5($new_pwd),
                );
                $res = $Admin->where(array('id' => $uid))->data($data)->save();
                if ($res){
                    $this->success('修改成功，请重新登录！', U('User/index'));
                }else {
                    $this->error('修改错误！请重新修改！');
                }
            }
        }else {
            //判断管理员是否有权限访问和可访问的目录
            $rdata = $Rule->where(array('is_show' => 1))->select();
            
            $acate = $adata['admin_cate'] = unserialize($adata['admin_cate']);
            $ctemp = explode(',', $acate);
            foreach ($rdata as $k=>$v){
                if (in_array($v['id'], $ctemp)){
                    $tarr[$k]['id'] = $v['id'];
                    $tarr[$k]['rname'] = $v['rname'];
                    $tarr[$k]['rule_url'] = "http://".$_SERVER['HTTP_HOST'].$v['rule_url'];
                }
            }
            
            $this->assign('cate', $tarr);
            $this->display();
        }
        
    }

    public function vfauto()
    {
        // 生成验证码
        $Verify = new \Think\Verify();
        $Verify->entry();
    }
}

?>