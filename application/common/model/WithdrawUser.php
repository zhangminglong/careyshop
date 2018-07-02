<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    提现账号模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/20
 */

namespace app\common\model;

class WithdrawUser extends CareyShop
{
    /**
     * 最大添加数量
     * @var int
     */
    const WITHDRAWUSER_COUNT_MAX = 10;

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
        'withdraw_user_id',
        'user_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'withdraw_user_id' => 'integer',
        'user_id'          => 'integer',
        'is_delete'        => 'integer',
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
     * 添加一个提现账号
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addWithdrawUserItem($data)
    {
        if (!$this->validateData($data, 'WithdrawUser')) {
            return false;
        }

        // 避免无关字段并初始化部分数据
        unset($data['withdraw_user_id'], $data['is_delete']);
        empty($data['client_id']) ?: $data['client_id'] = (int)$data['client_id'];
        $data['user_id'] = is_client_admin() ? $data['client_id'] : get_client_id();

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个提现账号
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setWithdrawUserItem($data)
    {
        if (!$this->validateSetData($data, 'WithdrawUser.set')) {
            return false;
        }

        // 避免无关字段并初始化部分数据
        unset($data['is_delete']);
        empty($data['client_id']) ?: $data['client_id'] = (int)$data['client_id'];

        $userId = is_client_admin() ? $data['client_id'] : get_client_id();
        $map['user_id'] = ['eq', $userId];
        $map['withdraw_user_id'] = ['eq', $data['withdraw_user_id']];

        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除提现账号
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delWithdrawUserList($data)
    {
        if (!$this->validateData($data, 'WithdrawUser.del')) {
            return false;
        }

        $map['withdraw_user_id'] = ['in', $data['withdraw_user_id']];
        $map['user_id'] = ['eq', is_client_admin() ? $data['client_id'] : get_client_id()];

        if (false !== $this->isUpdate(true)->save(['is_delete' => 1], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 获取指定账号的一个提现账号
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getWithdrawUserItem($data)
    {
        if (!$this->validateData($data, 'WithdrawUser.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['withdraw_user_id'] = ['eq', $data['withdraw_user_id']];
            $map['user_id'] = ['eq', is_client_admin() ? $data['client_id'] : get_client_id()];

            $query->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取指定账号的提现账号列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getWithdrawUserList($data)
    {
        if (!$this->validateData($data, 'WithdrawUser.list')) {
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
     * 检测是否超出最大添加数量
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function isWithdrawUserMaximum($data)
    {
        if (!$this->validateData($data, 'WithdrawUser.maximum')) {
            return false;
        }

        $map['user_id'] = ['eq', is_client_admin() ? $data['client_id'] : get_client_id()];
        $result = $this->where($map)->count();

        if ($result >= self::WITHDRAWUSER_COUNT_MAX || !is_numeric($result)) {
            return $this->setError('已到达' . self::WITHDRAWUSER_COUNT_MAX . '个提现账号');
        }

        return true;
    }
}