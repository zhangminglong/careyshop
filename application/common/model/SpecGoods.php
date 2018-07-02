<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品规格列表模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/21
 */

namespace app\common\model;

class SpecGoods extends CareyShop
{
    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'goods_id',
    ];

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'goods_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'goods_id'  => 'integer',
        'price'     => 'float',
        'store_qty' => 'integer',
    ];

    /**
     * 添加商品规格列表
     * @access public
     * @param  int   $goodsId 商品编号
     * @param  array $data    外部数据
     * @return array|false
     * @throws
     */
    public function addGoodsSpec($goodsId, $data)
    {
        // 处理部分数据,并进行验证
        foreach ($data as $key => $value) {
            $data[$key]['goods_id'] = $goodsId;

            if (!$this->validateData($data[$key], 'SpecGoods')) {
                return false;
            }
        }

        $result = $this->allowField(true)->saveAll($data);
        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }
}