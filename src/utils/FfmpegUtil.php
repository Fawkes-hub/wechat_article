<?php


namespace fawkes\wechat_article;


class FfmpegUtil
{
    /*
 * ffmpeg直接获取视频信息
 *
 * */
//获取视频信息
    function getVideoInfo($video_file)
    {
        ob_start(); // 使用输出缓冲，获取ffmpeg所有输出内容
        $str = "ffprobe -v quiet -print_format json -show_format -show_streams '" . $video_file . "'";
        system($str);
        $jsonData = json_decode(ob_get_contents(), true);
        ob_end_clean(); // 使用输出缓冲，清除ffmpeg所有输出内容
        var_dump($jsonData);
        $res = [];
        $streams = end($jsonData['streams']);
        $res['width'] = $streams['width'] ?? 0;                     //宽度
        $res['height'] = $streams['height'] ?? 0;                   //长度
        $res['duration'] = $streams['duration'] ?? 0;              //获取视频长度
        $res['tagsCount'] = count($streams['tags'] ?? []);                  //获取数量
        $format = $jsonData['format'];
        $res['videoSize'] = sprintf("%.1f ", ($format['size'] / 1024 / 1000)) . "MB";     //获取视频大小
        if ($res['tagsCount'] == 4) {
            $res['rotate'] = $streams['tags']['rotate'];
//            $res['imgUrl']=$this->getVideoCover($video_file, '', $res['width'], $res['height']);
        } else {
            $res['rotate'] = "";
//            $res['imgUrl']=$this->getVideoCover($video_file, '', $res['height'], $res['width']);
        }
        return $res;
    }


    /**
     * 保存video
     * @param string $video_url 视频地址
     * @param string $save_path 视频的保存路径 需要带上完整的名称和后缀
     */
    public function saveVideo(string $video_url, string $save_path)
    {
        exec("ffmpeg -i '$video_url' -c copy '" . $save_path . "'  2>&1", $out, $status);
        return $out;
    }

    /*
     * 获得视频文件的缩略图
     * @file 视频地址
     * ，url 缩略图地址
     * */

    function getVideoCover($file, $time, $width = 0, $height = 0)
    {
        $sveFile = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'videoImg/';
        $name = time() . mt_rand(0, 99) . ".png";
        $time = $time ? $time : '2';//默认截取第一秒第一帧
        //   $str=exec("/usr/local/ffmpeg/bin/ffmpeg  -ss 10 -i ".$file." 2>&1 -y -f mjpeg  -vframes 1 -s 352x240 ".$_SERVER['DOCUMENT_ROOT']."/MVC/include/img/b-%03d.jpg",$out,$status);
        //     ffmpeg -ss 5 -i http://www.***.com/intangibleApp/mp4/2018-01-08-56.mp4 -vf rotate=0 -y -f mjpeg  -vframes 1 -s 1242*699 /home/test/你的项目/public/uploads/videoImg/%3d.png
//    $str= "ffmpeg -ss ".$time." -i ".$file."   -y -f mjpeg  -vframes 1 -s ".$width."*".$height.$sveFile.$name;
        $str = "ffmpeg -ss " . $time . " -i " . $file . "  -y -f mjpeg  -vframes 1 -s " . $height . "*" . $width . "  " . $sveFile . $name;

        $result = system($str);
        $saveUrl = $sveFile . $name;
        if ($_SERVER['HTTP_HOST'] == "www.***.com") {
            $fileUrl = "feiyiproject/videoImg/";       //正式地址图片路径
        } else {
            $fileUrl = "intangibleApp/videoImg/";       //测试地址路径
        }
        vendor('aliyun.autoload');
        $accessKeyId = "*******";//去阿里云后台获取秘钥
        $accessKeySecret = "********";//去阿里云后台获取秘钥
        $endpoint = "oss-cn-shenzhen.aliyuncs.com";//你的阿里云OSS地址
        $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket = "阿里云oss空间";//oss中的文件上传空间
        $object = $fileUrl . $name;//想要保存文件的名称
        $fname = 'http://image.***.com/' . ltrim($object, './');

        try {
            $ossClient->uploadFile($bucket, $object, $saveUrl);
            if (file_exists($saveUrl)) {
                unlink($saveUrl);
            }
        } catch (OssException $e) {
            printf($e->getMessage() . "\n");
            return;
        }
        return $fname;
    }

//获取视频大小
    function getVideoSize($video)
    {
        $size = sprintf("%.1f ", ($video['size'] / 1024 / 1000)) . "MB";
        return $size;
    }

    //获得视频文件的总长度时间和创建时间
    function getVideoTime($file)
    {
        $vtime = exec("ffmpeg -i " . $file . "  2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");//CuPlayer.com 总长度
        //$ctime = date("Y-m-d H:i:s",filectime($file));//创建时间
        //$duration = explode(":",$time);
        // $duration_in_seconds = $duration[0]*3600 + $duration[1]*60+ round($duration[2]);//CuPlayer.com 转化为秒
    }

}