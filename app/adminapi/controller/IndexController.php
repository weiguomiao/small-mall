<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\common\model\Admin;
use app\common\model\Brand;

class IndexController extends BaseapiController
{
    public function index(){
        /**
         * 测试 关联模型
         */
        /*$info = Admin::with('profile')->find(1);
        $this->ok($info);
        $data = Admin::with('profile')->select();
        $this->ok($data);
        die;*/

        //以档案表为主，一个管理员有一个档案，管理员模型中定义关联关系：
        /*$info = \app\common\model\Profile::with('admin')->find(1);
        $this->ok($info);
        $data = \app\common\model\Profile::with('admin')->select();
        $this->ok($data);
        die;*/

        //以分类表为主，一个分类下有多个品牌，分类模型中定义关联关系：
        /*$info = \app\common\model\Category::with('brands')->find(72);
        $this->ok($info);
        $data = \app\common\model\Category::with('brands')->select();
        $this->ok($data);*/

        //以品牌为主 查询一条数据
        /*$info=Brand::with('category')->find(1);
        $this->ok($info);
        $info=Brand::with('category')->select();
        $this->ok($info);*/


        /**
         * 测试 关联模型
         */
//        $data=Db::table('gb_admin')->find(1);
//        dump($data);die;


        /**
         * 测试Token工具类
         */
        //生成token
//        $token=Token::getToken(10);
//        dump($token);

        //解析token得到用户id
//        $user_id=Token::getUserId($token);
//        dump($user_id);die;

        /**
         * 测试响应方法
         */
        return self::success(200);

        //$this->ok(['id'=>100,'name'=>'zhangsan']);
        //$this->fail('参数错误');
        // return 'hhhhhhh';


    }
}
