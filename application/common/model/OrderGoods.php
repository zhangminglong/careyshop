<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    订单商品模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/8/9
 */

namespace app\common\model;

class OrderGoods extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'order_goods_id',
        'order_id',
        'order_no',
        'user_id',
        'key_name',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'order_goods_id' => 'integer',
        'order_id'       => 'integer',
        'goods_id'       => 'integer',
        'user_id'        => 'integer',
        'market_price'   => 'float',
        'shop_price'     => 'float',
        'qty'            => 'integer',
        'is_comment'     => 'integer',
        'is_service'     => 'integer',
        'status'         => 'integer',
    ];

    /**
     * belongsTo cs_order
     * @access public
     * @return mixed
     */
    public function toOrder()
    {
        return $this->belongsTo('Order', 'order_id');
    }

    /**
     * belongsTo cs_order
     * @access public
     * @return mixed
     */
    public function getOrder()
    {
        return $this->belongsTo('Order', 'order_id')->setEagerlyType(0);
    }

    /**
     * hasMany cs_order_goods
     * @access public
     * @return mixed
     */
    public function getOrderGoods()
    {
        return $this->hasMany('OrderGoods', 'order_id', 'order_id');
    }

    /**
     * 获取指定商品编号已购买的数量
     * @access public
     * @param  int $goodsId 商品编号
     * @return int
     */
    public static function getBoughtGoods($goodsId)
    {
        // 搜索条件
        $map['g.user_id'] = ['eq', get_client_id()];
        $map['g.goods_id'] = ['eq', $goodsId];
        $map['g.status'] = ['neq', 3];
        $map['o.trade_status'] = ['neq', 4];

        $result = self::alias('g')->join('order o', 'o.order_id = g.order_id')->where($map)->sum('g.qty');
        return $result;
    }

    /**
     * 判断订单商品是否允许评价
     * @access public
     * @param  string $orderNo      订单号
     * @param  int    $orderGoodsId 订单商品编号
     * @return bool
     * @throws
     */
    public function isComment($orderNo, $orderGoodsId)
    {
        // 搜索条件
        $map['order_goods_id'] = ['eq', $orderGoodsId];
        $map['order_no'] = ['eq', $orderNo];
        $map['user_id'] = ['eq', get_client_id()];

        $result = self::get(function ($query) use ($map) {
            // 获取关联订单数据
            $with['toOrder'] = function ($orderDb) {
                $orderDb->field('order_id,trade_status')->where(['is_delete' => ['eq', 0]]);
            };

            $query->with($with)->field('order_id,is_comment,status')->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('订单或订单商品不存在') : false;
        }

        if ($result->getAttr('is_comment') === 3) {
            return $this->setError('该订单商品不可评价');
        }

        if ($result->getAttr('is_comment') !== 0) {
            return $this->setError('该订单商品已评价');
        }

        if ($result->getAttr('status') !== 2 || $result->getAttr('to_order')->getAttr('trade_status') !== 3) {
            return $this->setError('该订单商品状态不允许评价');
        }

        return true;
    }

    /**
     * 获取一个订单商品明细
     * @access public
     * @param  array $data          外部数据
     * @param  bool  $returnArray   是否以数组的形式返回
     * @param  bool  $hasOrderGoods 是否关联订单数据
     * @return false|array|object
     * @throws
     */
    public function getOrderGoodsItem($data, $returnArray = true, $hasOrderGoods = false)
    {
        if (!$this->validateData($data, 'Order.goods_item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data, $hasOrderGoods) {
            $map['order_goods.order_goods_id'] = ['eq', $data['order_goods_id']];
            is_client_admin() ?: $map['order_goods.user_id'] = ['eq', get_client_id()];

            if ($hasOrderGoods) {
                $with['getOrder'] = function ($orderDb) {
                    $orderDb->where(['getOrder.is_delete' => ['neq', 2]]);
                };

                $query->with($with);
            }

            $query->alias('order_goods')->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('订单商品不存在') : false;
        }

        // 隐藏不需要输出的字段
        $hidden = [
            'order_id',
            'get_order.order_id', 'get_order.order_no', 'get_order.user_id',
        ];

        return $returnArray ? $result->hidden($hidden)->toArray() : $result;
    }
}