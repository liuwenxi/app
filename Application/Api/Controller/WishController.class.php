<?php
namespace Api\Controller;

use Common\Controller\BaseController;

class WishController extends BaseController
{
    public function _initialize()
    {
        header('Content-type:text/html;charset=utf-8');
        header("Access-Control-Allow-Origin: *");
    }

    /**
     * 搜索
     */
    public function search()
    {
        $User = M('User');
        $Wishcard = M('Wishcard');
        $Wishwall = M('Wishwall');
        $Wishhelp = M('Wishhelp');
        $background = M('WishwallBackground');

        if (IS_GET) {
            $keywords = I('get.keywords', '0', 'htmlspecialchars');
            $type = I('get.type');
            $page = I('get.page');

            if (empty($keywords)) {
                $datas = array();
            } else {
                $map['title'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                $map['content'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                $map['nickname'] = array('like', array('%' . $keywords . '%', '%' . $keywords, $keywords . '%'), 'OR');
                $map['_logic'] = 'OR';
                switch ($type) {
//                    case "0";
//                        $field = 'id,nickname,avatar,signature,gender';
//                        $usercount = $User->where($map)->count();
//                        $total['user'] = $User->where($map)->field($field)->page($page, 10)->select();
//                        foreach ($total['user'] as $k => $v) {
//                            $total['user'][$k]['className'] = '用户';
//                        }
//
//                        $field = 'id,content,set_user,background,posttime';
//                        $total['wall'] = $Wishwall->where($map)->field($field)->select();
//                        foreach ($total['wall'] as $k => $v) {
//                            $name = $User->where(array('id' => $v['set_user']))->find();
//                            $backgroundinfo = $background->where(array('id' => $v['background']))->find();
//                            $total['wall'][$k]['nickname'] = $name['nickname'];
//                            $total['wall'][$k]['font_color'] = $backgroundinfo['font_color'];
//                            $total['wall'][$k]['large_img'] = $backgroundinfo['large_img'];
//                            $total['wall'][$k]['posttime'] = date('Y-m-d', $v['posttime']);
//                            $total['wall'][$k]['className'] = '心愿墙';
//                        }
//
//                        $field = 'id,content,img,post_uid,post_time';
//                        $total['help'] = $Wishhelp->where($map)->field($field)->select();
//                        foreach ($total['help'] as $k => $v) {
//                            $name = $User->where(array('id' => $v['post_uid']))->find();
//                            $total['help'][$k]['nickname'] = $name['nickname'];
//                            $total['help'][$k]['posttime'] = date('Y-m-d', $v['post_time']);
//                            $total['help'][$k]['className'] = '心愿帮';
//                        }
//
//                        $field = 'id,title,content,img,set_user,posttime';
//                        $total['card'] = $Wishcard->where($map)->field($field)->select();
//                        foreach ($total['card'] as $k => $v) {
//                            $name = $User->where(array('id' => $v['set_user']))->find();
//                            $total['card'][$k]['nickname'] = $name['nickname'];
//                            $total['card'][$k]['posttime'] = date('Y-m-d', $v['posttime']);
//                            $total['card'][$k]['className'] = '心愿汇';
//                        }
//                        $total = array_merge($total['user'], $total['card'], $total['help'], $total['wall']);
//                        $count = count($total);
//                        $totalPage = ceil($count / 10);
//                        $datas = array_slice($total, $page, 10);
//                        break;
//                    case '1';
//                        $usercount = $User->where($map)->count();
//                        $totalPage = ceil($usercount / 10);
//                        $field = 'id,nickname,avatar,signature,gender';
//                        $datas = $User->where($map)->field($field)->page($page, 10)->select();
//                        foreach ($datas as $k => $v) {
//                            $datas[$k]['className'] = '用户';
//                        }
//                        break;
                    case "0";
                        $wallcount = $Wishwall->where($map)->count();
                        $totalPage = ceil($wallcount / 10);
                        $field = 'id,content,set_user,background,light_count,share_count,posttime';
                        $datas = $Wishwall->where($map)->field($field)->page($page, 10)->select();
                        foreach ($datas as $k => $v) {
                            $name = $User->where(array('id' => $v['set_user']))->find();
                            $backgroundinfo = $background->where(array('id' => $v['background']))->find();
                            $datas[$k]['avatar'] = $name['avatar'];
                            $datas[$k]['nickname'] = $name['nickname'];
                            $datas[$k]['gender'] = $name['gender'];
                            $datas[$k]['font_color'] = $backgroundinfo['font_color'];
                            $datas[$k]['large_img'] = $backgroundinfo['large_img'];
                            $datas[$k]['type'] = 0;
                        }
                        break;
                    case '1';
                        $helpcount = $Wishhelp->where($map)->count();
                        $totalPage = ceil($helpcount / 10);
                        $field = 'id,content,img,post_uid,post_time';
                        $datas = $Wishhelp->where($map)->field($field)->page($page, 10)->select();
                        foreach ($datas as $k => $v) {
                            $name = $User->where(array('id' => $v['post_uid']))->find();
                            $datas[$k]['nickname'] = $name['nickname'];
                            $datas[$k]['posttime'] = $v['post_time'];
                            $datas[$k]['type'] = 1;
                        }
                        break;
//                    case '4';
//
//                        break;
//                    case '5';
//                        $cartcount = $Wishcard->where($map)->count();
//                        $totalPage = ceil($cartcount / 10);
//                        $field = 'id,title,content,img,set_user,posttime';
//                        $datas = $Wishcard->where($map)->field($field)->page($page, 10)->select();
//                        foreach ($datas as $k => $v) {
//                            $name = $User->where(array('id' => $v['set_user']))->find();
//                            $datas[$k]['set_user'] = $name['nickname'];
//                            $datas[$k]['posttime'] = date('Y-m-d', $v['posttime']);
//                            $datas[$k]['className'] = '心愿汇';
//                        }
//                        break;
                }

                $results['status'] = 0;
                $results['totalPage'] = $totalPage;
                $results['count'] =  $wallcount + $helpcount;
                if($results['count'] == 0){
                    $results['currentPage'] = 0;
                }else{
                    $results['currentPage'] = $page;
                }
                $results['data'] = $datas;
                dexit($results);
            }
        }
    }

}