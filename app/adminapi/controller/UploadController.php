<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

class UploadController extends BaseapiController
{
    /**
     * 单图片上传
     */
    public function logo(){
        //接收参数
        $type = input('type');
        if(empty($type)){
            return self::error('缺少参数');
        }
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('logo');
        if(empty($file)){
            return self::error('必须上传文件');
        }

        //图片移动 /public/uploads/goods/  /public/uploads/category/  /public/uploads/brand/
        $info = $this->$file->validate([
            'size' => 10*1024*1024,
            'ext'=>'jpg,jpeg,png,gif'
        ])->check($file)->move(app()->getRootPath() . 'public/uploads');
        if($info){
            //返回图片路径  /uploads/category/20190715/dsfdsfas.jpg
//            $logo = DS . 'uploads' . DS . $type . DS . $info->getSaveName();
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::disk('public')->putFile( 'uploads',$file,$type);
           return self::success($savename);
        }else{
            //返回报错
            return self::error('上传失败');
        }
    }

    /**
     * 多图片上传
     */
    public function images(){
        //接收type参数 图片分组
        $type=input('type','goods');
        //获取上传的文件(数组)
        $files = request()->file('images');
        //遍历数组逐个上传文件
        try {
            validate([
                'size' => 10*1024*1024,
                'ext'=>'jpg,jpeg,png,gif'
            ])->check($files);
            $savename = [];
            foreach($files as $file) {
                $savename[] = \think\facade\Filesystem::putFile( 'uploads',$file,$type);
            }
           return self::success($savename);
        } catch (\think\exception\ValidateException $e) {
            echo $e->getMessage();
        }
    }
}
