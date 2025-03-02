<?php
namespace app\api\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Lang;
class Base extends Controller
{
    protected $userInfo;
	protected $currentLang;
	protected function _initialize()
    {
    	header("Access-Control-Allow-Origin:*");
		header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header("Access-Control-Allow-Headers:*");
        header("Access-Control-Max-Age: 86400");
        
        $this->currentLang = Lang::detect();
        $token = Request::instance()->header('token');
    	if(empty($token)){
    		ajaxReturn(-1,'ajax_登录过期');
    	}
        $this->userInfo = Db::name('user')-> where(['token'=>$token,'status'=>1])->find();
        if(empty($this->userInfo)){
    		ajaxReturn(-1,'ajax_登录过期');
        }else{
        	
        }
        $this->addAtionLog();
    }
     function addAtionLog() {
        $param = json_encode(request()->post(), JSON_UNESCAPED_UNICODE);
        $user_id = isset($this->userInfo['id'])?$this->userInfo['id']:'';
       	Db::name('user_action_log')->insert([
            'user_id'=>$user_id,
            'ca'=> request()->controller().'/'.request()->action(),
            'lang'=>  $this->currentLang,
            'param'=> $param,
            'content'=> 'token='.Request::instance()->header('token'),
            'add_time'=> date('Y-m-d H:i:s'),
            'ip'=>get_client_ip()
        ]);
    }
}


