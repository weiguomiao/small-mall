<?php
declare (strict_types=1);

namespace app\adminapi\controller;

use app\BaseController;
use app\common\model\Admin;
use app\common\model\Auth;
use app\common\model\Role;
use think\facade\Request;

class BaseapiController extends BaseController
{
    //无需登录的请求数组
    protected $no_login = ['login/captcha', 'login/login'];

    // 初始化
    protected function initialize()
    {
        parent::initialize();

        //处理跨域Options预检请求
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
            //允许的源域名
            header("Access-Control-Allow-Origin: *");
            //允许的请求头信息
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
            //允许的请求类型
            header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
            exit;
        }

        //登录检测
        try {
            //获取当前请求的控制器方法名称
            $path = Request::controller(true) . '/' . Request::action(true);
            if (!in_array($path, $this->no_login)) {
                //$admin_id = \tools\jwt\Token::getUserId();
                //为了测试方便 ，设置admin_id=1
                $admin_id = 1;
                //登录验证
                if (empty($admin_id)) {
                    return self::error('未登录或Token无效', 403);
                }
                //权限检测
                $auth_check=$this->auth_check();
                if(!$auth_check){
                    return self::error('没有权限访问',404);
                }
                //将获取的用户id 设置到请求信息中
                $this->request->admin_id = $admin_id;
            }
        } catch (\Exception $e) {          //避免服务器异常
            return self::error('服务异常，请检查token令牌', 403);
        }
    }

    /**
     * 权限检测
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function auth_check(){
        //判断是否特殊页面
        $controller=Request::controller(); //返回首字母大写
        $action=Request::action();
        if($controller=='Index'&&$action=='index'){
            return true;
        }
        //获取管理员角色id
        //$admin_id=input('admin_id');  //只能从前端中获取参数
        $admin_id = 1;
        $info=Admin::find($admin_id);
        $role_id=$info['role_id'];
        //判断是否超级管理员
        if($role_id==1){
            return true;
        }
        //查询当前管理员所拥有的权限
        $role=Role::find($role_id);
        //取出权限ids分割为数组
        $role_auth_ids=explode(',',$role['role_auth_ids']);
        //根据当前访问的控制器、方法查询到具体的权限id
        $auth=Auth::where('auth_c',$controller)->where('auth_a',$action)->find();
        $auth_id=$auth['id'];
        //判断当前权限id是否在role_auth_ids范围中
        if(in_array($auth_id,$role_auth_ids)){
            //有权限
            return true;
        }else{
            //无权限
            return false;
        }

    }
}
