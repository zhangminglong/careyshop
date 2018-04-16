<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    规则模型
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2018/3/27
 */

namespace app\common\model;

use think\Cache;

class AuthRule extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'rule_id',
        'group_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'rule_id'   => 'integer',
        'group_id'  => 'integer',
        'menu_auth' => 'array',
        'log_auth'  => 'array',
        'sort'      => 'integer',
        'status'    => 'integer',
    ];

    /**
     * 添加一条规则
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function addAuthRuleItem($data)
    {
        if (!$this->validateData($data, 'AuthRule')) {
            return false;
        }

        // 避免无关字段
        unset($data['rule_id']);

        $map['module'] = ['eq', $data['module']];
        $map['group_id'] = ['eq', $data['group_id']];

        if (self::checkUnique($map)) {
            return $this->setError('相同的模块已存在');
        }

        if (false !== $this->allowField(true)->save($data)) {
            Cache::clear('CommonAuth');
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一条规则
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getAuthRuleItem($data)
    {
        if (!$this->validateData($data, 'AuthRule.get')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['rule_id'] = ['eq', $data['rule_id']];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 编辑一条规则
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function setAuthRuleItem($data)
    {
        if (!$this->validateSetData($data, 'AuthRule.set')) {
            return false;
        }

        $result = self::get($data['rule_id']);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if (!empty($data['module'])) {
            $map['rule_id'] = ['neq', $data['rule_id']];
            $map['module'] = ['eq', $data['module']];
            $map['group_id'] = ['eq', $result->getAttr('group_id')];

            if (self::checkUnique($map)) {
                return $this->setError('相同的模块已存在');
            }
        }

        if (false !== $result->allowField(true)->save($data)) {
            Cache::clear('CommonAuth');
            return $result->toArray();
        }

        return false;
    }

    /**
     * 批量删除规则
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delAuthRuleList($data)
    {
        if (!$this->validateData($data, 'AuthRule.del')) {
            return false;
        }

        self::destroy($data['rule_id']);

        return true;
    }

    /**
     * 获取规则列表
     * @access public
     * @param  array $data 外部数据
     * @return array/false
     */
    public function getAuthRuleList($data)
    {
        if (!$this->validateData($data, 'AuthRule.list')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            // 搜索条件
            $map = [];
            $map['group_id'] = ['eq', $data['group_id']];
            !isset($data['module']) ?: $map['module'] = ['eq', $data['module']];
            !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'asc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'rule_id';

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
     * 批量设置规则状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setAuthRuleStatus($data)
    {
        if (!$this->validateData($data, 'AuthRule.status')) {
            return false;
        }

        $map['rule_id'] = ['in', $data['rule_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            Cache::clear('CommonAuth');
            return true;
        }

        return false;
    }

    /**
     * 设置规则排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setAuthRuleSort($data)
    {
        if (!$this->validateData($data, 'AuthRule.sort')) {
            return false;
        }

        $map['rule_id'] = ['eq', $data['rule_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            Cache::clear('CommonAuth');
            return true;
        }

        return false;
    }

    /**
     * 根据用户组编号与对应模块获取权限明细
     * @access public
     * @param  string $module  对应模块
     * @param  int    $groupId 用户组编号
     * @return array/false
     */
    public static function getMenuAuthRule($module, $groupId)
    {
        $map['module'] = ['eq', $module];
        $map['group_id'] = ['eq', $groupId];
        $map['status'] = ['eq', 1];

        $result = self::where($map)->cache(true, null, 'CommonAuth')->find();
        if (!$result) {
            if (false === $result) {
                Cache::clear('CommonAuth');
            }

            return false;
        }

        return $result->toArray();
    }
}