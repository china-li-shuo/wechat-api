<?php

namespace app\api\model;


use think\Db;
use think\Facade\Cache;
use think\Model;


class User extends Model
{
    protected $autoWriteTimestamp = true;
//    protected $createTime = ;


    /**
     * 用户是否存在
     * 存在返回uid，不存在返回0
     */
    public static function getByOpenID($openid)
    {
        $user = User::where('openid', '=', $openid)
            ->find();
        return $user;
    }


    public static function bindMobile($identities, $mobile)
    {

        $userInfo = Db::table('yx_user')
            ->where('mobile', $mobile)
            ->where('mobile_bind', 2)
            ->find();

        if (!empty($userInfo)) {
            $data = [
                'user_name'   => $userInfo['user_name'],
                'mobile'      => $userInfo['mobile'],
                'mobile_bind' => 1,
                'is_teacher'  => $userInfo['is_teacher'],
            ];

            Db::table('yx_user')
                ->where('id', $identities['uid'])
                ->update($data);

            Db::table('yx_user_class')
                ->where('user_id', $userInfo['id'])
                ->update(['user_id' => $identities['uid']]);
            return Db::table('yx_user')->where('id', $userInfo['id'])->delete();
        } else {
            $data = [
                'mobile'      => $mobile,
                'mobile_bind' => 1
            ];
            return Db::table('yx_user')
                ->where('id', $identities['uid'])
                ->update($data);
        }
    }

    public static function addUserInfo($data, $token)
    {
        $vars     = Cache::get($token);
        $arr      = json_decode($vars, true);
        $uid      = $arr['uid'];
        $userInfo = Db::table('yx_user')
            ->where('id', $uid)
            ->field('mobile_bind')
            ->find();

        $res = User::where('id', $uid)->update(
            [
                'nick_name'  => $data['nick_name'],
                'avatar_url' => $data['avatar_url']
            ]);


        return $userInfo['mobile_bind'];

    }

    public static function getUserInfo($uid)
    {
        $data = User::get($uid)->toArray();
        if (empty($data['is_teacher'])) {
            $data['is_teacher'] = 0;
        }
        return $data;
    }
}
