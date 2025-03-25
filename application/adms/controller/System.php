<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Cache;
use think\Db;
use app\cli\controller\Auto as autocon;
class System extends Base {
	private $table = 'gluser';
	private $order = 'add_time asc';
	private $addCol = [
    		['col'=>'user_name','chinaname'=>'用户名','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'nickname','chinaname'=>'昵称','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'password','chinaname'=>'密码','require'=>'required','type'=>'password','style'=>'width:290px'],
    	];
	private $changeCol = [
    		['col'=>'user_name','chinaname'=>'用户名','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'nickname','chinaname'=>'昵称','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'password','chinaname'=>'密码','require'=>'','type'=>'password','style'=>'width:290px']
    	];
    public function main(){
    	//*********************************************整理展示字段
    	$showCol = [
    		['col'=>'user_name','chinaname'=>'用户名','style'=>''],
    		['col'=>'nickname','chinaname'=>'昵称','style'=>''],
    		['col'=>'status','chinaname'=>'状态','style'=>''],
    		['col'=>'quanxian','chinaname'=>'权限','style'=>''],
    		['col'=>'add_time','chinaname'=>'添加时间','style'=>''],
    	];
    	//*********************************************整理展示字段
    	
    	//*********************************************操作字段
    	$operCol = [
 				'change'=> 	['chinaname'=>'修改'],
 				'status'=>['chinaname'=>'停用'],
 				'quanxian'=>['chinaname'=>'修改权限'],
    	];
    	
    	//*********************************************操作字段
    	
    	//*********************************************整理搜索字段
    	$searchCol =[
    		'status'=>['chinaname'=>'状态','ca'=>'eq','type'=>'select','selectData'=>[],'style'=>'width=50px;']
    	];
    	$searchCol['status']['selectData'] = $this->statusSelectData();
    	$where = $this->getWhere($searchCol);
    	
    	$guanli_id = intval(session('sk_id'));
    	
    	if($guanli_id != 3 && $guanli_id != 4)
    	{
    	    $where['id'] = ['not in',[3,4]];  //排除管理员
    	}
    	
    	if(!isset($where['status'])){
    		$where['status']= 1;
    	}
    	//***********************************************整理搜索字段
   	 	$list = Db::name($this->table)->where($where)->order($this->order)->paginate(15);//查询数据
   	 	$page = isset($this->params['page'])?$this->params['page']:1;
   	 	$pageInfo = setPage($list->total(),$page);
   	 	$list =  $list->all(); //分页对象转化为数组
   	 	foreach($list as $k=>$v){
			$list[$k]['statusVal'] = $v['status'];
   	 		$list[$k]['status'] = $v['status'] ==1?'启用':'停用';   
   	 		   // 权限
            $arr = [];
            foreach ($this->module as $k1 => $v1) {
                $temp_arr = array_intersect(explode(',', $v['auth']), $v1['list']);
                if (!empty($temp_arr)) {
                    $module_auth_arr = [];
                    foreach ($temp_arr as $k2 => $v2) {
                        $module_auth_arr[] = $this->auth[$v2]['name'];
                    }
                    $arr[] = ['name'=> $v1['name'], 'auth'=> implode('，', $module_auth_arr)];
                }
            }	 		
            $quanxian = !empty($arr) ? json_encode($arr) : '';
   	 		$list[$k]['quanxian'] = "<a class='layui-btn layui-btn-mini layui-btn-warm look-auth' data-auth='$quanxian'>查看</a>"	;	
        }
    	$this->assignAll($list,$pageInfo,$where,$this->getSearchHtml($searchCol),$this->gettableHtml($showCol,$list,$operCol));
    	return view();
    }
    public function add(){
    	$this->assign('addhtml', $this->getAddHtml($this->addCol));
    	$this->assign('submitAction', url(request()->controller().'/addSubmit'));
    	return view();
    }
    public function addSubmit(){
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
    	if(strlen($addData['user_name'])<3){
    		htajaxReturn(0,'用户名长度最低3位');
    	}
    	if(strlen($addData['password'])<6){
    		htajaxReturn(0,'密码长度最低6位');
    	}
    	$chongfu = Db::name('gluser')->where(['user_name'=>$addData['user_name']])->count();
    	if($chongfu>0){
    		htajaxReturn(0,'已存在该用户名');
    	}
    	
		$saft = '';//盐值
    	$pool='azxcbnmsaldfkjhpwoeoyut';//定义一个验证码池，验证码由其中几个字符组成
		$word_length=6;//验证码长度
	    for ($i = 0, $mt_rand_max = strlen($pool) - 1; $i < $word_length; $i++)
	    {
	        $saft .= $pool[mt_rand(0, $mt_rand_max)];
	    }
	    $addData['password'] = md5($addData['password'].$saft);
	    $addData['saft'] = $saft;
    	$addData['add_time'] = date('Y-m-d H:i:s');
    	Db::name($this->table)->insert($addData);
		htajaxReturn(1,'新增成功');
    }
     public function change(){
    	$getdata =  $this->request->get();
    	$data_id = isset($getdata['data_id'])?$getdata['data_id']:'';
    	$modelDetail = Db::name($this->table)->find($data_id);
    	if(empty($modelDetail)){
			htajaxReturn(0,'非法参数');
    	}
    	$this->assign('changeHtml', $this->getChangeHtml($this->changeCol,$modelDetail));
    	$this->assign('submitAction', url(request()->controller().'/changeSubmit'));
    	$this->assign('data_id',$data_id);
    	return view();
    }
      public function changeSubmit(){
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
		if(strlen($changeData['user_name'])<3){
    		htajaxReturn(0,'用户名长度最低3位');
    	}
    	if(!empty($changeData['password'])&&strlen($changeData['password'])<6){
    		htajaxReturn(0,'密码长度最低6位');
    	}    	
    	$chongfu = Db::name('gluser')->where(['user_name'=>$changeData['user_name'],'id'=>['neq',$data_id]])->count();
    	if($chongfu>0){
    		htajaxReturn(0,'已存在该用户名');
    	}
    	if(empty($changeData['password'])){
    		unset($changeData['password']);
    	}else{
    		 $changeData['password'] = md5($changeData['password'].$modelDetail['saft']);
    	}
    	unset($changeData['id']);
    	Db::name($this->table)->where(['id'=>$data_id])->update($changeData);
		htajaxReturn(1,'修改成功');
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
    
    // 	if(!empty($url)){
    	    
    // 	    Db::name('config')->where(['config_sign'=>'logourl'])->update(['config_value'=>$url]);

    // 	}
        
		htajaxReturn(1,'操作成功',['newurl'=>getGoopic($url)]);
    }
    
    public function quanxian(){
    	$postdata =  $this->request->get();
    	$user_id = isset($postdata['user_id'])?$postdata['user_id']:'';
        $userInfo = Db::name('gluser')->find($user_id);
        if (empty($userInfo)) {
            $this->error('账号不存在');
        }
        $userInfo['auth'] = explode(',', $userInfo['auth']);
        foreach ($this->module as $key => $value) {
            foreach ($value['list'] as $k => $v) {
            	$checked = '';
                if(in_array($v,$userInfo['auth'])){
                	$checked = 'checked';
                }
                $this->module[$key]['auth'][] = ['id'=>$v,'name'=>$this->auth[$v]['name'],'checked'=>$checked];
            }
        }
        $this->assign('module', $this->module);
        $this->assign('userInfo', $userInfo);
        $this->assign('auth', $this->auth);
    	return view();
    }
    public function savequanxian(){
    	$postdata =  $this->request->post();
    	$user_id = isset($postdata['user_id'])?$postdata['user_id']:'';
    	$auths = isset($postdata['auths'])?$postdata['auths']:'';
    	Db::name('gluser')->where(['id'=>$user_id])->update(['auth'=>$auths]);
		htajaxReturn(1,'保存成功');
    }

    public function changjianwenti(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$baocundata = isset($postdata['baocundata'])?$postdata['baocundata']:[];
	    	$changeData =[];
	    	foreach($baocundata as $val){
	    		$neirong = $val['neirong'];
	    		$lang = str_replace("notice","",$val['id']);
	    		$lang = str_replace("_","-",$lang);
    			Db::name('config')->where(['config_sign'=>'changjianwenti','lang'=>$lang])->update(['config_value'=>$neirong]);
	    	}
			htajaxReturn(1,'修改成功');
     	}else{
	    	$noticelang = Db::name('config')->where(['config_sign'=>'changjianwenti'])->field('lang,config_value')->select();
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
					$temp = ['langname'=>$val['name'],'lang'=>$val['code'],'config_value'=>'','zw'=>''];
				}else{
					$temp = $noticedetaildata[$val['code']];
					$temp['langname'] = $val['name'];
					$temp['zw'] = $val['zw'];
					$temp['config_value'] = html_entity_decode($temp['config_value']);
				}
				$temp['lang'] = str_replace("-","_",$temp['lang']);
				$result[] = $temp;
			}
	    	$this->assign('result',$result);
	    	return view();
     	}
    }
    public function kefu(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$kefuurl = isset($postdata['kefuurl'])?$postdata['kefuurl']:'';
	    	
	    	if(!strstr($kefuurl,'https://t.me/'))
	    	{
	    	    htajaxReturn(0,'格式错误');
	    	}
	    	
	   // 	$kefufeiji = isset($postdata['kefufeiji'])?$postdata['kefufeiji']:'';
			Db::name('config')->where(['config_sign'=>'kefuurl'])->update(['config_value'=>$kefuurl]);
// 			Db::name('config')->where(['config_sign'=>'kefufeiji'])->update(['config_value'=>$kefufeiji]);
			
			htajaxReturn(1,'修改成功');
     	}else{
 			$kefuurl = getConfig('kefuurl',0);
 			$kefufeiji = getConfig('kefufeiji',0);
	    	$this->assign('kefuurl',$kefuurl);
	    	$this->assign('kefufeiji',$kefufeiji);
	    	return view();
     	}
    }
      public function xiazai(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$android_url = isset($postdata['android_url'])?$postdata['android_url']:'';
	    	$ios_url = isset($postdata['ios_url'])?$postdata['ios_url']:'';
	    	$erweima_url = isset($postdata['erweima_url'])?$postdata['erweima_url']:'';
	    	$data = ['android_url'=>$android_url,'ios_url'=>$ios_url,'erweima_url'=>$erweima_url];
			Db::name('config')->where(['config_sign'=>'downdata'])->update(['config_value'=>json_encode($data)]);
			
			$inviteCode_show = isset($postdata['inviteCode_show'])?$postdata['inviteCode_show']:'';
			$inviteCode_show = intval($inviteCode_show);
			
		    $inviteCode_must = isset($postdata['inviteCode_must'])?$postdata['inviteCode_must']:'';
			$inviteCode_must = intval($inviteCode_must);
			
			$whatapp_show = isset($postdata['whatapp_show'])?$postdata['whatapp_show']:'';
			$whatapp_show = intval($whatapp_show);
			
			$whatapp_must = isset($postdata['whatapp_must'])?$postdata['whatapp_must']:'';
			$whatapp_must = intval($whatapp_must);
			
			$feiji_show = isset($postdata['feiji_show'])?$postdata['feiji_show']:'';
			$feiji_show = intval($feiji_show);
			
			$feiji_must = isset($postdata['feiji_must'])?$postdata['feiji_must']:'';
			$feiji_must = intval($feiji_must);
			$ndata = ['inviteCode_show'=>$inviteCode_show,'inviteCode_must'=>$inviteCode_must,
			           'whatapp_show'=>$whatapp_show,'whatapp_must'=>$whatapp_must,
			           'feiji_show'=>$feiji_show,'feiji_must'=>$feiji_must];
			           
			Db::name('config')->where(['config_sign'=>'regparam'])->update(['config_value'=>json_encode($ndata)]);
			
			htajaxReturn(1,'修改成功');
     	}else{
     	    
     	    $roude = rand(0,100);
     	    $logo = getConfig('logourl',0);
     	  //  $logo = '/upload/goodpic/logo/'.$name;
     	    $html = "logo设置：<a data_id='$roude' class='layui-btn layui-btn-mini layui-btn-warm logoset'>设置</a> <img src='{$logo }'  style='width: 90px;height: 80px;cursor: pointer;'  class='pic' alt='' />";
     	    
     	    
 			$downdata = getConfig('downdata',0);
 			$downdata = json_decode($downdata,true);
 			
 			$regparam = getConfig('regparam',0);
 			$regparam = json_decode($regparam,true);
 			
	    	$this->assign('downdata',$downdata);
	    	$this->assign('regparam',$regparam);
	    	$this->assign('logo',$logo);
	    	$this->assign('html',$html);
	    	return view();
     	}
    }
    function huilv(){
     	if(request()->isAjax()){//ajax
	    	$postdata =  $this->request->post();
	    	$usdt2trx = isset($postdata['usdt2trx'])?$postdata['usdt2trx']:'';
	    	$anquan_pwd = isset($postdata['anquan_pwd'])?$postdata['anquan_pwd']:'';
	    	if($usdt2trx>25||$usdt2trx<10){
				htajaxReturn(0,'离谱了');
	    	}
	    	if(!$this->yanzhenganquanpwd($anquan_pwd)){
				htajaxReturn(0,'安全密码错误');
	    	}
	    	Db::name('config')->where(['config_sign'=>'usdt2trx'])->update(['config_value'=>$usdt2trx]);
			htajaxReturn(1,'修改成功');
     	}else{
     		$usdt2trx = getConfig('usdt2trx',0);
	    	$this->assign('usdt2trx',$usdt2trx);
	    	return view();
     	}
    }
    function huoquzuixin(){
    	$autocon = new autocon();
    	$autocon->getHuilvxin();
		htajaxReturn(1,'修改成功');
    }
    function lunbotu(){
     	if(request()->isAjax()){
     	}else{
     		$list = Db::name('lunbo')->order('order_num  asc')->select();
     		foreach($list as $k=> $val){
	    		$list[$k]['pic'] = getGoopic($val['pic']);
     		}
	    	$this->assign('list',$list);
	    	return view();
     	}
    }
    function deletelunbo(){
    	$postdata =  $this->request->post();
    	$mainid = isset($postdata['mainid'])?$postdata['mainid']:'';
 		$list = Db::name('lunbo')->where(['id'=>$mainid])->delete();
		htajaxReturn(1,'成功');
    }
    function xinzenglunbo(){
 		$list = Db::name('lunbo')->insert(['pic'=>'']);
		htajaxReturn(1,'成功');
    }
    function xiugailunbo(){
    	$postdata =  $this->request->post();
    	$mainid = isset($postdata['mainid'])?$postdata['mainid']:'';
    	$pic = isset($postdata['url'])?$postdata['url']:'';
    	$scrollpic = Db::name('lunbo')->where(['id'=>$mainid])->update(['pic'=>$pic]);//查询数据
		ajaxReturn(1,'操作成功',['pic'=>getGoopic($pic)]);
    
    }
	public function paixv(){
    	$postdata =  $this->request->post();
    	$saveString = isset($postdata['saveString'])?$postdata['saveString']:'';
    	$idarray = explode(',',$saveString);
    	$newarray = [];
    	$i =1;
    	foreach($idarray as $val){
    		$scrollpic = Db::name('lunbo')->where(['id'=>$val])->update(['order_num'=>$i]);//查询数据
    		$i++;
    	}
		ajaxReturn(1,'保存成功');
	}
    
}