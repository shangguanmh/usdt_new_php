<?php
namespace app\cli\controller;

use think\Controller;
use SignatureHelper;
use think\Db;
use GuzzleHttp\Client;
use app\api\controller\Lottery;
use GoogleAuthenticator; 
use app\cli\controller\Auto as autocon;
use  app\cli\util\Util;

class Test extends Controller
{
    
    function mainengliang(){
        Util::mainengliang('TGsaiPfz7nTj5oRcVVXiycQ1m4hZSj8HDn');
    }
    
	function zhuanzhang(){
		$autocon = new autocon();
    // 	$re_trx = $autocon->getUSDT('TSpQBC6VX5sQJSQXAmYEqtVmAt15mhubut');
    	
    // 	echo('trx='.$re_trx);
    
    
    yjmoneyChange('bh_充值返利','0.295987',1,[],'IN1700398074688589504');
	}
	function chulitichengchongzhi(){
		$list = Db::name('chognzhifanli')->where(['jibie'=>['in',[2,3]]])->select();
		foreach($list as $val){
			$findone = Db::name('yjmoney_water')->where(['add_time'=>$val['add_time'],'user_id'=>$val['user_id'],'type'=>'bh_充值返利'])->find();
			if(!empty($findone)){
				 Db::name('chognzhifanli')->where(['id'=>$val['id']])->update(['money'=>$findone['change']]);
			}else{
				echo '</br>找不到ID'.$val['id'];
			}
//			exit();
		}
	}
	function shoudongguiji(){
//		exit();
		$list = Db::name('invest_order')->where(['status'=>2,'id'=>['egt',242]])->select();
		foreach($list as $val){
			$domain = 'https://apilist.tronscanapi.com';
			if(MOSHI =='ceshi'){
				$domain = 'https://shastapi.tronscan.org';
			}
			$address = $val['to_address'];
			$invest_id = $val['id'];
			$url = "$domain/api/account/tokens?address=$address&start=0&limit=20&token=trx&hidden=0&show=0&sortType=0";
			$resu = $this->http_get($url);
			$resu = json_decode($resu,true);
			$trxyue = isset($resu['data'][0]['amount'])?$resu['data'][0]['amount']:0;
			$token = $val['huobi'];
//			echo "</br>invest_id= $invest_id , $address 余额".$trxyue.'TRX';
			if(isset($resu['data'][0]['tokenAbbr'])&&isset($resu['data'][0]['amount'])&&
			$resu['data'][0]['tokenAbbr'] =='trx'&&$trxyue>0.001){
				
				echo '</br>余额'.$resu['data'][0]['amount'].'|'.$val['to_address'];
				
    			$guijizhanghu = getConfig('guijizhanghu',0);
    			$money = $trxyue;
    			echo "</br>$address 转账".$money.'TRX';
    			$this->guijizhuanzhang($address,'TRX',$guijizhanghu,$money,$invest_id);
//  			exit();
			}else{
			}
		}
	}
	function chakanUSDT(){
//		exit();
		$list = Db::name('invest_order')->where(['status'=>2,'id'=>['egt',1]])->select();
		foreach($list as $val){
			$domain = 'https://apilist.tronscanapi.com';
			if(MOSHI =='ceshi'){
				$domain = 'https://shastapi.tronscan.org';
			}
			$address = $val['to_address'];
			$invest_id = $val['id'];
			$url = "$domain/api/account/tokens?address=$address&start=0&limit=20&token=usdt&hidden=0&show=0&sortType=0";
			$resu = $this->http_get($url);
			$resu = json_decode($resu,true);
			$balance = isset($resu['data'][0]['balance'])?$resu['data'][0]['balance']:0;
			$balance = bcdiv($balance,1000000,6);
			
			$token = $val['huobi'];
//			echo "</br>invest_id= $invest_id , $address 余额".$trxyue.'TRX';
			if(isset($resu['data'][0]['tokenAbbr'])&&isset($resu['data'][0]['balance'])&&
			$resu['data'][0]['tokenAbbr'] =='USDT'&&$balance>1){
				xvyaoguiji($val['to_address'],$val['huobi'],0);
//				exit();
//				echo '</br>余额'.$balance.'|'.$val['to_address'];
//  			$guijizhanghu = getConfig('guijizhanghu',0);
//  			$money = $balance;
//  			echo "</br>$address 转账".$money.'USDT';
//  			exit();
//  			$this->guijizhuanzhang($address,'TRX',$guijizhanghu,$money,$invest_id);
			}else{
			}
			Db::name('text')->insert(['text'=>'处理了提现的'.$val['id'],'add_time'=>date('Y-m-d H:i:s')]);
			
		}
	}
	function http_get($url)
	{
	    $headers[] = "Content-Type: application/json";
	    $headers[] = "TRON_PRO_API_KEY:12e722cf-bd91-4266-9aff-18778bf7a21f";
	    $curl = curl_init(); // 启动一个CURL会话
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_TIMEOUT,8);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $tmpInfo = curl_exec($curl);     
	    //关闭URL请求
	    curl_close($curl);
	    return $tmpInfo;   
	}
		function guijizhuanzhang($fromaddress,$huobi,$toaddress,$money,$invest_id){
		//真实网
		$api = new \Tron\Api(new Client(['base_uri' => 'https://api.trongrid.io']));
	    $config = [
	        'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',// USDT TRC20
	        'decimals' => 6,
	    ];
	    //测试网
	    if(MOSHI =='ceshi'){
	    	$api = new \Tron\Api(new Client(['base_uri' => 'https://api.shasta.trongrid.io']));
	    	$config = [
	    	    'contract_address' => 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs',// USDT TRC20
	    	    'decimals' => 6,
	    	];
	    }
	    $xitongaddress = DB::name('addressdizhi')->where(['address'=>$fromaddress])->find();
	    if(!empty($xitongaddress)){
	    	Db::name('guiji_record')->insert(['from_address'=>$fromaddress,'to_address'=>$toaddress,'add_time'=>date('Y-m-d H:i:s')
	    	,'huobi'=>$huobi,'money'=>$money,'status'=>0,'invest_id'=>$invest_id]);
			$guiji_recordID = Db::name('guiji_record')->getLastInsID();
			if($huobi == 'TRX'){
		        $wallet = new \Tron\TRX($api, $config);
			}elseif($huobi == 'USDT'){
		        $wallet = new \Tron\TRC20($api, $config);
			}
			$from = new \Tron\Address($xitongaddress['address'],$xitongaddress['privateKey'],'');
			$from->hexAddress =  $wallet->tron->address2HexString($from->address);
			$to = new \Tron\Address($toaddress,'','');
			$to->hexAddress =  $wallet->tron->address2HexString($to->address);
			$tranResult = $wallet->transfer($from, $to,$money);
			Db::name('guiji_record')->where(['id'=>$guiji_recordID])->update(['status'=>1,'detail'=>json_encode($tranResult)]);
			return $tranResult;
	    }
	}
	
	function wakuang(){
		
	}
	function wakuangdaozhang(){}
	function chuli(){
			$userList = Db::name('user')->where(['add_time'=>['gt','2023-03-09 01:00:00']])->select();
			$uisd = array_column($userList,'id');
			$yjmoney_water = Db::name('yjmoney_water')->where(['type'=>'bh_挖矿收益'])->where('add_time >="2023-03-11 09:04:58" and add_time<="2023-03-11 09:05:51"')->select();
			
//			echo Db::name('user')->getlastsql();
			foreach($yjmoney_water as $val){
				if(in_array($val['user_id'],$uisd)){
					Db::name('user')->where(['id'=>$val['user_id']])->setInc('commission_balance', -$val['change']);
					Db::name('yjmoney_water')->where(['id'=>$val['id']])->delete();
//					echo $val['user_id'];exit();
				}
			}
	}
	
	function chulichangjianwenti(){
		$list = Db::name('config')->where(['config_sign'=>'changjianwenti'])->select();
		foreach($list as $val){
			$val['config_value'] = str_replace('56usdt.com','vptrx.com',$val['config_value']);
			$list = Db::name('config')->where(['id'=>$val['id']])->update(['config_value'=>$val['config_value']]);
		}
	}
	
	function testchoujiang(){
		$roomInfo = Db::name('lottery_room')->where(['id'=>1])->find();
		for($i=1;$i<=100;$i++){
		$this->choujiangDeal($roomInfo,1);
		}
	}
	function choujiangDeal($roomInfo,$user_id){
 		$lock_key = getRedisXM('choujiang'.$user_id);
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
   		$roomInfo['shangpin'] = json_decode($roomInfo['shangpin'],true);
   		$zhongjianggailv = [];
   		$shangpinKeyVal = [];
   		foreach($roomInfo['shangpin'] as $val){
   			$shangpinKeyVal[$val['index']] = $val;
   			$zhongjianggailv[$val['index']] = $val['xyz'];
   		}
   		yjmoneyChange('bh_抽奖',-$roomInfo['cost'],$user_id,[]);
   		$zhongjiang = $this->get_rand($zhongjianggailv);
   		if(!isset($shangpinKeyVal[$zhongjiang])){
     	   feifaReturn(2);
   		}
   		Db::name('choujiang')->insert(['user_id'=>$user_id,'cost'=>$roomInfo['cost']
   		,'jiangpin_name'=>$shangpinKeyVal[$zhongjiang]['name']
   		,'jiangpin_type'=>$shangpinKeyVal[$zhongjiang]['jiang_type']
   		,'jiangpin_count'=>$shangpinKeyVal[$zhongjiang]['count']
   		,'zhongjiang_index'=>$zhongjiang
   		,'add_time'=>date('Y-m-d H:i:s'),'room_id'=>$roomInfo['id']]);
		redisCache()->del($lock_key);
		$msg = '恭喜你，你抽中的奖品是'.$shangpinKeyVal[$zhongjiang]['name'];
		return ['msg'=>$msg,'zhongjiang'=>$zhongjiang];
 	}
 	function get_rand($proArr) { 
 		//$proArr=[["id"=>gailv]]
 		//$proArr=[["1"=>2,'2'=>3,'3'=>55]]
	 $result = ''; 
	 //概率数组的总概率精度 
	 $proSum = array_sum($proArr); 
	 //概率数组循环 
	 foreach ($proArr as $key => $proCur) { 
	  $randNum = mt_rand(1, $proSum); 
	  if ($randNum <= $proCur) { 
	   $result = $key; 
	   break; 
	  } else { 
	   $proSum -= $proCur; 
	  }  
	 } 
	 unset ($proArr); 
	 return $result; 
} 

	function ceshimiyao(){
		$domain = 'https://apilist.tronscanapi.com';
		$url = "$domain/api/account/tokens?address=$address&start=0&limit=20&token=trx&hidden=0&show=0&sortType=0";
	    $headers[] = "Content-Type: application/json";
	    $headers[] = "TRON_PRO_API_KEY:2be174e5-aa16-446d-81c4-b91b6fb44369";
	    $curl = curl_init(); // 启动一个CURL会话
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_TIMEOUT,5);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $tmpInfo = curl_exec($curl);     
	    //关闭URL请求
	    curl_close($curl);
	    echo $tmpInfo;
	}
	function ceshiyue(){
		$fromaddress = 'TLprX8KxeCgC7H1UoyUkZtZ5Cc7pQtAkGt';
		$huobi = 'TRX';
		echo $this->getBalannce($fromaddress,$huobi);
	}
	function ceshiheader(){
		echo '</br>'.json_encode(zhuanzhang('TRX','0.001','TWiyNxQK4MP9mt65zENUfDJ4ALtJNmJDKx'));
	}
	function getBalannce($address,$huobi){
		//真实网
		$api = new \Tron\Api(new Client(getClientParam()));
	    $config = [
	        'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',// USDT TRC20
	        'decimals' => 6,
	    ];
	    //测试网
			if($huobi == 'TRX'){
		        $wallet = new \Tron\TRX($api, $config);
			}elseif($huobi == 'USDT'){
		        $wallet = new \Tron\TRC20($api, $config);
			}
			$address = new \Tron\Address($address,'','');
			$address->hexAddress =  $wallet->tron->address2HexString($address->address);
			$money = $wallet->balance($address);
			if(empty($money)){
				$money = 0;
			}
			echo  $money;
	
	}
	public function shengjivipaaa(){
	    $userID = 2462;
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
// 		Db::name('user')->where(['id'=>$userID])->update(['vip_level'=>$heshiVip['level']]);
		$newVipInfo =$heshiVip['task_money'];
		//充值马上就有任务
		$randtask = getOnerenwu($heshiVip['level']);
		$today = date('Y-m-d');
		if($newVipInfo>0){
		 	$usertaskdata = ['user_id'=>$userID,'task_id'=>$randtask['id'],'taskname'=>$randtask['taskname'],
		 	'day'=>$today,'status'=>1,'money'=>$newVipInfo,'type'=>2,'add_time'=>date('Y-m-d H:i:s')];
		 	Db::name('user_task')->insert($usertaskdata);
		}

	}
	public function gugeyanzheng(){
		$ga = new GoogleAuthenticator();
		if ($ga->verifyCode('CDEIZTTMXZVYD6YY', '632364', 2)) {
			echo '验证成功';
		} else {
			echo '验证失败';
		}
	}
}
