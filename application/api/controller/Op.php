<?php
namespace app\api\controller;
use think\ Db;
use think\Request;
use think\Controller;
use think\Lang;
class Op extends Base {
    public function getMyinfo(){
	   	$huilv = getConfig('usdt2trx',0);
	   	$xjtiyanjin = getConfig('xjtiyanjin',0);
    	$email = $this->userInfo['email'];
    	$tel = $this->userInfo['tel'];
		$basic_balance = $this->userInfo['basic_balance']+$xjtiyanjin;
		$commission_balance = $this->userInfo['commission_balance'];
		$licai_balance = $this->userInfo['licai_balance'];
		$zongyue = ($basic_balance+$commission_balance+$licai_balance);
    	if(ZHB == 'USDT'){
    		$basic_balance_LING = ($this->userInfo['basic_balance'])*$huilv;
    		$commission_balance_LING = $this->userInfo['commission_balance']*$huilv;
    		$licai_balance_LING = $this->userInfo['licai_balance']*$huilv;
    	}else{
    		$basic_balance_LING = bcdiv($this->userInfo['basic_balance'],$huilv,6);
    		$commission_balance_LING = bcdiv($this->userInfo['commission_balance'],$huilv,6);
    		$licai_balance_LING = bcdiv($this->userInfo['licai_balance'],$huilv,6);
    	}
    	$zong_LING = round(($basic_balance_LING+$commission_balance_LING+$licai_balance_LING),3);
    	$invite_code = $this->userInfo['invite_code'];
    	$vip_level = 'VIP'.$this->userInfo['vip_level'];
    	$haveunread = 0;
    	$existMsg = Db::name('msg')->where(['user_id'=>$this->userInfo['id'],'read'=>0])->count();
    	if($existMsg>0){
    		$haveunread = 1;
    	}
    	$username = !empty($email)?$email:$tel;
    	$chongzhi_count = round($this->userInfo['xfje'],3);
    	$vipRate = Db::name('vip_set')->where(['level'=>$this->userInfo['vip_level']])->value('rate');
    	if(empty($vipRate)){
    		$vipRate = '1';
    	}
    	$lingwaiHuobi = 'TRX';
    	if(ZHB=='TRX'){
    		$lingwaiHuobi = 'USDT';
    	}
    	$vipRate = $vipRate.'%';
    	$mylevelkuangshi =  Db::name('vip_set')->where(['level'=>$this->userInfo['vip_level']])->value('vip_name');
    	
    	$pageURL = 'https://'.$_SERVER["SERVER_NAME"] . getConfig('logourl',0);
    	
    	$data = ['logo'=>$pageURL,'email'=>$email,'tel'=>$tel,'basic_balance'=>round($basic_balance,3),'commission_balance'=>round($commission_balance,3),
    	'licai_balance'=>round($licai_balance,3),
    	'basic_balance_LING'=>$basic_balance_LING,'commission_balance_LING'=>$commission_balance_LING,'licai_balance_LING'=>$licai_balance_LING,'zong_LING'=>$zong_LING,
    	'invite_code'=>$invite_code,'zongyue'=>round($zongyue,3),'haveunread'=>$haveunread,'username'=>$username,'vipRate'=>$vipRate,
    	'ZHB'=>ZHB,'lingwaiHuobi'=>$lingwaiHuobi,'mylevelkuangshi'=>$mylevelkuangshi
    	,'vip_level'=>$vip_level,'chongzhi_count'=>$chongzhi_count];
		ajaxReturn(1,'成功',$data);
    }
    public function changeLoginpwd(){
       $postdata = request() -> post();
       $oldpwd = isset($postdata["oldpwd"]) ? $postdata["oldpwd"] : '';
       $newpwd1 = isset($postdata["newpwd1"]) ? $postdata["newpwd1"] : '';
       $newpwd2 = isset($postdata["newpwd2"]) ? $postdata["newpwd2"] : '';
       if(empty($oldpwd)){
     	   ajaxReturn(0, 'ajax_旧登录密码不能为空');
       }
       if(strlen($newpwd1)>30||strlen($newpwd1)<6){
     	   ajaxReturn(0, 'ajax_新登录密码长度');
       }
       $reg = '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/';
       if(!preg_match($reg,$newpwd1)){
     	   ajaxReturn(0, 'ajax_新登录密码格式');
       }
       if($newpwd1!=$newpwd2){
     	   ajaxReturn(0, 'ajax_两次密码不一样');
       }
       if($this->userInfo['login_pwd']!=md5($oldpwd.$this->userInfo['salt'])){
     	   ajaxReturn(0, 'ajax_登录密码不正确');
       }
	   $login_pwd = md5($newpwd1.$this->userInfo['salt']);
       Db::name('user')->where(['id'=>$this->userInfo['id']])->update(['login_pwd'=>$login_pwd]);
       ajaxReturn(1, '成功');
    }
     public function changeAnquanpwd(){
       $postdata = request() -> post();
       $oldpwd = isset($postdata["oldpwd"]) ? $postdata["oldpwd"] : '';
       $newpwd1 = isset($postdata["newpwd1"]) ? $postdata["newpwd1"] : '';
       $newpwd2 = isset($postdata["newpwd2"]) ? $postdata["newpwd2"] : '';
       if(empty($oldpwd)){
     	   ajaxReturn(0, 'ajax_旧安全密码不能为空');
       }
       if(strlen($newpwd1)>30||strlen($newpwd1)<6){
     	   ajaxReturn(0, 'ajax_新安全密码长度');
       }
       $reg = '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/';
       if(!preg_match($reg,$newpwd1)){
     	   ajaxReturn(0, 'ajax_新安全密码格式');
       }
       if($newpwd1!=$newpwd2){
     	   ajaxReturn(0, 'ajax_两次密码不一样');
       }
       if($this->userInfo['anquan_pwd']!=md5($oldpwd.$this->userInfo['salt'])){
     	   ajaxReturn(0, 'ajax_安全密码不正确');
       }
	   $anquan_pwd = md5($newpwd1.$this->userInfo['salt']);
       Db::name('user')->where(['id'=>$this->userInfo['id']])->update(['anquan_pwd'=>$anquan_pwd]);
       ajaxReturn(1, '成功');
    }
    public function msglist(){
    	$list = Db::name('msg')->where(['user_id'=>$this->userInfo['id']])->field('id,title,add_time,read')->order('read asc,add_time desc')->paginate(10);
    	$list = json_encode($list);
		$list = json_decode($list,true);
		unset($list['total']);
		unset($list['per_page']);
       ajaxReturn(1, '成功',$list);
    }
     public function msgdetail(){
     	$postdata = request() -> post();
        $msg_id = isset($postdata["msg_id"]) ? $postdata["msg_id"] : '';
    	$msg = Db::name('msg')->where(['user_id'=>$this->userInfo['id'],'id'=>$msg_id])->field('id,content,add_time')->find();
    	if(empty($msg)){
       		feifaReturn(1);
    	}
    	$msg['msgtype'] = 'System notification';
    	Db::name('msg')->where(['id'=>$msg_id])->update(['read'=>1]);
        ajaxReturn(1, '成功',$msg);
    }
    public function readAll(){
    	Db::name('msg')->where(['user_id'=>$this->userInfo['id'],'read'=>0])->update(['read'=>1]);
        ajaxReturn(1, '成功');
    }
    public function yaoqingdata(){
    	$postdata = request() -> post();
      	$date = isset($postdata["date"]) ? $postdata["date"] : date('Y-m-d');
    	$today = $date;
    	if(empty($date)){
    		$date =  date('Y-m-d');
    	}
    	$lastday = date('Y-m-d',strtotime($date.'-1 day'));
    	$today_starttime = $today.' 00:00:00';
    	$today_endtime = $today.' 23:59:59';
    	
    	$zong_wakuang = round(Db::name('wakuang')->where(['user_id'=>$this->userInfo['id'],'status'=>2])->sum('lixi'),2);
    	$zong_chongzhi = round(Db::name('chognzhifanli')->where(['user_id'=>$this->userInfo['id']])->sum('money'),2);
    	$zongfanyong = round(($zong_wakuang+$zong_chongzhi),2);;
    	//今日挖矿返佣
    	$jinri_wakuang = round(Db::name('wakuang')->where(['user_id'=>$this->userInfo['id'],'status'=>2,'daoqi_day'=>$today])->sum('lixi'),2);
    	//今日充值返佣
    	$jinri_chongzhi = round(Db::name('chognzhifanli')->where(['user_id'=>$this->userInfo['id'],'add_time'=>['LIKE',"%$today%"]])->sum('money'),2);
    	$jinri_fanyong = round(($jinri_chongzhi+$jinri_wakuang),2);
    	//昨日挖矿返佣
    	$zuori_wakuang = round(Db::name('wakuang')->where(['user_id'=>$this->userInfo['id'],'status'=>2,'daoqi_day'=>$lastday])->sum('lixi'),2);
    	//昨日充值返佣
    	$zuori_chongzhi = round(Db::name('chognzhifanli')->where(['user_id'=>$this->userInfo['id'],'add_time'=>['LIKE',"%$lastday%"]])->sum('money'),2);
    	$zuori_fanyong = round(($zuori_wakuang+$zuori_chongzhi),2);
    	$myuid = $this->userInfo['id'];
    	$jinri_yq = Db::name('user')->where(['add_time'=>['LIKE',"%$today%"]])->where("from_who = $myuid OR erji_from = $myuid OR sanji_from=$myuid")->count();
    	$zuori_yq = Db::name('user')->where(['add_time'=>['LIKE',"%$lastday%"]])->where("from_who = $myuid OR erji_from = $myuid OR sanji_from=$myuid")->count();
    	$invite_code = $this->userInfo['invite_code'];
    	$tuite_url = 'https://twitter.com/intent/tweet?text=%20https%3A%2F%2Fh2.bzz.vip%2F%23%2Flogin%2Fregister%3Fcode%3D285700';
    	$facebook_rrl = 'https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fh2.bzz.vip%2F%23%2Flogin%2Fregister%3Fcode%3D285700';
    	
		
		//邀请链接
		$laiyuan = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'zheshibucunzai';
		if(!isset($_SERVER['HTTP_REFERER'])&&!empty($_SERVER['HTTP_REFERER'])){
    		$hostName = str_replace("ht.","",$_SERVER['HTTP_HOST']);
            $hostName = $_SERVER['REQUEST_SCHEME'].'://s.'.$hostName.'/';
    		$yq_url = $hostName.'s/'.$this->userInfo['invite_code'];
    	}else{
    		$hostName = str_replace("ht.","",$_SERVER['HTTP_HOST']);
            $hostName = $_SERVER['REQUEST_SCHEME'].'://s.'.$hostName.'/';
            $yq_url = $hostName.'s/'.$this->userInfo['invite_code'];
    	}
    	
    	
    	$tuandui_level1 = $this->userInfo['yiji_count'];
    	$tuandui_level2 = $this->userInfo['erji_count'];
    	$tuandui_level3 = $this->userInfo['sanji_count'];
    	//团队人数
    	$tuanduirenshu = $this->userInfo['yiji_count']+$this->userInfo['erji_count']+$this->userInfo['sanji_count'];
    	//团队业绩
    	$myid = $this->userInfo['id'];
    	$tuanduiyeji = Db::name('user')->where("from_who = $myid OR erji_from=$myid OR sanji_from =$myid OR id=$myid ")->sum('xfje');
    	
    	
    		$cz1 = 0;
		$cz2 = 0;
		$cz3 = 0;
    	$tx1 = 0;
		$tx2 = 0;
		$tx3 = 0;		
    	if( $this->userInfo['daili'] == 1){
    	    //一级
    	    $yijiInfo = Db::name('user')->where(['from_who'=>$this->userInfo['id']])
    	    ->field('SUM(xfje) AS xfje,SUM(txje) AS txje')->find();
    	    $cz1 = $yijiInfo['xfje']??0;
    	    $tx1 = $yijiInfo['txje']??0;
    	    $cz1 = bcmul($cz1,1,1);
    	    $tx1 = bcmul($tx1,1,1);
    	     //2级
    	    $yijiInfo = Db::name('user')->where(['erji_from'=>$this->userInfo['id']])
    	    ->field('SUM(xfje) AS xfje,SUM(txje) AS txje')->find();
    	    $cz2 = $yijiInfo['xfje']??0;
    	    $tx2 = $yijiInfo['txje']??0;
    	    $cz2 = bcmul($cz2,1,1);
    	    $tx2 = bcmul($tx2,1,1);
    	     //3级
    	    $yijiInfo = Db::name('user')->where(['sanji_from'=>$this->userInfo['id']])
    	    ->field('SUM(xfje) AS xfje,SUM(txje) AS txje')->find();
    	    $cz3= $yijiInfo['xfje']??0;
    	    $tx3 = $yijiInfo['txje']??0;
    	    $cz3 = bcmul($cz3,1,1);
    	    $tx3 = bcmul($tx3,1,1);
    	    
    	}
    	
    	
    	
    	$data = ['zongfanyong'=>$zongfanyong,'zong_wakuang'=>$zong_wakuang,'zong_chongzhi'=>$zong_chongzhi,'jinri_fanyong'=>$jinri_fanyong,
    	'jinri_wakuang'=>$jinri_wakuang,'jinri_chongzhi'=>$jinri_chongzhi,'zuori_wakuang'=>$zuori_wakuang,'zuori_chongzhi'=>$zuori_chongzhi,
    	'zuori_fanyong'=>$zuori_fanyong,'jinri_yq'=>$jinri_yq,'zuori_yq'=>$zuori_yq,'moneydan'=>ZHB,'people'=>lang('danwei_人'),'tuanduirenshu'=>lang('col_团队人数'),
    	'xiangqing'=>lang('详情'),'tuanduiyeji'=>$tuanduiyeji,'tuanduirenshudata'=>$tuanduirenshu,'date'=>$date,
    	'chaxun'=>lang('col_查询'),'col_日期'=>lang('col_日期'),
    	'tuandui_level1'=>$tuandui_level1,'tuandui_level2'=>$tuandui_level2,'tuandui_level3'=>$tuandui_level3,
    	'tx1'=>$tx1,'tx2'=>$tx2,'tx3'=>$tx3,'cz1'=>$cz1,'cz2'=>$cz2,'cz3'=>$cz3,
    	'daili'=>$this->userInfo['daili'],
    	'tuite_url'=>$tuite_url,'facebook_rrl'=>$facebook_rrl,'yq_url'=>$yq_url,'invite_code'=>$invite_code,'yq_复制链接'=>lang('yq_复制链接')];
    	
    	
    	
        ajaxReturn(1, '成功',$data);
    }
    public function vipdata(){
    	$vipdata = Db::name('vip_set')->where([])->
    	field('level,up_money,task_money,rate,rate1_chongzhi,rate2_chongzhi,rate3_chongzhi,pic,vip_name')
    	->order('level asc')->select();
    	$showwakuang = 0;
    	 
    	$isShowVip0 = getConfig('isShowvip0',0);   //是否显示vip0
    	$index = -1;
    	foreach($vipdata as $k=>$val){
    	    
    	    if($vipdata[$k]['level'] == 0 && !$isShowVip0)
    	    {
    	        $index = $k;
    	        //vip0 并且设置不显示
    	       // continue;
    	    }
    	    
    		$vipdata[$k]['up_money'] = round($val['up_money'],0);
    		$vipdata[$k]['task_money'] = round($val['task_money'],2);
    		$vipdata[$k]['level'] = 'VIP'.$vipdata[$k]['level'];
    		$vipdata[$k]['rate'] = $val['rate'].'%';
    		$vipdata[$k]['rate1_chongzhi'] = $val['rate1_chongzhi'].'%';
    		$vipdata[$k]['rate2_chongzhi'] = $val['rate2_chongzhi'].'%';
    		$vipdata[$k]['shengjijine'] = lang('vip_升级金额');
    		
    		$money = $val['up_money'] - $this->userInfo['basic_balance'];
    		
    		 $money = substr(sprintf("%.5f", $money),0,-1);
    		 $haixvyaoMoney = $money;
    // 		$haixvyaoMoney = round(bcsub($val['up_money'],$this->userInfo['basic_balance'],6),0);
    		$vipdata[$k]['goumai'] = lang('vip_购买',[$haixvyaoMoney.ZHB]);
    		$vipdata[$k]['goumai2'] = lang('col_还需要才购买',[$haixvyaoMoney.ZHB]);
			$vipdata[$k]['showupdate'] = 0;
    		if($this->userInfo['vip_level']<$val['level']){
    			$vipdata[$k]['showupdate'] = 1;
    			$vipdata[$k]['up_money'] = round($val['up_money'],0);
    		}
    		$vipdata[$k]['rate3_chongzhi'] = $val['rate3_chongzhi'].'%';
    		$vipdata[$k]['pic'] = getGoopic($val['pic']);
    		if($vipdata[$k]['rate'] <= 0){
    // 			unset($vipdata[$k]);
    		}
    	}
    	
    	if($index != -1)
    	{
    	    unset($vipdata[$index]);
    	    $vipdata = array_values($vipdata);
    	}
    	
    	$mylevelkuangshi =  Db::name('vip_set')->where(['level'=>$this->userInfo['vip_level']])->value('vip_name');
        ajaxReturn(1, '成功',['vipdata'=>$vipdata,'mylevel'=>'VIP'.$this->userInfo['vip_level'],
        'mysum'=>round($this->userInfo['basic_balance'],2),'kuangshi_name'=>$mylevelkuangshi]);
    }
    public function zhuanzhangdata(){
        ajaxReturn(1, '成功',['commission_balance'=>($this->userInfo['commission_balance']),'col_转账金额'=>lang('col_转账金额'),
        'licai_balance'=>($this->userInfo['licai_balance']),'col_账户可用余额'=>lang('col_账户可用余额')]

        );
    }
    public function loginout(){
    	Db::name('user')->where(['id'=>$this->userInfo['id']])->update(['token'=>'']);
        ajaxReturn(1, '成功');
    }
    public function vipnewdata(){
    	$vipdata = Db::name('vip_set')->where([])->
    	field('level,min,pic,task_money,up_money,task_count,vip_name')
    	->order('level asc')->select();
    	$showwakuang = 0;
    	$isShowVip0 = getConfig('isShowvip0',0);   //是否显示vip0
    	$index = -1;
    	foreach($vipdata as $k=>$val){
    	    
    	    if($val['level'] == 0 && !$isShowVip0)
    	    {
    	        $index = $k;
    	        //vip0 并且设置不显示
    	      
    	    }
    		$vipdata[$k]['level'] = 'VIP'.$vipdata[$k]['level'];
    		$vipdata[$k]['showjiesuo'] = 0;
    		$vipdata[$k]['lijijiesuo'] = 0;
    		if($this->userInfo['vip_level'] !=$val['level']){
    			$vipdata[$k]['showjiesuo'] = 1;
    		}
    		if($this->userInfo['vip_level'] <$val['level']){
    			$vipdata[$k]['lijijiesuo'] = 1;
    		}
    		
    		$money = $val['up_money'] - $this->userInfo['basic_balance'];
    		
    		 $money = substr(sprintf("%.5f", $money),0,-1);
    // 		 $haixvyaoMoney = $money;
    		 $haixvyaoMoney = round($money,2);
    // 		$haixvyaoMoney = round(bcsub($val['up_money'],$this->userInfo['basic_balance'],6),0);
    		if($haixvyaoMoney<0){
    			$haixvyaoMoney = 0;
    		}
    		$vipdata[$k]['haixvyao'] = $haixvyaoMoney.' '.ZHB;
    		$vipdata[$k]['jiesuo'] = round($val['up_money'],2).' '.ZHB;
    		$vipdata[$k]['pic'] = getGoopic($val['pic']);
    		$vipdata[$k]['meiri_renwu'] =$val['task_count'];
    		$vipdata[$k]['meiri_shouyi'] = round($val['task_money'],2).' '.ZHB;
    		$vipdata[$k]['meiyue_shouyi'] = round(($val['task_money']*30),2).' '.ZHB;;
    		$vipdata[$k]['unlock_time'] = '00:00~23:59';
    	}
    	if($index != -1)
    	{
    	    unset($vipdata[$index]);
    	    $vipdata = array_values($vipdata);
    	}
    	
    	$ziduan = ['col_生效时间','col_每日任务','col_每日收益','col_每月收益',
    	'col_立即解锁','col_已经解锁有效','col_每天矿石'];
	    $col = getcol($ziduan);
        ajaxReturn(1, '成功',['vipdata'=>$vipdata,'mylevel'=>'VIP'.$this->userInfo['vip_level'],'col'=>$col]);
    }
    public function updateVip(){
    	$postdata = request() -> post();
        $level = isset($postdata["level"]) ? $postdata["level"] : '';
		$level = str_replace("VIP","",$level);
		if(empty($level)){
			feifaReturn(1);
			
		}
		if($level<=$this->userInfo['vip_level']){
			feifaReturn(2);
		}
		$oldVip = $this->userInfo['vip_level'];
		$newVip = $level;
		$costLevel = Db::name('vip_set')->where(['level'=>$this->userInfo['vip_level']])->value('up_money');
    	if(empty($costLevel)){
    		$costLevel = 0;
    	}
    	$lock_key = getRedisXM('shengjivip'.$this->userInfo['id']);
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
		$vipInfo = Db::name('vip_set')->where(['level'=>$level])->find();		
		$needMoney = bcsub($vipInfo['up_money'],$costLevel,6);
		$myMoney = Db::name('user')->where(['id'=>$this->userInfo['id']])->value('basic_balance');
		if($myMoney<$needMoney){
       		ajaxReturn(0, 'ajax_余额不足');
		}
		basicmoneyChange('bh_升级VIP',-$needMoney,$this->userInfo['id'],[]);
		Db::name('user')->where(['id'=>$this->userInfo['id']])->update(['vip_level'=>$level]);
		shengjiVip($oldVip,$newVip,$this->userInfo['id']);
        ajaxReturn(1, '成功');
    }
}