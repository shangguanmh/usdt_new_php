<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Db;
class Game extends Base {
	private $table = 'lottery_room';
	private $order = 'id asc';
	private $addCol = [
    		['col'=>'cost','chinaname'=>'每局花费（U）','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'name','chinaname'=>'房间','require'=>'required','type'=>'select','selectData'=>[],'style'=>''],
    		['col'=>'奖品','chinaname'=>'抽奖次数','require'=>'required','type'=>'number','style'=>''],
    	];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'cost','chinaname'=>'每局花费（U）','style'=>''],
    		['col'=>'name','chinaname'=>'房间','style'=>''],
    		['col'=>'shangpin','chinaname'=>'奖品','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 				'change'=> 	['chinaname'=>'修改'],
// 				'status'=>['chinaname'=>'停用']
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
   	 		$shangpin = json_decode($v['shangpin'],true);
   	 		$shangpintexxt = '<table><tr><td>名称</td><td>奖品</td><td>中奖权重</td></tr>';
   	 		foreach($shangpin as $val){
   	 			
   	 			$shangpintexxt.='<tr><td>'.$val['name'].'</td><td>'.$val['count'].$val['jiang_type'].'</td><td>'.$val['xyz'].'</td></tr>';
   	 		}
 			$shangpintexxt.='</table>';
			$list[$k]['shangpin'] = $shangpintexxt;
   	 		
   	 		 	 		
        }
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    public function rule(){
     	if(request()->isAjax()){//ajax
     		//ajax
	    	$postdata =  $this->request->post();
	    	$baocundata = isset($postdata['baocundata'])?$postdata['baocundata']:[];
	    	$changeData =[];
	    	foreach($baocundata as $val){
	    		$neirong = $val['neirong'];
	    		$lang = str_replace("notice","",$val['id']);
	    		$lang = str_replace("_","-",$lang);
	    		$have = Db::name('game_rule_lang')->where(['lang'=>$lang])->field('id')->find();
	    		if(empty($have)){
	    			Db::name('game_rule_lang')->insert(['content'=>$neirong,'lang'=>$lang]);
	    		}else{
	    			Db::name('game_rule_lang')->where(['id'=>$have['id']])->update(['content'=>$neirong]);
	    		}
	    	}
			htajaxReturn(1,'修改成功');
     	}else{
	    	$noticelang = Db::name('game_rule_lang')->where([])->field('lang,content')->select();
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
					$temp = ['langname'=>$val['name'],'lang'=>$val['code'],'content'=>'','zw'=>$val['zw']];
				}else{
					$temp = $noticedetaildata[$val['code']];
					$temp['langname'] = $val['name'];
					$temp['zw'] = $val['zw'];
					$temp['content'] = html_entity_decode($temp['content']);
				}
				$temp['lang'] = str_replace("-","_",$temp['lang']);
				$result[] = $temp;
			}
	    	$this->assign('result',$result);
	    	return view();
     	}
    }
      public function record(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'user_id','chinaname'=>'用户','style'=>''],
    		['col'=>'room','chinaname'=>'房间','style'=>''],
    		['col'=>'cost','chinaname'=>'花费(U)','style'=>''],
    		['col'=>'jiangpin_name','chinaname'=>'奖品','style'=>''],
    		['col'=>'yingli','chinaname'=>'盈利（对平台而言）','style'=>''],
    		['col'=>'add_time','chinaname'=>'时间','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    	];
    	$where = $this->getWhere($searchCol);
    	//***********************************************整理搜索字段
   	 	$list = Db::name('choujiang')
 		->alias('choujiang')->join('user user','user.id = choujiang.user_id')
   	 	->field('choujiang.*,user.email,user.tel,user.id as userid')
   	 	->where($where)->order('choujiang.add_time desc')->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	$roomList = Db::name('lottery_room')->where([])->select();
 		$roomKey = [];
 		foreach($roomList as $val){
 			$roomKey[$val['id']] = $val['name'];
 		}
   	 	foreach($list as $k=>$v){
   	 		$list[$k]['yingli'] = -$v['yingli'];
   	 		if($list[$k]['yingli']>0){
   	 			$list[$k]['yingli'] = -$v['yingli'];
   	 			$list[$k]['yingli'] = fontcolor('+'.$list[$k]['yingli'],'green');
   	 		}elseif($list[$k]['yingli']<0){
   	 			$list[$k]['yingli'] = fontcolor($list[$k]['yingli'],'red');
   	 		}
    		$list[$k]['room'] = isset($roomKey[$v['room_id']])?$roomKey[$v['room_id']]:'';
			$list[$k]['user_id'] .='</br>'.$v['email'].'</br>'.$v['tel'];
        }
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
}