<?php
declare (strict_types = 1);

namespace app;

use think\App;

use app\common\enum\HttpCode;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array $data 数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 返回json数据
     * @param mixed $data 响应数据
     * @param string $msg 错误消息
     * @param int $code 响应码
     * @param int $status_code 响应状态码
     * @param array $header 响应头
     * @return \think\response\Json
     */
    protected static function returnJson($data, $msg, int $code, int $status_code, array $header = [])
    {
        return json(['data' => $data, 'msg' => $msg, 'code' => $code])->code($status_code)->header($header);
    }

    /**
     * 返回成功json
     * @param $data
     * @param int $code
     * @param int $status_code
     * @param array $header
     * @return \think\response\Json
     */
    public static function success($data, int $code = HttpCode::SUCCESS, int $status_code = 200, array $header = [])
    {
        return self::returnJson($data, '', $code, $status_code, $header);
    }

    /**
     * 返回错误json
     * @param string $msg 错误消息
     * @param int $code
     * @param int $status_code
     * @param array $header 响应头
     * @return \think\response\Json
     */
    public static function error(string $msg, int $code = HttpCode::ERROR, int $status_code = 200, array $header = [])
    {
        return self::returnJson(null, $msg, $code, $status_code, $header);
    }

    /**
     * 重定向跳转
     * @param string $path
     * @param array $vars
     */
    public function redirect(string $path, $vars = [])
    {
        $url = (string)url($path, $vars);
        return header('location:' . $url);
    }

}
