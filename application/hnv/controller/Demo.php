<?php
namespace app\hnv\controller;
use think\Controller;
use think\Request;
use think\Db;
class Good extends Base {
	private $table = 'good';
	private $order = 'add_time desc';
	private $addCol = [
    		['col'=>'good_name','chinaname'=>'商品标题','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'type','chinaname'=>'分类','require'=>'required','type'=>'select','selectData'=>[],'style'=>''],
    		['col'=>'luck','chinaname'=>'抽奖次数','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'price','chinaname'=>'价格','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'prefe_limit','chinaname'=>'可使用优惠券金额','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'youhuiprice','chinaname'=>'优惠券面值','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'left_count','chinaname'=>'库存','require'=>'required','type'=>'number','style'=>'']
    	];
	private $changeCol = [
    		['col'=>'good_name','chinaname'=>'商品标题','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'type','chinaname'=>'分类','require'=>'required','type'=>'select','selectData'=>[],'style'=>''],
    		['col'=>'luck','chinaname'=>'抽奖次数','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'price','chinaname'=>'价格','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'prefe_limit','chinaname'=>'可使用优惠券金额','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'youhuiprice','chinaname'=>'优惠券面值','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'left_count','chinaname'=>'库存','require'=>'required','type'=>'number','style'=>'']
    	];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'good_name','chinaname'=>'商品名','style'=>''],
    		['col'=>'type','chinaname'=>'分类','style'=>''],
    		['col'=>'luck','chinaname'=>'可获抽奖次数','style'=>''],
    		['col'=>'order_num','chinaname'=>'排序','style'=>''],
    		['col'=>'sales','chinaname'=>'销量','style'=>''],
    		['col'=>'youhuiprice','chinaname'=>'优惠券上限金额','style'=>''],
    		['col'=>'left_count','chinaname'=>'库存','style'=>'']
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 				'change'=> 	['chinaname'=>'修改'],
 				'status'=>['chinaname'=>'停用']
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'good_name'=>['chinaname'=>'商品名','ca'=>'like','type'=>'text','style'=>''],
    		'type'=>['chinaname'=>'分类','ca'=>'eq','type'=>'select','selectData'=>[['id'=>'','text'=>'请选择']],'style'=>'']
    	];
    	$where = $this->getWhere($searchCol);
    	//***********************************************整理搜索字段
   	 	$list = Db::name($this->table)->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$id = $v['id'];
			$list[$k]['statusVal'] = $v['status'];
   	 		$list[$k]['status'] = $v['status'] ==1?fontcolor('启用','green'):fontcolor('停用','red');   	 		
        }
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
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
}