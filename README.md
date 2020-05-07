# wechat_article
基于QueryList的微信公众号文章获取工具--
可以获得：文章标题、文章作者、文章原创标识、文章正文、文章发布时间、文章简介、文章原始url、文章主图、文章公众号名称

#### 安装教程

```bash
composer require fawkes/wechat_article
```

#### 使用说明

```php
use fawkes\wechat_article\wechatArticle;
use fawkes\wechat_article\wechatArticleException;

$articleInfo = new wechatArticle();
try {
    $article = $articleInfo->crawQueryByUrl("https://mp.weixin.qq.com/s/YuhmAYMLgCxktxVo1bgmCQ");
    print_r($article['content']);
    unset($article['content']); //调试上会有一些不方便
    var_dump($article);
} catch (wechatArticleException $e) {
    var_dump($e->getMessage());
}
/**
    //可以获得的字段
      $title; //文章标题
      $article_author; //文章作者
      $copyright_stat; //文章原创标识
      $content; //文章正文   ---数据库字段建议 longtext
      $article_release_time; //文章发布时间  --时间戳
      $digest; //文章简介
      $article_url; //文章原始url
      $thumb; //文章主图
      $wx_nickname; //文章公众号
**/
```