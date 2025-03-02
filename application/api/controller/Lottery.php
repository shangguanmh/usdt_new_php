<?php
namespace app\api\controller;
use think\ Db;
use think\Request;
use think\Controller;
use think\Lang;
class Lottery extends Base {
	
 	public function roomList(){
 		$list = Db::name('lottery_room')->where([])->field('id as room_id,name,cost')->select();
		foreach($list as $k=>$val){
			$list[$k]['name']  = lang('col_'.$val['name']);
			$list[$k]['cost']  = round($val['cost'],3);
 		}
   	    ajaxReturn(1, '成功',['list'=>$list,'tip'=>lang('col_每日赠送免费次数')]);
 	}
 	
 	public function lotterydetail(){
 		$postdata = request() -> post();
        $room_id = isset($postdata["room_id"]) ? $postdata["room_id"] : 0;
 		$roomInfo = Db::name('lottery_room')->where(['id'=>$room_id])->find();
 		if(empty($roomInfo)){
     	   feifaReturn(1);
 		}
		$roomInfo['name']  = lang($roomInfo['name']);
		$roomInfo['cost']  = round($roomInfo['cost'],3);
 		$roomInfo['shangpin'] = json_decode($roomInfo['shangpin'],true);
 		foreach($roomInfo['shangpin'] as $k=>$v){
 			unset($roomInfo['shangpin'][$k]['xyz']);
 		}
 		$today = date('Y-m-d');
 		$freeTime = 0;
 		if($room_id == 1){
 			//查看表里有没有数据
 			$choujiangfree = Db::name('choujiang_free')->where(['day'=>$today,'user_id'=>$this->userInfo['id'],'room_id'=>$room_id])->find();
 			if(!empty($choujiangfree)){
 				$freeTime = $choujiangfree['cishu'];
 			}else{
 				$zongsongTime = 2;
 				//查看今天有没有充值
 				$zuidiTrx = 98;//最低充值10，防止计算有误差
 				$huilv = getConfig('usdt2trx',0);
				$zuidiTrx = bcdiv($zuidiTrx,$huilv,6);
 				$havetoday = Db::name('invest_order')->where(['add_time'=>['LIKE',"%$today%"],'status'=>2,
 				'user_id'=>$this->userInfo['id'],'zuihou_value'=>['egt',$zuidiTrx]])->count();
 				if($havetoday>0){
 					Db::name('choujiang_free')->insert(['day'=>$today,'user_id'=>$this->userInfo['id'],'room_id'=>1,'cishu'=>$zongsongTime]);
 					$freeTime = $zongsongTime;
 				}
 			}
 		}
 		$guize =  Db::name('game_rule_lang')->where(['lang'=>$this->currentLang])->value('content');
	 	$roomInfo['guize'] = $guize;
	 	$yue =  $this->userInfo['licai_balance'];
	 	$roomInfo['yue'] = round($yue,3);
	 	$roomInfo['col_理财账户'] = lang('col_理财账户');
	 	$roomInfo['col_每次抽奖消耗'] = lang('col_每次抽奖消耗').'：  '.$roomInfo['cost'].'    USDT';
	 	$roomInfo['col_奖品就绪'] = lang('col_奖品就绪');
	 	$roomInfo['col_活动规则'] = lang('col_活动规则');
	 	$roomInfo['col_确定'] = lang('uni.showModal.confirm');
	 	$roomInfo['col_取消'] = lang('uni.showModal.cancel');
	 	$roomInfo['col_前往充值'] = lang('col_前往充值');
	 	$roomInfo['ajax_余额不足'] = lang('ajax_余额不足');
	 	$roomInfo['col_抽奖记录'] = lang('col_抽奖记录');
	 	$showFreeText = true;
	 	if($room_id == 1){
	 		$roomInfo['col_剩余免费抽奖次数'] = lang('col_剩余免费抽奖次数').'：';
	 	}else{
	 		$showFreeText = false;
	 		$roomInfo['col_剩余免费抽奖次数'] = '';
	 	}
	 	$roomInfo['freeTime'] = $freeTime;
	 	$roomInfo['showFreeText'] = $showFreeText;
   	    ajaxReturn(1, '成功',$roomInfo);
 	}
 	function choujiang(){
 		$today = date('Y-m-d');
 		$postdata = request() -> post();
        $room_id = isset($postdata["room_id"]) ? $postdata["room_id"] : 0;
        $roomInfo = Db::name('lottery_room')->where(['id'=>$room_id])->find();
 		if(empty($roomInfo)){
     	   feifaReturn(1);
 		}
 		$shifoujainceYue = true;
		$choujiangfree = Db::name('choujiang_free')->where(['day'=>$today,'user_id'=>$this->userInfo['id'],'room_id'=>$room_id])->find();
		if(!empty($choujiangfree)&&$choujiangfree['cishu']>0){
			$shifoujainceYue = false;
		}
 		if($shifoujainceYue){
	 		$myMoney = Db::name('user')->where(['id'=>$this->userInfo['id']])->value('licai_balance');
			if($myMoney<$roomInfo['cost']){
	       		ajaxReturn(0, 'ajax_余额不足');
			}
 		}
 		
		$data = $this->choujiangDeal($roomInfo,$this->userInfo['id']);
   	    ajaxReturn(1, '成功',['index'=>$data['zhongjiang'],'msg'=>$data['msg']]);
 	}
 	function choujiangDeal($roomInfo,$user_id){
 		$today = date('Y-m-d');
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
   		$zhongjiang = $this->get_rand($zhongjianggailv);
   		if(!isset($shangpinKeyVal[$zhongjiang])){
     	   feifaReturn(2);
   		}
   		$choujiangfree = Db::name('choujiang_free')->where(['day'=>$today,'user_id'=>$this->userInfo['id'],'room_id'=>$roomInfo['id']])->find();
   		if(!empty($choujiangfree)&&$choujiangfree['cishu']>0){//扣免费次数
   			Db::name('choujiang_free')->where(['id'=>$choujiangfree['id']])->setDec('cishu',1);
   			$roomInfo['cost'] = 0;
   			$zuidi2ge = [];
   			$zuidi2ge[] = $roomInfo['shangpin'][count($roomInfo['shangpin'])-1];
   			$zuidi2ge[] = $roomInfo['shangpin'][count($roomInfo['shangpin'])-2];
   			$suijiIndex = mt_rand(0,1);
   			$zhongjiang = $zuidi2ge[$suijiIndex]['index'];
   			//免费抽奖只给最低那2个
   		}else{
   			licaimoneyChange('bh_抽奖',-$roomInfo['cost'],$user_id,[]);
   		}
	    $huilv = getConfig('usdt2trx',0);
	    $money = $shangpinKeyVal[$zhongjiang]['count'];
	    if($shangpinKeyVal[$zhongjiang]['jiang_type'] == 'T'){
			$money = bcdiv($money,$huilv,6);
	    }
	    $yingli = bcsub($money,$roomInfo['cost'],6);
	    Db::name('choujiang')->insert(['user_id'=>$user_id,'cost'=>$roomInfo['cost']
	    ,'jiangpin_name'=>$shangpinKeyVal[$zhongjiang]['name']
	    ,'jiangpin_type'=>$shangpinKeyVal[$zhongjiang]['jiang_type']
	    ,'jiangpin_count'=>$shangpinKeyVal[$zhongjiang]['count']
	    ,'zhongjiang_index'=>$zhongjiang
	    ,'yingli'=>$yingli
	    ,'dengzhi_money'=>$money
	    ,'add_time'=>date('Y-m-d H:i:s'),'room_id'=>$roomInfo['id']]);
        yjmoneyChange('bh_抽奖',$money,$this->userInfo['id'],[]);
		$msg = lang("col_恭喜你抽中").'  '.$shangpinKeyVal[$zhongjiang]['name'];
		redisCache()->del($lock_key);
		return ['msg'=>$msg,'zhongjiang'=>$zhongjiang];
 	}
 	function choujiangRecord(){
 		$roomList = Db::name('lottery_room')->where([])->select();
 		$roomKey = [];
 		foreach($roomList as $val){
 			$roomKey[$val['id']] = $val['name'];
 		}
	    $list = Db::name('choujiang')->where(['user_id'=>$this->userInfo['id']])->field('cost,jiangpin_name,yingli,room_id')->order('add_time desc')->limit(50)->select();
	    foreach($list as $k=>$val){
//	    	$list[$k]['room_name'] = isset($roomKey[$val['room_id']])?$roomKey[$val['room_id']]:'';
//	    	if(!empty($list[$k]['room_name'])){
//	    		$list[$k]['room_name'] = lang($list[$k]['room_name']);
//	    	}
	    	$list[$k]['yingli'] = round($list[$k]['yingli'],3);
	    	$list[$k]['cost'] = round($list[$k]['cost'],3);
	    }
   	    ajaxReturn(1, '成功',['list'=>$list,'col_费用'=>lang('col_费用'),'col_奖品'=>lang('col_奖品'),'col_盈利'=>lang('col_盈利')]);
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
	
}



























