<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/5
 * Time: 18:23
 */


namespace app\article\model;


class Record extends BaseModel
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'xcx_record';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_reading';

    protected $hidden = ['create_time','update_time'];

    public function article()
    {
        //只要一个推送时间绑定到父模型
        return $this->hasOne('EnglishArticle','id','article_id')
            ->bind('push_date');
    }

    public function child()
    {
        //只要一个推送时间绑定到父模型
        return $this->hasOne('EnglishArticleChild','article_id','article_id')
            ->bind(['words','phrase','sentence_id','judgment_question']);
    }

    /**
     *按用户获取摘要
     */
    public static function getSummaryByUser($uid, $page, $size)
    {
        $record = self::with('article,child')
            ->where('user_id', '=', $uid)
            ->paginate($size, true, ['page' => $page]);
       return $record;
    }

}