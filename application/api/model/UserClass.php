<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/7
 * Time: 16:42
 */

namespace app\api\model;


use think\Db;

class UserClass
{
    /**
     * 获取和此用户一样班级的学员
     * @param $uid
     */
    public static function getAllUserByUid($uid)
    {
        $classData = Db::table('yx_user_class')->where('user_id',$uid)->find();

        if ($classData){
            return Db::table('yx_user_class')->where('class_id',$classData['class_id'])->select();
        }

        return false;
    }
}