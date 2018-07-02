<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    友情链接模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/27
 */

namespace app\common\model;

class FriendLink extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'friend_link_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'friend_link_id' => 'integer',
        'sort'           => 'integer',
        'status'         => 'integer',
    ];

    /**
     * 添加一个友情链接
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addFriendLinkItem($data)
    {
        if (!$this->validateData($data, 'FriendLink')) {
            return false;
        }

        // 避免无关字段
        unset($data['friend_link_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个友情链接
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setFriendLinkItem($data)
    {
        if (!$this->validateSetData($data, 'FriendLink.set')) {
            return false;
        }

        $map['friend_link_id'] = ['eq', $data['friend_link_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除友情链接
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delFriendLinkList($data)
    {
        if (!$this->validateData($data, 'FriendLink.del')) {
            return false;
        }

        self::destroy($data['friend_link_id']);

        return true;
    }

    /**
     * 获取一个友情链接
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getFriendLinkItem($data)
    {
        if (!$this->validateData($data, 'FriendLink.item')) {
            return false;
        }

        $result = self::get($data['friend_link_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取友情链接列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getFriendLinkList($data)
    {
        if (!$this->validateData($data, 'FriendLink.list')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            // 搜索条件
            $map['status'] = ['eq', 1];

            // 后台管理搜索
            if (is_client_admin()) {
                unset($map['status']);
                !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
                empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
            }

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'friend_link_id';

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
     * 批量设置友情链接状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setFriendLinkStatus($data)
    {
        if (!$this->validateData($data, 'FriendLink.status')) {
            return false;
        }

        $map['friend_link_id'] = ['in', $data['friend_link_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 设置友情链接排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setFriendLinkSort($data)
    {
        if (!$this->validateData($data, 'FriendLink.sort')) {
            return false;
        }

        $map['friend_link_id'] = ['eq', $data['friend_link_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            return true;
        }

        return false;
    }
}