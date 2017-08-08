<?php

namespace Admin\Controller;

use Admin\Controller\AdminController;

class IndexController extends AdminController {

    public function index() {


        $host = $_SERVER['HTTP_HOST'];
        $uploadMax = ini_get('upload_max_filesize');
        $window = $_SERVER['HTTP_USER_AGENT'];
// 		$Ip = $_SERVER[''];
        $svrIp = $_SERVER['SERVER_NAME'];
        $phpVsn = PHP_VERSION;
        $svrSys = php_uname();
        $phpMod = php_sapi_name();

        $AdminRole = M('AdminRole');

        $ActList = $AdminRole->join('__ADMIN__ ON __ADMIN__.role_id =__ADMIN_ROLE__.ROLE_id')->where(array('id' => $_SESSION['adminid']))->find();



        $act_list = $ActList['act_list'];
        $menu_list = $this->getRoleMenu($act_list);
        $this->assign('menu_list', $menu_list);



        $this->assign('window', $window);
        $this->assign('svrIp', $svrIp);
        $this->assign('php', $phpVsn);
        $this->assign('phpMod', $phpMod);
        $this->assign('svrSys', $svrSys);
        $this->assign('uploadMax', $uploadMax);
        $this->assign('host', $host);

        //加载视图
        $this->display();
    }

    public function home() {
        $User = M('User');
        $Admin = M('Admin');
        $AdminRole = M('AdminRole');
        $Xinyuan = M('Wishcard');

        $admin = $Admin->where(array('id' => $_SESSION['adminid']))->find(); 
        $role = $AdminRole->where(array('role_id' =>$admin['role_id']))->find();
        $usernum = $User->count();
        $xyd = $User->sum('totalmon');
        $xynum = $Xinyuan->count();

        $this->assign('usernum', $usernum);
        $this->assign('sys_info', $this->get_sys_info());
        $this->assign('xynum', $xynum);
        $this->assign('xyd', $xyd);
        $this->assign('admin', $admin);
        $this->assign('role', $role);
        $this->display();
    }

    public function get_sys_info() {
        $sys_info['os'] = PHP_OS;
        $sys_info['zlib'] = function_exists('gzclose') ? 'YES' : 'NO'; //zlib
        $sys_info['safe_mode'] = (boolean) ini_get('safe_mode') ? 'YES' : 'NO'; //safe_mode = Off		
        $sys_info['timezone'] = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
        $sys_info['curl'] = function_exists('curl_init') ? 'YES' : 'NO';
        $sys_info['web_server'] = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['phpv'] = phpversion();
        $sys_info['ip'] = GetHostByName($_SERVER['SERVER_NAME']);
        $sys_info['fileupload'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknown';
        $sys_info['max_ex_time'] = @ini_get("max_execution_time") . 's'; //脚本最大执行时间
        $sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
        $sys_info['domain'] = $_SERVER['HTTP_HOST'];
        $sys_info['memory_limit'] = ini_get('memory_limit');
        $sys_info['version'] = file_get_contents('./Application/Admin/Conf/version.txt');
        $mysqlinfo = M()->query("SELECT VERSION() as version");
        $sys_info['mysql_version'] = $mysqlinfo['version'];
        if (function_exists("gd_info")) {
            $gd = gd_info();
            $sys_info['gdinfo'] = $gd['GD Version'];
        } else {
            $sys_info['gdinfo'] = "未知";
        }
        return $sys_info;
    }

    public function getRoleMenu($act_list) {
        $modules = $roleMenu = array();
        $rs = M('system_module')->where('level>1 AND visible=1')->order('orderby desc')->select();

        if ($act_list == 'all') {
            foreach ($rs as $row) {
                if ($row['level'] == 3) {
                    $row['url'] = U("Admin/" . $row['ctl'] . "/" . $row['act'] . "");
                    $modules[$row['parent_id']][] = $row; //子菜单分组
                }
                if ($row['level'] == 2) {
                    $pmenu[$row['mod_id']] = $row; //二级父菜单
                }
            }
        } else {
            $act_list = explode(',', $act_list);
            foreach ($rs as $row) {
                if (in_array($row['mod_id'], $act_list)) {
                    $row['url'] = U("Admin/" . trim($row['ctl']) . "/" . $row['act'] . "");
                    $modules[$row['parent_id']][] = $row; //子菜单分组
                }
                if ($row['level'] == 2) {
                    $pmenu[$row['mod_id']] = $row; //二级父菜单
                }
            }
        }
        $keys = array_keys($modules); //导航菜单
        foreach ($pmenu as $k => $val) {
            if (in_array($k, $keys)) {
                $val['submenu'] = $modules[$k]; //子菜单
                $roleMenu[] = $val;
            }
        }
        return $roleMenu;
    }

    public function dealArr($rawdata) {
        $listkey = array();
        $retnList = array();  //返回的数组
        $list = array();

        foreach ($rawdata as $kk => $vv) {
            foreach ($vv as $ko => $vo) {
                if (in_array($ko, $listkey)) {
                    foreach ($retnList as $k => $v) {
                        foreach ($v as $ke => $ve) {
                            if ($ko == $ke) {
                                $retnList[$k][$ke] += $vo;
                            }
                        }
                    }
                } else {
                    $mon = 0.00;
                    array_push($listkey, $ko);
                    $mon += $vo;
                    $list = array(
                        $ko => $mon,
                    );

                    array_push($retnList, $list);
                }
            }
        }

        return $retnList;
    }

    public function recomArr($arr) {
        $mkey = array();
        $mval = array();
        foreach ($arr as $key => $val) {
            foreach ($val as $kk => $vv) {
                array_push($mkey, $kk);
                array_push($mval, $vv);
            }
        }
        $mkey = implode(',', $mkey);
        $mval = implode(',', $mval);

        return $mkey . '=>' . $mval;
    }

}
