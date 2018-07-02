<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品折扣模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/31
 */

namespace app\common\model;

class Discount extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'discount_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'discount_id' => 'integer',
        'type'        => 'integer',
        'begin_time'  => 'timestamp',
        'end_time'    => 'timestamp',
        'status'      => 'integer',
    ];

    /**
     * hasMany cs_discount_goods
     * @access public
     * @return mixed
     */
    public function discountGoods()
    {
        return $this->hasMany('DiscountGoods', 'discount_id');
    }

    /**
     * 检测相同时间段内是否存在重复商品
     * @access private
     * @param  string $beginTime 开始时间
     * @param  string $endTime   结束时间
     * @param  array  $goodsList 外部商品列表
     * @param  int    $excludeId 排除折扣Id
     * @return bool
     * @throws
     */
    private function isRepeatGoods($beginTime, $endTime, $goodsList, $excludeId = 0)
    {
        $map = [];
        $excludeId == 0 ?: $map['discount_id'] = ['neq', $excludeId];
        $map['begin_time'] = ['< time', $endTime];
        $map['end_time'] = ['> time', $beginTime];

        // 获取相同时间范围内的商品
        $result = self::all(function ($query) use ($map) {
            $query->with('discountGoods')->where($map);
        });

        if (false === $result) {
            return false;
        }

        foreach ($result as $value) {
            $discountGoods = $value->getAttr('discount_goods')->column('goods_id');
            $inGoods = array_intersect($discountGoods, $goodsList);

            if (!empty($inGoods)) {
                $error = '商品Id:' . implode(',', $inGoods) . ' 已在同时间段的"';
                $error .= $value->getAttr('name') . '(Id:' . $value->getAttr('discount_id') . ')"中存在';
                return $this->setError($error);
            }
        }

        return true;
    }

    /**
     * 添加一个商品折扣
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addDiscountItem($data)
    {
        if (!$this->validateData($data, 'Discount')) {
            return false;
        }

        // 避免无关字段
        unset($data['discount_id']);

        // 检测相同时间段内是否存在重复商品
        $goodsList = array_column($data['discount_goods'], 'goods_id');
        if (!$this->isRepeatGoods($data['begin_time'], $data['end_time'], $goodsList)) {
            return false;
        }

        // 开启事务
        self::startTrans();

        try {
            // 添加主表
            if (false === $this->allowField(true)->save($data)) {
                throw new \Exception($this->getError());
            }

            // 添加折扣商品
            $result = $this->toArray();
            $discountGoodsDb = new DiscountGoods();
            $result['discount_goods'] = $discountGoodsDb->addDiscountGoods($data['discount_goods'], $this->getAttr('discount_id'));

            if (false === $result['discount_goods']) {
                throw new \Exception($discountGoodsDb->getError());
            }

            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 编辑一个商品折扣
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setDiscountItem($data)
    {
        if (!$this->validateData($data, 'Discount.set')) {
            return false;
        }

        // 检测相同时间段内是否存在重复商品
        $goodsList = array_column($data['discount_goods'], 'goods_id');
        if (!$this->isRepeatGoods($data['begin_time'], $data['end_time'], $goodsList, $data['discount_id'])) {
            return false;
        }

        // 开启事务
        self::startTrans();

        try {
            // 修改主表
            $map['discount_id'] = ['eq', $data['discount_id']];
            if (false === $this->allowField(true)->save($data, $map)) {
                throw new \Exception($this->getError());
            }

            // 删除关联数据
            $discountGoodsDb = new DiscountGoods();
            if (false === $discountGoodsDb->where($map)->delete()) {
                throw new \Exception($discountGoodsDb->getError());
            }

            // 添加折扣商品
            $result = $this->toArray();
            $result['discount_goods'] = $discountGoodsDb->addDiscountGoods($data['discount_goods'], $data['discount_id']);

            if (false === $result['discount_goods']) {
                throw new \Exception($discountGoodsDb->getError());
            }

            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 获取一个商品折扣
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getDiscountItem($data)
    {
        if (!$this->validateData($data, 'Discount.item')) {
            return false;
        }

        $result = self::get($data['discount_id'], 'discountGoods');
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 批量删除商品折扣
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delDiscountList($data)
    {
        if (!$this->validateData($data, 'Discount.del')) {
            return false;
        }

        $result = self::all($data['discount_id']);
        if (!$result) {
            return true;
        }

        foreach ($result as $value) {
            $value->delete();
            $value->discountGoods()->delete();
        }

        return true;
    }

    /**
     * 批量设置商品折扣状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setDiscountStatus($data)
    {
        if (!$this->validateData($data, 'Discount.status')) {
            return false;
        }

        $map['discount_id'] = ['in', $data['discount_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 获取商品折扣列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getDiscountList($data)
    {
        if (!$this->validateData($data, 'Discount.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
        empty($data['type']) ?: $map['type'] = ['in', $data['type']];
        !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];
        empty($data['begin_time']) ?: $map['begin_time'] = ['< time', $data['end_time']];
        empty($data['end_time']) ?: $map['end_time'] = ['> time', $data['begin_time']];

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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'discount_id';

            $query
                ->with('discountGoods')
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