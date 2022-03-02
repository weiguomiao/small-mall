<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\common\model\Admin;
use think\Request;
use think\Response;

class adminController extends BaseapiController
{
    /**
     * 显示资源列表
     *
     * @return Response
     */
    public function index()
    {
        //接收参数   keyword   page
        $params=input();
        //搜索条件
        $where=[];
        if(!empty($params['keyword'])){
            $keyword=$params['keyword'];
            $where[]=['username','like',"%".$keyword."%"];
        }
        //分页查询(搜索)
        //SELECT t1.*,t2.role_name FROM gb_admin t1 left
        // join gb_role t2 on t1.role_id=t2.id  WHERE username LIKE '%admin%' LIMIT 0,2;
        //$list=Admin::where($where)->paginate(3);
        $list=Admin::alias('t1')   //alias给表取别名
            ->join('gb_role t2','t1.role_id=t2.id','left')  //表连接，取别名时：空格加别名
            ->field('t1.*,t2.role_name')  //指定需要查询的数据
            ->where($where)
            ->paginate(3);
        //返回数据
        return self::success($list);
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save(Request $request)
    {
        //接收参数
        $params=input();
        //参数检测
        $validate=$this->validate($params,[
           'username|用户名'=>'require|unique:admin',
            'password|密码'=>'length:3,20',
            'admin_phone|电话号码'=>'require',
            'role_id|所属角色'=>'require|integer|gt:0'
        ]);
        if($validate!==true){
            return self::error($validate,401);
        }
        //添加数据
        if(empty($params['password'])){
            $params['password']='123';
        }
        $params['password']=md5($params['password']);
        $info=Admin::create($params);
        //返回查询一条完整的数据
        $admin=Admin::find($info['admin_id']);
        //返回数据
        return self::success($admin);
    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update(Request $request, $id)
    {
        //接收参数
        $params=input();
        if($id==1){
            return self::error('超级管理员不能修改');
        }
        if(!empty($params['type'])&&$params['type']=='reset_pwd'){
            $password=md5('123');
            Admin::update(['password'=>$password],['id'=>$id]);
        }else{
            //参数检测
            $validate=$this->validate($params,[
                'role_id|所属角色'=>'require'
            ]);
            if($validate!==true){
                return self::error($validate);
            }
            //修改参数
            unset($params['username']);
            unset($params['password']);
            Admin::update($params,['id'=>$id]);
        }
        $info=Admin::find($id);
        //返回数据
        return self::success($info);
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return Response
     */
    public function delete($id)
    {
        //删除数据（不能删除超级管理员,不能删除自己）
         if($id==1){
             return self::error('不能删除超级管理员');
         }
         if($id==input('id')){
             return self::error('不能删除自己');
         }
         Admin::destroy($id);
        //返回数据
        return self::success('');
    }
}
