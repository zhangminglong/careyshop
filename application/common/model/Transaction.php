<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    交易结算模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/6/20
 */

namespace app\common\model;

class Transaction extends CareyShop
{
    /**
     * 收入
     * @var int
     */
    const TRANSACTION_INCOME = 0;

    /**
     * 支出
     * @var int
     */
    const TRANSACTION_EXPENDITURE = 1;

    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 更新时间字段
     * @var bool/string
     */
    protected $updateTime = false;

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'transaction_id' => 'integer',
        'user_id'        => 'integer',
        'type'           => 'integer',
        'amount'         => 'float',
        'balance'        => 'float',
        'to_payment'     => 'integer',
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
     * 添加一条交易结算
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addTransactionItem($data)
    {
        if (!$this->validateData($data, 'Transaction')) {
            return false;
        }

        if (!isset($data['user_id'])) {
            return $this->setError('交易结算对应账号编号必须填写');
        }

        // 避免无关字段及处理部分数据
        unset($data['transaction_id']);
        $this->setAttr('transaction_id', null);
        $data['action'] = get_client_name();

        if (false !== $this->isUpdate(false)->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取交易结算列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getTransactionList($data)
    {
        if (!$this->validateData($data, 'Transaction.list')) {
            return false;
        }

        // 搜索条件
        $map['transaction.user_id'] = ['eq', get_client_id()];
        !isset($data['type']) ?: $map['transaction.type'] = ['eq', $data['type']];
        empty($data['source_no']) ?: $map['transaction.source_no'] = ['eq', $data['source_no']];
        empty($data['module']) ?: $map['transaction.module'] = ['eq', $data['module']];
        empty($data['card_number']) ?: $map['transaction.card_number'] = ['eq', $data['card_number']];

        if (!empty($data['begin_time']) && !empty($data['end_time'])) {
            $map['transaction.create_time'] = ['between time', [$data['begin_time'], $data['end_time']]];
        }

        // 关联查询
        $with = [];

        // 后台管理搜索
        if (is_client_admin()) {
            $with = ['getUser'];
            unset($map['transaction.user_id']);
            empty($data['action']) ?: $map['transaction.action'] = ['eq', $data['action']];
            !isset($data['to_payment']) ?: $map['transaction.to_payment'] = ['eq', $data['to_payment']];
            empty($data['account']) ?: $map['getUser.username|getUser.nickname'] = ['eq', $data['account']];
        }

        // 获取总数量,为空直接返回
        $totalResult = $this->alias('transaction')->with($with)->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map, $with) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'transaction_id';

            $query
                ->alias('transaction')
                ->with($with)
                ->where($map)
                ->order(['transaction.' . $orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}