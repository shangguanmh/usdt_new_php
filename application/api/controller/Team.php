<?php
namespace app\api\controller;
use think\ Db;
use think\Request;
use think\Controller;
use think\Lang;
class Team extends Base {
 	public function myteam(){
   		$postdata = request() -> post();
        $level = isset($postdata["level"]) ? $postdata["level"] : 1;
		if(!array($level,[1,2,3])){
			feifaReturn(1);
		}
    	$yilist = Db::name('user')->where(['from_who'=>$this->userInfo['id']])->field('id')->select();
		$targetID = array_column($yilist,'id');
		$targetID[] = '-1';//加上防止空数组查处所有
		if($level == 2||$level == 3){
    		$erjiList = Db::name('user')->where(['from_who'=>['in',$targetID]])->field('id')->select();
			$targetID = array_column($erjiList,'id');
			$targetID[] = '-1';//加上防止空数组查处所有
			if($level == 3){
	    		$sanjiList = Db::name('user')->where(['from_who'=>['in',$targetID]])->field('id')->select();
				$targetID = array_column($sanjiList,'id');
			}
		}
		if(empty($targetID)){
			$targetID = [-1];
		}
		$list = Db::name('user')->where(['id'=>['in',$targetID]])->field('id,email,tel,add_time,xfje')->paginate(10);
		$list = json_encode($list);
		$list = json_decode($list,true);
		foreach($list['data'] as $k=>$val){
			$nikname = !empty($val['email'])?$val['email']:$val['tel'];
			if(strlen($nikname)>=4){
				$nikname = substr($nikname,0,2).'****'.substr($nikname,-2);
			}elseif(strlen($nikname)>=2){
				$nikname = substr($nikname,0,2).'****';
			}
			$list['data'][$k]['nikname'] = $nikname;
			$list['data'][$k]['add_time'] = date('Y/m/d',strtotime($val['add_time']));
			$list['data'][$k]['chongzhi'] = round($val['xfje'],2);
			$list['data'][$k]['fanli_wakuang'] =  round(Db::name('wakuang')
			->where(['user_id'=>$this->userInfo['id'],'laizi_user_id'=>$val['id'],'status'=>2])
			->sum('money'),2);
			$list['data'][$k]['fanli_chongzhi'] = round(Db::name('chognzhifanli')
			->where(['user_id'=>$this->userInfo['id'],'laizi_user_id'=>$val['id']])
			->sum('money'),2);
			unset($list['data'][$k]['email']);
			unset($list['data'][$k]['tel']);
		}
		//文字
	    $ziduan = ['col_充值金额2','col_充值返利','col_挖矿返利','col_账户','col_财务','col_加入时间'];
	    $col = getcol($ziduan);
		$list['text'] = $col;
		unset($list['per_page']);
        ajaxReturn(1, '成功',$list);
    }
}



























