<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/9/27
 * Time: 14:00
 */


namespace app\api_dream\model;


class Classic extends BaseModel
{
    protected $connection = 'db_config_dream';

    public static function getLatest()
    {
        $classic = self::order('index desc')
            ->find();
        return $classic;
    }
}