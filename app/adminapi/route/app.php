<?php
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

//后台接口域名路由

//adminapi模块的首页路由
Route::get('/','adminapi/index/index');
//获取验证码
Route::get('captcha/:id', "\\think\\captcha\\CaptchaController@index");//访问图片需要
Route::get('captcha','adminapi/login/captcha');
//登录接口
Route::post('login', 'adminapi/login/login');
//退出接口
Route::get('logout','adminapi/login/logout');
//权限接口
Route::resource('auths','adminapi/auth');
//查询菜单权限的接口
Route::get('nav','adminapi/auth/nav');
//角色接口
Route::resource('roles','adminapi/role');
//管理员接口
Route::resource('admins','adminapi/admin');
//商品分类接口
Route::resource('categorys','adminapi/category');
//单图片上传
Route::post('logo','adminapi/upload/logo');
//多图片上传
Route::post('images','adminapi/upload/images');
//商品品牌接口
Route::resource('brands','adminapi/brand');
//商品类型接口
Route::resource('types','adminapi/type');
//商品接口
Route::resource('goods','adminapi/goods');




