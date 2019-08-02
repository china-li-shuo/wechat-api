<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/1
 * Time: 11:06
 */


namespace app\article\model;


class EnglishArticle extends BaseModel
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'xcx_english_article';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_reading';

    public function son()
    {
        return $this->hasOne('EnglishArticleChild','article_id','id')
            ->bind(['words']);
    }

    public function category()
    {
        return $this->belongsTo('Category','category_id','id')
            ->bind(['english_name']);
    }

    /**
     * 获取推送日期的文章
     * @param $push_date 推送日期
     */
    public static function getPushDateArticle($push_date)
    {
        $articleInfo = self::with('son,category')
            ->where('push_date',$push_date)
            ->find();
        return $articleInfo;
    }
}