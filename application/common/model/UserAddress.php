<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    收货地址管理模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/30
 */

namespace app\common\model;

class UserAddress extends CareyShop
{
    /**
     * 最大添加数量
     * @var int
     */
    const ADDRESS_COUNT_MAX = 20;

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'user_id',
        'is_delete',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'user_address_id',
        'user_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'user_address_id' => 'integer',
        'country'         => 'integer',
        'province'        => 'integer',
        'city'            => 'integer',
        'district'        => 'integer',
        'is_delete'       => 'integer',
    ];

    /**
     * 全局查询条件
     * @access protected
     * @param  object $query 模型
     * @return void
     */
    protected function base($query)
    {
        $query->where(['is_delete' => ['eq', 0]]);
    }

    /**
     * 获取指定账号的收货地址列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAddressList($data)
    {
        if (!$this->validateData($data, 'UserAddress.list')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            $query->where(['user_id' => ['eq', is_client_admin() ? $data['client_id'] : get_client_id()]]);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 获取指定账号的一个收货地址
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAddressItem($data)
    {
        if (!$this->validateData($data, 'UserAddress.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['user_address_id'] = ['eq', $data['user_address_id']];
            $map['user_id'] = ['eq', is_client_admin() ? $data['client_id'] : get_client_id()];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取指定账号的默认收货地址信息
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getAddressDefault($data)
    {
        if (!$this->validateData($data, 'UserAddress.get_default')) {
            return false;
        }

        $map['user_id'] = ['eq', is_client_admin() ? $data['client_id'] : get_client_id()];
        $userId = User::where($map)->value('user_address_id', 0);

        if (!$userId) {
            return $this->setError('尚未指定默认收货地址或尚未存在');
        }

        $result = self::get($userId);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 添加一个收货地址
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addAddressItem($data)
    {
        if (!$this->validateData($data, 'UserAddress')) {
            return false;
        }

        // 处理部分数据
        unset($data['user_address_id'], $data['is_delete']);
        !isset($data['is_default']) ?: $data['is_default'] = (int)$data['is_default'];
        $data['user_id'] = is_client_admin() ? $data['client_id'] : get_client_id();

        if (false !== $this->allowField(true)->save($data)) {
            if (isset($data['is_default']) && $data['is_default'] == 1) {
                $this->setUserAddressDefault($this->getAttr('user_id'), $this->getAttr('user_address_id'));
            }

            return $this->hidden(['client_id'])->toArray();
        }

        return false;
    }

    /**
     * 编辑一个收货地址
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setAddressItem($data)
    {
        if (!$this->validateSetData($data, 'UserAddress.set')) {
            return false;
        }

        // 避免无关字段,并且处理部分字段
        unset($data['is_delete']);
        !isset($data['is_default']) ?: $data['is_default'] = (int)$data['is_default'];

        $userId = is_client_admin() ? $data['client_id'] : get_client_id();
        $map['user_id'] = ['eq', $userId];
        $map['user_address_id'] = ['eq', $data['user_address_id']];

        if (false !== $this->allowField(true)->save($data, $map)) {
            if (isset($data['is_default']) && $data['is_default'] == 1) {
                $this->setUserAddressDefault($userId, $this->getAttr('user_address_id'));
            }

            return $this->hidden(['client_id'])->toArray();
        }

        return false;
    }

    /**
     * 批量删除收货地址
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delAddressList($data)
    {
        if (!$this->validateData($data, 'UserAddress.del')) {
            return false;
        }

        $map['user_address_id'] = ['in', $data['user_address_id']];
        $map['user_id'] = ['eq', is_client_admin() ? $data['client_id'] : get_client_id()];

        if (false !== $this->isUpdate(true)->save(['is_delete' => 1], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 设置账号默认收货地址
     * @access public
     * @param  int $clientId  账号Id
     * @param  int $addressId 收货地址Id
     * @return void
     */
    private function setUserAddressDefault($clientId, $addressId)
    {
        User::where(['user_id' => ['eq', $clientId]])->setField('user_address_id', $addressId);
    }

    /**
     * 设置一个收货地址为默认
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setAddressDefault($data)
    {
        if (!$this->validateData($data, 'UserAddress.default')) {
            return false;
        }

        $result = self::get($data['user_address_id']);
        if (!$result) {
            return is_null($result) ? $this->setError('收货地址不存在') : false;
        }

        if (!is_client_admin() && $result->getAttr('user_id') != get_client_id()) {
            return false;
        }

        $this->setUserAddressDefault($result->getAttr('user_id'), $result->getAttr('user_address_id'));
        return true;
    }

    /**
     * 检测是否超出最大添加数量
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function isAddressMaximum($data)
    {
        if (!$this->validateData($data, 'UserAddress.maximum')) {
            return false;
        }

        $map['user_id'] = ['eq', is_client_admin() ? $data['client_id'] : get_client_id()];
        $result = $this->where($map)->count();

        if ($result >= self::ADDRESS_COUNT_MAX || !is_numeric($result)) {
            return $this->setError('已到达' . self::ADDRESS_COUNT_MAX . '个收货地址');
        }

        return true;
    }
}