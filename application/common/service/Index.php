<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    API协助接口服务层
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/26
 */

namespace app\common\service;

use think\Cache;

class Index extends CareyShop
{
    /**
     * 调整最优状态(正式环境有效)
     * @access public
     * @return array|false
     */
    public static function setSystemOptimize()
    {
        $shell = [
            'autoload'   => 'optimize:autoload',    // 生成类库映射文件
            'route'      => 'optimize:route',       // 生成路由缓存
            'config'     => 'optimize:config',      // 生成配置缓存
            'config_api' => 'optimize:config api',  // 生成配置缓存(api模块)
            'schema'     => 'optimize:schema',      // 生成数据表字段缓存
        ];

        $result = [];
        $rootPath = ROOT_PATH . 'careyshop';

        foreach ($shell as $key => $value) {
            $output = shell_exec(sprintf('php %s %s', $rootPath, $value));
            $result[] = $key . ':' . chop($output);
        }

        return !empty($result) ? $result : false;
    }

    /**
     * 清空所有缓存
     * @access public
     * @return bool
     */
    public static function clearCacheAll()
    {
        return Cache::clear();
    }

    /**
     * 获取系统版本号
     * @access public
     * @return array
     */
    public static function getVersion()
    {
        return ['version' => get_version()];
    }
}