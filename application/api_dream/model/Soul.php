<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/9/26
 * Time: 16:38
 */


namespace app\api_dream\model;


class Soul extends BaseModel
{
    //设置当前模型的数据库链接
    protected $connection = 'db_config_dream';
    /**
     *获取摘要
     */
    public static function getSummaryByPage($page, $size)
    {
        $soul = self::paginate($size, true, ['page' => $page]);
        return $soul;
    }
}