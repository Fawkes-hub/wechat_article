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

class Controller{
        /**
         * 获取文章的内容
         * @param Request $request
         */
        public function article(Request $request){
            $articleInfo = new wechatArticle();
            try {
                $articleInfo->setHttpToImg('/图片防盗链设置的接口?imgUrl='); //当前防盗链请求地址
                $article = $articleInfo->crawQueryByUrl("https://mp.weixin.qq.com/s/YuhmAYMLgCxktxVo1bgmCQ");
                print_r($article['content']);
                unset($article['content']);
                var_dump($article);
            } catch (wechatArticleException $e) {
                var_dump($e->getMessage());
            }
        }
    
        /**
         * 图片防盗链的处理
         * @param Request $request
         * @return \think\Response
         */
        public function proxy(Request $request){
            $articleInfo = new wechatArticle();
            $imgUrl = $request->param('imgUrl');
            $headers = [];
            $headers['Content-Type'] = 'image/png';
            $content = $articleInfo->getImg($imgUrl);
            return response($content,200,$headers);
        }
}
```