<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/6
 * Time: 16:26
 */

namespace app\api\model;


use think\Db;

class UserClass
{
    public static function getClassInfo($uid)
    {
        return Db::table('yx_user_class')->where('user_id',$uid)->find();
    }

    public static function getClassName($classInfo)
    {
        $classData = Db::table('yx_class')->where('id',$classInfo['class_id'])->find();
        return $classData['class_name'];
    }
}