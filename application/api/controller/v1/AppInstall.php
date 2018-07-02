<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    应用安装包控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/3/9
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class AppInstall extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个应用安装包
            'add.app.install.item'      => ['addAppInstallItem'],
            // 编辑一个应用安装包
            'set.app.install.item'      => ['setAppInstallItem'],
            // 获取一个应用安装包
            'get.app.install.item'      => ['getAppInstallItem'],
            // 批量删除应用安装包
            'del.app.install.list'      => ['delAppInstallList'],
            // 获取应用安装包列表
            'get.app.install.list'      => ['getAppInstallList'],
            // 根据条件查询是否有更新
            'query.app.install.updated' => ['queryAppInstallUpdated'],
            // 根据请求获取一个应用安装包
            'request.app.install.item'  => ['requestAppInstallItem'],
        ];
    }
}