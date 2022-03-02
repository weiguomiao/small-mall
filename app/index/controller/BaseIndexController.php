<?php
declare (strict_types = 1);

namespace app\index\controller;

use app\admin\model\Category;
use app\BaseController;
use think\facade\View;

class BaseIndexController extends BaseController
{
    //
    public function initialize()
    {
        //查询分类信息
        $category =Category::select()->toArray();
        //转化为父子级树状结构
        $category = get_tree_list($category);
        //变量赋值
        $viewData=[
            'category'=>$category
        ];
        View::assign($viewData);
    }
}
