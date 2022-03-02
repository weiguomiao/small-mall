<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class Category extends Model
{
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //定义分类-品牌关联 以分类表为主，一个分类下有多个品牌
    public function brands()
    {
        //hasMany('关联模型','外键','主键')
        return $this->hasMany('Brand', 'cate_id', 'id');
    }

}
