<?php
namespace app\api\controller;
use think\ Cache;
use think\ Db;
use think\ Request;
use think\ Controller;
use think\ Lang;
use PHPMailer\PHPMailer; 
use PHPMailer\Exception; 
use lib\Test1;
class Mail extends BaseNologin {
	   function sendEmail(){
		$mail = new PHPMailer(true);
		try { 
		    //服务器配置 
		    $mail->CharSet ="UTF-8";                     //设定邮件编码 
		    $mail->SMTPDebug = 0;                        // 调试模式输出 
		    $mail->isSMTP();                             // 使用SMTP 
		    $mail->Host = 'smtp.gmail.com';                // SMTP服务器 
		    $mail->SMTPAuth = true;                      // 允许 SMTP 认证 
		    $mail->Username = 'leecxlb808@gmail.com';                // SMTP 用户名  即邮箱的用户名 
		    $mail->Password = 'dguxjwrldvnxjcvz';             // SMTP 密码  部分邮箱是授权码(例如163邮箱) 
		    $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议 
		    $mail->Port = 465;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持 
		
		    $mail->setFrom('leecxlb808@gmail.com', 'PHPMailer');  //发件人 
		    $mail->addAddress('403986780@qq.com', 'TANKING');  // 收件人 
		
		    //Content 
		      //Content
		    $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
		    $mail->Subject = '这里是邮件标题' . time();
		    $mail->Body    = '<h1>这里是邮件内容</h1>' . date('Y-m-d H:i:s');
		    $mail->AltBody = '如果邮件客户端不支持HTML则显示此内容';
		
		    $mail->send();
		    echo '邮件发送成功'; 
		} catch (Exception $e) { 
		    echo '邮件发送失败: ', $mail->ErrorInfo; 
		}
//  	$postdata = request() -> post();
//      $email = isset($postdata["email"]) ? $postdata["email"] : '';
//      $reg = '/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/';
//		if(!preg_match($reg,$email)){
//	     	 ajaxReturn(0, 'ajax_邮箱格式');
//	    }
//	     //生成随机码
//	 	$pool='0123456789';//定义一个验证码池，验证码由其中几个字符组成
//		$word_length=6;//验证码长度
//	  	$verCode = '';//验证码
//	    for ($i = 0, $mt_rand_max = strlen($pool) - 1; $i < $word_length; $i++)
//	    {
//	        $verCode .= $pool[mt_rand(0, $mt_rand_max)];
//	    }
//		Db::name('email_vercode') -> insert(['email'=>$email,'vercode'=>$verCode,'add_time'=>date('Y-m-d H:i:s')]);
// 	    ajaxReturn(1, '成功');
    }
}