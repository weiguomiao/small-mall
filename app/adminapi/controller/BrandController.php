<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\common\model\Brand;
use app\common\model\Goods;
use think\Request;

class BrandController extends BaseapiController
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        //接收参数 cate_id   keyword  page
        $params=input();
        $where=[];
        if(isset($params['cate_id'])&&!empty($params['cate_id'])){
            //分类下的品牌列表
            $where['cate_id']=$params['cate_id'];
            //查询数据
            //SELECT t1.*, t2.cate_name FROM `pyg_brand` t1 left
            // join pyg_category t2 on t1.cate_id = t2.id where cate_id = 72;
            $list = Brand::where($where)->field('id,name')->select();
           /* $list =Brand::alias('t1')
                ->join(config('database.prefix').'category t2', 't1.cate_id=t2.id', 'left')
                ->field('t1.*, t2.cate_name')
                ->where($where)
                ->select();*/
        }else{
            //分页+搜索
            if (isset($params['keyword'])&&!empty($params['keyword'])){
                $keyword=$params['keyword'];
                $where[]=['name','like','%'.$keyword.'%'];
            }
            //分页查询
            //$list=Brand::where($where)->paginate(10);
            //SELECT t1.*, t2.cate_name FROM `pyg_brand` t1 left
            // join pyg_category t2 on t1.cate_id = t2.id where name like '%亚%' limit 0,10;
            $list = Brand::alias('t1')
                ->join('gb_category t2', 't1.cate_id=t2.id', 'left')
                ->field('t1.*, t2.cate_name')
                ->where($where)
                ->paginate(10);
        }
        //返回数据
        return self::success($list);

    }


    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\Response
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
            'name' => 'require',
            'cate_id' => 'require|integer|gt:0',
            'is_hot' => 'require|in:0,1',
            'sort' => 'require|between:0,9999'
        ]);
        if($validate !== true){
            return self::error($validate);
        }
        //生成缩略图  /uploads/brand/20190716/1232.jpg
            //is_file — 判断给定文件名是否为一个正常的文件
        if(isset($params['logo']) && !empty($params['logo']) && is_file('.' . $params['logo'])){
            \think\Image::open('.' . $params['logo'])->thumb(200,100)->save('.' . $params['logo']);
        }
        //添加数据
        $brand = Brand::create($params);
        $info = Brand::find($brand['id']);
        //返回数据
        return self::success($info);
    }

    /**
     * 显示指定的资源
     *
     * @param  int $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function read($id)
    {
        //查询一条数据
        $info = Brand::find($id);
        //如果查询分类名称
        /*$info = \app\common\model\Brand::alias('t1')
            ->join('pyg_category t2', 't1.cate_id=t2.id', 'left')
            ->field('t1.*, t2.cate_name')
            ->where('t1.id', $id)
            ->find();*/
        //返回数据
        return self::success($info);
    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\Response
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
            'name' => 'require',
            'cate_id' => 'require|integer|gt:0',
            'is_hot' => 'require|in:0,1',
            'sort' => 'require|between:0,9999'
        ]);
        if($validate !== true){
            return self::error($validate);
        }
        //修改数据(logo 图片 缩略图)
        if(isset($params['logo']) && !empty($params['logo']) && is_file('.' . $params['logo'])){
            //生成缩略图
            //$params['logo']
            \think\Image::open('.' . $params['logo'])->thumb(200, 100)->save('.' . $params['logo']);
        }
        Brand::update($params, ['id' => $id]);
        $info = Brand::find($id);
        //返回数据
        return self::success($info);
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //判断品牌下是否有商品
        $total=Goods::where('brand_id',$id)->count();
        if($total>0){
            return self::error('品牌下有商品，不能删除');
        }
        //删除商品
        Brand::destroy($id);
        //返回数据
        return self::success('');
    }
}
