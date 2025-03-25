<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Db;
class Useraction extends Base {
	private $table = 'user_action_log';
	private $order = 'add_time desc';
    public function main(){
        // exit();
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'user_id','chinaname'=>'用户','style'=>''],
    		['col'=>'ca','chinaname'=>'操作','style'=>''],
    		['col'=>'add_time','chinaname'=>'时间','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'email'=>['chinaname'=>'email/手机','ca'=>'like','type'=>'text','style'=>''],
    		'ca'=>['chinaname'=>'操作','ca'=>'eq','type'=>'select',
    		'selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'User/login','text'=>'登录'],['id'=>'Invest/postdraw','text'=>'发起提现']
    		,['id'=>'Invest/postinvest','text'=>'发起充值']],'style'=>'']
    	];
    	$where = $this->getWhere($searchCol);
    		//有无充值
    	$biaozhi = [];
    	$orwhere = 'user.id >0';
    	if(isset($_GET['email'])){
    		$orwhere .= ' AND(email LIKE "%'.$_GET['email'].'%" OR tel like "%'.$_GET['email'].'%")';
    		$biaozhi['email'] = $_GET['email'];
    		unset($where['email']);
    	}
    	
    	$notin = ['Op/getmyinfo','User/countrycode','Main/changelang','Main/languagelist','Main/menutext'];
    	if(isset($where['ca'])){
    		 $where['ca'] = $where['ca'];
    	}else{
    		$where['ca'] = ['notin',$notin];
    	}
    	//***********************************************整理搜索字段
   	 	$list = Db::name($this->table)
 		->alias('action')->join('user user','user.id = action.user_id')
   	 	->field('action.*,user.email,user.tel,user.id as userid')
   	 	->where($where)->where($orwhere)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
// 	 	echo Db::name($this->table)->getlastsql();
   	 	$cabiao = [
   	 	'Main/maindata'=>'加载首页',
   	 	'Invest/drawinfo'=>'进入充值页面',
   	 	'Licai/licai'=>'进入理财页面',
   	 	'Invest/caiwujilu'=>'查看财务记录',
   	 	'User/login'=>'登录',
   	 	'Op/yaoqingdata'=>'查看团队数据',
   	 	'Invest/investinfo'=>'打开充值页面',
   	 	'Invest/zhuanzhang'=>'发起转账',
   	 	'Invest/drawinfo'=>'打开提现页面',
   	 	'Invest/investinfo'=>'打开充值页面',
   	 	'Invest/postinvest'=>'发起充值请求',
   	 	'Invest/postdraw'=>'发起提现请求',
   	 	'Op/vipdata'=>'查看VIP数据',
   	 	'Team/myteam'=>'查看我的团队数据',
   	 	'Op/zhuanzhangdata'=>'打开转账界面',
   	 	'Op/loginout'=>'退出登录',
   	 	'Main/changjianwenti'=>'查看常见问题',
   	 	'Main/kefuurl'=>'打开联系客服页面',
   	 	'Licai/licaidetail'=>'查看单个理财详情',
   	 	'Invest/postdraw'=>'发起提现请求',
   	 	'Lottery/roomlist'=>'打开房间列表',
   	 	'Lottery/lotterydetail'=>'进入游戏界面',
   	 	'Lottery/choujiangrecord'=>'查看自己的抽奖记录',
   	 	'Lottery/choujiang'=>'抽奖',
   	 	'Task/getmylist'=>'查看任务',
   	 	'Op/vipnewdata'=>'查看VIP信息',
   	 	
   	 			
   	 	
   	 	];
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		if(in_array($v['ca'],$notin)){
   	 			unset($list[$k]);
   	 			continue;
   	 		}
   	 		$id = $v['id'];
			$list[$k]['user_id'] .='</br>'.$v['email'].'</br>'.$v['tel'];
			$list[$k]['ca'] = isset($cabiao[$v['ca']])?$cabiao[$v['ca']]:$v['ca'];
        }
        if(!empty($biaozhi)){
    		foreach($biaozhi as $k=>$v){
    			$where[$k] = $v;
    		}
    	}
        $addshow = 0;
        $searchshow = 1;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
}