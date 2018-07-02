<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    订单促销方式模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/5/31
 */

namespace app\common\model;

class PromotionItem extends CareyShop
{
    /**
     * 隐藏属性
     * @var array
     */
    protected $hidden = [
        'promotion_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'promotion_id' => 'integer',
        'quota'        => 'float',
        'settings'     => 'array',
    ];

    /**
     * 添加促销方式
     * @access public
     * @param  array $settings    促销方式配置参数
     * @param  int   $promotionId 促销编号
     * @return array|false
     * @throws
     */
    public function addPromotionItem($settings, $promotionId)
    {
        // 处理外部填入数据并进行验证
        foreach ($settings as $key => $item) {
            if (!$this->validateData($settings[$key], 'PromotionItem.add')) {
                return false;
            }

            foreach ($item['settings'] as $value) {
                if (!$this->validateData($value, 'PromotionItem.settings')) {
                    return false;
                }
            }

            $settings[$key]['promotion_id'] = $promotionId;
        }

        $result = $this->allowField(true)->isUpdate(false)->saveAll($settings);
        if (false !== $result) {
            return $result->toArray();
        }

        return false;
    }
}