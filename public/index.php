<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    应用入口文件
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/24
 */

// PHP版本检查
if (version_compare(PHP_VERSION, '5.6', '<')) {
    header("Content-type: text/html; charset=utf-8");
    die('PHP版本过低，最少需要PHP5.6，请升级PHP版本！');
}

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

// 定义额外的系统常量
define('APP_PUBLIC_PATH', '');
define('ADMIN_MODULE', 'admin');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';