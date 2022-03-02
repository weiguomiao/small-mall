<?php
// 应用公共文件

if(!function_exists('get_cate_list')){
    //递归函数 实现无限级分类列表
    function get_cate_list($list,$pid=0,$level=0) {
        static $tree = array();
        foreach($list as $row) {
            if($row['pid']==$pid) {
                $row['level'] = $level;
                $tree[] = $row;
                get_cate_list($list, $row['id'], $level + 1);
            }
        }
        return $tree;
    }
}

if(!function_exists('get_tree_list')){
    //引用方式实现 父子级树状结构
    function get_tree_list($list){
        //将每条数据中的id值作为其下标
        $temp = [];
        foreach($list as $v){
            $v['son'] = [];
            $temp[$v['id']] = $v;
        }
        //获取分类树
        foreach($temp as $k=>$v){
            $temp[$v['pid']]['son'][] = &$temp[$v['id']];
        }
        return isset($temp[0]['son']) ? $temp[0]['son'] : [];
    }
}

//截取多余字符用省略号代替
function cut_str($sourcestr,$cutlength)
{
    $returnstr='';
    $i=0;
    $n=0;
    $str_length=strlen($sourcestr);//字符串的字节数
    while (($n<$cutlength) and ($i<=$str_length))
    {
        $temp_str=substr($sourcestr,$i,1);
        $ascnum=Ord($temp_str);//得到字符串中第$i位字符的ascii码
        if ($ascnum>=224)    //如果ASCII位高与224，
        {
            $returnstr=$returnstr.substr($sourcestr,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
            $i=$i+3;            //实际Byte计为3
            $n++;            //字串长度计1
        }
        elseif ($ascnum>=192) //如果ASCII位高与192，
        {
            $returnstr=$returnstr.substr($sourcestr,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
            $i=$i+2;            //实际Byte计为2
            $n++;            //字串长度计1
        }
        elseif ($ascnum>=65 && $ascnum<=90) //如果是大写字母，
        {
            $returnstr=$returnstr.substr($sourcestr,$i,1);
            $i=$i+1;            //实际的Byte数仍计1个
            $n++;            //但考虑整体美观，大写字母计成一个高位字符
        }
        else                //其他情况下，包括小写字母和半角标点符号，
        {
            $returnstr=$returnstr.substr($sourcestr,$i,1);
            $i=$i+1;            //实际的Byte数计1个
            $n=$n+0.5;        //小写字母和半角标点等与半个高位字符宽…
        }
    }
    if ($str_length>$i){
        $returnstr = $returnstr . "…";//超过长度时在尾处加上省略号
    }
    return $returnstr;
}

if(!function_exists('curl_request'))
{
    //使用curl函数库发送请求
    function curl_request($url, $post=true, $params=[], $https=true)
    {
        //初始化请求
        $ch = curl_init($url);
        //默认是get请求。如果是post请求 设置请求方式和请求参数
        if($post){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        //如果是https协议，禁止从服务器验证本地证书
        if($https){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        //发送请求，获取返回结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        /*if(!$res){
            $msg = curl_error($ch);
            dump($msg);die;
        }*/
        //关闭请求
        curl_close($ch);
        return $res;
    }
}

if(!function_exists('sendmsg')){
    //使用curl_request函数调用短信接口发送短信
    function sendmsg($phone, $content)
    {
        //从配置中取出请求地址、appkey
        $gateway = config('msg.gateway');
        $appkey = config('msg.appkey');
        //https://way.jd.com/chuangxin/dxjk?mobile=13568813957&content=【创信】你的验证码是：5873，3分钟内有效！&appkey=您申请的APPKEY
        $url = $gateway . '?appkey=' . $appkey;
        //get请求
        /*$url .= '&mobile=' . $phone . '&content=' . $content;
        $res = curl_request($url, false, [], true);*/
        //post请求
        $params = [
            'mobile' => $phone,
            'content' => $content
        ];
        $res = curl_request($url, true, $params, true);
        //处理结果
        if(!$res){
            return '请求发送失败';
        }
        //解析结果
        $arr = json_decode($res, true);
        if(isset($arr['code']) && $arr['code'] == 10000){
            //短信接口调用成功
            return true;
        }else{
            /*if(isset($arr['msg'])){
                return $arr['msg'];
            }*/
            return '短信发送失败';
        }
    }
}

if(!function_exists('encrypt_phone')){
    //手机号加密  15312345678   =》  153****5678
    function encrypt_phone($phone)
    {
        return substr($phone,0,3) . '****' . substr($phone, 7);
    }
}
