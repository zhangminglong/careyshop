<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    Api结果输出
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/7/9
 */

namespace app\api\exception;

use think\Request;

class ApiOutput
{
    /**
     * 输出格式
     * @var string
     */
    public static $format = 'json';

    /**
     * X-Powered-By
     * @var array
     */
    public static $poweredBy = ['X-Powered-By' => '基于CareyShop商城框架系统'];

    /**
     * 数据输出
     * @access public
     * @param array  $data    数据
     * @param int    $code    状态码
     * @param bool   $error   正常或错误
     * @param string $message 提示内容
     * @return mixed
     */
    public static function outPut($data = [], $code = 200, $error = false, $message = '')
    {
        // 头部
        $header = [];
        $header = array_merge($header, self::$poweredBy);

        // 参数
        $options = ['root_node' => 'careyshop'];

        // 数据
        $result = [
            'status'  => $code,
            'message' => $error == true ? empty($message) ? '发生未知异常' : $message : 'success',
        ];

        if (!$error) {
            $result['data'] = !empty($data) ? $data : (object)[];
        } else {
            // 状态(非HTTPS始终为200状态,防止运营商劫持)
            $code = Request::instance()->isSsl() ? $code : 200;
        }

        switch (self::$format) {
            case 'jsonp':
                return jsonp($result, $code, $header);

            case 'xml':
                return xml($result, $code, $header, $options);

            case 'json':
            default:
                return json($result, $code, $header);
        }
    }
}