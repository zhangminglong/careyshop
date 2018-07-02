<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    配送轨迹模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/27
 */

namespace app\common\model;

use think\Config;
use util\Http;

class DeliveryDist extends CareyShop
{
    /**
     * 即时查询URL
     * @var string
     */
    const TRACK_URL = 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx';

    /**
     * 轨迹订阅URL
     * @var string
     */
    const FOLLOW_URL = 'http://api.kdniao.cc/api/dist';

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
        'is_sub'           => 'integer',
        'trace'            => 'array',
    ];

    /**
     * hasOne cs_delivery_item
     * @access public
     * @return mixed
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
     * 添加一条配送记录
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function addDeliveryDistItem($data)
    {
        if (!$this->validateData($data, 'DeliveryDist')) {
            return false;
        }

        // 避免无关字段及设置部分字段
        $data['trace'] = [];
        $data['user_id'] = is_client_admin() ? $data['client_id'] : get_client_id();
        unset($data['delivery_dist_id'], $data['delivery_code'], $data['state']);

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

        // 对数据再次进行处理
        $data['delivery_code'] = $deliveryResult->getAttr('code');
        $data['delivery_item_id'] = $deliveryResult->getAttr('delivery_item_id');
        $data['is_sub'] = Config::get('is_sub.value', 'delivery_dist');
        unset($data['client_id'], $data['delivery_id']);

        // 配送记录存在则直接返回
        $distResult = self::get(function ($query) use ($data) {
            $map['user_id'] = ['eq', $data['user_id']];
            $map['order_code'] = ['eq', $data['order_code']];
            $map['delivery_code'] = ['eq', $data['delivery_code']];
            $map['logistic_code'] = ['eq', $data['logistic_code']];

            $query->where($map);
        });

        if ($distResult) {
            return $distResult->toArray();
        }

        // 如开启订阅配送轨迹则向第三方订阅
        if (1 == $data['is_sub']) {
            // 请求正文内容
            $requestData = [
                'ShipperCode'  => $deliveryResult->getAttr('code'),
                'LogisticCode' => $data['logistic_code'],
                'OrderCode'    => $data['order_code'],
                'Remark'       => 'CareyShop',
            ];
            $requestData = json_encode($requestData, JSON_UNESCAPED_UNICODE);

            // 请求系统参数
            $postData = [
                'RequestData' => urlencode($requestData),
                'EBusinessID' => Config::get('api_id.value', 'delivery_dist'),
                'RequestType' => '1008',
                'DataSign'    => \app\common\service\DeliveryDist::getCallbackSign($requestData),
                'DataType'    => '2',
            ];

            $result = Http::httpPost(self::FOLLOW_URL, $postData);
            $result = json_decode($result, true);

            if (!isset($result['Success']) || true != $result['Success']) {
                return $this->setError(isset($result['Reason']) ? $result['Reason'] : '订阅配送轨迹出错');
            }
        }

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 接收推送过来的配送轨迹
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     */
    public function putDeliveryDistData($data)
    {
        $result['callback_return_type'] = 'json';
        $result['is_callback'] = [
            'EBusinessID' => Config::get('api_id.value', 'delivery_dist'),
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
     * 查询实时物流轨迹
     * @access private
     * @param  string $deliveryCode 快递公司编码
     * @param  string $logisticCode 快递单号
     * @return false|array
     */
    private function getOrderTracesByJson($deliveryCode, $logisticCode)
    {
        // 请求正文内容
        $requestData = ['ShipperCode' => $deliveryCode, 'LogisticCode' => $logisticCode];
        $requestData = json_encode($requestData, JSON_UNESCAPED_UNICODE);

        // 请求系统参数
        $postData = [
            'RequestData' => urlencode($requestData),
            'EBusinessID' => Config::get('api_id.value', 'delivery_dist'),
            'RequestType' => '1002',
            'DataSign'    => \app\common\service\DeliveryDist::getCallbackSign($requestData),
            'DataType'    => '2',
        ];

        $result = Http::httpPost(self::TRACK_URL, $postData);
        $result = json_decode($result, true);

        if (!isset($result['Success']) || true != $result['Success']) {
            return false;
        }

        return [
            'state' => $result['State'],
            'trace' => \app\common\service\DeliveryDist::snake($result['Traces']),
        ];
    }

    /**
     * 根据流水号获取配送记录
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getDeliveryDistCode($data)
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

            $query->with($with)->where($map);
        });

        if (false === $result) {
            return false;
        }

        $updata = [];
        $result = $result->toArray();

        foreach ($result as $key => $value) {
            // 忽略已订阅或已签收的配送记录
            if (1 === $value['is_sub'] || 3 === $value['state']) {
                continue;
            }

            $track = $this->getOrderTracesByJson($value['get_delivery_item']['code'], $value['logistic_code']);
            if (false !== $track) {
                $result[$key]['state'] = (int)$track['state'];
                $result[$key]['trace'] = $track['trace'];

                // 如已签收则更新数据
                if (3 == $track['state']) {
                    $updata[] = [
                        'delivery_dist_id' => $value['delivery_dist_id'],
                        'state'            => $track['state'],
                        'trace'            => $track['trace'],
                    ];
                }
            }
        }

        if (!empty($updata)) {
            self::saveAll($updata);
        }

        return $result;
    }

    /**
     * 获取配送记录列表
     * @access public
     * @param  array $data 外部数据
     * @return false|array
     * @throws
     */
    public function getDeliveryDistList($data)
    {
        if (!$this->validateData($data, 'DeliveryDist.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['order_code']) ?: $map['delivery_dist.order_code'] = ['eq', $data['order_code']];
        empty($data['logistic_code']) ?: $map['delivery_dist.logistic_code'] = ['eq', $data['logistic_code']];
        !isset($data['state']) ?: $map['delivery_dist.state'] = ['eq', $data['state']];
        !isset($data['is_sub']) ?: $map['delivery_dist.is_sub'] = ['eq', $data['is_sub']];

        if (!empty($data['timeout'])) {
            $map['delivery_dist.state'] = ['neq', 3];
            $map['delivery_dist.create_time'] = ['elt', time() - ($data['timeout'] * 86400)];
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