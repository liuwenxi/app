<?php
namespace Api\Controller;
use Workerman\Worker;
class WorkermanController
{
    /**
     * 用户信息查询
     */
    public function index(){

        if(!IS_CLI){
            die("access illegal");
        }
        require_once APP_PATH.'Workerman/Autoloader.php';
        define('MAX_REQUEST', 1000);// 每个进程最多执行1000个请求
        Worker::$daemonize = true;//以守护进程运行
        Worker::$pidFile = '/data/log/workerman.pid';//方便监控WorkerMan进程状态
        Worker::$stdoutFile = '/data/log/stdout.log';//输出日志, 如echo，var_dump等
        Worker::$logFile = '/data/log/workerman.log';//workerman自身相关的日志，包括启动、停止等,不包含任何业务日志
        $worker = new Worker('websocket://120.76.188.165:2346');//此处我使用内网ip
//        $worker->onConnect  = function($connection)
//        {
//            $connection->send('success:connect');
//        };
        $worker->onMessage = function($connection, $data){
            static $request_count = 0;// 已经处理请求数
//            $connection->send('success');
            //解析传输的数据
            $datas=json_decode($data,true);
            switch ($datas['type']){

                case 'login':
                    $ChatLog=M('chat_log');
                    $GLOBALS[$datas['user']]=$connection;
                    $where['take_user']=$datas['user'];
                    $where['type']=1;
                    $chatlogs=$ChatLog->where($where)->select();
                    if($chatlogs){
                        $connection->send(json_encode($chatlogs));
                        foreach ($chatlogs as $key => $val){
                            $ChatLog->where(array("id"=>$val['id']))->save(array("type"=>0));
                        }
                    }
                    $res['type']="login";
                    $res['success']=true;
                    $connection->send(json_encode($res));
                    break;

                case 'logout':
                    $res['type']="logout";
                    $res['success']=true;
                    $connection->send(json_encode($res));
                    unset($GLOBALS[$datas['user']]);
                    break;

                case 'send'://发送信息
                    $User=M('User');
                    $ChatLog=M('chat_log');
                    $where=array();
                    $where['wish_id']=$datas['wish_id'];
                    //查找最新的时间段
                    $count=$ChatLog->where($where)->count();
                    if($count>0){
                        $segment=$count%10==0?(intval($count/10))+1:intval($count/10)+1;
                    }else{
                        $segment=1;
                    }
//                    $where['send_user']=$datas['send_user'];
//                    $where['take_user']=$datas['take_user'];
//                    $userInfo=$User->field('password,pay_pwd',true)->where(array('id'=>$datas['send_user']))->find();
                    $newData['wish_id']=$datas['wish_id'];
                    $newData['send_user']=$datas['send_user'];
                    $newData['take_user']=$datas['take_user'];
                    $newData['message']=$datas['message'];
                    $newData['segment']=$segment;
                    $newData['timestamp']=time();
                    if(!empty($GLOBALS[$datas['take_user']])){
                        $da['type']="take";
                        $da['send_user']=$datas['send_user'];
                        $da['success']=true;
                        $da['message']=$datas['message'];
                        $GLOBALS[$datas['take_user']]->send(json_encode($da));
                        $newData['type']=0;
                    }else{
                        $newData['type']=1;
                    }
                    var_dump($newData);
                    if($ChatLog->add($newData)){
                        $res['type']="send";
                        $res['success']=true;
                        $connection->send(json_encode($res));
                    }else{
                        $res['type']="send";
                        $res['success']=false;
                        $connection->send(json_encode($res));
                    }

                    break;
            }

            /*
             * 退出当前进程，主进程会立刻重新启动一个全新进程补充上来，从而完成进程重启
             */
            if(++$request_count >= MAX_REQUEST){// 如果请求数达到1000
                Worker::stopAll();
            }
        };

        // 只启动1个进程，这样方便客户端之间传输数据
        $worker->count = 1;
        // 运行worker
        Worker::runAll();
    }

}
