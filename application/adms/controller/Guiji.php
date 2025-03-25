<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Cache;
use think\Db;
class Guiji extends Base {
    public function useraddress(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'address','chinaname'=>'地址','style'=>''],
    		['col'=>'yonghu','chinaname'=>'充值用户','style'=>''],
    		['col'=>'usdt','chinaname'=>'USDT余额','style'=>''],
    		['col'=>'trx','chinaname'=>'TRX余额','style'=>''],
    		['col'=>'chongzhijilu','chinaname'=>'充值记录','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
    	
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    // 		'address'=>['chinaname'=>'地址','ca'=>'like','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	$where['user_id'] = ['neq',0];
    	//***********************************************整理搜索字段
   	 	$list = Db::name('addressdizhi')->where($where)->order('user_id desc')->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$yonghu = Db::name('user')->where(['id'=>$v['user_id']])->field('id,email,tel')->find();
   	 		if(!empty($yonghu)){
   	 			$list[$k]['yonghu'] = $yonghu['email'].'</br>'.$yonghu['tel'];
   	 			$adress = $v['address'];
   	 			$vv = "<a address='$adress' class='layui-btn layui-btn-mini layui-btn-warm logoset'>查看记录</a>";
   	 			$list[$k]['chongzhijilu'] = $vv;
   	 		}
   	 	}
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
     public function record(){
    	//*********************************************整理展示字段
    	$showCol = [
    	    ['col'=>'invest_id','chinaname'=>'订单号','style'=>''],
    		['col'=>'from_address','chinaname'=>'转账钱包','style'=>''],
    		['col'=>'to_address','chinaname'=>'收款钱包','style'=>''],
    		['col'=>'huobi','chinaname'=>'归集类型','style'=>''],
    		['col'=>'money','chinaname'=>'金额','style'=>''],
    		['col'=>'add_time','chinaname'=>'时间','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'from_address'=>['chinaname'=>'转账钱包','ca'=>'eq','type'=>'text','style'=>''],
    		'to_address'=>['chinaname'=>'收款钱包','ca'=>'eq','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	$where['status'] = 1;
    	//***********************************************整理搜索字段
   	 	$list = Db::name('guiji_record')->where($where)->order('add_time desc')->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$domain = 'https://tronscan.org';
            
            $list[$k]['from_address'] = '<a class="dizhi" href="'.$domain.'/#/address/'.$v['from_address'].'" target="_blank">'.$v['from_address'].'</a>';
			$list[$k]['to_address'] ='<a class="dizhi" href="'.$domain.'/#/address/'.$v['to_address'].'" target="_blank">'.$v['to_address'].'</a>';
 			$list[$k]['status'] = ['status'] == 1?fontcolor('未完成','red'):fontcolor('已完成','green');
 			$list[$k]['money'] = '<b>'.$list[$k]['money'].'<b>';
   	 		
   	 	}
 	 	$addshow = 0;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    public function yijianguiji(){
    	$list = DB::name('guiji_record')->where(['status'=>0])->select();
    	foreach($list as $val){
    		xvyaoguiji($val['from_address'],$val['huobi'],0);
    		DB::name('guiji_record')->where(['id'=>$val['id']])->delete();
    	}
		htajaxReturn(1,'操作成功，重新发起归集'.count($list).'条');
    }
}