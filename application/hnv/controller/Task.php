<?php
namespace app\hnv\controller;
use think\Controller;
use think\Request;
use think\Db;
class Task extends Base {
	private $table = 'task';
	private $order = 'id desc';
	private $addCol = [
    		['col'=>'taskname','chinaname'=>'任务名称','require'=>'required','type'=>'text','style'=>'width:290px'],
    	];
	private $changeCol = [
    		['col'=>'taskname','chinaname'=>'任务名称','require'=>'required','type'=>'text','style'=>'width:290px'],
	];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'taskname','chinaname'=>'任务名称','style'=>''],
    		['col'=>'pic','chinaname'=>'图片','style'=>''],
    		['col'=>'vip_level','chinaname'=>'绑定VIP','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 				'change'=> 	['chinaname'=>'修改'],
 				'del'=> 	['chinaname'=>'删除'],
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'taskname'=>['chinaname'=>'任务名称','ca'=>'like','type'=>'text','style'=>''],
    		'vip_level'=>['chinaname'=>'VIP等级','ca'=>'eq','type'=>'select','selectData'=>[],'style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	$vipList = Db::name('vip_set')->where([])->field('level')->order('level asc')->select();
    	$searchCol['vip_level']['selectData'][] = ['id'=>'-1','text'=>'请选择'];
    	foreach($vipList as $val){
    	$searchCol['vip_level']['selectData'][] = ['id'=>$val['level'],'text'=>'VIP'.$val['level']];
    	}
    	//***********************************************整理搜索字段
   	 	$list = Db::name($this->table)->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$id = $v['id'];
   	 		$pic = getGoopic($v['pic']);
 			$list[$k]['pic'] = "<img src='$pic'  style='width: 90px;height: 80px;cursor: pointer;'  class='pic' alt='' />
 			 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a data_id='$id' class='layui-btn layui-btn-mini layui-btn-warm logoset'>设置</a>"; 	
 			 if($v['vip_level']!=-1){
 			 	 $list[$k]['vip_level']  = 'VIP'.$v['vip_level'];
 			 }else{
 			 	 $list[$k]['vip_level']  = '无绑定';
 			 }
		 	 $list[$k]['vip_level']  .="<a data_id='$id' class='layui-btn layui-btn-mini layui-btn-warm vipset'>设置</a>";
        }
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
        public function updatelogo(){
		$postdata =  $this->request->post();
    	$url = isset($postdata['url'])?$postdata['url']:'';
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$modeldetail = Db::name('task')->find($data_id);
    	if(!empty($modeldetail)){
//			deletepic($modeldetail['logo']);
			Db::name('task')->where(['id'=>$data_id])->update(['pic'=>$url]);
    	}
		htajaxReturn(1,'操作成功',['newurl'=>getGoopic($url)]);
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
      public function del(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
		$modelDetail = Db::name($this->table)->find($data_id);
    	if(empty($modelDetail)){
			htajaxReturn(0,'非法参数');
    	}
		$modelDetail = Db::name($this->table)->where(['id'=>$data_id])->delete();
		htajaxReturn(1,'操作成功');
    	
    }
     public function lingqu(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'user_id','chinaname'=>'用户','style'=>''],
    		['col'=>'taskname','chinaname'=>'任务名称','style'=>''],
    		['col'=>'money','chinaname'=>'金额','style'=>''],
    		['col'=>'lingqudate','chinaname'=>'时间','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 				'change'=> 	['chinaname'=>'修改'],
 				'del'=> 	['chinaname'=>'删除'],
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'taskname'=>['chinaname'=>'任务名','ca'=>'like','type'=>'text','style'=>''],
    		'email'=>['chinaname'=>'用户','ca'=>'like','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	$where['user_task.status'] = 2;
    	$orwhere = 'user_task.id >0';
    	if(isset($where['email'])){
    		$orwhere .= ' AND(email LIKE "%'.$_GET['email'].'%" OR tel like "%'.$_GET['email'].'%")';
    		$biaozhi['email'] = $_GET['email'];
    		unset($where['email']);
    	}
    	
    	
    	//***********************************************整理搜索字段
   	 	$list = Db::name('user_task')
   	 	->alias('user_task')
   	 	->join('user user','user.id = user_task.user_id')
   	 	->where($where)->field('user_task.*,user.id as uid,user.email,user.tel')
   	 	->where($orwhere)
   	 	->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$id = $v['id'];
			$list[$k]['user_id'] =$v['email'].'</br>'.$v['tel'];
   	 		
        }
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    public function vipset(){
     	if(request()->isAjax()){
     		$postdata =  $this->request->post();
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:0;
	    	$vip_level = isset($postdata['vip_level'])?$postdata['vip_level']:-1;
	    	$vipinfo = Db::name('vip_set')->where(['level'=>$vip_level])->find();
	    	if(empty($vipinfo)&&$vip_level!=-1){
				htajaxReturn(0,'错误带参');
	    	}
	    	Db::name('task')->where(['id'=>$data_id])->update(['vip_level'=>$vip_level]);
			htajaxReturn(1,'修改成功');
     	}else{
     		$postdata =  $this->request->get();
     		$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
     		$vipList = Db::name('vip_set')->where([])->field('level')->order('level asc')->select();
     		$selectlist = [];
     		$selectlist[] = ['id'=>'-1','text'=>'不绑定VIP'];
     		foreach($vipList as $val){
		    	$selectlist[] = ['id'=>$val['level'],'text'=>'VIP'.$val['level']];
	    	}
     		$this->assign('data_id',$data_id);
     		$this->assign('vipList',$selectlist);
     		return view();
     	}
    }
}