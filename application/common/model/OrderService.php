<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    售后服务模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/10/10
 */

namespace app\common\model;

use think\Config;

class OrderService extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'order_service_id',
        'order_no',
        'order_goods_id',
        'key_name',
        'key_value',
        'user_id',
        'qty',
        'type',
        'goods_status',
        'refund_fee',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'order_service_id' => 'integer',
        'order_goods_id'   => 'integer',
        'user_id'          => 'integer',
        'admin_id'         => 'integer',
        'qty'              => 'integer',
        'type'             => 'integer',
        'goods_status'     => 'integer',
        'image'            => 'array',
        'status'           => 'integer',
        'is_return'        => 'integer',
        'refund_fee'       => 'float',
        'refund_detail'    => 'array',
        'delivery_fee'     => 'float',
        'admin_new'        => 'integer',
        'user_new'         => 'integer',
    ];

    /**
     * belongsTo cs_order
     * @access public
     * @return mixed
     */
    public function getOrder()
    {
        return $this
            ->belongsTo('Order', 'order_no', 'order_no')
            ->field('order_no,trade_status,delivery_status,payment_status,finished_time')
            ->setEagerlyType(0);
    }

    /**
     * belongsTo cs_order_goods
     * @access public
     * @return mixed
     */
    public function getOrderGoods()
    {
        return $this
            ->belongsTo('OrderGoods')
            ->field('order_goods_id,goods_name,goods_id,goods_image,key_value,qty,is_service,status')
            ->setEagerlyType(0);
    }

    /**
     * belongsTo cs_order_refund
     * @access public
     * @return mixed
     */
    public function getOrderRefund()
    {
        return $this
            ->belongsTo('OrderRefund', 'refund_no', 'refund_no', [], 'left')
            ->field('order_refund_id,refund_no,status')
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
            ->field('user_id,username,nickname,head_pic');
    }

    /**
     * hasOne cs_admin
     * @access public
     * @return mixed
     */
    public function getAdmin()
    {
        return $this
            ->hasOne('Admin', 'admin_id', 'admin_id', [], 'left')
            ->field('admin_id,username,nickname,head_pic');
    }

    /**
     * hasMany cs_service_log
     * @access public
     * @return mixed
     */
    public function getServiceLog()
    {
        return $this
            ->hasMany('ServiceLog')
            ->order(['service_log_id' => 'desc']);
    }

    /**
     * 生成唯一售后单号
     * @access private
     * @return string
     */
    private function getServiceNo()
    {
        do {
            $serviceNo = get_order_no('SH_');
        } while (self::checkUnique(['service_no' => ['eq', $serviceNo]]));

        return $serviceNo;
    }

    /**
     * 添加售后服务日志
     * @access public
     * @param  array  $serviceData 售后单数据
     * @param  string $comment     备注
     * @param  string $desc        描述
     * @return bool
     */
    public function addServiceLog($serviceData, $comment, $desc)
    {
        $data = [
            'order_service_id' => $serviceData['order_service_id'],
            'service_no'       => $serviceData['service_no'],
            'comment'          => $comment,
            'description'      => $desc,
        ];

        $is_new = is_client_admin() ? 'user_new' : 'admin_new';
        $this->isUpdate(true)->save([$is_new => 1], ['service_no' => ['eq', $data['service_no']]]);

        $serviceDb = new ServiceLog();
        if (!$serviceDb->addServiceLogItem($data)) {
            return $this->setError($serviceDb->getError());
        }

        return true;
    }

    /**
     * 根据订单号撤销符合条件的售后单(内部调用)
     * @access public
     * @param  string $orderNo 订单号
     * @param  string $type    撤销类型
     * @return bool
     * @throws
     */
    public function inCancelOrderService($orderNo, $type)
    {
        $result = self::all(function ($query) use ($orderNo) {
            $map['order_service.order_no'] = ['eq', $orderNo];
            $map['order_service.status'] = ['not in', '2,5,6'];

            // 过滤不需要的字段
            $field = [
                'goods_image', 'key_name', 'key_value', 'reason', 'description',
                'image', 'address', 'consignee', 'zipcode', 'mobile', 'logistic_code',
            ];

            $query->field($field, true)->with('getOrderGoods,getOrderRefund')->where($map);
        });

        if (false === $result || !in_array($type, ['delivery', 'complete'])) {
            return false;
        }

        // 无处理数据直接返回
        if ($result->isEmpty()) {
            return true;
        }

        // 准备初始化数据
        $logData = [];
        $comment = $type == 'delivery' ? '由于商品已发货' : '由于您已确认收货';

        foreach ($result as $value) {
            // 修改售后服务单
            if (false === $value->isUpdate(true)->save(['status' => 5, 'result' => '撤销申请'])) {
                return $this->setError($value->getError());
            }

            // 修改订单商品售后状态
            $goodsDb = $value->getAttr('get_order_goods');
            if ($goodsDb->getAttr('is_service') === 1) {
                if (false === $goodsDb->isUpdate(true)->save(['is_service' => 0])) {
                    return $this->setError($goodsDb->getError());
                }
            }

            // 修改退款申请状态
            if (!empty($value->getAttr('refund_no'))) {
                $refundDb = $value->getAttr('get_order_refund');
                if ($refundDb->getAttr('status') === 0) {
                    $refundData = ['status' => 3, 'out_trade_msg' => $comment . '，本次退款申请撤销。'];
                    if (false === $refundDb->isUpdate(true)->save($refundData)) {
                        return $this->setError($refundDb->getError());
                    }
                }
            }

            $logData[] = [
                'order_service_id' => $value->getAttr('order_service_id'),
                'service_no'       => $value->getAttr('service_no'),
                'action'           => get_client_name(),
                'client_type'      => get_client_type(),
                'comment'          => $comment . '，本次售后服务申请撤销。',
                'description'      => '撤销申请',
            ];
        }

        // 写入操作日志
        $serviceLogDb = new ServiceLog();
        if (false === $serviceLogDb->saveAll($logData)) {
            return $this->setError($serviceLogDb->getError());
        }

        return true;
    }

    /**
     * 获取订单商品可申请的售后服务
     * @access public
     * @param  array  $data         外部数据
     * @param  object $orderGoodsDb 订单商品模型对象
     * @param  bool   $isRefundFee  是否返回退款结构
     * @return false|array
     * @throws
     */
    public function getOrderServiceGoods($data, &$orderGoodsDb = null, $isRefundFee = false)
    {
        if (!$this->validateData($data, 'OrderService')) {
            return false;
        }

        // 初始化可申请的售后服务 0=否 1=是 $refund=可退金额
        $refund = [
            'refund_fee'    => 0,           // 最大可退金额
            'refund_detail' => [],          // 退款明细
            'delivery_fee'  => 0,           // 包含运费
        ];

        $service = [
            'is_refund'         => 0,       // 是否可申请退款
            'is_refund_refunds' => 0,       // 是否可申请退款退货
            'is_exchange'       => 0,       // 是否可申请换货
            'is_maintain'       => 0,       // 是否可申请维修
            'order_goods'       => $refund, // 订单商品数据
        ];

        // 订单商品是否存在正在进行的售后单
        $result = self::get(function ($query) use ($data) {
            $map['order_goods_id'] = ['eq', $data['order_goods_id']];
            $map['user_id'] = ['eq', get_client_id()];
            $map['status'] = ['in', '0,1,3,4'];

            $query->field('service_no')->where($map);
        });

        if (false === $result) {
            return false;
        }

        // 订单商品存在售后单则返回单号
        if (!is_null($result)) {
            return ['service_no' => $result->getAttr('service_no')];
        }

        // 获取订单商品基础数据(订单、订单商品)
        $orderGoodsDb = (new OrderGoods())->getOrderGoodsItem($data, false, true);
        if (!$orderGoodsDb || !is_object($orderGoodsDb)) {
            return !is_null($orderGoodsDb) ? $this->setError('数据获取异常') : $service;
        }

        // 获取订单商品需要的字段
        $visible = ['order_no', 'goods_name', 'goods_id', 'goods_image', 'key_name', 'key_value', 'qty'];
        $service['order_goods'] = array_merge($orderGoodsDb->visible($visible)->toArray(), $refund);

        // 检测订单商品是否允许申请售后(可申请、已售后)
        if (!in_array($orderGoodsDb->getAttr('is_service'), [0, 2])) {
            return $service;
        }

        // 检测订单商品状态是否允许申请售后(已发、已收)
        if (!in_array($orderGoodsDb->getAttr('status'), [1, 2])) {
            return $service;
        }

        // 此"get_order"来自"OrderGoods",因此不受字段限制
        $orderDb = $orderGoodsDb->getAttr('get_order');
        switch ($orderDb->getAttr('trade_status')) {
            case 2: // 处理已发货,未确认收货
                $service['is_refund'] = 1;
                $service['is_refund_refunds'] = 1;
                break;
            case 3: // 处理已发货,已确认收货
                $service['is_maintain'] = 1;
                $finishedTime = $orderDb->getData('finished_time') + Config::get('days.value', 'service') * 86400;
                if ($finishedTime >= time()) {
                    $service['is_refund'] = 1;
                    $service['is_refund_refunds'] = 1;
                    $service['is_exchange'] = 1;
                }
                break;
        }

        // 获取订单商品最大可退金额
        if ($service['is_refund'] === 1 || $service['is_refund_refunds'] === 1) {
            if (!empty($data['is_refund_fee']) || $isRefundFee) {
                $isDelivery = false;
                $service['order_goods']['refund_detail'] = $this->getMaxRefundFee((object)$orderGoodsDb, $isDelivery);
                $service['order_goods']['refund_fee'] = round(array_sum($service['order_goods']['refund_detail']), 2);
                !$isDelivery ?: $service['order_goods']['delivery_fee'] = $orderDb->getAttr('delivery_fee');
            }
        }

        return $service;
    }

    /**
     * 客服对售后服务单添加备注(顾客不可见)
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setOrderServiceRemark($data)
    {
        if (!$this->validateData($data, 'OrderService.remark')) {
            return false;
        }

        $map['service_no'] = ['eq', $data['service_no']];
        if (false !== $this->where($map)->setField('remark', $data['remark'])) {
            return true;
        }

        return false;
    }

    /**
     * 获取一个售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getOrderServiceItem($data)
    {
        if (!$this->validateData($data, 'OrderService.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['service_no'] = ['eq', $data['service_no']];

            if (!is_client_admin()) {
                $map['user_id'] = ['eq', get_client_id()];
                $query->field('admin_id,remark,admin_new', true);
            }

            $query->with('getUser,getServiceLog')->where($map);
        });

        if (false !== $result && !is_null($result)) {
            // 隐藏不需要输出的字段
            $hidden = [
                'order_service_id',
                'get_service_log.service_log_id',
                'get_service_log.order_service_id',
                'get_service_log.service_no',
                'get_user.user_id',
            ];

            $result->isUpdate(true)->save([is_client_admin() ? 'admin_new' : 'user_new' => 0]);
            return $result->hidden($hidden)->toArray();
        }

        return is_null($result) ? null : false;
    }

    /**
     * 获取售后服务单列表
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getOrderServiceList($data)
    {
        if (!$this->validateData($data, 'OrderService.list')) {
            return false;
        }

        $map = [];
        is_client_admin() ?: $map['user_id'] = ['eq', get_client_id()];
        empty($data['service_no']) ?: $map['service_no'] = ['eq', $data['service_no']];
        empty($data['order_no']) ?: $map['order_no'] = ['eq', $data['order_no']];
        !isset($data['type']) ?: $map['type'] = ['eq', $data['type']];
        !isset($data['status']) ?: $map['status'] = ['eq', $data['status']];

        if (!empty($data['begin_time']) && !empty($data['end_time'])) {
            $map['create_time'] = ['between time', [$data['begin_time'], $data['end_time']]];
        }

        if (is_client_admin()) {
            if (!empty($data['account'])) {
                $mapUser['username|nickname'] = ['eq', $data['account']];
                $userId = User::where($mapUser)->value('user_id', 0, true);
                $map['user_id'] = ['eq', $userId];
            }

            if (!empty($data['my_service'])) {
                $map['admin_id'] = ['eq', get_client_id()];
            }
        }

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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'order_service_id';

            // 关联查询
            $with = [];

            // 过滤字段
            $field = [
                'description', 'image', 'is_return', 'result', 'refund_detail',
                'refund_no', 'address', 'consignee', 'zipcode', 'mobile', 'logistic_code',
            ];

            if (!is_client_admin()) {
                array_push($field, 'admin_id', 'remark', 'admin_new');
            } else {
                $with = ['getUser', 'getAdmin'];
            }

            $query
                ->with($with)
                ->field($field, true)
                ->where($map)
                ->order([$orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            // 隐藏不需要输出的字段
            $hidden = [
                'order_service_id',
                'get_user.user_id', 'get_admin.admin_id',
            ];

            return ['items' => $result->hidden($hidden)->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 检测售后服务单数量是否已大于等于订单商品
     * @access private
     * @param  string $orderNo 订单号
     * @return bool
     */
    private function isServiceEgtOrderGoods($orderNo)
    {
        $map['order_no'] = ['eq', $orderNo];
        $map['type'] = ['in', '0,1'];
        $map['status'] = ['not in', '2,5']; // 不包括"已拒绝","已撤销"

        $serviceCount = $this->where($map)->group('order_goods_id')->count();
        $goodsCount = OrderGoods::where(['order_no' => ['eq', $orderNo]])->count();

        return $serviceCount + 1 >= $goodsCount;
    }

    /**
     * 获取订单商品最大可退金额
     * @access private
     * @param  object $orderGoodsDb 订单商品模型对象
     * @param  bool   &$isDelivery  是否退回运费
     * @return array
     */
    private function getMaxRefundFee($orderGoodsDb, &$isDelivery)
    {
        $data = [
            'money_amount'    => 0, // 余额
            'integral_amount' => 0, // 积分
            'card_amount'     => 0, // 购物卡
            'payment_amount'  => 0, // 支付
        ];

        if (!isset($orderGoodsDb) || !is_object($orderGoodsDb)) {
            return $data;
        }

        // 获取订单模型对象(此"get_order"来自"OrderGoods",因此不受字段限制)
        $orderDb = $orderGoodsDb->getAttr('get_order');

        // 获取各项实付金额
        $data['money_amount'] = $orderDb->getAttr('use_money');
        $data['integral_amount'] = $orderDb->getAttr('use_integral');
        $data['card_amount'] = $orderDb->getAttr('use_card');
        $data['payment_amount'] = PaymentLog::getPaymentLogValue($orderDb->getAttr('payment_no'));

        // 存在运费时,非最后订单商品一律按比分比扣除运费
        $totalAmount = array_sum($data);
        $deliveryFee = $orderDb->getAttr('delivery_fee');

        if ($deliveryFee > 0) {
            $isDelivery = $this->isServiceEgtOrderGoods($orderDb->getAttr('order_no'));
            foreach ($data as $key => $value) {
                $data[$key] -= ($deliveryFee / $totalAmount) * $value;
            }

            unset($key, $value);
        }

        // 计算订单商品可退百分比
        $orderScale = $orderGoodsDb->getAttr('shop_price') * $orderGoodsDb->getAttr('qty');
        $orderScale /= $orderDb->getAttr('goods_amount');

        // 计算实际可退金额
        $tempData = $data;
        $totalAmount = array_sum($tempData);

        foreach ($data as $key => $value) {
            $data[$key] = $value * $orderScale;
            !$isDelivery ?: $data[$key] += ($tempData[$key] / $totalAmount) * $deliveryFee;
            $data[$key] = round($data[$key], 2);
        }

        return $data;
    }

    /**
     * 添加一个维修或换货售后服务单
     * @access private
     * @param  array  $data 外部数据
     * @param  string $type 售后类型 maintain或exchange
     * @return false|array
     * @throws
     */
    private function addMaintainOfExchange(&$data, $type)
    {
        if (!$this->validateData($data, 'OrderService.maintain')) {
            return false;
        }

        if ($type !== 'maintain' && $type !== 'exchange') {
            return $this->setError('售后类型只能为 maintain 或 exchange');
        }

        $orderGoodsDb = null;
        $result = $this->getOrderServiceGoods($data, $orderGoodsDb);

        if (false === $result) {
            return false;
        }

        if (isset($result['service_no'])) {
            return $this->setError('订单商品存在尚未完成的售后服务');
        }

        if ($result['is_' . $type] !== 1) {
            return $this->setError('订单商品不满足申请该服务的条件');
        }

        if ($data['qty'] > $result['order_goods']['qty']) {
            return $this->setError('最大允许申请数量为 ' . $result['order_goods']['qty']);
        }

        // 售后单入库数据准备
        $serviceData = [
            'service_no'     => $this->getServiceNo(),
            'order_no'       => $result['order_goods']['order_no'],
            'order_goods_id' => $data['order_goods_id'],
            'goods_image'    => $result['order_goods']['goods_image'],
            'key_name'       => $result['order_goods']['key_name'],
            'key_value'      => $result['order_goods']['key_value'],
            'user_id'        => get_client_id(),
            'qty'            => $result['order_goods']['qty'],
            'type'           => $type === 'maintain' ? 3 : 2,
            'reason'         => $data['reason'],
            'description'    => !empty($data['description']) ? $data['description'] : '',
            'goods_status'   => 2,
            'image'          => !empty($data['image']) ? $data['image'] : [],
        ];

        // 开启事务
        self::startTrans();

        try {
            // 写入售后服务单
            if (false === $this->isUpdate(false)->save($serviceData)) {
                throw new \Exception($this->getError());
            }

            // 修改订单商品售后状态
            if (false === $orderGoodsDb->isUpdate(true)->save(['is_service' => 1])) {
                throw new \Exception($orderGoodsDb->getError());
            }

            // 写入售后服务单日志
            if (!$this->addServiceLog($this->toArray(), '发起申请售后服务。', '申请售后')) {
                throw new \Exception($this->getError());
            }

            self::commit();
            return $this->hidden(['order_service_id', 'admin_new'])->toArray();
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 添加一个维修售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     */
    public function addOrderServiceMaintain($data)
    {
        return $this->addMaintainOfExchange($data, 'maintain');
    }

    /**
     * 添加一个换货售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     */
    public function addOrderServiceExchange($data)
    {
        return $this->addMaintainOfExchange($data, 'exchange');
    }

    /**
     * 添加一个仅退款或退货退款售后服务单
     * @access private
     * @param  array  $data 外部数据
     * @param  string $type 售后类型 refund或refund_refunds
     * @return false|array
     * @throws
     */
    private function addServiceRefund(&$data, $type)
    {
        if (!$this->validateData($data, 'OrderService.' . $type)) {
            return false;
        }

        if ($type !== 'refund' && $type !== 'refund_refunds') {
            return $this->setError('售后类型只能为 refund 或 refund_refunds');
        }

        $orderGoodsDb = null;
        $result = $this->getOrderServiceGoods($data, $orderGoodsDb, true);

        if (false === $result) {
            return false;
        }

        if (isset($result['service_no'])) {
            return $this->setError('订单商品存在尚未完成的售后服务');
        }

        if ($result['is_' . $type] !== 1) {
            return $this->setError('订单商品不满足申请该服务的条件');
        }

        if (bccomp($data['refund_fee'], $result['order_goods']['refund_fee'], 2) === 1) {
            return $this->setError('最大允许退款金额为 ' . $result['order_goods']['refund_fee']);
        }

        // 按申请额计算实际退款结构
        $totalAmount = array_sum($result['order_goods']['refund_detail']);
        foreach ($result['order_goods']['refund_detail'] as &$value) {
            $value = round(($data['refund_fee'] / $totalAmount) * $value, 2);
        }

        // 售后单入库数据准备
        $serviceData = [
            'service_no'     => $this->getServiceNo(),
            'order_no'       => $result['order_goods']['order_no'],
            'order_goods_id' => $data['order_goods_id'],
            'goods_image'    => $result['order_goods']['goods_image'],
            'key_name'       => $result['order_goods']['key_name'],
            'key_value'      => $result['order_goods']['key_value'],
            'user_id'        => get_client_id(),
            'type'           => $type === 'refund' ? 0 : 1,
            'reason'         => $data['reason'],
            'description'    => !empty($data['description']) ? $data['description'] : '',
            'goods_status'   => $type === 'refund' ? $data['goods_status'] : 2,
            'image'          => !empty($data['image']) ? $data['image'] : [],
            'refund_fee'     => $data['refund_fee'],
            'refund_detail'  => $result['order_goods']['refund_detail'],
            'delivery_fee'   => $result['order_goods']['delivery_fee'],
        ];

        // 开启事务
        self::startTrans();

        try {
            // 写入售后服务单
            if (false === $this->isUpdate(false)->save($serviceData)) {
                throw new \Exception($this->getError());
            }

            // 修改订单商品售后状态
            if (false === $orderGoodsDb->isUpdate(true)->save(['is_service' => 1])) {
                throw new \Exception($orderGoodsDb->getError());
            }

            // 写入售后服务单日志
            if (!$this->addServiceLog($this->toArray(), '发起申请售后服务。', '申请售后')) {
                throw new \Exception($this->getError());
            }

            self::commit();
            return $this->hidden(['order_service_id', 'admin_new'])->toArray();
        } catch (\Exception $e) {
            self::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 添加一个仅退款售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     */
    public function addOrderServiceRefund($data)
    {
        return $this->addServiceRefund($data, 'refund');
    }

    /**
     * 添加一个退款退货售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     */
    public function addOrderServiceRefunds($data)
    {
        return $this->addServiceRefund($data, 'refund_refunds');
    }

    /**
     * 添加一条售后服务单留言
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function addOrderServiceMessage($data)
    {
        if (!$this->validateData($data, 'OrderService.message')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['service_no'] = ['eq', $data['service_no']];
            is_client_admin() ?: $map['user_id'] = ['eq', get_client_id()];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('售后服务单不存在') : false;
        }

        // 写入售后服务单日志
        $desc = is_client_admin() ? '商家留言' : '买家留言';
        if ($this->addServiceLog($result->toArray(), $data['message'], $desc)) {
            return true;
        }

        return false;
    }

    /**
     * 同意(接收)一个售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setOrderServiceAgree($data)
    {
        if (!$this->validateData($data, 'OrderService.agree')) {
            return false;
        }

        $result = self::get(['service_no' => $data['service_no']]);
        if (!$result) {
            return is_null($result) ? $this->setError('售后服务单不存在') : false;
        }

        $adminId = $result->getAttr('admin_id');
        if ($adminId > 0 && $adminId != get_client_id()) {
            $nickname = $result->getAttr('get_admin');
            return $this->setError((is_null($nickname) ? '其他人员' : $nickname['nickname']) . '已在处理此售后单');
        }

        if ($result->getAttr('status') !== 0) {
            return $this->setError('售后服务单当前状态不允许设置');
        }

        // 开启事务
        $result::startTrans();

        try {
            // 更新主数据
            if (false === $result->isUpdate(true)->save(['status' => 1, 'admin_id' => get_client_id()])) {
                throw new \Exception($this->getError());
            }

            // 写入售后服务单日志
            $comment = '商家已同意处理此笔售后服务单。';
            if (!$this->addServiceLog($result->toArray(), $comment, '同意售后')) {
                throw new \Exception($this->getError());
            }

            $result::commit();
            return true;
        } catch (\Exception $e) {
            $result::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 拒绝一个售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setOrderServiceRefused($data)
    {
        if (!$this->validateData($data, 'OrderService.refused')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['order_service.service_no'] = ['eq', $data['service_no']];
            $query->with('getOrderGoods')->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('售后服务单不存在') : false;
        }

        if ($result->getAttr('status') !== 0) {
            return $this->setError('售后服务单当前状态不允许设置');
        }

        // 开启事务
        $result::startTrans();

        try {
            // 更新主数据
            if (false === $result->isUpdate(true)->save(['status' => 2, 'result' => $data['result']])) {
                throw new \Exception($this->getError());
            }

            // 更新订单商品售后状态
            $goodsDb = $result->getAttr('get_order_goods');
            if (false === $goodsDb->isUpdate(true)->save(['is_service' => 2])) {
                throw new \Exception($goodsDb->getError());
            }

            // 写入售后服务单日志
            $comment = '商家已拒绝售后服务，如有需要您可以再次申请。';
            if (!$this->addServiceLog($result->toArray(), $comment, '拒绝售后')) {
                throw new \Exception($this->getError());
            }

            $result::commit();
            return true;
        } catch (\Exception $e) {
            $result::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 设置退换货、维修商品是否寄还商家
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setOrderServiceSendback($data)
    {
        if (!$this->validateData($data, 'OrderService.sendback')) {
            return false;
        }

        $result = self::get(['service_no' => $data['service_no']]);
        if (!$result) {
            return is_null($result) ? $this->setError('售后服务单不存在') : false;
        }

        if ($result->getAttr('is_return') === $data['is_return']) {
            return true;
        }

        if ($result->getAttr('type') === 0) {
            return $this->setError('该售后服务单类型不允许设置');
        }

        if ($result->getAttr('status') !== 1) {
            return $this->setError('售后服务单当前状态不允许设置');
        }

        if (!empty($result->getAttr('logistic_code'))) {
            return $this->setError('买家已寄件，不允许设置');
        }

        // 开启事务
        $result::startTrans();

        try {
            if (false === $result->isUpdate(true)->save(['is_return' => $data['is_return']])) {
                throw new \Exception($this->getError());
            }

            $comment = $data['is_return'] == 0 ?
                '商家取消了商品寄回的请求。' :
                '请按商家收件地址将商品寄出，填写快递单号、并填写您的收件地址。' . PHP_EOL .
                '收件地址：' . Config::get('address.value', 'service') . PHP_EOL .
                '收件人：' . Config::get('consignee.value', 'service') . PHP_EOL .
                '电话：' . Config::get('mobile.value', 'service') . PHP_EOL .
                '邮编：' . Config::get('zipcode.value', 'service');

            // 写入售后服务单日志
            if (!$this->addServiceLog($result->toArray(), $comment, '商品寄回')) {
                throw new \Exception($this->getError());
            }

            $result::commit();
            return true;
        } catch (\Exception $e) {
            $result::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 买家填写快递单号或寄回信息
     * @access private
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    private function setLogisticCode(&$data)
    {
        $result = self::get(function ($query) use ($data) {
            $map['service_no'] = ['eq', $data['service_no']];
            $map['user_id'] = ['eq', get_client_id()];

            $query->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('售后服务单不存在') : false;
        }

        if ($result->getAttr('type') === 0) {
            return $this->setError('该售后服务单类型不允许填写');
        }

        if ($result->getAttr('type') === 1 && $result->getAttr('goods_status') !== 2) {
            return $this->setError('售后服务单中的商品未收到货，不需要填写');
        }

        if ($result->getAttr('status') !== 1) {
            return $this->setError('售后服务单当前状态不允许填写');
        }

        if ($result->getAttr('is_return') !== 1) {
            return $this->setError('商家未要求寄回待售后商品');
        }

        // 开启事务
        $result::startTrans();

        try {
            // 添加一条配送记录
            $distData = [
                'client_id'     => $result->getAttr('user_id'),
                'order_code'    => $result->getAttr('service_no'),
                'delivery_id'   => $data['delivery_id'],
                'logistic_code' => $data['logistic_code'],
            ];

            $distDb = new DeliveryDist();
            if (false === $distDb->addDeliveryDistItem($distData)) {
                throw new \Exception($distDb->getError());
            }

            // 更新售后服务单部分数据,并准备允许写入的字段
            $data['status'] = 3; // 已寄件
            unset($data['order_service_id']);
            $field = ['logistic_code', 'status'];
            if (in_array($result->getAttr('type'), [2, 3])) {
                $field = array_merge($field, ['address', 'consignee', 'zipcode', 'mobile']);
            }

            if (false === $result->allowField($field)->isUpdate(true)->save($data)) {
                throw new \Exception($this->getError());
            }

            // 写入售后服务单日志
            $comment = '买家已将待售后商品寄出，请注意查收！';
            if (!$this->addServiceLog($result->toArray(), $comment, '买家寄出')) {
                throw new \Exception($this->getError());
            }

            $result::commit();
            return true;
        } catch (\Exception $e) {
            $result::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 买家上报换货、维修后的快递单号,并填写商家寄回时需要的信息
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setOrderServiceBuyer($data)
    {
        if (!$this->validateData($data, 'OrderService.buyer')) {
            return false;
        }

        return $this->setLogisticCode($data);
    }

    /**
     * 买家上报退款退货后的快递单号
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setOrderServiceLogistic($data)
    {
        if (!$this->validateData($data, 'OrderService.logistic')) {
            return false;
        }

        return $this->setLogisticCode($data);
    }

    /**
     * 设置一个售后服务单状态为"售后中"
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setOrderServiceAfter($data)
    {
        if (!$this->validateData($data, 'OrderService.item')) {
            return false;
        }

        $result = self::get(['service_no' => $data['service_no']]);
        if (!$result) {
            return is_null($result) ? $this->setError('售后服务单不存在') : false;
        }

        if (!in_array($result->getAttr('status'), [1, 3])) {
            return $this->setError('售后服务单当前状态不允许设置');
        }

        // 开启事务
        $result::startTrans();

        try {
            // 更新主数据
            if (false === $result->isUpdate(true)->save(['status' => 4])) {
                throw new \Exception($this->getError());
            }

            // 写入售后服务单日志
            $comment = '商家对该售后服务单正在进行售后服务。';
            if (!$this->addServiceLog($result->toArray(), $comment, '售后服务')) {
                throw new \Exception($this->getError());
            }

            $result::commit();
            return true;
        } catch (\Exception $e) {
            $result::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 撤销一个售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setOrderServiceCancel($data)
    {
        if (!$this->validateData($data, 'OrderService.cancel')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['service_no'] = ['eq', $data['service_no']];
            is_client_admin() ?: $map['user_id'] = ['eq', get_client_id()];

            $query->with('getOrderGoods,getOrderRefund')->where($map);
        });

        if (!$result) {
            return is_null($result) ? $this->setError('售后服务单不存在') : false;
        }

        if (in_array($result->getAttr('status'), [2, 5, 6])) {
            return $this->setError('售后服务单当前状态不允许设置');
        }

        // 开启事务
        $result::startTrans();

        try {
            // 更新主数据
            if (false === $result->isUpdate(true)->save(['status' => 5])) {
                throw new \Exception($this->getError());
            }

            // 更新订单商品售后状态
            $goodsDb = $result->getAttr('get_order_goods');
            if (false === $goodsDb->isUpdate(true)->save(['is_service' => 2])) {
                throw new \Exception($goodsDb->getError());
            }

            // 写入售后服务单日志
            $comment = (is_client_admin() ? '商家' : '买家') . '主动撤销售后服务单';
            if (!$this->addServiceLog($result->toArray(), $comment . '。', '撤销申请')) {
                throw new \Exception($this->getError());
            }

            // 更新订单退款单状态
            if (!empty($result->getAttr('refund_no'))) {
                $refundDb = $result->getAttr('get_order_refund');
                $refundData = ['status' => 3, 'out_trade_msg' => '由于' . $comment . '，本次退款申请撤销。'];

                if ($refundDb->getAttr('status') === 0) {
                    if (false === $refundDb->isUpdate(true)->save($refundData)) {
                        throw new \Exception($refundDb->getError());
                    }
                }
            }

            $result::commit();
            return true;
        } catch (\Exception $e) {
            $result::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 追回已赠送出的所有资源(累计消费(会员等级)、积分、优惠劵)
     * @access private
     * @param  array  $orderData 订单数据
     * @param  string $serviceNo 售后单号
     * @return bool
     */
    private function recoverGiveResources($orderData, $serviceNo)
    {
        // 减少累计消费金额
        $userMoneyDb = new UserMoney();
        if ($orderData['pay_amount'] > 0) {
            if (!$userMoneyDb->decTotalMoney($orderData['pay_amount'], $orderData['user_id'])) {
                return $this->setError($userMoneyDb->getError());
            }
        }

        // 减少赠送积分
        if ($orderData['give_integral'] > 0) {
            if (!$userMoneyDb->setPoints(-$orderData['give_integral'], $orderData['user_id'])) {
                return $this->setError($userMoneyDb->getError());
            }

            $txLogData = [
                'user_id'    => $orderData['user_id'],
                'type'       => Transaction::TRANSACTION_EXPENDITURE,
                'amount'     => $orderData['give_integral'],
                'balance'    => $userMoneyDb->where(['user_id' => ['eq', $orderData['user_id']]])->value('points'),
                'source_no'  => $serviceNo,
                'remark'     => '退回赠送',
                'module'     => 'points',
                'to_payment' => Payment::PAYMENT_CODE_USER,
            ];

            $txDb = new Transaction();
            if (!$txDb->addTransactionItem($txLogData)) {
                return $this->setError($txDb->getError());
            }
        }

        // 作废已赠送的优惠劵
        if (!empty($orderData['give_coupon'])) {
            $mapGive['coupon_give_id'] = ['in', $orderData['give_coupon']];
            $mapGive['user_id'] = ['eq', $orderData['user_id']];
            $mapGive['use_time'] = ['eq', 0];

            $couponGiveDb = new CouponGive();
            if (false === $couponGiveDb->save(['is_delete' => 1], $mapGive)) {
                return $this->setError($couponGiveDb->getError());
            }
        }

        return true;
    }

    /**
     * 退回用户余额或积分
     * @access private
     * @param  string $type      余额或积分
     * @param  float  $value     值
     * @param  int    $userId    账号编号
     * @param  string $serviceNo 售后单号
     * @return bool
     */
    private function refundUserMoney($type, $value, $userId, $serviceNo)
    {
        if ($value <= 0 || !in_array($type, ['money_amount', 'integral_amount'])) {
            return true;
        }

        $userMoneyDb = new UserMoney();
        if ($type == 'money_amount') {
            $result = $userMoneyDb->setBalance($value, $userId);
        } else {
            $result = $userMoneyDb->setPoints($value, $userId);
        }

        if (false === $result) {
            return $this->setError($userMoneyDb->getError());
        }

        $type = 'money_amount' == $type ? 'balance' : 'points';
        $txLogData = [
            'user_id'    => $userId,
            'type'       => Transaction::TRANSACTION_INCOME,
            'amount'     => $value,
            'balance'    => UserMoney::where(['user_id' => ['eq', $userId]])->value($type),
            'source_no'  => $serviceNo,
            'remark'     => '售后退款',
            'module'     => $type == 'balance' ? 'money' : 'points',
            'to_payment' => Payment::PAYMENT_CODE_USER,
        ];

        $txDb = new Transaction();
        if (!$txDb->addTransactionItem($txLogData)) {
            return $this->setError($txDb->getError());
        }

        return true;
    }

    /**
     * 退回购物卡可用余额
     * @access private
     * @param  float  $value     值
     * @param  object &$orderDb  订单模型
     * @param  string $serviceNo 售后单号
     * @return bool
     */
    private function refundCardUser($value, &$orderDb, $serviceNo)
    {
        if ($value <= 0) {
            return true;
        }

        $userId = $orderDb->getAttr('user_id');
        $number = $orderDb->getAttr('card_number');

        $cardUserDb = new CardUse();
        if (!$cardUserDb->incCardUseMoney($number, $value, $userId)) {
            return $this->setError($cardUserDb->getError());
        }

        $txLogData = [
            'user_id'     => $userId,
            'type'        => Transaction::TRANSACTION_INCOME,
            'amount'      => $value,
            'balance'     => CardUse::where(['user_id' => $userId, 'number' => $number])->value('money'),
            'source_no'   => $serviceNo,
            'remark'      => '售后退款',
            'module'      => 'card',
            'to_payment'  => Payment::PAYMENT_CODE_CARD,
            'card_number' => $number,
        ];

        $txDb = new Transaction();
        if (!$txDb->addTransactionItem($txLogData)) {
            return $this->setError($txDb->getError());
        }

        return true;
    }

    /**
     * 原路退回在线支付
     * @access private
     * @param  float  $value      值
     * @param  array  $orderData  订单数据
     * @param  object &$serviceDb 售后单模型
     * @return bool
     */
    private function refundPayment($value, $orderData, &$serviceDb)
    {
        if ($value <= 0 || $orderData['total_amount'] <= 0 || empty($orderData['payment_no'])) {
            return true;
        }

        $refundNo = '';
        $refundDb = new OrderRefund();

        if (!$refundDb->refundOrderPayment($orderData, $value, $refundNo)) {
            return $this->setError($refundDb->getError());
        }

        if (false === $serviceDb->isUpdate(true)->save(['refund_no' => $refundNo])) {
            return false;
        }

        return true;
    }

    /**
     * 当仅退款或退款退货总金额到达订单金额时关闭订单
     * @access private
     * @param  object &$orderDb 订单模型
     * @return bool
     */
    private function isCancelOrder(&$orderDb)
    {
        // 查询是否已全部退款
        $map['order_no'] = ['eq', $orderDb->getAttr('order_no')];
        $map['user_id'] = ['eq', $orderDb->getAttr('user_id')];
        $map['type'] = ['in', '0,1'];
        $map['status'] = ['eq', 6];

        $sum = self::where($map)->sum('refund_fee');
        $totalAmount = round($orderDb->getAttr('pay_amount') + $orderDb->getAttr('delivery_fee'), 2);

        if ($sum >= $totalAmount - 0.01 && $sum <= $totalAmount + 0.01) {
            // 修改订单数据
            if (false === $orderDb->isUpdate(true)->save(['trade_status' => 4, 'payment_status' => 0])) {
                return $this->setError($orderDb->getError());
            }

            // 写入订单操作日志
            if (!$orderDb->addOrderLog($orderDb->toArray(), '退款已完成，订单关闭', '取消订单')) {
                return $this->setError($orderDb->getError());
            }
        }

        return true;
    }

    /**
     * 完成仅退款、退款退货售后服务单
     * @access private
     * @param  array  $data       外部数据
     * @param  object &$serviceDb 售后单模型
     * @return bool
     */
    private function completeContainsFeeService($data, &$serviceDb)
    {
        if (!is_object($serviceDb)) {
            return $this->setError('参数异常');
        }

        if (!in_array($serviceDb->getAttr('status'), [1, 4])) {
            return $this->setError('售后服务单当前状态不允许设置');
        }

        // 避免无关数据
        unset($data['order_service_id']);

        // 开启事务
        $serviceDb::startTrans();

        try {
            // 更新主数据
            if (false === $serviceDb->allowField(['result', 'status'])->save($data)) {
                throw new \Exception($this->getError());
            }

            // 更新订单商品售后状态
            $goodsDb = $serviceDb->getAttr('get_order_goods');
            if (false === $goodsDb->isUpdate(true)->save(['is_service' => 3, 'status' => 3])) {
                throw new \Exception($goodsDb->getError());
            }

            // 获取订单数据
            $serviceNo = $serviceDb->getAttr('service_no');
            $orderDb = Order::where(['order_no' => ['eq', $serviceDb->getAttr('order_no')]])->find();

            if (!$orderDb) {
                throw new \Exception(is_null($orderDb) ? '订单不存在' : $orderDb->getError());
            }

            // 检测是否需要追回赠送资源
            if ($orderDb->getAttr('is_give') === 1) {
                if ($orderDb->getAttr('trade_status') === 3) {
                    if (!$this->recoverGiveResources($orderDb->toArray(), $serviceNo)) {
                        throw new \Exception($this->getError());
                    }
                }

                if (false === $orderDb->isUpdate(true)->save(['is_give' => 0])) {
                    throw new \Exception($orderDb->getError());
                }
            }

            // 日志详情数据准备
            $comment = '售后服务完成，合计退款：'. $serviceDb->getAttr('refund_fee') . PHP_EOL;

            // 根据退款结构退回款项
            if ($serviceDb->getAttr('refund_fee') > 0) {
                $refundDetail = $serviceDb->getAttr('refund_detail');
                foreach ($refundDetail as $key => $value) {
                    if ($value <= 0) {
                        continue;
                    }

                    if ('payment_amount' == $key) {
                        if (!$this->refundPayment($value, $orderDb->toArray(), $serviceDb)) {
                            throw new \Exception($orderDb->getError());
                        }

                        $comment .= '在线支付原路退回：' . $value . PHP_EOL;
                        continue;
                    }

                    if ('card_amount' == $key) {
                        if (!$this->refundCardUser($value, $orderDb, $serviceNo)) {
                            throw new \Exception($orderDb->getError());
                        }

                        $comment .= '购物卡退回：' . $value . PHP_EOL;
                        continue;
                    }

                    if (in_array($key, ['money_amount', 'integral_amount'])) {
                        if ('integral_amount' == $key) {
                            $refundDetail[$key] *= $orderDb->getAttr('integral_pct');
                            $value = $refundDetail[$key];
                        }

                        if (!$this->refundUserMoney($key, $value, $serviceDb->getAttr('user_id'), $serviceNo)) {
                            throw new \Exception($orderDb->getError());
                        }

                        $comment .= ('integral_amount' == $key ? '积分' : '余额') . '退回：' . $value . PHP_EOL;
                        continue;
                    }
                }
            }

            // 写入售后服务单日志
            if (!$this->addServiceLog($serviceDb->toArray(), $comment, '完成售后')) {
                throw new \Exception($orderDb->getError());
            }

            // 隐藏已存在的评价
            if ($goodsDb->getAttr('is_comment') > 0) {
                $map['order_goods_id'] = ['eq', $goodsDb->getAttr('order_goods_id')];
                GoodsComment::where($map)->setField('is_show', 0);
            }

            // 当仅退款或退款退货总金额到达订单金额时关闭订单
            if (!$this->isCancelOrder($orderDb)) {
                throw new \Exception($orderDb->getError());
            }

            $serviceDb::commit();
            return true;
        } catch (\Exception $e) {
            $serviceDb::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 完成换货、维修售后服务单
     * @access private
     * @param  array  $data      外部数据
     * @param  object &$serviceDb 数据模型
     * @return bool
     */
    private function completeNotFeeService($data, &$serviceDb)
    {
        if (!is_object($serviceDb)) {
            return $this->setError('参数异常');
        }

        if ($serviceDb->getAttr('is_return') === 1) {
            if (!$this->validateData($data, 'OrderService.logistic')) {
                return false;
            }
        }

        if ($serviceDb->getAttr('status') !== 4) {
            return $this->setError('售后服务单当前状态不允许设置');
        }

        // 避免无关数据
        unset($data['order_service_id']);

        // 开启事务
        $serviceDb::startTrans();

        try {
            // 添加一条配送记录
            if ($serviceDb->getAttr('is_return') === 1) {
                $distData = [
                    'client_id'     => $serviceDb->getAttr('user_id'),
                    'order_code'    => $serviceDb->getAttr('service_no'),
                    'delivery_id'   => $data['delivery_id'],
                    'logistic_code' => $data['logistic_code'],
                ];

                $distDb = new DeliveryDist();
                if (false === $distDb->addDeliveryDistItem($distData)) {
                    throw new \Exception($distDb->getError());
                }
            }

            // 更新主数据
            if (false === $serviceDb->allowField(['result', 'status'])->save($data)) {
                throw new \Exception($this->getError());
            }

            // 更新订单商品售后状态
            $goodsDb = $serviceDb->getAttr('get_order_goods');
            if (false === $goodsDb->isUpdate(true)->save(['is_service' => 2])) {
                throw new \Exception($goodsDb->getError());
            }

            // 写入售后服务单日志
            $comment = '商家已完成售后服务';
            $comment .= $serviceDb->getAttr('is_return') === 0 ? '。' : '，并已将售后商品寄出。';

            if (!$this->addServiceLog($serviceDb->toArray(), $comment, '完成售后')) {
                throw new \Exception($goodsDb->getError());
            }

            $serviceDb::commit();
            return true;
        } catch (\Exception $e) {
            $serviceDb::rollback();
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 完成一个售后服务单
     * @access public
     * @param  array $data 外部数据
     * @return bool
     * @throws
     */
    public function setOrderServiceComplete($data)
    {
        if (!$this->validateData($data, 'OrderService.complete')) {
            return false;
        }

        $result = self::get(['service_no' => $data['service_no']]);
        if (!$result) {
            return is_null($result) ? $this->setError('售后服务单不存在') : false;
        }

        // 完成实际业务
        $data['status'] = 6;
        $isSuccess = false;

        switch ($result->getAttr('type')) {
            case 0:
            case 1:
                $isSuccess = $this->completeContainsFeeService($data, $result);
                break;

            case 2:
            case 3:
                $isSuccess = $this->completeNotFeeService($data, $result);
                break;
        }

        return $isSuccess;
    }
}