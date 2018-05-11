<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    配送轨迹模型
 *
 * @author      zxm <252404501@qq.com>
 * @version     v1.1
 * @date        2017/4/27
 */

namespace app\common\model;

use think\Config;
use util\Http;

class DeliveryDist extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'delivery_item_id',
        'delivery_code',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'delivery_dist_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'delivery_dist_id' => 'integer',
        'user_id'          => 'integer',
        'delivery_item_id' => 'integer',
        'state'            => 'integer',
        'trace'            => 'array',
    ];

    /**
     * hasOne cs_delivery_item
     * @access public
     * @return $this
     */
    public function getDeliveryItem()
    {
        return $this
            ->hasOne('DeliveryItem', 'delivery_item_id', 'delivery_item_id')
            ->field('name,code')
            ->setEagerlyType(0);
    }

    /**
     * hasOne cs_user
     * @access public
     * @return $this
     */
    public function getUser()
    {
        return $this
            ->hasOne('User', 'user_id', 'user_id', [], 'left')
            ->field('username,nickname,head_pic')
            ->setEagerlyType(0);
    }

    /**
     * 订阅一条配送轨迹
     * @access public
     * @param  array $data 外部数据
     * @return false/array
     */
    public function subscribeDistItem($data)
    {
        if (!$this->validateData($data, 'DeliveryDist')) {
            return false;
        }

        // 避免无关字段及设置部分字段
        $data['user_id'] = is_client_admin() ? $data['client_id'] : get_client_id();
        $data['trace'] = [];
        unset($data['delivery_dist_id'], $data['delivery_code'], $data['state'], $data['client_id']);

        // 根据配送方式编号获取快递公司编码
        $deliveryResult = Delivery::get(function ($query) use ($data) {
            $query
                ->alias('d')
                ->field('i.delivery_item_id,i.code')
                ->join('delivery_item i', 'i.delivery_item_id = d.delivery_item_id')
                ->where(['d.delivery_id' => ['eq', $data['delivery_id']]]);
        });

        if (!$deliveryResult) {
            return $this->setError('配送方式数据不存在');
        }

        // 请求正文内容
        $requestData = [
            'ShipperCode'  => $deliveryResult->getAttr('code'),
            'LogisticCode' => $data['logistic_code'],
            'OrderCode'    => $data['order_code'],
            'Remark'       => 'CareyShop',
        ];

        $data['delivery_code'] = $requestData['ShipperCode'];
        $data['delivery_item_id'] = $deliveryResult->getAttr('delivery_item_id');
        $requestData = json_encode($requestData, JSON_UNESCAPED_UNICODE);

        // 请求系统参数
        $postData = [
            'RequestData' => urlencode($requestData),
            'EBusinessID' => Config::get('ebusiness_id.value', 'delivery_dist'),
            'RequestType' => '1008',
            'DataSign'    => \app\common\service\DeliveryDist::getCallbackSign($requestData),
            'DataType'    => '2',
        ];

        $result = Http::httpPost(Config::get('api_url.value', 'delivery_dist'), $postData);
        $result = json_decode($result, true);

        if (true != $result['Success']) {
            return $this->setError($result['Reason']);
        }

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 接收推送过来的配送数据
     * @access public
     * @param  array $data 外部数据
     * @return false/array
     */
    public function putDistData($data)
    {
        $result['callback_return_type'] = 'json';
        $result['is_callback'] = [
            'EBusinessID' => Config::get('ebusiness_id.value', 'delivery_dist'),
            'UpdateTime'  => date('Y-m-d H:i:s'),
            'Success'     => true,
        ];

        if (empty($data['RequestData'])) {
            $result['is_callback']['Success'] = false;
            $result['is_callback']['Reason'] = '请提交推送内容';
            return $result;
        }

        // 目前只有101配送轨迹订阅,如有其他业务则进行派分
        if (!isset($data['RequestType']) || '101' != $data['RequestType']) {
            $result['is_callback']['Success'] = false;
            $result['is_callback']['Reason'] = '请求指令错误';
            return $result;
        }

        // 需要把HTML实体转换为字符
        $requestData = htmlspecialchars_decode($data['RequestData']);
        if (\app\common\service\DeliveryDist::getCallbackSign($requestData) != urlencode($data['DataSign'])) {
            $result['is_callback']['Success'] = false;
            $result['is_callback']['Reason'] = '请求非法';
            return $result;
        }

        $requestData = json_decode($requestData, true);
        foreach ($requestData['Data'] as $value) {
            if (true == $value['Success']) {
                $updata = [
                    'state' => $value['State'],
                    'trace' => \app\common\service\DeliveryDist::snake($value['Traces']),
                ];

                $map['delivery_code'] = ['eq', $value['ShipperCode']];
                $map['logistic_code'] = ['eq', $value['LogisticCode']];
                $this->data($updata, true)->isUpdate(true)->save($updata, $map);
            }
        }

        return $result;
    }

    /**
     * 获取配送信息
     * @access public
     * @param  array $data 外部数据
     * @return false/array
     */
    public function getDistItem($data)
    {
        if (!$this->validateData($data, 'DeliveryDist.item')) {
            return false;
        }

        $result = self::all(function ($query) use ($data) {
            $map['delivery_dist.order_code'] = ['eq', $data['order_code']];
            empty($data['logistic_code']) ?: $map['delivery_dist.logistic_code'] = ['eq', $data['logistic_code']];
            empty($data['exclude_code']) ?: $map['delivery_dist.logistic_code'] = ['not in', $data['exclude_code']];

            $with = ['getDeliveryItem'];
            is_client_admin() ? $with[] = 'getUser' : $map['delivery_dist.user_id'] = ['eq', get_client_id()];

            $query->field('delivery_dist_id', true)->with($with)->where($map);
        });

        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }

    /**
     * 获取配送信息列表
     * @access public
     * @param  array $data 外部数据
     * @return false/array
     */
    public function getDistList($data)
    {
        if (!$this->validateData($data, 'DeliveryDist.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['order_code']) ?: $map['delivery_dist.order_code'] = ['eq', $data['order_code']];
        empty($data['logistic_code']) ?: $map['delivery_dist.logistic_code'] = ['eq', $data['logistic_code']];
        !isset($data['state']) ?: $map['delivery_dist.state'] = ['eq', $data['state']];

        if (!empty($data['timeout'])) {
            $map['delivery_dist.state'] = ['neq', 3];

            if ($data['timeout'] <= 30) {
                $map['delivery_dist.create_time'] = ['exp', $this->raw(sprintf('+ %d >= %d', $data['timeout'] * 86400, time()))];
            }
        }

        if (is_client_admin() && !empty($data['account'])) {
            $map['getUser.username|getUser.nickname'] = ['eq', $data['account']];
        }

        // 关联查询
        $with = ['getDeliveryItem'];
        !is_client_admin() ?: $with[] = 'getUser';

        $totalResult = $this->with($with)->where($map)->count();
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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'delivery_dist_id';

            // 默认不返回"trace"字段
            if (empty($data['is_trace'])) {
                $query->field('trace', true);
            }

            $query
                ->with($with)
                ->where($map)
                ->order(['delivery_dist.' . $orderField => $orderType])
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }
}