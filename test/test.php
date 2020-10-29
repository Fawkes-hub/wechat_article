<?php
require_once '../vendor/autoload.php';
require_once '../src/wechatArticle.php';
require_once '../src/wechatArticleVideo.php';
require_once '../src/tools/tools.php';
require_once '../src/wechatArticleException.php';

use fawkes\wechat_article\wechatArticle;
use fawkes\wechat_article\wechatArticleException;
use fawkes\wechat_article\wechatArticleVideo;

try {
    $url = $_GET['url'] ?? "https://mp.weixin.qq.com/s/-kxb8IKY68KkwAqY6f-V8g";
    $articleClass = new wechatArticle;
    $article = $articleClass->crawQueryByUrl($url);
//    print_r($article['content']);
    unset($article['content']); //内容过长影响展示
    var_dump($article);
    var_dump($articleClass->title);

    //查看文章内的视频和音频
    $video = new  wechatArticleVideo();
    $video_arr = $video->actionGetwx($url);
    var_dump($video_arr);
} catch (wechatArticleException $e) {
    var_dump($e->getMessage());
    var_dump($e->getTrace());
}
