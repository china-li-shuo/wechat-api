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
        $classData = Db::table('yx_user_class')
            ->field('class_id')
            ->where('user_id', $uid)
            ->where('status', 1)
            ->find();

        if ($classData) {
            return Db::table('yx_user_class')
                ->where('class_id', $classData['class_id'])
                ->where('status', 1)
                ->field('user_id,class_id')
                ->select();
        }

        return false;
    }

    public static function getAllMembersOfClass($class_id)
    {
        return Db::table('yx_user_class')
            ->where('class_id', $class_id)
            ->where('status', 1)
            ->field('user_id,class_id')
            ->select();
    }
    /**
     * 获取用户id查询是否加入过班级
     * @param $uid
     * @return array|false|null|\PDOStatement|string|\think\Model
     */
    public static function getClassInfo($uid)
    {
        return Db::table('yx_user_class')
            ->where('user_id', $uid)
            ->where('status',1)
            ->find();
    }

    /**
     * 根据班级用户加入班级信息获取班级名称
     * @param $classInfo
     * @return mixed
     */
    public static function getClassName($classInfo)
    {
        $data = Db::table('yx_class')
            ->where('id', $classInfo['class_id'])
            ->find();
        return $data['class_name'];
    }

    /**
     * 班级详情
     * @param $classInfo
     */
    public static function getClassDetail($class_id)
    {
        return Db::name('class')->where('id',$class_id)->find();
    }

    /**
     * 判断此用户，此班级是否加入过
     * @param $uid
     * @param $class_id
     */
    public static function findUserClass($uid,$class_id)
    {
        return Db::table('yx_user_class')
            ->where('user_id', $uid)
            ->where('class_id', $class_id)
            ->where('status',1)
            ->find();
    }

    /**
     * 根据班级id查询这个班级下历史排行榜所有成员数据
     * @param $class_id
     * @return array|\PDOStatement|string|\think\Collection
     */
    public static function allHistoryClassUserData($class_id,$limit = 20)
    {
        return Db::name('user_class')
            ->alias('uc')
            ->join('user u','u.id=uc.user_id')
            ->where('uc.class_id', $class_id)
            ->where('uc.status', 1)
            ->field('uc.class_id,u.user_name,u.nick_name,u.avatar_url,u.already_number,u.punch_days')
            ->order('u.already_number','desc')
            ->limit($limit)
            ->select();

    }

    /**
     * 根据班级id查询这个班级下历史排行榜所有成员数据
     * @param $class_id
     * @return array|\PDOStatement|string|\think\Collection
     */
    public static function allTodayClassUserData($class_id)
    {
        return Db::name('user_class')
            ->alias('uc')
            ->join('user u','u.id=uc.user_id')
            ->where('uc.class_id', $class_id)
            ->where('uc.status', 1)
            ->field('uc.user_id,uc.class_id,u.user_name,u.nick_name,u.avatar_url,u.already_number,u.punch_days')
            ->orderRand('uc.user_id')
            ->limit(30)
            ->select();

    }

    /**
     * 找排序后的第一个，也是公共班级
     */
    public static function getAscClassInfo()
    {
        return Db::name('class')->order('sort')->select();
    }
}