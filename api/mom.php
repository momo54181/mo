<?php
$set=1;//设置公告是否开启 1开启 0关闭
$gg="莫莫";//测试公告
$passkey="237535";//为了防止注入这里限制到了10位
$mode=1;//0为次数模式 1为流量模式 (单位MB)


$vip_bduss = array("kRMZkRNTW10UGpVLXZ0MVRMSWtYT2xmS3Z1dzdwNDM5YWV1cjNVY01FNm45TTlnRUFBQUFBJCQAAAAAAAAAAAEAAAArUoGtztK6w7e9t8W3vAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAKdnqGCnZ6hgM");
//$ua1="netdisk;P2SP;2.2.60.26";
//$ua1="netdisk;2.2.51.6;netdisk;10.0.63;PC;android-android;QTP/1.0.32.2";
$ua1="netdisk;P2SP;3.0.0.3;netdisk;11.5.3;PC;PC-Windows;android-android;11.0;JSbridge4.4.0";
$xiancheng="64";//最大线程


//创建数据 http://127.0.0.1/api/Request.php?method=createdb

//注册key的次数（流量） http://127.0.0.1/api/Request.php?method=regist&pass=sswordnedd&code=888&time=88
//更新key的次数（流量） http://127.0.0.1/api/Request.php?method=update&pass=sswordnedd&code=888&time=88
//删除key的次数（流量） http://127.0.0.1/api/Request.php?method=delect&pass=sswordnedd&code=888&time=88

class MyDB extends SQLite3
{
	function __construct()
	{
		$this->open('user111.db');//这里最好自己改下
	}
}
function re_rediect($str){      //正则拦截，防SQL注入(只允许a-zA-Z0-9)
	if(preg_match("/^[a-zA-Z0-9]+$/", $str) == 1) {
    // string only contain the a to z , A to Z, 0 to 9
		return $str;
	}
	return "Undefined";
}



function getSubstr($str, $leftStr, $rightStr)
{
$left = strpos($str, $leftStr);
$right = strpos($str, $rightStr,$left);
if($left < 0 or $right < $left) return '';
return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
}

function reqq($url,$data,$ua,$bduss){
	
	//strpos($ret, '31045')!==false || strpos($ret, 'qdall01')!==false
	$num = count($bduss);
	for($i=0;$i<$num;$i++){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url."&devuid=O|".md5($bduss[$i])."&origin=dlna");//&origin=dlna 无需Range头即可下载
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_USERAGENT,$ua);
	curl_setopt($curl, CURLOPT_COOKIE, "BDUSS=".$bduss[$i]);
	//curl_setopt($curl, CURLOPT_POST,1)
	//curl_setopt($curl, CURLOPT_POSTFIELDS,$data)
	
	$data222 = curl_exec($curl);
	curl_close($curl);
	//return $data222;
	if( strpos($data222, '31045')===false && strpos($data222, 'qdall01')===false)
	{
		return $data222;
	}
	
	
	}
	return "Er";
	
}

$a=$_GET['method'];
$a=re_rediect($a);
if($a=="isok"){
	$meage=array(
	'code'=> 200,
	'messgae'=> '维护中',//code不为200时显示的错误提示
	'open'=>$set,
	'gg'=>$gg
	);
	echo json_encode($meage);
	exit ;
}
if($a=="regist"){
	$user=$_GET['code'];
	$time=$_GET['time'];//mode=0 时 是解析次数 ，mode=1 时 为流量大小(MB)
	$pass=$_GET['pass'];
	$user=re_rediect($user);
	$time=re_rediect($time);
	$pass=re_rediect($pass);
	
	
	if(strlen($user) > 8 || strlen($pass)>10 || $passkey!==$pass){
		
		$meage=array(
		'code'=> 403,
		'messgae'=> 'Forbiiden'
		);
		echo json_encode($meage);
		exit;
	}
	$db = new MyDB();
	$ret = $db->query('INSERT INTO user VALUES ("'.$user.'",'.(int)$time.');');
	
	$meage=array(
	'code'=> 200,
	'messgae'=> 'Registe OK'
	);
	echo json_encode($meage);
	exit ;
	
}
if($a=="request"){
	$user=$_GET['code'];
	$data=$_GET['data'];
	
	//$data=re_rediect($data);
	$user=re_rediect($user);
	$data1=base64_decode($data);
	$db = new MyDB();
	
	$ret = $db->query('
	SELECT * FROM user WHERE code="'.$user.'";
	');
	while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
		//echo $data1;
		$ret1 = reqq("https://d.pcs.baidu.com/rest/2.0/pcs/file?method=locatedownload&app_id=250528&ver=4.0&vip=2".$data1,"",$ua1,$vip_bduss);
		/**if(strpos($ret, '31045')!==false || strpos($ret, 'qdall01')!==false){
			$meage=array(
			'code'=> 405,
			'messgae'=> '账号过期或黑号，请提醒更换，此次不计数',
			);
			echo json_encode($meage);
			exit ;
		}**/
		//echo $ret1;
		if ($ret1=="Er"){
			$meage=array(
			'code'=> 405,
			'messgae'=> '账号过期或黑号，请提醒更换，此次不计数',
			);
			echo json_encode($meage);
			exit ;
		}
		$mm = $ret1;
		$dada=getSubstr($ret1,'client_ip','urls');
		$server = substr($dada ,3,strlen($dada)-6);
		//echo $server;
		//exit;
        $ret1=str_replace($server,"127.0.0.1",$ret1);
		$ret1=base64_encode($ret1);
		if ($mode==0){
			$tt= (int)$row['time']-1;
		}
		elseif ($mode==1){
			$size=getSubstr($mm,"&size=","&");
			$size1=(int)$size;
			$size1=$size1/1048576;
			$tt= (int)$row['time']-$size1;
			$now=(int)$row['time'];
			if($now < 1024){
				$remain = (string)$now."MB";
			}
			else{
				$remain = (string)($now/1024)."GB";
				
			}
			if($tt<0){
				
				$meage=array(
				'code'=> 406,
				'messgae'=> '流量不足,剩余流量:'.$remain,
				'inpu'=> '流量不足,剩余流量:'.$remain.'，请重新输入key或者下载小文件'
				);
				echo json_encode($meage);
				exit ;
				
			}
		}

		
		if($tt==0 || $tt <0){
			$ret = $db->exec('DELETE FROM user WHERE code="'.$user.'";');
		}
		else{
			$ret = $db->exec('UPDATE user SET time='.$tt.' WHERE code="'.$user.'";');
		}
		$meage=array(
		'code'=> 200,
		'messgae'=> 'Done',
		'data'=> $ret1,
		'split'=> $xiancheng,
		'ua' => $ua1
		);
		echo json_encode($meage);
		exit ;
	}
	$meage=array(
		'code'=> 404,
		'messgae'=> 'User not found',
		'inpu'=> 'key过期或错误，重新输入'
	);
	echo json_encode($meage);
	exit ;
}
if($a=="createdb"){
	
	$db = new MyDB();
	
	$ret = $db->query('
	CREATE TABLE user(
	code TEXT,
	time INT);
	');
	if($ret)
	{
		$meage=array(
		'code'=> 200,
		'messgae'=> 'Create OK'
		);
	}
	else 
	{
		$meage=array(
		'code'=> 403,
		'messgae'=> 'Exists or No promission'
		);
	}
	echo json_encode($meage);
	exit ;
}
if($a=="delect"){
	$pass=$_GET['pass'];
	$user=$_GET['code'];
	$user=re_rediect($user);
	$pass=re_rediect($pass);
	if(strlen($user) > 8 || strlen($pass)>10 || $passkey!==$pass){
		
		$meage=array(
		'code'=> 403,
		'messgae'=> 'Forbiiden'
		);
		echo json_encode($meage);
		exit;
	}
	$db = new MyDB();
	$ret = $db->exec('DELETE FROM user WHERE code="'.$user.'";');
	//$ret = $db->query('DELETE FROM user WHERE code=);');
	if($ret)
	{
		$meage=array(
		'code'=> 200,
		'messgae'=> 'DELETE OK'
		);
	}
	else 
	{
		$meage=array(
		'code'=> 403,
		'messgae'=> 'Not Exists or No promission'
		);
	}
	echo json_encode($meage);
	exit ;
}
if($a=="update"){
	$user=$_GET['code'];
	$time=$_GET['time'];//mode=0 时 是解析次数 ，mode=1 时 为流量大小(MB)
	$pass=$_GET['pass'];
	$user=re_rediect($user);
	$time=re_rediect($time);
	$pass=re_rediect($pass);
	if(strlen($user) > 8 || strlen($pass)>10 || $passkey!==$pass){
		$meage=array(
		'code'=> 403,
		'messgae'=> 'Forbiiden'
		);
		echo json_encode($meage);
		exit;
	}
	$db = new MyDB();
	$ret = $db->exec('UPDATE user SET time='.$time.' WHERE code="'.$user.'";');
	//$ret = $db->query('DELETE FROM user WHERE code=);');
	if($ret)
	{
		$meage=array(
		'code'=> 200,
		'messgae'=> 'Update OK'
		);
	}
	else 
	{
		$meage=array(
		'code'=> 403,
		'messgae'=> 'Not Exists or No promission'
		);
	}
	echo json_encode($meage);
	exit ;
}
$meage=array(
	'code'=> 403,
	'messgae'=> 'Method is not define'
);
echo json_encode($meage);
exit;
?>
