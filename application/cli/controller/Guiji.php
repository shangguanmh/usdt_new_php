<?php
namespace app\cli\controller;

use think\Controller;
use SignatureHelper;
use think\Db;
use GuzzleHttp\Client;
use Tron\Address;
use Tron\Api;
use Tron\TRC20;

class Guiji extends Controller
{
//	private $model = 'zhengshi';
	private $model = 'ceshi';
	private $key = '7479176d-30ba-4e29-8ed3-e8d9a83ac589';
	private $guijizhanghu = 'TRdrtu3xwZhp135VJmPrfPPD4n5RqEMd5v';  //TKBmX7Wq9MEAbihXHrbQDVCP4L2x51PEXh
	private $siteKey = 'trx';
	
	function http_get($url)
	{
	    
	    
	   // $headers = array();
	    $headers[] = "Content-Type: application/json";
	    
	    if(strpos($url,'tronscanapi'))
	    {
	        //波场key
	        $headers[] = "TRON-PRO-API-KEY:4e31110e-4a04-48d5-a828-b510cb5768cb";
	    }else {
	        $apikey = ['02808a87-1d51-499a-8174-8adcd70f7004','3e9177fb-d644-4c2a-9fb9-c1e03bcbe002','43dded1c-c209-4dfd-97fa-6fbe6114913f','7479176d-30ba-4e29-8ed3-e8d9a83ac589','744c6548-c60e-4652-9220-a22534731600','cb4e98c7-223e-4e57-bd8b-e6feefcb7010','655d1775-6c22-4bce-904c-f03d5a943650','9eda9a31-dcec-47da-ba18-1309df4b4cc6','8981ab20-8f57-4150-b92c-e54ba0dcbb87'];
	        $index = rand(0,count($apikey)-1);
	        $tronkey = $apikey[$index];
	        $headers[] = "TRON-PRO-API-KEY:".$tronkey;
	    }
	    
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
	   // echo($tmpInfo);
	   
	    return $tmpInfo;   
	}

	function guiji(){
		$getdata = request() -> get();
        
        $guijizhanghu = 'TAZQv4FEHfz6SwvtP6A7gfGm7w1UCmJ2xs';  //TKBmX7Wq9MEAbihXHrbQDVCP4L2x51PEXh
        
        $uri = 'https://api.trongrid.io';
        $api = new \Tron\Api(new Client(['base_uri' => 'https://api.trongrid.io','headers'=>['TRON-PRO-API-KEY'=> '7479176d-30ba-4e29-8ed3-e8d9a83ac589']]));
         $config = [
	          'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',// USDT TRC20
	        'decimals' => 6,
	           // 'address' => 'TPipV2NhmTKsiPDRc78YUWt4n9FYxVBqbT'
	       ];
        
        $trc20Wallet = new \Tron\TRC20($api, $config);
        
        // $from = $trc20Wallet->privateKeyToAddress("7479176d-30ba-4e29-8ed3-e8d9a83ac589");
        
		$list = Db::connect(config($this->siteKey))->name('caozuo')->where(['type'=>'guijianimei'])->select();
		
		if(!count($list))
		{
		    echo("已经空了，可以换一个 \n");
		}else
		{
		    echo("目前剩余:".count($list)."\n");
		}
		
		$list = array_slice($list,0,20);
// 		$caozuo = Db::connect(config($this->siteKey))->name('caozuo')->select();
		
	   // exit();
		foreach($list as $val){
			$lock_key = getRedisXM('guijiid'.$val['id']);
			$is_lock = redisCache()->setnx($lock_key, 1); 
			$extra = json_decode($val['extra'],true);
			
	    	if($is_lock){
				redisCache()->expire($lock_key, 15);
			}else{
				echo '锁住了哦';
				redisCache()->expire($lock_key, 15);
				continue;
			}
			
//			$invest_order = Db::connect(config($this->siteKey))->name('invest_order')->find($val['pk_id']);
			$address = $val['qianbao'];
			$token = $val['huobi'];
			
			//查询usdt余额
			 $naddress = new address(
                     $address,
                     '',
                 $trc20Wallet->tron->address2HexString($address)
             );
            
             $usdtyue = $trc20Wallet->balance($naddress);  //查询usdt
			$usdtyue = $usdtyue *1;
			//查trx
            $balance = $trc20Wallet->tron->getBalance($address);
            $trxyue = $balance*0.000001;
            $trxyue= number_format($trxyue,3,'.','');
            
            if(is_null($trxyue) && is_null($usdtyue))
            {
                echo("退出");
                return;
            }
            
            
            if($trxyue>0.01 || $usdtyue>0)
            {
                echo("-------------钱包地址:".$address."\n");
                if($trxyue > 0.01)
                {
                   echo("trx余额:".$trxyue."<br>"); 
                }
                if($usdtyue >0)
                {
                    echo("usdt余额:".$usdtyue."<br>-----------\n");
                }
	             
            }
            
            $neng = $this->getEnergy($address);

            if($usdtyue > 0)
            {
                
                if($neng < 3200)
                {
                    //买能量
                    $this->zhuanzhang($address);
                }
                //归集usdt
                $zuidizhi = 14;//想转U好贵好贵
                //看看TRX够不够
					if($trxyue<$zuidizhi){//不够10TRX，转10进去
						if($extra==0){
							$money = bcsub($zuidizhi,$trxyue,3);
							$zhuanzhangResult = $this->nzhuanzhang('TRX',$money,$address);
							echo("转账trx手续费");
							if(isset($zhuanzhangResult->txID)){
								//标志这一次转账是系统转的，不要让下一笔用户识别这笔是他充值
	   							Db::connect(config($this->siteKey))->name('addressdizhi')->where(['address'=>$address])->update(['lasthash_id'=>$zhuanzhangResult->txID]);
							}
//							echo '</br>转账结果：'.json_encode($zhuanzhangResult);
							$this->xvyaoguiji($address,'USDT',1);//下一分钟再去转USDT
							echo "\n 下一分钟再去转USDT \n";
						}else{
							echo "\n 不是第一次了，不转了\n";
						}
					}
					if($trxyue>=$zuidizhi){
				// 		$guijizhanghu = getConfig('guijizhanghu',0);
						$money = bcsub($usdtyue,$zuidizhi,3);
						$usdtre = $this->guijizhuanzhang($address,'USDT',$guijizhanghu,
						$usdtyue,$val['pk_id']);
						echo '</br>'.json_encode($usdtre);
						echo "\n USDT转账了 n";
						
						$this->xvyaoguiji($address,'TRX',1);//下一分钟再去转USDT
					}else{
						echo "\n TRX手续费不够10.不转了\n";
					}
                
            }elseif($trxyue>0)
            {
                if($token == 'USDT')
                {
                    $this->xvyaoguiji($address,'TRX',1);//下一分钟再去转USDT
                }else
                {
                //   $guijizhanghu = getConfig('guijizhanghu',0);
                   if($neng)
                   {
                       $money = bcsub($trxyue,0.3,3);
                   }else
                   {
                       $money = bcsub($trxyue,0,3);
                   }
	    			
	    			echo '</br>转账'.$money."TRX \n";
	    			$this->guijizhuanzhang($address,$token,$guijizhanghu,$money,$val['pk_id']);  
                }
                  
            }
            
            Db::connect(config($this->siteKey))->name('caozuo')->where(['id'=>$val['id']])->delete();
			redisCache()->del($lock_key); 
		}
	}
	
	
	//接口
    function tronapi($type)
    {
        
        $apikey = ['02808a87-1d51-499a-8174-8adcd70f7004',
	               '3e9177fb-d644-4c2a-9fb9-c1e03bcbe002',
	               '43dded1c-c209-4dfd-97fa-6fbe6114913f',
	               '7479176d-30ba-4e29-8ed3-e8d9a83ac589',
	               '744c6548-c60e-4652-9220-a22534731600',
	               'cb4e98c7-223e-4e57-bd8b-e6feefcb7010',
	               '655d1775-6c22-4bce-904c-f03d5a943650',
	               '9eda9a31-dcec-47da-ba18-1309df4b4cc6',
	               '8981ab20-8f57-4150-b92c-e54ba0dcbb87'];
	                    
	     $index = rand(0,count($apikey)-1);
	     $tronkey = $apikey[$index];
        
        $uri = 'https://api.trongrid.io';
        $api = new \Tron\Api(new Client(['base_uri' => $uri,'headers'=>['TRON-PRO-API-KEY'=> $tronkey]]));
        
        // $uri = 'https://api.trongrid.io';
        // $api = new \Tron\Api(new Client(['base_uri' => $uri,'headers'=>['TRON-PRO-API-KEY'=> $this->tron_key]]));
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
    
	
	//查询能量
    function getEnergy($add)
    {
        
        $add = 'TARsrzvAp1W6b9QN7MHyekksmpJ7YTz4Pg';
        //判断格式是否正确
        if(strlen($add) !=34)
        {
            return 0;
        }
        
        
        $trc20Wallet = $this->tronapi('TRC20');
	   	
	   	$info = $trc20Wallet->tron->getAccountResources($add); 
	   	
	   	if(empty($info['EnergyLimit']))
	   	{
	   	    return 0;
	   	}
	    
	    if(empty($info['EnergyUsed']))
	   	{
	   	    $EnergyUsed = 0;
	   	}else
	   	{
	   	    $EnergyUsed = $info['EnergyUsed'];
	   	}
	   	
        $EnergyLimit = $info['EnergyLimit'];
        
        
        return ($EnergyLimit-$EnergyUsed);
    }
    
	 public function zhuanzhang($add){
		
		$client = new Client();
        $headers = [
             'User-Agent' => 'Apifox/1.0.0 (https://apifox.com)',
            'Content-Type' => 'application/json'
        ];
        
     $key = 'E48d8E69Dc85D9424C1FDD2CBeE39731';
    // 待发送的数据包
    $body = array(
        'uid' => '378',
        'resource_type' => '0',
        'receive_address' => $add,
        'amount' => '33000',
        'freeze_day' => '1',
        'time' => time(),
    );
    // 发送的数据加上sign
    $body['sign'] = $this->getSign($key, $body);

    
    
     $request = new \GuzzleHttp\Psr7\Request('POST', 'https://api.tronqq.com/openapi/v2/order/submit', $headers, json_encode($body));
    
     $res = $client->sendAsync($request)->wait();
     
      echo $res->getBody();
      
		
	}
	
	// 获取sign
 function getSign($secret, $data) {
    // 对数组的值按key排序
    ksort($data);
    // 生成url的形式
    $params = http_build_query($data,'','');
    // 生成sign
    $params = str_replace('=','',$params);
    $str = $secret.$params;
    echo($str);
    $sign = md5($secret . $params);
    return $sign;
}

	function guijizhuanzhang($fromaddress,$huobi,$toaddress,$money,$invest_id){
		//真实网
		$api = new \Tron\Api(new Client(['base_uri' => 'https://api.trongrid.io','headers'=>['TRON-PRO-API-KEY'=> '7479176d-30ba-4e29-8ed3-e8d9a83ac589']]));
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
	    $xitongaddress = Db::connect(config($this->siteKey))->name('addressdizhi')->where(['address'=>$fromaddress])->find();
	    if(!empty($xitongaddress)){
	    	Db::connect(config($this->siteKey))->name('guiji_record')->insert(['from_address'=>$fromaddress,'to_address'=>$toaddress,'add_time'=>date('Y-m-d H:i:s')
	    	,'huobi'=>$huobi,'money'=>$money,'status'=>0,'invest_id'=>$invest_id]);
			$guiji_recordID = Db::connect(config($this->siteKey))->name('guiji_record')->getLastInsID();
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
			Db::connect(config($this->siteKey))->name('guiji_record')->where(['id'=>$guiji_recordID])->update(['status'=>1,'detail'=>json_encode($tranResult)]);
			return $tranResult;
	    }
	}
	
	
	function nzhuanzhang($huobi,$money,$toaddress){
	//真实网
	$api = new \Tron\Api(new Client(['base_uri' => 'https://api.trongrid.io','headers'=>['TRON-PRO-API-KEY'=> '744c6548-c60e-4652-9220-a22534731600']]));
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
	if($huobi == 'TRX'){
		$dakuan = getConfig('dakuan_trx',0);
		$dakuan = json_decode($dakuan,true);
		$dakuan['address'] = 'TRdrtu3xwZhp135VJmPrfPPD4n5RqEMd5v';
		$dakuan['key'] = 'f8b5ae023dde433fec7d3ff4e1c6dac3017e99abdb0037bb7df60d815a0cf33a';
        $wallet = new \Tron\TRX($api, $config);
	}elseif($huobi == 'USDT'){
		$dakuan = getConfig('dakuan_usdt',0);
		$dakuan = json_decode($dakuan,true);
		$dakuan['address'] = 'TRdrtu3xwZhp135VJmPrfPPD4n5RqEMd5v';
		$dakuan['key'] = 'f8b5ae023dde433fec7d3ff4e1c6dac3017e99abdb0037bb7df60d815a0cf33a';
        $wallet = new \Tron\TRC20($api, $config);
	}
	$from = new \Tron\Address($dakuan['address'],$dakuan['key'],'');
	$from->hexAddress =  $wallet->tron->address2HexString($from->address);
	$to = new \Tron\Address($toaddress,'','');
	$to->hexAddress =  $wallet->tron->address2HexString($to->address);
	$tranResult = $wallet->transfer($from, $to,$money);
	return $tranResult;
    }
    
    
     function yijianguiji(){
    // 	$list = Db::connect(config($this->siteKey))->name('guiji_record')->where(['status'=>0])->select();
    	$list = Db::connect(config($this->siteKey))->name('addressdizhi')->where('user_id','<>',0)->select();
    // 	var_dump($list);
    	foreach($list as $val){
    	    
    		$this->xvyaoguiji($val['address'],'USDT',0);
    	}
    	echo('操作成功，重新发起归集'.count($list).'条');

    }
    
    function xvyaoguiji($qianbao,$huobi,$extra=0){
	$caozuotemp = ['pk_id'=>0,'type'=>'guijianimei','add_time'=>date('Y-m-d H:i:s'),
	'op_time'=>date('Y-m-d H:i:s'),'extra'=>$extra,'qianbao'=>$qianbao,'huobi'=>$huobi];
	Db::connect(config($this->siteKey))->name('caozuo')->insert($caozuotemp);
    }
	
	
}
