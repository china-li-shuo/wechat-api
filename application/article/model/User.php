<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/6
 * Time: 11:52
 */


namespace app\article\model;


class User extends BaseModel
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'xcx_user';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_reading';
}