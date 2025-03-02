<?php
namespace app\api\controller;
use think\ Db;
use think\Request;
use think\Controller;
use think\Lang;
use GuzzleHttp\Client;
class Invest extends Base {
   	public function investInfo(){
   	   $address = '';
   	   $qianbao = Db::name('addressdizhi')->where(['user_id'=>$this->userInfo['id']])->value('address');
   	   if(empty($qianbao)){
   	   		$noUserAddress = Db::name('addressdizhi')->where(['user_id'=>0])->order('id asc')->find();
   	   		if(!empty($noUserAddress)){
   	 		    $address = $noUserAddress['address'];
   	 		    Db::name('addressdizhi')->where(['id'=>$noUserAddress['id']])->update(['user_id'=>$this->userInfo['id']]);
   	   		}
   	   }else{
   	   		$address = $qianbao;
   	   }
	   $huilv = getConfig('usdt2trx',0);
	   $ziduan = ['col_地址','col_充值账户','col_基础账户','col_理财账户','col_充值地址','col_复制地址','col_你的钱包','col_输入钱包地址',
	   'col_充值金额','col_输入金额','text_充值提示1','text_充值提示2','text_充值提示3','text_充值提示6','text_充值提示5','col_充值完成'];
	   $col = getcol($ziduan);
	   $zduidiwenzi = getConfig('zuiditikuan_text',0);
	   $zduidiwenzi = empty($zduidiwenzi)?2:$zduidiwenzi;
	   $col['text_充值提示6'] = lang('text_充值提示6',[$zduidiwenzi,ZHB,$zduidiwenzi,ZHB]);
	   $col['text_充值提示5'] = lang('text_充值提示5',[ZHB]);
	   $huobi = ['USDT','TRX'];
	   $data = ['qianbao'=>$address,'huilv'=>$huilv,'huobi'=>$huobi];
       ajaxReturn(1, '成功',['text'=>$col,'data'=>$data]);
 	}
	public function postinvest(){
		$postdata = request() -> post();
        $to_address = isset($postdata["to_address"]) ? $postdata["to_address"] : '';
//      $from_address = isset($postdata["from_address"]) ? $postdata["from_address"] : '';
        $huobi = isset($postdata["huobi"]) ? $postdata["huobi"] : '';
        $to_balance = isset($postdata["to_balance"]) ? $postdata["to_balance"] : 1;
		if(empty($to_address)){
       		ajaxReturn(0, 'col_提款地址不正确');
		}
//		if(empty($from_address)){
//			feifaReturn(2);
//		}
//		if(!in_array($huobi,['USDT','TRX'])){
//			feifaReturn(3);
//		}
//		if($money<=0){
//     		ajaxReturn(0, 'col_金额非法');
//		}
		if(!in_array($to_balance, [1,2])){
			feifaReturn(5);
		}
		$order_num = 'IN'.getOrderNo();
//	  	$huilv = getConfig('usdt2trx',0);
//		$zuihou_value = $money;
//		if($huobi =='TRX'){
//			$zuihou_value = bcdiv($money,$huilv,6);
//		}
		      //防止多次连续操作
     	$lock_key = getRedisXM('chongzhi'.$this->userInfo['id']);
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
		$haveExit = Db::name('invest_order')->where(['user_id'=>$this->userInfo['id'],'status'=>1])->find();
		if(empty($haveExit)){
			$insertdata = ['to_address'=>$to_address,'order_num'=>$order_num,'user_id'=>$this->userInfo['id'],'to_balance'=>$to_balance,'add_time'=>date('Y-m-d H:i:s')];
			Db::name('invest_order')->insert($insertdata);
			$pkid = Db::name('invest_order')->getLastInsID();
			$caozuo = ['pk_id'=>$pkid,'type'=>'chongzhi','add_time'=>date('Y-m-d H:i:s'),
			'op_time'=>date('Y-m-d H:i:s'),'extra'=>json_encode([])];
			DB::name('caozuo')->insert($caozuo);
		}
		redisCache()->del($lock_key);
        ajaxReturn(1, 'text_充值提示4');
	}
	public function drawInfo(){
   	   $qianbao = 'TRNRn8ifEFWQWpp2SFQGRyzJcSMuz8zYyw';
	   $ziduan = ['col_佣金账户','col_24小时提款','col_可提款','col_提款手续费','col_使用次数','col_手续费','col_限额','col_torn地址','col_安全密码','col_次','col_出款方式'];
	   $col = getcol($ziduan);
	   $huobi = json_decode(getConfig('tikuanfangshi',0),true);
       $commission_balance = $this->userInfo['commission_balance'];
	   $huilv = getConfig('usdt2trx',0);
	   if(ZHB == 'USDT'){
		   	$keti_TRX = bcmul($huilv,$commission_balance,6);
		   	$keti_USDT = $commission_balance;
	   }else{
	   		$keti_TRX = $commission_balance;
		   	$keti_USDT = bcdiv($commission_balance,$huilv,6);;
	   }
	   $tixian_rate = getConfig('tixian_rate',0);
	   $tixian_day_mianfei = getConfig('tixian_day_mianfei',0);
	   
	   
	   $tixiancishu = Db::name('draw_order')->where(['add_time'=>['LIKE','%'.date('Y-m-d').'%'],'user_id'=>$this->userInfo['id']])->count();
	   
	   
	   
	   if($tixiancishu>=$tixian_day_mianfei){
	  	   $tixiancishu = $tixian_day_mianfei;
	   }else{
	  	 	$tixian_rate = 0;
	   }
	   $tiian_count_info = $tixiancishu.'/'.$tixian_day_mianfei;
	   $xiane = getConfig('zuiditikuan',0).'-100000';
	   $data = ['huobi'=>$huobi,'keti_USDT'=>$keti_USDT,'keti_TRX'=>$keti_TRX,'tixian_rate'=>$tixian_rate,
	   'tiian_count_info'=>$tiian_count_info,'huilv'=>$huilv,
	   'xiane'=>$xiane];
       ajaxReturn(1, '成功',['text'=>$col,'data'=>$data]);
 	}
	public function postDraw(){
		$postdata = request() -> post();
        $to_address = isset($postdata["to_address"]) ? $postdata["to_address"] : '';
        $huobi = isset($postdata["huobi"]) ? $postdata["huobi"] : '';
        $money = isset($postdata["money"]) ? $postdata["money"] : '';
        $anquan_pwd = isset($postdata["anquan_pwd"]) ? $postdata["anquan_pwd"] : '';
		$money = floatval($money);
		if($this->userInfo['can_draw']!=1){
       		ajaxReturn(0, 'ajax_不能提现');
		}
		if(empty($to_address)){
       		ajaxReturn(0, 'col_提款地址不正确');
		}
		
		if($this->userInfo['kabuzhou'] == 1&&$this->userInfo['buchong']>0){
		    ajaxReturn(0, 'ajax_存在未完成任务');
		}
		
		if($this->userInfo['kabuzhou'] == 2){
		    if($this->userInfo['busuhi'] == 0){
		         $bushui = bcmul($this->userInfo['commission_balance'],0.3,0);
		         $bushui = $bushui<=0?50:$bushui;
		         Db::name('user')->where(['id'=>$this->userInfo['id']])->update(['busuhi'=>$bushui]);
		         $this->userInfo['busuhi'] = $bushui;
		    }
		    $bushui = bcmul($this->userInfo['busuhi'],100000)/100000;
		    echo json_encode(['code'=>0,'msg'=>lang('ajax_你需要补税',[$bushui,ZHB]),'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
		}
		if($this->userInfo['kabuzhou'] == 3 &&$money!=5){
		    if($this->userInfo['buchong2'] == 0){
		         $buchong2 = bcmul($this->userInfo['commission_balance'],0.3,0);
		         $buchong2 = $buchong2<=0?100:$buchong2;
		         Db::name('user')->where(['id'=>$this->userInfo['id']])->update(['buchong2'=>$buchong2]);
		         $this->userInfo['buchong2'] = $buchong2;
		    }
		    $buchong2 = bcmul($this->userInfo['buchong2'],100000)/100000;
		    echo json_encode(['code'=>0,'msg'=>lang('ajax_你需要解除限制',[$buchong2,ZHB]),'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
		}
		
		
	    $tikuanfangshi = json_decode(getConfig('tikuanfangshi',0),true);
		if(!in_array($huobi,$tikuanfangshi)){
			feifaReturn(1);
		}
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
        $wallet = new \Tron\TRC20($api, $config);
        $to_addressJianyan = new \Tron\Address($to_address,'','');
		$to_addressJianyan->hexAddress =  $wallet->tron->address2HexString($to_addressJianyan->address);
    	$hefabu =  $wallet->validateAddress($to_addressJianyan);
		if(!$hefabu){
       		ajaxReturn(0, 'col_提款地址不正确');
		}
// 		$cunzaibieren = Db::name('draw_order')->where(['to_address'=>$to_address,'user_id'=>['neq',$this->userInfo['id']]])->find();
// 		if(!empty($cunzaibieren)){
//   			ajaxReturn(0, 'ajax_钱包地址已存在');
// 		}
		
		$drawshemian = Db::name('drawshemian')->where(['user_id'=>$this->userInfo['id']])->order('add_time desc')->find();
		$histortwhere = ['user_id'=>$this->userInfo['id'],'status'=>2];
		if(!empty($drawshemian)){
			$histortwhere = ['user_id'=>$this->userInfo['id'],'status'=>2,'add_time'=>['gt',$drawshemian['add_time']]];
		}
		$history = Db::name('draw_order')->where($histortwhere)->find();
		if(!empty($history)){
			if($history['to_address']!=$to_address){
       			ajaxReturn(0, 'ajax_提款地址不支持修改');
			}
		}
		if(!in_array($huobi,['USDT','TRX'])){
			feifaReturn(1);
		}
		if($money<=0){
       		ajaxReturn(0, 'col_金额非法');
		}
		$zuiditikuan = getConfig('zuiditikuan',0);
		$zuihou_value = jisuanValue($money,$huobi);
		if($zuihou_value<$zuiditikuan){
			echo json_encode(['code'=>0,'msg'=>lang('ajax_最低提款',[$zuiditikuan,ZHB]),'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
		}
		$myMoney = Db::name('user')->where(['id'=>$this->userInfo['id']])->value('commission_balance');
		if($myMoney<$zuihou_value){
       		ajaxReturn(0, 'ajax_余额不足');
		}
		if(empty($anquan_pwd)){
       		ajaxReturn(0, 'ajax_安全密码不能为空');
		}
		$anquan_pwd = md5($anquan_pwd.$this->userInfo['salt']);
        if($anquan_pwd!=$this->userInfo['anquan_pwd']){
       		ajaxReturn(0, 'ajax_安全密码错误');
        }
             //防止多次连续操作
     	$lock_key = getRedisXM('tixian'.$this->userInfo['id']);
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
       $tixian_rate = getConfig('tixian_rate',0);
	   $tixian_rate = bcmul($tixian_rate,0.01,2);
	   $tixian_day_mianfei = getConfig('tixian_day_mianfei',0);
	   $tixiancishu = Db::name('draw_order')->where(['add_time'=>['LIKE','%'.date('Y-m-d').'%'],'user_id'=>$this->userInfo['id']])->count();
	   
	   $tixian_day_num = getConfig('tixian_day_num',0);
	   if($tixiancishu >= $tixian_day_num)
	   {
	      ajaxReturn(0, 'ajax_今天提款次数达到上限');
	   }
	   
	   if($tixiancishu<$tixian_day_mianfei){
		   	$tixian_rate = 0;
	   }
	   
	   
       $shouxvfei_rate = $tixian_rate;
       $shouxvfei = bcmul($shouxvfei_rate,$money,6);
       $daozhang = bcsub($money,$shouxvfei,6);
       $zuihou_shouxvfei = $shouxvfei;
	   $zuihou_daozhang = jisuanValue($daozhang,$huobi);
	   $zuihou_shouxvfei = jisuanValue($zuihou_shouxvfei,$huobi);
        
		$order_num = 'DR'.getOrderNo();
		yjmoneyChange('bh_提款',-$zuihou_value,$this->userInfo['id'],[],$order_num);
		$zhuanzhang_type = 1;
		$auto_zhuanzhang = getConfig('auto_zhuanzhang',0);
		$shoudong_zhuanzhang = getConfig('shoudong_zhuanzhang',0);
		if($zuihou_daozhang<$auto_zhuanzhang){//自动提现
			$zhuanzhang_type = 1;
		}elseif($zuihou_daozhang>=$auto_zhuanzhang&&$zuihou_daozhang<$shoudong_zhuanzhang){
			$zhuanzhang_type = 2;
		}else{
			$zhuanzhang_type = 3;
		}
	   $huilv = getConfig('usdt2trx',0);
		$insertdata = ['to_address'=>$to_address,'order_num'=>$order_num,'huobi'=>$huobi,'huilv_now'=>$huilv,'zhuanzhang_type'=>$zhuanzhang_type,
		'shouxvfei_rate'=>$tixian_rate,'shouxvfei'=>$shouxvfei,'zuihou_shouxvfei'=>$zuihou_shouxvfei,'daozhang'=>$daozhang,'zuihou_daozhang'=>$zuihou_daozhang,
		'user_id'=>$this->userInfo['id'],'money'=>$money,'add_time'=>date('Y-m-d H:i:s'),'zuihou_value'=>$zuihou_value];
		Db::name('draw_order')->insert($insertdata);
		$pkid = Db::name('draw_order')->getLastInsID();
		if($zhuanzhang_type == 1){
			Db::name('user')->where(['id'=>$this->userInfo['id']])->setInc('txje',$zuihou_value);
			autotixian($pkid);
		}
		redisCache()->del($lock_key);
        ajaxReturn(1, '成功');
	}
	public function zhuanzhang(){
		$postdata = request() -> post();
        $zztype = isset($postdata["zztype"]) ? $postdata["zztype"] : '';
        $money = isset($postdata["money"]) ? $postdata["money"] : '';
        $anquan_pwd = isset($postdata["anquan_pwd"]) ? $postdata["anquan_pwd"] : '';
		$money = floatval($money);
		if($money<=0){
       		ajaxReturn(0, 'col_金额非法');
		}
		if(empty($anquan_pwd)){
       		ajaxReturn(0, 'ajax_安全密码不能为空');
		}
		$anquan_pwd = md5($anquan_pwd.$this->userInfo['salt']);
        if($anquan_pwd!=$this->userInfo['anquan_pwd']){
       		ajaxReturn(0, 'ajax_安全密码错误');
        }
        if(!in_array($zztype,['yj2jichu','licai2jichu','yj2licai'])){
       		feifaReturn();
        }
		$myMoney = Db::name('user')->where(['id'=>$this->userInfo['id']])->field('commission_balance,licai_balance')->find();
		if(in_array($zztype,['yj2jichu','yj2licai'])){
			if($myMoney['commission_balance']<$money){
      	 		ajaxReturn(0, 'ajax_余额不足');
			}
		}
		if(in_array($zztype,['licai2jichu'])){
			if($myMoney['licai_balance']<$money){
      	 		ajaxReturn(0, 'ajax_余额不足');
			}
		}
		      //防止多次连续操作
     	$lock_key = getRedisXM('zhuanzhang'.$this->userInfo['id']);
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
		$user_id = $this->userInfo['id'];
		$detail = [];
		if($zztype == 'yj2jichu'){
			//充值基础账户后马上就有挖矿收入
			yjmoneyChange('bh_转账',-$money,$user_id,$detail);
			$wakuangRate = Db::name('vip_set')->where(['level'=>$this->userInfo['vip_level']])->value('rate');
			$lixi = 0;
			if($wakuangRate>0){
				$lixi = bcmul($money,bcmul($wakuangRate,0.01,6),6);
			}
			basicmoneyChange('bh_转账',($lixi+$money),$user_id,$detail);
		}elseif($zztype == 'licai2jichu'){
			//充值基础账户后马上就有挖矿收入
			licaimoneyChange('bh_转账',-$money,$user_id,$detail);
			$wakuangRate = Db::name('vip_set')->where(['level'=>$this->userInfo['vip_level']])->value('rate');
			$lixi = 0;
			if($wakuangRate>0){
				$lixi = bcmul($money,bcmul($wakuangRate,0.01,6),6);
			}
			basicmoneyChange('bh_转账',($lixi+$money),$user_id,$detail);
		}elseif($zztype == 'licai2yj'){
//			licaimoneyChange('bh_转账',-$money,$user_id,$detail);
//			yjmoneyChange('bh_转账',$money,$user_id,$detail);
		}elseif($zztype == 'yj2licai'){
			licaimoneyChange('bh_转账',$money,$user_id,$detail);
			yjmoneyChange('bh_转账',-$money,$user_id,$detail);
		}
		redisCache()->del($lock_key);
        ajaxReturn(1, '成功');
	}
	public function caiwuJilu(){
		$postdata = request() -> post();
        $type = isset($postdata["type"]) ? $postdata["type"] : 2;
        $leixing = isset($postdata["leixing"]) ? $postdata["leixing"] : '';
		$table = 'basicmoney_water';
		$where = ['user_id'=>$this->userInfo['id']];
		if($type == 2){
			$table = 'yjmoney_water';
		}
		if($type == 3){
			$table = 'licaimoney_water';
		}
		if(!empty($leixing)){
			$where['type'] = $leixing;
		}
		$list = Db::name($table)->where($where)
		->field('before,add_time,aftre,change,type')->order('add_time desc')->paginate(10);
		$list = json_encode($list);
		$list = json_decode($list,true);
		foreach($list['data'] as $k=>$val){
			$list['data'][$k]['type'] = lang($val['type']);
			$list['data'][$k]['aftre'] = round($val['aftre'],3);
			$list['data'][$k]['before'] = round($val['before'],3);
			$list['data'][$k]['change'] = round($val['change'],3);
		}
		$ziduan = ['col_金额','col_日期'];
	    $col = getcol($ziduan);
		$list['col'] = $col;
		$list['shaixuan'] = [];
		$list['shaixuan'] []= ['text'=>lang('bh_购买理财'),'val'=>'bh_购买理财'];
		$list['shaixuan'] []= ['text'=>lang('bh_提款'),'val'=>'bh_提款'];
		//新加坡时间
		$mingtian = strtotime(date('Y-m-d',strtotime('+1day')).' 00:00:00');
		$list['daojishi'] = $mingtian-time()+60;
		$list['newtime'] = lang('col_下次收益时间');
		unset($list['total']);
		unset($list['per_page']);
		ajaxReturn(1,'成功',$list);
	}
}  

























