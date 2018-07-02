<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    购物卡模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/11/20
 */

namespace app\common\model;

class Card extends CareyShop
{
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
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'card_id',
        'money',
        'give_num',
        'create_time',
        'end_time',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'card_id'          => 'integer',
        'money'            => 'float',
        'category'         => 'array',
        'exclude_category' => 'array',
        'give_num'         => 'integer',
        'active_num'       => 'integer',
        'end_time'         => 'timestamp',
        'status'           => 'integer',
        'is_delete'        => 'integer',
    ];

    /**
     * hasMany cs_card_use
     * @access public
     * @return mixed
     */
    public function getCardUse()
    {
        return $this->hasMany('CardUse', 'card_id');
    }

    /**
     * 添加一条购物卡
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addCardItem($data)
    {
        if (!$this->validateData($data, 'Card')) {
            return false;
        }

        // 避免无关字段并初始化
        unset($data['card_id'], $data['active_num']);
        !empty($data['category']) ?: $data['category'] = [];
        !empty($data['exclude_category']) ?: $data['exclude_category'] = [];

        // 开启事务
        self::startTrans();

        try {
            // 添加购物卡
            if (false === $this->allowField(true)->save($data)) {
                throw new \Exception($this->getError());
            }

            // 准备购物卡使用数据
            $useData = [];
            for ($i = 0; $i < $data['give_num']; $i++) {
                $useData[] = [
                    'card_id'  => $this->getAttr('card_id'),
                    'number'   => rand_number(16),
                    'password' => rand_string(16, false),
                    'money'    => $data['money'],
                ];
            }

            // 添加购物卡使用集合
            if (!$this->getCardUse()->insertAll($useData)) {
                throw new \Exception($this->getError());
            }

            self::commit();
            return $this->toArray();
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 编辑一条购物卡
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function setCardItem($data)
    {
        if (!$this->validateSetData($data, 'Card.set')) {
            return false;
        }

        // 避免不允许修改字段
        unset($data['active_num'], $data['is_delete']);

        // 数组字段特殊处理
        if (isset($data['category']) && '' == $data['category']) {
            $data['category'] = [];
        }

        if (isset($data['exclude_category']) && '' == $data['exclude_category']) {
            $data['exclude_category'] = [];
        }

        $map['card_id'] = ['eq', $data['card_id']];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一条购物卡
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getCardItem($data)
    {
        if (!$this->validateData($data, 'Card.get')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['card_id'] = ['eq', $data['card_id']];
            $map['is_delete'] = ['eq', 0];

            $query->field('is_delete', true)->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 批量设置购物卡状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCardStatus($data)
    {
        if (!$this->validateData($data, 'Card.status')) {
            return false;
        }

        $map['card_id'] = ['in', $data['card_id']];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 批量删除购物卡
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delCardList($data)
    {
        if (!$this->validateData($data, 'Card.del')) {
            return false;
        }

        $map['card_id'] = ['in', $data['card_id']];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['is_delete' => 1], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 获取购物卡列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getCardList($data)
    {
        if (!$this->validateData($data, 'Card.list')) {
            return false;
        }

        // 搜索条件
        $map['is_delete'] = ['eq', 0];
        empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'card_id';

            $query
                ->field('is_delete', true)
                ->where($map)
                ->order([$orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}