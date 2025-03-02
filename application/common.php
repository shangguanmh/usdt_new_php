<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Cache;
use think\Db;
use think\Request;
use think\Lang;
use GuzzleHttp\Client;
use Tron\Address;

// 应用公共文件
function ajaxReturn($code,$msg,$data = [],$isarray = 1){
	if(empty($data)&&$isarray ==1){
		$data = (object)$data;
	}
	echo json_encode(['code'=>$code,'msg'=>lang($msg),'data'=>$data],JSON_UNESCAPED_UNICODE);exit();
}
function htajaxReturn($code,$msg,$data = [],$isarray = 1){
	if(empty($data)&&$isarray ==1){
		$data = (object)$data;
	}
	echo json_encode(['code'=>$code,'msg'=>($msg),'data'=>$data],JSON_UNESCAPED_UNICODE);exit();
}
function feifaReturn($feifacode,$msg='ajax_非法提交'){
	echo json_encode(['code'=>0,'msg'=>lang($msg,[$feifacode]),'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
}
function get_client_ip() {
    if(isset($_SERVER['HTTP_X_REAL_IP'])){//nginx 代理模式下，获取客户端真实IP
        $ip=$_SERVER['HTTP_X_REAL_IP'];     
    }else if (@$_SERVER["HTTP_X_FORWARDED_FOR"]) 
             $ip = $_SERVER["HTTP_X_FORWARDED_FOR"]; 
      else if (@$_SERVER["HTTP_CLIENT_IP"]) 
            $ip = $_SERVER["HTTP_CLIENT_IP"]; 
      else if (@$_SERVER["REMOTE_ADDR"]) 
            $ip = $_SERVER["REMOTE_ADDR"]; 
      else if (@getenv("HTTP_X_FORWARDED_FOR")) 
           $ip = getenv("HTTP_X_FORWARDED_FOR"); 
     else if (@getenv("HTTP_CLIENT_IP")) 
         $ip = getenv("HTTP_CLIENT_IP"); 
    else if (@getenv("REMOTE_ADDR")) 
        $ip = getenv("REMOTE_ADDR"); 
    else 
    $ip = "0.0.0.0"; 
    return $ip; 
}

/**
 * 修改本地配置文件
 *
 * @param array $name ['配置名']
 * @param array $value ['参数']
 * @return boolean
 */
function setconfig($name, $value)
{

    if (is_array($name) and is_array($value)) {
        for ($i = 0; $i < count($name); $i++) {
            $names[$i] = '/\'' . $name[$i] . '\'(.*?),/';
            $values[$i] = "'" . $name[$i] . "'" . "=>" . "'" . $value[$i] . "',";
        }
        $fileurl = APP_PATH . "./config.php";
        $string = file_get_contents($fileurl); //加载配置文件
        $string = preg_replace($names, $values, $string); // 正则查找然后替换
        file_put_contents($fileurl, $string); // 写入配置文件
        
        return true;
    } else {
        
        return false;
    }
}

/**
 * 修改本地配置文件
 *
 * @param array $name ['配置名']
 * @param array $value ['参数']
 * @return boolean
 */
function setLangConfig($name, $value)
{

    if (is_array($name) and is_array($value)) {
        for ($i = 0; $i < count($name); $i++) {
            $names[$i] = '/\'' . $name[$i] . '\'(.*?),/';
            $values[$i] = "'" . $name[$i] . "'" . "=>" . "'" . $value[$i] . "',";
        }
        $fileurl = APP_PATH . "./config.php";
        $string = file_get_contents($fileurl); //加载配置文件
        $string = preg_replace($names, $values, $string); // 正则查找然后替换
        file_put_contents($fileurl, $string); // 写入配置文件
        
        return true;
    } else {
        
        return false;
    }
}

function redisCache() {
    $redis = new \Redis();
    $redis->connect('127.0.0.1','6379');
    $redis->auth('123456');
    return $redis;
}
function getRedisXM($key){
	return XM.$key;
}
function getConfig($config_sign,$needlang=1) {
	$where = ['config_sign'=>$config_sign];
	if($needlang ==1){
		$where['lang'] = Lang::detect();
	}
    $result = Db::name('config')->where($where)->value('config_value');
    return $result;
}
function getGoopic($fileName){
	if(strpos($fileName,'http') !== false){ 
		return $fileName;
	}
	if(empty($fileName)){
		return '';
	}
	return getyuming().'upload/goodpic/'.$fileName;
}
function setPage($count,$page, $page_size=15) {//处理分页的逻辑
    $page_count = ceil($count/$page_size);
    if ($page > $page_count) $page = $page_count;
    if ($page < 1) $page = 1;
    $offset = $page_size * ($page - 1);
    $limit = $offset.','.$page_size;
    return [
        'page'=> $page,
        'page_count'=> $page_count,
        'limit'=> $limit,
        'count'=> $count
    ];
}
function yjmoneyChange($type,$time,$user_id,$detail,$mark=''){
    
    
	$before = Db::name('user')->where(['id'=>$user_id])->value('commission_balance');
	$after =$before+$time;
	
	Db::name('yjmoney_water')->insert(['user_id'=>$user_id,'before'=>number_format($before,6),'aftre'=>number_format($after,6),
	'change'=>number_format($time,6),'type'=>$type,'detail'=>json_encode($detail),'mark'=>$mark,'add_time'=>date('Y-m-d H:i:s')]);
	Db::name('user')->where(['id'=>$user_id])->setInc('commission_balance', $time);
}
function htzengyueChange($type,$time,$user_id,$detail){
	$before = Db::name('user')->where(['id'=>$user_id])->value('zyue');
	$after =$before+$time;
	Db::name('htzyue_water')->insert(['user_id'=>$user_id,'before'=>number_format($before,6),'aftre'=>number_format($after,6),
	'change'=>number_format($time,6),'type'=>$type,'detail'=>json_encode($detail),'add_time'=>date('Y-m-d H:i:s')]);
	Db::name('user')->where(['id'=>$user_id])->setInc('zyue', $time);
}
function basicmoneyChange($type,$time,$user_id,$detail){
	$before = Db::name('user')->where(['id'=>$user_id])->value('basic_balance');
	$after =$before+$time;
	Db::name('basicmoney_water')->insert(['user_id'=>$user_id,'before'=>number_format($before,6),'aftre'=>number_format($after,6),
	'change'=>number_format($time,6),'type'=>$type,'detail'=>json_encode($detail),'add_time'=>date('Y-m-d H:i:s')]);
	Db::name('user')->where(['id'=>$user_id])->setInc('basic_balance', $time);
}
function licaimoneyChange($type,$time,$user_id,$detail){
	$before = Db::name('user')->where(['id'=>$user_id])->value('licai_balance');
	$after =$before+$time;
	Db::name('licaimoney_water')->insert(['user_id'=>$user_id,'before'=>number_format($before,6),'aftre'=>number_format($after,6),
	'change'=>number_format($time,6),'type'=>$type,'detail'=>json_encode($detail),'add_time'=>date('Y-m-d H:i:s')]);
	Db::name('user')->where(['id'=>$user_id])->setInc('licai_balance', $time);
}
function sysjifenChange($type,$time){
	$before = Db::name('sys_jifen')->where(['id'=>1])->value('jifen');
	$leiji_chongzhi = Db::name('sys_jifen')->where(['id'=>1])->value('leiji_chongzhi');
	$after =$before+$time;
	if($after >$leiji_chongzhi){
//		return;
	}
	Db::name('sysjifen_water')->insert(['before'=>number_format($before,6),'aftre'=>number_format($after,6),
	'change'=>number_format($time,6),'type'=>$type,'detail'=>json_encode([]),'add_time'=>date('Y-m-d H:i:s')]);
	Db::name('sys_jifen')->where(['id'=>1])->setInc('jifen', $time);
}
function getOrderNo(){
	list($msec, $sec) = explode(' ', microtime());
    $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    $suijishu = rand(0, 999999);
    if($suijishu<10){
    	$suijishu='00000'.$suijishu;
    }
    if($suijishu>=10&&$suijishu<100){
    	$suijishu='0000'.$suijishu;
    }
    if($suijishu>=100&&$suijishu<1000){
    	$suijishu='000'.$suijishu;
    }
    if($suijishu>=1000&&$suijishu<10000){
    	$suijishu='00'.$suijishu;
    }
    if($suijishu>=10000&&$suijishu<100000){
    	$suijishu='0'.$suijishu;
    }
	 return $msectime.$suijishu;
}
function getcol( $ziduan){
	$col = [];
	foreach($ziduan as $val){
		if(!empty($val)){
			$col[$val] = lang($val);
		}
	}
	return $col;
}
function getyuming(){
// 	return getConfig('houtaiyuming',0);
	return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/';
}
function fontcolor($text,$color){
	return "<font style='color:$color;'>$text</font>";
}
function deletepic($pic){
	if($pic=='defaulhead.png'){
	}else{
		if(!empty($pic)&&file_exists(ROOT_PATH.'public/upload/goodpic/'.$pic)){
			unlink(ROOT_PATH.'public/upload/goodpic/'.$pic);
		}
	}
}
function je($data){
	echo  json_encode($data,JSON_UNESCAPED_UNICODE);
}
function zhuanzhang($huobi,$money,$toaddress){


        // echo('提现货币:'.$huobi.' 提现金额:'.$money.' 提现地址:'.$toaddress."\n");

	if($huobi == 'TRX'){
		$dakuan = getConfig('dakuan_trx',0);
		$dakuan = json_decode($dakuan,true);
		$dakuan['key'] = '0x'.$dakuan['key'];
        // $wallet = new \Tron\TRX($api, $config);
        $wallet = tronapi('TRX');
	}elseif($huobi == 'USDT'){
		$dakuan = getConfig('dakuan_usdt',0);
		$dakuan = json_decode($dakuan,true);
		$dakuan['key'] = '0x'.$dakuan['key'];
		
// 		var_dump($dakuan);
        // $wallet = new \Tron\TRC20($api, $config);
        $wallet = tronapi('TRC20');
	}
	$from = new \Tron\Address($dakuan['address'],$dakuan['key'],'');
	$from->hexAddress =  $wallet->tron->address2HexString($from->address);
	$to = new \Tron\Address($toaddress,'','');
	$to->hexAddress =  $wallet->tron->address2HexString($to->address);
	$tranResult = $wallet->transfer($from, $to,$money);
	return $tranResult;
}
function jiajiemi($salt,$target){
	$one = isset($salt[0])?$salt[0]:'a';
	$three = isset($salt[2])?$salt[2]:'j';
	$jiaohuan1 = ord($one)%34;
	$jiaohuan2 = ord($three)%34;
	if($jiaohuan1 ==0||$jiaohuan1==1){
		$jiaohuan1 = 15;
	}
	if($jiaohuan2 ==0||$jiaohuan2==1){
		$jiaohuan2 = 18;
	}
	echo '</br>'.$target;
	if(isset($target[$jiaohuan1])&&isset($target[$jiaohuan2])){
		$temp = $target[$jiaohuan1];
		$target[$jiaohuan1] = $target[$jiaohuan2];
		$target[$jiaohuan2] = $temp;
		echo '</br>重置了';
	}
	echo '</br>'.$target;
}
function autotixian($pkid){
	DB::name('caozuo')->where(['pk_id'=>$pkid,'type'=>'autotixian'])->delete();
	$caozuotemp = ['pk_id'=>$pkid,'type'=>'autotixian','add_time'=>date('Y-m-d H:i:s'),
	'op_time'=>date('Y-m-d H:i:s'),'extra'=>json_encode([])];
	DB::name('caozuo')->insert($caozuotemp);
//	redisCache()->lrem(getRedisXM('tixian_duilie'),0,$pkid);
//	redisCache()->rpush(getRedisXM('tixian_duilie'),$pkid);
}
function xvyaoguiji($qianbao,$huobi,$extra=0,$pkid=0){
    
    $guiji = DB::name('caozuo')->where(['pk_id'=>$pkid,'type'=>'guijianimei','qianbao'=>$qianbao,'huobi'=>$huobi])->find();
    
	//记得如果第二次转，就加一分钟再转$op_time = date('Y-m-d H:i:s',(time()+60))暂时不需要
	$op_time = date('Y-m-d H:i:s',(time()+60));//归集全部推迟1分半钟，因为用API获取余额由延迟
	$caozuotemp = ['pk_id'=>$pkid,'type'=>'guijianimei','add_time'=>date('Y-m-d H:i:s'),
	'op_time'=>$op_time,'extra'=>$extra,'qianbao'=>$qianbao,'huobi'=>$huobi];
	if(empty($guiji))
	{
	    DB::name('caozuo')->insert($caozuotemp);
	}else
	{
	    DB::name('caozuo')->where(['id'=>$guiji['id']])->update($caozuotemp);
	}
}
function jisuanValue($money,$jisuanHuobi){
	if(ZHB == $jisuanHuobi){
		return $money;
	}
    $huilv = getConfig('usdt2trx',0);
    if(ZHB == 'USDT'){
       $money = bcdiv($money,$huilv,6);
    }else{
	   $money = bcmul($huilv,$money,6);
    }
	return $money;
}
function shengjiVip($userID){
		$userInfo = Db::name('user')->where(['id'=>$userID])->find();
		if(empty($userInfo)){
			return '';
		}
		$heshiVip = Db::name('vip_set')->where(['up_money'=>['elt',$userInfo['basic_balance']]])
		->order('up_money desc')->find();
		if(empty($heshiVip)||$heshiVip['level']<=$userInfo['vip_level']){//新的比旧的高
			return '';
		}
		//升级
		$lock_key = getRedisXM('shengjivip'.$userID);
    	$is_lock = redisCache()->setnx($lock_key, 2); 
    	if($is_lock){
			redisCache()->expire($lock_key, 1);
		}else{
			// 防止死锁
			if(redisCache()->ttl($lock_key) == -1){
				redisCache()->expire($lock_key, 2);
			}
			ajaxReturn(0,'ajax_服务器缓缓');
		}
		Db::name('user')
		->where(['id'=>$userID])
		->update(['vip_level'=>$heshiVip['level'],'tmp_task'=>-1]);
		
		$newVipInfo =$heshiVip['task_money'];
		
		//充值马上就有任务
// 		$randtask = getOnerenwu($heshiVip['level']);
		$today = date('Y-m-d');
		$uplevel_cztx = getConfig('uplevel_cztx',0);
		if($newVipInfo>0&&$uplevel_cztx ==1){
		    
		    $level = $heshiVip['level'];
		    $viptaskCount = Db::name('vip_set')->where(['level'=>$level])->value('task_count');
			if($viptaskCount<=0){
				$viptaskCount = 1;
			}
			
		    $hongbaofenpei = hongbaosuanfa1($newVipInfo,$viptaskCount);
		    
		    $randList = Db::name('task')->where(['vip_level'=>$level])->orderRaw('rand()')->limit($viptaskCount)->select();
			$biaoji[] = '找专属VIP';
			if(count($randList)<$viptaskCount){
				$haicha = $viptaskCount-count($randList);
				$biaoji[] = '专属VIP任务数量不够，再找'.$haicha;
				$randList_haicha = Db::name('task')->where([])->orderRaw('rand()')->limit($haicha)->select();
				$randList = array_merge($randList,$randList_haicha);
			}
		    
		    $today = date('Y-m-d');
		    $usertask = [];
				foreach($randList as $k=> $val){
					$money = isset($hongbaofenpei[$k])?$hongbaofenpei[$k]:0.000001;
					$usertask[] = ['user_id'=>$userID,'task_id'=>$val['id'],'taskname'=>$val['taskname'],
					'day'=>$today,'status'=>1,'money'=>$money,'add_time'=>date('Y-m-d H:i:s'),'type'=>1,'vip'=>$level];
				}
			Db::name('user_task')->insertAll($usertask);
		    
		    
		    
		    
		  //  $log_file = APP_PATH . 'recharge_Usdt_callback.log';
            
            // file_put_contents($log_file, date('Y-m-d H:i:s') . ': ' . $level . "\n", FILE_APPEND);
		 	// $usertaskdata = ['user_id'=>$userID,'task_id'=>$randtask['id'],'taskname'=>$randtask['taskname'],
		 	// 'day'=>$today,'status'=>1,'money'=>$newVipInfo,'type'=>2,'add_time'=>date('Y-m-d H:i:s')];
		 	// Db::name('user_task')->insert($usertaskdata);
		}
}





function hongbaosuanfa1($total,$num){
		$min = 0.000001;
		if($total<($min*$num)){
			$total = $min*$num;
		}
		$money_arr=array(); //定义空数组 ，存入结果
		for ($i=1;$i<$num;$i++)
		{
		    $safe_total=($total-($num-$i)*$min)/($num-$i);//随机安全上限
		    // 解释上面代码：
		    // 20-（10-1）*0.01 / 10-1
		    // 20-（10-2）*0.01 / 10-2
		    // 20-（10-3）*0.01 / 10-3
		    // .......
		    $money= mt_rand($min*1000000,$safe_total*1000000)/1000000;
		    $total=$total-$money;
		    $money_arr[]= $money;
		}
		$money_arr[] = round($total,6);
		return  $money_arr;
	}

function getOnerenwu($viplevel){
	$randList = Db::name('task')->where(['vip_level'=>$viplevel])->orderRaw('rand()')->limit(1)->find();
	if(empty($randList)){
		$randList = Db::name('task')->where([])->orderRaw('rand()')->limit(1)->find();
	}
	return $randList;
}
function getClientParam(){
	return ['base_uri' => 'https://api.trongrid.io','headers'=>['TRON-PRO-API-KEY'=>TRONKEY]];
}

//接口
    function tronapi($type)
    {
        
        $apiArr = Db::name('tronkey')->select();
	     $index = rand(0,count($apiArr)-1);
	     $apikey = $apiArr[$index];
	     
	     $timestamp = $apikey['day'];
	     if(date('Ymd', $timestamp) != date('Ymd')) {
	         //不是今天的，清零
	         $apikey['today_tron'] = 0;
	         $apikey['today_scan'] = 0;
	         $apikey['day'] = time();
	     }
	     
	     $apikey['today_tron']++;
	     $apikey['sum_tron']++;
	     
	     
	     $tronkey = $apikey['tronkey'];
	     
	     $dbquest = Db::name('tronkey')->where(['id'=>$apikey['id']])->update($apikey);
        
        $uri = 'https://api.trongrid.io';
        $api = new \Tron\Api(new Client(['base_uri' => $uri,'headers'=>['TRON-PRO-API-KEY'=> $tronkey]]));
        //'cb4e98c7-223e-4e57-bd8b-e6feefcb7010'
         $config = [
	          'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',// USDT TRC20
	          'decimals' => 6,
	       ];
        
        if($type == 'TRX'){
            $wallet = new \Tron\TRX($api, $config);
        }else
        {
            $wallet = new \Tron\TRC20($api, $config);
        }
        
        
        return $wallet;
    }
    function http_get($url)
	{

	    $headers[] = "Content-Type: application/json";
	    
	    $apiArr = Db::name('tronkey')->select();
	     $index = rand(0,count($apiArr)-1);
	     $apikey = $apiArr[$index];
	     
	     $timestamp = $apikey['day'];
	     if(date('Ymd', $timestamp) != date('Ymd')) {
	         //不是今天的，清零
	         $apikey['today_tron'] = 0;
	         $apikey['today_scan'] = 0;
	         $apikey['day'] = time();
	     }
	     
	     if(strpos($url,'tronscanapi'))
	    {
	        $today = $apikey['today_scan'];
	        $sum = $apikey['sum_scan'];
	        
	        $apikey['today_scan'] = $today+1;
	        $apikey['sum_scan'] = $sum+1;
	        $tronkey = $apikey['scankey'];
	        $headers[] = "TRON-PRO-API-KEY:".$tronkey;
	        Db::name('tronkey')->where(['id'=>$apikey['id']])->update($apikey);
	        
	    }elseif(strpos($url,'trongrid')) {
	        
	        $today = $apikey['today_tron'];
	        $sum = $apikey['sum_tron'];
	        
	        $apikey['today_tron'] = $today+1;
	        $apikey['sum_tron'] = $sum+1;
	        
	        $tronkey = $apikey['tronkey'];
	        $headers[] = "TRON-PRO-API-KEY:".$tronkey;
	        Db::name('tronkey')->where(['id'=>$apikey['id']])->update($apikey);
	    }
	    
	    $curl = curl_init(); // 启动一个CURL会话
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_TIMEOUT,5);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    
	    //这里指定网卡
	   // $typearr = ['eth0','eth0:0','eth0:1'];
	   // $type = $typearr[rand(0,count($typearr)-1)];
    //     curl_setopt($curl,  CURLOPT_INTERFACE,$type);
	    
	    $tmpInfo = curl_exec($curl);     
	    //关闭URL请求
	    curl_close($curl);
	    return $tmpInfo;   
	}
    
    function http_get2($url)
	{

	    $headers[] = "Content-Type: application/json";
	    
	    $curl = curl_init(); // 启动一个CURL会话
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_TIMEOUT,5);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    
	    //这里指定网卡
	   // $typearr = ['eth0','eth0:0','eth0:1'];
	   // $type = $typearr[rand(0,count($typearr)-1)];
    //     curl_setopt($curl,  CURLOPT_INTERFACE,$type);
	    
	    $tmpInfo = curl_exec($curl);     
	    //关闭URL请求
	    curl_close($curl);
	    return $tmpInfo;   
	}