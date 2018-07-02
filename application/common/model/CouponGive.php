<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    优惠劵发放模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/20
 */

namespace app\common\model;

class CouponGive extends CareyShop
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
        'coupon_give_id',
        'coupon_id',
        'exchange_code',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'coupon_give_id' => 'integer',
        'coupon_id'      => 'integer',
        'user_id'        => 'integer',
        'order_id'       => 'integer',
        'use_time'       => 'timestamp',
        'is_delete'      => 'integer',
    ];

    /**
     * belongsTo cs_coupon
     * @access public
     * @return mixed
     */
    public function getCoupon()
    {
        return $this
            ->belongsTo('Coupon', 'coupon_id')
            ->field('is_delete', true)
            ->setEagerlyType(0);
    }

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
     * 使用优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function useCouponItem($data)
    {
        if (!$this->validateData($data, 'CouponGive.use')) {
            return false;
        }

        if (empty($data['coupon_give_id']) && empty($data['exchange_code'])) {
            return $this->setError('优惠劵发放编号或兑换码必须填选其中一个');
        }

        if (!empty($data['coupon_give_id']) && !empty($data['exchange_code'])) {
            return $this->setError('优惠劵发放编号或兑换码只能填选其中一个');
        }

        if (!empty($data['exchange_code'])) {
            $data['user_id'] = get_client_id();
            $map['exchange_code'] = ['eq', $data['exchange_code']];
        } else {
            $map['user_id'] = ['eq', get_client_id()];
            $map['coupon_give_id'] = ['eq', $data['coupon_give_id']];
        }

        $data['use_time'] = time();
        if (false === $this->allowField(['user_id', 'order_id', 'use_time'])->save($data, $map)) {
            return false;
        }

        $mapCoupon['coupon_id'] = ['eq', $this->where($map)->value('coupon_id', 0, true)];
        Coupon::where($mapCoupon)->setInc('use_num');

        return true;
    }

    /**
     * 发放优惠劵
     * @access public
     * @param  int   $couponId 优惠劵编号
     * @param  array $userId   发放用户(等同于发放数量)
     * @param  int   $type     优惠劵类型
     * @return false|object
     * @throws
     */
    private function addCouponGive($couponId, $userId, $type)
    {
        // 获取优惠劵信息
        $map['coupon_id'] = ['eq', $couponId];
        $map['status'] = ['eq', 1];
        $map['is_invalid'] = ['eq', 0];
        $map['is_delete'] = ['eq', 0];

        $couponResult = Coupon::where($map)->find();
        if (!$couponResult) {
            return is_null($couponResult) ? $this->setError('优惠劵已失效') : false;
        }

        if ($couponResult->getAttr('type') !== $type) {
            return $this->setError('优惠劵发放类型不对应');
        }

        if (2 === $type) {
            $frequency = $couponResult->getAttr('frequency');
            if ($frequency !== 0) {
                $mapUser['coupon_id'] = ['eq', $couponId];
                $mapUser['user_id'] = ['in', $userId];

                if ($this->where($mapUser)->count() >= $frequency) {
                    return $this->setError('每人最多只能领取 ' . $frequency . ' 张');
                }
            }

            if (time() < $couponResult->getData('give_begin_time')) {
                return $this->setError('优惠劵领取时间未到');
            }

            if (time() > $couponResult->getData('give_end_time')) {
                return $this->setError('优惠劵领取时间已结束');
            }
        }

        if ($couponResult->getAttr('receive_num') >= $couponResult->getAttr('give_num')) {
            return $this->setError('优惠劵已被领完');
        }

        $remaining = $couponResult->getAttr('give_num') - $couponResult->getAttr('receive_num');
        if (count($userId) > $remaining && $couponResult->getAttr('give_num') !== 0) {
            return $this->setError('可发放数量不足' . count($userId) . '张');
        }

        // 准备生成的数据
        $data = [];
        foreach ($userId as $value) {
            $data[] = [
                'coupon_id'     => $couponId,
                'user_id'       => $value,
                'exchange_code' => get_randstr(10),
                'create_time'   => time(),
            ];
        }

        // 开启事务
        self::startTrans();

        try {
            $result = $this->saveAll($data);
            if (false === $result) {
                throw new \Exception($this->getError());
            }

            unset($map);
            $map['coupon_id'] = ['eq', $couponId];

            if (!$couponResult->where($map)->setInc('receive_num', count($userId))) {
                throw new \Exception($couponResult->getError());
            }

            self::commit();
            return $result;
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 向指定用户发放优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function giveCouponUser($data)
    {
        if (!$this->validateData($data, 'CouponGive.user')) {
            return false;
        }

        if (empty($data['username']) && empty($data['user_level_id'])) {
            return $this->setError('账号或会员等级必须填选其中一个');
        }

        if (!empty($data['username']) && !empty($data['user_level_id'])) {
            return $this->setError('账号或会员等级只能填选其中一个');
        }

        // 获取账号资料
        $map = [];
        empty($data['username']) ?: $map['username'] = ['in', $data['username']];
        empty($data['user_level_id']) ?: $map['user_level_id'] = ['in', $data['user_level_id']];

        $userIdResult = User::where($map)->column('user_id');
        if (!$userIdResult) {
            return $this->setError('账号数据不存在');
        }

        if ($this->addCouponGive($data['coupon_id'], $userIdResult, 0)) {
            return true;
        }

        return false;
    }

    /**
     * 生成线下优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function giveCouponLive($data)
    {
        if (!$this->validateData($data, 'CouponGive.live')) {
            return false;
        }

        if ($this->addCouponGive($data['coupon_id'], array_fill(0, $data['give_number'], 0), 1)) {
            return true;
        }

        return false;
    }

    /**
     * 领取码领取优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function giveCouponCode($data)
    {
        if (!$this->validateData($data, 'CouponGive.code')) {
            return false;
        }

        $result = Coupon::where(['give_code' => ['eq', $data['give_code']]])->find();
        if (!$result) {
            return is_null($result) ? $this->setError('优惠劵领取码无效') : false;
        }

        if ($this->addCouponGive($result->getAttr('coupon_id'), [get_client_id()], 2)) {
            return true;
        }

        return false;
    }

    /**
     * 下单送优惠劵(非对外接口)
     * @access public
     * @param  int $couponId 优惠劵编号
     * @param  int $userId   发放账号Id
     * @return false|array
     */
    public function giveCouponOrder($couponId, $userId)
    {
        $result = $this->addCouponGive($couponId, [$userId], 3);
        if ($result) {
            return $result->column('coupon_give_id');
        }

        return false;
    }

    /**
     * 获取已领取优惠劵列表
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getCouponGiveList($data)
    {
        if (!$this->validateData($data, 'CouponGive.list')) {
            return false;
        }

        // 搜索条件
        $mapOr = [];
        $map['coupon_give.is_delete'] = ['eq', 0];

        if (is_client_admin()) {
            !isset($data['coupon_id']) ?: $map['coupon_give.coupon_id'] = ['eq', $data['coupon_id']];
            empty($data['account']) ?: $map['getUser.username|getUser.nickname'] = ['eq', $data['account']];
        } else {
            $map['coupon_give.user_id'] = ['eq', get_client_id()];
        }

        if (isset($data['type'])) {
            // 正常状态优惠劵
            if ($data['type'] == 'normal') {
                $map['coupon_give.use_time'] = ['eq', 0];
                $map['getCoupon.use_end_time'] = ['gt', time()];
                $map['getCoupon.is_invalid'] = ['eq', 0];
            }

            // 已使用优惠劵
            if ($data['type'] == 'used') {
                $map['coupon_give.use_time'] = ['neq', 0];
            }

            // 无效优惠劵
            if ($data['type'] == 'invalid') {
                $map['coupon_give.use_time'] = ['eq', 0];
                $mapOr['getCoupon.use_end_time'] = ['lt', time()];
                $mapOr['getCoupon.is_invalid'] = ['eq', 1];
            }

            // 回收站优惠劵
            if ($data['type'] == 'delete') {
                $map['coupon_give.delete'] = ['eq', 1];
            }
        }

        // 关联查询
        $with = ['getCoupon'];
        !is_client_admin() ?: $with[] = 'getUser';

        $totalResult = $this->with($with)->where($map)->where(function ($query) use ($mapOr) {
            $query->whereOr($mapOr);
        })->count();

        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $with, $map, $mapOr) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            $query
                ->field('is_delete', true)
                ->with($with)
                ->where($map)
                ->where(function ($query) use ($mapOr) {
                    $query->whereOr($mapOr);
                })
                ->order(['coupon_give.coupon_give_id' => 'desc'])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 批量删除已领取优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delCouponGiveList($data)
    {
        if (!$this->validateData($data, 'CouponGive.del')) {
            return false;
        }

        // 搜索条件
        $map['coupon_give_id'] = ['in', $data['coupon_give_id']];

        if (is_client_admin()) {
            // 未使用的进行物理删除
            $map['use_time'] = ['eq', 0];
            self::where($map)->delete();

            // 已使用的放入回收站
            $map['use_time'] = ['neq', 0];
            if (false !== $this->save(['is_delete' => 1], $map)) {
                return true;
            }
        } else {
            $map['user_id'] = ['eq', get_client_id()];
            if (false !== $this->save(['is_delete' => 1], $map)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 批量恢复已删优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function recCouponGiveList($data)
    {
        if (!$this->validateData($data, 'CouponGive.del')) {
            return false;
        }

        $map['coupon_give_id'] = ['in', $data['coupon_give_id']];
        is_client_admin() ?: $map['user_id'] = ['eq', get_client_id()];

        if (false !== $this->save(['is_delete' => 0], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 导出线下生成的优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getCouponGiveExport($data)
    {
        if (!$this->validateData($data, 'CouponGive.export')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            $query
                ->field('coupon_id,user_id,order_id', true)
                ->where(['coupon_id' => ['eq', $data['coupon_id']]]);
        });

        if ($result !== false) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 根据商品Id列出可使用的优惠劵
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getCouponGiveSelect($data)
    {
        if (!$this->validateData($data, 'CouponGive.select')) {
            return false;
        }

        // 获取未使用的优惠劵
        $couponResult = self::all(function ($query) use ($data) {
            $map['coupon_give.user_id'] = ['eq', get_client_id()];
            $map['coupon_give.use_time'] = ['eq', 0];
            $map['coupon_give.is_delete'] = ['eq', 0];

            $query->with('getCoupon')->where($map)->order(['getCoupon.money' => 'desc']);
        });

        if ($couponResult->isEmpty()) {
            return [];
        }

        // 获取订单商品分类并进行筛选
        $result = [];
        $goodsResult = Goods::where(['goods_id' => ['in', $data['goods_id']]])->column('goods_category_id');

        // 优惠劵发放服务层实例化
        $giveSer = new \app\common\service\CouponGive();

        foreach ($couponResult as $value) {
            $temp = $value->hidden(['is_delete'])->toArray();
            $temp['is_use'] = (int)$giveSer->checkCoupon($temp, $goodsResult, $data['pay_amount']);
            $temp['not_use_error'] = 0 == $temp['is_use'] ? $giveSer->getError() : '';

            $result[] = $temp;
            unset($temp);
        }

        return $result;
    }

    /**
     * 验证优惠劵是否可使用
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getCouponGiveCheck($data)
    {
        if (!$this->validateData($data, 'CouponGive.check')) {
            return false;
        }

        if (empty($data['coupon_give_id']) && empty($data['exchange_code'])) {
            return $this->setError('优惠劵发放编号或兑换码必须填选其中一个');
        }

        if (!empty($data['coupon_give_id']) && !empty($data['exchange_code'])) {
            return $this->setError('优惠劵发放编号或兑换码只能填选其中一个');
        }

        // 获取优惠劵数据
        $map['coupon_give.use_time'] = ['eq', 0];
        $map['coupon_give.is_delete'] = ['eq', 0];

        if (!empty($data['exchange_code'])) {
            $map['coupon_give.exchange_code'] = ['eq', $data['exchange_code']];
        } else {
            $map['coupon_give.user_id'] = ['eq', get_client_id()];
            $map['coupon_give.coupon_give_id'] = ['eq', $data['coupon_give_id']];
        }

        // 获取未使用的优惠劵
        $couponResult = self::get(function ($query) use ($map) {
            $query->with('getCoupon')->where($map);
        });

        if (!$couponResult) {
            return is_null($couponResult) ? $this->setError('优惠劵不存在') : false;
        }

        // 获取订单商品分类并进行筛选
        $result = $couponResult->hidden(['is_delete'])->toArray();
        $goodsResult = Goods::where(['goods_id' => ['in', $data['goods_id']]])->column('goods_category_id');

        $giveSer = new \app\common\service\CouponGive();
        if (!$giveSer->checkCoupon($result, $goodsResult, $data['pay_amount'])) {
            return $this->setError($giveSer->getError());
        }

        return $result;
    }
}