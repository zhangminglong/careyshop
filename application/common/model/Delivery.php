<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    配送方式模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/27
 */

namespace app\common\model;

class Delivery extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'delivery_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'delivery_id'         => 'integer',
        'delivery_item_id'    => 'integer',
        'first_weight'        => 'float',
        'first_weight_price'  => 'float',
        'second_weight'       => 'float',
        'second_weight_price' => 'float',
        'first_item'          => 'integer',
        'first_item_price'    => 'float',
        'second_item'         => 'integer',
        'second_item_price'   => 'float',
        'first_volume'        => 'float',
        'first_volume_price'  => 'float',
        'second_volume'       => 'float',
        'second_volume_price' => 'float',
        'sort'                => 'integer',
        'status'              => 'integer',
    ];

    /**
     * hasMany cs_delivery_area
     * @access public
     * @return mixed
     */
    public function getDeliveryArea()
    {
        $field = [
            'region', 'first_weight_price', 'second_weight_price', 'first_item_price',
            'second_item_price', 'first_volume_price', 'second_volume_price',
        ];

        return $this->hasMany('DeliveryArea')->field($field);
    }

    /**
     * hasOne cs_delivery_item
     * @access public
     * @return mixed
     */
    public function getDeliveryItem()
    {
        return $this
            ->hasOne('deliveryItem', 'delivery_item_id', 'delivery_item_id')
            ->setEagerlyType(0);
    }

    /**
     * 添加一个配送方式
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addDeliveryItem($data)
    {
        if (!$this->validateData($data, 'Delivery')) {
            return false;
        }

        // 避免无关字段
        unset($data['delivery_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个配送方式
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setDeliveryItem($data)
    {
        if (!$this->validateSetData($data, 'Delivery.set')) {
            return false;
        }

        if (isset($data['delivery_item_id'])) {
            $map['delivery_id'] = ['neq', $data['delivery_id']];
            $map['delivery_item_id'] = ['eq', $data['delivery_item_id']];

            if (self::checkUnique($map)) {
                return $this->setError('快递公司编号已存在');
            }
        }

        $map = ['delivery_id' => ['eq', $data['delivery_id']]];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除配送方式
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delDeliveryList($data)
    {
        if (!$this->validateData($data, 'Delivery.del')) {
            return false;
        }

        self::destroy($data['delivery_id']);

        return true;
    }

    /**
     * 获取一个配送方式
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getDeliveryItems($data)
    {
        if (!$this->validateData($data, 'Delivery.item')) {
            return false;
        }

        $result = self::get($data['delivery_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取配送方式列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getDeliveryList($data)
    {
        if (!$this->validateData($data, 'Delivery.list')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            // 搜索条件
            $map['delivery.status'] = ['eq', 1];

            // 后台管理搜索
            if (is_client_admin()) {
                unset($map['delivery.status']);
                !isset($data['status']) ?: $map['delivery.status'] = ['eq', $data['status']];
                empty($data['name']) ?: $map['getDeliveryItem.name'] = ['like', '%' . $data['name'] . '%'];
            }

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = 'delivery.delivery_id';
            if (isset($data['order_field'])) {
                switch ($data['order_field']) {
                    case 'delivery_id':
                    case 'content':
                    case 'sort':
                    case 'status':
                        $orderField = 'delivery.' . $data['order_field'];
                        break;

                    case 'name':
                        $orderField = 'getDeliveryItem.' . $data['order_field'];
                        break;
                }
            }

            // 排序处理
            $order['delivery.sort'] = 'asc';
            $order[$orderField] = $orderType;

            $query->field('delivery_item_id', true)->with('getDeliveryItem')->where($map)->order($order);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 获取配送方式选择列表
     * @access public
     * @return array
     * @throws
     */
    public function getDeliverySelect()
    {
        $result = self::all(function ($query) {
            $query
                ->alias('d')
                ->field('d.delivery_id,i.name,i.code')
                ->join('delivery_item i', 'i.delivery_item_id = d.delivery_item_id', 'inner')
                ->where(['d.status' => ['eq', 1], 'i.is_delete' => ['eq', 0]])
                ->order('d.sort asc');
        });

        return $result->toArray();
    }

    /**
     * 根据配送方式获取运费
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getDeliveryFreight($data)
    {
        if (!$this->validateData($data, 'Delivery.freight')) {
            return false;
        }

        // 获取基础数据
        $delivery = self::get(function ($query) use ($data) {
            $query->where(['delivery_id' => ['eq', $data['delivery_id']], 'status' => ['eq', 1]]);
        });

        if (!$delivery) {
            return is_null($delivery) ? $this->setError('配送方式不存在') : false;
        }

        // 获取配送区域数据
        $deliveryArea = $delivery->getDeliveryArea()->select();

        // 获取区域列表
        $regionList = Region::getRegionCacheList();
        $regionId = [];

        while (true) {
            if (!isset($regionList[$data['region_id']])) {
                break;
            }

            if ($regionList[$data['region_id']]['parent_id'] <= 0) {
                break;
            }

            $regionId[] = $regionList[$data['region_id']]['region_id'];
            $data['region_id'] = $regionList[$data['region_id']]['parent_id'];
        }

        // 确认各个计量基础费用
        $firstWeightPrice = $delivery['first_weight_price'];
        $secondWeightPrice = $delivery['second_weight_price'];
        $firstItemPrice = $delivery['first_item_price'];
        $secondItemPrice = $delivery['second_item_price'];
        $firstVolumePrice = $delivery['first_volume_price'];
        $secondVolumePrice = $delivery['second_volume_price'];

        // 存在区域则需要取区域的费用
        foreach ($regionId as $value) {
            foreach ($deliveryArea->toArray() as $item) {
                foreach ($item['region'] as $region) {
                    if ($region['region_id'] == $value) {
                        $firstWeightPrice = $item['first_weight_price'];
                        $secondWeightPrice = $item['second_weight_price'];
                        $firstItemPrice = $item['first_item_price'];
                        $secondItemPrice = $item['second_item_price'];
                        $firstVolumePrice = $item['first_volume_price'];
                        $secondVolumePrice = $item['second_volume_price'];
                        break 3;
                    }
                }
            }
        }

        // 计算各个计量续量费用
        $result = [
            'delivery_fee' => 0,
            'weight_fee'   => 0,
            'item_fee'     => 0,
            'volume_fee'   => 0,
        ];

        if (!empty($data['weight_total'])) {
            $result['weight_fee'] = $firstWeightPrice;
            $result['delivery_fee'] += $firstWeightPrice;
            $weight = $data['weight_total'] - $delivery['first_weight'];

            while ($weight > 0 && $delivery['second_weight'] > 0 && $secondWeightPrice > 0) {
                $weight -= $delivery['second_weight'];
                $result['weight_fee'] += $secondWeightPrice;
                $result['delivery_fee'] += $secondWeightPrice;
            }
        }

        if (!empty($data['item_total'])) {
            $result['item_fee'] = $firstItemPrice;
            $result['delivery_fee'] += $firstItemPrice;
            $item = $data['item_total'] - $delivery['first_item'];

            while ($item > 0 && $delivery['second_item'] > 0 && $secondItemPrice > 0) {
                $item -= $delivery['second_item'];
                $result['item_fee'] += $secondItemPrice;
                $result['delivery_fee'] += $secondItemPrice;
            }
        }

        if (!empty($data['volume_total'])) {
            $result['volume_fee'] = $firstVolumePrice;
            $result['delivery_fee'] += $firstVolumePrice;
            $volume = $data['volume_total'] - $delivery['first_volume'];

            while ($volume > 0 && $delivery['second_volume'] > 0 && $secondVolumePrice > 0) {
                $volume -= $delivery['second_volume'];
                $result['volume_fee'] += $secondVolumePrice;
                $result['delivery_fee'] += $secondVolumePrice;
            }
        }

        return ['delivery_fee' => round($result['delivery_fee'], 2)];
    }

    /**
     * 批量设置配送方式状态
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setDeliveryStatus($data)
    {
        if (!$this->validateData($data, 'Delivery.status')) {
            return false;
        }

        $map['delivery_id'] = ['in', $data['delivery_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 验证快递公司编号是否已存在
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function uniqueDeliveryItem($data)
    {
        if (!$this->validateData($data, 'Delivery.unique')) {
            return false;
        }

        $map['delivery_item_id'] = ['eq', $data['delivery_item_id']];
        !isset($data['exclude_id']) ?: $map['delivery_id'] = ['neq', $data['exclude_id']];

        if (self::checkUnique($map)) {
            return $this->setError('快递公司编号已存在');
        }

        return true;
    }

    /**
     * 设置配送方式排序
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setDeliverySort($data)
    {
        if (!$this->validateData($data, 'Delivery.sort')) {
            return false;
        }

        $map['delivery_id'] = ['eq', $data['delivery_id']];
        if (false !== $this->save(['sort' => $data['sort']], $map)) {
            return true;
        }

        return false;
    }
}