<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    Api独立配置文件
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/03/22
 */

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 应用Trace
    'app_trace'           => false,
    // 默认输出类型
    'default_return_type' => 'json',
    // API调试模式
    'api_debug'           => false,

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'    => 'app\api\exception\ApiException',
];