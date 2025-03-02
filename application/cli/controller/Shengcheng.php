<?php
namespace app\cli\controller;
use think\Controller;
use think\Request;
use GuzzleHttp\Client;
use think\ Db;
class Shengcheng extends Controller {
	  public function index()
    {
        $uri = 'https://api.trongrid.io';// mainnet
        $api = new \Tron\Api(new Client(['base_uri' => 'https://api.trongrid.io','headers'=>['TRON_PRO_API_KEY'=>getConfig('tronkey',0)]]));
        
        
		$add_count = Db::name('addressdizhi')->where(['user_id'=>'0'])->count();
		if($add_count >100)
		{
		    echo("目前空闲的钱包地址很多，无需新增\n");
			exit();
		}
		
        
        $config = [
            'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',// USDT TRC20
            'decimals' => 6,
        ];

        $trc20Wallet = new \Tron\TRC20($api, $config);
        for($i=0;$i<=20;$i++){
	        try {
	        	$addressData = $trc20Wallet->generateAddress();
	       // 	$hefabu =  $trc20Wallet->validateAddress($addressData);
	        	$hefabu = 1;
	        } catch(InvalidArgumentException   $e) {
	        	continue;
        	}
        	if($hefabu){
////      	    echo '合法';
        	    $pool='qwertyuioplkjhgfdsazxcvbnmn';//定义一个验证码池，验证码由其中几个字符组成
				$word_length=6;//验证码长度
			  	$salt = '';//盐值
			    for ($i = 0, $mt_rand_max = strlen($pool) - 1; $i < $word_length; $i++)
			    {
			        $salt .= $pool[mt_rand(0, $mt_rand_max)];
			    }
//			    $ordNum = 0;
//			    $dierge = isset($salt[1])?$salt[1]:'b';
//			    $asci = ord($dierge);
//			    $strlen_prikey =strlen($addressData->privateKey);
//			    $yushu = $asci%$strlen_prikey;
//			    echo "第二个$dierge,asci=$asci"."余数：$yushu";
//			    echo '</br>'.$addressData->privateKey;
//			    if(isset($addressData->privateKey[$yushu])){
//			    	$addressData->privateKey[$yushu] = $dierge;
//			    }
//			    echo '</br>'.$addressData->privateKey;
        	    Db::name('addressdizhi')->insert(['privateKey'=>$addressData->privateKey,'address'=>$addressData->address,
        	    'hexAddress'=>$addressData->hexAddress,'add_time'=>date('Y-m-d H:i:s'),'salt'=>$salt]);
        	}else{
        	     echo '不合法';
        	}
//      	exit();
        }
    }
}