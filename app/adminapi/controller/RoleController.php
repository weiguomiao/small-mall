<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\common\model\Auth;
use app\common\model\Role;
use think\Request;

class RoleController extends BaseapiController
{
    /**
     * 显示资源列表
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        //查询数据（不需要查询超级管理员）
        $list=Role::where('id','>',1)->select();
        //对每条角色数据，查询对应的权限，增加role_auth_ids下标的数据（父子级树状结构）
        foreach ($list as $k=>$v){
            $auths=Auth::where('id','in',$v['role_auth_ids'])->select()->toArray();
            //转化为父子级树状结构
            $auths=get_tree_list($auths);
            //$v['role_auths']=$auths;//$v前面需要加&引用才能写到list中去
            $list[$k]['role_auths']=$auths;
        }
        unset($v);//$v前面有&时，需要unset
        //返回数据
        return self::success($list);
    }


    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save(Request $request)
    {
        //接收数据
        $params=input();
        //参数检测
        $validate=$this->validate($params,[
            'role_name'=>'require',
            'auth_ids'=>'require'
        ]);
        if($validate!==true){
           return self::error($validate);
        }
        //添加数据 改名与数据库对应
        $params['role_auth_ids']=$params['auth_ids'];
        $role=Role::create($params);
        $info=Role::find($role['id']);
        //返回数据
        return self::success($info);
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function read($id)
    {
        //查询数据
        $info=Role::field('id,role_name,desc,role_auth_ids')->find($id);
        //返回数据
        return self::success($info);
    }


}
