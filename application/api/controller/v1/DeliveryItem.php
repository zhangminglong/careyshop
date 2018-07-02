<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    快递公司控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/4/25
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class DeliveryItem extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 添加一个快递公司
            'add.delivery.company.item'    => ['addCompanyItem'],
            // 编辑一个快递公司
            'set.delivery.company.item'    => ['setCompanyItem'],
            // 批量删除快递公司
            'del.delivery.company.list'    => ['delCompanyList'],
            // 获取一个快递公司
            'get.delivery.company.item'    => ['getCompanyItem'],
            // 查询快递公司编码是否已存在
            'unique.delivery.company.code' => ['uniqueCompanyCode'],
            // 获取快递公司列表
            'get.delivery.company.list'    => ['getCompanyList'],
            // 获取快递公司选择列表
            'get.delivery.company.select'  => ['getCompanySelect'],
            // 复制一个快递公司为"热门类型"
            'copy.delivery.company.hot'    => ['copyCompanyHot'],
        ];
    }
}