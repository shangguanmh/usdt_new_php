<?php
namespace app\cli\controller;

use think\Controller;
use SignatureHelper;
use think\Db;
use Tron\Address;
use GuzzleHttp\Client;
class Auto extends Controller
{
//	private $model = 'zhengshi';
	private $model = 'ceshi';
	private $siteKey = 'shop2';
	
	public function mintask(){
//		Db::name('text')->insert(['text'=>'执行了一分钟','add_time'=>date('Y-m-d H:i:s')]);
		$getdata = request() -> get();
        $test = isset($getdata["test"]) ? $getdata["test"] : '';
        if($test!='1133222'){
//      	echo '测试中';exit();
        }
    	echo '运行中</br>';
		$now = date('Y-m-d H:i:s');
		$caozuo = Db::name('caozuo')->where(['op_time'=>['elt',$now]])->select();
// 		echo json_encode($caozuo);exit();
		if(!empty($caozuo)){
			foreach($caozuo as $val){
				$lock_key = getRedisXM('mingtask'.$val['id']);
				$is_lock = redisCache()->setnx($lock_key, 1); 
				if($test=='1133222'){
					redisCache()->del($lock_key); 
				}
		    	if($is_lock){
					redisCache()->expire($lock_key, 2);
				}else{
					echo '</br>锁住了哦'.$val['id'];
					redisCache()->expire($lock_key, 2);
					continue;
				}
				$type = $val['type'];
				$extra = json_decode($val['extra'],true);
				switch($type){
					case 'licai'://理财到期
						$extra['issue'] = isset($extra['issue'])?$extra['issue']:'';
						$extra['user_licai_id'] = isset($extra['user_licai_id'])?$extra['user_licai_id']:'';
						$licaiIssue = Db::name('user_licai_issue')->where(['issue'=>$extra['issue'],
						'user_licai_id'=>$extra['user_licai_id'],'status'=>1])->find();
						//结算这个利息
						if(!empty($licaiIssue)){
      					 	yjmoneyChange('bh_结算利息',$licaiIssue['lixi'],$licaiIssue['user_id'],[]);
							Db::name('user_licai_issue')->where(['id'=>$licaiIssue['id']])->update(['status'=>2]);
							$maxIssue =  Db::name('user_licai_issue')->where(['user_licai_id'=>$extra['user_licai_id']])->max('issue');
							echo '</br>'.$maxIssue;
							if($maxIssue == $extra['issue'] ){//最后一期，结束这笔理财
							echo '</br>最后一期结束';
								Db::name('user_licai')->where(['id'=>$licaiIssue['user_licai_id']])->update(['status'=>2]);
								//返还本金
      					 		yjmoneyChange('bh_返还本金',$licaiIssue['benjin'],$licaiIssue['user_id'],[]);
							}
						}
						Db::name('caozuo')->where(['id'=>$val['id']])->delete();
					break;
					case 'updateXiaxian'://更新下线数量
						$from_who = Db::name('user')->where(['id'=>$val['pk_id']])->value('from_who');
						$yiji = Db::name('user')->where(['id'=>$from_who])->find();
						if(!empty($yiji)){
							$erji_from = $yiji['from_who'];
							$sanji_from = $yiji['erji_from'];
							Db::name('user')->where(['id'=>$val['pk_id']])->update(['erji_from'=>$erji_from,'sanji_from'=>$sanji_from]);
							if($from_who>0){
								$yijicount = Db::name('user')->where(['from_who'=>$from_who])->count();
								Db::name('user')->where(['id'=>$yiji['id']])->update(['yiji_count'=>$yijicount]);
							}
							if($erji_from>0){
								$erjicount = Db::name('user')->where(['erji_from'=>$erji_from])->count();
								Db::name('user')->where(['id'=>$erji_from])->update(['erji_count'=>$erjicount]);
							}
							if($sanji_from>0){
								$sanjicount = Db::name('user')->where(['sanji_from'=>$sanji_from])->count();
								Db::name('user')->where(['id'=>$sanji_from])->update(['sanji_count'=>$sanjicount]);
							}
						}
					Db::name('caozuo')->where(['id'=>$val['id']])->delete();
					break;
					case 'chongzhiticheng'://充值的提成
						$invest_order = Db::name('invest_order')->where(['id'=>$val['pk_id']])->find();
						if(!empty($invest_order)){
						    $order_num = $invest_order['order_num'];
							$zuihouvalue = $invest_order['zuihou_value'];
							$user = Db::name('user')
							->alias('user')
				   		 	->join('user user1','user.from_who = user1.id','left')
				   		 	->join('user user2','user.erji_from = user2.id','left')
				   		 	->join('user user3','user.sanji_from = user3.id','left')
				   		 	->where(['user.id'=>$invest_order['user_id']])
							->field('user.id,user.vip_level,user1.id as user_id1,user2.id as user_id2,user3.id as user_id3,
							user1.vip_level as vip_level1,user2.vip_level as vip_level2,user3.vip_level as vip_level3')
							->find();
//							je($user);exit();
							if(!empty($user)){
								if(!isset($vipSS)||empty($vipSS)){
									$vipset = Db::name('vip_set')->where([])->field('level,rate1_chongzhi,rate2_chongzhi,rate3_chongzhi')->select();
									$vipSS = [];
									foreach($vipset as $onevip){
										$vipSS[$onevip['level']] = $onevip;
									}
								}
								//
								$yiji_user_id = 0;
								$erji_user_id = 0;
								$sanji_user_id = 0;
								$yiji_ticheng = 0;
								$erji_ticheng = 0;
								$sanji_ticheng = 0;
								$rate1_chongzhi = 0;
								$rate2_chongzhi = 0;
								$rate3_chongzhi = 0;
								
								if(!empty($user['user_id1'])){
									$yiji_user_id = $user['user_id1'];
									$rate1_chongzhi = isset($vipSS[$user['vip_level1']]['rate1_chongzhi'])?$vipSS[$user['vip_level1']]['rate1_chongzhi']:0.6;
									$yiji_ticheng = bcmul($invest_order['zuihou_value'],bcmul($rate1_chongzhi,0.01,6),6);
				 					yjmoneyChange('bh_充值返利',$yiji_ticheng,$user['user_id1'],[],$order_num);
				 					Db::name('chognzhifanli')->insert(['user_id'=>$user['user_id1'],'czjine'=>$invest_order['zuihou_value'],'money'=>$yiji_ticheng,
				 					'add_time'=>date('Y-m-d H:i:s'),
				 					'vip_level'=>$user['vip_level1'],'rate'=>$rate1_chongzhi,'laizi_user_id'=>$invest_order['user_id'],'jibie'=>1]);
								}
								if(!empty($user['user_id2'])){
									$erji_user_id = $user['user_id2'];
									$rate2_chongzhi = isset($vipSS[$user['vip_level2']]['rate2_chongzhi'])?$vipSS[$user['vip_level2']]['rate2_chongzhi']:0.6;
									$erji_ticheng = bcmul($invest_order['zuihou_value'],bcmul($rate2_chongzhi,0.01,6),6);
				 					yjmoneyChange('bh_充值返利',$erji_ticheng,$user['user_id2'],[],$order_num);
			 						Db::name('chognzhifanli')->insert(['user_id'=>$user['user_id2'],'czjine'=>$invest_order['zuihou_value'],'money'=>$erji_ticheng,
				 					'add_time'=>date('Y-m-d H:i:s'),
				 					'vip_level'=>$user['vip_level2'],'rate'=>$rate1_chongzhi,'laizi_user_id'=>$invest_order['user_id'],'jibie'=>2]);
								}
								if(!empty($user['user_id3'])){
									$sanji_user_id = $user['user_id3'];
									$rate3_chongzhi = isset($vipSS[$user['vip_level3']]['rate3_chongzhi'])?$vipSS[$user['vip_level3']]['rate3_chongzhi']:0.6;
									$sanji_ticheng = bcmul($invest_order['zuihou_value'],bcmul($rate3_chongzhi,0.01,6),6);
				 					yjmoneyChange('bh_充值返利',$sanji_ticheng,$user['user_id3'],[],$order_num);
				 					Db::name('chognzhifanli')->insert(['user_id'=>$user['user_id3'],'czjine'=>$invest_order['zuihou_value'],'money'=>$sanji_ticheng,
				 					'add_time'=>date('Y-m-d H:i:s'),
				 					'vip_level'=>$user['vip_level3'],'rate'=>$rate1_chongzhi,'laizi_user_id'=>$invest_order['user_id'],'jibie'=>3]);
								}
								$updatedata  = [
									'yiji_user_id' => $yiji_user_id,
									'erji_user_id' => $erji_user_id,
									'sanji_user_id' => $sanji_user_id,
									'yiji_ticheng' => $yiji_ticheng,
									'erji_ticheng' => $erji_ticheng,
									'sanji_ticheng' =>$sanji_ticheng,
									'rate1_chongzhi' => $rate1_chongzhi,
									'rate2_chongzhi' =>$rate2_chongzhi,
									'rate3_chongzhi' => $rate3_chongzhi
								];
								Db::name('invest_order')->where(['id'=>$invest_order['id']])->update($updatedata);
							}
						}
					Db::name('caozuo')->where(['id'=>$val['id']])->delete();
					break;
					case 'chongzhi':
					$miao = date('s');
				// 	if($miao>=20){//看秒数，如果是前现在是0，20，40执行一次，只要0秒这次去查询
				// 		echo '不是前二十秒';
				// 		redisCache()->del($lock_key); 
				// 		break;
				// 	}
					$chenggong = false;
					$julishijian = round((time()-strtotime($val['op_time']))/60,1);
					$fen = date('i');
					if($julishijian<10){//10分钟前一分钟执行一次
					}elseif($julishijian>=10&&$julishijian<20){//10-20分钟前2分钟执行一次
						if($fen%2!=0){
					    	echo '10-20分钟每2分钟执行一次';
							redisCache()->del($lock_key); 
							break;
						}
					}else{//20分钟以后3分钟执行一次
						if($fen%3!=0){
//							Db::name('text')->insert(['text'=>'20-30分钟每3分钟执行一次'.$val['pk_id'].',fen:'.$fen.'剩余分钟数'.$julishijian,'add_time'=>date('Y-m-d H:i:s')]);
                    	echo '20分钟以后3分钟执行一次';
							redisCache()->del($lock_key); 
							break;
						}
//						Db::name('text')->insert(['text'=>'执行了'.$val['pk_id'],'add_time'=>date('Y-m-d H:i:s')]);
					}
					$invest_order = Db::name('invest_order')->where(['id'=>$val['pk_id'],'status'=>1])->find();
					if(!empty($invest_order)&&!empty($invest_order['to_address'])){
						//先查询TRX的
						echo '</br>开始查询trx的'.$chenggong.'aa';
						$result = $this->getTrxRecord($invest_order['to_address']);
						$result = json_decode($result,true);
//						je($result);exit();
						if(isset($result['data'])&&count($result['data'])>0){
							$adressLast = Db::name('addressdizhi')->where(['address'=>$invest_order['to_address']])->find();
							foreach($result['data'] as $record){
								if(isset($record['ret'][0]['contractRet'])
								&&$record['ret'][0]['contractRet'] =='SUCCESS'
								&&isset($record['raw_data']['contract'][0]['parameter']['value']['amount'])
								&&isset($record['txID'])
								&&!isset($record['raw_data']['contract'][0]['parameter']['value']['asset_name'])//只有不存在这个字段才是TRX
								&&!empty($adressLast)
								){
									$txID = $record['txID'];
									$lasthash_id = $adressLast['lasthash_id'];
									$last_time = $adressLast['last_time'];
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
										Db::name('addressdizhi')->where(['address'=>$invest_order['to_address']])
										->update(['lasthash_id'=>$txID,'last_time'=>$block_timestamp]);
										$chenggong = true;
										$owner_address = isset($record['raw_data']['contract'][0]['parameter']['value']['owner_address'])?
										$record['raw_data']['contract'][0]['parameter']['value']['owner_address']:'';
										$this->daozhang($owner_address,$jine,'TRX',$val['pk_id'],$txID);
										echo '</br>TRX到账了';
										redisCache()->del($lock_key); 
										break;
								    }
								    if($block_timestamp == $last_time||$lasthash_id ==$txID){//匹配到一样的交易，就结束了
										echo '</br>匹配到一样的交易，就结束了trx'.$block_timestamp.'  '.$txID;
										redisCache()->del($lock_key); 
										break;
									}
							}
						}
					}
						//查询usdt,trx不成功才查询
						echo '</br>开始查询usdt的'.$chenggong.'aa'.$val['pk_id'];
						if(!$chenggong){
							echo '</br>开始查询usdt的';
							$result = $this->getUsdtRecord($invest_order['to_address']);
							$result = json_decode($result,true);
							je($result);
							if(isset($result['data'])&&count($result['data'])>0){
								$adressLast = Db::name('addressdizhi')->where(['address'=>$invest_order['to_address']])->find();
								foreach($result['data'] as $record){
									if(
									isset($record['transaction_id'])
									&&isset($record['block_timestamp'])
									&&isset($record['token_info']['symbol'])
									&&$record['token_info']['symbol'] == 'USDT'
									&&$record['type'] == 'Transfer'
									&&$record['value']
									&&!empty($adressLast)
									){
										$transaction_id = $record['transaction_id'];
										$lasthash_id = $adressLast['lasthash_id'];
										$last_time = $adressLast['last_time'];
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
											Db::name('addressdizhi')->where(['address'=>$invest_order['to_address']])
											->update(['lasthash_id'=>$transaction_id,'last_time'=>$block_timestamp]);
											$chenggong = true;
											$owner_address = isset($record['from'])?
											$record['from']:'';
											$this->daozhang($owner_address,$jine,'USDT',$val['pk_id'],$transaction_id);
											redisCache()->del($lock_key); 
											break;
										}
										if($block_timestamp == $last_time&&$lasthash_id ==$transaction_id){//匹配到一样的交易，就结束了
												echo '</br>匹配到一样的交易，就结束了usdt'.$block_timestamp.'  '.$transaction_id;
											redisCache()->del($lock_key); 
											break;
										}
									}
									
								}
								
							}
						}
					}else{
						Db::name('caozuo')->where(['id'=>$val['id']])->delete();
						echo '</br>空值';
					}
					if($chenggong){//成功
						Db::name('caozuo')->where(['id'=>$val['id']])->delete();
						Db::name('text')->insert(['text'=>'成功删除提现操作'.$val['pk_id'],'add_time'=>date('Y-m-d H:i:s')]);
					}
					if((time()-strtotime($val['op_time']))>(30*60)){//或者30分钟就删除这个操作
						echo '15分钟不到账删除';
						Db::name('caozuo')->where(['id'=>$val['id']])->delete();
						Db::name('invest_order')->where(['id'=>$val['pk_id']])->update(['status'=>3]);
						Db::name('text')->insert(['text'=>'30分钟不到账就删除这个操作'.$val['pk_id'],'add_time'=>date('Y-m-d H:i:s')]);
					}
					break;
					case 'autotixian':
                        $tixian_kaiguan = getConfig('tixian_kaiguan',1)??true; //提现开关
                          echo '自动提现中..'.$tixian_kaiguan;
                        if(!$tixian_kaiguan)
                        {
                            continue 2;
                        }
                        
                        $config = Db::name('nconfig')->find();
	                     $tixian_trx = $config['tixian_trx'];
	                     $tixian_usdt = $config['tixian_usdt'];
	                     $tixian_Energy = $config['tixian_energy'];
	                     
	                     $tixian = Db::name('draw_order')->where(['id'=>$val['pk_id'],'status'=>1])->find();
	                     
	                     if(!empty($tixian))
	                     {
	                         if($tixian['huobi'] == 'USDT')
						    {
						        if($tixian['daozhang']>$tixian_usdt)
						        {
						            echo('余额不足，提现失败');
						            Db::name('caozuo')->where(['id'=>$val['id']])->delete();
						          //余额不足
						          continue 2;
						        }
						    }else
						    {
						        if($tixian['daozhang'] >$tixian_trx)
						        {
						           echo('余额不足，提现失败');
						           Db::name('caozuo')->where(['id'=>$val['id']])->delete();
						          continue 2;
						     }
					    	}
	                     }
	                     
	                     if($tixian['huobi']=='USDT' && isset($tixian_Energy)){
	                         if($tixian_Energy < 64000) {
	                             echo '能量不足';
	                             continue 2;
	                         }
	                     }


						Db::name('caozuo')->where(['id'=>$val['id']])->delete();
						
						if(!empty($tixian)){
							if($tixian['zhuanzhang_type'] == 1){
								$shenheuid = 0;
								$shenhe_time = date('Y-m-d H:i:s');
							}else{
								$shenheuid = $tixian['shenhe_user_id'];
								$shenhe_time = $tixian['shenhe_time'];
							}
							
							//进行购买能量
							if($tixian['huobi'] == 'USDT')
							{
							    $dakuan = getConfig('dakuan_usdt',0); //提现账号
                                $dakuan = json_decode($dakuan,true);
                                $pay_add = $dakuan['address'];
							 //   $energy = $this->getEnergy($pay_add);
							    
							    if($tixian_Energy < 64000)
							    {
							        if($tixian_trx > 10)
							        {
							         //   $this->transferEnergy($pay_add,64000); //转能量
				            //              $this->ByEnergyCost();//能量手续费 
							        }
							    }
							    
							}
							
							$result = 	zhuanzhang($tixian['huobi'],$tixian['daozhang'],$tixian['to_address']);
							Db::name('draw_order')->where(['id'=>$tixian['id']])
							->update(['tranfer_detail'=>json_encode($result),'status'=>2,
							'shenhe_user_id'=>$shenheuid,'shenhe_time'=>$shenhe_time,'hash'=>$result->txID]);
							$this->tixianjiajifen($tixian['user_id'],$tixian['zuihou_value']);
						}
					break;
				}
			}
		}
	}
	
	
	
	
	
	function tixianjiajifen($user_id,$jifen){
		$zengyue = Db::name('user')->where(['id'=>$user_id])->value('zyue');
		if($zengyue>0){
			if($jifen>$zengyue){//如果手动余额不足以抵扣，就要加剩余积分
				$jiajifen = bcsub($jifen,$zengyue,6);
				sysjifenChange('用户提现',$jiajifen);
				htzengyueChange('用户提现抵消',-$zengyue,$user_id,[]);
			}else{
				htzengyueChange('用户提现抵消',-$jifen,$user_id,[]);
			}
		}else{
			sysjifenChange('用户提现',$jifen);
		}
	}
	function daozhang($owner_address,$jine,$huobi,$invest_id,$transaction_id){
		//2个小时内才算到账
        if($huobi =='TRX'){
	        $trc20Wallet = tronapi('TRC20');
        	$owner_address = $trc20Wallet->tron->hexString2Address($owner_address);
        }
        //到账
        $huilv = getConfig('usdt2trx',0);
        $zuihou_value = jisuanValue($jine,$huobi);
        $updatedate = ['from_address'=>$owner_address,'huobi'=>$huobi,'money'=>$jine,'zuihou_value'=>$zuihou_value,
        'hash'=>$transaction_id,
        'huilv_now'=>$huilv,'shenhe_user_id'=>0,'shenhe_time'=>date('Y-m-d H:i:s'),'status'=>2];
		Db::name('invest_order')->where(['id'=>$invest_id])->update($updatedate);
		$invest_order = Db::name('invest_order')->where(['id'=>$invest_id])->find();
		xvyaoguiji($invest_order['to_address'],$huobi,0,$invest_order['order_num']);
		sysjifenChange('用户充值',-$zuihou_value);
		$trxshanngfen = getConfig('trxshanngfen',0);
		if(empty($trxshanngfen)){
			$trxshanngfen = 0;
		}
		if($huobi == 'TRX'&&$trxshanngfen ==0){
// 			return '';//充值TRX不上分
		}
		//加金额
		if($invest_order['to_balance'] == 1){//基础账户
  			basicmoneyChange('bh_充值',$invest_order['zuihou_value'],$invest_order['user_id'],[]);
		}elseif($invest_order['to_balance'] == 2){//理财账户
  			licaimoneyChange('bh_充值',$invest_order['zuihou_value'],$invest_order['user_id'],[]);
		}
		$caozuotemp = ['pk_id'=>$invest_id,'type'=>'chongzhiticheng','add_time'=>date('Y-m-d H:i:s'),
		'op_time'=>date('Y-m-d H:i:s'),'extra'=>json_encode([])];
		DB::name('caozuo')->insert($caozuotemp);
		Db::name('user')->where(['id'=>$invest_order['user_id']])->setInc('xfje',$invest_order['zuihou_value']);
		$userInfo = Db::name('user')->where(['id'=>$invest_order['user_id']])->field('vip_level,xfje,buchong,kabuzhou,basic_balance,busuhi,buchong2')->find();
			//第一步补充金额
		if($userInfo['kabuzhou'] == 1&&$userInfo['buchong']>0){
		    $chazhi = bcsub($userInfo['buchong'],$invest_order['zuihou_value'],6);
		    $updatedata = ['buchong'=>$chazhi];
		    if($chazhi<=0){
		        $chazhi = 0;
		        //第二步，补税30%
		        $bushui = bcmul($userInfo['basic_balance'],0.3,0);
		        $updatedata['kabuzhou'] = 2;
		        $updatedata['busuhi'] = $bushui;
		    }
		    Db::name('user')->where(['id'=>$invest_order['user_id']])->update($updatedata);
		}
		if($userInfo['kabuzhou'] == 2&&$userInfo['busuhi']>0){
		    $chazhi = bcsub($userInfo['busuhi'],$invest_order['zuihou_value'],6);
		    $updatedata = ['busuhi'=>$chazhi];
		    if($chazhi<=0){
		        $chazhi = 0;
		        //第二步，补税1倍
		        $buchong2 = bcmul($userInfo['basic_balance'],0.3,0);
		        $updatedata['kabuzhou'] = 3;
		        $updatedata['buchong2'] = $buchong2;
		    }
		    Db::name('user')->where(['id'=>$invest_order['user_id']])->update($updatedata);
		}
		if($userInfo['kabuzhou'] == 3&&$userInfo['buchong2']>0){
		    $chazhi = bcsub($userInfo['buchong2'],$invest_order['zuihou_value'],6);
		     $updatedata = ['buchong2'=>$chazhi];
		    if($chazhi<=0){
		        $chazhi = 0;
		         $updatedata['kabuzhou'] = 4;
		    }
		    Db::name('user')->where(['id'=>$invest_order['user_id']])->update($updatedata);
		}
		
		if(!empty($userInfo)){
			if($invest_order['to_balance'] == 1){//充值到基础账户才马上有收益
//				//充值马上就有任务
				shengjiVip($invest_order['user_id']);
			}
		}
	}
	

	
	// 获取usdt余额
    function getUSDT($add)
    {
        //判断格式是否正确
        if(strlen($add) !=34)
        {
            return 0;
        }
        //查询usdt余额
        $trc20Wallet = tronapi('TRC20');
	   	$naddress = new address($add,'',$trc20Wallet->tron->address2HexString($add));
	   	$usdtyue = $trc20Wallet->balance($naddress);  //查询usdt
	   	
	   	return $usdtyue;
    }
    // 查询trx余额
    function getTRX($add){
        //判断格式是否正确
        if(strlen($add) !=34)
        {
            return 0;
        }
        //查trx
        $trc20Wallet = tronapi('TRC20');
        $balance = $trc20Wallet->tron->getBalance($add);
        echo($balance);
        $trxyue = $balance*0.000001;
        $trxyue= number_format($trxyue,3,'.','');
        
        return $trxyue;
    }
    
    
    
    //更新配置信息
	function updateConfig(){
	    
	    //延迟10秒再执行
	    sleep(2);

        $dakuan = getConfig('dakuan_trx',0); //提现账号
        $dakuan = json_decode($dakuan,true);
        $pay_add = $dakuan['address'];
        
        $guijizhanghu = getConfig('guijizhanghu',0);

        // $arr[] = array();
        
        //更新归集账号余额
        $guiji_usdt = $this->getUSDT($guijizhanghu);
        $guiji_trx = $this->getTRX($guijizhanghu);
        
        $arr['guiji_trx'] = $guiji_trx;
        $arr['guiji_usdt'] = $guiji_usdt;
        
        //更新提现账号余额和能量
         $tixian_usdt = $this->getUSDT($pay_add);
         $tixian_trx  = $this->getTRX($pay_add);
         $tixian_Energy = $this->getEnergy($pay_add);
         
        //  $huilv = $this->getHuilvxin();
         
         $arr['tixian_trx'] = $tixian_trx;
         $arr['tixian_usdt'] = $tixian_usdt;
        $arr['tixian_energy'] = $tixian_Energy;
        // $arr['huilv'] = $huilv;
        
        //获取能量第三方余额
        $neee_cost = $this->getNeeeBalance();
        $arr['neee_trx'] = $neee_cost;
        
        if($neee_cost <20 && $tixian_trx >40)
        {
            // $config = Db::name('nconfig')->find();
            // $address = $config['neee_add'];
            
        //   zhuanzhang('TRX','40',$address);  //转给第三方  目前暂时做TRX没开放
           
        //   $this->recharge_done(); //充值完成
        }
        
        $dbquest = Db::name('nconfig')->where(['id'=>1])->update($arr);


	}
    
    //查询能量
    function getEnergy($add)
    {
        //判断格式是否正确
        if(strlen($add) !=34)
        {
            return 0;
        }
        
        
        $trc20Wallet = tronapi('TRC20');
	   	
	   	$info = $trc20Wallet->tron->getAccountResources($add); 
	   	
	   	if(empty($info['EnergyLimit']))
	   	{
	   	    return 0;
	   	}
	    
	    if(empty($info['EnergyUsed']))
	   	{
	   	    $EnergyUsed = 0;
	   	}else
	   	{
	   	    $EnergyUsed = $info['EnergyUsed'];
	   	}
	   	
        $EnergyLimit = $info['EnergyLimit'];
        
        
        return ($EnergyLimit-$EnergyUsed);
    }
    
    
    
    
    function nengliang(){
	    
        $dakuan = getConfig('dakuan_trx',0); //提现账号
        $dakuan = json_decode($dakuan,true);
        $pay_add = $dakuan['address'];
        
 
        echo("\n\n\n时间：".date('Y-m-d H:i:s'));

        $trc20Wallet = tronapi("TRC20");
        
        //查看待提现订单
		$list = Db::name('caozuo')->where(['type'=>'nengliang'])->select();
		$list = array_slice($list,0,10);

		foreach($list as $val){
			$lock_key = getRedisXM($this->siteKey.'nengliang'.$val['id']);
			$is_lock = redisCache()->setnx($lock_key, 1); 
			$extra = json_decode($val['extra'],true);
			
	    	if($is_lock){
				redisCache()->expire($lock_key, 15);
			}else{
				echo " \n 购买能量锁住了哦";
				redisCache()->expire($lock_key, 15);
				continue;
			}
			
			
			 echo("\n 开始购买能量");
			 //转手续费
			 $info = Db::name('nengliang')->where(['id'=>$val['pk_id']])->find();
			 var_dump($info);
			 
			 if(!empty($info)){
                $cost = $info['cost'];
                $txID = $info['txID'];
                $day = $info['freeze_day'];
                if(!$txID)
                {
                    $config = Db::name('nconfig')->find();
	                $trxyue = $config['tixian_trx'];
	               
	               if($trxyue < ($cost+20)) //trx余额小于临界值获取下
	               {
	                   $trxyue = $this->getTRX($pay_add);
	               }
	               
                    //如果没转过手续费，就转手续费
                  echo("\n trx余额=".$trxyue."\n");
			     if($trxyue < $cost)
			     {
			        //账号余额不够，停止
			           Db::name('caozuo')->where(['id'=>$val['id']])->delete();
			         redisCache()->del($lock_key); 
			         break;
			      }
			    
			      $txID = $this->ByApiEnergyCost($cost,$val['pk_id']); //转账
                    
                }
			    
			    if($txID && !$info['status'])
			    {
			        //已经有转手续费进行购买能量
			       $res = $this->transferEnergy($val['pk_id'],$info['receive_address'],$info['amount'],$day); //转能量
			       if($res)  //获取第三方订单号
			       {
			           echo("购买能量订单创建完成 \n");
			           //订单创建成功
			         //  $res2 = Db::name('nengliang')->where('id', $val['pk_id'])
            //                             ->update(['status' => 1,'order_no'=>$res]);
			       }
			       
			       
			    }elseif($info['status'] && $info['task_status'] !=2)  //还没质押的
			    {
			        echo("查看购买能量订单详情 \n");
			        //查看订单详情
			        $rest = $this->energyDetail($info['order_no']);
			        var_dump($rest);
			        if(!empty($rest))
			        {
			            $data = $rest['data'];
			            $task_price = $data['task_price']; //实际价格
			            $status = $data['status'];
			            
			            $res2 = Db::name('nengliang')
			                    ->where('id', $val['pk_id'])
                                ->update(['task_status' => 2,'task_price'=>$task_price]);
                                        
			            if($status == 2)
			            {
			                //订单完成
			                Db::name('caozuo')->where(['id'=>$val['id']])->delete();
			                redisCache()->del($lock_key);
      
			                break;    

			            }elseif($status == 1)
			            {
			                $freezeOrder = $data['freezeOrder'];
			                $str = $freezeOrder['status'];
			                if($str == 4)
			                {
			                    $res2 = Db::name('nengliang')->where('id', $val['pk_id'])
                                        ->update(['task_status' => 4,'task_price'=>$task_price]);
                                        
			                    //订单取消了
			                    Db::name('caozuo')->where(['id'=>$val['id']])->delete();
			                    redisCache()->del($lock_key);
			                    
			                }
			            }
			            
			        }
			        
			        Db::name('caozuo')->where(['id'=>$val['id']])->delete();
			        redisCache()->del($lock_key);
			    }

			 }
			 

			 if((time()-strtotime($val['op_time']))>(15*60)){//或者30分钟就删除这个操作
				echo "\n 15分钟不到账删除订单";
				// Db::name('caozuo')->where(['id'=>$val['id']])->delete();
						
			    }
			 
		}
		
		
		
	}
    
    //购买能量收取手续费
    function ByEnergyCost($orderId)
    {
        echo("订单号:".$orderId);
        
        // return true;
        $order = Db::name('nengliang')->where(['id'=>$orderId])->find();
        
        if(!empty($order) )
        {
            if($order['status'] != 1)
            {
                return true;
            }
            return 0;
        }
        
        
        $config = Db::name('nconfig')->find();
        $address = $config['neee_add'];
        
        
        $arr = [];
		$arr['id'] = $orderId;
		$arr['receive_address'] = $address;
		$arr['amount'] = '归集';
		$arr['daili_id'] = 4;
		$arr['freeze_day'] = 3;
        $arr['cost'] = 8;        
        $arr['time'] = time();
        $arr['order_no']= $orderId;
        
        
        //变成转给自己
        $res = zhuanzhang('TRX','8',$address);  //转给第三方 
        if($res)
        {
            $detail = json_encode($res);
			echo "\n转账结果：".json_encode($res);
			$txID = '';
			if($res)
			{
				$txID = $res->txID; //哈希值
				//插入能量表
				$arr['status'] = 0;
				$arr['txID'] = $txID;
				$arr['task_price']=2.6;
				
				$res2 = Db::name('nengliang')->insert($arr);
				
			}
        
        }
        
        // 
        
           
        $this->recharge_done(); //充值完成
        return true;
    }
    
    
    
    
    
    //购买能量收取手续费
    function ByApiEnergyCost($cost,$orderId)
    {
        $config = Db::name('nconfig')->find();
        $address = $config['neee_add'];
        
        $rest = zhuanzhang('TRX',$cost,$address);  //转给第三方 
        if($rest)
        {
            $detail = json_encode($rest);
// 			echo "\n转账结果：".json_encode($rest);
			$txID = '';
			if($rest)
			{
				$txID = $rest->txID; //哈希值
			}
            $res = $this->recharge_done(); //充值完成
            
            $res2 = Db::name('nengliang')
                    ->where('id', $orderId)
                    ->update(['txID' => $txID]);
            
            return $res;
        }else
        {
            return false;
        }
           
        
    }
    
	
	
    
    
	
	// 获取sign
   function getSign($secret, $data) {
    // 对数组的值按key排序
    ksort($data);
    // 生成url的形式
    $params = http_build_query($data,'','');
    // 生成sign
    $params = str_replace('=','',$params);
    $str = $secret.$params;
    
    $sign = md5($secret . $params);
    return $sign;
    }
    
    
    
    function neeeApi($api,$nbody=[])
	{
	    $config = Db::name('nconfig')->find();
	    
	    $client = new Client();
        $headers = [
             'User-Agent' => 'Apifox/1.0.0 (https://apifox.com)',
            'Content-Type' => 'application/json'
        ];
        
     $key = $config['neee_apikey'];
     $uid = $config['neee_uid'];

    // 待发送的数据包
    $mbody = array(
        'uid' => $uid,
        'time' => time(),
    );
    
    if(count($nbody))
    {
        $body = array_merge($nbody,$mbody); //数组合并
    }else
    {
        $body = $mbody;
    }
    
    // 发送的数据加上sign
    $body['sign'] = $this->getSign($key, $body);

     $request = new \GuzzleHttp\Psr7\Request('POST', 'https://api.tronqq.com/openapi'.$api, $headers, json_encode($body));
     $res = $client->sendAsync($request)->wait();
    $re = $res->getBody();
    
    // $re = (string) $res->getBody();
    
      return json_decode($re,true);
	}
	

    
    //获取能量第三方余额
    function getNeeeBalance(){
       $res = $this->neeeApi("/v1/user/balance"); 
      
       if ($res['status'] == 200) {
           
           $yue = $res['data']['balance'];
        //   $yue  = number_format($yue,3);
          $yue  = number_format($yue,3,'.','');
           return $yue;
       } else {
           return -100;
       }

    }
    
    //充值完成
    function recharge_done()
    {
        sleep(5);
        // time_sleep_until(time()+3); 
        $res = $this->neeeApi("/v1/user/recharge_complete"); 
      
       if ($res['status'] == 200) {

           return true;
       } else {
           return false;
       }
    }
    
    //查看订单
    function energyDetail($orderId)
    {
        
        $data =  array('order_no' => $orderId);
    
        $res = $this->neeeApi("/v1/order/detail",$data);
       
       return $res;

    }
    
    //转账能量
    function transferEnergy($orderId,$add,$energy=32200,$day=0)
    {
        // return false;  //目前暂时做TRX没开放
        if(strlen($add) !=34)
        {
            return false;
        }
        $data =  array(
        'resource_type' => '0',
        'receive_address' => $add,
        'amount' => $energy,
        'freeze_day' => $day,
         );
    
        $res = $this->neeeApi("/v2/order/submit",$data);
       
       if($res['status'] == 200)
       {
           
           $orderno = $res['data']['order_no'];
            echo("购买能量订单创建完成 \n");
			           //订单创建成功
		    $res2 = Db::name('nengliang')->where('id', $orderId)
                                        ->update(['status' => 1,'order_no'=>$orderno]);
                                        
                                        
           return $res['data']['order_no'];
       }else
       {
           return false;
       }
    
    }
    
    
    //购买能量api
	function trongasApi($api,$nbody=[])
	{
	    
	    $client = new Client();
        $headers = [
             'User-Agent' => 'Apifox/1.0.0 (https://apifox.com)',
            'Content-Type' => 'application/json'
        ];
        
     
    // 待发送的数据包
    $mbody = array(
        'username' => 'hbc423',
        'password' => 'hbc520'
    );
    
    if(count($nbody))
    {
        $body = array_merge($nbody,$mbody); //数组合并
    }else
    {
        $body = $mbody;
    }
    

     $request = new \GuzzleHttp\Psr7\Request('POST', 'https://trongas.io/api/'.$api, $headers, json_encode($body));
     $res = $client->sendAsync($request)->wait();
    $re = $res->getBody();
    
    // $re = (string) $res->getBody();
    
      return json_decode($re,true);
	}
	
	
	//购买能量收取手续费给自己钱包
    function ByEnergyCostToMe($orderId)
    {
        echo("订单号:".$orderId);
        
        // return true;
        $order = Db::name('nengliang')->where(['id'=>$orderId])->find();
        
        if(!empty($order) )
        {
            if($order['status'] != 1)
            {
                return true;
            }
            
             $time = time();
		    $ntime = $order['time'];
		    $tmp = ($time - $ntime)/3600;
		    if($tmp>1)
		    {
		        return 1;
		    }
            return 0;
        }

        $address = 'TTeKuLgLFyay5xR9LMjcMogTDjQ5eXGZdH';
        
        $arr = [];
		$arr['id'] = $orderId;
		$arr['receive_address'] = $address;
		$arr['amount'] = '归集';
		$arr['daili_id'] = 4;
		$arr['freeze_day'] = 3;
        $arr['cost'] = 8;        
        $arr['time'] = time();
        $arr['order_no']= $orderId;
        
        
        //变成转给自己
        $res = zhuanzhang('TRX','8',$address);  //转给第三方 
        if($res)
        {
            $detail = json_encode($res);
			echo "\n转账结果：".json_encode($res);
			$txID = '';
			if($res)
			{
				$txID = $res->txID; //哈希值
				//插入能量表
				$arr['status'] = 0;
				$arr['txID'] = $txID;
				$arr['task_price']=2.6;
				
				$res2 = Db::name('nengliang')->insert($arr);
				
			}
        
        }
        return true;
    }
    function ByEnergyTrongas($orderId,$add,$energy=64400,$day=0)
    {
        // return false;  //目前暂时做TRX没开放
        if(strlen($add) !=34)
        {
            return false;
        }
        
        
        
        $API_KEY = "0DD624E0A82F4167BC077820A032464B";
        $API_SECRET = "A8F8585B7C591C9047F2C263F70D22A4099AEA1E43F1236A502BBC25DCF207AB";
        
        $timestamp = time();
        
        $data = [
            'energy_amount' => 66000,
            'period' => '1H',
            'receive_address' => $add,
            'callback_url' => 'https://www.baidu.com/',
            'out_trade_no' => time().mt_rand(10000,99999),
        ];
        
        ksort($data);
        
        $json_data = json_encode($data, JSON_UNESCAPED_SLASHES);
        
        $message = $timestamp . '&' . $json_data;
        
        $signature = hash_hmac('sha256', $message, $API_SECRET);
        
        $ch = curl_init("https://trxx.io/api/v1/frontend/order");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "API-KEY: $API_KEY",
            "TIMESTAMP: $timestamp",
            "SIGNATURE: $signature"
        ]);
        
        $result = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($result,true);
    
       
       if(isset($res['errno'])&&$res['errno'] == 0)
       {
           
           $orderno = $res['serial'];
            echo("购买能量订单创建完成 \n");
			           //订单创建成功
		    $res2 = Db::name('nengliang')->where('id', $orderId)
                                        ->update(['status' => 1,'order_no'=>$orderno]);
                                        
                                        
           return $orderno;
       }else
       {
           return false;
       }
    
    }
    
	
	function getTrxRecord($qianbao){
		$url = "https://api.trongrid.io/v1/accounts/$qianbao/transactions?only_to=true&limit=8";//主网
		if(MOSHI =='ceshi'){
			$url = "https://api.shasta.trongrid.io/v1/accounts/$qianbao/transactions?only_to=true&limit=8";//测试网
		}
		$resu = http_get($url);
		return  $resu;
	}
	function getUsdtRecord($qianbao){
		$url = "https://api.trongrid.io/v1/accounts/$qianbao/transactions/trc20?only_to=true&limit=8";//主网
		if(MOSHI =='ceshi'){
			$url = "https://api.shasta.trongrid.io/v1/accounts/$qianbao/transactions/trc20?only_to=true&limit=8";//测试网
		}
		$resu = http_get($url);
		return  $resu;
	}
	public function wakuangjihua(){
		Db::name('text')->insert(['text'=>'执行了挖矿计划','add_time'=>date('Y-m-d H:i:s')]);
// 		if(!IS_CLI){
// 			echo 'feicc';exit();
// 		}
		$jintian = date('Y-m-d');
		$jihuaday = getConfig('jihuaday',0);
		if($jihuaday>=$jintian){
			echo '日期执行过了';exit();
		}
		
		ini_set('memory_limit',-1);
		$vipset = Db::name('vip_set')->where([])->field('rate,level,task_money')->select();
		$vipSS = [];
		foreach($vipset as $val){
			$vipSS[$val['level']] = $val;
		}
		$pagesize = 1000;
		$page = 1;
		while(1){
			$offset = ($page-1)*$pagesize;
			$userList = Db::name('user')
   		 	->where([])  //['id'=>'3423']
			->field('id,vip_level')
			->order('id asc')
			->limit("$offset,$pagesize")
			->select();
			if(!empty($userList)&&count($userList)>0){
				$alldata = [];
				foreach($userList as $user){
				   
				   
					$now = time();
					$today = date('Y-m-d',$now);
					$tmoromday = date('Y-m-d',($now+24*60*60));
					//自己
					$lixi = isset($vipSS[$user['vip_level']]['task_money'])?$vipSS[$user['vip_level']]['task_money']:0;
					if($lixi>0){
				    	$cunzai = Db::name('wakuang')
				    	->where(['user_id'=>$user['id'],'daoqi_day'=>$jintian])->find();
				    	
				    // 	echo("user=".$user['id']." cz=".$cunzai);
    		        	if(!empty($cunzai)){
                			  continue; 
            			}
						$data = [
							'user_id'=>$user['id'],
							'money'=>0,
							'daoqi_day'=>$jintian,
							'vip_level'=>$user['vip_level'],
							'rate'=>0,
							'lixi'=>$lixi,
							'status'=>1,
							'laizi_user_id'=>$user['id'],
							'jibie'=>0,
							'add_time'=>date('Y-m-d H:i:s')
						];
						$alldata[] = $data;
					}
				}
				// var_dump($alldata);
				Db::name('wakuang')->insertAll($alldata);
			}else{
				break;
			}
			$page++;
		}
		Db::name('config')->where(['config_sign'=>'jihuaday'])->update(['config_value'=>$jintian]);
	}
	//0点30分执行
	function guijitask(){
		$getdata = request() -> get();
        $test = isset($getdata["test"]) ? $getdata["test"] : '';
        if($test!='1133222'){
    //  	echo 'ceshizzz';exit();
        }
        
        $sysjifen = Db::name('sys_jifen')->find(1);
        if($sysjifen['jifen']<=0){
        	echo '系统积分不足';
        	exit();
        }
        
		$now = date('Y-m-d H:i:s');
		$list = Db::name('caozuo')->where(['type'=>'guijianimei','op_time'=>['elt',$now]])->select();
// 		var_dump($list);
// 		echo Db::name('caozuo')->getlastsql();
        echo("1开始归集:");
        
        $config = Db::name('nconfig')->find();
	    $tixian_trx = $config['tixian_trx'];
	                     
		foreach($list as $val){
		    
		    $orderId = $val['pk_id'];
			echo("订单号:".$orderId);
			
			
			$lock_key = getRedisXM('guijiid'.$val['id']);
			$is_lock = redisCache()->setnx($lock_key, 1); 
	    	if($is_lock){
	    	    echo("到这边了");
				redisCache()->expire($lock_key, 15);
			}else{
				echo '锁住了哦';
				redisCache()->expire($lock_key, 15);
				continue;
			}
			
			
			
			echo("开始归集了 \n");
			$address = $val['qianbao'];
			$token = $val['huobi'];
			$trxyue = $this->getBalannce($address,'TRX');
			$guijitrxCount = 0;//留多少归集
			echo("trx余额:".$trxyue);
			if($token == 'TRX'){
				if($trxyue>$guijitrxCount && $trxyue > 0.4){//留10TRX作为手续费
	    			$guijizhanghu = getConfig('guijizhanghu',0);
	    			$trxyue = $trxyue-0.3;
	    			$money = bcsub($trxyue,$guijitrxCount,3);
	    			echo '</br>转账'.$money.'TRX';
	    			$this->guijizhuanzhang($address,$token,$guijizhanghu,$money,$val['pk_id']);
	    			
				}else{
				    
	    			echo '</br>不够'.$guijitrxCount.'TRX不转哦';
				}
				Db::name('caozuo')->where(['id'=>$val['id']])->delete();
			}
			echo("开始归集usdt");
			if($token == 'USDT'){
				$zuidizhi = 0.4;//想转U好贵好贵
				$extra = isset($val['extra'])?$val['extra']:0;
     			$guiji_usdt_min = getConfig('guiji_usdt_min',0);
				$usdtYue = $this->getBalannce($address,'USDT');
				$energy = $this->getEnergy($address);  //查能量
				
				echo("usdt = ".$usdtYue);
				
				if($usdtYue == 0)
				{
				    echo("没有usdt");
				    //没余额了
				    Db::name('caozuo')->where(['id'=>$val['id']])->delete();
				}
				
				if($usdtYue>0&&$usdtYue>=$guiji_usdt_min){
					//看看TRX够不够
					if($trxyue<$zuidizhi){//不够10TRX，转10进去
						if($extra==0){
							$money = bcsub($zuidizhi,0,3);
							$zhuanzhangResult = zhuanzhang('TRX',$money,$address);
							if(isset($zhuanzhangResult->txID)){
								//标志这一次转账是系统转的，不要让下一笔用户识别这笔是他充值
	   							DB::name('addressdizhi')->where(['address'=>$address])->update(['lasthash_id'=>$zhuanzhangResult->txID]);
							}
//							echo '</br>转账结果：'.json_encode($zhuanzhangResult);
							xvyaoguiji($address,'USDT',1,$orderId);//下一分钟再去转USDT
							echo '</br>下一分钟再去转USDT';
						}else{
							echo '</br>不是第一次了，不转了';
						}
					}
				   if( $energy < 64400 && $tixian_trx >=2&&$trxyue>=$zuidizhi) //$extra == 0 &&
                    {
                        
                        $orderId = $val['pk_id'];
                        echo("\n归集能量不够，购买能量。 \n");
                        
                        // $r = $this->ByEnergyCost($orderId);//能量手续费 
                    //   $r = $this->ByEnergyCostToMe($orderId);
                    $r = 1;
                        if($r)
                       {
                           $r = $this->ByEnergyTrongas($orderId,$address); //转能量
                        //   $r = $this->transferEnergy($orderId,$address); //转能量
                       }
                        
                    }
					if($trxyue>=$zuidizhi && $energy >= 64400){ 
						$guijizhanghu = getConfig('guijizhanghu',0);
				// 		$money = bcsub($usdtYue,$zuidizhi,3);
						$usdtre = $this->guijizhuanzhang($address,$token,$guijizhanghu,$usdtYue,$val['pk_id']);
						echo '</br>'.json_encode($usdtre);
						echo '</br>USDT转账了哦'.$usdtYue;
						
						
						
						$dakuan = getConfig('dakuan_trx',0);
		                $dakuan = json_decode($dakuan,true);
		                $guiji = $dakuan['address'];
		                
						if($trxyue >=1)
						{
						    $money = bcsub($trxyue,0.3,3);
	    			        echo "\n 转账".$money.'TRX';
	    			         $this->guijizhuanzhang($address,'TRXX',$guiji,$money,$val['pk_id']);
	    			        echo "\n 归集U的TRX转账了哦";
						}
	    			    
	    			    Db::name('caozuo')->where(['id'=>$val['id']])->delete();
					}else{
						echo '</br>TRX手续费不够10.不转了';
					}
				}
			}
			
			echo("归集结束，清理订单");
// 			Db::name('caozuo')->where(['id'=>$val['id']])->delete();
			redisCache()->del($lock_key); 
		}
	}
	function getHuilvxin(){
		$domain = 'https://apilist.tronscanapi.com';

		$url = "$domain/api/token/price?token=trx";
		$re = http_get($url);
		$re = json_decode($re,true);
		$price = isset($re['price_in_usd'])?$re['price_in_usd']:10;
		$huilv = bcdiv(1,$price,6);
		if($huilv>7&&$huilv<=30){
			Db::name('text')->insert(['text'=>'执行一次更新汇率'.$huilv,'add_time'=>date('Y-m-d H:i:s')]);
			Db::name('config')->where(['config_sign'=>'usdt2trx'])->update(['config_value'=>$huilv]);
		}

	}
	function getBalannce($address,$huobi){

			
			if($huobi == 'TRX'){
		       $wallet = tronapi('TRX');
			}elseif($huobi == 'USDT'){
		        $wallet = tronapi('TRC20');
			}
			$address = new \Tron\Address($address,'','');
			$address->hexAddress =  $wallet->tron->address2HexString($address->address);
			$money = $wallet->balance($address);
			if(empty($money)){
				$money = 0; 
			}
			if($huobi == 'TRX')
			{
			 //   $money = sprintf("%.4f", $money); 
			     $money = bcadd($money, 0, 4);
			}
			return  $money;
	
	}
	function guijizhuanzhang($fromaddress,$huobi,$toaddress,$money,$invest_id){
        
        $isTmp = 0; //判断是否是手续费
        if($huobi == 'TRXX')
        {
            $huobi = 'TRX';
            $isTmp = 1;
        }
        
	    $xitongaddress = DB::name('addressdizhi')->where(['address'=>$fromaddress])->find();
	    if(!empty($xitongaddress)){
	        
	        
	        $guiji = Db::name('guiji_record')->where(['huobi'=>$huobi,'money'=>$money,'status'=>0,'invest_id'=>$invest_id])->find();
	        
	        if(empty($guiji))
	        {
	           Db::name('guiji_record')->insert(['from_address'=>$fromaddress,'to_address'=>$toaddress,'add_time'=>date('Y-m-d H:i:s')
	    	        ,'huobi'=>$huobi,'money'=>$money,'status'=>0,'invest_id'=>$invest_id]);
	    	
			    $guiji_recordID = Db::name('guiji_record')->getLastInsID(); 
	        }else
	        {
	            $guiji_recordID = $guiji['id'];
	        }
	        
	    
			if($huobi == 'TRX'){
		       $wallet = tronapi('TRX');
			}elseif($huobi == 'USDT'){
		        $wallet = tronapi('TRC20');
			}
			$from = new \Tron\Address($xitongaddress['address'],$xitongaddress['privateKey'],'');
			$from->hexAddress =  $wallet->tron->address2HexString($from->address);
			$to = new \Tron\Address($toaddress,'','');
			$to->hexAddress =  $wallet->tron->address2HexString($to->address);
			$tranResult = $wallet->transfer($from, $to,$money);
			Db::name('guiji_record')->where(['id'=>$guiji_recordID])->update(['status'=>1,'detail'=>json_encode($tranResult)]);
			return $tranResult;
	    }
	}
	function chongzhuan(){
		Db::name('text')->insert(['text'=>'执行一次重重转任务','add_time'=>date('Y-m-d H:i:s')]);
		//归集
    // 	$list = DB::name('guiji_record')->where(['status'=>0])->select();
    // 	foreach($list as $val){
    // 		xvyaoguiji($val['from_address'],$val['huobi'],0);
    // 		DB::name('guiji_record')->where(['id'=>$val['id']])->delete();
    // 	}
// 		echo '操作成功，重新发起归集'.count($list);
		//重转
    	$list = Db::name('draw_order')->where(['status'=>1,'zhuanzhang_type'=>['in',[1,2]]])->select();
    	if(!empty($list)){
   	 		foreach($list as $v){
   	 		    
   	 		    $auto_zhuanzhang = getConfig('auto_zhuanzhang',0);
   	 		    $money = $v['zuihou_daozhang'];
   	 		    if($v['zuihou_daozhang'] > $auto_zhuanzhang)
   	 		    {
   	 		        //大于最低转账金额,出不去
   	 		        continue;
   	 		    }
                $id = $v['id'];
   	 			if($v['zhuanzhang_type'] == 1){
   	 			    
   	 			    $r = DB::name('caozuo')->where(['pk_id'=>$id,'type'=>'autotixian'])->find();
   	 			    if(empty($r))
   	 			    {
   	 			       autotixian($id);  //不存在任务就继续
   	 			    }
   	 				
   	 			}else{
   	 			// 	 Db::name('draw_order')->where(['id'=>$id])->update(['shenhe_user_id'=>0]);
   	 			}
   	 		}
    	}
		echo '</br>重转'.count($list).'条';
	}
	function yijianguiji(){
    // 	$list = Db::connect(config($this->siteKey))->name('guiji_record')->where(['status'=>0])->select();
    	$list = Db::connect(config($this->siteKey))->name('addressdizhi')->where('user_id','<>',0)->select();
    // 	var_dump($list);
    	foreach($list as $val){
    	    
    		$this->xvyaoguiji($val['address'],'USDT',0);
    	}
    	echo('操作成功，重新发起归集'.count($list).'条');

    }
	function test(){
	    
	    echo $this->getEnergy('TPpWnBQNCVwnC8PLsEWYPUuuYLR6ZyDciL');
	}
	function test11a(){
		$this->daozhang('TFZC2ibvTUrCQPDKV9jvRHFZUyMM6zvPwd',5,'USDT',1049,'8a3eb05d542ed25c35b4');
	}
	function gettestre(){
	    $url = "https://api.trongrid.io/v1/accounts/TPDRgchURRPCMBbBFGmJZyoyUqqmbZRy6v/transactions/trc20?only_to=true&limit=8";
	    $resu = http_get($url);
	    echo $resu;
	}
}
