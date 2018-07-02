<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    用户组模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/3/29
 */

namespace app\common\model;

use think\Cache;

class AuthGroup extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'group_id',
        'system',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'group_id' => 'integer',
        'system'   => 'integer',
        'sort'     => 'integer',
        'status'   => 'integer',
    ];

    /**
     * hasMany cs_auth_rule
     * @access public
     * @return mixed
     */
    public function hasAuthRule()
    {
        return $this->hasMany('AuthRule', 'group_id');
    }

    /**
     * 添加一个用户组
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addAuthGroupItem($data)
    {
        if (!$this->validateData($data, 'AuthGroup')) {
            return false;
        }

        // 避免无关字段
        unset($data['group_id'], $data['system']);

        if (false !== $this->allowField(true)->save($data)) {
            Cache::clear('CommonAuth');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个用户组
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setAuthGroupItem($data)
    {
        if (!$this->validateSetData($data, 'AuthGroup.set')) {
            return false;
        }

        $map['group_id'] = ['eq', $data['group_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            Cache::clear('CommonAuth');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一个用户组
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAuthGroupItem($data)
    {
        if (!$this->validateData($data, 'AuthGroup.item')) {
            return false;
        }

        $result = self::get($data['group_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 删除一个用户组
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delAuthGroupItem($data)
    {
        if (!$this->validateData($data, 'AuthGroup.del')) {
            return false;
        }

        $result = self::get($data['group_id']);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if ($result->getAttr('system') === 1) {
            return $this->setError('系统保留用户组不允许删除');
        }

        // 查询是否已被使用
        if (User::checkUnique(['group_id' => $data['group_id']])) {
            return $this->setError('当前用户组已被使用');
        }

        if (Admin::checkUnique(['group_id' => $data['group_id']])) {
            return $this->setError('当前用户组已被使用');
        }

        // 删除本身与规则表中的数据
        $result->delete();
        $result->hasAuthRule()->delete();
        Cache::clear('CommonAuth');

        return true;
    }

    /**
     * 获取用户组列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAuthGroupList($data)
    {
        if (!$this->validateData($data, 'AuthGroup.list')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            // 搜索条件
            $map = [];
            !isset($data['exclude_id']) ?: $map['group_id'] = ['not in', $data['exclude_id']];
            !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'asc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'group_id';

            // 排序处理
            $order['sort'] = 'asc';
            $order[$orderField] = $orderType;

            $query
                ->cache(true, null, 'CommonAuth')
                ->where($map)
                ->order($order);
        });

        if (false === $result) {
            Cache::clear('CommonAuth');
            return false;
        }

        return $result->toArray();
    }

    /**
     * 批量设置用户组状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setAuthGroupStatus($data)
    {
        if (!$this->validateData($data, 'AuthGroup.status')) {
            return false;
        }

        $map['group_id'] = ['in', $data['group_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            Cache::clear('CommonAuth');
            return true;
        }

        return false;
    }

    /**
     * 设置用户组排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setAuthGroupSort($data)
    {
        if (!$this->validateData($data, 'AuthGroup.sort')) {
            return false;
        }

        $map['group_id'] = ['eq', $data['group_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            Cache::clear('CommonAuth');
            return true;
        }

        return false;
    }
}