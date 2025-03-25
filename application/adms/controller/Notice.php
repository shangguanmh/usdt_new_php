<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Db;
class Notice extends Base {
	private $table = 'notice';
	private $order = 'status desc,order_num desc';
	private $addCol = [
    		['col'=>'tel_name','chinaname'=>'模版名','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'order_num','chinaname'=>'排序','require'=>'required','type'=>'number','style'=>''],
    	];
	private $changeCol = [
    		['col'=>'tel_name','chinaname'=>'模版名','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'order_num','chinaname'=>'排序','require'=>'required','type'=>'number','style'=>''],
    	];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'tel_name','chinaname'=>'模版名','style'=>''],
    		['col'=>'order_num','chinaname'=>'排序','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>'']
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 				'change'=> 	['chinaname'=>'修改'],
 				'status'=>['chinaname'=>'停用'],
 				'delete'=>['chinaname'=>'删除'],
 				'xiangxi'=> 	['chinaname'=>'公告详细']
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
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
   	 		$list[$k]['order_num'] = '<input data_id="'.$id.'" class="layui-input paixvin" style="width:50px" type="number" style="" placeholder="" value="'.$v['order_num'].'">';
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
    public function setorder(){
    	$postdata =  $this->request->post();
    	$newval = isset($postdata['newval'])?$postdata['newval']:'';
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$order_num = isset($postdata['order_num'])?$postdata['order_num']:'';
    	Db::name('notice')->where(['id'=>$data_id])->update([$order_num=>$newval]);
		htajaxReturn(1,'保存成功');
    }
      public function delete(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
		$modelDetail = Db::name($this->table)->find($data_id);
    	if(empty($modelDetail)){
			htajaxReturn(0,'非法参数');
    	}
		$modelDetail = Db::name('notice')->where(['id'=>$data_id])->delete();
		Db::name('notice_lang')->where(['notice_id'=>$data_id])->delete();
		htajaxReturn(1,'操作成功');
    }
    public function xiangxi(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$baocundata = isset($postdata['baocundata'])?$postdata['baocundata']:[];
	    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
	    	$modelDetail = Db::name('notice')->find($data_id);
	    	if(empty($modelDetail)){
				htajaxReturn(0,'非法参数');
	    	}
	    	$changeData =[];
	    	foreach($baocundata as $val){
	    		$neirong = $val['neirong'];
	    		$html_text = $val['html_text'];
	    		$lang = str_replace("notice","",$val['id']);
	    		$lang = str_replace("_","-",$lang);
	    		$existid = $val['existid'];
	    		if($existid <= 0){
	    			$data = ['lang'=>$lang,'notice_id'=>$data_id,'content'=>$neirong,'content_text'=>$html_text];
	    			Db::name('notice_lang')->insert($data);
	    		}else{
	    			$data = ['content'=>$neirong,'content_text'=>$html_text];
	    			Db::name('notice_lang')->where(['id'=>$existid])->update($data);
	    		}
	    	}
			htajaxReturn(1,'修改成功');
     	}else{
	    	$getdata =  $this->request->get();
	    	$data_id = isset($getdata['data_id'])?$getdata['data_id']:'';
	    	$noticelang = Db::name('notice_lang')->where(['notice_id'=>$data_id])->select();
	    	if(empty($noticelang)){
				$noticelang = [];
	    	}
  			$langlist = json_decode(getConfig('langlist',0),true);
  			$noticedetaildata = [];
			foreach($noticelang as $val){
				$noticedetaildata[$val['lang']] = $val;
			}	   
			$result = []; 
			foreach($langlist as $k=> $val){
				if(!isset($noticedetaildata[$val['code']])){
					$temp = ['id'=>0,'notice_id'=>$data_id,'langname'=>$val['name'],'lang'=>$val['code'],
					'content'=>'','content_text'=>'','zw'=>$val['zw']];
				}else{
					$temp = $noticedetaildata[$val['code']];
					$temp['langname'] = $val['name'];
					$temp['zw'] = $val['zw'];
					$temp['content'] = html_entity_decode($temp['content']);
					
				}
				$temp['lang'] = str_replace("-","_",$temp['lang']);
				$result[] = $temp;
			}
//			echo json_encode($result);
	    	$this->assign('data_id',$data_id);
	    	$this->assign('result',$result);
	    	return view();
     	}
    }
}