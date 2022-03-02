<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/7
 * Time: 17:10
 */

namespace app\adminapi\controller;

use think\captcha\facade\Captcha;
use app\common\model\Admin;
use tools\jwt\Token;

class LoginController extends BaseapiController
{
    /**
     * 获取验证码图片地址
     */
    public function captcha()
    {
        return Captcha::create();
    }

    /**
     * 登录接口
     */
    public function login()
    {
        //获取输入变量
        $param = input();
        $param['password']=md5($param['password']);
        $result=(new Admin())->login($param);
        if($result){
            return self::success($result);
        }else{
            return self::error($result);
        }
    }

    /**
     * 退出
     */
    public function logout(){
        //获取当前请求中的token
        //清空token  将需清空的token存入缓存，再次使用时，会读取缓存进行判断
        $token = Token::getRequestToken();
        //从缓存中取出注销的token数组
        $delete_token = cache('delete_token') ?: [];
        //将当前的token加入到数组中
        $delete_token[] = $token;
        //将新的数组重新存到缓存中，缓存1天
        cache('delete_token', $delete_token, 86400);
        return self::success('');
    }
}