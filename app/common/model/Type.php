<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Type extends Model
{
    //定义类型 -规格名关联 一个类型有多个规格名
    public function specs(){
        return $this->hasMany('Spec','type_id');
    }

    //定义类型-属性关联 一个类型下有多个属性
    public function attrs(){
        return $this->hasMany('Attribute','type_id');
    }
}
