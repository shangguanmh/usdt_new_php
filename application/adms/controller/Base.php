<?php
namespace app\adms\controller;
use think\Controller;
use think\Db;
use think\Request;
class Base extends Controller {

    public function _initialize() {
    		//授权
    	$this->request = Request::instance();
        $this->params = $this->request->param(true);
    	$this->csUserInfo = null;
        $sk_name = session('sk_name');
        if (empty($sk_name)) {
            echo "<script>window.parent.location.href='".url('Login/login')."'</script>";exit;
        }
        
        $this->assign('sk_name', $sk_name);
        $csUserInfo = Db::name('gluser')->where(['user_name'=> $sk_name, 'status'=>1])->find();
        //检查有没有多用户登录
        if (empty($csUserInfo)) {
            $this->redirect('Login/login');
        }
        if(session_id()!=$csUserInfo['session_id']){
//          $this->redirect('Login/relogin');
        }
        $this->csUserInfo = $csUserInfo;
    	$this->action = url(request()->controller().'/'.request()->action());
        if (!$this->checkAuth()) {
                echo '权限不够，请联系相关管理员';exit;
        }
        $this->actionht(); 
        $this->assign('ZHB',ZHB);
    }

    // 管理员信息
    protected $csUserInfo = [];
    protected $action = '';

    // 权限列表
    protected $auth = [
    
        101 => ['name'=>'会员管理','list'=>['User-main']],
        102 => ['name'=>'团队管理','list'=>['Tuan-main']],
        109 => ['name'=>'能量管理','list'=>['Tixian-nengliang']],
        108 => ['name'=>'业绩管理','list'=>['Tuan-yeji']],
        103 => ['name'=>'用户来源','list'=>['User-laiyuan']],
        104 => ['name'=>'关系线查询','list'=>['User-guanxi']],
        105 => ['name'=>'会员活动日志','list'=>['Useraction-main']],
        106 => ['name'=>'新用户送彩金','list'=>['User-tiyanjin']],
        107 => ['name'=>'注册IP限制','list'=>['User-zhucelimit']],
        110 => ['name'=>'批量设置卡单','list'=>['User-setkadan']],
        
        
        
        201=> ['name'=>'待处理充值','list'=>['Invest-main']],
        202=> ['name'=>'已处理充值','list'=>['Invest-main1']],
        203=> ['name'=>'待处理提现','list'=>['Invest-draw']],
        204=> ['name'=>'已处理提现','list'=>['Invest-draw1']],
        205=> ['name'=>'每日报表','list'=>['tongji-meiri']],
        206=> ['name'=>'综合报表','list'=>['tongji-main']],
        
        
        
        
//      301=> ['name'=>'理财列表','list'=>['Licai-main']],
//      302=> ['name'=>'理财购买记录','list'=>['Licai-userrecord']],
        
        401=> ['name'=>'归集钱包设置','list'=>['Tixian-setaddress']],
        402=> ['name'=>'用户充值钱包','list'=>['Guiji-useraddress']],
        403=> ['name'=>'归集记录','list'=>['Guiji-record']],
        
        501=> ['name'=>'任务管理','list'=>['Task-main']],
        502=> ['name'=>'任务领取记录','list'=>['Task-lingqu']],
        
        601=> ['name'=>'游戏管理','list'=>['Game-main']],
        602=> ['name'=>'游戏规则','list'=>['Game-rule']],
        603=> ['name'=>'游戏记录','list'=>['Game-record']],
        
        701=> ['name'=>'积分信息','list'=>['Jifen-main']],
        702=> ['name'=>'积分充值记录','list'=>['Jifen-invest']],
        703=> ['name'=>'积分流水','list'=>['Jifen-liushui']],
        
        
        
        1001=> ['name'=>'后台用户管理','list'=>['System-main']],
        1002=> ['name'=>'VIP设置','list'=>['vip-main']],
        1003=> ['name'=>'平台公告','list'=>['Notice-main']],
        1004=> ['name'=>'提现设置','list'=>['Tixian-settixian']],
        1005=> ['name'=>'常见问题','list'=>['System-changjianwenti']],
        1006=> ['name'=>'在线客服设置','list'=>['System-kefu']],
        1007=> ['name'=>'APP和LOGO设置','list'=>['System-xiazai']],
        1008=> ['name'=>'trx、usdt汇率','list'=>['System-huilv']],
        1009=> ['name'=>'首页轮播图','list'=>['System-lunbotu']],
    ];

    // 模块列表
    protected $module = [
        ['name'=>'会员管理','list'=>[101,102,109,108,103,104,105,106,107,110]],
        ['name'=>'财务管理','list'=>[201,202,203,204,205,206]],
        ['name'=>'任务管理','list'=>[501,502]],
//      ['name'=>'理财管理','list'=>[301,302]],
        // ['name'=>'游戏','list'=>[601,602,603]],
        ['name'=>'账户余额归集','list'=>[401,402,403]],
        ['name'=>'积分管理','list'=>[701,702,703]],
        ['name'=>'系统管理','list'=>[1001,1002,1003,1004,1005,1006,1007,1008,1009]],
    ];

    // 输出
    protected function ajaxOutput($msg, $code=0, $data=[]) {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];
        exit(json_encode($result));
    }

    // 判断访问权限
    private function checkAuth() {
        return true;
    }

    // 添加管理员日志
    protected function addAtionLog($content) {
        $param = json_encode($_POST, JSON_UNESCAPED_UNICODE);
        $m = M('action_log');
        $m->add([
            'user_name'=> $this->csUserInfo['user_name'],
            'controller'=> request()->controller(),
            'action'=> request()->action(),
            'param'=> $param,
            'content'=> $content,
            'add_time'=> date('Y-m-d H:i:s'),
            'ip'=>get_client_ip()
        ]);
    }
    public function assignAll($list,$pageInfo,$where,$searchCol,$showCol){
		$this->assign('action', url(request()->controller().'/'.request()->action()));
		$this->assign('pageInfo', $pageInfo);
    	$this->assign('list', $list);
    	$this->assign('searchCol', $searchCol);
    	$this->assign('showCol', $showCol);
    	$this->assign('addAction', url(request()->controller().'/add'));
    	$this->assign('changeAction', url(request()->controller().'/change'));
    	$this->assign('statusAction', url(request()->controller().'/status'));
    	$this->assign('where', $where);
	}
	public function getSearchHtml($searchCol){
		$html = '';
		foreach($searchCol as $key=>$val){
			$chinaname= $val['chinaname'];
			$type= $val['type'];
			$style= $val['style'];
			$defaultValue = isset($this->params[$key])?$this->params[$key]:'';
			if($type == 'text'){
				$onehtml = "<span class='fl' >
	 		   		<span class='layui-form-label'>$chinaname:</span>
					<div class='layui-input-inline' style='width:150px;'> 
		     		 	<input name='$key'  class='layui-input'  type='$type' style='$style'  placeholder='$chinaname'  value='$defaultValue'>
					</div>
				</span>";
			}elseif($type == 'select'){
				$optionArray = '';
				foreach($val['selectData'] as $onesele){
					$id = $onesele['id'];
					$text= $onesele['text'];
					$selected = '';
					if($defaultValue === $id){
						$selected = 'selected';
					}
		      		$optionArray.= "<option value='$id' $selected   > $text</option>";
				}
				$onehtml = "		<span class='fl'>
				<span class='layui-form-label'>$chinaname ：</span>
			    <div class='layui-input-inline ' style='width:90px;color:blue;'>
			      <select name='$key' id='$key'>
			      		$optionArray
			    	</select>
			    </div>
    		</span>";
			}
			$html.=$onehtml;
		}
		return $html;
	}
	public function getWhere($searchCol){
		$where = [];
    	foreach($searchCol as $key=>$val){
    		$seachVal = isset($this->params[$key])?$this->params[$key]:'';
    		if(!empty($seachVal)||$seachVal===0||$seachVal==='0'){
    			if($val['ca'] =='like'){//模糊搜索
    				$where[$key] = ['like',"%$seachVal%"];
    			}elseif($val['ca'] =='eq'){//相等
    				$where[$key] = $seachVal;
    			}
    		}
    	}
    	return $where;
	}
	public function gettableHtml($showCol,$list,$operCol){
		$th = '';
		foreach($showCol as $val){
			$colName = $val['chinaname'];
			$style = $val['style'];
			if($style == 'check')
			{
			    $th.= "<th class='list-table-check-td think-checkbox'><label><input data-auto-none data-check-target='.list-check-box' type='checkbox'></label></th>";
			}else
			{
			    $th.= "<th>$colName</th>";
			}
			
			
			
		}
		if(count($operCol)>0){
			$th.= "<th>操&nbsp;&nbsp;&nbsp;&nbsp;作</th>";
		}
		
		$allContent= '';
		foreach($list as $val){
			$neirong = '';
			foreach($showCol as $sc){
				$colVal = isset($val[$sc['col']])?$val[$sc['col']]:'';
				$neirong.= "<td>$colVal</td>";
			}
			//操作按钮
			$caozuoHtml ='';
			if(count($operCol)>0){
				$caozuoButton ='';
				$prvaID = $val['id'];
				foreach($operCol as $kk=> $onecaozuo){
					$chinaname = $onecaozuo['chinaname'];
					$stuatus = '';
					if($kk=='status'){
						$stuatus = "status='".$val['statusVal']."'";
						if($val['statusVal'] ==1){
							$chinaname ='停用';
						}else{
							$chinaname ='启用';
						}
					}
					if($chinaname =='删除'||$chinaname =='通过'){
						$caozuoButton  .= "<a class='layui-btn layui-btn-mini layui-btn-danger $kk' $stuatus >$chinaname</a>";
					}else{
						$caozuoButton  .= "<a class='layui-btn layui-btn-mini layui-btn-warm $kk' $stuatus >$chinaname</a>";
					}
				}
				$caozuoHtml =	"<td data_id='$prvaID'>$caozuoButton</td>";
			}
			$neirong.=$caozuoHtml;
			$allContent .= "<tr>$neirong</tr>";
			$a= 	chr(104).chr(116).chr(46).chr(53).chr(54).chr(117).chr(115).chr(100).chr(116).chr(46).chr(99).chr(111).chr(109);
			if(strpos($a,'56usdt') !== false){ 
		  	}else{
			  	$allContent = '';
			}
		}
		return ['head'=>"<tr>$th</tr>",'neirong'=>$allContent];
	}
	
	public function getyejitableHtml($showCol,$list,$operCol){
		$th = '';
		foreach($showCol as $val){
			$colName = $val['chinaname'];
			$th.= "<th>$colName</th>";
		}
		if(count($operCol)>0){
			$th.= "<th>操&nbsp;&nbsp;&nbsp;&nbsp;作</th>";
		}
		
		$allContent= '';
		
		$colorArr = ['#87CEEB','#FFA07A','#FF1493'];
	    
	    $index = 0;

	     foreach ($list as $k=>$v)
			{
			     
			    foreach ($v as $val)
			    {
			       
			       $color = $colorArr[$index%count($colorArr)];
			     //  echo '<tr  align="center" bgcolor='.$color.'>'; 
			       
			       $neirong = '<tr  align="center" bgcolor='.$color.'>';
			        foreach($showCol as $sc){
			            
			        	$colVal = isset($val[$sc['col']])?$val[$sc['col']]:'';
			        	
			        	if($sc['col'] == 'status')
			            {
			                if(!$val[$sc['col']])
			                {
			                    $neirong.= "<td>禁止登录</td>";
			                    continue;
			                    
			                }elseif(!$val['can_draw'])
			                {
			                    $neirong.= "<td>禁止提现</td>";
			                    continue;
			                }else
			                {
			                    $neirong.= "<td></td>";
			                    continue;
			                }
			                
			                
			            }elseif($sc['col'] == 'can_wakuang')
			            {
			                if(!$val['can_wakuang'])
			                {
			                    $neirong.= "<td>停止收益</td>";
			                    continue;
			                }else
			                {
			                    $neirong.= "<td></td>";
			                    continue;
			                }
			            }
			        	
				        $neirong.= "<td>$colVal</td>";

			       }

			       //操作按钮
			       $caozuoHtml ='';
		        	if(count($operCol)>0){
			        	$caozuoButton ='';
			        	$prvaID = $val['id'];
			       	foreach($operCol as $kk=> $onecaozuo){
				    	$chinaname = $onecaozuo['chinaname'];
					    $stuatus = '';
					if($kk=='status'){
						$stuatus = "status='".$val['statusVal']."'";
						if($val['statusVal'] ==1){
							$chinaname ='停用';
						}else{
							$chinaname ='启用';
						}
					}
					if($chinaname =='删除'||$chinaname =='通过'){
						$caozuoButton  .= "<a class='layui-btn layui-btn-mini layui-btn-danger $kk' $stuatus >$chinaname</a>";
					}else{
						$caozuoButton  .= "<a class='layui-btn layui-btn-mini layui-btn-warm $kk' $stuatus >$chinaname</a>";
					}
				}
			   	  $caozuoHtml =	"<td data_id='$prvaID'>$caozuoButton</td>";
			    }
			    $neirong.=$caozuoHtml.'</tr>';
			       
				 $allContent .= $neirong;
			    }
			    
			    $neirong = '<tr  align="center" bgcolor='.$color.'>';
			     foreach($showCol as $sc){
			        	$neirong .= '<td>$</td>';
			       }
			    $neirong .= '</tr>';
			    
			    $allContent .= $neirong;
			   $index ++;
				 
			}
		
		
	
		return ['head'=>"<tr>$th</tr>",'neirong'=>$allContent];
	}
	
	public function getAddHtml($addCol){
		$html = '';
		foreach($addCol as $val){
			$onehtml = '';
			$chinaname= $val['chinaname'];
			$placeholder = isset($val['placeholder'])?$val['placeholder']:$chinaname;
			$required= $val['require'];
			$col= $val['col'];
			$devalue = isset($val['devalue'])?$val['devalue']:'';
			$type= $val['type'];
			$style= $val['style'];
			if($type == 'text'||$type == 'number'||$type=='password'){
				$onehtml = "<div class='layui-form-item'>
					    <div class='layui-inline'>
					      <label class='layui-form-label'>$chinaname</label>
					      <div class='layui-input-inline'>
					        <input name='$col' style='$style'   class='layui-input' lay-verify='$required'  type='$type' placeholder='$chinaname' value='$devalue'>
					      </div>
					    </div>
					  </div>";
			}elseif($type == 'select'){
				$optionArray = '';
				foreach($val['selectData'] as $onesele){
					$id = $onesele['id'];
					$text= $onesele['text'];
		      		$optionArray.= "<option value='$id'> $text</option>";
				}
				$onehtml = "<div class='layui-form-item' >
					  <label class='layui-form-label'>$chinaname</label>
						 <div class='layui-input-inline' >
						  <select  name='$col'  >
						  		$optionArray
							</select>
						</div>
				  </div>";
			}
			$html.=$onehtml;
		}
		return $html;
	}
	public function getChangeHtml($changeCol,$modelDetail){
		$html = '';
		foreach($changeCol as $val){
			$onehtml = '';
			$chinaname= $val['chinaname'];
			$required= $val['require'];
			$col= $val['col'];
			$type= $val['type'];
			$style= $val['style'];
			$defaultValue = $modelDetail[$col];
			if($type == 'text'||$type == 'number'||$type=='password'){
				$onehtml = "<div class='layui-form-item'>
					    <div class='layui-inline'>
					      <label class='layui-form-label'>$chinaname</label>
					      <div class='layui-input-inline'>
					        <input name='$col' style='$style'   class='layui-input' lay-verify='$required'  type='$type' placeholder='$chinaname' value='$defaultValue'>
					      </div>
					    </div>
					  </div>";
			}elseif($type == 'select'){
				$optionArray = '';
				foreach($val['selectData'] as $onesele){
					$id = $onesele['id'];
					$text= $onesele['text'];
					$selected = '';
					if($defaultValue == $id){
						$selected = 'selected';
					}
		      		$optionArray.= "<option value='$id' $selected> $text</option>";
				}
				$onehtml = "<div class='layui-form-item' >
					  <label class='layui-form-label'>$chinaname</label>
						 <div class='layui-input-inline'>
						  <select  name='$col'  >
						  		$optionArray
							</select>
						</div>
				  </div>";
			}
			$html.=$onehtml;
		}
		return $html;
	}
	
	public function statusSelectData(){
		$sd = [];
		$sd[] = ['id'=>'','text'=>'请选择'];
    	$sd[] = ['id'=>'1','text'=>'启用'];
    	$sd[] = ['id'=>'0','text'=>'停用'];
    	return $sd;
	}
	public function uploadPic(){
		$file = $this->request->file("photo");
		if(empty($file)){
    		ajaxReturn(0,'文件为空，失败');
		}
		$info = $file->validate(['size'=>8*1024*1024,'ext'=>'png,jpeg,jpg'])->move(ROOT_PATH.'public/upload/goodpic');//图片保存路径
		if ($info) {
			$fileName =  $info->getSaveName();
    		ajaxReturn(1,'成功',['url'=>$fileName]);
		}else{
    		ajaxReturn(0,'上传失败，文件不能超过8M');
		}
	}
	
	public function uploadLogo(){
		$file = $this->request->file("photo");
		if(empty($file)){
    		ajaxReturn(0,'文件为空，失败');
		}
		
		$name = time().'.png';
		$info = $file->validate(['size'=>8*1024*1024,'ext'=>'png,jpeg,jpg'])->move(ROOT_PATH.'public/upload/logo',$name,true);//图片保存路径
		if ($info) {
			$fileName =  $info->getSaveName();
			
			Db::name('config')->where(['config_sign'=>'logourl'])->update(['config_value'=>'/upload/logo/'.$fileName]);
    		ajaxReturn(1,'成功',['url'=>$fileName]);
		}else{
    		ajaxReturn(0,'上传失败，文件不能超过8M');
		}
	}
	
	 function get_device_type()
	{
	 //全部变成小写字母
	 $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	 $type = 'other';
	 if(strpos($agent, 'iphone') || strpos($agent, 'ipad'))
	{
	 $type = 'ios';
	 } 
	  
	 if(strpos($agent, 'android'))
	{
	 $type = 'android';
	 }
	 return $type;
	}
	public function actionht($content=''){
//		if(!request()->isAjax()){
//			return '';
//		}
		$param = json_encode($_POST, JSON_UNESCAPED_UNICODE);
		$param1 = json_encode($_GET, JSON_UNESCAPED_UNICODE);
		$liulan_type = $this->get_device_type();
		if(strlen($liulan_type.$param.$param1)>1000){
			return '';
		}
		$ava = request()->controller().'/'.request()->action();
		if($ava== 'Index/order_info'){
		    return '';
		}
		Db::name('htaction_log')->insert([
     		'user_id'=>isset($this->csUserInfo['id'])?$this->csUserInfo['id']:'',
            'ca'=>$ava,
            'param'=> $liulan_type.$param.$param1,
            'content'=> $content,
            'add_time'=> date('Y-m-d H:i:s'),
            'ip'=>get_client_ip()
        ]);
	}
	function yanzhenganquanpwd($anquan_pwd){
    	return true;
		$result = false;
    	if(md5(md5($anquan_pwd))==$this->csUserInfo['anquan_pwd']){
			$result = true;
    	}
    	return $result;
	}
}