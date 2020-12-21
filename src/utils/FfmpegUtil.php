<?php


namespace fawkes\wechat_article;


use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;

class FfmpegUtil
{
    /*
 * ffmpeg直接获取视频信息
 *
 * */

    /**
     * 读取资源地址的信息  获取信息 然后进行RPC上传到KS3服务
     * @param $origin_file_path
     * @param $vid
     * @param $type
     * @return array|int[]|mixed
     * @throws \Exception
     */
    public function uploadKs3($origin_file_path, $vid)
    {
        if (empty($origin_file_path)) {
            throw new wechatArticleException("资源URL地址为空：", $origin_file_path);
        }
        $dir = APP_PATH . '/';
        //使用扩展读取信息
        $ffmpeg = FFMpeg::create(array(
            'ffmpeg.binaries' => 'ffmpeg',
            'ffprobe.binaries' => 'ffprobe',
            'timeout' => 3600, // The timeout for the underlying process
            'ffmpeg.threads' => 12,   // The number of threads that FFMpeg should use
        ));
        $fileClass = $ffmpeg->open($origin_file_path);
        $resourcesType = null;
        //检查是否音频还是视频
        $steamsInfo = null;
        $coverImgKS3 = '';
        if ($fileClass->getStreams()->first()->isVideo()) {
            $resourcesType = 'video';
            //视频
            $steamsInfo = $fileClass->getStreams()->videos()->first();
            //视频长度和尺寸
            $width = $steamsInfo->get('width') ?? 0;
            $height = $steamsInfo->get('height') ?? 0;
            $extension = 'mp4';
            //获取封面图
            $frame = $fileClass->frame(TimeCode::fromSeconds(1));
            //保存的路径
            $coverImgKS3 = $dir . 'cover-' . time() . ".jpg";
            var_dump($coverImgKS3);
            $frame->save($coverImgKS3);
        } elseif ($fileClass->getStreams()->first()->isAudio()) {
            $resourcesType = 'audio';
            //音频
            $steamsInfo = $fileClass->getStreams()->audios()->first();
            $extension = 'mp3';
        } else {
            throw new wechatArticleException("资源信息错误");
        }
        //时间长度
        $duration = $steamsInfo->get('duration') ?? 0;
        $format = $fileClass->getFormat();
        $ksFileName = $resourcesType . '-' . time() . '.' . $extension;
        $size = $format->get("size") ?? 0;
        //转化为M的大小
//        $mb_size = round(($size/1024/1024),3);
        //保存视频
        //先将文件保存到服务器本地
        $full_path = $dir . $ksFileName;
        //获取视频的大小和尺寸
        $format = new X264();
        $format->setAudioCodec("libmp3lame");
        $fileClass->save($format, $full_path);
        $path = $full_path;
        $file = [
            'file_size' => $size,
            'file_name' => $ksFileName,
            'file_path' => $path,
        ];
        $file['duration'] = $duration;
        if ($resourcesType == 'video') {
            $file['cover'] = $coverImgKS3;
        }
        return $file;
    }

}