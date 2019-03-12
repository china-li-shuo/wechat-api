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
        $classData = Db::table('yx_user_class')->where('user_id',$uid)->where('status',1)->find();
        if ($classData){
            return Db::table('yx_user_class')->where('class_id',$classData['class_id'])->where('status',1)->field('user_id,class_id')->select();
        }

        return false;
    }

    /**
     * 获取用户id查询是否加入过班级
     * @param $uid
     * @return array|false|null|\PDOStatement|string|\think\Model
     */
    public static function getClassInfo($uid)
    {
        return Db::table('yx_user_class')->where('user_id',$uid)->find();
    }

    /**
     * 根据班级用户加入班级信息获取班级名称
     * @param $classInfo
     * @return mixed
     */
    public static function getClassName($classInfo)
    {
        $data = Db::table('yx_class')->where('id',$classInfo['class_id'])->find();
        return $data['class_name'];
    }
}