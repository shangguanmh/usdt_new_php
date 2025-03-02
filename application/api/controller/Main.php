<?php
namespace app\ api\ controller;
use think\ Cache;
use think\ Db;
use think\ Request;
use think\ Controller;
use think\ Lang;
class Main extends BaseNologin {
    protected $userInfo;
    public function LanguageList() {
  		$langlist = json_decode(getConfig('langlist',0),true);
  		$token = Request::instance()->header('token');
  		$defaultlLang = '';
    	if(!empty($token)){
    		$defaultlLang = Db::name('user')-> where(['token'=>$token])->value('lang');
    	}
  		if(empty($defaultlLang)){
		//根据IP获得..
  		$ipInfo = $this->http_get();	
  		$ipInfo = json_decode($ipInfo,true);
  		if(isset($ipInfo['countryCode'])){
  			if(in_array($ipInfo['countryCode'],['SA','YE','OM','KW','BH','QA','IQ','SY','JO','LB','PS','EG','SD','LY',
  			'TN','MR','DZ','KM','DJ','SO','MA'])){//阿拉伯语
  				$defaultlLang = 'ar';
  			}elseif(in_array($ipInfo['countryCode'],['TW','HK','MO','CN'])){//港澳台
  				$defaultlLang = 'cht';
  			}elseif(in_array($ipInfo['countryCode'],['DE','AT','LI','CH','LU','BE'])){//德语
  				$defaultlLang = 'de';
  			}elseif(in_array($ipInfo['countryCode'],['ES','AR','BO','CL','CO','CR','CU','DM','EC','SV','GQ','GT','HN'
  			,'MX','NI','PA','PY','PE','UY','VE'])){//西班牙语
  				$defaultlLang = 'es';
  			}elseif(in_array($ipInfo['countryCode'],['CG','MG','CM','CI','NE','SN','ML','RW','HT','TD','GN','BI','BJ','TG',
  			'CF','KM'])){//法语
  				$defaultlLang = 'fr';
  			}elseif(in_array($ipInfo['countryCode'],['ID'])){//印尼
  				$defaultlLang = 'id';
  			}elseif(in_array($ipInfo['countryCode'],['IT','SM','VA'])){//意大利语
  				$defaultlLang = 'it';
  			}elseif(in_array($ipInfo['countryCode'],['JP'])){//日语
  				$defaultlLang = 'jp';
  			}elseif(in_array($ipInfo['countryCode'],['KR'])){//韩语
  				$defaultlLang = 'kor';
  			}elseif(in_array($ipInfo['countryCode'],['BR','MZ','AO','PT','GW','TL','GQ','CV'])){//葡萄牙
  				$defaultlLang = 'pt';
  			}elseif(in_array($ipInfo['countryCode'],['RU','KZ','BY','KG','TJ'])){//俄语
  				$defaultlLang = 'ru';
  			}elseif(in_array($ipInfo['countryCode'],['TR','CY','BG','AZ','RO'])){//俄语
  				$defaultlLang = 'tr';
  			}else{
      				$defaultlLang = 'en';
  			}
  		}else{//获取错误
  			$defaultlLang = 'en';
  		}
  		}
  		$resu = [];
		foreach($langlist as $val){
			if($val['code'] == $defaultlLang){
				$resu = $val;
			}
		} 
		if(empty($resu)){
			$resu = $langlist[0];
		} 		
  		
        ajaxReturn(1, '成功', ['langlist'=>$langlist,'default'=>$resu]);
    }
    	function http_get()
	{
		$ip = get_client_ip();
//		$ip = '212.26.11.255';
	    $headers[] = "Content-Type: application/json";
	    $headers[] = "";
	    $curl = curl_init(); // 启动一个CURL会话
	    curl_setopt($curl, CURLOPT_URL, 'http://ip-api.com//json/'.$ip.'?lang=zh-CN');
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_TIMEOUT,3);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $tmpInfo = curl_exec($curl);     
	    //关闭URL请求
	    curl_close($curl);
	    return $tmpInfo;   
	}
    function MenuText() {
        $menu = [
        	'ZHB'=>ZHB,
        	'logo'=> 'https://'.$_SERVER["SERVER_NAME"] . getConfig('logourl',0),
            'index' => [
                //index
            	'tab1' => lang('tab1'),
                'tab2' => lang('tab2'),
                'tab3' => lang('tab3'),
                'tab4' => lang('tab4'),
                'tab5' => lang('tab5')
            ],
            'main' => [
                //首页
                'main_充值' => lang('main_充值'),
                'main_提款' => lang('main_提款'),
                'main_VIP' => lang('main_VIP'),
                'main_团队' => lang('main_团队'),
                'main_app' => lang('main_app'),
                'main_白皮书' => lang('main_白皮书'),
                'main_常见问题' => lang('main_常见问题'),
                'main_基础账户' => lang('main_基础账户'),
                'main_佣金账户' => lang('main_佣金账户'),
                'main_体验账户' => lang('main_体验账户'),
                'main_理财账户' => lang('main_理财账户'),
                'main_总资产余额' => lang('main_总资产余额'),
                'main_合作伙伴' => lang('main_合作伙伴'),
                'main_累计利润' => lang('main_累计利润'),
                'main_会员数量' => lang('main_会员数量'),
                'main_关于我们' => lang('main_关于我们'),
                'main_我们是最佳平台' => lang('main_我们是最佳平台'),
                'main_平台数据显示' => lang('main_平台数据显示'),
                'main_关于我们详细' => lang('main_关于我们详细'),
                'main_加入成为' => lang('main_加入成为'),
                'main_加入详细文字' => lang('main_加入详细文字'),
                'main_在线客服' => lang('main_在线客服'),
                'main_客服文字' => lang('main_客服文字'),
                'main_详情' => lang('main_详情'),
                'main_收益列表' => lang('col_收益列表'),
                'main_游戏' => lang('main_游戏'),
                'main_下载APP' => lang('main_下载APP'),
            ],
            'jiaoyi' => [
                //交易
                'jj_挖矿正在运行' => lang('jj_挖矿正在运行'),
                'jj_领取' => lang('jj_领取'),
                'jj_已领取' => lang('jj_已领取'),
                'jj_已过期' => lang('jj_已过期'),
                'jj_查看全部' => lang('jj_查看全部'),
                'jj_交易利润' => lang('jj_交易利润')
            ],
            //投资
            'touzi' => [
                'jj_投资记录' => lang('jj_投资记录')
            ],
            //邀请
            'yaoqing' => [
                'yq_总返佣' => lang('yq_总返佣'),
                'yq_总挖矿返佣' => lang('yq_总挖矿返佣'),
                'yq_总充值返佣' => lang('yq_总充值返佣'),
                'yq_今日返佣' => lang('yq_今日返佣'),
                'yq_昨日返佣' => lang('yq_昨日返佣'),
                'yq_今日邀请人数' => lang('yq_今日邀请人数'),
                'yq_昨日邀请人数' => lang('yq_昨日邀请人数'),
                'yq_推荐并赚取奖励' => lang('yq_推荐并赚取奖励'),
                'yq_分享推荐' => lang('yq_分享推荐'),
                'yq_邀请码' => lang('yq_邀请码'),
                'yq_奖励文字' => lang('yq_奖励文字'),
                'yq_邀请统计' => lang('yq_邀请统计'),
                'yq_团队人数' => lang('col_团队人数'),
                'yq_团队业绩' => lang('yq_团队业绩'),
            ],
            //我的
            'wode' => [
                'me_总资产余额' => lang('me_总资产余额'),
                'me_充值' => lang('me_充值'),
                'me_提款' => lang('me_提款'),
                'me_充值数量' =>lang( 'me_充值数量'),
                'me_充值提醒' => lang('me_充值提醒'),
                'me_APP服务' => lang('me_APP服务'),
                'me_团队' => lang('me_团队'),
                'me_财务记录' => lang('me_财务记录'),
                'me_投资记录' => lang('me_投资记录'),
                'me_转账' => lang('me_转账'),
                'me_邀请' => lang('me_邀请'),
                'me_通知' => lang('me_通知'),
                'me_修改登录密码' => lang('me_修改登录密码'),
                'me_修改安全密码' => lang('me_修改安全密码'),
                'me_登出' => lang('me_登出')
            ],
            'tuandui' => [
                //团队
                'team_账户' => lang('team_账户'),
                'team_财务' => lang('team_财务'),
                'team_加入时间' => lang('team_加入时间')
            ],
            'caiwu' => [
                //财务记录
                'caiwu_基础账户' => lang('caiwu_基础账户'),
                'caiwu_佣金账户' => lang('caiwu_佣金账户'),
                'caiwu_理财账户' => lang('caiwu_理财账户'),
                'caiwu_体验账户' => lang('caiwu_体验账户')
            ],
            'zhuanzhang' => [
                //转账
                'zz_账户' => lang('zz_账户'),
                'zz_请输入安全密码' => lang('zz_请输入安全密码')
            ],
            'tongzhi' => [
                //通知
                'notice_一键已读' => lang('notice_一键已读')
            ],
            'denglumima' => [
                //修改登录密码
                'xgdl_旧登录密码' => lang('xgdl_旧登录密码'),
                'xgdl_新登录密码' => lang('xgdl_新登录密码'),
                'xgdl_确认新密码' => lang('xgdl_确认新密码'),
                //修改安全密码
                'xgaq_旧安全密码' => lang('xgaq_旧安全密码'),
                'xgaq_新安全密码' => lang('xgaq_新安全密码'),
                'xgaq_确认登录密码' => lang('xgaq_确认登录密码')
            ],
          	'vip' => [
	          	'vip_当前等级'=>lang('vip_当前等级'),
			    'vip_立即升级'=>lang('vip_立即升级'),
			    'vip_充值数量1'=>lang('vip_充值数量1'),
			    'vip_基础'=>lang('vip_基础'),
			    'vip_返利'=>lang('vip_返利'),
			    'vip_等级'=>lang('vip_等级'),
			    'vip_收益'=>lang('vip_收益'),
			    'vip_充值返利'=>lang('vip_充值返利'),
			    'vip_挖矿返利'=>lang('vip_挖矿返利'),
			    'vip_充值数量2'=>lang('vip_充值数量2'),
			    'vip_升级金额'=>lang('vip_升级金额'),
			    'vip_有问题'=>lang('vip_有问题')
   			 ],
            'gonggao' => [
                //公告
                'gg_公告' => lang('gg_公告'),
                'gg_下一条' => lang('gg_下一条'),
                'gg_上一条' => lang('gg_上一条'),
                'gg_我知道了' => lang('gg_我知道了'),
                'gg_详细' => lang('gg_详细')
            ],
            'webtitle'=>[
            	'title_首页'=>lang('title_首页'),
			    'title_交易'=>lang('title_交易'),
			    'title_投资'=>lang('title_投资'),
			    'title_邀请'=>lang('title_邀请'),
			    'title_我的'=>lang('title_我的'),
			    'title_团队'=>lang('title_团队'),
			    'title_财务记录'=>lang('title_财务记录'),
			    'title_投资记录'=>lang('title_投资记录'),
			    'title_转账'=>lang('title_转账'),
			    'title_通知'=>lang('title_通知'),
			    'title_修改登录密码'=>lang('title_修改登录密码'),
			    'title_修改安全密码'=>lang('title_修改安全密码'),
			    'title_投资记录'=>lang('title_投资记录'),
			    'title_login'=>lang('title_登录'),
			    'title_注册'=>lang('title_注册'),
			    'title_reg'=>lang('title_注册'),
			    'title_忘记密码'=>lang('title_忘记密码'),
			    'title_app'=>lang('title_app'),
			    'title_vip等级'=>lang('title_vip等级'),
			    'title_充值'=>lang('title_充值'),
			    'title_提款'=>lang('title_提款'),
			    'title_公告详细'=>lang('title_公告详细'),
			    'title_游戏'=>lang('title_游戏'),
			    'title_游戏房间'=>lang('title_游戏房间'),
			    'title_任务'=>lang('title_任务'),
            ],
            'login'=>[
        	    'EmailLogin'=>lang('login_邮箱登录'),
			    'telLogin'=>lang('login_手机号登录'),
			    'rememberPwd'=>lang('login_自动登录'),
			    'login'=>lang('login_登录'),
			    'register'=>lang('login_注册'),
			    'login_手机号'=>lang('login_手机号'),
			    'forgetPwd'=>lang('login_忘记密码'),
			    'login_邮箱注册'=>lang('login_邮箱注册'),
			    'login_emailreg'=>lang('login_邮箱注册'),
			    'login_telreg'=>lang('login_手机号注册'),
			    'login_手机号注册'=>lang('login_手机号注册'),
			    'email'=>lang('login_邮箱'),
			    'loginPwd'=>lang('login_登录密码'),
			    'login_Telegram'=>lang('login_Telegram'),
			    'login_Whatsapp'=>lang('login_Whatsapp'),
			    'login_邀请码'=>lang('login_邀请码'),
			    'login_验证码'=>lang('login_验证码'),
			    'login_确认密码'=>lang('login_确认密码'),
			    'login_安全密码'=>lang('login_安全密码'),
			    'login_SecurityCode'=>lang('login_安全密码'),
			    'login_确认安全密码'=>lang('login_确认安全密码'),
			    'login_发送邮件'=>lang('login_发送邮件'),
			    'login_找回密码提示'=>lang('login_找回密码提示'), //.getConfig('kefufeiji',0)
			    'login_邮箱找回'=>lang('login_邮箱找回'),
    			'login_手机号找回'=>lang('login_手机号找回'),
            ],
            'task'=>[
                'col_邀请好友获取任务'=>lang('col_邀请好友获取任务'),
                'col_充值获取任务'=>lang('col_充值获取任务'),
			    'col_今日所有任务'=>lang('col_今日所有任务'),
			    'col_今日剩余任务'=>lang('col_今日剩余任务'),
			    'col_去完成'=>lang('col_去完成'),
			    'col_进行中'=>lang('col_进行中'),
			    'col_价格'=>lang('col_价格'),
			    'col_已完成'=>lang('col_已完成'),
			    'col_完成时间'=>lang('col_完成时间'),
			    'col_任务大厅'=>lang('col_任务大厅'),
			    'col_任务刷新倒计时'=>lang('col_任务刷新倒计时'),
            ],
            'common' => [
                //公共部分
                'common_没有更多数据' => lang('common_没有更多数据'),
                'common_查看更多' => lang('common_查看更多'),
                'common_正在刷新' => lang('common_正在刷新'),
                'common_确认' => lang('common_确认'),
                'col_充电完成' => lang('col_充电完成'),
                "uni.app.quit"=> lang("uni.app.quit"),
			    "uni.async.error"=> lang("uni.async.error"),
			    "uni.showActionSheet.cancel"=> lang("uni.showActionSheet.cancel"),
			    "uni.showToast.unpaired"=> lang("uni.showToast.unpaired"),
			    "uni.showLoading.unpaired"=> lang("uni.showLoading.unpaired"),
			    "uni.showModal.cancel"=> lang("uni.showModal.cancel"),
			    "uni.showModal.confirm"=> lang("uni.showModal.confirm"),
			    "uni.chooseImage.cancel"=> lang("uni.chooseImage.cancel"),
			    "uni.chooseImage.sourceType.album"=> lang("uni.chooseImage.sourceType.album"),
			    "uni.chooseImage.sourceType.camera"=> lang("uni.chooseImage.sourceType.camera"),
			    "uni.chooseVideo.cancel"=> lang("uni.chooseVideo.cancel"),
			    "uni.chooseVideo.sourceType.album"=> lang("uni.chooseVideo.sourceType.album"),
			    "uni.chooseVideo.sourceType.camera"=> lang("uni.chooseVideo.sourceType.camera"),
			    "uni.previewImage.cancel"=> lang("uni.previewImage.cancel"),
			    "uni.previewImage.button.save"=> lang("uni.previewImage.button.save"),
			    "uni.previewImage.save.success"=> lang("uni.previewImage.save.success"),
			    "uni.previewImage.save.fail"=> lang("uni.previewImage.save.fail"),
			    "uni.setClipboardData.success"=> lang("uni.setClipboardData.success"),
			    "uni.scanCode.title"=> lang("uni.scanCode.title"),
			    "uni.scanCode.album"=> lang("uni.scanCode.album"),
			    "uni.scanCode.fail"=> lang("uni.scanCode.fail"),
			    "uni.scanCode.flash.on"=> lang("uni.scanCode.flash.on"),
			    "uni.scanCode.flash.off"=> lang("uni.scanCode.flash.off"),
			    "uni.startSoterAuthentication.authContent"=> lang("uni.startSoterAuthentication.authContent"),
			    "uni.picker.done"=> lang("uni.picker.done"),
			    "uni.picker.cancel"=> lang("uni.picker.cancel"),
			    "uni.video.danmu"=> lang("uni.video.danmu"),
			    "uni.video.volume"=> lang("uni.video.volume"),
			    "uni.button.feedback.title"=> lang("uni.button.feedback.title"),
			    "uni.button.feedback.send"=> lang("uni.button.feedback.send"),
            ],
            'scroll'=>[
            'down'=>[
            'textInOffset'=> '下拉刷新', // 下拉的距离在offset范围内的提示文本
		    'textOutOffset'=> '释放更新', // 下拉的距离大于offset范围的提示文本
		    'textLoading'=> '加载中 ...', // 加载中的提示文本
		    'textSuccess'=> '加载成功', // 加载成功的文本
		    'textErr'=> '加载失败', // 加载失败的文本
		    ],'up'=>[
		    'textLoading'=> '加载中 ...', // 加载中的提示文本
		    'textNoMore'=> '-- END --', // 没有更多数据的提示
    		],
		    'empty'=>
		   	 ['tip'=> '~ 空空如也 ~' ]
            
            ],
            'version'=>2,
            'currentLang'=>$this->currentLang
        ];
        ajaxReturn(1, '成功', $menu);
    }
    public function changjianwenti(){
    	$changjianwenti = getConfig('changjianwenti');
        ajaxReturn(1, '成功', ['val'=>html_entity_decode($changjianwenti)]);
    }
    public function kefuurl(){
    	$kefuurl = getConfig('kefuurl',0);
        ajaxReturn(1, '成功', ['val'=>$kefuurl]);
    }
    public function getnotice(){
    	$noticelist = Db::name('notice')
    	->alias('notice')->join('notice_lang notice_lang','notice_lang.notice_id = notice.id and lang="'.$this->currentLang.'"','left')
    	->field('notice_id,content')
    	->where(['status'=>1])->order('order_num desc')->select();
        ajaxReturn(1, '成功', $noticelist);
    }
    public function maindata(){
    	$noticelist = Db::name('notice')
    	->alias('notice')->join('notice_lang notice_lang','notice_lang.notice_id = notice.id and lang="'.$this->currentLang.'"','left')
    	->field('notice_id,content,content_text')
    	->where(['status'=>1])->order('order_num desc')->select();
    	foreach($noticelist as $k=> $val){
    		$noticelist[$k]['content'] = html_entity_decode($val['content']);
    	}
    	$zhuanqianlist = [];
    	for($j=1;$j<=20;$j++){
	    	//祝贺谁获得多少钱
		    $returnStr='';
		    $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
		    for($i = 0; $i < 3; $i ++) {
		        $returnStr .= $pattern [mt_rand ( 0, 61 )]; //生成php随机数
		    }
		    $vipJi = mt_rand(1,8);
		    $money = mt_rand(1,99999999);
		    $money = bcdiv($money,1000000,3);
		    $zhuanqianlist[] = ['title'=>'VIP'.$vipJi,'info'=>$returnStr.'****','money'=>'+$'.$money];
    	}
    	
    	$leijilirun = 123123.23+(time()-strtotime('2023-01-11 00:00:00'))*26;
    	$huiyuan_count = +(time()-strtotime('2023-01-11 00:00:00'))*2;;
    	$baipishu_url = 'https://docs.google.com/gview?embedded=true&url=https://tron.network/static/doc/white_paper_v_2_0.pdf';
    	$lunbotu = Db::name('lunbo')->where([])->order('order_num asc')->select();
    	$lunbotupic = [];
    	foreach($lunbotu as $k=> $val){
    		$lunbotupic[] = getGoopic(($val['pic']));
    	}
		$downdata = getConfig('downdata',0);
    	$downdata = json_decode($downdata,true);
        ajaxReturn(1, '成功', ['appurl'=>$downdata['erweima_url'],'kefuurl'=>getConfig('kefuurl',0),'lunbotu'=>$lunbotupic,'noticelist'=>$noticelist,'leijilirun'=>$leijilirun,'huiyuan_count'=>$huiyuan_count,'baipishu_url'=>$baipishu_url,'zhuanqianlist'=>$zhuanqianlist]);
    }
    function downdata(){
		$downdata = getConfig('downdata',0);
		$downdata = json_decode($downdata,true);
    	$ios_url = $downdata['ios_url'];
    	$android_url = $downdata['android_url'];
    	$erweima_url = $downdata['erweima_url'];
    	$android_down = 'Android Download';
    	$ios_down = 'IOS Download';
        ajaxReturn(1, '成功', ['logo'=> 'https://'.$_SERVER["SERVER_NAME"] . getConfig('logourl',0),'APP_Download'=>lang('APP_Download'),'ios_url'=>$ios_url,'android_url'=>$android_url,'android_down'=>'Android Download'
        ,'ios_down'=>lang('down_ios'),'erweima_url'=>$erweima_url]);
    }
    function changeLang(){
    	$postdata = request() -> post();
  		$langlist = json_decode(getConfig('langlist',0),true);
  		$langCodelist = array_column($langlist,'code');
        $lang = isset($postdata["lang"]) ? $postdata["lang"] : '';
        if(!in_array($lang,$langCodelist)){
     	   feifaReturn(1);
        }
        $token = Request::instance()->header('token');
    	if(!empty($token)){
    		Db::name('user')-> where(['token'=>$token])->update(['lang'=>$lang]);
    	}
        ajaxReturn(1, '成功');
    }
}