<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商品规格图片模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/21
 */

namespace app\common\model;

class SpecImage extends CareyShop
{
    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'goods_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'goods_id'     => 'integer',
        'spec_item_id' => 'integer',
    ];

    /**
     * 添加商品规格图片
     * @access public
     * @param  int   $goodsId 商品编号
     * @param  array $data    外部数据
     * @return array|false
     * @throws
     */
    public function addSpecImage($goodsId, $data)
    {
        // 处理部分数据,并进行验证
        foreach ($data as $key => $value) {
            $data[$key]['goods_id'] = $goodsId;

            if (!$this->validateData($data[$key], 'SpecImage')) {
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