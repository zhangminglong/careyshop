<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    订单促销模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/31
 */

namespace app\common\model;

class Promotion extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'promotion_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'promotion_id' => 'integer',
        'begin_time'   => 'timestamp',
        'end_time'     => 'timestamp',
        'status'       => 'integer',
    ];

    /**
     * hasMany cs_promotion_item
     * @access public
     * @return mixed
     */
    public function promotionItem()
    {
        return $this->hasMany('PromotionItem', 'promotion_id');
    }

    /**
     * 检测相同时间段内是否存在重复促销
     * @access private
     * @param  string $beginTime 开始时间
     * @param  string $endTime   结束时间
     * @param  int    $excludeId 排除折扣Id
     * @return bool
     * @throws
     */
    private function isRepeatPromotion($beginTime, $endTime, $excludeId = 0)
    {
        $map = [];
        $excludeId == 0 ?: $map['promotion_id'] = ['neq', $excludeId];
        $map['begin_time'] = ['< time', $endTime];
        $map['end_time'] = ['> time', $beginTime];

        // 获取相同时间范围内的促销
        $result = $this->where($map)->find();
        if (false === $result) {
            return false;
        }

        if ($result) {
            $error = sprintf('该时间段内已存在"%s(Id:%d)"', $result->getAttr('name'), $result->getAttr('promotion_id'));
            return $this->setError($error);
        }

        return true;
    }

    /**
     * 添加一个订单促销
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addPromotionItem($data)
    {
        if (!$this->validateData($data, 'Promotion')) {
            return false;
        }

        // 避免无关及处理部分数据
        unset($data['promotion_id']);

        if (!$this->isRepeatPromotion($data['begin_time'], $data['end_time'])) {
            return false;
        }

        // 开启事务
        self::startTrans();

        try {
            // 添加主表
            if (false === $this->allowField(true)->save($data)) {
                throw new \Exception($this->getError());
            }

            // 保留主表数据
            $result = $this->toArray();

            // 添加促销方式配置项
            $promotionItemDb = new PromotionItem();
            $result['promotion_item'] = $promotionItemDb->addPromotionItem($data['promotion_item'], $this->getAttr('promotion_id'));

            if (false === $result['promotion_item']) {
                throw new \Exception($promotionItemDb->getError());
            }

            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 编辑一个订单促销
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setPromotionItem($data)
    {
        if (!$this->validateSetData($data, 'Promotion.set')) {
            return false;
        }

        if (isset($data['begin_time']) || isset($data['end_time'])) {
            if (!$this->isRepeatPromotion($data['begin_time'], $data['end_time'], $data['promotion_id'])) {
                return false;
            }
        }

        // 开启事务
        self::startTrans();

        try {
            // 修改主表
            $map['promotion_id'] = ['eq', $data['promotion_id']];
            if (false === $this->allowField(true)->save($data, $map)) {
                throw new \Exception($this->getError());
            }

            // 获取主表数据
            $result = $this->toArray();

            if (!empty($data['promotion_item'])) {
                // 删除关联数据
                $promotionItemDb = new PromotionItem();
                if (false === $promotionItemDb->where($map)->delete()) {
                    throw new \Exception($promotionItemDb->getError());
                }

                // 添加促销方式配置项
                $result['promotion_item'] = $promotionItemDb->addPromotionItem($data['promotion_item'], $data['promotion_id']);
                if (false === $result['promotion_item']) {
                    throw new \Exception($promotionItemDb->getError());
                }
            }

            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 获取一个订单促销
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getPromotionItem($data)
    {
        if (!$this->validateData($data, 'Promotion.item')) {
            return false;
        }

        $result = self::get($data['promotion_id'], 'promotionItem');
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 批量设置订单促销状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setPromotionStatus($data)
    {
        if (!$this->validateData($data, 'Promotion.status')) {
            return false;
        }

        $map['promotion_id'] = ['in', $data['promotion_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 批量删除订单促销
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function delPromotionList($data)
    {
        if (!$this->validateData($data, 'Promotion.del')) {
            return false;
        }

        $result = self::all($data['promotion_id']);
        if (!$result) {
            return true;
        }

        foreach ($result as $value) {
            $value->delete();
            $value->promotionItem()->delete();
        }

        return true;
    }

    /**
     * 获取订单促销列表
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getPromotionList($data)
    {
        if (!$this->validateData($data, 'Promotion.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'promotion_id';

            $query
                ->with('promotionItem')
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
     * 获取正在进行的促销列表
     * @access public
     * @return false|array
     * @throws
     */
    public function getPromotionActive()
    {
        // 同一个时段内只允许存在一个促销,所以返回get就可以了
        $result = self::get(function ($query) {
            $with['promotionItem'] = function ($query) {
                $query->order(['quota' => 'desc']);
            };

            $map['begin_time'] = ['elt', time()];
            $map['end_time'] = ['egt', time()];
            $map['status'] = ['eq', 1];

            $query->with($with)->field('status', true)->where($map);
        });

        if (false !== $result) {
            return is_null($result) ? [] : $result->toArray();
        }

        return [];
    }
}