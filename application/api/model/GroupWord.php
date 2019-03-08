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

    /**
     * 根据组id查询此组下所有的单词
     * @param $id
     */
    public static function selectGroupWord($id)
    {
        return Db::table(self::PREFIX.'group_word')->where('group',$id)->select();
    }
}