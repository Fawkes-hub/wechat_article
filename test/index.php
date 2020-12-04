<?php
require_once '../vendor/autoload.php';
require_once '../src/wechatArticle.php';
require_once '../src/wechatArticleVideo.php';
require_once '../src/tools/tools.php';
require_once '../src/wechatArticleException.php';
require_once '../src/utils/FfmpegUtil.php';

use fawkes\wechat_article\wechatArticleVideo;

try {
    $url = $_GET['url'] ?? "https://mp.weixin.qq.com/s/-kxb8IKY68KkwAqY6f-V8g";
//    $articleClass = new wechatArticle;
//    $article = $articleClass->crawQueryByUrl($url);
////    print_r($article['content']);
//    unset($article['content']); //内容过长影响展示
//    var_dump($article);
//    var_dump($articleClass->title);

    //查看文章内的视频和音频
    $video = new  wechatArticleVideo();
    $ffmpegUtil = new \fawkes\wechat_article\FfmpegUtil();
    $info_arr = $video->actionGetwx($url);
    $video_arr = $info_arr['video'] ?? [];
    $voice_arr = $info_arr['voice'] ?? [];
    //获取下载用户
    $save_info = [];
    $sour_arr = array_merge($video_arr, $voice_arr);
    foreach ($sour_arr as $value) {
        $name = !empty($value['title']) ? $value['title'] . "-" : "" . $value['vid'];
        $url = $value['url'] ?? "";
        if ($url) {
            $videoInfo = $ffmpegUtil->getVideoInfo($url);
            $save_info[] = $videoInfo;
        }
    }
    //查看信息
    var_dump($save_info);
} catch (\Exception $e) {
    var_dump($e);
    var_dump($e->getMessage());
    var_dump($e->getTrace());
}
