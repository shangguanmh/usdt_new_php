<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Db;
class Licai extends Base {
	private $table = 'licai';
	private $order = 'status desc,order_num desc';
	private $addCol = [
    		['col'=>'name','chinaname'=>'名称','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'buy_min','chinaname'=>'最少购买','devalue'=>0,'require'=>'required','type'=>'number','style'=>''],
    		['col'=>'buy_max','chinaname'=>'最大购买','devalue'=>0,'require'=>'required','type'=>'number','style'=>''],
    		['col'=>'day','chinaname'=>'投资天数','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'rate_day','chinaname'=>'每日收益（%）','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'shouyi_type','chinaname'=>'收益类型','require'=>'required','type'=>'select','selectData'=>[['id'=>1,'text'=>'每日结息'],['id'=>2,'text'=>'到期本息']],'style'=>''],
    		['col'=>'limit_all_count','chinaname'=>'购买次数限制','devalue'=>0,'require'=>'required','type'=>'number','style'=>''],
    		['col'=>'limit_vip_level','chinaname'=>'VIP等级限制','devalue'=>0,'require'=>'required','type'=>'number','style'=>''],
    	];
	private $changeCol = [
    		['col'=>'name','chinaname'=>'名称','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'buy_min','chinaname'=>'最少购买','devalue'=>0,'require'=>'required','type'=>'number','style'=>''],
    		['col'=>'buy_max','chinaname'=>'最大购买','devalue'=>0,'require'=>'required','type'=>'number','style'=>''],
    		['col'=>'day','chinaname'=>'投资天数','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'rate_day','chinaname'=>'每日收益（%）','require'=>'required','type'=>'number','style'=>''],
    		['col'=>'shouyi_type','chinaname'=>'收益类型','require'=>'required','type'=>'select','selectData'=>[['id'=>1,'text'=>'每日结息'],['id'=>2,'text'=>'到期本息']],'style'=>''],
    		['col'=>'limit_all_count','chinaname'=>'购买次数限制','devalue'=>0,'require'=>'required','type'=>'number','style'=>''],
    		['col'=>'limit_vip_level','chinaname'=>'VIP等级限制','devalue'=>0,'require'=>'required','type'=>'number','style'=>''],
    	];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'name','chinaname'=>'名称','style'=>''],
    		['col'=>'logo','chinaname'=>'图片','style'=>''],
    		['col'=>'xiane','chinaname'=>'购买限额','style'=>''],
    		['col'=>'day','chinaname'=>'投资天数','style'=>''],
    		['col'=>'rate_day','chinaname'=>'每日收益','style'=>''],
    		['col'=>'shouyi_type','chinaname'=>'收益类型','style'=>''],
    		['col'=>'order_num','chinaname'=>'排序</br>(越大越前)','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
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
    		'name'=>['chinaname'=>'名称','ca'=>'like','type'=>'text','style'=>''],
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
   	 		$list[$k]['status'] = $v['status'] ==1?fontcolor('销售中','green'):fontcolor('停售中','red');   
   	 		if($v['buy_min'] == 0){
   	 			$v['buy_min'] = '无限制';
   	 		}	
   	 		if($v['buy_max'] == 0){
   	 			$v['buy_max'] = '无限制';
   	 		} 
   	 		if($v['limit_vip_level'] == 0){
   	 			$v['limit_vip_level'] = '无限制';
   	 		}else{
   	 			$v['limit_vip_level'] = 'VIP'.$v['limit_vip_level'];
   	 		}	
   	 		$logo = getGoopic($v['logo']);
   	 		$list[$k]['logo'] = "<img src='$logo'  style='width: 90px;height: 80px;cursor: pointer;'  class='pic' alt='' />
   	 		 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a data_id='$id' class='layui-btn layui-btn-mini layui-btn-warm logoset'>设置</a>";		
   	 		$list[$k]['xiane'] = '最小：'. $v['buy_min']  .'</br>'.'最大：'. $v['buy_max'].'</br>'.'VIP等级：'. $v['limit_vip_level'];		
 			$list[$k]['rate_day'] .= '%';
   	 		$list[$k]['shouyi_type'] = $v['shouyi_type'] ==1?'每天结息，到期还本':'到期归还本息';   
   	 		$list[$k]['order_num'] = '<input data_id="'.$id.'" class="layui-input paixvin" style="width:50px" type="number" style="" placeholder="" value="'.$v['order_num'].'">';
        }
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    public function setorder(){
    	$postdata =  $this->request->post();
    	$newval = isset($postdata['newval'])?$postdata['newval']:'';
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$order_num = isset($postdata['order_num'])?$postdata['order_num']:'';
    	Db::name('licai')->where(['id'=>$data_id])->update([$order_num=>$newval]);
		htajaxReturn(1,'保存成功');
    }
    public function add(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$addData =[];
	    	foreach($this->addCol as $val){
	    		$addData[$val['col']] =$postdata[$val['col']];
	    		if(strpos($val['require'],'required') !== false){
	    			if(empty($postdata[$val['col']])&&$postdata[$val['col']]!=0){
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
    	if($addData['day']<=0||$addData['day']>60){
			htajaxReturn(0,'投资天数格式不对了');
    	}
    	if($addData['buy_min']<0||$addData['buy_max']<0){
			htajaxReturn(0,'购买限额不对');
    	}
    	if($addData['rate_day']<0||$addData['rate_day']>20){
			htajaxReturn(0,'每日收益是不是手抖了');
    	}
    	if(!in_array($addData['shouyi_type'],[1,2])){
			htajaxReturn(0,'收益类型不对');
    	}
    	if($addData['limit_all_count']<0){
			htajaxReturn(0,'购买次数限制填写有误');
    	}
    	if($addData['limit_vip_level']<0){
			htajaxReturn(0,'购买会员等级限制填写有误');
    	}
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
      public function updatelogo(){
		$postdata =  $this->request->post();
    	$url = isset($postdata['url'])?$postdata['url']:'';
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$modeldetail = Db::name('licai')->find($data_id);
    	if(!empty($modeldetail)){
			deletepic($modeldetail['logo']);
			Db::name('licai')->where(['id'=>$data_id])->update(['logo'=>$url]);
    	}
		htajaxReturn(1,'操作成功',['newurl'=>getGoopic($url)]);
    }
    //购买记录
    
    public function userrecord(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'user_id','chinaname'=>'用户','style'=>''],
    		['col'=>'licai_id','chinaname'=>'理财','style'=>''],
    		['col'=>'order_num','chinaname'=>'订单号','style'=>''],
    		['col'=>'money','chinaname'=>'购买金额','style'=>''],
    		['col'=>'rate_day','chinaname'=>'每日收益','style'=>''],
    		['col'=>'tianinfo','chinaname'=>'到期情况','style'=>''],
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
    	];
    	$where = $this->getWhere($searchCol);
    	//***********************************************整理搜索字段
   	 	$list = Db::name('user_licai')
   	 	->alias('user_licai')
   	 	->join('user user','user.id = user_licai.user_id')
   	 	->where($where)->field('user_licai.*,user.id as uid,user.email,user.tel')
   	 	->order('user_licai.add_time desc')->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
			$list[$k]['statusVal'] = $v['status'];
   	 		$id = $v['id'];
			$list[$k]['user_id'] =$v['uid'].'</br>'.$v['email'].'</br>'.$v['tel'];
			$list[$k]['licai_id'] ='理财ID：'.$v['licai_id'].'</br>名称：'.$v['name'];
   	 		$shouyi_type = $v['shouyi_type'] ==1?'每天结息，到期还本':'到期归还本息';   
   	 		
			$list[$k]['tianinfo'] ='投资天数：'.$v['day'].'</br>	收益类型：'.$shouyi_type.'</br>	购买时间：'
			.$v['add_time'].'</br>到期时间：'.$v['daoqi_time'].'</br>总共利息：'.$v['zonglirun'];
			$list[$k]['rate_day'] .= '%';
			$list[$k]['status'] = $v['status'] == 1?fontcolor('未结算','red'):fontcolor('已结算','green');
			$list[$k]['status'] .= "</br><a data_id='$id' class='layui-btn layui-btn-mini layui-btn-warm issuedetail'>查看每期详细</a>";
        }
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',2);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
     public function licaiissue(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'issue','chinaname'=>'期数','style'=>''],
    		['col'=>'daoqi_time','chinaname'=>'到期时间','style'=>''],
    		['col'=>'lixi','chinaname'=>'利息','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    	];
    	$postdata =  $this->request->get();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$where = $this->getWhere($searchCol);
    	$where['user_licai_id'] = $data_id;
    	//***********************************************整理搜索字段
   	 	$list = Db::name('user_licai_issue')
   	 	->where($where)->order('issue asc')->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
			$list[$k]['status'] = $v['status'] == 1?fontcolor('未结算','red'):fontcolor('已结算','green');
        }
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',2);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
}