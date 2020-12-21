<?php
/**
 * 获取文章内的视频和音频的真实地址
 * 资源地址可以直接下载
 */

namespace fawkes\wechat_article;


/**
 * Class ArticleVideo
 * @package fawkes\ArticleVideo
 */
class wechatArticleVideo
{
    /**
     * 抓取微信公众号的文章和里面的视频 url
     * @param $url
     * @return array|bool
     * @throws wechatArticleException
     */
    public function actionGetwx($url)
    {
        if (empty($url)) {
            throw new wechatArticleException("请输入公众号文章地址");
        }
        $info_id_arr = $this->getChatInfoId($url);
        //获取真实地址链接
        $info_arr = [];
        foreach ($info_id_arr as $key => $value) {
            $vid = $value['vid'];
            //获取视频
            switch ($value['type']) {
                case 'video':
                    $video_json = Tools::getVqqInfo($vid);
                    if (!empty($video_json['msg']) && $video_json['msg'] == 'vid is wrong') {
                        //检测微视
                        $return = $this->weishiQQCom($vid);
                    } else {
                        //腾讯视频的真是地址获取
                        $return = $this->vQQCom($video_json);
                    }
                    break;
                case 'voice':
                    $return = $this->voiceInfo($vid);
                    break;
                default:
                    break;
            }
            if (!isset($return['name'])) {
                $return['name'] = $value['name'] ?? '';
            }
            $info_arr[] = [
                'type' => $value['type'],
                'data' => $return
            ];
        }
        return array_values($info_arr);
    }

    /**
     * 获取公众号中的资源  音频和视频
     * @param $url
     * @return array
     * @throws wechatArticleException
     */
    private function getChatInfoId($url)
    {
        //微信的链接有长链和短链，以下为长链
        //$url ='http://mp.weixin.qq.com/s?__biz=MzI0NTc1MTczNA==&mid=2247485130&idx=1&sn=945cfb8b0cfdd99f1b730889de0216e2&chksm=e9488c13de3f05057be6c6b065f8e44d43c566cb9ee3a4f35cf8084382742159181ea480b935&scene=27';
        if (stripos($url, '?')) {
            if (stripos($url, '#wechat_redirect')) {
                $url = str_replace('#wechat_redirect', '', $url);
            }
            $json = $url . '&f=json';
        } else {
            $json = $url . '?f=json';
        }
        $data = Tools::curl_request($json);
        $data = json_decode($data, 1);
        //获取json中的得到视频vid
        $vid_arr = $data['video_ids'] ?? [];
        //获取json中的得到音频的mid
        $voice_arr = array_column($data['voice_in_appmsg'], 'voice_id') ?? [];
        //要将这些进行排序
        $voiceArr = [];
        $content_noencode = $data['content_noencode'];
        //匹配歌曲的名称
        $pattern = '~ name=\\"(.*?)\\"~';
        preg_match_all($pattern, $content_noencode, $matches);
        if (array_key_exists(1, $matches) && !empty($matches[1][0])) {
            //找到名字了
            $voiceArr = $matches[1];
        }
        $chat_info_resources_arr = array_merge($voice_arr, $vid_arr);
        $chat_info_resources = [];
        foreach ($chat_info_resources_arr as $value) {
            $resources['sort'] = stripos($content_noencode, $value);
            $resources['name'] = '';
            if (in_array($value, $vid_arr)) {
                $resources['type'] = 'video';
            } else if (in_array($value, $voice_arr)) {
                $resources['type'] = 'voice';
                $resources['name'] = $voiceArr[array_search($value, $voice_arr)];
                //找到歌名
                $resources['name'] = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", " ", strip_tags($resources['name']));
            }
            $resources['vid'] = $value;
            $chat_info_resources[] = $resources;
        }
        //按照原有的格式进行排序
        $sort = array_column($chat_info_resources, 'sort');
        array_multisort($sort, SORT_ASC, $chat_info_resources);
        return $chat_info_resources;
    }

    /**
     * 腾讯微视获取真实地址
     * @param string $vid 视频资源地址
     * @return array
     */
    private function weishiQQCom($vid)
    {
        $url = 'https://mp.weixin.qq.com/mp/videoplayer?action=get_mp_video_play_url&vid=' . $vid;
        $data = Tools::curl_request($url);
        $data = json_decode($data, 1);
        //得到数据的json 组装成功url
        $format_id = $data['url_info'][0]['format_id'];
        $title = $data['title'];
        $url = $data['url_info'][0]['url'] . "&vid=$vid&format_id=$format_id";
        return [
            'vid' => $vid,
            'type' => '公众号素材视频',
            'name' => $title,
            'url' => $url
        ];
    }

    /**
     * 腾讯视频的处理url
     * @param array $video_json 腾讯视频数据
     * @return array
     */
    private function vQQCom(array $video_json)
    {
        $title = $video_json['vl']['vi'][0]['ti'];
        $vid = $video_json['vl']['vi'][0]['vid'];
        //高质量视频
        $fn_pre = $video_json['vl']['vi'][0]['lnk'];
        $host = $video_json['vl']['vi'][0]['ul']['ui'][0]['url'];
        $streams = $video_json['fl']['fi'];
        $seg_cnt = $video_json['vl']['vi'][0]['cl']['fc'];
        $part_format_id = end($streams)['id'];
        $part_urls = [];
        for ($part = 1; $part <= $seg_cnt + 1; $part++) {
            $filename = $fn_pre . '.p' . ($part_format_id % 10000) . '.' . $part . '.mp4';
            $key_api = "http://vv.video.qq.com/getkey?otype=json&platform=11&format="
                . $part_format_id . "&vid=" . $vid . "&filename=" . $filename . "&appver=3.2.19.333";
            $part_info = Tools::curl($key_api);
            preg_match('/QZOutputJson=(.*);$/Uis', $part_info, $key_json);
            $key_json = json_decode($key_json[1], 1);
            if (empty($key_json['key'])) {
                $vkey = $video_json['vl']['vi'][0]['fvkey'];
                $url = $video_json['vl']['vi'][0]['ul']['ui'][0]['url'] . $fn_pre . '.mp4?vkey=' . $vkey;
            } else {
                $vkey = $key_json['key'];
                $url = $host . $filename . "?vkey=" . $vkey;
            }
            $part_urls[] = $url;
        }
        //真实的地址
        if (empty($part_urls)) {
            //获取的视频质量低
            if (!empty($video_json['vl']['vi'])) {
                $keys = [];
                foreach ($video_json['vl']['vi'] as $key => $value) {
                    $fvkey = $value['fvkey'];
                    $fn = $value['fn'];
                    $self_host = $value['ul']['ui'][$key]['url'];
                    $keys['fvkey'] = $fvkey;
                    $keys['fn'] = $fn;
                    $keys['self_host'] = $self_host;
                    $keys['lnk'] = $value['lnk'];
                }
                $part_urls[0] = $keys['self_host'] . $keys['fn'] . '?vkey=' . $keys['fvkey'];
            }
        }
        return [
            'vid' => $vid,
            'type' => '腾讯视频',
            'name' => $title,
            'url' => current($part_urls)
        ];
    }

    /**
     * 获取音频真实地址
     * @param string $vid
     * @return  array
     */
    private function voiceInfo(string $vid)
    {
        $url = 'https://res.wx.qq.com/voice/getvoice?mediaid=' . $vid;
        return [
            'vid' => $vid,
            'type' => '音频资料',
            'url' => $url
        ];
    }
}
