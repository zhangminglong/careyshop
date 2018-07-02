<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    资源管理控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/10
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Storage extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个资源目录
            'add.storage.directory.item'    => ['addStorageDirectoryItem'],
            // 编辑一个资源目录
            'set.storage.directory.item'    => ['setStorageDirectoryItem'],
            // 获取资源目录选择列表
            'get.storage.directory.select'  => ['getStorageDirectorySelect'],
            // 将资源目录标设为默认目录
            'set.storage.directory.default' => ['setStorageDirectoryDefault'],
            // 获取一个资源或资源目录
            'get.storage.item'              => ['getStorageItem'],
            // 获取资源列表
            'get.storage.list'              => ['getStorageList'],
            // 获取导航数据
            'get.storage.navi'              => ['getStorageNavi'],
            // 重命名一个资源
            'rename.storage.item'           => ['renameStorageItem'],
            // 将图片资源设为目录封面
            'cover.storage.item'            => ['coverStorageItem'],
            // 验证资源是否允许移动到指定目录
            'check.storage.move'            => ['checkStorageMove'],
            // 批量移动资源到指定目录
            'move.storage.list'             => ['moveStorageList'],
            // 获取资源缩略图
            'get.storage.thumb'             => ['getThumb', 'app\common\service\Upload'],
            // 获取资源缩略图实际路径
            'get.storage.thumb.url'         => ['getThumbUrl', 'app\common\service\Upload'],
            // 批量删除资源
            'del.storage.list'              => ['delStorageList'],
        ];
    }

    /**
     * 验证资源是否允许移动到指定目录
     * @access protected
     * @return bool
     */
    protected function checkStorageMove()
    {
        $data = $this->getParams();
        $validate = $this->validate($data, 'Storage.move');

        if (true !== $validate) {
            return $this->setError($validate);
        }

        return self::$model->isMoveStorage($data['storage_id'], $data['parent_id']);
    }
}