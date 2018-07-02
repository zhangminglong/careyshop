<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    提现模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/21
 */

namespace app\common\model;

use think\Config;

class Withdraw extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'withdraw_id',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'withdraw_id',
        'withdraw_no',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'withdraw_id'      => 'integer',
        'user_id'          => 'integer',
        'money'            => 'float',
        'fee'              => 'float',
        'amount'           => 'float',
        'status'           => 'integer',
    ];

    /**
     * hasOne cs_user
     * @access public
     * @return mixed
     */
    public function getUser()
    {
        return $this
            ->hasOne('User', 'user_id', 'user_id', [], 'left')
            ->field('username,nickname,head_pic')
            ->setEagerlyType(0);
    }

    /**
     * 生成唯一提现单号
     * @access private
     * @return string
     */
    private function getWithdrawNo()
    {
        do {
            $withdrawNo = get_order_no('TX_');
        } while (self::checkUnique(['withdraw_no' => ['eq', $withdrawNo]]));

        return $withdrawNo;
    }

    /**
     * 添加交易记录
     * @access public
     * @param  int    $type       收入或支出
     * @param  float  $amount     总金额
     * @param  int    $userId     账号编号
     * @param  string $withdrawNo 提现单号
     * @param  string $remark     备注
     * @return bool
     */
    private function addTransaction($type, $amount, $userId, $withdrawNo, $remark)
    {
        $transactionData = [
            'user_id'    => $userId,
            'type'       => $type,
            'amount'     => $amount,
            'balance'    => UserMoney::where(['user_id' => ['eq', $userId]])->value('balance'),
            'source_no'  => $withdrawNo,
            'remark'     => $remark,
            'module'     => 'money',
            'to_payment' => Payment::PAYMENT_CODE_USER,
        ];

        $transactionDb = new Transaction();
        if (false === $transactionDb->addTransactionItem($transactionData)) {
            return $this->setError($transactionDb->getError());
        }

        return true;
    }

    /**
     * 获取一个提现请求
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getWithdrawItem($data)
    {
        if (!$this->validateData($data, 'Withdraw.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['withdraw.withdraw_no'] = ['eq', $data['withdraw_no']];
            is_client_admin() ? $query->with('getUser') : $map['withdraw.user_id'] = ['eq', get_client_id()];

            $query->alias('withdraw')->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取提现请求列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getWithdrawList($data)
    {
        if (!$this->validateData($data, 'Withdraw.list')) {
            return false;
        }

        // 搜索条件
        $map['withdraw.user_id'] = ['eq', get_client_id()];
        empty($data['withdraw_no']) ?: $map['withdraw.withdraw_no'] = ['eq', $data['withdraw_no']];
        !isset($data['status']) ?: $map['withdraw.status'] = ['eq', $data['status']];

        if (!empty($data['begin_time']) && !empty($data['end_time'])) {
            $map['withdraw.create_time'] = ['between time', [$data['begin_time'], $data['end_time']]];
        }

        // 关联查询
        $with = [];

        // 后台管理搜索
        if (is_client_admin()) {
            $with = ['getUser'];
            unset($map['withdraw.user_id']);
            empty($data['account']) ?: $map['getUser.username|getUser.nickname'] = ['eq', $data['account']];
        }

        $totalResult = $this->alias('withdraw')->with($with)->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map, $with) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'asc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'withdraw_id';

            $query
                ->alias('withdraw')
                ->with($with)
                ->where($map)
                ->order(['withdraw.' . $orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 申请一个提现请求
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addWithdrawItem($data)
    {
        if (!$this->validateData($data, 'Withdraw')) {
            return false;
        }

        $map['withdraw_user_id'] = ['eq', $data['withdraw_user_id']];
        $map['user_id'] = ['eq', get_client_id()];

        $userResult = WithdrawUser::where($map)->find();
        if (!$userResult) {
            return $this->setError('提现账号异常');
        }

        // 处理数据
        unset($data['withdraw_id'], $data['remark']);
        $data['fee'] = Config::get('withdraw_fee.value', 'system_info');
        $data['withdraw_no'] = $this->getWithdrawNo();
        $data['user_id'] = get_client_id();
        $data['amount'] = round($data['money'] + (($data['fee'] / 100) * $data['money']), 2);
        $data['status'] = 0;
        $data['name'] = $userResult['name'];
        $data['mobile'] = $userResult['mobile'];
        $data['bank_name'] = $userResult['bank_name'];
        $data['account'] = $userResult['account'];

        // 开启事务
        self::startTrans();

        try {
            // 添加主表
            if (false === $this->allowField(true)->save($data)) {
                throw new \Exception($this->getError());
            }

            // 减少可用余额,并增加锁定余额
            $userMoneyDb = new UserMoney();
            if (!$userMoneyDb->decBalanceAndIncLock($data['amount'], $data['user_id'])) {
                throw new \Exception($userMoneyDb->getError());
            }

            // 添加交易记录
            if (!$this->addTransaction(Transaction::TRANSACTION_EXPENDITURE, $data['amount'], get_client_id(), $this->getAttr('withdraw_no'), '申请提现')) {
                throw new \Exception($this->getError());
            }

            self::commit();
            return $this->hidden(['withdraw_user_id'])->toArray();
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 取消一个提现请求
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function cancelWithdrawItem($data)
    {
        if (!$this->validateData($data, 'Withdraw.cancel')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['withdraw_no'] = ['eq', $data['withdraw_no']];
            $map['user_id'] = ['eq', get_client_id()];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if ($result->getAttr('status') !== 0) {
            return $this->setError('提现状态已不可取消');
        }

        // 开启事务
        self::startTrans();

        try {
            // 修改主表
            if (false === $result->setAttr('status', 2)->save()) {
                throw new \Exception($this->getError());
            }

            // 增加可用余额,并减少锁定余额
            $userMoneyDb = new UserMoney();
            $amount = $result->getAttr('amount');

            if (!$userMoneyDb->incBalanceAndDecLock($amount, get_client_id())) {
                throw new \Exception($userMoneyDb->getError());
            }

            // 添加交易记录
            if (!$this->addTransaction(Transaction::TRANSACTION_INCOME, $amount, get_client_id(), $result->getAttr('withdraw_no'), '取消提现')) {
                throw new \Exception($this->getError());
            }

            self::commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 处理一个提现请求
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function processWithdrawItem($data)
    {
        if (!$this->validateData($data, 'Withdraw.process')) {
            return false;
        }

        $result = self::get(['withdraw_no' => $data['withdraw_no']]);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if ($result->getAttr('status') !== 0) {
            return $this->setError('提现状态已不可处理');
        }

        if (false !== $result->setAttr('status', 1)->save()) {
            return true;
        }

        return false;
    }

    /**
     * 完成一个提现请求
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function completeWithdrawItem($data)
    {
        if (!$this->validateData($data, 'Withdraw.complete')) {
            return false;
        }

        $result = self::get(['withdraw_no' => $data['withdraw_no']]);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if ($result->getAttr('status') !== 1) {
            return $this->setError('提现状态不可完成');
        }

        // 开启事务
        self::startTrans();

        try {
            // 修改主表
            if (false === $result->save(['status' => 3, 'remark' => $data['remark']])) {
                throw new \Exception($this->getError());
            }

            // 减少锁定余额
            $userMoneyDb = new UserMoney();
            if (!$userMoneyDb->decLockBalance($result->getAttr('amount'), $result->getAttr('user_id'))) {
                throw new \Exception($userMoneyDb->getError());
            }

            self::commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 拒绝一个提现请求
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function refuseWithdrawItem($data)
    {
        if (!$this->validateData($data, 'Withdraw.refuse')) {
            return false;
        }

        $result = self::get(['withdraw_no' => $data['withdraw_no']]);
        if (!$result) {
            return is_null($result) ? $this->setError('数据不存在') : false;
        }

        if ($result->getAttr('status') !== 0 && $result->getAttr('status') !== 1) {
            return $this->setError('提现状态不可拒绝');
        }

        // 开启事务
        self::startTrans();

        try {
            // 修改主表
            if (false === $result->save(['status' => 4, 'remark' => $data['remark']])) {
                throw new \Exception($this->getError());
            }

            // 增加可用余额,并减少锁定余额
            $userMoneyDb = new UserMoney();
            $amount = $result->getAttr('amount');

            if (!$userMoneyDb->incBalanceAndDecLock($amount, $result->getAttr('user_id'))) {
                throw new \Exception($userMoneyDb->getError());
            }

            // 添加交易记录
            if (!$this->addTransaction(Transaction::TRANSACTION_INCOME, $amount, $result->getAttr('user_id'), $result->getAttr('withdraw_no'), '拒绝提现')) {
                throw new \Exception($this->getError());
            }

            self::commit();
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }
}