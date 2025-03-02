<?php
namespace app\ api\ controller;
use think\ Cache;
use think\ Db;
use think\ Request;
use think\ Controller;
use think\ Lang;
class User extends BaseNologin {
    public function countryCode(){
    	$list = ['+1','+996','+880','+49','+44','+34','+33','+62','+39','+81','+82','+351','+7','+90','+505','+91','+92','+93','+94'];

        ajaxReturn(1, '成功',$list);
    }
    public function getLogo(){
   
    	$pageURL = 'https://'.$_SERVER["SERVER_NAME"] . getConfig('logourl',0);
		ajaxReturn(1, '成功',['logo'=>$pageURL]);
        
    }
    public function register(){
	   $postdata = request() -> post();
       $tel = isset($postdata["tel"]) ? $postdata["tel"] : '';
       $country_code = isset($postdata["country_code"]) ? $postdata["country_code"] : '';
       $email = isset($postdata["email"]) ? $postdata["email"] : '';
       $login_pwd = isset($postdata["login_pwd"]) ? $postdata["login_pwd"] : '';
       $anquan_pwd = isset($postdata["anquan_pwd"]) ? $postdata["anquan_pwd"] : '';
       $telegram = isset($postdata["telegram"]) ? $postdata["telegram"] : '';
       $whatsapp = isset($postdata["whatsapp"]) ? $postdata["whatsapp"] : '';
       $invite_code = isset($postdata["invite_code"]) ? $postdata["invite_code"] : '';
       $lang = Request::instance()->header('lang');
       $postdata['lang'] = $lang;
       if(empty($tel)&&empty($email)){
     	   ajaxReturn(0, 'ajax_手机或邮箱不能为空');
       }
       if(!empty($tel)&&!empty($email)){
       		feifaReturn(1);
       }
       if(!empty($tel)&&empty($country_code)){
       		feifaReturn(2);
       }
	   if(!empty($tel)){
	   		$reg = '/^[0-9]*$/';
		   	if(!preg_match($reg,$tel)){
	     	   ajaxReturn(0, 'ajax_手机格式错误');
	       }
			$have = Db::name('user')->where(['tel'=>$tel])->find();
			if(!empty($have)){
     		   ajaxReturn(0, 'ajax_该账户已注册');
			}
		}       
        if(!empty($email)){
        	$reg = '/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/';
		   	if(!preg_match($reg,$email)){
	     	   ajaxReturn(0, 'ajax_邮箱格式');
	       }
			$have = Db::name('user')->where(['email'=>$email])->find();
			if(!empty($have)){
     		   ajaxReturn(0, 'ajax_该账户已注册');
			}
		}   
       	$regparam = getConfig('regparam',0);
    	$regparam = json_decode($regparam,true);
       if($regparam['inviteCode_must'] ==1&&$invite_code!=168168&&empty(DB::name('user')->where(['invite_code'=>$invite_code])->find())){
     	   ajaxReturn(0, 'ajax_邀请码不存在');
       }
       if(empty($login_pwd)){
     	   ajaxReturn(0, 'ajax_登录密码不能为空');
       }
       if(strlen($login_pwd)>30||strlen($login_pwd)<6){
     	   ajaxReturn(0, 'ajax_登录密码长度');
       }
       $reg = '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/';
       if(!preg_match($reg,$login_pwd)){
//   	   ajaxReturn(0, 'ajax_登录密码格式');
       }
       if(empty($anquan_pwd)){
     	   ajaxReturn(0, 'ajax_安全密码不能为空');
       }
       if(strlen($anquan_pwd)>30||strlen($anquan_pwd)<6){
     	   ajaxReturn(0, 'ajax_安全密码长度');
       }
       if(!preg_match($reg,$anquan_pwd)){
//   	   ajaxReturn(0, 'ajax_安全密码格式');
       }
       if($regparam['feiji_must'] ==1&&empty($telegram)){
     	   ajaxReturn(0, 'ajax_telegram空');
       }
       if($regparam['whatapp_must'] ==1&&empty($whatsapp)){
     	   ajaxReturn(0, 'ajax_whatsapp空');
       }
       //一个IP最多能注册3个
       $zhuce_ip = get_client_ip();
       $ipzhuce_count  = getConfig('zhuce_ip_limit',0);
       if($zhuce_ip!='0.0.0.0'&&$ipzhuce_count>0){
	       $zhuceCount = Db::name('user')->where(['zhuce_ip'=>$zhuce_ip])->count();
	       if($zhuceCount>=$ipzhuce_count){
	     	   ajaxReturn(0, 'ajax_注册失败，请联系客服');
	       }
       }
       
       $user_id = $this->newUser($postdata);
       //生成token
      	$v = 1;
		$key = mt_rand();
		$hash = hash_hmac("sha1", $v . mt_rand() . time(), $key, true);
		$token = str_replace('=', '', strtr(base64_encode($hash), '+/', '-_')); 
		
		
		Db::name('user')->where(['id'=>$user_id])->update(['token'=>$token,'wrong_time'=>0,'last_login_time'=>date('Y-m-d H:i:s')]);
// 		ajaxReturn(1, 'ajax_登录成功',['token'=>$token]);
		
 	   ajaxReturn(1, 'ajax_注册成功',['token'=>$token]);
    }
    function login(){
       $postdata = request() -> post();
       $login_type = isset($postdata["login_type"]) ? $postdata["login_type"] : '';
       $email = isset($postdata["email"]) ? $postdata["email"] : '';
       $tel = isset($postdata["tel"]) ? $postdata["tel"] : '';
       $country_code = isset($postdata["country_code"]) ? $postdata["country_code"] : '';
       $login_pwd = isset($postdata["login_pwd"]) ? $postdata["login_pwd"] : '';
       if(!in_array($login_type,['email','tel'])){
       		feifaReturn(1);
       }
       if($login_type == 'tel'&&empty($tel)){
 	  	  ajaxReturn(0, 'ajax_手机不能为空');
       }
       if($login_type == 'email'&&empty($email)){
 	  	  ajaxReturn(0, 'ajax_邮箱不能为空');
       }
       if(empty($login_pwd)){
 	  	  ajaxReturn(0, 'ajax_登录密码不能为空');
       }
       if($login_type =='email'){
       		$userInfo = Db::name('user')->where(['email'=>$email,'status'=>1])->find(); 
       }elseif($login_type =='tel'){	
       		$userInfo = Db::name('user')->where(['tel'=>$tel,'country_code'=>$country_code,'status'=>1])->find(); 
       }
       if(isset($userInfo)&&!empty($userInfo)){
	       	if($userInfo['wrong_time']>=8){
 	  	 		 ajaxReturn(0, 'ajax_登录次数受限');
	       	}
       	    $salt = $userInfo['salt'];
	   		$zhengquepwd = md5($login_pwd.$salt);
	   		if($zhengquepwd!=$userInfo['login_pwd']){
				Db::name('user')->where(['id'=>$userInfo['id']])->setInc('wrong_time',1);
 	  	 		ajaxReturn(0, 'ajax_用户名或密码错误');
	   		}
       }else{
 			 ajaxReturn(0, 'ajax_用户名或密码错误');
       }
       //生成token
      	$v = 1;
		$key = mt_rand();
		$hash = hash_hmac("sha1", $v . mt_rand() . time(), $key, true);
		$token = str_replace('=', '', strtr(base64_encode($hash), '+/', '-_')); 
		Db::name('user')->where(['id'=>$userInfo['id']])->update(['token'=>$token,'wrong_time'=>0,'last_login_time'=>date('Y-m-d H:i:s')]);
		ajaxReturn(1, 'ajax_登录成功',['token'=>$token]);
    }
    function newUser($postdata){
    	$tel = isset($postdata["tel"]) ? $postdata["tel"] : '';
        $country_code = isset($postdata["country_code"]) ? $postdata["country_code"] : '';
        $email = isset($postdata["email"]) ? $postdata["email"] : '';
        $login_pwd = isset($postdata["login_pwd"]) ? $postdata["login_pwd"] : '';
        $anquan_pwd = isset($postdata["anquan_pwd"]) ? $postdata["anquan_pwd"] : '';
        $telegram = isset($postdata["telegram"]) ? $postdata["telegram"] : '';
        $whatsapp = isset($postdata["whatsapp"]) ? $postdata["whatsapp"] : '';
        $invite_code = isset($postdata["invite_code"]) ? $postdata["invite_code"] : '';
        $lang = isset($postdata["lang"]) ? $postdata["lang"] : '';
        $from_who = 0;
        $laiyuan = 0;
        if(!empty($invite_code)){
   			$from = Db::name('user')->where(['invite_code'=>$invite_code])->find();
   			if(!empty($from)){
     		   $from_who = $from['id'];
     		   if($from['laiyuan'] == 0){
     			   $laiyuan = $from['id'];
     		   }else{
     			   $laiyuan = $from['laiyuan'];
     		   }
   			}
        }
    	$data = ['tel'=>$tel,'country_code'=>$country_code,'email'=>$email,'login_pwd'=>$login_pwd,'anquan_pwd'=>$anquan_pwd,
    	'zhuce_ip'=>get_client_ip(),
    	'telegram'=>$telegram,'whatsapp'=>$whatsapp,'from_who'=>$from_who,'lang'=>$lang];
   		Db::name('user')->insert($data);
		$user_id = Db::name('user')->getLastInsID();
   		//体验金
   		$tiyanjin = getConfig('tiyanjin',0);
   		if($tiyanjin>0){
   			basicmoneyChange('bh_体验金',$tiyanjin,$user_id,[]);
   			shengjiVip($user_id);
   		}
   		//默认VIP0。如果有任务就加上今天的挖矿
   		$userLevel = Db::name('user')->where(['id'=>$user_id])->value('vip_level');
   		$vip0 = DB::name('vip_set')->where(['level'=>$userLevel])->find();
   		$haveTask = 	Db::name('user_task')->where(['user_id'=>$user_id])->find();//防止上面体验今升级然后有任务，不要重复添加了
   		if(!empty($vip0)&&$vip0['task_money']>0&&empty($haveTask)){
			$randtask = Db::name('task')->where([])->orderRaw('rand()')->find();
			$randtask = getOnerenwu($vip0['level']);
			$today = date('Y-m-d');
			$usertaskdata = ['user_id'=>$user_id,'task_id'=>$randtask['id'],'taskname'=>$randtask['taskname'],
		 	'day'=>$today,'status'=>1,'money'=>$vip0['task_money'],'type'=>3,'add_time'=>date('Y-m-d H:i:s')];
		 	// Db::name('user_task')->insert($usertaskdata);
   		}
// 		$newInviteCode = 26485+$user_id;
		$newInviteCode = $this->getInviteCode($user_id);
		$pool='zxcvbnmkjahsgflpoiuytrewq';//定义一个验证码池，验证码由其中几个字符组成
		$word_length=6;//验证码长度
	  	$salt = '';//盐值
	    for ($i = 0, $mt_rand_max = strlen($pool) - 1; $i < $word_length; $i++)
	    {
	        $salt .= $pool[mt_rand(0, $mt_rand_max)];
	    }
	    $login_pwd = md5($login_pwd.$salt);
	    $anquan_pwd = md5($anquan_pwd.$salt);
		Db::name('user')->where(['id'=>$user_id])->update(['invite_code'=>$newInviteCode,'salt'=>$salt,'login_pwd'=>$login_pwd,
		'anquan_pwd'=>$anquan_pwd,'add_time'=>date('Y-m-d H:i:s'),'laiyuan'=>$laiyuan]);
		if($from_who>0){//有上级
			$caozuotemp = ['pk_id'=>$user_id,'type'=>'updateXiaxian','add_time'=>date('Y-m-d H:i:s'),
			'op_time'=>date('Y-m-d H:i:s'),'extra'=>json_encode([])];
			DB::name('caozuo')->insert($caozuotemp);
		}
		return $user_id;
    }
    
    function sendEmail(){
    	
     	ajaxReturn(0, 'login_找回密码提示');
    	$postdata = request() -> post();
        $email = isset($postdata["email"]) ? $postdata["email"] : '';
        $reg = '/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/';
		if(!preg_match($reg,$email)){
	     	 ajaxReturn(0, 'ajax_邮箱格式');
	    }
	     //生成随机码
	 	$pool='0123456789';//定义一个验证码池，验证码由其中几个字符组成
		$word_length=6;//验证码长度
	  	$verCode = '';//验证码
	    for ($i = 0, $mt_rand_max = strlen($pool) - 1; $i < $word_length; $i++)
	    {
	        $verCode .= $pool[mt_rand(0, $mt_rand_max)];
	    }
		Db::name('email_vercode') -> insert(['email'=>$email,'vercode'=>$verCode,'add_time'=>date('Y-m-d H:i:s')]);
 	    ajaxReturn(1, '成功');
    }
    
    
     //输入当前的用户编号自增长的id 
function getInviteCode($userId)
{
    // $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $chars = '0123456789';
    $num = strlen($chars);
    $str = '';
    while ($userId > 0) {
        $mod = $userId % $num;
        $userId = ($userId - $mod) / $num;
        $str = $chars[$mod] . $str;
    }

    $cd=$this->createNonceStr(6 - strlen($str));

    // 不足用随机字符串补充，10表示邀请码邀请10位
    $str = str_pad($str, 6, $cd, STR_PAD_LEFT);
    return $str;
}

   function createNonceStr($length = 16)
 {
    // $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $chars = '0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
        return $str;
  }
    
    public function findpwd(){
	   $postdata = request() -> post();
       $email = isset($postdata["email"]) ? $postdata["email"] : '';
       $vercode = isset($postdata["vercode"]) ? $postdata["vercode"] : '';
       $login_pwd = isset($postdata["login_pwd"]) ? $postdata["login_pwd"] : '';
       $login_pwd2 = isset($postdata["login_pwd2"]) ? $postdata["login_pwd2"] : '';
       $anquan_pwd = isset($postdata["anquan_pwd"]) ? $postdata["anquan_pwd"] : '';
       $anquan_pwd2 = isset($postdata["anquan_pwd2"]) ? $postdata["anquan_pwd2"] : '';
       if(empty($email)){
 	  	  ajaxReturn(0, 'ajax_邮箱不能为空');
       }
        $reg = '/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/';
		if(!preg_match($reg,$email)){
	     	 ajaxReturn(0, 'ajax_邮箱格式');
	    }
        if(empty($vercode)){
     	   ajaxReturn(0, 'ajax_验证码不能为空');
       }
       if(empty($login_pwd)){
     	   ajaxReturn(0, 'ajax_登录密码不能为空');
       }
       if(strlen($login_pwd)>30||strlen($login_pwd)<6){
     	   ajaxReturn(0, 'ajax_登录密码长度');
       }
       $reg = '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]+$/';
       if(!preg_match($reg,$login_pwd)){
     	   ajaxReturn(0, 'ajax_登录密码格式');
       }
       if($login_pwd2!=$login_pwd){
     	   ajaxReturn(0, 'ajax_两次登录密码不一样');
       }
       if(empty($anquan_pwd)){
     	   ajaxReturn(0, 'ajax_安全密码不能为空');
       }
       if(strlen($anquan_pwd)>30||strlen($anquan_pwd)<6){
     	   ajaxReturn(0, 'ajax_安全密码长度');
       }
       if(!preg_match($reg,$anquan_pwd)){
     	   ajaxReturn(0, 'ajax_安全密码格式');
       }
       if($anquan_pwd2!=$anquan_pwd){
     	   ajaxReturn(0, 'ajax_两次安全密码不一样');
       }
      //验证码5分钟有效
    	$fiveMin = date('Y-m-d H:i:s',time()-10*60);
		$exist = Db::name('email_vercode')-> where(['email'=>$email,'add_time'=>['egt',$fiveMin]])->order('add_time desc')->find();
    	if(empty($exist)||$exist['vercode']!=$vercode){
			Db::name('user')->where(['email'=>$email])->setInc('wrong_time',1);
//  		ajaxReturn(0,'ajax_验证码有误');
    	}
    	$existUser = Db::name('user')->where(['email'=>$email])->find();
    	if(empty($existUser)){
    		ajaxReturn(0,'ajax_该用户不存在');
    	}
    	if($existUser['wrong_time']>=8){
    		ajaxReturn(0,'ajax_找回密码受限');
    	}
    	$login_pwd = md5($login_pwd.$existUser['salt']);
	    $anquan_pwd = md5($anquan_pwd.$existUser['salt']);
		Db::name('user')->where(['id'=>$existUser['id']])->update(['login_pwd'=>$login_pwd,'anquan_pwd'=>$anquan_pwd]);
		Db::name('user')->where(['email'=>$email])->update(['wrong_time'=>0]);
 	    ajaxReturn(1, 'ajax_找回成功');
    }
    function regparam(){
    	$regparam = getConfig('regparam',0);
    	$regparam = json_decode($regparam,true);
    	
		ajaxReturn(1, 'ajax_登录成功',['regparam'=>$regparam]);
    }
	public function shareurl($code){
	    
	    $hostName = str_replace("ht.","",$_SERVER['HTTP_HOST']);
	    $hostName = str_replace("s.","",$_SERVER['HTTP_HOST']);
        $hostName = $_SERVER['REQUEST_SCHEME'].'://'.$hostName.'/';
        $yq_url = $hostName.'#/pages/login/register?code='.$code;
        header("location:$yq_url");exit();
		
	}
}