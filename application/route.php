<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    路由配置文件
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/03/22
 */

use think\Route;

Route::rule('api/:version/:controller', 'api/:version.:controller/index');