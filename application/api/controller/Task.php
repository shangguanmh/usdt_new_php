<?php
namespace app\api\controller;
use think\ Db;
use think\Request;
use think\Controller;
use think\Lang;
class Task extends Base {
	function getMylist(){
		//新加坡时间
		$biaoji = [];
		$mingtian = strtotime(date('Y-m-d',strtotime('+1day')).' 00:00:00');
		$daojishi = $mingtian-time()+60;
	 	$postdata = request() -> post();
        $status = isset($postdata["status"]) ? $postdata["status"] : 1;
		$today = date('Y-m-d');
		if(!in_array($status,[1,2])){
     	   feifaReturn(1);
		}
		$jinrisuoyourenwu = 0;
		$jinrishengyu = 0;
		//看看有没有冻结金额，有就给加，没有就不给
		$cunzai =Db::name('user_task')->where(['user_id'=>$this->userInfo['id'],'day'=>$today,'vip'=>$this->userInfo['vip_level']])->count();
		
		if($this->userInfo['tmp_task'] != -1|| !$this->userInfo['can_wakuang']){  //$this->userInfo['tmp_tank'] == -1 ||
//		if($this->userInfo['tmp_task'] != -1|| !$this->userInfo['can_wakuang'] ||(empty($wakuang)&&$cunzai<=0)){  //$this->userInfo['tmp_tank'] == -1 ||
   			 ajaxReturn(2, '成功',['jinrisuoyourenwu'=>$jinrisuoyourenwu,'jinrishengyu'=>$jinrishengyu,
   			 'daojishi'=>$daojishi
   			 ]);
		}
		if($cunzai<=0){//需要新增今天的计划任务
			$lock_key = getRedisXM('shengcehngtask'.$this->userInfo['id']);
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
			$vipInfo = Db::name('vip_set')->where(['level'=>$this->userInfo['vip_level']])->find();
			$viptaskCount =$vipInfo['task_count'];
			if($viptaskCount<=0){
				$viptaskCount = 1;
			}
			$hongbaofenpei = $this->hongbaosuanfa($vipInfo['task_money'],$viptaskCount);
			//先找出专属VIP
			$randList = Db::name('task')->where(['vip_level'=>$vipInfo['level']])->orderRaw('rand()')->limit($viptaskCount)->select();
			$biaoji[] = '找专属VIP';
			if(count($randList)<$viptaskCount){
				$haicha = $viptaskCount-count($randList);
				$biaoji[] = '专属VIP任务数量不够，再找'.$haicha;
				$randList_haicha = Db::name('task')->where([])->orderRaw('rand()')->limit($haicha)->select();
				$randList = array_merge($randList,$randList_haicha);
			}
			//升级是否重置任务
			$serawhere = ['type'=>1,'user_id'=>$this->userInfo['id'],'day'=>$today];
			if($this->userInfo['vip_level']>0){//如果是大于零的VIP等级，那么久排除掉今日这个VIP0的任务，因为不管能否连续，VIP0升级后都是可以连续领取的
			    $serawhere['vip'] = ['neq',0];
			}
			$uplevel_cztx = getConfig('uplevel_cztx',0);
			if($uplevel_cztx == 1){
			    $serawhere['vip'] = $this->userInfo['vip_level'];
			}
			$cunzaijintinatask = Db::name('user_task')->where($serawhere)->count();
			if($cunzaijintinatask<=0){//不存在今天的计划任务，才去新赠计划任务啊
				$usertask = [];
				foreach($randList as $k=> $val){
					$money = isset($hongbaofenpei[$k])?$hongbaofenpei[$k]:0.000001;
					if($money>0){
						$usertask[] = ['user_id'=>$this->userInfo['id'],'task_id'=>$val['id'],'taskname'=>$val['taskname'],
						'day'=>$today,'status'=>1,'money'=>$money,'add_time'=>date('Y-m-d H:i:s'),'type'=>1,'vip'=>$this->userInfo['vip_level']];
					}
				}
				Db::name('user_task')->insertAll($usertask);
			}
			Db::name('wakuang')->where(['user_id'=>$this->userInfo['id'],'daoqi_day'=>$today])->update(['status'=>2]);
			redisCache()->del($lock_key);
		}
		$tasklist =Db::name('user_task')->where(['user_id'=>$this->userInfo['id'],'day'=>$today,'status'=>$status])
		->alias('user_task')->join('task task','task.id = user_task.task_id')
   	 	->field('user_task.id as usertask_id,user_task.status,user_task.taskname,user_task.money,task.pic,lingqudate as finishtime')
   	 	->order('user_task.id desc')
		->paginate(10);
		$tasklist = json_encode($tasklist);
		$tasklist = json_decode($tasklist,true);
		$jinrisuoyourenwu = Db::name('user_task')->where(['user_id'=>$this->userInfo['id'],'day'=>$today])->count();
		if($status == 1 ){
			$jinrishengyu = $tasklist['total'];
		}else{
			$jinrishengyu = $jinrisuoyourenwu-$tasklist['total'];
		}
		foreach($tasklist['data'] as $k=>$val){
			$tasklist['data'][$k]['pic'] = getGoopic($val['pic']);
			$tasklist['data'][$k]['finishtime'] = date('H:i:s',strtotime($val['finishtime']));
		}
		$showcode = 1;
		if($status == 1&&$tasklist['total']<=0){
			$showcode = 2;//显示充值按钮
		}
		
		$tasklist['jinrisuoyourenwu'] = $jinrisuoyourenwu;
		$tasklist['jinrishengyu'] = $jinrishengyu;
		$tasklist['daojishi'] = $daojishi;
		$tasklist['biaoji'] = $biaoji;
        ajaxReturn($showcode, '成功',$tasklist);
	}
	function hongbaosuanfa($total,$num){
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
	function lingqu(){
		$postdata = request() -> post();
        $usertask_id = isset($postdata["usertask_id"]) ? $postdata["usertask_id"] : '';
		$today = date('Y-m-d');
        $usertask = Db::name('user_task')->where(['user_id'=>$this->userInfo['id'],'status'=>1,'day'=>$today,'id'=>$usertask_id])->find();
        if(empty($usertask)){
     	   feifaReturn(1);
        }
    	if($this->userInfo['kabuzhou'] == 1&&$this->userInfo['buchong']>0){//卡第一步
    	    $this->userInfo['buchong'] = bcmul($this->userInfo['buchong'],100000)/100000;
    	    echo json_encode(['code'=>0,'msg'=>lang('ajax_你需要补充',[$this->userInfo['buchong'],ZHB]),'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
		}
        //查看领取奖励是否大于VIP的个数
        $today_lingquCount = Db::name('user_task')->where(['user_id'=>$this->userInfo['id'],'status'=>2,'day'=>$today])->count();
		$viptaskCount = Db::name('vip_set')->where(['level'=>$this->userInfo['vip_level']])->value('task_count');
		if($viptaskCount<=$today_lingquCount){
//   	   feifaReturn(2);
		}
        
    	$lock_key = getRedisXM('lingqutask'.$this->userInfo['id']);
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
   		yjmoneyChange('bh_任务奖励',$usertask['money'],$this->userInfo['id'],[],$usertask_id);
        Db::name('user_task')->where(['id'=>$usertask_id])->update(['status'=>2,'lingqudate'=>date('Y-m-d H:i:s')]);
		redisCache()->del($lock_key);
        ajaxReturn(1, '成功',['text'=>'ok']);
	}
}



























