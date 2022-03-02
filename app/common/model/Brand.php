<?php
/**
 * Created by PhpStorm.
 * User: 遇憬技术
 * Date: 2020/8/12
 * Time: 15:20
 */

namespace app\common\model;


use think\Model;
use think\model\concern\SoftDelete;

class Brand extends Model
{
    //软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    //定义品牌-分类关联关系 一个品牌属于一个分类
    public function category()
    {
        return $this->BelongsTo('Category', 'cate_id', 'id')->bind(['cate_name']);
        //return $this->BelongsTo('Category', 'cate_id', 'id')
    }
}