<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    PHP自带WebServer支持
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/24
 */

/**
 * 启动命令：php -S localhost:8080 router.php
 */
if (is_file($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])) {
    return false;
} else {
    if (!isset($_SERVER['PATH_INFO'])) {
        $_SERVER['PATH_INFO'] = $_SERVER['REQUEST_URI'];
    }

    require __DIR__ . "/index.php";
}