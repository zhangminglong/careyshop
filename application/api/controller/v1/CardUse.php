<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    购物卡使用控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/11/21
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class CardUse extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 批量设置购物卡是否有效
            'set.card.use.invalid' => ['setCardUseInvalid'],
            // 导出生成的购物卡
            'get.card.use.export'  => ['getCardUseExport'],
            // 绑定购物卡
            'bind.card.use.item'   => ['bindCardUseItem'],
            // 获取已绑定的购物卡
            'get.card.use.list'    => ['getCardUseList'],
            // 获取可合并的购物卡列表
            'get.card.use.merge'   => ['getCardUseMerge'],
            // 相同购物卡进行余额合并
            'set.card.use.merge'   => ['setCardUseMerge'],
            // 根据商品Id列出可使用的购物卡
            'get.card.use.select'  => ['getCardUseSelect'],
            // 验证购物卡是否可使用
            'get.card.use.check'   => ['getCardUseCheck'],
        ];
    }
}