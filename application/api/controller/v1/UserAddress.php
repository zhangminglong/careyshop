<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    收货地址管理控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/30
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class UserAddress extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取指定账号的收货地址列表
            'get.user.address.list'    => ['getAddressList'],
            // 获取指定账号的一个收货地址
            'get.user.address.item'    => ['getAddressItem'],
            // 获取指定账号的默认收货地址信息
            'get.user.address.default' => ['getAddressDefault'],
            // 添加一个收货地址
            'add.user.address.item'    => ['addAddressItem'],
            // 编辑一个收货地址
            'set.user.address.item'    => ['setAddressItem'],
            // 批量删除收货地址
            'del.user.address.list'    => ['delAddressList'],
            // 设置一个收货地址为默认
            'set.user.address.default' => ['setAddressDefault'],
            // 检测是否超出最大添加数量
            'is.user.address.maximum'  => ['isAddressMaximum'],
        ];
    }
}