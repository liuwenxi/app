<?php 
//php可执行文件位置
//时区
#crontab -e
#59 23 * * * /usr/local/php/bin/php /data/web/wish4you/doCheckStatus.php
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

//查找所有心愿已过期但是没有修改状态的心愿
$xinyuan_sql = "SELECT * FROM `wxy_xinyuan` WHERE `status` = 1";
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

		$overTime=ceil(($time-$val['finishtime'])/86400);
		if($overTime>=30){
			//修改状态为未完结
			$set_sql="UPDATE `wxy_xinyuan` set `status` = 5 WHERE `id` = ".$val['id'];
			$result = $mysqli->query($set_sql);
		}elseif($overTime==29){
			//发送发起人站内信
			$add_sql="INSERT INTO `wxy_message`(`uid`,`xid`,`title`,`des`,`posttime`,`releasetime`,`type`) VALUES ('".$val['set_user']."','".$val['id']."','".$val['title']."', '离##完结还剩最后一天啦，再不完结就摊上大事啦！', '".$time."','".$time."','2')";
			$result = $mysqli->query($add_sql);
		}elseif($overTime==25){
			//发送发起人站内信
			$add_sql="INSERT INTO `wxy_message`(`uid`,`xid`,`title`,`des`,`posttime`,`releasetime`,`type`) VALUES ('".$val['set_user']."','".$val['id']."','".$val['title']."', '你的心愿在大声喊你去更新，离##完结还剩5天了！', '".$time."','".$time."','2')";
			$result = $mysqli->query($add_sql);
		}elseif($overTime==15){
			//发送发起人站内信
			$add_sql="INSERT INTO `wxy_message`(`uid`,`xid`,`title`,`des`,`posttime`,`releasetime`,`type`) VALUES ('".$val['set_user']."','".$val['id']."','".$val['title']."', '上天留给我们的时间已经不多了，离你的心愿##完结还剩15天！', '".$time."','".$time."','2')";
			$result = $mysqli->query($add_sql);
		}

		//提交或者回滚  并输出记录
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