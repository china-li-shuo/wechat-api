<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/14
 * Time: 9:37
 */


namespace app\api\model;


class ClassPermission extends BaseModel
{
    protected $hidden = ['create_time', 'update_time'];

    public static function getGroupsPermission($stage_id,$class_id)
    {
        $permission = self::where(
            ['class_id'=>$class_id,
             'stage'=>$stage_id])
            ->field('groups')
            ->find();
        return $permission;
    }

    public static function getPermitStage($class_id)
    {
        $stage =self::where('class_id', $class_id)
            ->select();
        return $stage;
    }
}