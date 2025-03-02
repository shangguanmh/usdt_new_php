<?php
namespace app\hnv\controller;
use think\Controller;
use think\Db;
use think\Request;
use Verify; 
use GoogleAuthenticator; 
class login extends Controller{	
	protected function _initialize()
    {
        parent::_initialize();
        $this->request = Request::instance();
        $this->params = $this->request->param(true);
    }
 
	public function loginVertfy() {
		$postdata =  $this->request->post();
    	$user_name=isset($postdata["username"])?$postdata["username"]:'';
    	$password=isset($postdata["passwd"])?$postdata["passwd"]:'';
    	$verify_code=isset($postdata["captcha_code"])?$postdata["captcha_code"]:'';
		if (empty($user_name)) {
			ajaxReturn(0,'用户名不能为空');
		}
		if (empty($password)) {
			ajaxReturn(0,'密码不能为空');
		}
		if (empty($verify_code)) {
			ajaxReturn(0,'验证码不能为空');
		}
		// 验证码验证
    	$Verify = new Verify();
		if ($Verify->check($verify_code) === false) {
			ajaxReturn(0,'验证码错误');
		}
		$request = Request::instance();
		if(strpos($request->domain(),'etao68') !== false){ 
		  }else{
//		  	echo '';exit();
		  }
		$denglu = false;
		$userInfo = Db::name('gluser')->where([
            'user_name' => $user_name,
			'status' => 1,
		])->find();
		if(isset($userInfo['wrong'])&&$userInfo['wrong']>=5){
			ajaxReturn(0,'密码次数达到5次，不能登录');
		}
		if(!empty($userInfo)){
			if($userInfo['login_type'] == 1&&$userInfo['password']==md5($password.$userInfo['saft'])){
				$denglu = true;
			}
			if($userInfo['login_type'] == 2){
				$ga = new GoogleAuthenticator();
				if($ga->verifyCode($userInfo['ga_code'], $password, 1)){
					$denglu = true;
				}
			}
		}else{
			
		}
		$param = json_encode($_POST, JSON_UNESCAPED_UNICODE);
        if (!$denglu) {
        	Db::name('gluser')->where(['user_name'=>$user_name])->setInc('wrong',1);
	        Db::name('htaction_log')->insert([
	            'user_id'=>'',
	            'ca'=> 'login/loginVertfy',
	            'param'=> $param,
	            'content'=> '登陆失败',
	            'add_time'=> date('Y-m-d H:i:s'),
	            'ip'=>get_client_ip()
	        ]);
			ajaxReturn(0,'用户名或密码错误');
        }
		Db::name('htaction_log')->insert([
         		'user_id'=>'',
	            'ca'=> 'login/loginVertfy',
	            'param'=> $param,
	            'content'=> '登陆成功',
	            'add_time'=> date('Y-m-d H:i:s'),
	            'ip'=>get_client_ip()
        ]);
        Db::name('gluser')->where(['id'=>$userInfo['id']])->update(['session_id'=>session_id(),'wrong'=>0]);
        session('sk_name', $userInfo['user_name']);
        session('sk_id', $userInfo['id']);
		ajaxReturn(1,'登陆成功',[]);
    }
    public function login() {
    	$request = Request::instance();
		if(strpos($request->domain(),'ht.usdt-lp.com') !== false){ 
		  }else{
//		  	echo '';exit();
		  }
		return $this->fetch();
    }
    /**
	 * 退出登录
	*/
    public function logout() {
        session('sk_name', null);
    	$this->redirect('Login/login');
    }
	public function relogin(){
        session('sk_name', null);
		$denglu = url('Login/login');
		echo "<script>alert('你的帐号在别处登录，请重新登录');location.href='$denglu'; </script>";
	}
    /**
	 * 获取验证码
    */
    public function verifyCode() {
    	$Verify = new Verify();
        $Verify->codeSet = '0123456789';
		$Verify->length   = 4;
		$Verify->fontttf   = '4.ttf';//字体
        $Verify->useCurve   = false;//是否画混淆曲线
        $Verify->useNoise = true;//是否添加杂点
		$Verify->entry();
    }
}