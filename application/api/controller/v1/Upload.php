<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    资源上传控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/19
 */

namespace app\api\controller\v1;

use app\api\controller\CareyShop;

class Upload extends CareyShop
{
    /**
     * 方法路由器
     * @access protected
     * @return array
     */
    protected static function initMethod()
    {
        return [
            // 获取上传模块列表
            'get.upload.module'   => ['getUploadModule', 'app\common\service\Upload'],
            // 获取上传地址
            'get.upload.url'      => ['getUploadUrl', 'app\common\service\Upload'],
            // 获取上传Token
            'get.upload.token'    => ['getUploadToken', 'app\common\service\Upload'],
            // 替换上传资源
            'replace.upload.item' => ['replaceUploadItem', 'app\common\service\Upload'],
            // 资源上传请求(第三方OSS只能单文件直传方式上传)
            'add.upload.list'     => ['addUploadList', 'app\common\service\Upload'],
            // 接收第三方推送数据
            'put.upload.data'     => ['putUploadData', 'app\common\service\Upload'],
        ];
    }
}