<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    OSS基类
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/22
 */

namespace oss;

use think\Request;

abstract class Upload
{
    /**
     * 错误信息
     * @var string
     */
    protected $error = '';

    /**
     * @var \think\Request Request 实例
     */
    protected $request;

    /**
     * 待删除资源列表
     * @var array
     */
    protected $delFileList = [];

    /**
     * 待删除资源Id列表
     * @var array
     */
    protected $delFileIdList = [];

    /**
     * 资源替换
     * @var string
     */
    protected $replace = '';

    /**
     * 构造函数
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->request = Request::instance();
    }

    /**
     * 返回错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置错误信息
     * @access public
     * @param  string $error 错误信息
     * @return false
     */
    public function setError($error)
    {
        $this->error = $error;
        return false;
    }

    /**
     * 添加待删除资源
     * @access public
     * @param  string $path 资源路径
     * @return void
     */
    public function addDelFile($path)
    {
        $this->delFileList[] = $path;
    }

    /**
     * 添加待删除资源Id
     * @access public
     * @param  mixed $id 资源Id
     * @return void
     */
    public function addDelFileId($id)
    {
        $this->delFileIdList[] = $id;
    }

    /**
     * 获取待删除资源Id列表
     * @access public
     * @return array
     */
    public function getDelFileIdList()
    {
        return $this->delFileIdList;
    }

    /**
     * 查询条件数据转字符
     * @access public
     * @param  array $options 查询条件
     * @return string
     */
    protected function queryToString($options = [])
    {
        $temp = [];
        foreach ($options as $key => $value) {
            if (is_string($key) && !is_array($value)) {
                $temp[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }

        return implode('&', $temp);
    }

    /**
     * 获取上传地址
     * @access protected
     * @return array
     */
    abstract protected function getUploadUrl();

    /**
     * 获取上传Token
     * @access protected
     * @param  string $replace 替换资源(path)
     * @return array
     */
    abstract protected function getToken($replace = '');

    /**
     * 接收第三方推送数据
     * @access protected
     * @return array
     */
    abstract protected function putUploadData();

    /**
     * 上传资源
     * @access protected
     * @return array
     */
    abstract protected function uploadFiles();

    /**
     * 获取资源缩略图实际路径
     * @access protected
     * @param  array $urlArray 路径结构
     * @return void
     */
    abstract protected function getThumbUrl($urlArray);

    /**
     * 批量删除资源
     * @access protected
     * @return bool
     */
    abstract protected function delFileList();
}