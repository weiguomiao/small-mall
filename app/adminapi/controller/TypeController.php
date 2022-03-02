<?php
declare (strict_types=1);

namespace app\adminapi\controller;

use app\common\model\Attribute;
use app\common\model\Goods;
use app\common\model\Spec;
use app\common\model\SpecValue;
use app\common\model\Type;
use think\Request;
use \think\facade\Db;

class TypeController extends BaseapiController
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
        //查询数据
        $list = Db::name('type')->select();
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
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params, [
            'type_name|模型名称' => 'require|max:20',
            'spec|规格' => 'require|array',
            'attr|属性' => 'require|array'
        ]);
        if ($validate !== true) {
            return self::error($validate);
        }

            //添加商品类型
            //4+2  添加类型、批量添加规格名、批量添加规格值、批量添加属性； 去除空的规格，去除空的属性
            //添加商品类型 $type['id']  后续要使用
            $type = Type::create($params);
            //添加商品规格名
                //去除空的规格值 去除没有值的规格名
                //外遍历规格名称
            foreach ($params['spec'] as $i => $spec) {
                if (trim($spec['name']) == '') {
                    unset($params['spec'][$i]);
                    //continue;
                } else {
                    //内遍历规格值
                    foreach ($spec['value'] as $k => $value) {
                        //$value就是一个规格值
                        if (trim($value) == '') {
                            unset($params['spec'][$i]['value'][$k]);
                        }
                    }
                    //判断规格值数组，是否为空数组
                    if (empty($params['spec'][$i]['value'])) {
                        unset($params['spec'][$i]);
                    }
                }
            }
            unset($spec);
                //遍历组装 数据表需要的数据
            $specs = [];
            foreach ($params['spec'] as $spec) {
                $row = [
                    'type_id' => $type['id'],
                    'spec_name' => $spec['name'],
                    'sort' => $spec['sort']
                ];
                $specs[] = $row;
            }
            //批量添加 规格名称
            $spec_model = new Spec();
            $spec_data = $spec_model->saveAll($specs);
            //添加商品规格值
                //外层遍历规格名称
            $spec_values = [];
            foreach ($params['spec'] as $i => $spec) {
                //内层遍历规格值
                foreach ($spec['value'] as $k => $value) {
                    $row = [
                        'spec_id' => $spec_data[$i]['id'],
                        'spec_value' => $value,
                        'type_id' => $type['id']
                    ];
                    $spec_values[] = $row;
                }
            }
            $spec_value_model = new SpecValue();
            $spec_value_model->saveAll($spec_values);
            //添加商品属性
                //去除空的属性名和空的属性值
                //外层遍历属性名
            foreach ($params['attr'] as $i => &$attr) {
                if (trim($attr['name']) == '') {
                    unset($params['attr'][$i]);
                    continue;
                }
                $attr['value']=[
                    "1","2"
                ];
                //内层遍历属性值
                foreach ($attr['value'] as $k => $value) {
                    if (trim($value) == '') {
                        unset($attr['value'][$k]); //对应$attr加引用的情况
                        //unset($params['attr'][$i]['value'][$k]);
                    }
                }

            }
            unset($attr);
            $attrs = [];
            foreach ($params['attr'] as $i => $attr) {
                if(is_array($attr)){
                   $aa= implode(',', $attr['value']);
                }else{
                    $aa=null;
                }
                $row = [
                    'attr_name' => $attr['name'],
                    'sort' => $attr['sort'],
                    'attr_value' =>$aa ,//implode  将一个一维数组的值转化为字符串
                    'type_id' => $type['id']
                ];
                $attrs[] = $row;
            }
            //批量添加属性
            $attr_model = new Attribute();
            $attr_model->saveAll($attrs);

            //返回数据
            $type = Type::find($type['id']);
            return self::success($type);
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
        //查询一条数据（包含规格信息 ，规格值， 属性信息）
        $info = Type::with(['specs', 'specs.spec_values', 'attrs'])->find($id);
        return self::success($info);
    }


    /**
     * 保存更新的资源
     *
     * @param  \think\Request $request
     * @param  int $id
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params, [
            'type_name|模型名称' => 'require|max:20',
            'spec|规格' => 'require|array',
            'attr|属性' => 'require|array'
        ]);
        if ($validate !== true) {
            return self::error($validate);
        }
        //开启事务
        Db::startTrans();
        try {
            //修改模（类）型名称
            Type::update(['type_name' => $params['type_name']], ['id' => $id]);
            //Type::where('id', $id)->update(['type_name'=>$params['type_name']]);
            //去除空的规格名和规格值
            //参数数组参考：
            /*$params = [
                'type_name' => '手机',
                'spec' => [
                    ['name' => '颜色', 'sort' => 50, 'value'=>['黑色', '白色', '金色']],
                    //['name' => '颜色1', 'sort' => 50, 'value'=>['', '']],
                    ['name' => '内存', 'sort' => 50, 'value'=>['64G', '128G', '256G']],
                ],
                'attr' => [
                    ['name' => '毛重', 'sort'=>50, 'value' => []],
                    ['name' => '产地', 'sort'=>50, 'value' => ['进口', '国产','']],
                ]
            ]*/
            //外层遍历规格名称
            foreach ($params['spec'] as $i => $spec) {
                if (trim($spec['name']) == '') {
                    unset($params['spec'][$i]);
                    continue;
                } else {
                    //内存遍历规格值
                    foreach ($spec['value'] as $k => $value) {
                        //$value就是一个规格值
                        if (trim($value) == '') {
                            unset($params['spec'][$i]['value'][$k]);
                        }
                    }
                    //判断规格值数组，是否为空数组
                    if (empty($params['spec'][$i]['value'])) {
                        unset($params['spec'][$i]);
                    }
                }
            }
            //批量删除原来的规格名  删除条件 类型type_id
            Spec::destroy(['type_id' => $id]);
            //\app\common\model\Spec::where('type_id', $id)->delete();
            //批量添加新的规格名
            $specs = [];
            foreach ($params['spec'] as $i => $spec) {
                $row = [
                    'spec_name' => $spec['name'],
                    'sort' => $spec['sort'],
                    'type_id' => $id
                ];
                $specs[] = $row;
            }
            $spec_model = new Spec();
            $spec_data = $spec_model->saveAll($specs);
            /*$spec_data = [
                ['id' => 10, 'spec_name' => '颜色', 'sort' => 50], //实际上是模型对象
                ['id' => 20, 'spec_name' => '内存', 'sort' => 50],
            ];*/
            //批量删除原来的规格值
            SpecValue::destroy(['type_id' => $id]);
            //批量添加新的规格值
            $spec_values = [];
            foreach ($params['spec'] as $i => $spec) {
                foreach ($spec['value'] as $value) {
                    $row = [
                        'spec_id' => $spec_data[$i]['id'],
                        'type_id' => $id,
                        'spec_value' => $value
                    ];
                    $spec_values[] = $row;
                }
            }
            $spec_value_model = new SpecValue();
            $spec_value_model->saveAll($spec_values);
            //去除空的属性值
            foreach ($params['attr'] as $i => $attr) {
                if (trim($attr['name']) == '') {
                    unset($params['attr'][$i]);
                    //continue;
                } else {
                    foreach ($attr['value'] as $k => $value) {
                        if (trim($value) == '') {
                            unset($params['attr'][$i]['value'][$k]);
                        }
                    }
                }
            }
            //批量删除原来的属性
            Attribute::destroy(['type_id' => $id]);
            //批量添加新的属性
            $attrs = [];
            foreach ($params['attr'] as $i => $attr) {
                $row = [
                    'type_id' => $id,
                    'attr_name' => $attr['name'],
                    'attr_values' => implode(',', $attr['value']),
                    'sort' => $attr['sort']
                ];
                $attrs[] = $row;
            }
            $attr_model = new Attribute();
            $attr_model->saveAll($attrs);
            //提交事务
            Db::commit();
            //返回数据
            $type = Type::find($id);
            return self::success($type);
        } catch (\Exception $e) {
            //回滚事务
            Db::rollback();
            //返回数据
            return self::error('操作失败');
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delete($id)
    {
        //判断是否有商品在使用该商品类型
        $goods = Goods::where('type_id', $id)->find();
        if ($goods) {
            return self::error('商品下有该商品类型');
        }

        // 启动事务   用于数据库操作比较多的时候
        Db::startTrans();
        try {
            //删除数据（商品类型、类型下的规格名、类型下的规格值、类型下的属性）
            Type::destroy($id);
            Spec::destroy(['type_id', $id]);
            SpecValue::destroy(['type_id', $id]);
            Attribute::destroy(['type_id', $id]);
            // 提交事务
            Db::commit();
            //返回数据
            return self::success('1');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return self::error('删除失败');
        }

    }
}
