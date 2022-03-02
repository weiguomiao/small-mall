<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/7
 * Time: 10:37
 */
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::rule('/','index/index/index');
Route::rule('login','index/login/login');
Route::rule('register','index/login/register');
Route::rule('phone','index/login/phone');
Route::rule('dologin','index/login/dologin');
Route::rule('sendcode','index/login/sendcode');
Route::rule('logout','index/login/logout');
Route::rule('category','index/baseIndex/category');
Route::rule('goods/index','index/goods/index');
Route::rule('goods/detail','index/goods/detail');
