<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Db;
class Vip extends Base {
	private $table = 'vip_set';
	private $order = 'level asc';
	private $changeCol = [
		['col'=>'level','chinaname'=>'商品标题','require'=>'required','type'=>'text','style'=>'width:290px'],
		['col'=>'min','chinaname'=>'金额下限','require'=>'required','type'=>'number','style'=>''],
		['col'=>'max','chinaname'=>'金额上限','require'=>'required','type'=>'number','style'=>''],
		['col'=>'rate','chinaname'=>'挖矿收益率','require'=>'required','type'=>'number','style'=>''],
		['col'=>'rate1_chongzhi','chinaname'=>'一级充值返利','require'=>'required','type'=>'number','style'=>''],
		['col'=>'rate2_chongzhi','chinaname'=>'二级充值返利','require'=>'required','type'=>'number','style'=>''],
		['col'=>'rate3_chongzhi','chinaname'=>'三级充值返利','require'=>'required','type'=>'number','style'=>''],
		['col'=>'rate1_wakuang','chinaname'=>'一级挖矿返利','require'=>'required','type'=>'number','style'=>''],
		['col'=>'rate2_wakuang','chinaname'=>'二级挖矿返利','require'=>'required','type'=>'number','style'=>''],
		['col'=>'rate3_wakuang','chinaname'=>'三级挖矿返利','require'=>'required','type'=>'number','style'=>''],
	];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'level','chinaname'=>'等级','style'=>''],
    		['col'=>'pic','chinaname'=>'图片','style'=>''],
    		['col'=>'task_count','chinaname'=>'任务数量','style'=>''],
    		['col'=>'up_money','chinaname'=>'购买金额('.ZHB.')','style'=>''],
    		['col'=>'task_money','chinaname'=>'任务每日奖励('.ZHB.')','style'=>''],
    		['col'=>'chongzhirate','chinaname'=>'下级充值返利','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 				'change'=> 	['chinaname'=>'修改'],
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[];
    	$where = $this->getWhere($searchCol);
    	//***********************************************整理搜索字段
   	 	$list = Db::name($this->table)->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	$isShowVip0 = getConfig('isShowvip0',0);   //是否显示vip0
   	 	
   	 	foreach($list as $k=>$v){
   	 		$id = $v['id'];
			$list[$k]['level'] = 'VIP'.$v['level'];
			if($v['level'] == 0 && !$isShowVip0)
			{
			    $list[$k]['level'] .='<br><span style="color: red;">*前端不显示</span>';
			    
			    $list[$k]['up_money'] .='<br><span style="color: red;">*默认是VIP0,该设置不生效</span>';
			    $list[$k]['task_money'] .='<br><span style="color: red;">*默认是VIP0,注册就送奖励</span>';
			}
			
			$tiyanjin = getConfig('tiyanjin',0);
			if($tiyanjin>0)
			{
			    $money = $v['up_money']-$tiyanjin;
			    $list[$k]['up_money'] .= '<br>'.fontcolor('(实际金额：'.($v['up_money']-$tiyanjin).')','#CD5C5C'); 
			}
			
			$list[$k]['chongzhirate'] = '一级：'.$v['rate1_chongzhi'].'%</br>二级：'.$v['rate2_chongzhi'].'%</br>三级：'.$v['rate3_chongzhi'].'%';
   	 		$pic = getGoopic($v['pic']);
		 	$list[$k]['pic'] = "<img src='$pic'  style='width: 90px;height: 80px;cursor: pointer;'  class='pic' alt='' />
   	 		 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a data_id='$id' class='layui-btn layui-btn-mini layui-btn-warm logoset'>设置</a>";
   	 	}
        
    	$this->assign('addshow',2);
    	$this->assign('searchshow',2);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
        public function change(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	$task_count = intval(isset($postdata['task_count'])?$postdata['task_count']:0);
	    	$task_money= floatval(isset($postdata['task_money'])?$postdata['task_money']:0);
	    	$up_money = floatval(isset($postdata['up_money'])?$postdata['up_money']:0);
	    	$rate1_chongzhi = floatval(isset($postdata['rate1_chongzhi'])?$postdata['rate1_chongzhi']:0);
	    	$rate2_chongzhi = floatval(isset($postdata['rate2_chongzhi'])?$postdata['rate2_chongzhi']:0);
	    	$rate3_chongzhi = floatval(isset($postdata['rate3_chongzhi'])?$postdata['rate3_chongzhi']:0);
	    	$modelDetail = Db::name($this->table)->find($data_id);
	    	if(empty($modelDetail)){
				htajaxReturn(0,'非法参数');
	    	}
	    	$lipu = 70;
	    	if($task_count<0){
				htajaxReturn(0,'任务数量不能小于0');
	    	}
	    	if($task_money<0){
				htajaxReturn(0,'任务收益不能小于等于0');
	    	}
	    	if($up_money<0){
				htajaxReturn(0,'升级所需金额不能小于等于0');
	    	}
	    	
	    	if($rate1_chongzhi<0||$rate1_chongzhi>$lipu){
				htajaxReturn(0,'一级充值分成离谱了吧');
	    	}
	    	if($rate2_chongzhi<0||$rate2_chongzhi>$lipu){
				htajaxReturn(0,'二级充值分成离谱了吧');
	    	}
	    	if($rate3_chongzhi<0||$rate3_chongzhi>$lipu){
				htajaxReturn(0,'三级充值分成离谱了吧');
	    	}
	    	$changeData['up_money'] = $up_money;
	    	$changeData['task_money'] = $task_money;
	    	$changeData['task_count'] = $task_count;
	    	$changeData['rate1_chongzhi'] = $rate1_chongzhi;
	    	$changeData['rate2_chongzhi'] = $rate2_chongzhi;
	    	$changeData['rate3_chongzhi'] = $rate3_chongzhi;
	    	Db::name($this->table)->where(['id'=>$data_id])->update($changeData);
			htajaxReturn(1,'修改成功');
     	}else{
	    	$getdata =  $this->request->get();
	    	$data_id = isset($getdata['data_id'])?$getdata['data_id']:'';
	    	$modelDetail = Db::name($this->table)->find($data_id);
	    	if(empty($modelDetail)){
				htajaxReturn(0,'非法参数');
	    	}
	    	$modelDetail['level'] = 'VIP'.$modelDetail['level'];
	    	$this->assign('data_id',$data_id);
	    	$this->assign('modelDetail',$modelDetail);
	    	return view();
     	}
    }
        public function updatelogo(){
		$postdata =  $this->request->post();
    	$url = isset($postdata['url'])?$postdata['url']:'';
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$modeldetail = Db::name('vip_set')->find($data_id);
    	if(!empty($modeldetail)){
//			deletepic($modeldetail['logo']);
			Db::name('vip_set')->where(['id'=>$data_id])->update(['pic'=>$url]);
    	}
		htajaxReturn(1,'操作成功',['newurl'=>getGoopic($url)]);
    }
}