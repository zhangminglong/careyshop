<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    资源样式模型
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2018/5/31
 */

namespace app\common\model;

class StorageStyle extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'storage_style_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'storage_style_id' => 'integer',
        'platform'         => 'integer',
        'size'             => 'array',
        'crop'             => 'array',
        'quality'          => 'integer',
        'status'           => 'integer',
    ];

    public function uniqueStorageStyleCode($data)
    {
        if (!$this->validateData($data, 'StorageStyle.unique')) {
            return false;
        }

        $map['code'] = ['eq', $data['code']];
        !isset($data['exclude_id']) ?: $map['storage_style_id'] = ['neq', $data['exclude_id']];

        if (self::checkUnique($map)) {
            return $this->setError('资源样式编码已存在');
        }

        return true;
    }

    public function addStorageStyleItem($data)
    {
        if (!$this->validateData($data, 'StorageStyle')) {
            return false;
        }

        // 避免无关字段
        unset($data['storage_style_id']);
        !empty($data['size']) ?: $data['size'] = [];
        !empty($data['crop']) ?: $data['crop'] = [];

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    public function setStorageStyleItem($data)
    {
        if (!$this->validateSetData($data, 'StorageStyle.set')) {
            return false;
        }

        // 验证编码是否重复
        if (!empty($data['code'])) {
            $map['storage_style_id'] = ['neq', $data['storage_style_id']];
            $map['code'] = ['eq', $data['code']];

            if (self::checkUnique($map)) {
                return $this->setError('资源样式编码已存在');
            }
        }

        // 处理数组
        if (isset($data['size']) && '' == $data['size']) {
            $data['size'] = [];
        }

        if (isset($data['crop']) && '' == $data['crop']) {
            $data['crop'] = [];
        }


    }
}