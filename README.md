# wechat_article
基于QueryList的微信公众号文章获取工具

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
```