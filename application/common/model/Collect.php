<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    收藏夹模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/7/15
 */

namespace app\common\model;

class Collect extends CareyShop
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
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'user_id',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'collect_id',
        'user_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'collect_id' => 'integer',
        'user_id'    => 'integer',
        'goods_id'   => 'integer',
        'is_top'     => 'integer',
    ];

    /**
     * hasOne cs_goods
     * @access public
     * @return mixed
     */
    public function getGoods()
    {
        $field = [
            'goods_category_id', 'name', 'short_name', 'brand_id', 'market_price', 'shop_price',
            'store_qty', 'comment_sum', 'sales_sum', 'attachment', 'status', 'is_delete',
        ];

        return $this
            ->hasOne('Goods', 'goods_id', 'goods_id')
            ->field($field)
            ->setEagerlyType(0);
    }

    /**
     * 添加一个商品收藏
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function addCollectItem($data)
    {
        if (!$this->validateData($data, 'Collect')) {
            return false;
        }

        // 避免无关字段,并初始化部分数据
        unset($data['collect_id']);
        $data['user_id'] = get_client_id();

        if (0 == $data['user_id']) {
            return true;
        }

        $map['goods_id'] = ['eq', $data['goods_id']];
        $map['user_id'] = ['eq', $data['user_id']];
        if (self::checkUnique($map)) {
            return true;
        }

        if (false !== $this->allowField(true)->save($data)) {
            return true;
        }

        return false;
    }

    /**
     * 批量删除商品收藏
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delCollectList($data)
    {
        if (!$this->validateData($data, 'Collect.del')) {
            return false;
        }

        $map['collect_id'] = ['in', $data['collect_id']];
        $map['user_id'] = ['eq', get_client_id()];
        $this->where($map)->delete();

        return true;
    }

    /**
     * 清空商品收藏夹
     * @access public
     * @return bool
     */
    public function clearCollectList()
    {
        self::destroy(['user_id' => get_client_id()]);
        return true;
    }

    /**
     * 设置收藏商品是否置顶
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setCollectTop($data)
    {
        if (!$this->validateData($data, 'Collect.top')) {
            return false;
        }

        $map['user_id'] = ['eq', get_client_id()];
        $map['collect_id'] = ['in', $data['collect_id']];

        if (false !== $this->save(['is_top' => $data['is_top']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 获取商品收藏列表
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getCollectList($data)
    {
        if (!$this->validateData($data, 'Collect.list')) {
            return false;
        }

        // 搜索条件
        $map['collect.user_id'] = ['eq', get_client_id()];
        $totalResult = $this->with('getGoods')->where($map)->count();

        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($map, $data) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'collect_id';

            // 字段排序处理
            $order['collect.is_top'] = 'desc';
            $order['collect.' . $orderField] = $orderType;

            $query->with('getGoods')->where($map)->order($order)->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 获取商品收藏数量
     * @access public
     * @return array
     */
    public function getCollectCount()
    {
        // 搜索条件
        $map['collect.user_id'] = ['eq', get_client_id()];
        $totalResult = $this->with('getGoods')->where($map)->count();

        return ['total_result' => $totalResult];
    }
}