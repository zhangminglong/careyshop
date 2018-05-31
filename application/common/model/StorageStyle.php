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

use think\Cache;

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


    /**
     * 验证资源样式编码是否唯一
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
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

    /**
     * 添加一个资源样式
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
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

    /**
     * 编辑一个资源样式
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
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

        $map = ['storage_style_id' => ['eq', $data['storage_style_id']]];
        $result = $this->allowField(true)->save($data, $map);

        if (false !== $result) {
            Cache::clear('StorageStyle');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一个资源样式
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getStorageStyleItem($data)
    {
        if (!$this->validateData($data, 'StorageStyle.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['storage_style_id'] = ['eq', $data['storage_style_id']];
            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 根据编码获取资源样式
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getStorageStyleCode($data)
    {
        if (!$this->validateData($data, 'StorageStyle.code')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['code'] = ['eq', $data['code']];
            !isset($data['platform']) ?: $map['platform'] = ['eq', $data['platform']];

            $query->cache(true, null, 'StorageStyle')->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取资源样式列表
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getStorageStyleList($data)
    {
        if (!$this->validateData($data, 'StorageStyle.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
        empty($data['code']) ?: $map['code'] = ['eq', $data['code']];
        !isset($data['platform']) ?: $map['platform'] = ['eq', $data['platform']];
        !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];

        $totalResult = $this->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'storage_style_id';

            $query
                ->where($map)
                ->order([$orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 批量删除资源样式
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delStorageStyleList($data)
    {
        if (!$this->validateData($data, 'StorageStyle.del')) {
            return false;
        }

        self::destroy($data['storage_style_id']);
        Cache::clear('StorageStyle');

        return true;
    }

    /**
     * 批量设置资源样式状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setStorageStyleStatus($data)
    {
        if (!$this->validateData($data, 'StorageStyle.status')) {
            return false;
        }

        $map['storage_style_id'] = ['eq', $data['storage_style_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }
}