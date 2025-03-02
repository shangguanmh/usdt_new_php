<?php
namespace app\hnv\controller;
use think\Controller;
use think\Request;
use think\Db;

use app\api\controller\Invest as Apiinvest;
class Invest extends Base {
	private $table = 'invest_order';
	private $order = 'id desc';
	private $addCol = [];
	private $changeCol = [];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    // 		['col'=>'id','chinaname'=>'id/订单号','style'=>''],
//  		['col'=>'order_num','chinaname'=>'订单号','style'=>''],
    		['col'=>'user_id','chinaname'=>'用户ID<br>email</br>手机号','style'=>''],
    		['col'=>'to_address','chinaname'=>'充值钱包','style'=>''],
    // 		['col'=>'from_address','chinaname'=>'用户钱包','style'=>''],
    		['col'=>'to_balance','chinaname'=>'到账钱包','style'=>''],
    		['col'=>'huobi','chinaname'=>'充值货币</br>充值金额</br>等值usdt','style'=>''],
    		['col'=>'add_time','chinaname'=>'充值时间','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>'']
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
// 				'tongguo'=> 	['chinaname'=>'通过'],
// 				'butongguo'=>['chinaname'=>'不通过']
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'order_num'=>['chinaname'=>'订单号','ca'=>'like','type'=>'text','style'=>''],
    		'email'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	$where['invest.status']= 1;
    	//***********************************************整理搜索字段
   	 	$list = Db::name($this->table)
   	 	->alias('invest')->join('user user','user.id = invest.user_id')
   	 	->field('invest.*,user.email,user.tel,user.id as userid')
   	 	->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 	    $list[$k]['id'] = '序号ID:'.$v['id'];
   	 	    $list[$k]['id'] .= '<br>'.$v['order_num'];
   	 	    
			$list[$k]['statusVal'] = $v['status'];
			if($v['status'] ==1){
				$list[$k]['status'] = '待审核';   	 		
			}elseif($v['status'] ==2){
				$list[$k]['status'] = fontcolor('已通过','green');   	 		
			}elseif($v['status'] ==3){
				$list[$k]['status'] = fontcolor('不通过','red');  	 		
			}
			$list[$k]['to_balance'] = $v['to_balance']== 1?'基础账户':'理财账户';
			$list[$k]['huobi'] .='</br>'.$v['money'].'</br>'.$v['zuihou_value'];
			
			$list[$k]['user_id'] = '用户ID:'.$v['user_id'];
			$list[$k]['user_id'] .='</br>'.$v['email'].'</br>'.$v['tel'];
			$domain = 'https://tronscan.org';

			$list[$k]['to_address'] ='<a class="dizhi" href="javascript:void(0);" target="_blank">'.$v['to_address'].'</a>';
        }
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
     public function main1(){
    	//*********************************************整理展示字段
    	$showCol = [
    // 		['col'=>'id','chinaname'=>'id','style'=>''],
 		    ['col'=>'order_num','chinaname'=>'订单号','style'=>''],
    		['col'=>'user_id','chinaname'=>'用户ID<br>email</br>手机号','style'=>''],
    		['col'=>'to_address','chinaname'=>'充值钱包','style'=>''],
    // 		['col'=>'from_address','chinaname'=>'用户钱包','style'=>''],
    // 		['col'=>'to_balance','chinaname'=>'到账钱包','style'=>''],
    		['col'=>'huobi','chinaname'=>'充值货币</br>充值金额</br>等值usdt','style'=>''],
    		['col'=>'add_time','chinaname'=>'充值时间','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
    		['col'=>'guiji','chinaname'=>'操作','style'=>'']
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
   	// 			'chongxinguiji'=> ['chinaname'=>'重新归集'],
    	
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'order_num'=>['chinaname'=>'订单号','ca'=>'like','type'=>'text','style'=>''],
    		'email'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
    		'status'=>['chinaname'=>'状态','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'2','text'=>'成功'],['id'=>'3','text'=>'失败']],'style'=>''],
    		'to_balance'=>['chinaname'=>'到账钱包','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'1','text'=>'基础账户'],['id'=>'2','text'=>'理财账户']],'style'=>'']
    	];
    	$where = $this->getWhere($searchCol);
    	if(isset($where['status'])&&!empty($where['status'])){
    		$where['invest.status']= $where['status'];
    	}else{
    		$where['invest.status']= ['in',[2,3]];
    	}
    	unset($where['status']);
    	//***********************************************整理搜索字段
   	 	$list = Db::name($this->table)
   	 	->alias('invest')->join('user user','user.id = invest.user_id')
   	 	->join('gluser gluser','gluser.id = invest.shenhe_user_id','left')
   	 	->join('guiji_record guiji','guiji.invest_id = invest.order_num and guiji.huobi = invest.huobi','left')
   	 	->field('invest.*,user.email,user.tel,user.id as userid,gluser.user_name,xfje,txje,guiji.status as gj_id,guiji.detail as gj_data')
   	 	->where($where)->order('id desc')->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	
   	 	$domain = 'https://tronscan.org';
   	 	
   	 	foreach($list as $k=>$v){
   	 	    
   	 	  //  $list[$k]['id'] = '序号ID:'.$v['id'];
   	 	  //  $list[$k]['order_num'] .= '<br>'.$v['order_num'];
   	 	    if($v['hash'])
   	 	    {
   	 	      //  $list[$k]['order_num'] .= '<br><a class="dizhi" href="'.$domain.'/#/transaction/'.$v['hash'].'" target="_blank">查看哈希</a>';
   	 	          $list[$k]['order_num'] .= '<br><a class="dizhi" href="javascript:void(0);" target="_blank">查看哈希</a>';
   	 	    }
   	 	    
			$list[$k]['statusVal'] = $v['status'];
			if($v['status'] ==1){
				$list[$k]['status'] = '待审核';   	 		
			}elseif($v['status'] ==2){
				$list[$k]['status'] = fontcolor('已通过','green');   	 		
			}elseif($v['status'] ==3){
				$list[$k]['status'] = fontcolor('不通过','red');  	 		
			}
			
			
			
			$list[$k]['to_balance'] = $v['to_balance']== 1?'基础账户':'理财账户';
			$list[$k]['huobi'] .='</br>'.$v['money'].'</br>'.$v['zuihou_value'];
			if(empty($v['email'])){
				$v['email'] = $v['tel'];
			}
			
			$list[$k]['user_id'] = '用户ID:'.$v['user_id'];
			$list[$k]['user_id'] .='</br>'.$v['email'].'</br>累计充值：'.round($v['xfje'],2).'</br>累计提现：'.round($v['txje'],2);
			
			
			$list[$k]['status'] .='</br>审核人：'.$v['user_name'].'</br>审核时间：'.$v['shenhe_time'];
			
			
			
			
			if($v['gj_id'])
			{
			    $gj_data = $v['gj_data'];
			    $ndata = json_decode($gj_data);
			    
			    
			    $list[$k]['guiji'] = fontcolor('归集成功','red'); 
			 //   $list[$k]['guiji'] .= '<br><a class="dizhi" href="'.$domain.'/#/transaction/'.$ndata->txID.'" target="_blank">查看哈希</a>';
			     $list[$k]['guiji'] .= '<br><a class="dizhi" href="javascript:void(0);" target="_blank">查看哈希</a>';
			}elseif(!empty($v['from_address']))
			{
			    $list[$k]['guiji'] = "<a class='layui-btn layui-btn-mini layui-btn-warm chongxinguiji' data_id=".$v['user_id']." >重新归集</a>";
			}
			
			$list[$k]['to_address'] ='接收:<a class="dizhi" href="javascript:void(0);" target="_blank">'.$v['to_address'].'</a>';
			
			if(!empty($v['from_address']))
			{
			    $add = $v['from_address'];
			    $list[$k]['to_address'] .='<br><br>发送:<a class="dizhi" href="javascript:void(0);" target="_blank">'.$v['from_address'].'</a>';
			
			$list[$k]['to_address'] .='<br><br><div data_id="'.$v['from_address'].'">TRX:<span id="trx'.$add.'" style="font-weight:bold;color: red;" >0</span>TRX   
                    USDT:<span id="usdt'.$add.'" style="font-weight:bold;color: red;">0</span>U  <a class="layui-btn layui-btn-mini layui-btn-danger chaxun">查询</a></div>'; //$v['from_address']
			}
			
			
      
        }
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    
    
    function chaxun()
    {
        $postdata =  $this->request->post();
    	$add = isset($postdata['data_id'])?$postdata['data_id']:'';
    	if($add)
    	{
    	    
    	 $domain = 'https://apilist.tronscanapi.com';

		$urltrx = "$domain/api/account/tokens?address=$add&start=0&limit=20&token=trx&hidden=0&show=0&sortType=0";
		$urlusdt = "$domain/api/account/tokens?address=$add&start=0&limit=20&token=USDT&hidden=0&show=0&sortType=0";

        $re_trx = http_get($urltrx);
    	$re_usdt = http_get($urlusdt);
    	
    	$re_usdt = json_decode($re_usdt,true);

    	$re_trx = json_decode($re_trx,true);
		$trxyue = isset($re_trx['data'][0]['amount'])?$re_trx['data'][0]['amount']:0;
		$usdtyue = isset($re_usdt['data'][0]['quantity'])?$re_usdt['data'][0]['quantity']:0;
    	    

    	    htajaxReturn(1,'成功',['usdtyue'=>$usdtyue,'trxyue'=>$trxyue]);
    	}else
    	{
    	    htajaxReturn(0,'失败，格式错误','');
    	}
    }
    
    
    public function add(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$addData =[];
	    	foreach($this->addCol as $val){
	    		$addData[$val['col']] =$postdata[$val['col']];
	    		if(strpos($val['require'],'required') !== false){
	    			if(empty($postdata[$val['col']])){
						htajaxReturn(0,$val['chinaname'].'不能为空');
	    			}
	    		}
	    	}
	    	$addData['add_time'] = date('Y-m-d H:i:s');
	    	Db::name($this->table)->insert($addData);
			htajaxReturn(1,'新增成功');
     	}else{
     		$this->assign('addhtml', $this->getAddHtml($this->addCol));
     		$this->assign('submitAction', url(request()->controller().'/add'));
     		return view();
     	}
    }
    public function  chongxinguiji(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$invest_order = Db::name('invest_order')->where(['id'=>$data_id])->find();
    	if(empty($invest_order)||$invest_order['status']!=2){
			htajaxReturn(1,'此条充值不成功，不需要归集');
    	}
		xvyaoguiji($invest_order['to_address'],$invest_order['huobi'],0,$invest_order['order_num']);
		htajaxReturn(1,'成功');
    }
     public function hulve(){
         
     	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	Db::name('draw_order')->where(['id'=>$data_id])->update(['chuli'=>1]);
    
		htajaxReturn(1,'操作成功');
    }
     public function chongzhuan(){
    	$list = Db::name('draw_order')->where(['status'=>1,'zhuanzhang_type'=>['in',[1,2]]])->select();
    	if(!empty($list)){
   	 		foreach($list as $v){
   	 			if($v['zhuanzhang_type'] == 1){
   	 				autotixian($v['id']);
   	 			}else{
   	 				 Db::name('draw_order')->where(['id'=>$v['id']])->update(['shenhe_user_id'=>0]);
   	 			}
   	 		}
    	}
		htajaxReturn(1,'重转'.count($list).'条提现任务添加完成，请等候系统自动执行');
    }
     public function change(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	$modelDetail = Db::name($this->table)->find($data_id);
	    	if(empty($modelDetail)){
				htajaxReturn(0,'非法参数');
	    	}
	    	$changeData =[];
	    	foreach($this->changeCol as $val){
	    		$changeData[$val['col']] =$postdata[$val['col']];
	    		if(strpos($val['require'],'required') !== false){
	    			if(empty($postdata[$val['col']])&&$postdata[$val['col']]!=0&&$postdata[$val['col']]!='0'){
						htajaxReturn(0,$val['chinaname'].'不能为空');
	    			}
	    		}
	    	}
	    	unset($changeData['id']);
	    	Db::name($this->table)->where(['id'=>$data_id])->update($changeData);
			htajaxReturn(1,'修改成功');
     	}else{
	    	$getdata =  $this->request->get();
	    	$data_id = isset($getdata['data_id'])?$getdata['data_id']:'';
	    	$modelDetail = Db::name($this->table)->find($data_id);
	    	if(empty($modelDetail)){
				htajaxReturn(0,'非法参数');
	    	}
	    	$this->assign('changeHtml', $this->getChangeHtml($this->changeCol,$modelDetail));
	    	$this->assign('submitAction', url(request()->controller().'/change'));
	    	$this->assign('data_id',$data_id);
	    	return view();
     	}
    }
      public function status(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$status = isset($postdata['status'])?$postdata['status']:'';
		$modelDetail = Db::name($this->table)->find($data_id);
    	if(empty($modelDetail)){
			htajaxReturn(0,'非法参数');
    	}
    	$newVal = $modelDetail['status'] ==0?1:0;
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->update(['status'=>$newVal]);
		htajaxReturn(1,'操作成功');
    }
//   public function tongyi(){
//   	if(request()->isAjax()){//ajax
//			$postdata =  $this->request->post();
//	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
//	    	$tongyi = isset($postdata['tongyi'])?$postdata['tongyi']:3;
//	    	$anquan_pwd= isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
//	 		$invest_order = Db::name('invest_order')->where(['id'=>$data_id,'status'=>1])->find();
//	    	if(empty($invest_order)){
//				htajaxReturn(0,'非法参数');
//	 		}
//	 		if($tongyi == 2){
//	 			if(md5(md5($anquan_pwd))!=$this->csUserInfo['anquan_pwd']){
//	 				htajaxReturn(0,'安全密码错误');
//	 			}
//	 		}
//	      	//防止多次连续操作
//	     	$lock_key = getRedisXM('tongyichongzhi'.$data_id);
//	    	$is_lock = redisCache()->setnx($lock_key, 2); 
//	    	if($is_lock){
//				redisCache()->expire($lock_key, 1);
//			}else{
//				// 防止死锁
//				if(redisCache()->ttl($lock_key) == -1){
//					redisCache()->expire($lock_key, 2);
//				}
//				htajaxReturn(0,'服务器缓缓');
//			}
//			if($tongyi == 2){//同意加金额
//				if($invest_order['to_balance'] == 1){//基础账户
//	      			basicmoneyChange('bh_充值',$invest_order['zuihou_value'],$invest_order['user_id'],[]);
//				}elseif($invest_order['to_balance'] == 2){//理财账户
//	      			licaimoneyChange('bh_充值',$invest_order['zuihou_value'],$invest_order['user_id'],[]);
//				}
//				$caozuotemp = ['pk_id'=>$data_id,'type'=>'chongzhiticheng','add_time'=>date('Y-m-d H:i:s'),
//				'op_time'=>date('Y-m-d H:i:s'),'extra'=>json_encode([])];
//				DB::name('caozuo')->insert($caozuotemp);
//				Db::name('user')->where(['id'=>$invest_order['user_id']])->setInc('xfje',$invest_order['zuihou_value']);
//				$userInfo = Db::name('user')->where(['id'=>$invest_order['user_id']])->field('vip_level,xfje')->find();
//				if(!empty($userInfo)){
//					$newVipLevel = Db::name('vip_set')->where(['min'=>['elt',$userInfo['xfje']],'max'=>['egt',$userInfo['xfje']]])->find();
//					if(!empty($newVipLevel)){
//						if($newVipLevel['level']!=$newVipLevel){
//							Db::name('user')->where(['id'=>$invest_order['user_id']])->update(['vip_level'=>$newVipLevel['level']]);
//						}
//					}
//				}
//			}
//			Db::name('invest_order')->where(['id'=>$data_id])->update(['status'=>$tongyi,'shenhe_user_id'=>$this->csUserInfo['id'],
//			'shenhe_time'=>date('Y-m-d H:i:s')]);
//			redisCache()->del($lock_key);
//			htajaxReturn(1,'操作成功');
//   	}else{
//			$postdata =  $this->request->get();
//	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
//	 		$invest_order = Db::name('invest_order')->where(['id'=>$data_id,'status'=>1])->find();
//	 		if(empty($invest_order)){
//	 			echo '错误';exit();
//	 		}
//	 		$money = $invest_order['zuihou_value'].'USDT';
//	 		if($invest_order['huobi'] == 'TRX'){
//	 			$money = $invest_order['money'].'TRX'.'='.$invest_order['zuihou_value'].'USDT';;
//	 		}
//	    	$this->assign('order_num',$invest_order['order_num']);
//	    	$this->assign('money',$money);
//	    	$this->assign('data_id',$data_id);
//	    	return view();
//   	}
//  }
     public function draw(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'id','chinaname'=>'id/订单号','style'=>''],
//  		['col'=>'order_num','chinaname'=>'订单号','style'=>''],
    		['col'=>'user_id','chinaname'=>'用户ID<br>email</br>手机号','style'=>''],
    		['col'=>'to_address','chinaname'=>'提现钱包','style'=>''],
    		['col'=>'xiangqing','chinaname'=>'金额详细','style'=>''],
    		['col'=>'shouxvfei','chinaname'=>'手续费率</br>手续费</br>等值usdt','style'=>''],
    		['col'=>'shijidaozhang','chinaname'=>'实际到账','style'=>''],
    		['col'=>'add_time','chinaname'=>'提现时间','style'=>''],
    		['col'=>'chulival','chinaname'=>'处理','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
    		['col'=>'zhuanzhang_type','chinaname'=>'转账类型','style'=>''],
    		['col'=>'caozuo','chinaname'=>'操作','style'=>'']
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
// 				'tongguo'=> 	['chinaname'=>'通过'],
// 				'butongguo'=>['chinaname'=>'不通过']
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'order_num'=>['chinaname'=>'订单号','ca'=>'like','type'=>'text','style'=>''],
    		'email'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
			'chuli'=>['chinaname'=>'处理','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'全部'],['id'=>0,'text'=>'未处理'],['id'=>1,'text'=>'已忽略']],'style'=>'']
    	];
    	$where = $this->getWhere($searchCol);
    	if(isset($where['chuli'])&&$where['chuli'] == -1){
    	    unset($where['chuli']);
    	}
    	$where['draw_order.status']= 1;
    	$where['draw_order.status2']= 0;
    	//***********************************************整理搜索字段
   	 	$list = Db::name('draw_order')
   	 	->alias('draw_order')
   	 	->join('user user','user.id = draw_order.user_id','left')
   	 	->field('draw_order.*,user.email,user.tel,user.id as userid')
   	 	->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 	    
   	 	  //  $list[$k]['id'] = '序号ID:'.$v['id'];
   	 	    $list[$k]['id'] = '<br>'.$v['order_num'];
   	 	    
			$list[$k]['statusVal'] = $v['status'];
			$data_id = $v['id'];
			if($v['status'] ==1){
				$list[$k]['status'] = '待审核';   	 		
			}elseif($v['status'] ==2){
				$list[$k]['status'] = fontcolor('已通过','green');   	 		
			}elseif($v['status'] ==3){
				$list[$k]['status'] = fontcolor('不通过','red');  	 		
			}
			$domain = 'https://tronscan.org';
			
			if($v['chuli'] ==0){
				$list[$k]['chulival'] = '未处理'; 
					$list[$k]['chulival'].= "	<a style='margin-left:10px' data_id='{$v['id']}'    class='layui-btn layui-btn-mini layui-btn-warm hulve'>忽略</a>";
			}elseif($v['chuli'] ==1){
				$list[$k]['chulival'] = fontcolor('已忽略','green'); 	 	 		
			}
			$list[$k]['to_address'] ='<a class="dizhi" href="javascript:void(0);" target="_blank">'.$v['to_address'].'</a>';
			
			$list[$k]['xiangqing'] ='提现货币：'.$v['huobi'].'</br>金额：'.$v['money'].'</br>手续费：'.$v['shouxvfei'].'</br>实际到账：'.$v['daozhang'];
			$list[$k]['shouxvfei'] =($v['shouxvfei_rate']*100).'%</br>'.$v['shouxvfei'].$v['huobi'].'</br>'.$v['zuihou_shouxvfei'];
			
			$list[$k]['user_id'] = '用户ID:'.$v['user_id'];
			$list[$k]['user_id'] .='</br>'.$v['email'].'</br>'.$v['tel'];
			$list[$k]['shijidaozhang'] = fontcolor('<b>'.$v['daozhang'].'</b>','red').fontcolor('<b>'.$v['huobi'].'</b>','black');
			if($v['zhuanzhang_type'] == 1){//自动提现
				$list[$k]['zhuanzhang_type'] = '小额自动转';
				$list[$k]['caozuo'] = '';
			}elseif($v['zhuanzhang_type'] == 2){
				if($v['shenhe_user_id']<=0){
					$list[$k]['caozuo'] = '<div data_id="'.$data_id.'"><a class="layui-btn layui-btn-mini layui-btn-danger tongguo">通过</a><a class="layui-btn layui-btn-mini layui-btn-warm butongguo">不通过</a></div>';
					$list[$k]['zhuanzhang_type'] = '审核后自动转';
				}else{
					
					$list[$k]['zhuanzhang_type'] = '已审核，等待系统转账';
				
				}
			}else{
				$list[$k]['caozuo'] = '<div data_id="'.$data_id.'"><a class="layui-btn layui-btn-mini layui-btn-danger tongguo">通过</a><a class="layui-btn layui-btn-mini layui-btn-warm butongguo">不通过</a></div>';
				$list[$k]['zhuanzhang_type'] = '必须手动转账';
			}
        }
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	if(!isset($where['chuli'])){
    	    $where['chuli'] = -1;
    	}
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
      public function draw1(){
    	//*********************************************整理展示字段
    	$showCol = [
    // 		['col'=>'id','chinaname'=>'id','style'=>''],
 		['col'=>'order_num','chinaname'=>'订单号','style'=>''],
    		['col'=>'user_id','chinaname'=>'用户ID<br>email</br>手机号','style'=>''],
    		['col'=>'to_address','chinaname'=>'提现钱包','style'=>''],
    		['col'=>'xiangqing','chinaname'=>'详细','style'=>''],
    		['col'=>'add_time','chinaname'=>'提现时间','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>'']
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'order_num'=>['chinaname'=>'订单号','ca'=>'like','type'=>'text','style'=>''],
    		'email'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
    		'status'=>['chinaname'=>'状态','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'2','text'=>'成功'],['id'=>'3','text'=>'失败']],'style'=>'']
    	];
    	$where = $this->getWhere($searchCol);
	  	if(isset($where['status'])&&!empty($where['status'])){
    		$where['draw_order.status']= $where['status'];
    	}else{
    		$where['draw_order.status']= ['in',[2,3]];
    	}
		$where['draw_order.status2']= 0;
    	unset($where['status']);
    	//***********************************************整理搜索字段
   	 	$list = Db::name('draw_order')
   	 	->alias('draw_order')
   	 	->join('user user','user.id = draw_order.user_id','left')
   	 	->join('gluser gluser','gluser.id = draw_order.shenhe_user_id','left')
   	 	->field('draw_order.*,user.email,user.tel,user.id as userid,gluser.user_name,user.xfje,user.txje')
   	 	->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	
   	 	$domain = 'https://tronscan.org';
   	 	
   	 	foreach($list as $k=>$v){
   	 	    
   	 	  //   $list[$k]['id'] = '序号ID:'.$v['id'];
   	 	  //  $list[$k]['id'] .= '<br>'.$v['order_num'];
   	 	    if($v['hash'])
   	 	    {
   	 	        $list[$k]['order_num'] .= '<br><a class="dizhi" href="javascript:void(0);" target="_blank">查看哈希</a>';
   	 	    }
   	 	    
			$list[$k]['statusVal'] = $v['status'];
			if($v['status'] ==1){
				$list[$k]['status'] = '待审核';   	 		
			}elseif($v['status'] ==2){
				$list[$k]['status'] = fontcolor('已通过  ','green');   
				
				
			if($v['zhuanzhang_type'] == 2)
			{
			    $list[$k]['status'] .= fontcolor("审核通过自动出",'red'); 
			}elseif($v['zhuanzhang_type'] == 3)
			{
			    $list[$k]['status'] .=fontcolor("需要手动出款",'red'); 
			}
				
			}elseif($v['status'] ==3){
				$list[$k]['status'] = fontcolor('不通过','red');  	 		
			}
			$list[$k]['xiangqing'] ='提现货币：'.$v['huobi'].'</br>金额：'.$v['money'].'</br>手续费：'.$v['shouxvfei'].'</br>实际到账：'.$v['daozhang'];
			$domain = 'https://tronscan.org';
			if(MOSHI== 'ceshi'){
				$domain = 'https://shasta.tronscan.org';
			}
			$list[$k]['to_address'] ='<a class="dizhi" href="javascript:void(0);" target="_blank">'.$v['to_address'].'</a>';
			if(empty($v['email'])){
				$v['email'] = $v['tel'];
			}
			$list[$k]['user_id'] .='</br>'.$v['email'].'</br>累计充值：'.round($v['xfje'],2).'</br>累计提现：'.round($v['txje'],2);
			if($v['zhuanzhang_type'] == 1){
				$v['user_name'] = '自动转账';
			}
			$list[$k]['status'] .='</br>审核人：'.$v['user_name'].'</br>审核时间：'.$v['shenhe_time'];
			
			
			$add = $v['to_address'];
// 			$list[$k]['to_address'] .='<br><br><div data_id="'.$add.'">TRX:<span id="trx'.$add.'" style="font-weight:bold;color: red;" >0</span>TRX   
//                     USDT:<span id="usdt'.$add.'" style="font-weight:bold;color: red;">0</span>U  <a class="layui-btn layui-btn-mini layui-btn-danger chaxun">查询</a></div>'; 
			
        }
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
     public function tongyidraw(){
     	if(request()->isAjax()){//ajax
			$postdata =  $this->request->post();
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	$tongyi = isset($postdata['tongyi'])?$postdata['tongyi']:3;
	    	$anquan_pwd= isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	 		$draw_order = Db::name('draw_order')->where(['id'=>$data_id,'status'=>1,'zhuanzhang_type'=>['in',[2,3]]])->find();
	    	if(empty($draw_order)){
				htajaxReturn(0,'非法参数');
	 		}
	      	//防止多次连续操作
	     	$lock_key = getRedisXM('tongyitixian'.$data_id);
	    	$is_lock = redisCache()->setnx($lock_key, 2); 
	    	if($is_lock){
				redisCache()->expire($lock_key, 1);
			}else{
				// 防止死锁
				if(redisCache()->ttl($lock_key) == -1){
					redisCache()->expire($lock_key, 2);
				}
				htajaxReturn(0,'服务器缓缓');
			}
			if($tongyi == 2){//同意加金额
				if(!$this->yanzhenganquanpwd($anquan_pwd)){
					htajaxReturn(0,'安全密码错误');
		    	}
				Db::name('user')->where(['id'=>$draw_order['user_id']])->setInc('txje',$draw_order['zuihou_value']);
				if($draw_order['zhuanzhang_type'] == 2){//自动提现
					autotixian($draw_order['id']);
				}else{//需要手动的则更新
				}
				
			}
			if($draw_order['zhuanzhang_type'] == 2&$tongyi == 2){//因为定时任务要检测是不是1.所以只有需要手动转账才置为2
				$tongyi = 1;
			}
			Db::name('draw_order')->where(['id'=>$data_id])->update(['status'=>$tongyi,'chuli'=>1,'shenhe_user_id'=>$this->csUserInfo['id'],
			'shenhe_time'=>date('Y-m-d H:i:s')]);
			redisCache()->del($lock_key);
			htajaxReturn(1,'操作成功');
     	}else{
			$postdata =  $this->request->get();
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	 		$draw_order = Db::name('draw_order')->where(['id'=>$data_id,'status'=>1,'zhuanzhang_type'=>['in',[2,3]]])->find();
	 		if(empty($draw_order)){
	 			echo '错误';exit();
	 		}
	 		$money = $draw_order['zuihou_daozhang'].'USDT';
	 		if($draw_order['huobi'] == 'TRX'){
	 			$money = $draw_order['money'].'TRX'.'='.$draw_order['zuihou_daozhang'].'USDT';;
	 		}
	 		$text = '*异常';
	 		if($draw_order['zhuanzhang_type'] ==2){
	 			$text = fontcolor('*请注意，同意后会<b>自动转账</b>','red');
	 		}elseif($draw_order['zhuanzhang_type'] ==3){
	 			$text = fontcolor('*同意后须<b>手动转账</b>','red');
	 		}
	    	$this->assign('text',$text);
	    	$this->assign('order_num',$draw_order['order_num']);
	    	$this->assign('money',$money);
	    	$this->assign('data_id',$data_id);
	    	return view();
     	}
    }
}