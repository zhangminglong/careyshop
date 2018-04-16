<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    Api控制器
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/03/23
 */

namespace app\api\controller;

class Index
{
    public function index()
    {
        return ['status' => 200, 'data' => 'welcome to careyshop api'];
    }
}