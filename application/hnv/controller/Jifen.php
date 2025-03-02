<?php
namespace app\hnv\controller;
use think\Controller;
use think\Request;
use think\Db;
use GuzzleHttp\Client;
class Jifen extends Base {
	private $table = 'good';
	private $order = 'add_time desc';
	private $addCol = [];
	private $changeCol = [];
    public function main(){
    	//*********************************************整理展示字段
    	return view();
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
	    	$this->yanzheng($addData);
	    	$addData['add_time'] = date('Y-m-d H:i:s');
	    	Db::name($this->table)->insert($addData);
			htajaxReturn(1,'新增成功');
     	}else{
     		$this->assign('addhtml', $this->getAddHtml($this->addCol));
     		$this->assign('submitAction', url(request()->controller().'/add'));
     		return view();
     	}
    }
     function yanzheng($addData){
//  	if($addData['day']<=0||$addData['day']>30){
//			htajaxReturn(0,'投资天数格式不对了');
//  	}
//  	if($addData['buy_min']<0||$addData['buy_max']<0){
//			htajaxReturn(0,'购买限额不对');
//  	}
//  	if($addData['rate_day']<0||$addData['rate_day']>20){
//			htajaxReturn(0,'每日收益是不是手抖了');
//  	}
//  	if(!in_array($addData['shouyi_type'],[1,2])){
//			htajaxReturn(0,'收益类型不对');
//  	}
//  	if($addData['limit_all_count']<0){
//			htajaxReturn(0,'购买次数限制填写有误');
//  	}
//  	if($addData['limit_vip_level']<0){
//			htajaxReturn(0,'购买会员等级限制填写有误');
//  	}
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
	    	$this->yanzheng($changeData);
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
      public function getdata(){
    	$data = Db::name('sys_jifen')->find(1);
		htajaxReturn(1,'操作成功',$data);
    }
    public function chongzhichaxun(){
			$data = Db::name('sys_jifen')->find(1);
			$chenggong = false;
			$result = $this->getTrxRecord($data['qianbao']);
			$result = json_decode($result,true);
			if(isset($result['data'])&&count($result['data'])>0){
				foreach($result['data'] as $record){
					if(isset($record['ret'][0]['contractRet'])
					&&$record['ret'][0]['contractRet'] =='SUCCESS'
					&&isset($record['raw_data']['contract'][0]['parameter']['value']['amount'])
					&&isset($record['txID'])
					&&!isset($record['raw_data']['contract'][0]['parameter']['value']['asset_name'])//只有不存在这个字段才是TRX
					&&!empty($data)
					){
						$txID = $record['txID'];
						$lasthash_id = $data['hash_id'];
						$last_time = $data['block_time'];
						$block_timestamp = $record['block_timestamp'];
						$julishijian =time()- $block_timestamp/1000;
						$jine = $record['raw_data']['contract'][0]['parameter']['value']['amount'];
						$jine = bcdiv($jine,1000000,6);
						if(
						$lasthash_id !=$txID
						&&$last_time<$block_timestamp
						&&$julishijian<=48*60*60//48个小时前才算
						&&$jine>=1
						){//这样才符合
							Db::name('sys_jifen')->where(['id'=>1])
							->update(['hash_id'=>$txID,'block_time'=>$block_timestamp]);
							$chenggong = true;
							$owner_address = isset($record['raw_data']['contract'][0]['parameter']['value']['owner_address'])?
							$record['raw_data']['contract'][0]['parameter']['value']['owner_address']:'';
							$this->daozhang($owner_address,$jine,'TRX',$data['qianbao']);
//							echo '</br>TRX到账了';
							break;
					    }
					    if($block_timestamp == $last_time||$lasthash_id ==$txID){//匹配到一样的交易，就结束了
//							echo '</br>匹配到一样的交易，就结束了trx'.$block_timestamp.'  '.$txID;
							break;
						}
				}
			}
		}
		//查询usdt,trx不成功才查询
//		echo '</br>开始查询usdt的'.$chenggong.'aa';
		if(!$chenggong){
//			echo '</br>开始查询usdt的';
			$result = $this->getUsdtRecord($data['qianbao']);
			$result = json_decode($result,true);
//							je($result);
			if(isset($result['data'])&&count($result['data'])>0){
				foreach($result['data'] as $record){
					if(
					isset($record['transaction_id'])
					&&isset($record['block_timestamp'])
					&&isset($record['token_info']['symbol'])
					&&$record['token_info']['symbol'] == 'USDT'
					&&$record['type'] == 'Transfer'
					&&$record['value']
					&&!empty($data)
					){
						$transaction_id = $record['transaction_id'];
						$lasthash_id = $data['hash_id'];
						$last_time = $data['block_time'];
						$block_timestamp = $record['block_timestamp'];
						$jine = $record['value'];
						$jine = bcdiv($jine,1000000,6);
						$julishijian =time()- $block_timestamp/1000;
						if(
						$lasthash_id !=$transaction_id
						&&$last_time<$block_timestamp
						&&$julishijian<=48*60*60//48个小时前才算
						&&$jine>0.1
						){
							Db::name('sys_jifen')->where(['id'=>1])
							->update(['hash_id'=>$transaction_id,'block_time'=>$block_timestamp]);
							$chenggong = true;
							$owner_address = isset($record['from'])?
							$record['from']:'';
							$this->daozhang($owner_address,$jine,'USDT',$data['qianbao']);
							break;
						}
						if($block_timestamp == $last_time&&$lasthash_id ==$transaction_id){//匹配到一样的交易，就结束了
//								echo '</br>匹配到一样的交易，就结束了usdt'.$block_timestamp.'  '.$transaction_id;
							break;
						}
					}
					
				}
				
			}
		}
		htajaxReturn(0,'未检测到有新充值');
    }
    function getTrxRecord($qianbao){
		$url = "https://api.trongrid.io/v1/accounts/$qianbao/transactions?only_to=true&limit=8";//主网
		if(MOSHI =='ceshi'){
			$url = "https://api.shasta.trongrid.io/v1/accounts/$qianbao/transactions?only_to=true&limit=8";//测试网
		}
		$resu = $this->http_get($url);
		return  $resu;
	}
	function getUsdtRecord($qianbao){
		$url = "https://api.trongrid.io/v1/accounts/$qianbao/transactions/trc20?only_to=true&limit=8";//主网
		if(MOSHI =='ceshi'){
			$url = "https://api.shasta.trongrid.io/v1/accounts/$qianbao/transactions/trc20?only_to=true&limit=8";//测试网
		}
		$resu = $this->http_get($url);
		return  $resu;
	}
	function http_get($url)
	{
	    $headers[] = "Content-Type: application/json";
	    $headers[] = "TRON_PRO_API_KEY:12e722cf-bd91-4266-9aff-18778bf7a21f";
	    $curl = curl_init(); // 启动一个CURL会话
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_TIMEOUT,8);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $tmpInfo = curl_exec($curl);     
	    //关闭URL请求
	    curl_close($curl);
	    return $tmpInfo;   
	}
	function daozhang($from_address,$jine,$huobi,$to_address){
			//2个小时内才算到账
	    $uri = 'https://api.trongrid.io';
        $api = new \Tron\Api(new Client(['base_uri' => $uri]));
        if($huobi =='TRX'){
	        $config = [
	            'contract_address' => 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs',// USDT TRC20
	            'decimals' => 6,
	        ];
	        $trc20Wallet = new \Tron\TRC20($api, $config);
        	$from_address = $trc20Wallet->tron->hexString2Address($from_address);
        }
    	$lock_key = getRedisXM('sysjifenshangfen');
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
		$baifenbi = Db::name('sys_jifen')->where(['id'=>1])->value('choucheng');
        $zuihou_val = jisuanValue($jine,$huobi);//先换成主货币，再除以百分比
        if($baifenbi!=0){
      	   $zuihou_val = bcdiv($zuihou_val,$baifenbi,6);
        }
        
        
		Db::name('sys_jifen_invest')->insert(['money'=>$jine,'huobi'=>$huobi,'zuihou_val'=>$zuihou_val,
		'add_time'=>date('Y-m-d H:i:s'),'from_address'=>$from_address,'to_address'=>$to_address]);
		Db::name('sys_jifen')->where(['id'=>1])->setInc('leiji_chongzhi',$zuihou_val);
		sysjifenChange('充值上分',$zuihou_val);
		redisCache()->del($lock_key);
		htajaxReturn(1,'充值'.$jine.$huobi.'，上分'.$zuihou_val);
	}
	    public function liushui(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'type','chinaname'=>'变化类型','style'=>''],
    		['col'=>'change','chinaname'=>'变化余额','style'=>''],
    		['col'=>'aftre','chinaname'=>'剩余余额','style'=>''],
    		['col'=>'add_time','chinaname'=>'操作时间','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
//  		'id'=>['chinaname'=>'用户ID','ca'=>'eq','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	//***********************************************整理搜索字段
   	 	$list = Db::name('sysjifen_water')->where($where)->order('add_time desc')->paginate(10);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page,10);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 	}
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
       public function invest(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'money','chinaname'=>'金额','style'=>''],
    		['col'=>'huobi','chinaname'=>'类型','style'=>''],
    		['col'=>'zuihou_val','chinaname'=>'换算系统积分','style'=>''],
    		['col'=>'from_address','chinaname'=>'转账钱包','style'=>''],
    		['col'=>'to_address','chinaname'=>'充值钱包','style'=>''],
    		['col'=>'add_time','chinaname'=>'操作时间','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
//  		'id'=>['chinaname'=>'用户ID','ca'=>'eq','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	//***********************************************整理搜索字段
   	 	$list = Db::name('sys_jifen_invest')->where($where)->order('add_time desc')->paginate(10);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page,10);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$domain = 'https://tronscan.org';
			if(MOSHI== 'ceshi'){
				$domain = 'https://shasta.tronscan.org';
			}
			$list[$k]['to_address'] ='<a style="color:blue;" class="dizhi" href="'.$domain.'/#/address/'.$v['to_address'].'" target="_blank">'.$v['to_address'].'</a>';
      
   	 	}
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    
    
    
    
}