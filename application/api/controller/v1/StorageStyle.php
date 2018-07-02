<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    资源样式控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/5/31
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class StorageStyle extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 验证资源样式编码是否唯一
            'unique.storage.style.code' => ['uniqueStorageStyleCode'],
            // 添加一个资源样式
            'add.storage.style.item'    => ['addStorageStyleItem'],
            // 编辑一个资源样式
            'set.storage.style.item'    => ['setStorageStyleItem'],
            // 获取一个资源样式
            'get.storage.style.item'    => ['getStorageStyleItem'],
            // 根据编码获取资源样式
            //'get.storage.style.code'    => ['getStorageStyleCode'],
            // 获取资源样式列表
            'get.storage.style.list'    => ['getStorageStyleList'],
            // 批量删除资源样式
            'del.storage.style.list'    => ['delStorageStyleList'],
            // 批量设置资源样式状态
            'set.storage.style.status'  => ['setStorageStyleStatus'],
        ];
    }
}