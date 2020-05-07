<?php
/**
 * 通过querylist微信公众号文章
 *
 * @author fawkes
 * @date: 2020-05-07
 */

namespace fawkes\wechat_article;

use Jaeger\GHttp;
use QL\QueryList;

/**
 * Class wechatArticle
 * @package fawkes\wechat_article
 * @property-read string title 文章标题
 * @property-read string article_author  文章作者
 * @property-read string copyright_stat  文章原创标识
 * @property-read string content  文章正文   ---数据库字段建议 longtext
 * @property-read string article_release_time  文章发布时间  --时间戳
 * @property-read string digest  文章简介
 * @property-read string article_url  文章原始url
 * @property-read string thumb  文章主图
 * @property-read string wx_nickname  文章公众号
 */
class wechatArticle
{
    public $articleInfo = [];

    private $title; //文章标题
    private $article_author; //文章作者
    private $copyright_stat; //文章原创标识
    private $content; //文章正文   ---数据库字段建议 longtext
    private $article_release_time; //文章发布时间  --时间戳
    private $digest; //文章简介
    private $article_url; //文章原始url
    private $thumb; //文章主图
    private $wx_nickname; //文章公众号

    /**
     * 获取文章的内容
     * @param string $name 需要获取的字段名
     * @return mixed|string
     * @throws wechatArticleException
     */
    public function __get(string $name)
    {
        if (empty($this->articleInfo)) {
            throw new wechatArticleException("文章提取异常：内容为空");
        }
        return $this->articleInfo[$name] ?? '';
    }

    /**
     * 采集文章
     * @param string $url
     * @throws wechatArticleException
     */
    public function crawQueryByUrl(string $url)
    {
        $info = $this->queryArticle($url);
        if (empty($info) || empty($info['article_url'])
            || empty($info['title']) || empty($info['content'])) {
            throw new wechatArticleException("文章提取异常：内容为空");
        }
        if (empty($info['article_url'])) {
            $info['article_url'] = $url;
        } else { //截取url中最有效参数 &sn= 之前的
            $info['article_url'] = !empty(explode('&chksm', $info['article_url'])) ?
                explode('&chksm', $info['article_url'])[0] : $info['article_url'];
        }
        $this->articleInfo = $info;
        return $info;
    }

    /**
     * 获取信息
     * @param string $url
     * @throws wechatArticleException
     */
    private function queryArticle(string $url)
    {
        try {
            // 采集规则
            $rules = [
                'title' => ['.rich_media_title', 'text'],
                'article_author' => ['.rich_media_meta_text', 'text'],
                'copyright_stat' => ['#copyright_logo', 'text'],
//            'content' => ['.rich_media_content', 'html'],
                'content' => ['#js_content', 'html'],
            ];
            $AllHtml = GHttp::get($url);
            // 直接匹配出body中的内容
            preg_match('/<body[^>]+>(.+)\s+<\/body>/s', $AllHtml, $arr);
            if (empty($arr)) {
                throw new wechatArticleException("文章提取异常：链接错误");
            }
            $html = $arr[0];
            $query = (new QueryList);
            $data = $query->html($html)->rules($rules)->query()->getData();
            $info = [];
            if ($data->count() > 0) {
                if (isset($data->all()[0])) { //之前是二维数组，后面变成了一维数组 做了个兼容 -2020-03-25
                    $info = current($data->all());
                } else {
                    $info = $data->all();
                }
                $basicContent = self::articleBasicInfo($html, $info); //全部都传入完整内容
                $info = array_merge($info, $basicContent);
            }
            return $info;
        } catch (\Exception $e) {
            throw new wechatArticleException("文章提取异常：{$e->getMessage()}");
        }
    }


    /**
     * 对获取的文章进行二次解析，获取更准确的内容
     * @param string $content 文章详情源码js
     * @param array $article_data 已经获取了的文章标题
     * @return array
     */
    private function articleBasicInfo($content, $article_data = [])
    {
        //获取图文文章内容
        $item = [
            'article_release_time' => 'ct',//发布时间
            'title' => 'msg_title',//标题
            'digest' => 'msg_desc',//描述
            'article_url' => 'msg_link',//文章链接
            'thumb' => 'msg_cdn_url',//封面图片链接
            'wx_nickname' => 'nickname',//公众号名称
        ];
        //针对纯图片文章的格式进行了精准解析
        $imgItem = [
            'article_release_time' => 'ct',//发布时间
            'title' => 'title',//标题
            'digest' => 'title',//描述
            'article_url' => 'msg_link',//文章链接
            'thumb' => 'thumb',//封面图片链接
            'wx_nickname' => 'nick_name',//公众号名称
        ];

        $basicInfo = [];
        foreach ($item as $k => $v) {
            $value = '';
            if (!empty($article_data[$k])) {
                $value = $article_data[$k];
            } else {
                $pattern = '/ var ' . $v . ' = "(.*?)";/s';
                preg_match_all($pattern, $content, $matches);
                if (array_key_exists(1, $matches) && !empty($matches[1][0])) {
                    $value = trim(self::htmlTransform($matches[1][0]));
                }
                if ($value == '') {
                    if ($k == 'thumb') { //文章类型的就是用主图
                        $pattern = '/<img src="(.*?)"/s';
                        preg_match_all($pattern, $content, $matches);
                        if (array_key_exists(1, $matches) && !empty($matches[1][0])) {
                            $value = trim(self::htmlTransform($matches[1][0]));
                        }
                    } else {
                        $pattern = '/d.' . $imgItem[$k] . ' = "(.*?)";/s';
                        preg_match_all($pattern, $content, $matches);
                        if (array_key_exists(1, $matches) && !empty($matches[1][0])) {
                            $value = trim(self::htmlTransform($matches[1][0]));
                        }
                    }
                }
            }
            $basicInfo[$k] = $value;
        }
        return $basicInfo;
    }

    /**
     * 特殊字符转换
     * @param  $string
     * @return $string
     * @author bignerd
     * @since  2016-08-16T17:30:52+0800
     */
    private function htmlTransform($string)
    {
        $string = str_replace('&quot;', '"', $string);
        $string = str_replace('\x26amp;', '&', $string);
        $string = str_replace('&amp;', '&', $string);
        $string = str_replace('amp;', '', $string);
        $string = str_replace('&lt;', '<', $string);
        $string = str_replace('&gt;', '>', $string);
        $string = str_replace('&nbsp;', ' ', $string);
        $string = str_replace("\\", '', $string);
        return $string;
    }

}