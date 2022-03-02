<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class Profile extends Model
{
    //定义相对的关联 档案-管理员的关联 一个档案属于一个模型
    public function admin(){
        //belongsTo('关联模型','外键', '关联主键');
        return $this->belongsTo('Admin','uid','admin_id');
    }
}
