<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\common\model\Category;
use think\Request;
use think\Response;

class CategoryController extends BaseapiController
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
        //接收pid参数  影响查询的数据
        //接收type参数  影响返回的数据
        //$pid = input('pid', '');
        //if($pid === ''){}
        $params = input();
        $where = [];
        //isset检测变量是否已设置并且非 NULL
        if(isset($params['pid'])){
            $where['pid'] = $params['pid'];
        }

        //查询数据
        $list = Category::where($where)->select();
        //转化为标准二维数组结构
        $list = (new \think\Collection($list))->toArray();
        /*if(isset($params['type']) && $params['type'] == 'list'){

        }else{
            $list = get_cate_list($list);
        }*/
        if(!isset($params['type']) || $params['type'] != 'list'){
            //转化为无限级分类列表
            $list = get_cate_list($list);
        }
        //返回数据
        return self::success($list);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return void
     */
    public function create()
    {
        //
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
        $validate = $this->validate($params, [
            'cate_name' => 'require|length:2,20',
            'pid' => 'require|integer|egt:0',
            'is_show' => 'require|in:0,1',
            'is_hot' => 'require|in:0,1',
            'sort' => 'require|between:0,9999',
        ]);
        if($validate!==true){
            return self::error($validate);
        }
        //添加数据（处理pid_path level）
        if($params['pid']==0){
            //顶级分类
            $params['pid_path']=0;
            $params['pid_path_name']='';
            $params['level']=0;
        }else{
            //不是顶级分类
            $p_info=Category::where('id',$params['pid'])->find();
            if(empty($p_info)){
                //没有查到
                return self::error('数据异常，请稍后再试');
            }
            $params['pid_path']=$p_info['pid_path'].'_'.$p_info['id'];
            $params['pid_path_name']=$p_info['pid_path_name'].'_'.$p_info['cate_name'];
            $params['level']=$p_info['level']+1;
        }
        //logo图片处理
        $params['image_url'] = isset($params['logo']) ? $params['logo'] : '';
        //生成缩略图  /uploads/brand/20190716/1232.jpg
        //is_file — 判断给定文件名是否为一个正常的文件
        if(isset($params['image_url']) && !empty($params['image_url']) && is_file('.' . $params['image_url'])){
            \think\Image::open('.' . $params['image_url'])->thumb(200,100)->save('.' . $params['image_url']);
        }
        $cate=Category::create($params);
        //返回数据
        $info=Category::find($cate['id']);
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
        $info=Category::find($id);
        //返回数据
        return self::success($info);
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
        //参数检测
        $validate = $this->validate($params, [
            'cate_name' => 'require|length:2,20',
            'pid' => 'require|integer|egt:0',
            'is_show' => 'require|in:0,1',
            'is_hot' => 'require|in:0,1',
            'sort' => 'require|between:0,9999',
        ]);
        if($validate!==true){
            return self::error($validate);
        }
        //修改数据（处理pid_path level）
        if($params['pid']==0){
            //顶级分类
            $params['pid_path']=0;
            $params['pid_path_name']='';
            $params['level']=0;
        }else{
            //不是顶级分类
            $p_info=Category::where('id',$params['pid'])->find();
            if(empty($p_info)){
                //没有查到
                return self::error('数据异常，请稍后再试');
            }
            $params['pid_path']=$p_info['pid_path'].'_'.$p_info['id'];
            $params['pid_path_name']=$p_info['pid_path_name'].'_'.$p_info['cate_name'];
            $params['level']=$p_info['level']+1;
        }
        if(isset($params['logo']) && !empty($params['logo'])){
            $params['image_url'] = $params['logo'];
        }
        Category::update($params, ['id' => $id]);
        //返回数据
        $info = Category::find($id);
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
        //删除数据
        //判断分类下是否有子分类
        $total=Category::where('pid',$id)->count();
        if($total>0){
            return self::error('有子分类，无法删除');
        }
        Category::destroy($id);
        //返回数据
        return self::success('');
    }
}
