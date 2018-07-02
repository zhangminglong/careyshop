<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    商城前台控制器
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/03/23
 */

namespace app\index\controller;

class Index
{
    public function index()
    {
        $html = '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "微软雅黑", serif; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <p><span style="font-size:30px">欢迎使用CareyShop商城框架系统 - Index</span></p></div><script>var _hmt=_hmt||[];(function(){var hm=document.createElement("script");hm.src="https://hm.baidu.com/hm.js?e325e60ca4cd358f2b424f5aecb8021a";var s=document.getElementsByTagName("script")[0];s.parentNode.insertBefore(hm,s)})();console.log(_hmt);</script>';
        return $html;
    }
}