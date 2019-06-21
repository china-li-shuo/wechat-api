<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/18
 * Time: 10:18
 */


namespace app\api\model;


class English extends BaseModel
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'yx_english_word';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_2';
}