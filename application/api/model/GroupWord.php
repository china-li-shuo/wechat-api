<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/6
 * Time: 14:16
 */

namespace app\api\model;


use think\Db;

class GroupWord
{
    const PREFIX = 'yx_question.yx_';

    public static function findFirst()
    {
        return Db::table(self::PREFIX.'group_word')->where('group',1)->select();
    }
}