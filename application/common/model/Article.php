<?php
/**
 * @copyright   Copyright (c) http://careyshop.cn All rights reserved.
 *
 * CareyShop    文章管理模型
 *
 * @author      zxm <252404501@qq.com>
 * @date        2017/3/29
 */

namespace app\common\model;

class Article extends CareyShop
{
    /**
     * 是否需要自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 只读属性
     * @var array
     */
    protected $readonly = [
        'article_id',
    ];

    /**
     * 字段类型或者格式转换
     * @var array
     */
    protected $type = [
        'article_id'     => 'integer',
        'article_cat_id' => 'integer',
        'is_top'         => 'integer',
        'status'         => 'integer',
        'page_views'     => 'integer',
    ];

    /**
     * hasOne cs_article_cat
     * @access public
     * @return mixed
     */
    public function getArticleCat()
    {
        return $this
            ->hasOne('ArticleCat', 'article_cat_id', 'article_cat_id')
            ->field('cat_name,cat_type')
            ->setEagerlyType(0);
    }

    /**
     * 添加一篇文章
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function addArticleItem($data)
    {
        if (!$this->validateData($data, 'Article')) {
            return false;
        }

        // 避免无关字段
        unset($data['article_id']);

        if (false !== $this->allowField(true)->save($data)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 编辑一篇文章
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function setArticleItem($data)
    {
        if (!$this->validateSetData($data, 'Article.set')) {
            return false;
        }

        $map['article_id'] =['eq', $data['article_id']];
        if (false !== $this->allowField(true)->save($data, $map)) {
            return $this->toArray();
        }

        return false;
    }

    /**
     * 批量删除文章
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function delArticleList($data)
    {
        if (!$this->validateData($data, 'Article.del')) {
            return false;
        }

        self::destroy($data['article_id']);

        return true;
    }

    /**
     * 获取一篇文章
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getArticleItem($data)
    {
        if (!$this->validateData($data, 'Article.item')) {
            return false;
        }

        $result = self::get(function ($query) use ($data) {
            $map['article_id'] = ['eq', $data['article_id']];
            is_client_admin() ?: $map['status'] = ['eq', 1];

            $query->where($map);
        });

        if (false !== $result) {
            is_null($result) ?: $result->setInc('page_views');
            return is_null($result) ? null : $result->toArray();
        }

        return false;
    }

    /**
     * 获取文章列表
     * @access public
     * @param  array $data 外部数据
     * @return array|false
     * @throws
     */
    public function getArticleList($data)
    {
        if (!$this->validateData($data, 'Article.list')) {
            return false;
        }

        // 获取分类Id,包括子分类
        $catIdList = [];
        if (isset($data['article_cat_id'])) {
            $catIdList[] = (int)$data['article_cat_id'];
            $articleCat = ArticleCat::getArticleCatList($data['article_cat_id']);

            foreach ($articleCat as $value) {
                $catIdList[] = $value['article_cat_id'];
            }
        }

        // 搜索条件
        $map['article.status'] = ['eq', 1];
        empty($catIdList) ?: $map['article.article_cat_id'] = ['in', $catIdList];
        empty($data['title']) ?: $map['article.title'] = ['like', '%' . $data['title'] . '%'];

        // 后台管理搜索
        if (is_client_admin()) {
            unset($map['article.status']);
            !isset($data['status']) ?: $map['article.status'] = ['eq', $data['status']];
            !isset($data['is_top']) ?: $map['article.is_top'] = ['eq', $data['is_top']];
            empty($data['keywords']) ?: $map['article.keywords'] = ['like', '%' . $data['keywords'] . '%'];
        }

        $totalResult = $this->with('getArticleCat')->where($map)->count();
        if ($totalResult <= 0) {
            return ['total_result' => 0];
        }

        $result = self::all(function ($query) use ($data, $map) {
            // 翻页页数
            $pageNo = isset($data['page_no']) ? $data['page_no'] : 1;

            // 每页条数
            $pageSize = isset($data['page_size']) ? $data['page_size'] : config('paginate.list_rows');

            // 排序方式
            $orderType = !empty($data['order_type']) ? $data['order_type'] : 'desc';

            // 排序的字段
            $orderField = !empty($data['order_field']) ? $data['order_field'] : 'article_id';

            // 文章前后台置顶处理
            is_client_admin() ?: $order['article.is_top'] = 'desc';
            $order['article.' . $orderField] = $orderType;

            $query
                ->field('content', true)
                ->with('getArticleCat')
                ->where($map)
                ->order($order)
                ->page($pageNo, $pageSize);
        });

        if (false !== $result) {
            return ['items' => $result->toArray(), 'total_result' => $totalResult];
        }

        return false;
    }

    /**
     * 批量设置文章置顶
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setArticleTop($data)
    {
        if (!$this->validateData($data, 'Article.top')) {
            return false;
        }

        $map['article_id'] = ['in', $data['article_id']];
        if (false !== $this->save(['is_top' => $data['is_top']], $map)) {
            return true;
        }

        return false;
    }

    /**
     * 批量设置文章是否显示
     * @access public
     * @param  array $data 外部数据
     * @return bool
     */
    public function setArticleStatus($data)
    {
        if (!$this->validateData($data, 'Article.status')) {
            return false;
        }

        $map['article_id'] = ['in', $data['article_id']];
        if (false !== $this->save(['status' => $data['status']], $map)) {
            return true;
        }

        return false;
    }
}