<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/2
 * Time: 16:13
 */


namespace app\article\model;


class Collect extends BaseModel
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'xcx_collect';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_reading';

    public function article()
    {
        return $this->hasOne('EnglishArticle','id','article_id')
            ->bind(['title','push_date']);
    }

    public static function getSummaryByUser($uid, $page, $size)
    {
        $collect = self::with('article')
            ->where(['user_id'=>$uid,'status'=>1])
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
       return $collect;
    }
}