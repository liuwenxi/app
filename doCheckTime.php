<?php 
//php可执行文件位置
//时区
#crontab -e
#00 * * * * /usr/local/php/bin/php /data/web/wish4you/doCheckTime.php
$time_zone="Asia/Shanghai";
ini_set("date.timezone",$time_zone);

//数据库信息
$server = "rm-wz9k84smkebt2d305o.mysql.rds.aliyuncs.com";
$user = "kar1x";
$pwd = "wish4you_com";
$database_name = "wish4you";

//打开链接
$mysqli = new mysqli($server,$user,$pwd,$database_name);
if (!$mysqli){
	file_put_contents("/data/web/wish4you/log/error/mysql_error.txt", json_encode($mysqli->connect_errno));
	die("Connect error: " . $mysqli->connect_errno);
}


$time=time();//当前时间
//$mixTime=$time-4*24*3600;//4天前
//$maxTime=$time-120*24*3600;//120天前

//查找所有心愿已过期但是没有修改状态的心愿
//$sql = "SELECT * FROM `wxy_xinyuan` WHERE `status` = 0 AND `chk_time` BETWEEN ".$maxTime." AND ".$mixTime;
$xinyuan_sql = "SELECT * FROM `wxy_xinyuan` WHERE `status` = 0 AND `chk_time` + `set_day` * 86400 <= ".$time;
$arr = $mysqli->query($xinyuan_sql);
while ($rows = $arr->fetch_array())
{
	$xyList[]=$rows;
}

if($xyList){
	//循环出心愿
	foreach($xyList as $key => $val){
		//开启事务
		$mysqli->autocommit(false);
		//查找所有参与心愿的记录
		$xinyuanlist_sql = "SELECT * FROM `wxy_xinyuan_list` WHERE `xid` = ".$val['id'];
		$finds = $mysqli->query($xinyuanlist_sql);
		while ($rows = $finds->fetch_array())
		{
			$xylist[]=$rows;
		}

		if($xylist){
			//查找每个参与心愿的用户
			foreach($xylist as $k => $v){

				//未退款的
				if($v['is_refund']==0){
					//添加退款记录
					$add_sql="INSERT INTO `wxy_bill`(`uid`,`type`,`mon`,`xid`,`posttime`,`is_finish`) VALUES ('".$v['invo_uid']."', '4', '".$v['invo_mon']."', '".$val['id']."','".time()."','1')";
					$result = $mysqli->query($add_sql);
					if($result){

						//返回心愿豆
						$set_sql="UPDATE `wxy_user` set `totalmon` = `totalmon` + ".$v['invo_mon']." WHERE `id` = ".$v['invo_uid'];
						$result = $mysqli->query($set_sql);
						if($result){

							//修改参与心愿信息状态
							$set_sql="UPDATE `wxy_xinyuan_list` set `is_refund` = 1 WHERE `id` = ".$v['id'];
							$result = $mysqli->query($set_sql);

							//发送参与者站内信
							$add_sql="INSERT INTO `wxy_message`(`uid`,`xid`,`title`,`des`,`posttime`,`releasetime`,`type`) VALUES ('".$v['invo_uid']."','".$val['id']."','".$val['title']."', '太可惜了，您参与过的心愿##未能达成，送出的心愿豆将会回到你的账户。', '".time()."','".time()."','3')";
							$result = $mysqli->query($add_sql);
						}
					}
				}
			}
		}

		//修改心愿状态
		$set_sql="UPDATE `wxy_xinyuan` set `status` = 2 , `is_refund` = 1 WHERE `id` = ".$val['id'];
		file_put_contents("/data/web/wish4you/log/succ/sql.txt", json_encode($set_sql));
		$result = $mysqli->query($set_sql);

		//发送发起人站内信
		$add_sql="INSERT INTO `wxy_message`(`uid`,`xid`,`title`,`des`,`posttime`,`releasetime`,`type`) VALUES ('".$val['set_user']."','".$val['id']."','".$val['title']."', '有点遗憾，您的心愿##到期未能达成，养好精神下次再来吧！', '".time()."','".time()."','2')";
		$result = $mysqli->query($add_sql);
		//提交回滚  并输出记录
		if (!$mysqli->errno) {
			$name=$val['id'];
			file_put_contents("/data/web/wish4you/log/succ/$name.txt", json_encode($val));
			$mysqli->commit();
		} else {
			$name=$val['id'];
			file_put_contents("/data/web/wish4you/log/error/$name.txt", json_encode($val));
			$mysqli->rollback();
		}

	}
}

//最后要关闭数据库链接
mysqli_close($mysqli);

?>