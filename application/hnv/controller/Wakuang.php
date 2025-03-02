<?php
namespace app\hnv\controller;
use think\Controller;
use think\Request;
use think\Db;
class Wakuang extends Base {
	private $table = 'wakuang';
	private $order = 'daoqi_day desc';
	private $addCol = [];
	private $changeCol = [];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'user','chinaname'=>'用户','style'=>''],
    		['col'=>'laiyuan','chinaname'=>'来源','style'=>''],
    		['col'=>'money','chinaname'=>'挖矿本金','style'=>''],
    		['col'=>'rate','chinaname'=>'收益率','style'=>''],
    		['col'=>'lixi','chinaname'=>'利息','style'=>''],
    		['col'=>'daoqi_day','chinaname'=>'返息时间','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'email'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
    		'status'=>['chinaname'=>'状态','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择'],['id'=>'1','text'=>'未兑付'],['id'=>'2','text'=>'已兑付']],'style'=>'']
    	];
    	$where = $this->getWhere($searchCol);
    	//***********************************************整理搜索字段
    	if(isset($where['status'])){
    		$where['wakuang.status'] = $where['status'];
    		unset($where['status']);
    	}
   	 	$list = Db::name($this->table)
 		->alias('wakuang')->join('user user','user.id = wakuang.user_id')
 		->field('wakuang.*,user.email')
   	 	->where($where)->order('daoqi_day desc')->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$userinfo = Db::name('user')->find($v['user_id']);
   	 		$laiziuserinfo = Db::name('user')->find($v['laizi_user_id']);
			$list[$k]['user']  =$userinfo['email'];
			$laiyuan = '自己';
			$shouyilv = '';
			if($v['jibie'] == 0){
				$shouyilv = $v['rate'].'%';
				$laiyuan = '自己';
			}elseif($v['jibie'] == 1){
				$shouyilv = '下线收益的'.$v['rate'].'%';
				$laiyuan = '一级代理</br>(来自'.$laiziuserinfo['email'].')';
			}elseif($v['jibie'] == 2){
				$shouyilv = '下线收益的'.$v['rate'].'%';
				$laiyuan = '二级代理</br>(来自'.$laiziuserinfo['email'].')';
			}elseif($v['jibie'] == 3){
				$shouyilv = '下线收益的'.$v['rate'].'%';
				$laiyuan = '三级代理</br>(来自'.$laiziuserinfo['email'].')';
			}
			$list[$k]['laiyuan']  = $laiyuan;
			$list[$k]['rate'] =$shouyilv;
			
   	 		$id = $v['id'];
			$list[$k]['statusVal'] = $v['status'];
   	 		$list[$k]['status'] = $v['status'] ==1?fontcolor('未兑付',''):fontcolor('已兑付','green');   	 		
        }
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
}