<?php
namespace app\hnv\controller;
use think\Controller;
use think\Request;
use think\Cache;
use think\Db;
use app\cli\controller\Auto as autocon;
class Tixian extends Base {
    
        private $table = 'good';
    	private $order = 'timn desc';
	    private $addCol = [ ];
    	private $changeCol = [    	];
    
	    public function settixian(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	
	    	$tixian_day_mianfei = isset($postdata['tixian_day_mianfei'])?$postdata['tixian_day_mianfei']:'';
	    	$tixian_day_num = isset($postdata['tixian_day_num'])?$postdata['tixian_day_num']:'';
	    	$tixian_rate = isset($postdata['tixian_rate'])?$postdata['tixian_rate']:'';
	    	$uplevel_cztx = isset($postdata['uplevel_cztx'])?$postdata['uplevel_cztx']:0;
	    	$tixian_rate = floatval($tixian_rate);
	    	$tixian_day_mianfei = intval($tixian_day_mianfei);
	    	$tixian_day_num = intval($tixian_day_num);
	    	
	    	
	    	$dakuan_trx_address = isset($postdata['dakuan_trx_address'])?$postdata['dakuan_trx_address']:'';
	    	$dakuan_trx_key = isset($postdata['dakuan_trx_key'])?$postdata['dakuan_trx_key']:'';
	    	$dakuan_usdt_address = isset($postdata['dakuan_usdt_address'])?$postdata['dakuan_usdt_address']:'';
	    	$dakuan_usdt_key = isset($postdata['dakuan_usdt_key'])?$postdata['dakuan_usdt_key']:'';
	    	$auto_zhuanzhang = isset($postdata['auto_zhuanzhang'])?$postdata['auto_zhuanzhang']:'';
	    	$shoudong_zhuanzhang = isset($postdata['shoudong_zhuanzhang'])?$postdata['shoudong_zhuanzhang']:'';
	    	$anquan_pwd = isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	    	$zuiditikuan = isset($postdata['zuiditikuan'])?$postdata['zuiditikuan']:'';
	    	
	    	$tikuanfangshi = isset($postdata['tikuanfangshi'])?$postdata['tikuanfangshi']:''; //提款方式
	    	
	    	$tikuan = explode(',',$tikuanfangshi);
	    	if(!empty($tikuan))
	    	{
	    	    foreach ($tikuan as $v)
	    	    {
	    	        if($v != 'TRX' && $v != 'USDT')
	    	        {
	    	            htajaxReturn(0,'只支持TRX和USDT');
	    	        }
	        	}
	    	}else
	    	{
	    	    htajaxReturn(0,'格式错误,只支持TRX和USDT');
	    	}
	    	
	    	
	    	if($tixian_day_mianfei<0){
				htajaxReturn(0,'每日免手续费次数必须大于零');
	    	}
	    	if($tixian_day_num<0){
				htajaxReturn(0,'每日次数必须大于零');
	    	}
	    	
	    	if($tixian_rate<0||$tixian_rate>20){
				htajaxReturn(0,'提现费率也离谱了吧');
	    	}
	    	if($shoudong_zhuanzhang<$auto_zhuanzhang){
				htajaxReturn(0,'必须手动转账金额必须大于自动转账的金额');
	    	}
	    	if(!$this->yanzhenganquanpwd($anquan_pwd)){
				htajaxReturn(0,'安全密码错误');
	    	}
	    	
    		$dakuan_usdt = getConfig('dakuan_usdt',0);
     		$dakuan_usdt = json_decode($dakuan_usdt,true);
     		$dakuan_trx = getConfig('dakuan_trx',0);
     		$dakuan_trx = json_decode($dakuan_trx,true);
     		$dakuan_usdt['address'] = $dakuan_usdt_address;
     		$dakuan_trx['address'] = $dakuan_trx_address;
	    	if(strpos($dakuan_trx_key,'********') !== false){ 
			}else{
				$dakuan_trx['key'] = $dakuan_trx_key;
			}
			if(strpos($dakuan_usdt_key,'********') !== false){ 
			}else{
				$dakuan_usdt['key'] = $dakuan_usdt_key;
			}
			
			Db::name('config')->where(['config_sign'=>'tikuanfangshi'])->update(['config_value'=>json_encode($tikuan)]);
	    	Db::name('config')->where(['config_sign'=>'tixian_rate'])->update(['config_value'=>$tixian_rate]);
	    	Db::name('config')->where(['config_sign'=>'tixian_day_mianfei'])->update(['config_value'=>$tixian_day_mianfei]);
	    	Db::name('config')->where(['config_sign'=>'tixian_day_num'])->update(['config_value'=>$tixian_day_num]);
	    	Db::name('config')->where(['config_sign'=>'dakuan_trx'])->update(['config_value'=>json_encode($dakuan_trx)]);
	    	Db::name('config')->where(['config_sign'=>'dakuan_usdt'])->update(['config_value'=>json_encode($dakuan_usdt)]);
	    	Db::name('config')->where(['config_sign'=>'auto_zhuanzhang'])->update(['config_value'=>$auto_zhuanzhang]);
	    	Db::name('config')->where(['config_sign'=>'zuiditikuan'])->update(['config_value'=>$zuiditikuan]);
	    	Db::name('config')->where(['config_sign'=>'shoudong_zhuanzhang'])->update(['config_value'=>$shoudong_zhuanzhang]);
	    	Db::name('config')->where(['config_sign'=>'uplevel_cztx'])->update(['config_value'=>$uplevel_cztx]);
			htajaxReturn(1,'修改成功');
     	}else{
     	    
     		$tixian_rate = getConfig('tixian_rate',0);
     		$tixian_day_num = getConfig('tixian_day_num',0);
     		$tixian_day_mianfei = getConfig('tixian_day_mianfei',0);
     		$uplevel_cztx = getConfig('uplevel_cztx',0);
     		$dakuan_usdt = getConfig('dakuan_usdt',0);
     		$dakuan_usdt = json_decode($dakuan_usdt,true);
     		$dakuan_trx = getConfig('dakuan_trx',0);
     		$dakuan_trx = json_decode($dakuan_trx,true);
     		if(strlen($dakuan_usdt['key'])>1){
     			$dakuan_usdt['key'] = $dakuan_usdt['key'][0].'**********'.$dakuan_usdt['key'][(strlen($dakuan_usdt['key'])-1)];
     		}
     		if(strlen($dakuan_trx['key'])>1){
     			$dakuan_trx['key'] = $dakuan_trx['key'][0].'**********'.$dakuan_trx['key'][(strlen($dakuan_trx['key'])-1)];
     		}
     		$auto_zhuanzhang = getConfig('auto_zhuanzhang',0);
     		$shoudong_zhuanzhang = getConfig('shoudong_zhuanzhang',0);
     		$zuiditikuan = getConfig('zuiditikuan',0);
     		
     		$tikuanfangshi = getConfig('tikuanfangshi',0);
     		$tikuanfangshi = json_decode($tikuanfangshi,true);
     		$tikuan = '';
     		foreach ($tikuanfangshi as $k=>$v)
     		{
     		    if($k)
     		    {
     		        $tikuan .=',';
     		    }
     		    $tikuan .=$v;
     		    
     		}
    //  		echo($tikuan);
     	  //  var_dump($tikuanfangshi[0]);
     	    
     	    $this->assign('tikuanfangshi',$tikuan);
     	    $this->assign('uplevel_cztx',$uplevel_cztx);
	    	$this->assign('zuiditikuan',$zuiditikuan);
	    	$this->assign('shoudong_zhuanzhang',$shoudong_zhuanzhang);
	    	$this->assign('auto_zhuanzhang',$auto_zhuanzhang);
	    	$this->assign('tixian_day_mianfei',$tixian_day_mianfei);
	    	$this->assign('tixian_day_num',$tixian_day_num);
	    	$this->assign('tixian_rate',$tixian_rate);
	    	$this->assign('dakuan_usdt',$dakuan_usdt);
	    	$this->assign('dakuan_trx',$dakuan_trx);
	    	return view();
     	}
    
    }
     public function setaddress(){
         
    	
     	if(request()->isAjax()){//ajax
     	
     	$guanli_id = intval(session('sk_id'));
    	
    	if($guanli_id != 4)
    	{
    	   // htajaxReturn(0,'不允许修改，联系技术');  //排除管理员
    	}
	    	$postdata =  $this->request->post();
	    	$guijizhanghu = isset($postdata['guijizhanghu'])?$postdata['guijizhanghu']:'';
	    	$guiji_usdt_min = isset($postdata['guiji_usdt_min'])?$postdata['guiji_usdt_min']:'';
	    	$anquan_pwd = isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	    	if($guiji_usdt_min<=0||$guiji_usdt_min>100){
				htajaxReturn(0,'usdt归集界限有误');
	    	}
	    	Db::name('config')->where(['config_sign'=>'guijizhanghu'])->update(['config_value'=>$guijizhanghu]);
	    	Db::name('config')->where(['config_sign'=>'guiji_usdt_min'])->update(['config_value'=>$guiji_usdt_min]);
	    	if(!$this->yanzhenganquanpwd($anquan_pwd)){
				htajaxReturn(0,'安全密码错误');
	    	}
			htajaxReturn(1,'修改成功');
     	}else{
     		$guijizhanghu = getConfig('guijizhanghu',0);
     		$guiji_usdt_min = getConfig('guiji_usdt_min',0);
	    	$this->assign('guijizhanghu',$guijizhanghu);
	    	$this->assign('guiji_usdt_min',$guiji_usdt_min);
	    	return view();
     	}
    }
    
    
    public function nengliang(){
        
        $daili_id = intval(session('sk_id'));

    	//*********************************************整理展示字段
    	if($daili_id == 4)
    	{
    	    $showCol = [
    		['col'=>'id','chinaname'=>'订单号','style'=>''],
    		['col'=>'nickname','chinaname'=>'发起人','style'=>''],
    		['col'=>'order_no','chinaname'=>'第三方订单号','style'=>''],
    		['col'=>'receive_address','chinaname'=>'钱包地址','style'=>''],
    		['col'=>'amount','chinaname'=>'资源数量','style'=>''],
    		['col'=>'freeze_day','chinaname'=>'天数','style'=>''],
    		['col'=>'status','chinaname'=>'订单状态','style'=>''],
    		['col'=>'task_status','chinaname'=>'任务状态','style'=>''],
    		['col'=>'cost','chinaname'=>'价格','style'=>''],
    		['col'=>'task_price','chinaname'=>'实际价格','style'=>''],
    		['col'=>'time','chinaname'=>'时间','style'=>''],
    	];
    	}else
    	{
    	    $showCol = [
    		['col'=>'id','chinaname'=>'订单号','style'=>''],
    		['col'=>'nickname','chinaname'=>'发起人','style'=>''],
    		['col'=>'order_no','chinaname'=>'订单号','style'=>''],
    		['col'=>'receive_address','chinaname'=>'钱包地址','style'=>''],
    		['col'=>'amount','chinaname'=>'资源数量','style'=>''],
    		['col'=>'freeze_day','chinaname'=>'天数','style'=>''],
    		['col'=>'status','chinaname'=>'订单状态','style'=>''],
    		['col'=>'task_status','chinaname'=>'任务状态','style'=>''],
    		['col'=>'cost','chinaname'=>'价格','style'=>''],
    		['col'=>'cost','chinaname'=>'时间','style'=>''],
    	    ];
    	}
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 			// 	'yejiguanli'=>['chinaname'=>'业绩管理']
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    // 		'email'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
    	];
    	$where = []; //$this->getWhere($searchCol)
    	//***********************************************整理搜索字段
   	 	if($daili_id != 4)
   	 	{
   	 	    $where['daili_id'] = ['neq',4];
   	 	}
   	 	
   	 	
   	 	$list = Db::name('nengliang')->where($where)->alias('nl')
   	 	->field('nl.*,user.nickname')
   	 	->join('gluser user','user.id = nl.daili_id','left')
   	 	->order('time desc')->paginate(15);//查询数据
   
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
        
        foreach($list as $k=>$v){
   	 		$day = $v['freeze_day'];
   	 		if(!$day)
   	 		{
   	 		   $list[$k]['freeze_day'] = '<b style="color:red;">1小时</b>';
   	 		}else
   	 		{
   	 		    $list[$k]['freeze_day'] = '<b style="color:red;">'.$day.'天</b>';
   	 		}
   	 		
   	 		$status = $v['status'];
   	 		if($status)
   	 		{
   	 		    $list[$k]['status'] = '<span style="color:#8B0000;">成功</span>';
   	 		}else
   	 		{
   	 		    $list[$k]['status'] = '<span style="color:#228B22;">待接单</span>';
   	 		}
   	 		
   	 		$task_status = $v['task_status'];
   	 		$name = '未接单';
   	 		$ncolor = '#1E90FF';
   	 		switch($task_status)
   	 		{
   	 		    case 1:
   	 		      $name = '已接单';
   	 		      $ncolor = '#800080';
   	 		    break;
   	 		    case 2:
   	 		     // $name = '<b style="color:red;">已质押</b>';
   	 		     $name = '<a class="layui-btn  btn-default "  
   	 		                    style="float:right;line-height:25px;height:25px;width:auto;font-size:6px;" >已质押</a>';
   	 		      $ncolor = '#FF0000';
   	 		    break;
   	 		    case 4:
   	 		      $ncolor = '#FF00FF';
   	 		      $name = '订单取消';
   	 		    break;
   	 		}
   	 		
   	 		$list[$k]['task_status'] = '<span style="color:'.$ncolor.';">'.$name.'</span>';
			
			$time = date("Y-m-d H:i:s", $v['time']);
			$list[$k]['time'] = $time;
			
        }
        
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    
    
    
    /**
     * 购买能量
     * @auth true
     * @menu true
     */
    public function add_nengliang()
    {
        
        if (request()->isPost()) {
            
            
            $status = input('post.status/d', 1);
            
            
            $daili_id = intval(session('sk_id'));
    
            $day = input('post.day/s', '');
            $receive_address = input('post.receive_address/s', '');
            $amount = input('post.amount/s', '');
            $sun = 75;
            $nday = $day;
            switch ($day) {
                case '0':
                    // code...
                    $nday = 1;
                    break;
                case '1':
                    // code...
                    $sun = 95;
                    break;
                case '3':
                    // code...
                    $sun = 51;
                    break;
            }
            
            $cost = $amount*0.000001*$sun*$nday+0.56;
            $cost = number_format($cost, 3);
            
            if($amount<32000)
            {
                return $this->error('购买能量不能少于32000');
            }
            
            if(strlen($receive_address) !=34 && strlen($receive_address))
            {
                 return $this->error('钱包地址错误');
             }
            
            
            if($cost > 200)
            {
                // return $this->error('这么贵啊！！联系管理审核添加');
            }

            $orderid = 'NL'.getOrderNo();
            $res = Db::name('nengliang')
                ->insert([
                    'id' => $orderid,
                    'daili_id' => $daili_id,
                    'freeze_day' => $day,
                    'receive_address' => $receive_address,
                    'amount' => $amount,
                    'cost' => $cost,
                    'time' => time(),
                ]);
                
               
            if (!$res) {
                return $this->error('添加失败');
            }else
            {
                
                $caozuotemp = ['pk_id'=>$orderid,'type'=>'nengliang','add_time'=>date('Y-m-d H:i:s'),
	            'op_time'=>date('Y-m-d H:i:s'),'extra'=>'0','qianbao'=>$receive_address,'huobi'=>'TRX'];
	
	            Db::name('caozuo')->insert($caozuotemp);
	            
                 
                //  ajaxReturn(0,'加载中...');
                
                
                $autocon = new autocon();
                $txID = $autocon->ByApiEnergyCost($cost,$orderid); //转账
                
                if($txID)
                {
                    $res = $autocon->transferEnergy($orderid,$receive_address,$amount,$day); //转能量
                }
                // sleep(5);
                return $this->success('发起成功，请等待'); 
            }
           
            
            
        }
        
        $dakuan = getConfig('dakuan_usdt',0); //提现账号
        $dakuan = json_decode($dakuan,true);
        $pay_add = $dakuan['address'];
                                
        $config = Db::name('nconfig')->find();

        $this->neee_trx = $config['tixian_trx'];
        $this->assign('pay_add',$pay_add);
        $this->assign('neee_trx',$this->neee_trx);
        return $this->fetch();
    }
}