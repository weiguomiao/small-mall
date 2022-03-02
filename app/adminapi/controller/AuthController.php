<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\common\model\Admin;
use app\common\model\Auth;
use app\common\model\Role;
use think\Request;
use think\Response;

class AuthController extends BaseapiController
{
    /**
     * 显示资源列表
     *
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        //接收参数keyword type
        $params=input();
        $where=[];
        if(!empty($params['keyword'])){
            $keyword=$params['keyword'];
            $where[]=['auth_name','like',"%".$keyword."%"];
        }
        //查询数据 field是指定查询那些数据
        $list=Auth::field('id,auth_name,pid,pid_path,auth_c,auth_a,is_nav,level')->where($where)->select();
        $list=(new \think\Collection($list))->toArray();
        //
        if(!empty($params['type'])&&$params['type']=='tree'){
            //父子级树状列表
            $list=get_tree_list($list);
        }else{
            //无限级分类列表
            $list=get_cate_list($list);
        }
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
        //接收数据
        $params=input();
        //dump($params);die;
        //参数检测
        $validate=$this->validate($params,[
            'auth_name|权限名称'=>'require',
            'pid|上级权限'=>'require',
            'is_nav|菜单权限'=>'require'
//            'auth_c|控制器名称'=>'require',
//            'auth_a|方法名称'=>'require',
        ]);
        if($validate!==true){
           return self::error($validate,401);
        }

        //添加数据(是否顶级，级别和pid_path处理)
        if($params['pid']==0){
            $params['level']=0;
            $params['pid_path']=0;
            $params['auth_c']='';
            $params['auth_a']='';
        }else{
            //不是顶级
            //查询上级信息
            $p_info=Auth::find($params['pid']);
            if(empty($p_info)){
               return self::error('数据异常');
            }
            //设置级别+1，家族图谱拼接
            $params['level']=$p_info['level']+1;
            $params['pid_path']=$p_info['pid_path'].'_'.$p_info['id'];
        }
        //restful 严格风格

        $auth=Auth::create($params);
        //dump($auth);die;
        $info=Auth::find($auth['id']);
        //返回数据
        return self::success($info);
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function read($id)
    {
        //查询数据
        $auth=Auth::field('id,auth_name,pid,pid_path,auth_c,auth_a,is_nav,level')->find($id);
        //返回数据
        return self::success($auth);
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
        //接收数据
        $params=input();
        //参数检测
        $validate=$this->validate($params,[
            'auth_name|权限名称'=>'require',
            'pid|上级权限'=>'require',
            'is_nav|菜单权限'=>'require'
//            'auth_c|控制器名称'=>'require',
//            'auth_a|方法名称'=>'require',
        ]);
        if($validate!==true){
           return self::error($validate,401);
        }
        //修改数据
        $auth=Auth::find($id);
        if(empty($auth)){
           return self::error('数据异常');
        }
        if($params['pid']==0){
            //(是顶级，级别和pid_path处理)
            $params['level']=0;
            $params['pid_path']=0;
        }else if($params['pid']!=$auth['pid']){
            $p_auth=Auth::find($params['pid']);
            if(empty($p_auth)){
               return self::error('数据异常');
            }
            $params['level']=$p_auth['level']+1;
            $params['pid_path']=$p_auth['pid_auth'].'_'.$p_auth['id'];
        }
        Auth::update($params,['id'=>$id],true);
        //返回数据
        $info=Auth::find($id);
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
        //判断是否有子权限
        $total=Auth::where('pid',$id)->count();
        if($total>0){
           return self::error('有子权限，无法删除');
        }
        //删除数据
        Auth::destroy($id);
        //还回数据
        return self::success('');
    }

    /**菜单权限接口
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function nav(){
        //获取用户登录的管理员用户id
        $admin_id=$this->request->admin_id;
        //$admin_id=1;
        //查询管理员的角色id
        $info=Admin::find($admin_id);
        $role_id=$info['role_id'];
        //判断是否超级管理员
        if($role_id==1){
            //超级管理员  直接查询权限表 菜单权限 is_nav=1
            $data=Auth::where('is_nav',1)->select()->toArray();
        }else{
            //先查询角色表 role_auth_ids
            $role=Role::find($role_id);
            $role_auth_ids=$role['role_auth_ids'];

            //再查询权限表
            $data=Auth::where('is_nav',1)->where('id','in',$role_auth_ids)->select()->toArray();
        }
        //再转化为 父子级树状结构 ， 有层次感
        $data=get_tree_list($data);
        //返回数据
        return self::success($data);
    }
}
