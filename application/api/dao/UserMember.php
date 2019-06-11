<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/29
 * Time: 15:49
 */

namespace app\api\dao;


use think\Db;

class UserMember
{
    /**
     * 判断互联网用户是否购买过付费阶段
     */
    public static function getUserMember($uid)
    {
        return Db::name('user_member')->where('user_id',$uid)->select();
    }
}