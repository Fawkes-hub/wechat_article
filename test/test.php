<?php
require_once '../vendor/autoload.php';
require_once '../src/wechatArticle.php';
require_once '../src/wechatArticleException.php';

use fawkes\wechat_article\wechatArticle;
use fawkes\wechat_article\wechatArticleException;

try {
    $articleClass = new wechatArticle;
    $article = $articleClass->crawQueryByUrl("https://mp.weixin.qq.com/s/-kxb8IKY68KkwAqY6f-V8g");
//    print_r($article['content']);
    unset($article['content']); //内容过长影响展示
    print_r($article);
    var_dump($articleClass->title);
} catch (wechatArticleException $e) {
    var_dump($e->getMessage());
    var_dump($e->getTrace());
}
