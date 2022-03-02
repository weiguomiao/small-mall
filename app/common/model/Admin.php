<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/7
 * Time: 17:59
 */

namespace app\common\model;


use app\common\validate\AdminValidate;
use think\Model;
use think\model\concern\SoftDelete;
use tools\jwt\Token;

class Admin extends Model
{
    protected $pk="admin_id";

    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //定义 管理员-档案的关联 一个管理员有一个档案
    public function profile(){
        //hasOne('关联模型类名', '外键', '主键') bind 绑定属性到父模型（数组）
        //return $this->hasOne('Profile','uid','admin_id')->bind(['idnum']);
        return $this->hasOne('Profile','uid','admin_id');
    }

    //登录
    public function login($param){
        $validate=new AdminValidate();

        if(!$validate->scene('login')->check($param)){
            return '用户名或者密码错误';
        }
        $where = [
            'username' => $param['username'],
            'password' =>$param['password']
        ];
        //查询管理员表数据
        $info = $this->where($where)->find();
        if(!$info){
            //用户名或者密码错误
            return '用户名或者密码错误';
        }
        //生成token令牌
        $token=Token::getToken($info['admin_id']);
        $data=[
            'token'=>$token,
            'admin_id'=>$info['admin_id'],
            'admin_phone'=>$info['admin_phone']
        ];
        //登录成功
        return $data;
    }
}