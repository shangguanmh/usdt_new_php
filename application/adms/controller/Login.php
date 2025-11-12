<?php
namespace app\adms\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\captcha\Captcha;
use GoogleAuthenticator;
class login extends Controller
{	
    protected function _initialize()
    {
        parent::_initialize();
        $this->request = Request::instance();
        $this->params = $this->request->param(true);
    }

    public function loginVertfy()
    {
        $postdata = $this->request->post();
        $user_name = isset($postdata["username"]) ? $postdata["username"] : '';
        $password = isset($postdata["passwd"]) ? $postdata["passwd"] : '';
        $verify_code = isset($postdata["captcha_code"]) ? $postdata["captcha_code"] : '';

        if (empty($user_name)) ajaxReturn(0, '用户名不能为空');
        if (empty($password)) ajaxReturn(0, '密码不能为空');
        if (empty($verify_code)) ajaxReturn(0, '验证码不能为空');

        // 验证码校验
        $captcha = new Captcha();
        if (!$captcha->check($verify_code)) {
            ajaxReturn(0, '验证码错误');
        }

        $request = Request::instance();
        $denglu = false;

        $userInfo = Db::name('gluser')->where([
            'user_name' => $user_name,
            'status' => 1,
        ])->find();

        if (isset($userInfo['wrong']) && $userInfo['wrong'] >= 5) {
            ajaxReturn(0, '密码次数达到5次，不能登录');
        }

        if (!empty($userInfo)) {
            if ($userInfo['login_type'] == 1 && $userInfo['password'] == md5($password . $userInfo['saft'])) {
                $denglu = true;
            }
            if ($userInfo['login_type'] == 2) {
                $ga = new GoogleAuthenticator();
                if ($ga->verifyCode($userInfo['ga_code'], $password, 1)) {
                    $denglu = true;
                }
            }
        }

        $param = json_encode($_POST, JSON_UNESCAPED_UNICODE);
        if (!$denglu) {
            Db::name('gluser')->where(['user_name' => $user_name])->setInc('wrong', 1);
            Db::name('htaction_log')->insert([
                'user_id' => '',
                'ca' => 'login/loginVertfy',
                'param' => $param,
                'content' => '登陆失败',
                'add_time' => date('Y-m-d H:i:s'),
                'ip' => get_client_ip()
            ]);
            ajaxReturn(0, '用户名或密码错误');
        }

        Db::name('htaction_log')->insert([
            'user_id' => '',
            'ca' => 'login/loginVertfy',
            'param' => $param,
            'content' => '登陆成功',
            'add_time' => date('Y-m-d H:i:s'),
            'ip' => get_client_ip()
        ]);

        Db::name('gluser')->where(['id' => $userInfo['id']])->update(['session_id' => session_id(), 'wrong' => 0]);
        session('sk_name', $userInfo['user_name']);
        session('sk_id', $userInfo['id']);
        ajaxReturn(1, '登陆成功', []);
    }

    public function login()
    {
        return $this->fetch();
    }

    public function logout()
    {
        session('sk_name', null);
        $this->redirect('Login/login');
    }

    public function relogin()
    {
        session('sk_name', null);
        $denglu = url('Login/login');
        echo "<script>alert('你的帐号在别处登录，请重新登录');location.href='$denglu'; </script>";
    }

    /**
     * 获取验证码
     */
    public function verifyCode()
    {
        $captcha = new Captcha();
        $captcha->codeSet = '0123456789';
        $captcha->length = 4;
        $captcha->fontttf = '4.ttf'; // 请将 4.ttf 字体放入 public/static/font/ 目录
        $captcha->useCurve = false;
        $captcha->useNoise = true;
        return $captcha->entry();
    }
}
