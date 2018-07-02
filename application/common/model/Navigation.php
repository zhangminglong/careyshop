<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    导航模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/7
 */

namespace app\common\model;

use think\Cache;

class Navigation extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'navigation_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'navigation_id' => 'integer',
        'sort'          => 'integer',
        'status'        => 'integer',
    ];

    /**
     * 添加一个导航
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addNavigationItem($data)
    {
        if (!$this->validateData($data, 'Navigation')) {
            return false;
        }

        // 避免无关字段
        unset($data['navigation_id']);

        if (false !== $this->allowField(true)->save($data)) {
            Cache::clear('Navigation');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个导航
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setNavigationItem($data)
    {
        if (!$this->validateSetData($data, 'Navigation.set')) {
            return false;
        }

        $map['navigation_id'] = ['eq', $data['navigation_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            Cache::clear('Navigation');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除导航
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delNavigationList($data)
    {
        if (!$this->validateData($data, 'Navigation.del')) {
            return false;
        }

        self::destroy($data['navigation_id']);
        Cache::clear('Navigation');

        return true;
    }

    /**
     * 获取一个导航
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getNavigationItem($data)
    {
        if (!$this->validateData($data, 'Navigation.item')) {
            return false;
        }

        $result = self::get($data['navigation_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 批量设置是否新开窗口
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setNavigationTarget($data)
    {
        if (!$this->validateData($data, 'Navigation.target')) {
            return false;
        }

        $map['navigation_id'] = ['in', $data['navigation_id']];
        if (false !== $this->save(['target' => $data['target']], $map)) {
            Cache::clear('Navigation');
            return true;
        }

        return false;
    }

    /**
     * 批量设置是否启用
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setNavigationStatus($data)
    {
        if (!$this->validateData($data, 'Navigation.status')) {
            return false;
        }

        $map['navigation_id'] = ['in', $data['navigation_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            Cache::clear('Navigation');
            return true;
        }

        return false;
    }

    /**
     * 获取导航列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getNavigationList($data)
    {
        if (!$this->validateData($data, 'Navigation.list')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            // 搜索条件
            $map['status'] = ['eq', 1];

            // 后台管理搜索
            if (is_client_admin()) {
                unset($map['status']);
                empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
                !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
            }

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'asc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'navigation_id';

            // 排序处理
            $order['sort'] = 'asc';
            $order[$orderField] = $orderType;

            $query->cache(true, null, 'Navigation')->where($map)->order($order);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 设置导航排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setNavigationSort($data)
    {
        if (!$this->validateData($data, 'Navigation.sort')) {
            return false;
        }

        $map['navigation_id'] = ['eq', $data['navigation_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            return true;
        }

        return false;
    }
}