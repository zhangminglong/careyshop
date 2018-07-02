<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    cUrl扩展库
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/1
 */

namespace util;

class Http
{
    /**
     * 通过GET方式访问
     * @access public
     * @param  string $url    host
     * @param  bool   $isGzip 是否gzip压缩
     * @return string
     */
    public static function httpGet($url, $isGzip = false)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        if ($isGzip) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept-Encoding: gzip, deflate']);
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        }

        if (!$result = curl_exec($curl)) {
            return 'http error:' . curl_error($curl);
        }

        curl_close($curl);
        return $result;
    }

    /**
     * 通过POST方式访问
     * @access public
     * @param  string $url    host
     * @param  array  $data   发送数据
     * @param  string $type   content-type
     * @param  bool   $isGzip 是否gzip压缩
     * @return string
     */
    public static function httpPost($url, $data, $type = 'form', $isGzip = false)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);

        if (mb_stripos($url, 'https://', null, 'utf-8') !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_string($data)) {
            $postData = $data;
        } else if (is_array($data) && $type === 'json') {
            $postData = json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $encoded = [];
            foreach ($data as $name => $value) {
                $encoded[] = $name . '=' . $value;
            }

            $postData = implode('&', $encoded);
        }

        switch ($type) {
            case 'text':
                $header = 'text/text;charset=utf-8';
                break;
            case 'json':
                $header = 'application/json;charset=utf-8';
                break;
            case 'xml':
                $header = 'text/xml;charset=utf-8';
                break;
            case 'html':
                $header = 'text/html;charset=utf-8';
                break;
            default:
                $header = 'application/x-www-form-urlencoded;charset=utf-8';
        }

        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['content-type: ' . $header]);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($isGzip) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept-Encoding: gzip, deflate']);
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        }

        if (!$result = curl_exec($curl)) {
            return 'http error:' . curl_error($curl);
        }

        curl_close($curl);
        return $result;
    }
}