<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class SpecGoods extends Model
{
    //
    public function goods(){
        return $this->belongsTo('SpecGoods', 'goods_id', 'id');
    }
}
