<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/2
 * Time: 14:47
 */


namespace app\article\model;


class EnglishArticleChild extends BaseModel
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'xcx_english_article_child';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_reading';

    public function cnj()
    {
        return $this->belongsTo('Sentences','sentence_id','id');
    }

    public static function getChildDetail($article_id)
    {
        $articleChild = self::with('cnj')
            ->where('article_id', '=', $article_id)
            ->find();
        return $articleChild;
    }
}