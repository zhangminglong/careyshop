<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    本地上传
 *
 * @author      zxm <252404501@qq.com>
 * @date        2018/1/22
 */

namespace oss\careyshop;

use app\common\model\Storage;
use oss\Upload as UploadBase;
use think\Config;
use think\File;
use think\Image;
use think\Url;

class Upload extends UploadBase
{
    /**
     * 模块名称
     * @var string
     */
    const NAME = 'CareyShop(本地上传)';

    /**
     * 模块
     * @var string
     */
    const MODULE = 'careyshop';

    /**
     * 最大上传字节数
     * @var int
     */
    protected static $maxSize;

    /**
     * 最大上传信息
     * @var string
     */
    protected static $maxSizeInfo;

    /**
     * 构造函数
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setFileMaxSize();
    }

    /**
     * 设置最大可上传大小
     * @access private
     * @return void
     */
    private function setFileMaxSize()
    {
        if (is_null(self::$maxSize)) {
            $serverSize = ini_get('upload_max_filesize');
            $userMaxSize = Config::get('file_size.value', 'upload');

            if (!empty($userMaxSize)) {
                if (string_to_byte($userMaxSize) < string_to_byte($serverSize)) {
                    self::$maxSizeInfo = $userMaxSize;
                    self::$maxSize = string_to_byte($userMaxSize);
                    return;
                }
            }

            self::$maxSizeInfo = $serverSize;
            self::$maxSize = string_to_byte($serverSize);
        }
    }

    /**
     * 获取上传地址
     * @access public
     * @return array
     */
    public function getUploadUrl()
    {
        $uploadUrl = Url::bUild('/api/v1/upload', ['method' => 'add.upload.list'], true, true);
        $param = [
            ['name' => 'x:replace', 'type' => 'hidden', 'default' => $this->replace],
            ['name' => 'x:parent_id', 'type' => 'hidden', 'default' => 0],
            ['name' => 'x:filename', 'type' => 'hidden', 'default' => ''],
            ['name' => 'token', 'type' => 'hidden', 'default' => ''],
            ['name' => 'file', 'type' => 'file', 'default' => ''],
        ];

        return ['upload_url' => $uploadUrl, 'module' => self::MODULE, 'param' => $param];
    }

    /**
     * 获取上传Token
     * @access public
     * @param  string $replace 替换资源(path)
     * @return array
     */
    public function getToken($replace = '')
    {
        empty($replace) ?: $this->replace = $replace;
        $response['upload_url'] = $this->getUploadUrl();
        $response['token'] = self::MODULE;

        return ['token' => $response, 'expires' => 0];
    }

    /**
     * 上传资源
     * @access public
     * @return array|false
     */
    public function uploadFiles()
    {
        // 检测请求数据总量不得超过服务器设置值
        $posMaxSize = ini_get('post_max_size');
        if ($this->request->server('CONTENT_LENGTH') > string_to_byte($posMaxSize)) {
            return $this->setError('附件合计总大小不能超过' . $posMaxSize);
        }

        // 获取上传资源数据
        $filesData = [];
        $files = $this->request->file();

        if (is_null($files)) {
            return $this->setError('请选择需要上传的附件');
        }

        if ($this->request->has('x:replace', 'param', true) && count($files) > 1) {
            return $this->setError('替换资源只能上传单个文件');
        }

        foreach ($files as $value) {
            if ($this->request->has('x:replace', 'param', true) && count($value) > 1) {
                return $this->setError('替换资源只能上传单个文件');
            }

            if (is_object($value)) {
                $result = $this->saveFile((object)$value);
                if (is_array($result)) {
                    $filesData[] = $result;
                } else {
                    $filesData[] = ['status' => 500, 'message' => $result];
                }
            } else if (is_array($value)) {
                foreach ($value as $item) {
                    $result = $this->saveFile($item);
                    if (is_array($result)) {
                        $filesData[] = $result;
                    } else {
                        $filesData[] = ['status' => 500, 'message' => $result];
                    }
                }
            }
        }

        return $filesData;
    }

    /**
     * 接收第三方推送数据
     * @access public
     * @return false
     */
    public function putUploadData()
    {
        return $this->setError(self::NAME . '模块异常访问!');
    }

    /**
     * 保存资源并写入库
     * @access private
     * @param  object $file 上传文件对象
     * @return array|string
     * @throws
     */
    private function saveFile($file)
    {
        if (!$file || !$file instanceof File) {
            return '请选择需要上传的附件';
        }

        // 非法附件检测
        if (in_array($file->getMime(), ['text/x-php', 'text/html'])) {
            return '禁止上传非法附件';
        }

        // 创建验证规则数据
        $rule = [
            'size' => self::$maxSize,
            'ext'  => Config::get('image_ext.value', 'upload') . ',' . Config::get('file_ext.value', 'upload'),
        ];

        // 保存附件到项目目录
        $filePath = DS . 'uploads' . DS . 'files' . DS;
        if ($this->request->has('x:replace', 'param', true)) {
            $movePath = pathinfo($this->request->param('x:replace'));
            $filePath = $movePath['dirname'] . DS;
            $info = $file->validate($rule)->move(ROOT_PATH . 'public' . $filePath, $movePath['basename']);
        } else {
            $info = $file->validate($rule)->move(ROOT_PATH . 'public' . $filePath);
        }

        if (false === $info) {
            return $file->getError();
        }

        // 判断是否为图片
        list($width, $height) = @getimagesize($info->getPathname());
        $isImage = (int)$width > 0 && (int)$height > 0;

        // 附件相对路径,并统一斜杠为'/'
        $path = APP_PUBLIC_PATH . $filePath . $info->getSaveName();
        $path = str_replace('\\', '/', $path);

        // 自定义附件名
        $filename = $this->request->param('x:filename');

        // 写入库数据准备
        $data = [
            'parent_id' => (int)$this->request->param('x:parent_id', 0),
            'name'      => !empty($filename) ? $filename : $file->getInfo('name'),
            'mime'      => $file->getInfo('type'),
            'ext'       => mb_strtolower($info->getExtension(), 'utf-8'),
            'size'      => $info->getSize(),
            'pixel'     => $isImage ? ['width' => $width, 'height' => $height] : [],
            'hash'      => $info->hash('sha1'),
            'path'      => $path,
            'url'       => $this->request->host() . $path . '?type=' . self::MODULE,
            'protocol'  => self::MODULE,
            'type'      => $isImage ? 0 : 1,
        ];

        if ($this->request->has('x:replace', 'param', true)) {
            unset($data['parent_id']);
        }

        $map['path'] = ['eq', $data['path']];
        $map['protocol'] = ['eq', self::MODULE];
        $map['type'] = ['neq', 2];

        $storageDb = new Storage();
        $result = $storageDb->field('mime,path,cover,sort', true)->where($map)->find();

        if (false === $result) {
            return $this->setError($storageDb->getError());
        }

        if (!is_null($result)) {
            // 删除被替换资源的缩略图文件
            if (0 === $result->getAttr('type')) {
                $thumb = ROOT_PATH . 'public' . $data['path'];
                $thumb = str_replace(IS_WIN ? '/' : '\\', DS, $thumb);

                $this->clearThumb($thumb);
            }

            // 替换资源进行更新
            if (false === $result->save($data)) {
                return $this->setError($storageDb->getError());
            }

            $ossResult = $result->hidden(['mime'])->setAttr('status', 200)->toArray();
        } else {
            // 插入新记录
            if (false === $storageDb->isUpdate(false)->save($data)) {
                return $this->setError($storageDb->getError());
            }

            $ossResult = $storageDb->hidden(['mime'])->setAttr('status', 200)->toArray();
        }

        $ossResult['oss'] = Config::get('oss.value', 'upload');
        return $ossResult;
    }

    /**
     * 根据请求参数组合成hash值
     * @access private
     * @param  array  $param 请求参数
     * @param  string $path  资源路径
     * @return string|false
     */
    private function getFileSign($param, $path)
    {
        if (!is_file($path)) {
            return false;
        }

        $sign = sha1_file($path);
        foreach ($param as $key => $value) {
            switch ($key) {
                case 'size':
                case 'crop':
                    if (is_array($value) && count($value) <= 2) {
                        $sign .= ($key . implode('', $value));
                    }
                    break;

                case 'format':
                case 'quality':
                    if (is_string($value) || is_numeric($value)) {
                        $sign .= ($key . $value);
                    }
                    break;
            }
        }

        return hash('sha1', $sign);
    }

    /**
     * 组合新的URL或PATH
     * @access private
     * @param  string $fileName 文件名
     * @param  string $suffix   后缀
     * @param  array  $fileInfo 原文件信息
     * @param  array  $urlArray 外部URL信息
     * @param  string $type     新的路径方式
     * @return string
     */
    private function getNewUrl($fileName, $suffix, $fileInfo, $urlArray = null, $type = 'url')
    {
        if ($type === 'url') {
            $url = $urlArray['scheme'] . '://';
            $url .= $urlArray['host'];
            $url .= $fileInfo['dirname'];
            $url .= '/' . $fileName;
            $url .= '.' . $suffix;
        } else if ($type === 'path') {
            $url = ROOT_PATH . 'public';
            $url .= str_replace(IS_WIN ? '/' : '\\', DS, $fileInfo['dirname']);
            $url .= DS . $fileName;
            $url .= '.' . $suffix;
        } else {
            $url = ROOT_PATH . 'public';
            $url .= str_replace(IS_WIN ? '/' : '\\', DS, $fileInfo['dirname']);
            $url .= DS . $fileInfo['basename'];
        }

        return $url;
    }

    /**
     * 获取资源缩略图实际路径
     * @access public
     * @param  array $urlArray 路径结构
     * @return string
     */
    public function getThumbUrl($urlArray)
    {
        // 获取自定义后缀,不合法则使用原后缀
        $fileInfo = pathinfo($urlArray['path']);
        $suffix = $fileInfo['extension'];
        $param = $this->request->param();
        $extension = ['jpg', 'png', 'svg', 'gif', 'bmp', 'tiff', 'webp'];
        $url = $this->getNewUrl($fileInfo['filename'], $fileInfo['extension'], $fileInfo, $urlArray);

        // 非图片资源则直接返回
        if (!in_array($fileInfo['extension'], $extension)) {
            return $url;
        }

        // 获取源文件位置,并且生成缩略图文件名,验证源文件是否存在
        $source = $this->getNewUrl('', '', $fileInfo, null, null);
        $fileSign = $this->getFileSign($param, $source);

        if (false === $fileSign) {
            return $url . '?error=' . rawurlencode('资源文件不存在');
        }

        // 处理输出格式
        if (!empty($param['type'])) {
            if (in_array($param['type'], $extension)) {
                $suffix = $param['type'];
            }
        }

        // 如果缩略图已存在则直接返回(转成缩略图路径)
        $fileInfo['dirname'] .= '/' . $fileInfo['filename'];
        if (is_file($this->getNewUrl($fileSign, $suffix, $fileInfo, null, 'path'))) {
            return $this->getNewUrl($fileSign, $suffix, $fileInfo, $urlArray);
        }

        // 检测尺寸是否正确
        list($sWidth, $sHeight) = @array_pad(isset($param['size']) ? $param['size'] : [], 2, 0);
        list($cWidth, $cHeight) = @array_pad(isset($param['crop']) ? $param['crop'] : [], 2, 0);

//        if (!$sWidth && !$sHeight) {
//            return $url;
//        }

        try {
            // 创建图片实例(并且是图片才创建缩略图文件夹)
            $imageFile = Image::open($source);

            $thumb = ROOT_PATH . 'public' . $fileInfo['dirname'];
            $thumb = str_replace(IS_WIN ? '/' : '\\', DS, $thumb);
            !is_dir($thumb) && mkdir($thumb, 0755, true);

            // 处理缩放尺寸、裁剪尺寸
            foreach ($param as $key => $value) {
                switch ($key) {
                    case 'size':
                        $sWidth <= 0 && $sWidth = $sHeight;
                        $sHeight <= 0 && $sHeight = $sWidth;
                        $imageFile->thumb($sWidth, $sHeight, Image::THUMB_PAD);
                        break;

                    case 'crop':
                        $cWidth > $imageFile->width() && $cWidth = $imageFile->width();
                        $cHeight > $imageFile->height() && $cHeight = $imageFile->height();
                        $cWidth <= 0 && $cWidth = $imageFile->width();
                        $cHeight <= 0 && $cHeight = $imageFile->height();
                        $x = ($imageFile->width() - $cWidth) / 2;
                        $y = ($imageFile->height() - $cHeight) / 2;
                        $imageFile->crop($cWidth, $cHeight, $x, $y);
                        break;
                }
            }

            // 处理图片质量
            $quality = 90;
            if (!empty($param['quality'])) {
                $quality = $param['quality'] > 100 ? 100 : $param['quality'];
            }

            // 保存缩略图片
            $savePath = $this->getNewUrl($fileSign, $suffix, $fileInfo, null, 'path');
            $imageFile->save($savePath, $suffix, $quality);
        } catch (\Exception $e) {
            return $url . '?error=' . rawurlencode($e->getMessage());
        }

        return $this->getNewUrl($fileSign, $suffix, $fileInfo, $urlArray);
    }

    /**
     * 批量删除资源
     * @access public
     * @return bool
     */
    public function delFileList()
    {
        foreach ($this->delFileList as $value) {
            $path = ROOT_PATH . 'public' . $value;
            $path = str_replace(IS_WIN ? '/' : '\\', DS, $path);

            $this->clearThumb($path);
            is_file($path) && @unlink($path);
        }

        return true;
    }

    /**
     * 清除缩略图文件夹
     * @access private
     * @param  string $path 路径
     * @return void
     */
    private function clearThumb($path)
    {
        // 去掉后缀名,获得目录路径
        $thumb = mb_substr($path, 0, mb_strripos($path, '.', null, 'utf-8'), 'utf-8');

        if (is_dir($thumb) && $this->checkImg($path)) {
            $matches = glob($thumb . DS . '*');
            is_array($matches) && @array_map('unlink', $matches);
            @rmdir($thumb);
        }
    }

    /**
     * 验证是否为图片
     * @access private
     * @param  string $path 路径
     * @return bool
     */
    private function checkImg($path)
    {
        $info = @getimagesize($path);
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            return false;
        }

        return true;
    }
}