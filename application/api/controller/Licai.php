<?php
namespace app\api\controller;
use think\ Db;
use think\Request;
use think\Controller;
use think\Lang;
class Licai extends Base {
	   public function licai(){
    	$list = Db::name('licai')->where(['status'=>1])->field('id,logo,day,rate_day,limit_all_count,shouyi_type,limit_vip_level')->order('order_num desc')->paginate(10);
    	$list = json_encode($list);
		$list = json_decode($list,true);
		$list['col_text'] = ['col_rate_day'=>lang('col_每日利率'),'col_day'=>lang('col_周期'),'col_limit_all_count'=>lang('col_可购买次数'),
		'col_shouyi_type'=>lang('col_收益类型文字'),'col_limit_vip_level'=>lang('col_可购买等级')];
		foreach($list['data'] as $k=>$val){
			$list['data'][$k]['rate_day'] = $val['rate_day'].'%';
			$list['data'][$k]['day'] = $val['day'].lang('danwei_天');
			$list['data'][$k]['logo'] = getGoopic($val['logo']);
			$list['data'][$k]['shouyi_type'] = lang('col_收益类型'.$val['shouyi_type']);
			if($val['limit_all_count'] == 0){
				$list['data'][$k]['limit_all_count'] = lang('col_无限制');
			}
			if($val['limit_vip_level'] == 0){
				$list['data'][$k]['limit_vip_level'] = lang('col_无限制');
			}else{
				$list['data'][$k]['limit_vip_level'] = 'VIP'.$val['limit_vip_level'];
			}
		}
		unset($list['per_page']);
       ajaxReturn(1, '成功',$list);
   }
    public function licaiDetail(){
    	$postdata = request() -> post();
        $id = isset($postdata["id"]) ? $postdata["id"] : '';
    	$licai = Db::name('licai')->where(['id'=>$id])->field('id,buy_min,buy_max,day,rate_day,shouyi_type,limit_all_count,limit_vip_level')->find();
    	if(empty($licai)){
     	   feifaReturn(1);
    	}
    	$licai['yue'] = $this->userInfo['licai_balance'];
    	$licai['yjyue'] = $this->userInfo['commission_balance'];
    	$licai['col_text'] = ['col_buy_min'=>lang('col_起始金额'),'col_buy_max'=>lang('col_最高投资额'),'col_zhouqi'=>lang('col_周期'),
    	'col_支付账户'=>lang('col_支付账户'),'col_佣金账户'=>lang('col_佣金账户'),'col_理财账户'=>lang('col_理财账户'),
    	'col_账户可用余额'=>lang('col_账户可用余额'),'col_投资金额'=>lang('col_投资金额'),'col_佣金账户可用余额'=>lang('col_佣金账户可用余额'),'col_理财账户可用余额'=>lang('col_理财账户可用余额'),
		'col_rate_day'=>lang('col_每日利率'),'col_shouyi_type'=>lang('col_收益类型文字'),'col_limit_all_count'=>lang('col_可购买次数'),'col_limit_vip_level'=>lang('col_可购买等级')];
		$licai['shouyi_type'] = lang('col_收益类型'.$licai['shouyi_type']);
		$licai['day_num'] = $licai['day'];
		$licai['rate'] = $licai['rate_day'];
		$licai['day'] = $licai['day'].lang('danwei_天');
		$licai['rate_day'] = $licai['rate_day'].'%';
		if($licai['buy_min'] == 0){
			$licai['buy_min'] = lang('col_无限制');
		}
		if($licai['buy_max'] == 0){
			$licai['buy_max'] = lang('col_无限制');
		}
		if($licai['limit_all_count'] == 0){
			$licai['limit_all_count'] = lang('col_无限制');
		}
		if($licai['limit_vip_level'] == 0){
			$licai['limit_vip_level'] = lang('col_无限制');
		}else{
			$licai['limit_vip_level'] = 'VIP'.$licai['limit_vip_level'];
		}
		
		
       ajaxReturn(1, '成功',$licai);
  	}
  	function buylicai(){
  		$postdata = request() -> post();
        $id = isset($postdata["id"]) ? $postdata["id"] : '';
        $money = isset($postdata["money"]) ? $postdata["money"] : '';
        $anquan_pwd = isset($postdata["anquan_pwd"]) ? $postdata["anquan_pwd"] : '';
        $zhifu_type= isset($postdata["zhifu_type"]) ? $postdata["zhifu_type"] : 2;
        
        
        $money = floatval($money);
        if(empty($anquan_pwd)){
       		ajaxReturn(0, 'ajax_安全密码不能为空');
        }
        if($money<=0){
       		ajaxReturn(0, 'ajax_金额非法');
        }
        $licai = Db::name('licai')->find($id);
        if(empty($licai)||$licai['status']!=1){
        	feifaReturn(1);
        }
        if($licai['buy_min']!=0&&$money<$licai['buy_min']){
			echo json_encode(['code'=>0,'msg'=>lang('ajax_最低投资').$licai['buy_min'],'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
        }
        if($licai['buy_max']!=0&&$money>$licai['buy_max']){
			echo json_encode(['code'=>0,'msg'=>lang('ajax_最高投资').$licai['buy_max'],'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
        }
        if($this->userInfo['vip_level']<$licai['limit_vip_level']){
			echo json_encode(['code'=>0,'msg'=>lang('ajax_会员等级限制').'VIP'.$licai['limit_vip_level'],'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
        }
        $yueziduan = 'licai_balance';
        if($zhifu_type == 1){
      	  $yueziduan = 'commission_balance';//佣金余额
        }elseif($zhifu_type == 2){
      	  $yueziduan = 'licai_balance';//理财余额
        }else{
        	feifaReturn(1);
        }
		$myMoney = Db::name('user')->where(['id'=>$this->userInfo['id']])->value($yueziduan);
		if($myMoney<$money){
       		ajaxReturn(0, 'ajax_余额不足');
		}
		//购买总次数限制
		if($licai['limit_all_count']>0){
			$goumaiCount = Db::name('user_licai')->where(['user_id'=>$this->userInfo['id'],'licai_id'=>$id])->count();
			if($goumaiCount>=$licai['limit_all_count']){
				echo json_encode(['code'=>0,'msg'=>lang('ajax_购买次数限制').$licai['limit_all_count'],'data'=>[]],JSON_UNESCAPED_UNICODE);exit();
			}
		}
		
		
	    $anquan_pwd = md5($anquan_pwd.$this->userInfo['salt']);
        if($anquan_pwd!=$this->userInfo['anquan_pwd']){
       		ajaxReturn(0, 'ajax_安全密码错误');
        }
        		      //防止多次连续操作
     	$lock_key = getRedisXM('buylicai'.$this->userInfo['id']);
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
        $add_time = time();
        $daoqi_time = date('Y-m-d H:i:s',($add_time+$licai['day']*24*60*60));
		$zonglirun = bcmul($licai['rate_day']*0.01*$licai['day'],$money,2);
		$orderNo = 'CL'.getOrderNo();
        $usedlicaidata = ['name'=>$licai['name'],'order_num'=>$orderNo,'licai_id'=>$licai['id'],'user_id'=>$this->userInfo['id'],'add_time'=>date('Y-m-d H:i:s',$add_time),'money'=>$money,
        'daoqi_time'=>$daoqi_time,'rate_day'=>$licai['rate_day'],'shouyi_type'=>$licai['shouyi_type'],'day'=>$licai['day'],'zonglirun'=>$zonglirun];
       	if($zhifu_type == 1){//扣佣金
       		yjmoneyChange('bh_购买理财',-$money,$this->userInfo['id'],[]);
       	}else{//扣理财
       		licaimoneyChange('bh_购买理财',-$money,$this->userInfo['id'],[]);
       	}
       	Db::name('user_licai')->insert($usedlicaidata);
		$pkid = Db::name('user_licai')->getLastInsID();
		
		$detail_issue = [];
		$caozuo = [];
		$issue =1;
		if($licai['shouyi_type'] ==1){//每天结息
			for($i=1;$i<=$licai['day'];$i++){
				$lixi = bcmul($licai['rate_day']*0.01,$money,2);
	        	$daoqi_time = date('Y-m-d H:i:s',($add_time+$i*24*60*60));
				$temp = ['licai_id'=>$licai['id'],'user_id'=>$this->userInfo['id'],'user_licai_id'=>$pkid,'issue'=>$issue,
				'lixi'=>$lixi,'benjin'=>$money,'daoqi_time'=>$daoqi_time,'status'=>1];
				$caozuotemp = ['pk_id'=>0,'type'=>'licai','add_time'=>date('Y-m-d H:i:s',$add_time),
				'op_time'=>$daoqi_time,'extra'=>json_encode(['user_licai_id'=>$pkid,'issue'=>$issue])];
				$issue++;
				$detail_issue[] = $temp;
				$caozuo[] = $caozuotemp;
			}
		}elseif($licai['shouyi_type'] ==2){//到期归还结息
			$lixi = bcmul($licai['day']*$licai['rate_day']*0.01,$money,2);
        	$daoqi_time = date('Y-m-d H:i:s',($add_time+$licai['day']*24*60*60));
			$temp = ['licai_id'=>$licai['id'],'user_id'=>$this->userInfo['id'],'user_licai_id'=>$pkid,'issue'=>$issue,
			'lixi'=>$lixi,'benjin'=>$money,'daoqi_time'=>$daoqi_time,'status'=>1];
			$caozuotemp = ['pk_id'=>0,'type'=>'licai','add_time'=>date('Y-m-d H:i:s',$add_time),
			'op_time'=>$daoqi_time,'extra'=>json_encode(['user_licai_id'=>$pkid,'issue'=>$issue])];
			$caozuo[] = $caozuotemp;
			$detail_issue[] = $temp;
		}
		DB::name('user_licai_issue')->insertAll($detail_issue);
		DB::name('caozuo')->insertAll($caozuo);
		redisCache()->del($lock_key);
     	ajaxReturn(1, '成功');
  	}
	public function licaiRecord(){
    	$list = Db::name('user_licai')->where(['user_id'=>$this->userInfo['id']])
    	->field('id as userlicai_id,order_num,licai_id,add_time,money,daoqi_time,day,rate_day,zonglirun,status')
    	->order('add_time desc')->paginate(10);
		$list = json_encode($list);
		$list = json_decode($list,true);
		$list['col_text'] = ['col_投资金额'=>lang('col_投资金额'),'col_理财ID'=>lang('col_理财ID'),'col_订单号'=>lang('col_订单号'),
		'col_投资到期时间'=>lang('col_投资到期时间'),'col_投资到期利润'=>lang('col_投资到期利润'),'col_投资时间'=>lang('col_投资时间'),
		'col_每日利率'=>lang('col_每日利率')];
		foreach($list['data'] as $k=>$val){
			$list['data'][$k]['statusval'] = $val['status'];
			$list['data'][$k]['status'] = $list['data'][$k]['status'] ==1?lang('col_未结算'):lang('col_已结算');
			$list['data'][$k]['day'] .= lang('danwei_天'); 
			$list['data'][$k]['rate_day'] .= '%'; 
		}
		unset($list['per_page']);
        ajaxReturn(1, '成功',$list);
	}
	public function recordDetail(){
		$postdata = request() -> post();
        $userlicai_id = isset($postdata["userlicai_id"]) ? $postdata["userlicai_id"] : '';
		$userlicai = Db::name('user_licai')->find($userlicai_id);
        if(empty($userlicai)||$userlicai['user_id']!=$this->userInfo['id']){
        	feifaReturn(1);
        }
    	$list = Db::name('user_licai_issue')->where(['user_id'=>$this->userInfo['id'],'user_licai_id'=>$userlicai_id])
    	->field('lixi,status,daoqi_time,daoqi_time,issue')
    	->order('issue asc')->paginate(10);
		$list = json_encode($list);
		$list = json_decode($list,true);
		$list['col_text'] = ['col_期数到期时间'=>lang('col_投资金额'),'col_金额'=>lang('col_金额')];
		foreach($list['data'] as $k=>$val){
			$list['data'][$k]['statusval'] = $val['status'];
			$list['data'][$k]['status'] = $list['data'][$k]['status'] ==1?lang('col_未结算'):lang('col_已结算');
		}
		unset($list['per_page']);
        ajaxReturn(1, '成功',$list);
	}
}