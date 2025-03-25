<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Db;
use app\cli\controller\Auto as autocon;
class Tongji extends Base {
    public function main(){
    	$today = date('Y-m-d');
    	//基础账户余额
    	$jichu_balance = DB::name('user')->where([])->sum('basic_balance');
    	$yongjin_balance = DB::name('user')->where([])->sum('commission_balance');
    	$licai_balance = DB::name('user')->where([])->sum('licai_balance');
    	$licaiyue = DB::name('user_licai')->where(['status'=>1])->sum('money');
    	$data = ['jichu_balance'=>$jichu_balance,'yongjin_balance'=>$yongjin_balance,'licai_balance'=>$licai_balance,'licaiyue'=>$licaiyue];
    	$this->assign('data', $data);
    	$this->assign('today', $today);
    	
    	//今日数据
    	$startDay = $today.' 00:00:00';
    	$endDay = $today.' 23:59:59';
    	$chongzhirenshu = DB::name('invest_order')->where(['status'=>2,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->group('user_id')->count();
    	$chongzhibishu = DB::name('invest_order')->where(['status'=>2,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->count();
    	$zhucerenshu = DB::name('user')->where(['add_time'=>[['egt',$startDay],['elt',$endDay]]])->count();
    	$shouchongsql = "SELECT
					*
				FROM
					(
						SELECT
							min(add_time) AS shouchongTime,
							user_id,
							zuihou_value,
							id,
							COUNT(*)
						FROM
							jxxj_invest_order
						WHERE
							`status` = 2
						GROUP BY
							user_id
					) AS tab1
				WHERE
					shouchongTime >= '$startDay'
				AND shouchongTime <= '$endDay'" ;
    	$shouchongdata = Db::query($shouchongsql);
    	$shouchongrenshu = count($shouchongdata);
    	$shouchongjine = 0;
    	if($shouchongrenshu>0){
    		$qiuhe = array_column($shouchongdata,'zuihou_value');
    		$shouchongjine = array_sum($qiuhe);
    	}
    	$this->assign('chongzhirenshu', $chongzhirenshu);
    	$this->assign('chongzhibishu', $chongzhibishu);
    	$this->assign('zhucerenshu', $zhucerenshu);
    	$this->assign('shouchongrenshu', $shouchongrenshu);
    	$this->assign('shouchongjine', $shouchongjine);
    	
    	return view();
	}
	function xiangxidata(){
		$postdata =  $this->request->post();
    	$startday = isset($postdata['startday'])?$postdata['startday']:'';
    	$endday = isset($postdata['endday'])?$postdata['endday']:'';
    	$startDay = $startday.' 00:00:00';
    	$endDay = $endday.' 23:59:59';
    	$tongjishijian = $startDay.'  ~  '.$endDay;
    	//新增用户
		$newUser = DB::name('user')->where(['add_time'=>[['egt',$startDay],['elt',$endDay]]])->field('id,xfje')->select();
    	$newUsercount = count($newUser);
    	$newchongzhi = 0;
    	if($newUsercount>0){
    		foreach($newUser as $val){
    			$newchongzhi = bcadd($newchongzhi,$val['xfje'],6);
    		}
    	}
    	
    	//新增提币
    	$withdrawal_money = round(DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->sum('zuihou_value'),2);
    	$withdrawal_money_trx = round(DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startDay],['elt',$endDay]],'huobi'=>'TRX'])->sum('money'),2);
    	$withdrawal_money_usdt = round(DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startDay],['elt',$endDay]],'huobi'=>'USDT'])->sum('money'),2);
    	$shouxvfei = DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->sum('zuihou_shouxvfei');
    	//提现人数
    	$tixianrenshu = DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->group('user_id')->count();
    	$tixianbishu = DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->count();
    	//充值金额，人数，笔数
    	$chongzhi = DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->sum('zuihou_value');
    	$chongzhirenshu = DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->group('user_id')->count();
    	$chongzhibishu = DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startDay],['elt',$endDay]]])->count();
    	
    	
    	$chongzhi_money_trx = round(DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startDay],['elt',$endDay]],'huobi'=>'TRX'])->sum('money'),2);
    	$chongzhi_money_usdt = round(DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startDay],['elt',$endDay]],'huobi'=>'USDT'])->sum('money'),2);
    	
    	//挖矿金额
    	$wakuangJine = DB::name('user_task')->where(['lingqudate'=>[['egt',$startDay],['elt',$endDay]],'status'=>2])->sum('money');
    	$wakuangrenshu = DB::name('user_task')->where(['lingqudate'=>[['egt',$startDay],['elt',$endDay]],'status'=>2])->group('user_id')->count();
    	//收益
    	$thisczfl = DB::name('chognzhifanli')->where(['add_time'=>[['egt',$startDay],['elt',$endDay]]])
    	->sum('money');
    	//挖矿返利
    	$wakuangfanli = DB::name('wakuang')->where(['daoqi_day'=>[['egt',$startday],['elt',$endday]]])->sum('lixi');
    	///理财收益
    	$licaishouyi = DB::name('user_licai_issue')->where(['daoqi_time'=>[['egt',$startDay],['elt',$endDay]]])->sum('lixi');
    	
    	//理财购买
    	$licaigoumai = DB::name('user_licai')->where(['add_time'=>[['egt',$startDay],['elt',$endDay]]])->sum('money');
    	$licaidaoqi = DB::name('user_licai')->where(['daoqi_time'=>[['egt',$startDay],['elt',$endDay]]])->sum('money');
    	
    	
    	
    	
    	$data = ['tongjishijian'=>$tongjishijian,'newUsercount'=>$newUsercount,'newchongzhi'=>$newchongzhi,'withdrawal_money'=>$withdrawal_money
    	,'withdrawal_money_trx'=>$withdrawal_money_trx,'withdrawal_money_usdt'=>$withdrawal_money_usdt
    	,'shouxvfei'=>$shouxvfei,'tixianrenshu'=>$tixianrenshu,'tixianbishu'=>$tixianbishu,'wakuangJine'=>$wakuangJine,'wakuangrenshu'=>$wakuangrenshu,
    	'thisczfl'=>$thisczfl,'wakuangfanli'=>$wakuangfanli,'licaishouyi'=>$licaishouyi,'licaigoumai'=>$licaigoumai,'licaidaoqi'=>$licaidaoqi,
    	'chongzhi'=>$chongzhi,'chongzhirenshu'=>$chongzhirenshu,'chongzhibishu'=>$chongzhibishu,'chongzhi_money_trx'=>$chongzhi_money_trx,'chongzhi_money_usdt'=>$chongzhi_money_usdt,
    	];
		htajaxReturn(1,'成功',$data);
	}
	function jingshi(){
		$weifenpei = Db::name('addressdizhi')->where(['user_id'=>0])->count();
		$dakuan_usdt = getConfig('dakuan_usdt',0);
 		$dakuan_usdt = json_decode($dakuan_usdt,true);
 		$dakuan_trx = getConfig('dakuan_trx',0);
 		$dakuan_trx = json_decode($dakuan_trx,true);
 		
 		$config = Db::name('nconfig')->find();
	                     
	    $tixian_Energy = $config['tixian_energy'];
	                     
 		//查TRX余额
 		$addresstrx = $dakuan_trx['address'];
 		$addressusdt = $dakuan_usdt['address'];
 		$domain = 'https://apilist.tronscanapi.com';

		$urltrx = "$domain/api/account/tokens?address=$addresstrx&start=0&limit=20&token=trx&hidden=0&show=0&sortType=0";
		$urlusdt = "$domain/api/account/tokens?address=$addressusdt&start=0&limit=20&token=USDT&hidden=0&show=0&sortType=0";
 		$autocon = new autocon();
 		$nengliang = $autocon->getEnergy($addressusdt);
 		if($nengliang)
 		{
 		    $tixian_Energy = $nengliang;
 		}
 		
//  		$autocon->updateConfig();
//     	$re_trx = $autocon->http_get($urltrx);
//     	$re_usdt = $autocon->http_get($urlusdt);

        $re_trx = http_get($urltrx);
    	$re_usdt = http_get($urlusdt);
    	
    	$re_usdt = json_decode($re_usdt,true);
    	
    // 	var_dump($re_usdt);
    	$re_trx = json_decode($re_trx,true);
		$trxyue = isset($re_trx['data'][0]['amount'])?$re_trx['data'][0]['amount']:0;
		$usdtyue = isset($re_usdt['data'][0]['quantity'])?$re_usdt['data'][0]['quantity']:0;
		
		if($trxyue >0 || $usdtyue >0)
		{
		    if($trxyue)
		    {
		        $arr['tixian_trx'] = $trxyue;
		    }
		    if($usdtyue)
		    {
		        $arr['tixian_usdt'] = $usdtyue;
		    }
		    if($tixian_Energy)
		    {
		        $arr['tixian_energy'] = $tixian_Energy;
		    }
		    
		    Db::name('nconfig')->where(['id'=>1])->update($arr);
		}
		
		
		if(!$trxyue)
		{
		    $trxyue = $config['tixian_trx'];
		}
		if(!$usdtyue)
		{
		    $usdtyue = $config['tixian_usdt'];
		}
    	$jifen = Db::name('sys_jifen')->find(1);
		htajaxReturn(1,'成功',['weifenpei'=>$weifenpei,'usdtyue'=>$usdtyue,'trxyue'=>$trxyue,'jifen'=>$jifen['jifen'],'energy'=>$tixian_Energy]);
	}
	 public function meiri(){
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$jianday = ($page-1)*10;
   	 	$endday = date('Y-m-d',strtotime('-'.$jianday.' day'));
   	 	$startday = date('Y-m-d',strtotime('-'.($jianday+10).' day'));
   	 	
   	 	
   	 	$startdayTime = $startday.' 00:00:00';
   	 	$enddayTime = $endday.' 23:59:59';
   	 	$where = ['add_time'=>[['egt',$startdayTime],['elt',$enddayTime]]];
   	 	$dayArray  = $this->getEveryday($startday,$endday);
   	 	$xinzengyonghuSql = "select COUNT(*) as shuliang, DATE_FORMAT(add_time,'%Y-%m-%d') as zhucetime  FROM jxxj_user GROUP BY zhucetime";
   	 	$xinzengRe = Db::query($xinzengyonghuSql);
   	 	$meiridata = [];
   	 	$xinyonghu = [];

    	
   	 	//充值TRX
    	$chongzhi_money_trx = DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]],'huobi'=>'TRX'])
    	->field("sum(money) as money, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
    	$chongzhi_money_usdt = DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]],'huobi'=>'USDT'])
    	->field("sum(money) as money, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
    	$chongzhiAll = DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]]])
    	->field("sum(zuihou_value) as money, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
    	
    	$chongzhirenshu = DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]]])
    	->field("count(user_id) as num, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('user_id')
    	->select();
    	
    	$arr = array_column($chongzhirenshu, 'caozuotime');
    	$num_count = array_count_values($arr);

    	
    	$chongzhibishu = DB::name('invest_order')->where(['status'=>2,'status2'=>0,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]]])
    	->field("count(id) as num, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
    	
   	 	foreach($chongzhibishu as $val){
   	 		$meiridata[$val['caozuotime']]['chongzhibishu'] = $val['num'];
   	 	}
   	 	
    	
   	 	foreach($xinzengRe as $val){
   	 		$meiridata[$val['zhucetime']]['zhuceshu'] = $val['shuliang'];
   	 	}
   	 	foreach($chongzhi_money_trx as $val){
   	 		$meiridata[$val['caozuotime']]['chongzhi_trx'] = $val['money'];
   	 	}
   	 	foreach($chongzhi_money_usdt as $val){
   	 		$meiridata[$val['caozuotime']]['chongzhi_usdt'] = $val['money'];
   	 	}
   	 	foreach($chongzhiAll as $val){
   	 		$meiridata[$val['caozuotime']]['chongzhiAll'] = $val['money'];
   	 	}
   	 	//提现的
   	 	$tixian_money_usdt = DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]],'huobi'=>'USDT'])
    	->field("sum(money) as money, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
		$tixian_money_trx = DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]],'huobi'=>'TRX'])
    	->field("sum(money) as money, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
		$tixianAll = DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]]])
    	->field("sum(zuihou_value) as money, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
    	
    // 	echo("end=".$enddayTime);
    	$tixian_renshu = DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]]])
    	->field("count(user_id) as num, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
    // 	var_dump($tixian_renshu);
    	
    // 	$dd = DB::name('draw_order')->getlastsql();
    // 	echo($dd);
    	
    // 	var_dump($tixian_renshu);
    	$arr = array_column($tixian_renshu, 'caozuotime');
    // 	var_dump($arr);
    	$tixian_count = array_count_values($arr);
        // var_dump($tixian_count);
    	
    	$tixian_bishu = DB::name('draw_order')->where(['status'=>2,'add_time'=>[['egt',$startdayTime],['elt',$enddayTime]]])
    	->field("count(id) as num, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
    	
   	 	foreach($tixian_bishu as $val){
   	 		$meiridata[$val['caozuotime']]['tixian_renshu'] = $val['num'];
   	 	}
    	
    	
   	 	foreach($tixian_money_trx as $val){
   	 		$meiridata[$val['caozuotime']]['tixian_trx'] = $val['money'];
   	 	}
   	 	foreach($tixian_money_usdt as $val){
   	 		$meiridata[$val['caozuotime']]['tixian_usdt'] = $val['money'];
   	 	}
   	 	foreach($tixianAll as $val){
   	 		$meiridata[$val['caozuotime']]['tixianAll'] = $val['money'];
   	 	}
   	 	//挖矿支出
   	 	$wakuang = DB::name('user_task')->where(['status'=>2,'lingqudate'=>[['egt',$startdayTime],['elt',$enddayTime]]])
    	->field("sum(money) as money,  DATE_FORMAT(lingqudate,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
//  	echo  DB::name('user_task')->getlastsql();
   	 	foreach($wakuang as $val){
   	 		$meiridata[$val['caozuotime']]['wakuang'] = $val['money'];
   	 	}
   	 	//充值提成
   	 	$chongzhiticheng = DB::name('chognzhifanli')->where(['add_time'=>[['egt',$startdayTime],['elt',$enddayTime]]])
    	->field("sum(money) as money, DATE_FORMAT(add_time,'%Y-%m-%d') as caozuotime")
    	->group('caozuotime')
    	->select();
   	 	foreach($chongzhiticheng as $val){
   	 		$meiridata[$val['caozuotime']]['chongzhiticheng'] = $val['money'];
   	 	}
   	 		//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'day','chinaname'=>'日期','style'=>''],
    		['col'=>'zhuceshu','chinaname'=>'注册用户','style'=>''],
    		['col'=>'chongzhiAll','chinaname'=>'总共充值（'.ZHB.'）</br>其中充值TRX</br>其中充值USDT','style'=>''],
    		['col'=>'wakuang','chinaname'=>'领取任务('.ZHB.')','style'=>''],
    		['col'=>'chongzhiticheng','chinaname'=>'充值提成','style'=>''],
    		['col'=>'chongzhirenshu','chinaname'=>'充值信息','style'=>''],
    		
    		['col'=>'tixianAll','chinaname'=>'提现','style'=>''],
    		['col'=>'tixian_renshu','chinaname'=>'提现信息','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[];
    	//***********************************************整理搜索字段
    	$list = [];
// 	 	je($meiridata);
   	 	$dayArray  = array_reverse($dayArray);
   	 	foreach($dayArray as $v){
   	 		$meiridata[$v]['day'] = $v;
 			$list[] = $meiridata[$v];
        }
        foreach($list as $k=> $v){
        	if(isset($v['chongzhiAll'])&&$v['chongzhiAll']>0){
        		$v['chongzhi_usdt'] = isset($v['chongzhi_usdt'])?$v['chongzhi_usdt']:0;
        		$v['chongzhi_trx'] = isset($v['chongzhi_trx'])?$v['chongzhi_trx']:0;
        		
        		$list[$k]['chongzhiAll'].='   '.ZHB.'</br>'.$v['chongzhi_trx'].'   T</br>'.$v['chongzhi_usdt'].'   U';
        	}
        	if(isset($v['tixianAll'])&&$v['tixianAll']>0){
        		$v['tixian_trx'] = isset($v['tixian_trx'])?$v['tixian_trx']:0;
        		$v['tixian_usdt'] = isset($v['tixian_usdt'])?$v['tixian_usdt']:0;
        		$list[$k]['tixianAll'].='   '.ZHB.'</br>'.$v['tixian_trx'].'   T</br>'.$v['tixian_usdt'].'   U';
        	}
        	
        	if(isset($v['chongzhibishu'])&&$v['chongzhibishu']>0 && !empty($num_count[$v['day']])){
        	    $renshu = $num_count[$v['day']];
        	    
        	   // $v['chongzhirenshu'] = '人数:'. $v['chongzhirenshu']."</br>笔数:".$v['chongzhibishu'];
        	    $list[$k]['chongzhirenshu'] = '人数:'. $renshu."</br>笔数:".$v['chongzhibishu'];
        	    
        	}
        	
        	if(isset($v['tixian_renshu'])&&$v['tixian_renshu']>0){
        	    $renshu = $tixian_count[$v['day']];
        	    
        	   // $v['chongzhirenshu'] = '人数:'. $v['chongzhirenshu']."</br>笔数:".$v['chongzhibishu'];
        	    $list[$k]['tixian_renshu'] = '人数:'. $v['tixian_renshu']."</br>笔数:".$v['tixian_renshu'];
        	    
        	}
        	
        }
        
        
   	 	$pageInfo = setPage(10*10,$page);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    	return view();
	}
	 function getEveryday($startday,$end_day)
	{
	    $stimestamp = strtotime($startday);
	    $etimestamp = strtotime($end_day);
	    // 计算日期段内有多少天
	    $days = ($etimestamp - $stimestamp) / 86400 + 1;
	    // 保存每天日期
	    $date = array();
	    for ($i = 0; $i < $days; $i++) {
	        $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
	    }
	    return $date;
	}
}