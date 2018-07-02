<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    优惠劵模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/18
 */

namespace app\common\model;

class Coupon extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'coupon_id',
        'give_code',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'coupon_id'        => 'integer',
        'type'             => 'integer',
        'money'            => 'float',
        'quota'            => 'float',
        'category'         => 'array',
        'exclude_category' => 'array',
        'level'            => 'array',
        'frequency'        => 'integer',
        'give_num'         => 'integer',
        'receive_num'      => 'integer',
        'use_num'          => 'integer',
        'give_begin_time'  => 'timestamp',
        'give_end_time'    => 'timestamp',
        'use_begin_time'   => 'timestamp',
        'use_end_time'     => 'timestamp',
        'status'           => 'integer',
        'is_invalid'       => 'integer',
        'is_delete'        => 'integer',
    ];

    /**
     * 新增自动完成列表
     * @var array
     */
    protected $insert = [
        'give_code',
    ];

    /**
     * 领取码自动完成
     * @access protected
     * @param  array $args 参数
     * @return string
     */
    protected function setGiveCodeAttr(...$args)
    {
        $value = '';
        if (isset($args[1]['type']) && 2 == $args[1]['type']) {
            do {
                $value = get_randstr(10);
            } while (self::checkUnique(['give_code' => ['eq', $value]]));
        }

        return $value;
    }

    /**
     * 添加一张优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addCouponItem($data)
    {
        if (!$this->validateData($data, 'Coupon')) {
            return false;
        }

        // 避免无关字段并初始化
        unset($data['coupon_id'], $data['receive_num'], $data['use_num']);
        !empty($data['category']) ?: $data['category'] = [];
        !empty($data['exclude_category']) ?: $data['exclude_category'] = [];
        !empty($data['level']) ?: $data['level'] = [];

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一张优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setCouponItem($data)
    {
        if (!$this->validateSetData($data, 'Coupon.set')) {
            return false;
        }

        // 避免不允许修改字段
        unset($data['type'], $data['give_code'], $data['receive_num'], $data['use_num']);

        // 处理数组字段
        if (isset($data['category']) && '' == $data['category']) {
            $data['category'] = [];
        }

        if (isset($data['exclude_category']) && '' == $data['exclude_category']) {
            $data['exclude_category'] = [];
        }

        if (isset($data['level']) && '' == $data['level']) {
            $data['level'] = [];
        }

        $map['coupon_id'] = ['eq', $data['coupon_id']];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一张优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getCouponItem($data)
    {
        if (!$this->validateData($data, 'Coupon.get')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['coupon_id'] = ['eq', $data['coupon_id']];
            $map['is_delete'] = ['eq', 0];

            $query->field('is_delete', true)->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取优惠劵列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getCouponList($data)
    {
        if (!$this->validateData($data, 'Coupon.list')) {
            return false;
        }

        // 搜索条件
        $map['is_delete'] = ['eq', 0];
        empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
        !isset($data['type']) ?: $map['type'] = ['eq', $data['type']];
        !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
        !isset($data['is_invalid']) ?: $map['is_invalid'] = ['eq', $data['is_invalid']];

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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'coupon_id';

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

    /**
     * 批量删除优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delCouponList($data)
    {
        if (!$this->validateData($data, 'Coupon.del')) {
            return false;
        }

        $map['coupon_id'] = ['in', $data['coupon_id']];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['is_delete' => 1], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 批量设置优惠劵状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCouponStatus($data)
    {
        if (!$this->validateData($data, 'Coupon.status')) {
            return false;
        }

        $map['coupon_id'] = ['in', $data['coupon_id']];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 批量设置优惠劵是否失效
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCouponInvalid($data)
    {
        if (!$this->validateData($data, 'Coupon.invalid')) {
            return false;
        }

        $map['coupon_id'] = ['in', $data['coupon_id']];
        $map['is_delete'] = ['eq', 0];

        if (false !== $this->save(['is_invalid' => $data['is_invalid']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 获取当前可领取的优惠劵列表
     * @access public
     * @return array
     * @throws
     */
    public function getCouponActive()
    {
        $map['type'] = ['eq', 2];
        $map['give_num'] = ['exp', $this->raw('> `receive_num`')];
        $map['give_begin_time'] = ['<= time', time()];
        $map['give_end_time'] = ['> time', time()];
        $map['status'] = ['eq', 1];
        $map['is_invalid'] = ['eq', 0];
        $map['is_delete'] = ['eq', 0];

        $result = self::all(function ($query) use ($map) {
            $query
                ->field('coupon_id,type,use_num,status,is_invalid,is_delete', true)
                ->where($map);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return [];
    }
}