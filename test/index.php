<?php
require_once '../vendor/autoload.php';
require_once '../src/wechatArticle.php';
require_once '../src/wechatArticleVideo.php';
require_once '../src/tools/tools.php';
require_once '../src/wechatArticleException.php';
require_once '../src/utils/FfmpegUtil.php';

use fawkes\wechat_article\wechatArticle;
use fawkes\wechat_article\wechatArticleVideo;

try {
    //设置保存路径
    define('APP_PATH', __DIR__ . '/../upload');

//    $url = $_GET['url'] ?? "https://mp.weixin.qq.com/s/dZlOPw5ni9nDgcbC6VSo8g";
    $url = $_GET['url'] ?? "https://mp.weixin.qq.com/s/zcQkT2FatkuEo2bUsq6dqw";
    $articleClass = new wechatArticle;
    //通过HTML页面抓取内容
//    $article = $articleClass->crawQueryByUrl($url);
//    var_dump($article);
    //通过接口返回内容
    $article = $articleClass->crawJSONQueryByUrl($url);
    var_dump($article);

    //查看文章内的视频和音频
    $video = new  wechatArticleVideo();
    $ffmpegUtil = new \fawkes\wechat_article\FfmpegUtil();
    $resourceInformation = $video->actionGetwx($url);


    //获取资源信息
    $result = [];
    foreach ($resourceInformation as $resource) {
        try {
            $data = $resource['data'];
            $vid = $data['vid'];
            $origin_url_path = $data['url'];
            $resourceValue = $ffmpegUtil->uploadKs3($origin_url_path, $vid);
            if (!empty($resourceValue['code'])) {
                return $resourceValue;
            }
            $result[] = [
                'type' => $resource['type'],
                'vid' => $vid,
                'file_path' => $resourceValue['file_path'] ?? '',
                'duration' => $resourceValue['duration'] ?? 0,
                'cover' => $resourceValue['cover'] ?? '',
                'name' => $data['name'] ?? ''
            ];
        } catch (\Exception $e) {
            $result[] = [
                'type' => 'error',
                'vid' => $vid,
                'error' => $e->getFile() . $e->getLine() . $e->getMessage()
            ];
        }
    }
    //查看信息
    var_dump($result);
} catch (\Exception $e) {
    var_dump($e);
    var_dump($e->getMessage());
    var_dump($e->getTrace());
}
