<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\admin\model\Category;
use app\admin\model\Type;
use app\common\model\GoodsImages;
use think\facade\Db;
use think\Request;
use \app\common\model\Goods;
use \app\common\model\SpecGoods;

class GoodsController extends BaseapiController
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     * @throws \think\db\exception\DbException
     */
    public function index()
    {

        //接收参数  keyword  page(自动处理)
        $params = input();
        $where = [];
        if(isset($params['keyword']) && !empty($params['keyword'])){
            $keyword = $params['keyword'];
            $where[] = ['goods_name','like', "%".$keyword."%"];
        }
        //分页+搜索
        $list = Goods::with(['category','brand','type'])
            ->where($where)
            ->order('id desc')
            ->paginate(10);
        //返回数据
        return self::success($list);
    }


    /**
     * 保存新建的资源
     *
     * @param  \think\Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        //接收参数
        $params = input();
        //参数数组参考：(部分省略)
        /*$params = [
            'goods_name' => 'iphone X',
            'goods_price' => '8900',
            'goods_introduce' => 'iphone iphonex',
            'goods_logo' => '/uploads/goods/20190101/afdngrijskfsfa.jpg',
            'goods_images' => [
                '/uploads/goods/20190101/dfsssadsadsada.jpg',
                '/uploads/goods/20190101/adsafasdadsads.jpg',
                '/uploads/goods/20190101/dsafadsadsaasd.jpg',
            ],
            'cate_id' => '72',
            'brand_id' => '3',
            'type_id' => '16',
            'item' => [
                '18_21' => [
                    'value_ids'=>'18_21',
                    'value_names'=>'颜色：黑色；内存：64G',
                    'price'=>'8900.00',
                    'cost_price'=>'5000.00',
                    'store_count'=>100
                ],
                '18_22' => [
                    'value_ids'=>'18_22',
                    'value_names'=>'颜色：黑色；内存：128G',
                    'price'=>'9000.00',
                    'cost_price'=>'5000.00',
                    'store_count'=>50
                ]
            ],
            'attr' => [
                '7' => ['id'=>'7', 'attr_name'=>'毛重', 'attr_value'=>'150g'],
                '8' => ['id'=>'8', 'attr_name'=>'产地', 'attr_value'=>'国产'],
            ]
        ];*/
        //参数检测
        $validate = $this->validate($params, [
            'goods_name|商品名称' => 'require',
            'goods_price|商品价格' => 'require|float|gt:0',
            //省略无数字段检测
            'goods_logo|商品logo' => 'require',
            'goods_images|相册图片' => 'require|array',
            'attr|商品属性值' => 'require|array',
            'item|规格商品SKU' => 'require|array'
        ], [
            'goods_price.float' => '商品价格必须是小数或者整数'
        ]);
        if($validate !== true){
            return self::error($validate);
        }
        //开启事务
        Db::startTrans();
        try{
            //添加商品基本信息
            //商品logo图片 生成缩略图
            if(is_file('.' . $params['goods_logo'])){
                //生成缩略图
                // /uploads/goods/20190701/jdsfdslafdsa.jpg
                //\think\Image::open('.' . $params['goods_logo'])->thumb(210, 240)->save('.' . $params['goods_logo']);
                //以下代码，是给缩略图重新取名字
                $goods_logo = dirname($params['goods_logo']) . '/' . 'thumb_' . basename($params['goods_logo']);
                \think\Image::open('.' . $params['goods_logo'])->thumb(210, 240)->save('.' . $goods_logo);
                $params['goods_logo'] = $goods_logo;
            }
            //商品属性  生成json字符串
            $params['goods_attr'] = json_encode($params['attr'], JSON_UNESCAPED_UNICODE);
            $goods = Goods::create($params);
            //批量添加商品相册图片
            $goods_images = [];
            foreach($params['goods_images'] as $image){
                //生成两张不同尺寸的缩略图 800*800  400*400
                if(is_file('.' . $image)){
                    //定义 两张缩略图路径
                    $pics_big = dirname($image) . '/' . 'thumb_800_' . basename($image);
                    $pics_sma = dirname($image) . '/' . 'thumb_400_' . basename($image);
                    /*\think\Image::open('.' . $image)->thumb(800, 800)->save('.' . $pics_big);
                    \think\Image::open('.' . $image)->thumb(400, 400)->save('.' . $pics_sma);*/
                    $image_obj = \think\Image::open('.' . $image); //打开图片
                    $image_obj->thumb(800, 800)->save('.' . $pics_big);//打开图片一次，先生成大图再小图
                    $image_obj->thumb(400, 400)->save('.' . $pics_sma);
                    //组装一条数据
                    $row = [
                        'goods_id' => $goods['id'],
                        'pics_big' => $pics_big,
                        'pics_sma' => $pics_sma,
                    ];
                    $goods_images[] = $row;
                }
            }
            $goods_images_model = new GoodsImages();
            $goods_images_model->saveAll($goods_images);
            //批量添加规格商品 SKU
            $spec_goods = [];
            foreach($params['item'] as $v){
                $v['goods_id'] = $goods['id'];
                $spec_goods[] = $v;
            }
            $spec_goods_model = new SpecGoods();
            $spec_goods_model->saveAll($spec_goods);
            //提交事务
            Db::commit();
            //返回数据
            $info = Goods::with('category,brand,type')->find($goods['id']);
            return self::success($info);
        }catch (\Exception $e){
            //回滚事务
            Db::rollback();
            return self::error('操作失败');
        }
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
        //查询数据 多个嵌套关联，只有最后一个生效。比如with('type, type.attrs, type.specs') 生效的是type.specs
        $info = Goods::with('category_row,brand_row,goods_images,spec_goods')->find($id);
        //按照接口要求，改属性名
        $info['category'] = $info['category_row'];
        unset($info['category_row']);
        $info['brand'] = $info['brand_row'];
        unset($info['brand_row']);
        //商品所属模型信息
        //$info['type']['id']  $info['type_id']
        $type = Type::with('specs,specs.spec_values,attrs')->find($info['type_id']);
        $info['type'] = $type;
        return self::success($info);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($id)
    {
         //查询商品基本信息（关联模型查询）
        //嵌套关联太多，只能写一个 category_row.brands  type_row.specs type_row.attrs type_row.specs.spec_values
        $goods = Goods::with('category_row,category_row.brands,brand_row,goods_images,spec_goods')->find($id);
        $goods['category'] = $goods['category_row'];
        $goods['brand'] = $goods['brand_row'];
        unset($goods['category_row']);
        unset($goods['brand_row']);
        //单独查询所属模型及规格属性等信息
        $goods['type'] =Type::with('specs,specs.spec_values,attrs')->find($goods['type_id']);

        //查询分类信息（所有一级、所属一级的二级、所属二级的三级）
        $cate_one =Category::where('pid', 0)->select();
        //从商品所属的三级分类的pid_path中，取出所属的二级id和一级id
        $pid_path = explode('_', $goods['category']['pid_path']);
        //$pid_path[1] 一级id;  $pid_path[2] 二级id
        //查询所属一级的所有二级
        $cate_two = Category::where('pid', $pid_path[1])->select();
        //查询所属二级的所有三级
        $cate_three =Category::where('pid', $pid_path[2])->select();
        //查询所有的类型信息
        $type = Type::select();
        //返回数据
        $data = [
            'goods' => $goods,
            'category' => [
                'cate_one' => $cate_one,
                'cate_two' => $cate_two,
                'cate_three' => $cate_three,
            ],
            'type' => $type
        ];
        return self::success($data);
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //接收参数
        $params = input();
        //对富文本编辑器字段  goods_desc 过滤方式防范xss攻击
        $params['goods_desc'] = input('goods_desc', '', 'remove_xss');
        //参数检测
        $validate = $this->validate($params, [
            'goods_name|商品名称' => 'require',
            'goods_price|商品价格' => 'require|float|gt:0',
            //省略无数字段检测
            'goods_images|商品相册' => 'array',
            'item|规格值' => 'require|array',
            'attr|属性值' => 'require|array'
        ]);
        if($validate !== true){
            return self::error($validate);
        }
        //开启事务
        Db::startTrans();
        try{
            //商品logo图片
            if(isset($params['goods_logo']) && is_file('.' . $params['goods_logo'])){
                //生成缩略图
                //\think\Image::open('.' . $params['goods_logo'])->thumb(210, 240)->save('.' . $params['goods_logo']);
                $goods_logo = dirname($params['goods_logo']) . '/' . 'thumb_' . basename($params['goods_logo']);
                \think\Image::open('.' . $params['goods_logo'])->thumb(210, 240)->save('.' . $goods_logo);
                $params['goods_logo'] = $goods_logo;
            }
            //商品属性值 json
            $params['goods_attr'] = json_encode($params['attr'], JSON_UNESCAPED_UNICODE);
            //修改商品数据
            Goods::update($params, ['id' => $id]);
            //相册图片 批量添加(继续上传新的相册图片)
            if(isset($params['goods_images'])){
                $goods_images = [];
                foreach($params['goods_images'] as $image){
                    if(is_file('.' . $image)){
                        //生成两种不同尺寸的缩略图  800*800  400*400
                        $pics_big = dirname($image) .' / '. 'thumb_800_' . basename($image);
                        $pics_sma = dirname($image) .' / '. 'thumb_400_' . basename($image);
                        $image_obj = \think\Image::open('.' . $image);
                        $image_obj->thumb(800,800)->save('.' . $pics_big);
                        $image_obj->thumb(400,400)->save('.' . $pics_sma);
                        //组装数据批量添加
                        $row = [
                            'goods_id' => $id,
                            'pics_big' => $pics_big,
                            'pics_sma' => $pics_sma,
                        ];
                        $goods_images[] = $row;
                    }
                }
                //批量添加
                $goods_images_model = new GoodsImages();
                $goods_images_model->saveAll($goods_images);
            }
            //删除原来的规格商品SKU
            SpecGoods::destroy(['goods_id'=>$id]);
            //添加新的规格商品SKU
            $spec_goods = [];
            foreach($params['item'] as $k=>$v){
                $v['goods_id'] = $id;
                $spec_goods[] = $v;
            }
            $spec_goods_model = new SpecGoods();
            $spec_goods_model->saveAll($spec_goods);
            //提交事务
            Db::commit();
            //返回数据
            $info = Goods::with('category,brand,type')->find($id);
            return self::success($info);
        }catch (\Exception $e){
            //回滚事务
            Db::rollback();
            return self::error('操作失败');
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delete($id)
    {
         //上架的商品必须先下架再删除
        $goods = Goods::find($id);
        if(empty($goods)){
            return self::error('数据异常，商品已经不存在');
        }
        if($goods['is_on_sale']){
            //上架中，无法删除
            return self::error('上架中，无法删除');
        }
        //删除
        $goods->delete();

        //\app\common\model\Goods::destroy($id);
        return self::success('');
    }


    /**删除相册图片
     * @param $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delpics($id)
    {
        /*//删除一张相册图片
        \app\common\model\GoodsImages::destroy($id);
        //返回数据
        return self::success();*/
        //查询要删除的记录（获取图片路径）
        $info = GoodsImages::find($id);
        if(empty($info)){
            return self::error('数据异常，图片不存在');
        }
        //从数据表删除一张相册图片的记录
        $info->delete();
        //从磁盘中删除对应的两张图片
        unlink('.' .$info['pics_big']);
        unlink('.' .$info['pics_sma']);
        return self::success('');
    }

}
