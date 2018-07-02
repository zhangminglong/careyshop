<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    二维码管理模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/6/7
 */

namespace app\common\model;

class Qrcode extends CareyShop
{
    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'qrcode_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'qrcode_id' => 'integer',
        'size'      => 'integer',
    ];

    /**
     * 获取一个二维码
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getQrcodeItem($data = [])
    {
        if (!$this->validateData($data, 'Qrcode')) {
            return false;
        }

        if (isset($data['qrcode_id'])) {
            $result = self::get($data['qrcode_id']);
            if ($result) {
                unset($data);
                $data = $result->toArray();
            }
        }

        \app\common\service\Qrcode::getQrcodeItem($data);
        return true;
    }

    /**
     * 添加一个二维码
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addQrcodeItem($data)
    {
        if (!$this->validateData($data, 'Qrcode.add')) {
            return false;
        }

        // 避免无关字段
        unset($data['qrcode_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一个应用
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setQrcodeItem($data)
    {
        if (!$this->validateSetData($data, 'Qrcode.set')) {
            return false;
        }

        $map['qrcode_id'] = ['eq', $data['qrcode_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 获取一个二维码
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getQrcodeConfig($data)
    {
        if (!$this->validateData($data, 'Qrcode.config')) {
            return false;
        }

        $result = self::get($data['qrcode_id']);
        if (false !== $result) {
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 批量删除二维码
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delQrcodeList($data)
    {
        if (!$this->validateData($data, 'Qrcode.del')) {
            return false;
        }

        self::destroy($data['qrcode_id']);

        return true;
    }

    /**
     * 获取二维码列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getQrcodeList($data)
    {
        if (!$this->validateData($data, 'Qrcode.list')) {
            return false;
        }

        // 搜索条件
        $map = [];
        empty($data['name']) ?: $map['name'] = ['like', '%' . $data['name'] . '%'];
        !isset($data['size']) ?: $map['size'] = ['eq', $data['size']];

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
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'qrcode_id';

            $query
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