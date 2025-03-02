<?php
namespace app\hnv\controller;
use think\Controller;
use think\Request;
use think\Db;
use app\hnv\controller\User;

class Tuan extends Base {
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
    		['col'=>'user_id','chinaname'=>'用户','style'=>''],
    		['col'=>'czje','chinaname'=>'业绩','style'=>''],
    		['col'=>'tuanduicount','chinaname'=>'团队人数','style'=>''],
    		['col'=>'yiji_count','chinaname'=>'一级人数','style'=>''],
    		['col'=>'erji_count','chinaname'=>'二级人数','style'=>''],
    		['col'=>'sanji_count','chinaname'=>'三级人数','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 				'chakantuandui'=>['chinaname'=>'查看团队']
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'email'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
    	];
    	$where = $this->getWhere($searchCol);
    	//***********************************************整理搜索字段
   	 	$list = Db::name('user')->where($where)->alias('user')
   	 	->join('(SELECT user_id, sum(czjine) AS czje FROM  jxxj_chognzhifanli where jibie > 0 GROUP BY user_id) om','om.user_id = user.id','left')
   	 	->field('id,email,tel,yiji_count,erji_count,sanji_count,(yiji_count+erji_count+sanji_count) as tuanduicount,om.czje as czje')
   	 	->order('czje desc')->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$id = $v['id'];
			$list[$k]['user_id'] =$v['id'].'</br>'.$v['email'].'</br>'.$v['tel'];
        }
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    
    
    public function yeji(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'id','chinaname'=>'用户','style'=>''],
    		['col'=>'username','chinaname'=>'邮箱/手机号码','style'=>''],
    		['col'=>'xfje','chinaname'=>'充值','style'=>''],
    		['col'=>'txje','chinaname'=>'提现','style'=>''],
    		['col'=>'guojia','chinaname'=>'地区','style'=>''],
    		['col'=>'add_time','chinaname'=>'最后充值','style'=>''],
    		['col'=>'shangji','chinaname'=>'上级','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
    		['col'=>'can_wakuang','chinaname'=>'收益','style'=>''],
    		['col'=>'beizhu','chinaname'=>'备注','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 			// 	'yejiguanli'=>['chinaname'=>'业绩管理']
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    // 		'email'=>['chinaname'=>'email','ca'=>'like','type'=>'text','style'=>''],
    	];
    	$where = []; //$this->getWhere($searchCol)
    	//***********************************************整理搜索字段
   	 	$list = Db::name('user')
   	 	->alias('user')->where(['user.xfje'=>['gt',0]])  //$where
   	 	->join('user user2','user2.id = user.from_who','left')
   	 	->join('(SELECT user_id, MAX(add_time) AS max_date FROM  jxxj_invest_order where status = 2 GROUP BY user_id) om','om.user_id = user.id','left')
   	 	->join('jxxj_invest_order norder','norder.user_id = om.user_id and norder.add_time = om.max_date ','left')
   	 	->field('user.*,user2.id as shangjiid,user2.email as shangjiemail,user2.tel as shangjitel,norder.add_time as add_time')
   	 	->order('id asc')->paginate(15);//查询数据  
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
   	 		$id = $v['id'];
   	 		$username = $v['email'];
   	 		if(!strlen($username))
   	 		{
   	 		    $username = $v['tel'];
   	 		}
			$list[$k]['username'] = $username;
			$list[$k]['username'] .= "<a style='margin-left:10px' data_id='$id'   class='layui-btn layui-btn-mini layui-btn-warm tzsy'>更新收益</a>";
			$list[$k]['username'] .= "<a style='margin-left:10px' data_id='$id'   class='layui-btn layui-btn-mini layui-btn-warm chakantuandui'>查看团队</a>";
			
			$shangji = $v['shangjiemail'];
   	 		if(!strlen($shangji))
   	 		{
   	 		    $shangji = $v['shangjitel'];
   	 		}
   	 		$shangjiid = $v['shangjiid'];
			$list[$k]['shangji'] = $v['shangjiid'].'</br>'.$shangji;  //
			$list[$k]['shangji'] .= "<a style='margin-left:10px' data_id='$shangjiid'   class='layui-btn layui-btn-mini layui-btn-warm chakantuandui'>查看团队</a>";
        }
        
        
        $result = array();
     // php二维数据某个user_id字段值相同则放到一起,php数组根据某键值，把相同键值的合并最终生成一个新的二维数组.. key 为user_id
     foreach ($list as $key => $info) {
          $result[$info['shangji']][] = $info;
      }
    //   var_dump($result);
        // $list = $result;
        
        $addshow = count($this->addCol)>0?1:2;
        $searchshow = count($searchCol)>0?1:2;
    	$this->assign('addshow',$addshow);
    	$this->assign('searchshow',$searchshow);
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->getyejitableHtml($showCol,$result,$operCol));
    	return view();
    }
    
    public function ntzsy(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	$xingbiao =  Db::name('user')->where(['id'=>$data_id])->value('can_wakuang');
    	$xingbiao = $xingbiao==0?1:0;
		$modelDetail = Db::name('user')->where(['id'=>$data_id])->update(['can_wakuang'=>$xingbiao]);
		ajaxReturn(1,'操作成功');
    }
    
    public function lookxiaxian(){
    	$usercon = new User();
		$postdata =  $this->request->get();
    	$start_time = isset($postdata['start_time'])?$postdata['start_time']:date('Y-m-d');
    	$end_time = isset($postdata['end_time'])?$postdata['end_time']:date('Y-m-d');
    	
    	$starttime = $start_time.' 00:00:00';
    	$endTtime = $end_time.' 23:59:59';
    	$shijian = $starttime.'至'.$endTtime;
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:'';
    	//分销下限
  		$yijicount = 0;
  		$erjijicount = 0;
  		$sanjijicount = 0;
  		$yijilist = [];
  		$erjilist = [];
  		$sanjilist = [];
  		$suoyouID = [-1];
  		
  		
  		$userInfo = Db::name('user')->find($data_id);
  		
 		$yijilist = Db::name('user')->where(['from_who'=>$data_id])->field('id,email,tel,add_time,zhuce_ip,guojia,last_login_time,xfje')->order('add_time desc')->select();
 		$yijicount = count($yijilist);
 		if($yijicount>0){
 			$yijiIDs = array_column($yijilist,'id');
 			$erjilist = Db::name('user')->where(['from_who'=>['in',$yijiIDs]])->field('id,email,tel,add_time,zhuce_ip,guojia,last_login_time,xfje')->order('add_time desc')->select();
 			$erjijicount = count($erjilist);
 			if($erjijicount>0){
 				$erjiIDs = array_column($erjilist,'id');
   	 			$sanjilist = Db::name('user')->where(['from_who'=>['in',$erjiIDs]])->field('id,email,tel,add_time,zhuce_ip,guojia,last_login_time,xfje')->order('add_time desc')->select();
   	 			$sanjijicount = count($sanjilist);
 			}
 		}
 		$suoyouID = array_merge($suoyouID,array_column($yijilist,'id'));
 		$suoyouID = array_merge($suoyouID,array_column($erjilist,'id'));
 		$suoyouID = array_merge($suoyouID,array_column($sanjilist,'id'));
 		//总业绩
//		$chonghizAll  = Db::name('user')->where(['id'=>['in',$suoyouID]])->sum('xfje');
 		$chonghizAll  = Db::name('invest_order')->where(['user_id'=>['in',$suoyouID],'status'=>2,'add_time'=>[['egt',$starttime],['elt',$endTtime]]])->sum('zuihou_value');
 		$chonghiz_usdt  = Db::name('invest_order')->where(['user_id'=>['in',$suoyouID],'status'=>2,'huobi'=>'USDT','add_time'=>[['egt',$starttime],['elt',$endTtime]]])->sum('money');
 		$chonghiz_trx  = Db::name('invest_order')->where(['user_id'=>['in',$suoyouID],'status'=>2,'huobi'=>'TRX','add_time'=>[['egt',$starttime],['elt',$endTtime]]])->sum('money');
 		$chongzhirenshu =  Db::name('invest_order')->where(['user_id'=>['in',$suoyouID],'status'=>2,'add_time'=>[['egt',$starttime],['elt',$endTtime]]])->group('user_id')->count('money');
 		$chongzhi = ['chonghizAll'=>round($chonghizAll,3),'chonghiz_usdt'=>round($chonghiz_usdt,3),
 		'chonghiz_trx'=>round($chonghiz_trx,3),'chongzhirenshu'=>$chongzhirenshu];
 		foreach($yijilist as $k=>$val){
 			$yijilist[$k]['zhuce_ip'] =  $val['zhuce_ip']. "（".$usercon->getcountry($val['guojia'])."）";
 			$yijilist[$k]['zhuce_ip'] .= $val['last_login_time'];
 		}
 		foreach($erjilist as $k=>$val){
 			$erjilist[$k]['zhuce_ip'] = $val['zhuce_ip']. "（".$usercon->getcountry($val['guojia'])."）";;
 			$erjilist[$k]['zhuce_ip'] .= $val['last_login_time'];
 		}
 		foreach($sanjilist as $k=>$val){
 			$sanjilist[$k]['zhuce_ip'] = $val['zhuce_ip']. "（".$usercon->getcountry($val['guojia'])."）";;
 			$sanjilist[$k]['zhuce_ip'] .= $val['last_login_time'];
 		}
    	$data = ['yijicount'=>$yijicount,'erjicount'=>$erjijicount,'sanjicount'=>$sanjijicount,
    	'yijilist'=>$yijilist,'erjilist'=>$erjilist,'sanjilist'=>$sanjilist];
    	$this->assign('data', $data);
    	$this->assign('user',$userInfo);
    	$this->assign('chongzhi', $chongzhi);
    	$this->assign('data_id', $data_id);
    	$this->assign('start_time', $start_time);
    	$this->assign('end_time', $end_time);
    	$this->assign('shijian', $shijian);
    	return view();
    }
    function jinzhituandenglu(){
    	$postdata =  $this->request->post();
    	$data_id = isset($postdata['data_id'])?$postdata['data_id']:0;
    	$userInfo = Db::name('user')->find($data_id);
    	if(empty($userInfo)){
			ajaxReturn(0,'参数错误');
    	}
    	$UserList = Db::name('user')->where("from_who=$data_id OR erji_from =$data_id OR  sanji_from=$data_id")->field('id')->select();
    	$UserList = array_column($UserList,'id');
    	$UserList[] = $data_id;
    	Db::name('user')->where(['id'=>['in',$UserList]])->update(['status'=>0,'token'=>'']);
		ajaxReturn(1,'操作成功');
    }
}