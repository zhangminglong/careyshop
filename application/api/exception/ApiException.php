<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    Api异常类接管
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/03/22
 */

namespace app\api\exception;

use think\exception\Handle;
use think\exception\HttpException;

class ApiException extends Handle
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Exception $e
     * @return mixed
     */
    public function render(\Exception $e)
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
        }

        if (!isset($statusCode)) {
            $statusCode = 500;
        }

        return ApiOutput::outPut([], $statusCode, true, $e->getMessage());
    }
}