<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/19
 * Time: 14:25
 */


namespace app\api\model;


class Synonym extends BaseModel
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'yx_synonym';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_2';

    public function english()
    {
        return $this->hasOne('English','id','wid')->bind([
            'english_word','chinese_word','sentence','us_audio','us_phonetic'
        ]);
    }

    public static function getInfo($sid)
    {
        $synonym = self::with('english')
            ->where('sid',$sid)
            ->select();
        return $synonym;
    }
}