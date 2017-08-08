<?php

namespace Admin\Controller;

use Admin\Controller\AdminController;

/**
 * 数据统计
 * @package Admin\Controller
 */
class StatisticController extends AdminController
{
    private $mode_table = 'setting';

    public function index()
    {

        $user = M('User');
        $woman = $user->where('sex = 1')->count();
        $man = $user->where('sex = 0')->count();
        $Ladyboy = $user->where('sex = 3')->count();
        $user = $user->count();

        $woman1 = round($woman / $user * 100);
        $man1 = round($man / $user * 100);

        // var_dump($man1);exit;
        $this->assign('woman1', $woman1);
        $this->assign('man1', $man1);
        $this->assign('woman', $woman);
        $this->assign('man', $man);
        $this->assign('Ladyboy', $Ladyboy);


        $this->display();
    }

    /**
     * 应用概况
     */
    public function application()
    {
        $User = M('User');
        $UserLog = M('action_user_log');
        $Pv = M('Pv');

        $pvCpunt = M('Pv')->count();
        $userMap['type'] = 0;
        $userCount = $User->where($userMap)->count(); //总注册人数

        $phoneMap['bind_flag'] = 1;
        $phoneMap['type'] = 0;
        $phoneCount = $User->where($phoneMap)->count(); //手机注册人数

        $thirdMap['bind_flag'] = 0;
        $thirdMap['type'] = 0;
        $thirdCount = $User->where($thirdMap)->count(); //第三方注册人数

        $phoneBandMap['bind_flag'] = 1;
        $phoneBandMap['wechat_openid'] = array('neq', '');
        $phoneBandMap['type'] = 0;
        $phoneBandCount = $User->where($phoneBandMap)->count(); //绑定人数


        /**************今天时间*************************/
        $todayBegin = strtotime(date("Y-m-d 00:00:00"));
        $todayEnd = strtotime(date('Y-m-d 23:59:59'));
        /**************今天时间*************************/
        $todayMap['reg_time'] = array('between', array($todayBegin, $todayEnd));
        $todayPv['create_at'] = array('between', array($todayBegin, $todayEnd));
        $todayRegCount = $User->where($todayMap)->count(); //今天注册人数
        $todayPvCount = $Pv->where($todayPv)->count(); //今天访问人数

        $todayLog['create_at'] = array('between', array($todayBegin, $todayEnd));
        $todayLogCount = $UserLog->where($todayLog)->count();//今日回访人数

        $todayPhoneMap['reg_time'] = array('between', array($todayBegin, $todayEnd));
        $todayPhoneMap['bind_flag'] = 1;
        $todayPhoneCount = $User->where($todayPhoneMap)->count(); //今天手机注册人数

        $todayThirdeMap['reg_time'] = array('between', array($todayBegin, $todayEnd));
        $todayThirdeMap['bind_flag'] = 0;
        $todayThirdeCount = $User->where($todayThirdeMap)->count(); //今天第三方注册人数


        /**************本周时间*************************/
        $weekBegin = mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y"));
        $weekEnd = mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y"));
        $weekMap['reg_time'] = array('between', array($weekBegin, $weekEnd));
        $weekPv['create_at'] = array('between', array($weekBegin, $weekEnd));
        /**************本周时间*************************/
        $weekRegCount = $User->where($weekMap)->count(); //本周注册人数
        $weekPvCount = $Pv->where($weekPv)->count(); //本周访问人数

        $weekLog['create_at'] = array('between', array($weekBegin, $weekEnd));
        $weekLogCount = $UserLog->where($weekLog)->count();//本周回访人数

        $weekPhoneMap['reg_time'] = array('between', array($weekBegin, $weekEnd));
        $weekPhoneMap['bind_flag'] = 1;
        $weekPhoneCount = $User->where($weekPhoneMap)->count(); //本周手机注册人数

        $weekThirdeMap['reg_time'] = array('between', array($weekBegin, $weekEnd));
        $weekThirdeMap['bind_flag'] = 0;
        $weekThirdeCount = $User->where($weekThirdeMap)->count(); //本周第三方注册人数


        /**************本月时间*************************/
        $monthBegin = mktime(0, 0, 0, date("m"), 1, date("Y"));
        $monthEnd = mktime(23, 59, 59, date("m"), date("t"), date("Y"));
        $monthMap['reg_time'] = array('between', array($monthBegin, $monthEnd));
        $monthPv['create_at'] = array('between', array($monthBegin, $monthEnd));
        /**************本月时间*************************/
        $monthRegCount = $User->where($monthMap)->count(); //本月注册人数
        $monthPvCount = $Pv->where($monthPv)->count(); //本月访问人数

        $monthLog['create_at'] = array('between', array($monthBegin, $monthEnd));
        $monthLogCount = $UserLog->where($monthLog)->count();//本月回访人数

        $monthPhoneMap['reg_time'] = array('between', array($monthBegin, $monthEnd));
        $monthPhoneMap['bind_flag'] = 1;
        $monthPhoneCount = $User->where($monthPhoneMap)->count(); //本月手机注册人数

        $monthThirdeMap['reg_time'] = array('between', array($monthBegin, $monthEnd));
        $monthThirdeMap['bind_flag'] = 0;
        $monthThirdeCount = $User->where($monthThirdeMap)->count(); //本月第三方注册人数


        $this->assign('phoneBandCount', $phoneBandCount);//手机绑定第三方总数

        $this->assign('userCount', $userCount);//用户总数
        $this->assign('pvCount', $pvCpunt);//PV总数
        $this->assign('phoneCount', $phoneCount);//手机用户总数
        $this->assign('thirdCount', $thirdCount);//第三方用户总数

        $this->assign('todayRegCount', $todayRegCount);//今日注册总数
        $this->assign('todayPvCount', $todayPvCount);//今日PV
        $this->assign('todayPhoneCount', $todayPhoneCount);//今日手机注册
        $this->assign('todayThirdeCount', $todayThirdeCount);//今日第三方注册
        $this->assign('todayLogCount', $todayLogCount);//今日回访人数

        $this->assign('weekRegCount', $weekRegCount);//本周注册人数
        $this->assign('weekPvCount', $weekPvCount);//本周访问人数
        $this->assign('weekPhoneCount', $weekPhoneCount);//本周手机注册人数
        $this->assign('weekThirdeCount', $weekThirdeCount);//本周第三方注册人数
        $this->assign('weekLogCount', $weekLogCount);//本周回访人数

        $this->assign('monthRegCount', $monthRegCount);//本月注册人数
        $this->assign('monthPvCount', $monthPvCount);//本月访问人数
        $this->assign('monthPhoneCount', $monthPhoneCount);//本月手机注册人数
        $this->assign('monthThirdeCount', $monthThirdeCount);//本月第三方注册人数
        $this->assign('monthLogCount', $monthLogCount);//本月回访人数

        $this->display();
    }


    /**
     *心愿平论数据
     */
    public function comment()
    {
        /**************今天时间*************************/
        $todaybegin = strtotime(date("Y-m-d 00:00:00"));
        $todayend = strtotime(date('Y-m-d 23:59:59'));
        /**************今天时间*************************/

        /**************昨天时间*************************/
        $yesterdaybegin = strtotime(date('Y-m-d 00:00:00', strtotime("-1 day")));
        $yesterdayend = strtotime(date('Y-m-d 23:59:59', strtotime("-1 day")));
        /**************昨天时间*************************/

        //今天评论数据
        //评论数量
        $todaycomment = M('Comment')->where("pub_time BETWEEN " . $todaybegin . " and " . $todayend)->count();

        //评论男
        $todayman = M('Comment')->where("pub_time BETWEEN " . $todaybegin . " and " . $todayend . " and gender=0")->count();
        //评论女
        $todaywoman = M('Comment')->where("pub_time BETWEEN " . $todaybegin . " and " . $todayend . " and gender=1")->count();

        //昨天品论总数
        //评论数量
        $yesterdaycommetn = M('Comment')->where("pub_time BETWEEN " . $yesterdaybegin . " and " . $yesterdayend)->count();
        //评论男
        $yesterdayman = M('Comment')->where("pub_time BETWEEN " . $yesterdaybegin . " and " . $yesterdayend . " and gender =0")->count();

        //评论女
        $yesterdaywoman = M('Comment')->where("pub_time BETWEEN " . $yesterdaybegin . " and " . $yesterdayend . " and gender =1")->count();


        for ($i = 0; $i <= 23; $i++) {
            $time[] = date("Y-m-d {$i}");
        }
//        dump($time);exit;
        foreach ($time as $key => $val) {
            $info = M()->query("select count(`id`) as counts  from wxy_comment where from_unixtime(pub_time,'%Y-%m-%d %H') = '" . $val . "' and gender=1; ");
            $meanman[] = $info[0]['counts'];
        }
        //今天每小时评论人数


        foreach ($time as $key => $val) {
            $res = M()->query("select count(`id`) as counts  from wxy_comment where from_unixtime(pub_time,'%Y-%m-%d %H') = '" . $val . "' and gender=0; ");
            $meanwoman[] = $res[0]['counts'];
        }

        $this->assign('meanwoman', $meanwoman);
        $this->assign('meanman', $meanman);
        $this->assign('todaycomment', $todaycomment);
        $this->assign('todayman', $todayman);
        $this->assign('todaywoman', $todaywoman);
        $this->assign('yesterdaycommetn', $yesterdaycommetn);
        $this->assign('yesterdayman', $yesterdayman);
        $this->assign('yesterdaywoman', $yesterdaywoman);

        $this->display();
    }

    /**
     * 评论查询
     */
    public function findcomment()
    {
        if (!empty($_GET['reg_time'])) {
            $gap = explode('-', $_GET['reg_time']);
            $begin = strtotime($gap[0]);      //开始时间
            $end = strtotime($gap[1]);        //结束时间
            $where['pub_time'] = array('between', array($begin, $end));
        }

        if (!$begin || !$end) {
            $this->error('请输入正确时间');
        }

        //评论数量
        $count = M('Comment')->where($where)->count();
        //评论男
        $man = M('Comment')->where($where)->where('gender=0')->count();
        //评论女
        $woman = M('Comment')->where($where)->where('gender=1')->count();

        $this->assign('count', $count);
        $this->assign('man', $man);
        $this->assign('woman', $woman);
        $this->display();
    }


    /**
     * 最亮心愿
     */
    //每天的数据
    public function lighthot()
    {

        if (IS_POST) {
            $gap = explode('-', $_POST['reg_time']);
            $begin = strtotime($gap[0]);      //开始时间
            $end = strtotime($gap[1]);        //结束时间
            $where['time'] = array('between', array($begin, $end));
            $data = M('countwish')->field('sum(comment) as comment,sum(light) as light,sum(countcheck) as countcheck')->where($where)->select();
        } else {
            $todaybegin = strtotime(date("Y-m-d 00:00:00"));
            $todayend = strtotime(date('Y-m-d 23:59:59'));
            $where['time'] = array('between', array($todaybegin, $todayend));
            $data = M('countwish')->field('comment,light ,countcheck')->where($where)->select();
        }
        /*  $thisweek_start=mktime(0,0,0,date('m'),date('d')-date('w'+1),date('Y'));
          $thisweek_end=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));*/
        $this->assign('data', $data);
        $this->display();

    }

    /**
     * 背景使用次数
     */

    public function background()
    {
        $todaybegin = strtotime(date("Y-m-d 00:00:00"));
        $todayend = strtotime(date('Y-m-d 23:59:59'));
        $where['time'] = array('between', array($todaybegin, $todayend));
        $data = M('wishwall_background')->field('id,font_color,small_img,is_show,sort')->select();
        foreach ($data as $key => $val) {

            $data[$key]['count'] = M('wishwall')->field('background')->where('background=' . $val['id'])->count();
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function keyword()
    {
        $begin = strtotime(date("Y-m-d 00:00:00"));
        $end = strtotime(date('Y-m-d 23:59:59'));
        //新增心愿数量
        if (IS_POST) {
            $gap = explode('-', $_POST['reg_time']);
            $begin = strtotime($gap[0]);      //开始时间
            $end = strtotime($gap[1]);        //结束时间
        }
        $where['posttime'] = array('between', array($begin, $end));
        $map['join_time'] = array('between', array($begin, $end));
        $maps['create_at'] = array('between', array($begin, $end));
        $keyword = M('wishwall_keyword')->field('id,keyword')->select();
        $count = 0;
        $light = 0;
        $share = 0;
        foreach ($keyword as $key => $val) {
            $keyword[$key]['add'] = M('wishwall')->field('kid')->where($where)->where('kid=' . $val['id'])->count();
            $count += $keyword[$key]['add'];
            $keyword[$key]['light'] = M('wishwall_join')->field('kid')->where($map)->where('kid=' . $val['id'])->count();
            $light += $keyword[$key]['light'];
            $keyword[$key]['share'] = M('wishshare')->field('kid')->where($maps)->where('kid=' . $val['id'])->count();
            $share += $keyword[$key]['share'];
        }
        $this->assign('count', $count);
        $this->assign('light', $light);
        $this->assign('share', $share);
        $this->assign('keyword', $keyword);
        $this->display();
    }

    /**
     *新增用户
     */

    public function newuser()
    {

        if (IS_POST) {
            $time = $_POST['reg_time'];
            $gap = explode('-', $_POST['reg_time']);
            $begin = strtotime($gap[0]);      //开始时间
            $end = strtotime($gap[1]);        //结束时间
            $where['reg_time'] = array('between', array($begin, $end));
            $map['join_time'] = array('between', array($begin, $end));
            $post['posttime'] = array('between', array($begin, $end));
            $data = M('user')->field('id')->where($where)->select();
            $countuser = count($data);
            $countwish = '';
            $countcomment = '';
            foreach ($data as $key => $val) {
                $wishwall = M('wishwall')->where('set_user=' . $val['id'])->where($post)->count();
                $countwish += $wishwall;
                $comment = M('comment')->where('pub_user=' . $val['id'])->where($map)->count();
                $countcomment += $comment;
            }
            $avgcomment = $countcomment / $countuser;
            $avgwish = $countwish / $countuser;

        } else {
            $todaybegin = strtotime(date("Y-m-d 00:00:00"));
            $todayend = strtotime(date('Y-m-d 23:59:59'));
            //今天注册时间
            $where['reg_time'] = array('between', array($todaybegin, $todayend));
            //今天发布评论时间
            $map['pub_time'] = array('between', array($todaybegin, $todayend));
            //今天心愿发布时间
            $post['posttime'] = array('between', array($todaybegin, $todayend));
            $data = M('user')->field('id')->where($where)->select();
            $countuser = count($data);
            $countwish = '';
            $countcomment = '';
            foreach ($data as $key => $val) {
                $wishwall = M('wishwall')->where('set_user=' . $val['id'])->where($post)->count();
                $countwish += $wishwall;
                $comment = M('comment')->where('pub_user=' . $val['id'])->where($map)->count();
                $countcomment += $comment;
            }
            $avgcomment = $countcomment / $countuser;
            $avgwish = $countwish / $countuser;
            $time = date('Y-m-d');
        }
        //求每天的平均数
        $this->assign('time', $time);
        $this->assign('countuser', $countuser);
        $this->assign('countwish', $countwish);
        $this->assign('countcomment', $countcomment);
        $this->assign('avgcomment', $avgcomment);
        $this->assign('avgwish', $avgwish);
        $this->display();

    }

    public  function banner()
    {
        if (IS_POST) {
            $time = $_POST['reg_time'];
            $gap = explode('-', $_POST['reg_time']);
            $begin = strtotime($gap[0]);      //开始时间
            $end = strtotime($gap[1]);        //结束时间
            $where['create_at'] = array('between', array($begin, $end));
            $data = M('banner')->field('id,img,is_no')->select();
            foreach ($data as $key => $val) {
                $data[$key]['count'] = M('bannerpv')->field('id')->where($where)->where('bid=' . $val['id'])->count();
            }
        } else {
            $todaybegin = strtotime(date("Y-m-d 00:00:00"));
            $todayend = strtotime(date('Y-m-d 23:59:59'));
            $where['create_at'] = array('between', array($todaybegin, $todayend));
            $data = M('banner')->field('id,img,is_no')->select();
            foreach ($data as $key => $val) {
                $data[$key]['count'] = M('bannerpv')->field('id')->where($where)->where('bid=' . $val['id'])->count();
            }
        }
        $this->assign('data', $data);
        $this->display();
    }

}

