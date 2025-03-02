<?php
namespace app\hnv\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Lang;
class User extends Base {
	private $table = 'user';
	private $order = 'id desc';
	private $addCol = [];
	private $changeCol = [];
    public function main(){

    	//*********************************************整理展示字段
    	$yuyan = ['cht'=>'繁体','ar'=>'阿拉伯语','de'=>'德语','de'=>'德语','en'=>'英语','es'=>'西班牙语','fr'=>'法语','id'=>'印尼语',
    	'it'=>'意大利语','jp'=>'日本语','kor'=>'韩语','pt'=>'葡萄牙语','ru'=>'俄语','tr'=>'土耳其语'];
    	$showCol = [
    		['col'=>'id','chinaname'=>'id','style'=>''],
    		['col'=>'email','chinaname'=>'email/手机','style'=>''],
    // 		['col'=>'lang','chinaname'=>'语言</br>国家','style'=>''],
    		['col'=>'yue','chinaname'=>'基础/佣金余额</br>手工添加余额','style'=>''],
    // 		['col'=>'zyue','chinaname'=>'后台添加余额','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
    		
    		['col'=>'vip_level','chinaname'=>'VIP等级','style'=>''],
    		['col'=>'kabuzhou','chinaname'=>'卡提现步骤','style'=>''],
    		['col'=>'caiwu','chinaname'=>'财务记录','style'=>''],
    		['col'=>'caozuo','chinaname'=>'操作','style'=>''],
    // 		['col'=>'add_time','chinaname'=>'注册时间</br>注册IP</br>最后登录','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    	   
    		'email'=>['chinaname'=>'账号','ca'=>'like','type'=>'text','style'=>''],
    		'invite_code'=>['chinaname'=>'邀请码','ca'=>'eq','type'=>'text','style'=>''],
    		'chongzhi'=>['chinaname'=>'充值','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'1','text'=>'有充值'],['id'=>'2','text'=>'无充值']],'style'=>''],
    		
    		'xingbiao'=>['chinaname'=>'星标','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'0','text'=>'无星标'],['id'=>'1','text'=>'星标']],'style'=>''],
    		'wakuang'=>['chinaname'=>'挖矿收益','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'0','text'=>'有限制'],['id'=>'1','text'=>'无限制']],'style'=>''],
    		'renwu'=>['chinaname'=>'限制任务','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'0','text'=>'有限制'],['id'=>'-1','text'=>'无限制']],'style'=>''],
    		
    		'beizhu'=>['chinaname'=>'备注','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'1','text'=>'有备注'],['id'=>'0','text'=>'无备注']],'style'=>''],
    		
    		'vip_level'=>['chinaname'=>'VIP等级','ca'=>'eq','type'=>'select','selectData'=>
    		[['id'=>'','text'=>'请选择'],['id'=>'0','text'=>'VIP0'],['id'=>'1','text'=>'VIP1'],['id'=>'2','text'=>'VIP2'],['id'=>'3','text'=>'VIP3'],['id'=>'4','text'=>'VIP4'],['id'=>'5','text'=>'VIP5'],['id'=>'6','text'=>'VIP6']]
    		,'style'=>'']
    	];
    	$where = $this->getWhere($searchCol);
    	//有无充值
    	$biaozhi = [];
    	$orwhere = 'id >0';
    	if(isset($where['chongzhi'])){
    		if($where['chongzhi'] == 1){
    			$where['xfje'] = ['gt',0];
    		}else{
    			$where['xfje'] = 0;
    		}
    		$biaozhi['chongzhi'] = 1;
    		unset($where['chongzhi']);
    	}
    	
    	if(isset($where['renwu'])){
    		if($where['renwu'] == -1){//限制
    		    $where['tmp_task'] = -1; //没限制
    			
    		}else{
    			$where['tmp_task'] = ['gt',-1];
    		}
    // 		$biaozhi['renwu'] = 1;
    		unset($where['renwu']);
    	}
    	if(isset($where['wakuang'])){ 
    		if($where['wakuang'] == 0){//限制
    		    $where['can_wakuang'] = 0; //没限制
    			
    		}else{
    			$where['can_wakuang'] = 1;
    		}
    // 		$biaozhi['renwu'] = 1;
    		unset($where['wakuang']);
    	}
    	if(isset($where['xingbiao'])){
    		if($where['xingbiao'] == 0){//限制
    		    $where['xingbiao'] = 0; //没限制
    			
    		}else{
    			$where['xingbiao'] = 1;
    		}
    	}
    	
    	if(isset($where['beizhu'])){
    		if($where['beizhu'] == 0){//限制
    		    $where['beizhu'] = null; //没限制
    			
    		}else{
    			$where['beizhu'] = ['<>',''];
    		}
    	}
    	
    	if(isset($_GET['email'])){
    		$orwhere .= ' AND(email LIKE "%'.$_GET['email'].'%" OR tel like "%'.$_GET['email'].'%")';
    		$biaozhi['email'] = $_GET['email'];
    		unset($where['email']);
    	}
    	
    	
    	//在线人数
        $fiveMin = date('Y-m-d H:i:s',strtotime('-5 min'));
        $zaixian = Db::name('user_action_log')->where(['add_time'=>['egt',$fiveMin],'user_id'=>['gt',0]])
                    ->field('user_id')->group('user_id')->select();
        $zaixian_user = array();
        foreach ($zaixian as $v)
        {
            $zaixian_user[]=$v['user_id'];
        }
        
        //在线人数
        // $fiveMin = date('Y-m-d H:i:s',strtotime('-5 min'));
        $zaixianrenshu = count($zaixian_user);

    	//***********************************************整理搜索字段
   	 	$list = Db::name($this->table)->alias('user')->where($where)->where($orwhere)
   	 	      //  ->join('user_action_log log','log.user_id = user.id','left')->where(['log.add_time'=>['egt',$fiveMin]])->field('user.*,log.add_time as ntime')
   	 	        ->order($this->order)->paginate(15);//查询数据
   	
   	 	        
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	$userIds = array_column($list,'id');
   	 	$userIds[] = -1;
   	 	$today = date('Y-m-d');
   	 	$chongzhiData = Db::name('invest_order')->where(['status'=>2,'user_id'=>['in',$userIds],'add_time'=>['LIKE',"%$today%"]])->field('SUM(money) as money,huobi,user_id')->group('user_id,huobi')->select();
   	 	$tixianData = Db::name('draw_order')->where(['status'=>2,'status2'=>0,'user_id'=>['in',$userIds],'add_time'=>['LIKE',"%$today%"]])->field('SUM(money) as money,huobi,user_id')->group('user_id,huobi')->select();
   	 	$jinrichongzhi = [];
   	 	$jinritixian = [];
   	 	foreach($chongzhiData as $val){
   	 		$jinrichongzhi[$val['user_id'].'-'.$val['huobi']] = round($val['money'],2);
   	 	}
   	 	foreach($tixianData as $val){
   	 		$jinritixian[$val['user_id'].'-'.$val['huobi']] = round($val['money'],2);
   	 	}
   	 	
   	 	foreach($list as $k=>$v){
   	 		$statBtn = $v['status'] ==1?'停用':'启用'; 
   	 		$wakuangBtn = $v['can_wakuang'] ==1?'停止挖矿收益':'有挖矿收益(已禁止)'; 
   	 		$wkbtncolor = 'layui-btn-warm';
   	 		if($v['can_wakuang'] ==0){
   	 			$wkbtncolor = 'layui-btn-danger';
   	 		}
   	 		
   	 		$xingbiaoBtn = $v['xingbiao'] ==1?'取消星标':'设置星标'; 
   	 		$xbbtncolor = '';
   	 		if($v['xingbiao'] ==1){
   	 			$xbbtncolor = 'layui-btn-danger';
   	 		}
   	 		
   	 		$uid = $v['id'];
   	 		
   	 		
   	//  		$list[$k]['id'] = "<label><input class='list-check-box' value='$uid' type='checkbox'></label>";
   	//  		$list[$k]['id'] .= '<br>'.$v['id'];
   	 		
			$list[$k]['statusVal'] = $v['status'];
			
			
   	 		$list[$k]['status'] = $v['status'] ==1?fontcolor('正常','green'):fontcolor('封禁中','red');
   	 		$list[$k]['status'] .= '<br>';
   	 		$list[$k]['status'] .= $v['can_wakuang'] ==1?fontcolor('挖矿中','blue'):fontcolor('停止挖矿','purple');
   	 		
   	 		$list[$k]['yue'] = '<div uid="'.$uid.'">'.$v['basic_balance']."<a style='margin-left:10px' ls='jichu'  class='layui-btn layui-btn-mini layui-btn-warm liushui'>基础流水</a><a style='margin-left:10px'  ls='jichu'   class='layui-btn layui-btn-mini layui-btn-warm zye'>增加余额</a>";
   	 		$list[$k]['yue'].='</br>'.$v['commission_balance']."<a style='margin-left:10px'  ls='yj'  class='layui-btn layui-btn-mini layui-btn-warm liushui'>佣金流水</a><a style='margin-left:10px'  ls='yj'  class='layui-btn layui-btn-mini layui-btn-warm zye'>增加余额</a>";
   	 		$list[$k]['yue'].='</br>'.$v['licai_balance']."<a style='margin-left:10px'  ls='licai'  class='layui-btn layui-btn-mini layui-btn-warm liushui'>理财流水</a><a style='margin-left:10px'  ls='licai'  class='layui-btn layui-btn-mini layui-btn-warm zye'>增加余额</a></div>"; 	
   	 		$table = "<table cellpadding='10px' cellspacing='0px' class='yuetable'>
						<tr>
							<td style='width:120px'>".$v['basic_balance']."</td>
							<td uid='$uid'>
								<a style='margin-left:10px' ls='jichu'  class='layui-btn layui-btn-mini layui-btn-warm liushui'>基础流水</a>
								<a style='margin-left:10px'  ls='jichu'   class='layui-btn layui-btn-mini layui-btn-warm zye'>增加余额</a>
							</td>
						</tr>
						<tr>
							<td>".$v['commission_balance']."</td>
							<td uid='$uid'>
								<a style='margin-left:10px'  ls='yj'  class='layui-btn layui-btn-mini layui-btn-warm liushui'>佣金流水</a>
								<a style='margin-left:10px'  ls='yj'  class='layui-btn layui-btn-mini layui-btn-warm zye'>增加余额</a>
							</td>
						</tr>
						
						<tr>
							<td>".$v['zyue']."</td>
							<td uid='$uid'>
								<a style='margin-left:10px'  ls='zengyue'  class='layui-btn layui-btn-mini layui-btn-warm liushui'>手工添加流水</a>
								
							</td>
						</tr>
						
						
						
					</table>";
   	 		$list[$k]['yue'] =$table ;
   	 		$list[$k]['yue'] .= '注册IP：'.$v['zhuce_ip'];
   	 		
   	//  		$list[$k]['zyue'] .= "<font uid='$uid' ><a style='margin-left:10px'  ls='zengyue'   class='layui-btn layui-btn-mini layui-btn-warm liushui'>流水</a></font>";
   	 		
   	 		$jinzhitixian = '禁止提现（开放中）';
   	 		$btncolor = 'layui-btn-warm';
   	 		if($v['can_draw'] ==0){
   	 			$jinzhitixian = '放开提现（已禁止）';
   	 			$btncolor = 'layui-btn-danger';
   	 		}
   	 		
   	 		/*
   	 		
   	 		<tr>
							<td>".$v['licai_balance']."</td>
							<td uid='$uid'>
								<a style='margin-left:10px'  ls='licai'  class='layui-btn layui-btn-mini layui-btn-warm liushui'>理财流水</a>
								<a style='margin-left:10px'  ls='licai'  class='layui-btn layui-btn-mini layui-btn-warm zye'>增加余额</a>
							</td>
							
							</tr>
							
							
			*/
   	 		
   	 		
   	 		$jinzhirenwu = '禁止任务';
   	 		$renwu_btncolor = 'layui-btn-warm';
   	 		if($v['tmp_task'] !=-1){
   	 			$jinzhirenwu = '开放任务（已禁止）';
   	 			$renwu_btncolor = 'layui-btn-danger';
   	 		}
   	 		//代理
   	 		$dailit = '设为代理';
   	 		$daaili_btncolor = 'layui-btn-warm';
   	 		if($v['daili'] ==1){
   	 			$dailit = '取消代理（已是代理）';
   	 			$daaili_btncolor = 'layui-btn-danger';
   	 		}
   	 		
   	 		$domain = 'https://tronscan.org';
   	 		$userAddress = Db::name('addressdizhi')->where(['user_id'=>$uid])->value('address');
			$chaxunall ='<a class="layui-btn layui-btn-mini layui-btn-warm" href="'.$domain.'/#/address/'.$userAddress.'" target="_blank">查询U/T</a>';
 			$caozuotable = "<table cellpadding='10px' cellspacing='0px' class='yuetable'>
					<tr>
						<td >
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini layui-btn-warm status'>$statBtn</a>
						</td>
						<td>
							<a data_id='$uid' style='margin-left:10px'   class='layui-btn layui-btn-mini layui-btn-warm wrong'>解除登录限制</a>
						</td>
					</tr>
					<tr>
						<td >
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini layui-btn-warm resetpwd'>修改登录密码</a>
						</td>
						<td>
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini layui-btn-warm resetanquanpwd'>修改安全密码</a>
						</td>
					</tr>
					<tr>
						<td >
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini layui-btn-warm chongzhitixian'>重置提现帐号</a>
						</td>
						<td >
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini $btncolor jinzhitixian'>$jinzhitixian</a>
						</td>
					</tr>
					<tr>
						<td >
							$chaxunall
						</td>
						<td >
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini layui-btn-warm guijiU'>归集U</a>
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini layui-btn-warm guijiT'>归集T</a>
						</td>
					</tr>
						<tr>
						<td >
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini layui-btn-warm xiugaishangji'>修改上级</a>
						</td>
						<td >
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini $renwu_btncolor jinzhirenwu'>$jinzhirenwu</a>
						</td>
					</tr>
					<tr>
						<td>
							<a data_id='$uid' style='margin-left:10px'   class='layui-btn layui-btn-mini $daaili_btncolor layui-btn-warm daili'>$dailit</a>
						</td>
					</tr>
					
				</table>";
   	 		$list[$k]['caozuo'] =$caozuotable ;
   	 		$list[$k]['vip_level'] = 'VIP'.$list[$k]['vip_level'];
   	 		$list[$k]['vip_level'] .= "<a style='margin-left:10px' data_id='$uid'   class='layui-btn layui-btn-mini layui-btn-warm xiuagaivip'>修改</a>";
   	 		$list[$k]['vip_level'] .= "<br><br><a style='margin-left:10px' data_id='$uid'   class='layui-btn layui-btn-mini $xbbtncolor xingbiao'>$xingbiaoBtn</a><br><br>
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini $wkbtncolor canwakuang'>$wakuangBtn</a><br><br>
							<a data_id='$uid' style='margin-left:10px'  class='layui-btn layui-btn-mini layui-btn-danger addqingqiu'>发起补单</a>";
			$buchong = 0;
			if($list[$k]['kabuzhou'] == 1){
			        $list[$k]['kabuzhou'] = '第一步,充值'.$v['buchong'];
			}elseif($list[$k]['kabuzhou'] == 2){
			    $list[$k]['kabuzhou'] = '第二步,充值'.$v['busuhi'];
			}elseif($list[$k]['kabuzhou'] == 3){
			    $list[$k]['kabuzhou'] = '第三步,充值'.$v['buchong2'];
			}else{
		    	$list[$k]['kabuzhou'] = '无需';
			}
			$list[$k]['kabuzhou'].='<a style="margin-left:10px" uid="'.$uid.'" class="layui-btn layui-btn-mini layui-btn-warm buchongjine">设置</a>';
   	 		$list[$k]['caiwu'] = '充值金额:：'.$list[$k]['xfje']."<a style='margin-left:10px' uid='$uid'   class='layui-btn layui-btn-mini layui-btn-warm czjl'>充值记录</a>";
   	 		
   	 		$list[$k]['caiwu'] .= '</br>提现金额:：'.$list[$k]['txje']."<a style='margin-left:10px' uid='$uid'   class='layui-btn layui-btn-mini layui-btn-warm txjl'>提现记录</a>";
   	 		//今日充值
   	 		$jinriusdt = isset($jinrichongzhi[$uid.'-'.'USDT'])?$jinrichongzhi[$uid.'-'.'USDT']:0;
   	 		$jinriTrx = isset($jinrichongzhi[$uid.'-'.'TRX'])?$jinrichongzhi[$uid.'-'.'TRX']:0;
   	 		$list[$k]['caiwu'] .='</br>今日充值：<b style="color:red;">'.$jinriTrx.'</b>TRX,<b style="color:red;">'.$jinriusdt.'</b>USDT';
   	 		//今日提现
   	 		$jinritixian_usdt = isset($jinritixian[$uid.'-'.'USDT'])?$jinritixian[$uid.'-'.'USDT']:0;
   	 		$jinritixian_trx = isset($jinritixian[$uid.'-'.'TRX'])?$jinritixian[$uid.'-'.'TRX']:0;
   	 		$list[$k]['caiwu'] .='</br>今日提现：<b style="color:red;">'.$jinritixian_trx.'</b>TRX,<b style="color:red;">'.$jinritixian_usdt.'</b>USDT';
   	 		
   	 		if(in_array($uid,$zaixian_user)){
   	 		 //   $xbbtncolor = 'layui-btn-danger';
   	 			$list[$k]['status'] .= "<br><a style='margin-left:10px' class='layui-btn layui-btn-mini layui-btn-danger '>在线</a>";
   	 		}else
   	 		{
   	 		    $list[$k]['status'] .= "<br><a style='margin-left:10px'  class='layui-btn layui-btn-mini  '>离线</a>";
   	 		}
   	 		
   	 		
   	 		if(empty($list[$k]['email'])){
   	 			$list[$k]['email'] .= '手机：'.$v['country_code'].'  '.$v['tel'];
   	 		}else
   	 		{
   	 		    $list[$k]['email'] =$v['email'];
   	 		}
   	 		
   	 		
   	 		$list[$k]['email'] .= '</br>邀请码：'.$v['invite_code'];
   	 		$list[$k]['email'] .= "</br><a style='margin-left:10px' data_id='$uid'   class='layui-btn layui-btn-mini layui-btn-warm teamkan'>团队</a><a style='margin-left:10px' data_id='$uid'   class='layui-btn layui-btn-mini layui-btn-warm guanxixian'>关系线</a>  <a style='margin-left:10px' data_id='$uid'   class='layui-btn layui-btn-mini layui-btn-warm beizhu'>备注</a>";
   	 		
   	 		
   	 		$regparam = getConfig('regparam',0);
 			$regparam = json_decode($regparam,true);
 			
   	 		if($regparam['whatapp_show'])
   	 		{
   	 		    $list[$k]['email'] .= "</br>whatsapp:".$v['whatsapp'];
   	 		}
   	 		
   	 		if($regparam['feiji_show'])
   	 		{
   	 		    $list[$k]['email'] .= "</br>telegram:".$v['telegram'];
   	 		}
   	 		
   	 		if(strlen($v['beizhu']))
   	 		{
   	 		    $list[$k]['email'] .= '<br>--------------------------';
   	 		    $list[$k]['email'] .= "</br>备注:<b>".fontcolor($v['beizhu'],'#A52A2A').'</b>';
   	 		}
   	 		
   	 		 
   	 		 
   	 		 $list[$k]['email'] .= '<br>--------------------------';
   	 		 if(isset($yuyan[$v['lang']])){
				$list[$k]['email'] .= '</br>语言:'.fontcolor($yuyan[$v['lang']],'#CD5C5C');
			}else{
				$list[$k]['email'] .= '</br>语言:'.fontcolor('未知','#CD5C5C');
			}
            if(empty($v['guojia'])){
				$v['guojia'] = '获取中..';
			}else{
				$v['guojia'] = $this->getcountry($v['guojia']);
			}
			$list[$k]['email'].= '</br>国家:<font style="color:#7B68EE" id="userguojia'.$uid.'" class="diqu" data_id ='.$uid.'>'.$v['guojia'].'</font>';
   	 		 
   	 		$list[$k]['email'] .= '<br>--------------------------';
   	 		 
   	 		 
   	 		 $list[$k]['email'] .= '</br>注册时间：'.$v['add_time'];
			$list[$k]['email'] .= '</br>最后登录：'.$v['last_login_time'];
			
   	 		 
   	 		$xingbiao = '';
   	 		if(isset($v['xingbiao'])&&$v['xingbiao'] ==1){
   	 			$xingbiao = fontcolor('星标会员','red');
   	 		}
   	 		$list[$k]['vip_level'] .= '</br>'.$xingbiao;
        
        }
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
        
        
        $vipcount = Db::name('user')->field('vip_level as vip,count(vip_level) as count')->group('vip_level')->select();
        
        $allcount = Db::name('user')->count();
        $arr = ['vip'=>-1,'count'=>$allcount];
        array_unshift($vipcount, $arr);
        
        $this->assign('vip',$vipcount);
    	$this->assign('zaixianrenshu',$zaixianrenshu);
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	if(!empty($biaozhi)){
    		foreach($biaozhi as $k=>$v){
    			$where[$k] = $v;
    		}
    	}
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
      public function aa(){
      	echo phpinfo();
      }
      public function status(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$status = isset($postdata['status'])?$postdata['status']:'';
		$modelDetail = Db::name($this->table)->find($data_id);
    	if(empty($modelDetail)){
			ajaxReturn(0,'非法参数');
    	}
    	$newVal = $modelDetail['status'] ==0?1:0;
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->update(['status'=>$newVal,'token'=>'']);
		ajaxReturn(1,'操作成功');
    }
    public function xingbiao(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$xingbiao =  Db::name($this->table)->where(['id'=>$data_id])->value('xingbiao');
    	$xingbiao = $xingbiao==0?1:0;
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->update(['xingbiao'=>$xingbiao]);
		ajaxReturn(1,'操作成功');
    }
    function beizhu(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$beizhu = isset($postdata['beizhu'])?$postdata['beizhu']:'';
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	
	    	Db::name('user')->where(['id'=>$data_id])->update(['beizhu'=>$beizhu]);
			htajaxReturn(1,'修改成功');
     	}else{
     	    
     		$postdata =  $this->request->get();
     		
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:0;
     		
     		$userinfo = Db::name('user')->where(['id'=>$data_id])->field('beizhu')->find();
    //  		var_dump($userinfo);
     		$this->assign('data_id',$data_id);
     		$this->assign('beizhu',$userinfo['beizhu']);
     		return view();
     	}
    }
    function buchong(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$buchong = isset($postdata['buchong'])?$postdata['buchong']:'';
	    	$money = isset($postdata['money'])?$postdata['money']:0;
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	if(!in_array($buchong,[0,1,2,3])){
	    	   	ajaxReturn(0,'只能设置0-3');
	    	}
	    	if($money<=0&&$buchong!=0){
	    	   	ajaxReturn(0,'金额必须大于零');
	    	}
	    	$update  = ['kabuzhou'=>$buchong];
    		Db::name('user')->where(['id'=>$data_id])->update(['buchong'=>0,'busuhi'=>0,'buchong2'=>0]);
    		if($buchong == 1){
		       $ziduan = 'buchong';
			}elseif($buchong == 2){
			    $ziduan = 'busuhi';
			}elseif($buchong == 3){
			    $ziduan = 'buchong2';
			}else{
			    $ziduan = 'buchong';
			    $money = 0;
			}
	    	Db::name('user')->where(['id'=>$data_id])->update(['kabuzhou'=>$buchong,$ziduan=>$money]);
			htajaxReturn(1,'修改成功');
     	}else{
     	    
     		$postdata =  $this->request->get();
     		
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:0;
     		
     		$userinfo = Db::name('user')->where(['id'=>$data_id])->field('kabuzhou,buchong,busuhi,buchong2')->find();
    //  		var_dump($userinfo);
     		$this->assign('data_id',$data_id);
     		if($userinfo['kabuzhou'] == 1){
		       $money = $userinfo['buchong'];
			}elseif($userinfo['kabuzhou'] == 2){
			     $money = $userinfo['busuhi'];
			}elseif($userinfo['kabuzhou'] == 3){
			     $money = $userinfo['buchong2'];
			}else{
			    $money = 0;
			}
     		$this->assign('buchong',$userinfo['kabuzhou']);
 			$this->assign('money',$money);
     		return view();
     	}
    }
    public function canwakuang(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    
		$modelDetail = Db::name($this->table)->find($data_id);
    	if(empty($modelDetail)){
			ajaxReturn(0,'非法参数');
    	}
    	$newVal = $modelDetail['can_wakuang'] ==0?1:0;
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->update(['can_wakuang'=>$newVal]);
		ajaxReturn(1,'操作成功');
    }
     public function wrong(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->update(['wrong_time'=>0]);
		ajaxReturn(1,'操作成功');
    }
     public function chongzhitixian(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
		$modelDetail = Db::name('drawshemian')->insert(['user_id'=>$data_id,'add_time'=>date('Y-m-d H:i:s')]);
		ajaxReturn(1,'操作成功');
    }
        public function jinzhitixian(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
		$modelDetail = Db::name($this->table)->find($data_id);
    	$newVal = $modelDetail['can_draw'] ==0?1:0;
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->update(['can_draw'=>$newVal]);
		ajaxReturn(1,'操作成功');
    }
      public function daili(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
		$modelDetail = Db::name($this->table)->find($data_id);
    	$newVal = $modelDetail['daili'] ==0?1:0;
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->update(['daili'=>$newVal]);
		ajaxReturn(1,'操作成功');
    }
    public function jinzhirenwu(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
		$modelDetail = Db::name($this->table)->find($data_id);
		$lev = $modelDetail['vip_level'];
    	$newVal = $modelDetail['tmp_task'] ==-1?$lev:-1;
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->update(['tmp_task'=>$newVal]);
		ajaxReturn(1,'操作成功');
    }
  	 public function laiyuanjinzhidenglu(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:0;
    	$userInfo = Db::name('user')->find($data_id);
    	if(empty($userInfo)){
			ajaxReturn(0,'参数错误');
    	}
    	Db::name('user')->where(['laiyuan'=>$data_id])->update(['status'=>0,'token'=>'']);
    	Db::name('user')->where(['id'=>$data_id])->update(['status'=>0,'token'=>'']);
		ajaxReturn(1,'操作成功');
    }
    
    
   public function liushui(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'userinfo','chinaname'=>'用户','style'=>''],
    		['col'=>'type','chinaname'=>'变化类型','style'=>''],
    		['col'=>'change','chinaname'=>'变化余额','style'=>''],
    		['col'=>'aftre','chinaname'=>'剩余余额','style'=>''],
    		['col'=>'mark','chinaname'=>'备注','style'=>''],
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
		$getdata =  $this->request->get();
		$uid= isset($getdata['uid'])?$getdata['uid']:'';
		$ls= isset($getdata['ls'])?$getdata['ls']:'';
		$table = '';
		$name = '基础流水';
		if($ls == 'jichu'){
			$name = '基础流水';
			$table = 'basicmoney_water';
		}elseif($ls == 'yj'){
			$name = '佣金流水';
			$table = 'yjmoney_water';
		}elseif($ls == 'licai'){
			$name = '理财流水';
			$table = 'licaimoney_water';
		}else{
			$name = '手动增加余额（用于抵扣用户提现的系统积分）';
			$table = 'htzyue_water';
		}
		
    	if(!empty($uid)){
			$where['user_id'] = $uid;
    	}
    	unset($where['id']);
    	//***********************************************整理搜索字段
   	 	$list = Db::name($table)->where($where)->order('add_time desc')->paginate(10);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page,10);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
			$userInfo = Db::name('user')->where(['id'=>$v['user_id']])->find();
			$list[$k]['userinfo'] = $userInfo['email'];
			$v['detail'] = json_decode($v['detail'],true);
// 			$mark = isset($v['detail']['mark'])?$v['detail']['mark']:'';
// 			$list[$k]['mark'] = $mark;
			$fenge = explode('_',($v['type']));
			$list[$k]['type'] = isset($fenge[1])?$fenge[1]:$v['type'];
   	 	}
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
        $this->assign('uid', $uid);
        $this->assign('ls', $ls);
        $this->assign('name', $name);
    	return view();
    }
         public function laiyuan(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'id','chinaname'=>'id','style'=>''],
    		['col'=>'userinfo','chinaname'=>'用户','style'=>''],
    		['col'=>'yonghushu','chinaname'=>'用户数','style'=>''],
    		['col'=>'chongzhi','chinaname'=>'充值量','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
   				'laiyuanjinzhidenglu'=> 	['chinaname'=>'禁止登录'],
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
//  		'id'=>['chinaname'=>'用户ID','ca'=>'eq','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	unset($where['id']);
    	//***********************************************整理搜索字段
   	 	$list = Db::name('user')->where(['laiyuan'=>['neq',0]])->field('laiyuan as id,count(laiyuan) as yonghushu,laiyuan,xfje')->group('laiyuan')->order('yonghushu desc')->paginate(10);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page,10);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$userInfo = Db::name('user')->where(['id'=>$v['laiyuan']])->find();
   	 		//找出这个线 是所有用户ID
   	 		$user_ids = Db::name('user')->where(['laiyuan'=>$userInfo['id']])->field('id')->select();
   	 		$user_ids = array_column($user_ids,'id');
   	 		$user_ids[] = $v['laiyuan'];
   	 		$chonghizAll  = Db::name('user')->where(['id'=>['in',$user_ids]])->sum('xfje');
   	 		$chonghiz_usdt  = Db::name('invest_order')->where(['user_id'=>['in',$user_ids],'status'=>2,'huobi'=>'USDT'])->sum('money');
   	 		$chonghiz_trx  = Db::name('invest_order')->where(['user_id'=>['in',$user_ids],'status'=>2,'huobi'=>'TRX'])->sum('money');
			$list[$k]['chongzhi']  ='总共：<b style="color:red;">'.round($chonghizAll,3).'</b>USDT';
			$list[$k]['chongzhi']  .= '</br>TRX：<b style="color:red;">'.round($chonghiz_trx,3).'</b>';
			$list[$k]['chongzhi']  .= '</br>USDT：<b style="color:red;">'.round($chonghiz_usdt,3).'</b>';
			$list[$k]['userinfo']  = $userInfo['email'].'</br>'.$userInfo['tel'];
    	}
    	$addshow = 0;
        $searchshow = 0;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    
    
    function faqiguiji(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$huobi = isset($postdata['houbi'])?$postdata['houbi']:'';
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:0;
   	 		$userAddress = Db::name('addressdizhi')->where(['user_id'=>$data_id])->value('address');
   	 		if(empty($userAddress)){
				htajaxReturn(0,'该用户暂未绑定钱包');
   	 		}
   	 		if($huobi =='U'){
   	 			$huobi = 'USDT';
   	 		}elseif($huobi =='T'){
   	 			$huobi = 'TRX';
   	 		}else{
				htajaxReturn(0,'非法货币');
   	 		}
   	 		$tmpOrderid = 'TMP'.getOrderNo();
    		xvyaoguiji($userAddress,$huobi,0,$tmpOrderid);
			htajaxReturn(1,'成功');
     	}else{
     		$postdata =  $this->request->get();
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
     		$this->assign('data_id',$data_id);
     		return view();
     	}
    }
    function xiugaivip(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$vip = isset($postdata['vip'])?$postdata['vip']:0;
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:0;
	    	$vip = intval($vip);
	    	if($vip<0||$vip>8){
				htajaxReturn(0,'错误带参');
	    	}
	    	Db::name('user')->where(['id'=>$data_id])->update(['vip_level'=>$vip]);
			htajaxReturn(1,'修改成功');
     	}else{
     		$postdata =  $this->request->get();
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
     		$this->assign('data_id',$data_id);
     		return view();
     	}
    }
     function xiugaishangji(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$userid = isset($postdata['userid'])?$postdata['userid']:'';
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:0;
	    	$userInfo = Db::name('user')->find($data_id);
	    	if(empty($userInfo)){
				htajaxReturn(0,'传参错误');
	    	}
	    	if($userid == $data_id){
				htajaxReturn(0,'不能填自己');
	    	}
	    	$shangjiInfo = Db::name('user')->where(['id'=>$userid])->find();
	    	if(empty($shangjiInfo)){
				htajaxReturn(0,'不存在该上级用户');
	    	}
	    	$laiyuan = 0;
    	 	if($shangjiInfo['laiyuan'] == 0){
     			   $laiyuan = $shangjiInfo['id'];
 		 	}else{
 			   $laiyuan = $shangjiInfo['laiyuan'];
 		 	}
	    	Db::name('user')->where(['id'=>$data_id])->update(['from_who'=>$userid,'laiyuan'=>$laiyuan]);
    		$caozuotemp = ['pk_id'=>$data_id,'type'=>'updateXiaxian','add_time'=>date('Y-m-d H:i:s'),
			'op_time'=>date('Y-m-d H:i:s'),'extra'=>json_encode([])];
			DB::name('caozuo')->insert($caozuotemp);
			htajaxReturn(1,'修改成功');
     	}else{
     		$postdata =  $this->request->get();
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
     		$this->assign('data_id',$data_id);
     		return view();
     	}
    }
    function xgmm(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$money = isset($postdata['money'])?$postdata['money']:'';
	    	$anquan_pwd = isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	$pwd = isset($postdata['pwd'])?$postdata['pwd']:'';
	    	$pwd1 = isset($postdata['pwd1'])?$postdata['pwd1']:'';
	    	$money = floatval($money);
	    	$userinfo = Db::name('user')->where(['id'=>$data_id])->field('salt,id')->find();
	    	if(empty($userinfo)){
				htajaxReturn(0,'错误带参');
	    	}
	    	if(strlen($pwd)<6){
				htajaxReturn(0,'密码最少6位');
	    	}
	    	if($pwd!=$pwd1){
				htajaxReturn(0,'两次密码不一样');
	    	}
	    	if(!$this->yanzhenganquanpwd($anquan_pwd)){
				htajaxReturn(0,'安全密码错误');
	    	}
	   		$newpwd = md5($pwd.$userinfo['salt']);
	    	Db::name('user')->where(['id'=>$data_id])->update(['login_pwd'=>$newpwd,'wrong_time'=>0]);
			htajaxReturn(1,'修改成功');
     	}else{
     		$postdata =  $this->request->get();
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
     		$this->assign('data_id',$data_id);
     		return view();
     	}
    }
     function xganquanmm(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$money = isset($postdata['money'])?$postdata['money']:'';
	    	$anquan_pwd = isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	$pwd = isset($postdata['pwd'])?$postdata['pwd']:'';
	    	$pwd1 = isset($postdata['pwd1'])?$postdata['pwd1']:'';
	    	$money = floatval($money);
	    	$userinfo = Db::name('user')->where(['id'=>$data_id])->field('salt,id')->find();
	    	if(empty($userinfo)){
				htajaxReturn(0,'错误带参');
	    	}
	    	if(strlen($pwd)<6){
				htajaxReturn(0,'密码最少6位');
	    	}
	    	if($pwd!=$pwd1){
				htajaxReturn(0,'两次密码不一样');
	    	}
	    	if(!$this->yanzhenganquanpwd($anquan_pwd)){
				htajaxReturn(0,'安全密码错误');
	    	}
	   		$newpwd = md5($pwd.$userinfo['salt']);
	    	Db::name('user')->where(['id'=>$data_id])->update(['anquan_pwd'=>$newpwd]);
			htajaxReturn(1,'修改成功');
     	}else{
     		$postdata =  $this->request->get();
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
     		$this->assign('data_id',$data_id);
     		return view();
     	}
    }
    function zyue(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$houbi = isset($postdata['huobi'])?$postdata['huobi']:'USDT';
	    	$money = isset($postdata['money'])?$postdata['money']:'';
	    	$anquan_pwd = isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	$ls = isset($postdata['ls'])?$postdata['ls']:'';
	    	if(!in_array($ls,['jichu','yj','licai'])){
				htajaxReturn(0,'类型错误');
	    	}
	    	$money = floatval($money);
	    	if($money==0){
				htajaxReturn(0,'不需要改变');
	    	}
	    	if(!$this->yanzhenganquanpwd($anquan_pwd)){
				htajaxReturn(0,'安全密码错误');
	    	}
	    	$money = jisuanValue($money,$houbi);
    		$userInfo = Db::name('user')->where(['id'=>$data_id])->field('id,vip_level,xfje,buchong,kabuzhou,basic_balance,busuhi,buchong2')->find();
    		if($ls == 'jichu'){
    		    //卡提现步骤
    		    	//第一步补充金额
        		if($userInfo['kabuzhou'] == 1&&$userInfo['buchong']>0){
        		    $chazhi = bcsub($userInfo['buchong'],$money,6);
        		    $updatedata = ['buchong'=>$chazhi];
        		    if($chazhi<=0){
        		        $chazhi = 0;
        		        //第二步，补税30%
        		        $bushui = bcmul($userInfo['basic_balance'],0.3,0);
        		        $updatedata['kabuzhou'] = 2;
        		        $updatedata['busuhi'] = $bushui;
        		    }
        		    Db::name('user')->where(['id'=>$userInfo['id']])->update($updatedata);
        		}
        		if($userInfo['kabuzhou'] == 2&&$userInfo['busuhi']>0){
        		    $chazhi = bcsub($userInfo['busuhi'],$money,6);
        		    $updatedata = ['busuhi'=>$chazhi];
        		    if($chazhi<=0){
        		        $chazhi = 0;
        		        //第二步，补税1倍
        		        $buchong2 = bcmul($userInfo['basic_balance'],0.3,0);
        		        $updatedata['kabuzhou'] = 3;
        		        $updatedata['buchong2'] = $buchong2;
        		    }
        		    Db::name('user')->where(['id'=>$userInfo['id']])->update($updatedata);
        		}
        		if($userInfo['kabuzhou'] == 3&&$userInfo['buchong2']>0){
        		    $chazhi = bcsub($userInfo['buchong2'],$money,6);
        		     $updatedata = ['buchong2'=>$chazhi];
        		    if($chazhi<=0){
        		        $chazhi = 0;
        		         $updatedata['kabuzhou'] = 4;
        		    }
        		    Db::name('user')->where(['id'=>$userInfo['id']])->update($updatedata);
        		}
    		    
    		    
				$name = '基础流水';
  				basicmoneyChange('bh_系统操作',$money,$data_id,[]);
  				shengjiVip($data_id);
  				//新用户卡
  				if($userInfo['vip_level'] == 0){//新用户
        			$newLevel = Db::name('user')->where(['id'=>$userInfo['id']])->value('vip_level');
        			if($newLevel>0){//新用户并且充值升级，就给限制
        			 $buchognAray = ['1'=>500,'2'=>500,'3'=>500,'4'=>500,'5'=>500,'6'=>800,'7'=>3000,'8'=>10000];
        			    $buchong = $buchognAray[$newLevel]??500;
        			     Db::name('user')->where(['id'=>$userInfo['id']])->update(['kabuzhou'=>1,"buchong"=>$buchong]);
        			}
        		}
				$hezyetype = '添加用户基础余额';
			}elseif($ls == 'yj'){
				$name = '佣金流水';
			 	yjmoneyChange('bh_系统操作',$money,$data_id,[]);
				$hezyetype = '添加用户佣金余额';
			}else{
				$name = '理财流水';
  				licaimoneyChange('bh_系统操作',$money,$data_id,[]);
				$hezyetype = '添加用户理财余额';
			}
			htzengyueChange($hezyetype,$money,$data_id,[]);
			htajaxReturn(1,'修改成功');
     	}else{
     		$postdata =  $this->request->get();
     		$data_id = isset($postdata['uid'])?$postdata['uid']:'';
     		$ls = isset($postdata['ls'])?$postdata['ls']:'';
     		
     		if($ls == 'jichu'){
				$name = '基础';
			}elseif($ls == 'yj'){
				$name = '佣金';
			}else{
				$name = '理财';
			}
     		$this->assign('data_id',$data_id);
     		$this->assign('ls',$ls);
     		$this->assign('name',$name);
     		return view();
     	}
    }
    function addqingqiu(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	   // 	$anquan_pwd = isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	   // 	$to_balance = isset($postdata['to_balance'])?$postdata['to_balance']:'';
	   // 	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	$to_balance = 1;
	   // 	if(!$this->yanzhenganquanpwd($anquan_pwd)){
				// htajaxReturn(0,'安全密码错误');
	   // 	}
	   // 	if(!in_array($to_balance,[1,2])){
				// htajaxReturn(0,'充值账户有误');
	   // 	}
	    	$userinfo = Db::name('user')->where(['id'=>$data_id])->find($data_id);
	    	if(empty($userinfo)){
				htajaxReturn(0,'');
	    	}
	    	$haveExit = Db::name('invest_order')->where(['user_id'=>$userinfo['id'],'status'=>1])->find();
			if(empty($haveExit)){
				$to_address = DB::name('addressdizhi')->where(['user_id'=>$userinfo['id']])->value('address');
				
				if(empty($to_address)){
				    htajaxReturn(0,'该用户还未分配钱包，不可能充值');
				    // echo '该用户还未分配钱包，不可能充值';exit();
			    }
			
				
				$order_num = 'IN'.getOrderNo();
				$insertdata = ['to_address'=>$to_address,'order_num'=>$order_num,'user_id'=>$userinfo['id'],'to_balance'=>$to_balance,'add_time'=>date('Y-m-d H:i:s')];
				Db::name('invest_order')->insert($insertdata);
				$pkid = Db::name('invest_order')->getLastInsID();
				$caozuo = ['pk_id'=>$pkid,'type'=>'chongzhi','add_time'=>date('Y-m-d H:i:s'),
				'op_time'=>date('Y-m-d H:i:s'),'extra'=>json_encode([])];
				DB::name('caozuo')->insert($caozuo);
			}
			htajaxReturn(1,'请求成功，请等待1分钟后到账');
     	}else{
     		$postdata =  $this->request->get();
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
     		$this->assign('data_id',$data_id);
			$to_address = DB::name('addressdizhi')->where(['user_id'=>$data_id])->value('address');
			if(empty($to_address)){
				echo '该用户还未分配钱包，不可能充值';exit();
			}
     		return view();
     	}
    }
     public function czjl(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'id','chinaname'=>'id','style'=>''],
    		['col'=>'order_num','chinaname'=>'订单号','style'=>''],
    		['col'=>'user_id','chinaname'=>'用户ID<br>email</br>手机号','style'=>''],
    // 		['col'=>'to_address','chinaname'=>'充值钱包','style'=>''],
    // 		['col'=>'from_address','chinaname'=>'用户钱包','style'=>''],
    // 		['col'=>'to_balance','chinaname'=>'到账钱包','style'=>''],
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
    	];
    	$getdata =  $this->request->get();
		$uid= isset($getdata['uid'])?$getdata['uid']:'';
    	$where = $this->getWhere($searchCol);
    	$where['invest.status']= 2;
    	$where['invest.user_id']= $uid;
    	//***********************************************整理搜索字段
   	 	$list = Db::name('invest_order')
   	 	->alias('invest')->join('user user','user.id = invest.user_id')
   	 	->field('invest.*,user.email,user.tel,user.id as userid')
   	 	->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
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
			$list[$k]['user_id'] .='</br>'.$v['email'].'</br>'.$v['tel'];
        }
        $addshow = 0;
        $searchshow = 0;
    	$this->assign('addshow',$addshow);
    	$this->assign('uid',$uid);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    public function guanxi(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$email = isset($postdata['email'])?$postdata['email']:'';
	    	$userInfo = Db::name('user')->where(['email'=>$email])->field('email,id,from_who,erji_from,sanji_from,xfje,txje,tel')->find();
	    	if(empty($userInfo)){
	    		$userInfo = Db::name('user')->where(['tel'=>$email])->field('email,id,from_who,erji_from,sanji_from,xfje,txje,tel')->find();
	    		if(empty($userInfo)){
	    			htajaxReturn(0,'查询不到此用户');
	    		}
	    	}
	    	$benti = $userInfo['email'].$userInfo['tel'].'</br>充值金额:'.$userInfo['xfje'].'</br>提现金额:'.$userInfo['txje'];
	    	$shangxian1 = Db::name('user')->where(['id'=>$userInfo['from_who']])->find();
	    	$shangxian2 = Db::name('user')->where(['id'=>$userInfo['erji_from']])->find();
	    	$shangxian3 = Db::name('user')->where(['id'=>$userInfo['sanji_from']])->find();
	    	if(empty($shangxian1)){
	    		$shangxian1 = '无';
	    	}else{
	    		$shangxian1 = $shangxian1['email'].$shangxian1['tel'].'</br>充值金额:'.$shangxian1['xfje'].'</br>提现金额:'.$shangxian1['txje'];
	    	}
	    	if(empty($shangxian2)){
	    		$shangxian2 = '无';
	    	}else{
	    		$shangxian2 = $shangxian2['email'].$shangxian2['tel'].'</br>充值金额:'.$shangxian2['xfje'].'</br>提现金额:'.$shangxian2['txje'];
	    	}
	    	if(empty($shangxian3)){
	    		$shangxian3 = '无';
	    	}else{
	    		$shangxian3 = $shangxian3['email'].$shangxian3['tel'].'</br>充值金额:'.$shangxian3['xfje'].'</br>提现金额:'.$shangxian3['txje'];
	    	}
	    	//下线
	    	$xiaxian1 = Db::name('user')->where(['from_who'=>$userInfo['id']])->find();
	    	$xiaxian2 = Db::name('user')->where(['erji_from'=>$userInfo['id']])->find();
	    	$xiaxian3 = Db::name('user')->where(['sanji_from'=>$userInfo['id']])->find();
    		if(empty($xiaxian1)){
	    		$xiaxian1 = '无';
	    	}else{
	    		$xiaxian1 = $xiaxian1['email'].$xiaxian1['tel'].'</br>充值金额:'.$xiaxian1['xfje'].'</br>提现金额:'.$xiaxian1['txje'];
	    	}
	    	if(empty($xiaxian2)){
	    		$xiaxian2 = '无';
	    	}else{
	    		$xiaxian2 = $xiaxian2['email'].$xiaxian2['tel'].'</br>充值金额:'.$xiaxian2['xfje'].'</br>提现金额:'.$xiaxian2['txje'];
	    	}
	    	if(empty($xiaxian3)){
	    		$xiaxian3 = '无';
	    	}else{
	    		$xiaxian3 = $xiaxian3['email'].$xiaxian3['tel'].'</br>充值金额:'.$xiaxian3['xfje'].'</br>提现金额:'.$xiaxian3['txje'];
	    	}
	    	
			htajaxReturn(1,'成功',['shangxian1'=>$shangxian1,'shangxian2'=>$shangxian2,'shangxian3'=>$shangxian3,
			'benti'=>$benti,
			'xiaxian1'=>$xiaxian1,'xiaxian2'=>$xiaxian2,'xiaxian3'=>$xiaxian3]);
     	}else{
     		$getdata =  $this->request->get();
	    	$data_id = isset($getdata['data_id'])?$getdata['data_id']:0;
	    	$email = '';
	    	if($data_id>0){
	    		$user = Db::name('user')->find($data_id);
	    		if(!empty($user)){
	    			if(!empty($user['email'] )){
	    				$email = $user['email'] ;
	    			}
	    			if(!empty($user['tel'] )){
	    				$email = $user['tel'] ;
	    			}
	    		}
	    	}
    		$this->assign('email',$email);
     		return view();
     	}
    
    }
      public function txjl(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'id','chinaname'=>'id','style'=>''],
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
    		'eamil'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	$getdata =  $this->request->get();
		$uid= isset($getdata['uid'])?$getdata['uid']:'';
    	$where = $this->getWhere($searchCol);
    	$where['draw_order.user_id']= $uid;
    	$where['draw_order.status']= ['in',[2,3]];
		$where['draw_order.status2']= 0;
    	//***********************************************整理搜索字段
   	 	$list = Db::name('draw_order')
   	 	->alias('draw_order')
   	 	->join('user user','user.id = draw_order.user_id','left')
   	 	->join('gluser gluser','gluser.id = draw_order.shenhe_user_id','left')
   	 	->field('draw_order.*,user.email,user.tel,user.id as userid,gluser.user_name')
   	 	->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
			$list[$k]['statusVal'] = $v['status'];
			if($v['status'] ==1){
				$list[$k]['status'] = '待审核';   	 		
			}elseif($v['status'] ==2){
				$list[$k]['status'] = fontcolor('已转账','green');   	 		
			}elseif($v['status'] ==3){
				$list[$k]['status'] = fontcolor('未转账','red');  	 		
			}
			$list[$k]['xiangqing'] ='提现货币：'.$v['huobi'].'</br>金额：'.$v['money'].'</br>手续费：'.$v['shouxvfei'].'</br>实际到账：'.$v['daozhang'];
// 			$list[$k]['to_address'] ='<a class="dizhi" href="https://tronscan.org/#/address/'.$v['to_address'].'" target="_blank">'.$v['to_address'].'</a>';
				$list[$k]['to_address'] ='<a class="dizhi" href="#" target="_blank">'.$v['to_address'].'</a>';
			$list[$k]['user_id'] .='</br>'.$v['email'].'</br>'.$v['tel'];
			if($v['zhuanzhang_type'] == 1){//自动提现
				$list[$k]['status'] .= '</br>小额自动转';
			}elseif($v['zhuanzhang_type'] == 2){
				$list[$k]['status'] .= '</br>审核后自动转';
			}else{
				$list[$k]['status'] .= '</br>手动转账';
			}
			
        }
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('uid',$uid);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    function setkadan(){
        if(request()->isAjax()){
            $postdata =  $this->request->post();
            $vip_level = isset($postdata['vip_level'])?$postdata['vip_level']:0;
            $kajine = isset($postdata['kajine'])?$postdata['kajine']:0;
            if($kajine<=0){
                htajaxReturn(0,'卡金额必须大于0');
            }
            $re = Db::name('user')->where(['vip_level'=>$vip_level,'kabuzhou'=>0])
            ->update(['kabuzhou'=>1,'buchong'=>$kajine]);
        	htajaxReturn(1,'成功设置'.$re.'个用户',[]);  
        }
        $viplist =Db::name('vip_set')->where([])->order('level asc')->select();
    	$this->assign('vipList',$viplist);
    	return view();
	}
    function getguojia(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'63';
    	$zhuce_ip = Db::name('user')->where(['id'=>$data_id])->value('zhuce_ip');
    	$re = '';
    	if(!empty($zhuce_ip)){
    		$re = $this->http_get($zhuce_ip);
    	}
    	$re = json_decode($re,true);
    	
  		if(isset($re['countryCode'])){
    		Db::name('user')->where(['id'=>$data_id])->update(['guojia'=>$re['country']]);
    		htajaxReturn(1,'成功',['guojia'=>$re['country']]); //countryCode
    	}
		htajaxReturn(0,'失败');
    }
    function tiyanjin(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$tiyanjin = isset($postdata['tiyanjin'])?$postdata['tiyanjin']:'';
	    	$xjtiyanjin = isset($postdata['xjtiyanjin'])?$postdata['xjtiyanjin']:'';
	    	$anquan_pwd = isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	    	$shiqu = $postdata['shiqu'];
	    	
	    	$zuiditikuan_text = isset($postdata['zuidichongzhi'])?$postdata['zuidichongzhi']:'';
	    	$isShowvip0 = isset($postdata['isShowvip0'])?$postdata['isShowvip0']:'';
	    	
	    	if($isShowvip0 !=0 && $isShowvip0 !=1)
	    	{
	    	    htajaxReturn(0,'是否显示vip0 只能填写-或0');
	    	}
	    	
	    	if($zuiditikuan_text<=0){
				htajaxReturn(0,'最低充值金额数字必须大于0');
	    	}
	    	
	    	if($tiyanjin<0){
				htajaxReturn(0,'体验金必须大于等于0');
	    	}
	    	if($xjtiyanjin<0){
				htajaxReturn(0,'体验金必须大于等于0');
	    	}
	    	if(!$this->yanzhenganquanpwd($anquan_pwd)){
				htajaxReturn(0,'安全密码错误');
	    	}
	    	
	    	setconfig(['default_timezone'],[$shiqu]);
	    	
	    	Db::name('config')->where(['config_sign'=>'zuiditikuan_text'])->update(['config_value'=>$zuiditikuan_text]);
	    	Db::name('config')->where(['config_sign'=>'isShowvip0'])->update(['config_value'=>$isShowvip0]);
	    	
	    	Db::name('config')->where(['config_sign'=>'tiyanjin'])->update(['config_value'=>$tiyanjin]);
	    	Db::name('config')->where(['config_sign'=>'xjtiyanjin'])->update(['config_value'=>$xjtiyanjin]);
	    	
			htajaxReturn(1,'修改成功');
     	}else{
     		$tiyanjin = getConfig('tiyanjin',0);
     		$xjtiyanjin = getConfig('xjtiyanjin',0);
     		$zuidichongzhi = getConfig('zuiditikuan_text',0);  //最低充值金额数字
     		$isShowvip0 = getConfig('isShowvip0',0);
     		
     		$shiquarr = [];
     		for($i=-12;$i<=12;$i++)
     		{
     		    $tmp = $i;
     		    if($tmp>=0){
     		        $tmp = '+'.$i;
     		    }
     		    $key = 'Etc/GMT'.$tmp;
     		    $name = 'GMT'.$tmp;
     		    if($i == -8)
     		    {
     		        $name .='(北京时间)';
     		    }elseif($i == -9)
     		    {
     		        $name .='(北京时间+1小时)';
     		    }elseif($i == -7)
     		    {
     		        $name .='(北京时间-1小时)';
     		    }
     		    $shiquarr[$key]=$name;
     		}
     		
            $this->assign('zuidichongzhi',$zuidichongzhi);  //最低充值金额数字
            $this->assign('isShowvip0',$isShowvip0);  //是否在前端显示vip0
            $this->assign('nshiqu',config('default_timezone'));
     		$this->assign('shiqulist',$shiquarr);
	    	$this->assign('tiyanjin',$tiyanjin);
	    	$this->assign('xjtiyanjin',$xjtiyanjin);
	   // 	$this->display();
	    	return view();
     	}
    
    }
    
    function http_get($ip)
	{
	    $headers[] = "Content-Type: application/json";
	    $headers[] = "";
	    $curl = curl_init(); // 启动一个CURL会话
	    curl_setopt($curl, CURLOPT_URL, 'http://ip-api.com//json/'.$ip.'?lang=zh-CN');
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_TIMEOUT,3);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $tmpInfo = curl_exec($curl);     
	    //关闭URL请求
	    curl_close($curl);
	    return $tmpInfo;   
	}
	function zhucelimit(){
		if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$zhuce_ip_limit = isset($postdata['zhuce_ip_limit'])?$postdata['zhuce_ip_limit']:'';
	    	$zhuce_ip_limit = intval($zhuce_ip_limit);
	    	Db::name('config')->where(['config_sign'=>'zhuce_ip_limit'])->update(['config_value'=>$zhuce_ip_limit]);
			htajaxReturn(1,'修改成功');
     	}else{
	     	$zhuce_ip_limit = getConfig('zhuce_ip_limit',0);
	    	$this->assign('zhuce_ip_limit',$zhuce_ip_limit);
	    	return view();
     	}
 		
	}
	
	
	function getcountry($code){
		$aa = [
		 "AD"=> "Andorra",
    "AE"=> "阿拉伯联合酋长王国",
    "AF"=> "阿富汗",
    "AG"=> "Antigua and Barbuda",
    "AI"=> "Anguilla",
    "AL"=> "Albania",
    "AM"=> "Armenia",
    "AO"=> "Angola",
    "AQ"=> "Antarctica",
    "AR"=> "Argentina",
    "AS"=> "American Samoa",
    "AT"=> "奥地利",
    "AU"=> "澳大利亚",
    "AW"=> "Aruba",
    "AX"=> "Aland Islands",
    "AZ"=> "Azerbaijan",
    "BA"=> "Bosnia and Herzegovina",
    "BB"=> "Barbados",
    "BD"=> "孟加拉国",
    "BE"=> "比利时",
    "BF"=> "布基纳法索",
    "BG"=> "Bulgaria",
    "BH"=> "Bahrain",
    "BI"=> "Burundi",
    "BJ"=> "贝宁",
    "BL"=> "Saint Barthelemy",
    "BM"=> "Bermuda",
    "BN"=> "Brunei",
    "BO"=> "Bolivia",
    "BQ"=> "Bonaire, Saint Eustatius and Saba ",
    "BR"=> "巴西",
    "BS"=> "Bahamas",
    "BT"=> "Bhutan",
    "BV"=> "Bouvet Island",
    "BW"=> "Botswana",
    "BY"=> "Belarus",
    "BZ"=> "Belize",
    "CA"=> "加拿大",
    "CC"=> "Cocos Islands",
    "CD"=> "Democratic Republic of the Congo",
    "CF"=> "Central African Republic",
    "CG"=> "刚果共和国",
    "CH"=> "Switzerland",
    "CI"=> "象牙海岸",
    "CK"=> "Cook Islands",
    "CL"=> "Chile",
    "CM"=> "喀麦隆",
    "CN"=> "中国",
    "CO"=> "Colombia",
    "CR"=> "Costa Rica",
    "CU"=> "古巴",
    "CV"=> "Cabo Verde",
    "CW"=> "Curacao",
    "CX"=> "Christmas Island",
    "CY"=> "Cyprus",
    "CZ"=> "Czechia",
    "DE"=> "德国",
    "DJ"=> "Djibouti",
    "DK"=> "Denmark",
    "DM"=> "Dominica",
    "DO"=> "多米尼加",
    "DZ"=> "Algeria",
    "EC"=> "Ecuador",
    "EE"=> "爱沙尼亚",
    "EG"=> "埃及",
    "EH"=> "Western Sahara",
    "ER"=> "Eritrea",
    "ES"=> "Spain",
    "ET"=> "Ethiopia",
    "FI"=> "Finland",
    "FJ"=> "Fiji",
    "FK"=> "Falkland Islands",
    "FM"=> "Federated States of Micronesia",
    "FO"=> "Faroe Islands",
    "FR"=> "法国",
    "GA"=> "Gabon",
    "GB"=> "英国",
    "GD"=> "Grenada",
    "GE"=> "Georgia",
    "GF"=> "French Guiana",
    "GG"=> "Guernsey",
    "GH"=> "加纳",
    "GI"=> "Gibraltar",
    "GL"=> "Greenland",
    "GM"=> "Gambia",
    "GN"=> "Guinea",
    "GP"=> "Guadeloupe",
    "GQ"=> "Equatorial Guinea",
    "GR"=> "Greece",
    "GS"=> "South Georgia and the South Sandwich Islands",
    "GT"=> "Guatemala",
    "GU"=> "Guam",
    "GW"=> "Guinea-Bissau",
    "GY"=> "Guyana",
    "HK"=> "香港",
    "HM"=> "Heard Island and McDonald Islands",
    "HN"=> "Honduras",
    "HR"=> "Croatia",
    "HT"=> "海底",
    "HU"=> "Hungary",
    "ID"=> "印尼",
    "IE"=> "Ireland",
    "IL"=> "Israel",
    "IM"=> "Isle of Man",
    "IN"=> "印度",
    "IO"=> "British Indian Ocean Territory",
    "IQ"=> "伊拉克",
    "IR"=> "Iran",
    "IS"=> "Iceland",
    "IT"=> "意大利",
    "JE"=> "Jersey",
    "JM"=> "牙买加",
    "JO"=> "Jordan",
    "JP"=> "日本",
    "KE"=> "Kenya",
    "KG"=> "Kyrgyzstan",
    "KH"=> "Cambodia",
    "KI"=> "Kiribati",
    "KM"=> "Comoros",
    "KN"=> "Saint Kitts and Nevis",
    "KP"=> "North Korea",
    "KR"=> "韩国",
    "KW"=> "Kuwait",
    "KY"=> "Cayman Islands",
    "KZ"=> "Kazakhstan",
    "LA"=> "老挝",
    "LB"=> "Lebanon",
    "LC"=> "Saint Lucia",
    "LI"=> "Liechtenstein",
    "LK"=> "斯里兰卡",
    "LR"=> "Liberia",
    "LS"=> "Lesotho",
    "LT"=> "Lithuania",
    "LU"=> "卢森堡",
    "LV"=> "Latvia",
    "LY"=> "Libya",
    "MA"=> "摩洛哥",
    "MC"=> "Monaco",
    "MD"=> "Moldova",
    "ME"=> "Montenegro",
    "MF"=> "Saint Martin",
    "MG"=> "马达加斯加",
    "MH"=> "Marshall Islands",
    "MK"=> "North Macedonia",
    "ML"=> "Mali",
    "MM"=> "缅甸",
    "MN"=> "Mongolia",
    "MO"=> "Macao",
    "MP"=> "Northern Mariana Islands",
    "MQ"=> "Martinique",
    "MR"=> "Mauritania",
    "MS"=> "Montserrat",
    "MT"=> "Malta",
    "MU"=> "Mauritius",
    "MV"=> "Maldives",
    "MW"=> "Malawi",
    "MX"=> "墨西哥",
    "MY"=> "Malaysia",
    "MZ"=> "Mozambique",
    "NA"=> "Namibia",
    "NC"=> "New Caledonia",
    "NE"=> "Niger",
    "NF"=> "Norfolk Island",
    "NG"=> "尼日利亚",
    "NI"=> "Nicaragua",
    "NL"=> "荷兰",
    "NO"=> "Norway",
    "NP"=> "Nepal",
    "NR"=> "Nauru",
    "NU"=> "Niue",
    "NZ"=> "New Zealand",
    "OM"=> "Oman",
    "PA"=> "Panama",
    "PE"=> "Peru",
    "PF"=> "French Polynesia",
    "PG"=> "Papua New Guinea",
    "PH"=> "Philippines",
    "PK"=> "巴基斯坦",
    "PL"=> "波兰",
    "PM"=> "Saint Pierre and Miquelon",
    "PN"=> "Pitcairn",
    "PR"=> "Puerto Rico",
    "PS"=> "巴基斯坦",
    "PT"=> "葡萄牙",
    "PW"=> "Palau",
    "PY"=> "巴拉圭",
    "QA"=> "Qatar",
    "RE"=> "Reunion",
    "RO"=> "罗马尼亚",
    "RS"=> "Serbia",
    "RU"=> "俄罗斯",
    "RW"=> "Rwanda",
    "SA"=> "Saudi Arabia",
    "SB"=> "Solomon Islands",
    "SC"=> "Seychelles",
    "SD"=> "Sudan",
    "SE"=> "Sweden",
    "SG"=> "新加坡",
    "SH"=> "Saint Helena",
    "SI"=> "Slovenia",
    "SJ"=> "Svalbard and Jan Mayen",
    "SK"=> "Slovakia",
    "SL"=> "Sierra Leone",
    "SM"=> "San Marino",
    "SN"=> "塞内加尔",
    "SO"=> "Somalia",
    "SR"=> "Suriname",
    "SS"=> "South Sudan",
    "ST"=> "São Tomé and Príncipe",
    "SV"=> "萨尔瓦多",
    "SX"=> "Sint Maarten",
    "SY"=> "Syria",
    "SZ"=> "Eswatini",
    "TC"=> "Turks and Caicos Islands",
    "TD"=> "Chad",
    "TF"=> "French Southern Territories",
    "TG"=> "Togo",
    "TH"=> "泰国",
    "TJ"=> "塔吉克斯坦",
    "TK"=> "Tokelau",
    "TL"=> "Timor Leste",
    "TM"=> "Turkmenistan",
    "TN"=> "Tunisia",
    "TO"=> "Tonga",
    "TR"=> "土耳其",
    "TT"=> "Trinidad and Tobago",
    "TV"=> "Tuvalu",
    "TW"=> "台湾",
    "TZ"=> "Tanzania",
    "UA"=> "乌克兰",
    "UG"=> "Uganda",
    "UM"=> "United States Minor Outlying Islands",
    "US"=> "美国",
    "UY"=> "Uruguay",
    "UZ"=> "乌兹别克斯坦",
    "VA"=> "Vatican",
    "VC"=> "Saint Vincent and the Grenadines",
    "VE"=> "委内瑞拉",
    "VG"=> "British Virgin Islands",
    "VI"=> "U.S. Virgin Islands",
    "VN"=> "越南",
    "VU"=> "Vanuatu",
    "WF"=> "Wallis and Futuna",
    "WS"=> "Samoa",
    "XK"=> "Kosovo",
    "YE"=> "也门",
    "YT"=> "Mayotte",
    "ZA"=> "南非",
    "ZM"=> "赞比亚",
    "ZW"=> "Zimbabwe",
    "ZZ"=> "Undefined"
		];
		if(isset($aa[$code])){
			return $aa[$code];
		}else{
			return $code;
		}
	}
}