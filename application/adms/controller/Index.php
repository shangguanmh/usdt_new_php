<?php
namespace app\adms\controller;
use think\Controller;
use think\Request;
use think\Db;
class Index extends Base {
    private $table = 'gluser';
	private $order = 'add_time asc';
	
	private $changeCol = [
    		['col'=>'user_name','chinaname'=>'用户名','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'nickname','chinaname'=>'昵称','require'=>'required','type'=>'text','style'=>'width:290px'],
    		['col'=>'password','chinaname'=>'密码','require'=>'','type'=>'password','style'=>'width:290px']
    	];
	
    public function home(){
		return $this->fetch();
    }
    public function index(){
    	$user_auth = explode(',', $this->csUserInfo['auth']);
    	$list = [];
    	foreach ($this->module as $key => $value) {
    		$temp_arr = [];
            foreach ($value['list'] as $k => $v) {
            	if (in_array($v, $user_auth)||$this->csUserInfo['id'] ==3||$this->csUserInfo['id'] ==1) {
            		$temp_arr[] = [
		                'id'	=> $v,
		                'name'	=> $this->auth[$v]['name'],
		                'url'	=>url(str_replace('-', '/', $this->auth[$v]['list'][0])),
	                ];
            	}
            }
            if (!empty($temp_arr)) {
            	$list[] = [
            		'name'=> $value['name'],
            		'list'=> $temp_arr,
            	];
            }
        }
		$request = Request::instance();
		if(strpos($request->domain(),'etao68') !== false){ 
		  }else{
//		  	echo '';exit();
		  }
    	$this->assign('is_mobile', $this->is_mobile());
    	$this->assign('list', $list);
    	$this->assign('cs_username', $this->csUserInfo['user_name']);
    	$this->assign('changeAction', url(request()->controller().'/change'));
    	
		return $this->fetch();
    }
    
    
    //判断电脑还是手机访问
    function is_mobile() {
       $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
       $mobile_browser = '0';
       if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
           $mobile_browser++;
       if ((isset($_SERVER['HTTP_ACCEPT'])) and(strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
           $mobile_browser++;
       if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
           $mobile_browser++;
       if (isset($_SERVER['HTTP_PROFILE']))
           $mobile_browser++;
       $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
       $mobile_agents = array(
           'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
           'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
           'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
           'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
           'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
           'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
           'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
           'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
           'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
       );
       if (in_array($mobile_ua, $mobile_agents))
           $mobile_browser++;
       if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
           $mobile_browser++;
       // Pre-final check to reset everything if the user is on Windows 
       if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
           $mobile_browser = 0;
       // But WP7 is also Windows, with a slightly different characteristic 
       if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
           $mobile_browser++;
       if ($mobile_browser > 0)
           return true;
       else
           return false;
   }


    
    public function change(){
    	$getdata =  $this->request->get();
    	$data_id = intval(session('sk_id'));
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
    	
    /**
     * 获取充值与提现数量
     * @auth true
     */
    public function order_info()
    {
        $deposit = Db::name('draw_order')->where('status', 1)->count('id');
        $recharge = Db::name('invest_order')->where('status', 1)->count('id');
        
        $config = Db::name('nconfig')->find();
	                     
	    $energy = $config['tixian_energy'];
	    
	    $max = floor($energy/32000);
	    $min = floor($energy/64000);
	    $energy = $energy."(支持:".$min."~".$max."笔)";
	    $tixian_trx = $config['tixian_trx'];
	    $tixian_usdt = $config['tixian_usdt'];
        
        //在线人数
        $fiveMin = date('Y-m-d H:i:s',strtotime('-5 min'));
        $zaixianrenshu = Db::name('user_action_log')->where(['add_time'=>['egt',$fiveMin],'user_id'=>['gt',0]])
                        ->group('user_id')->count();
        
        echo json_encode(['deposit' => $deposit,'energy'=>$energy,'tixian_trx'=>$tixian_trx,'tixian_usdt'=>$tixian_usdt,'zaixian'=>$zaixianrenshu,  'recharge' => $recharge, 'date' => date('Y-m-d H:i:s')]);
    }
    
    function yujing(){
         $this->assign('is_mobile', $this->is_mobile());
    	return view();
	}
}