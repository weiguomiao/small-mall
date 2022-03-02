<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/8
 * Time: 9:20
 */

namespace app\common\validate;


use think\Validate;

class AdminValidate extends Validate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule=[
        'username|用户名' => 'require',
        'nickname|昵称'=>'require',
        'password|密码' => 'require',
        'code|验证码' => 'require|captcha',
    ];


    /**
     * 验证场景
     * @return AdminValidate
     */
    public function sceneLogin(){
        return $this->only(['username','password']);
    }

}