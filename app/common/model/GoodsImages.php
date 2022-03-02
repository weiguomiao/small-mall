<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class GoodsImages extends Model
{
    //
    public function goods(){
        return $this->belongsTo('GoodsImages', 'goods_id', 'id');
    }
}
