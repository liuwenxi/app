<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;

class StatisticalController extends AdminController{
    private $mode_table = 'setting';
    public function index(){
		
		$user = M('User');
	    $woman = $user->where('sex = 1')->count();
	    $man = $user->where('sex = 0')->count();
	    $Ladyboy = $user->where('sex = 3')->count();
	    $user = $user->count();
		
	    $woman1 = round($woman/$user *100);
	    $man1 = round($man/$user *100);
		
	// var_dump($man1);exit;
		 $this->assign('woman1', $woman1);
		 $this->assign('man1', $man1);
		 $this->assign('woman', $woman);
		 $this->assign('man', $man);
		 $this->assign('Ladyboy', $Ladyboy);

        
        $this->display();
    }
    
 
}

