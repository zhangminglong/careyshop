<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    客服模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/28
 */

namespace app\common\model;

class Support extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'support_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'support_id' => 'integer',
        'sort'       => 'integer',
        'status'     => 'integer',
    ];

    /**
     * 添加一名客服
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addSupportItem($data)
    {
        if (!$this->validateData($data, 'Support')) {
            return false;
        }

        // 避免无关字段
        unset($data['support_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一名客服
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setSupportItem($data)
    {
        if (!$this->validateSetData($data, 'Support.set')) {
            return false;
        }

        $map['support_id'] = ['eq', $data['support_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除客服
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delSupportList($data)
    {
        if (!$this->validateData($data, 'Support.del')) {
            return false;
        }

        self::destroy($data['support_id']);

        return true;
    }

    /**
     * 获取一名客服
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getSupportItem($data)
    {
        if (!$this->validateData($data, 'Support.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['support_id'] = ['eq', $data['support_id']];
            is_client_admin() ?: $map['status'] = ['eq', 1];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取客服列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getSupportList($data)
    {
        if (!$this->validateData($data, 'Support.list')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            // 搜索条件
            $map['status'] = ['eq', 1];

            // 后台管理搜索
            if (is_client_admin()) {
                unset($map['status']);
                !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
                empty($data['type_name']) ?: $map['type_name'] = ['like', '%' . $data['type_name'] . '%'];
                empty($data['nick_name']) ?: $map['nick_name'] = ['like', '%' . $data['nick_name'] . '%'];
            }

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'asc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'support_id';

            // 排序处理
            $order['sort'] = 'asc';
            $order[$orderField] = $orderType;

            $query->where($map)->order($order);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 批量设置客服状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setSupportStatus($data)
    {
        if (!$this->validateData($data, 'Support.status')) {
            return false;
        }

        $map['support_id'] = ['in', $data['support_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 设置客服排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setSupportSort($data)
    {
        if (!$this->validateData($data, 'Support.sort')) {
            return false;
        }

        $map['support_id'] = ['eq', $data['support_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            return true;
        }

        return false;
    }
}