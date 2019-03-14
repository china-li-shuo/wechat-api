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


    public static function bindMobile($identities = [],$mobile)
    {
        $user = User::get($identities['uid']);

        $user->mobile     = $mobile;
        return $user->save();
    }

    public static function addUserInfo($data,$token)
    {
        $vars = Cache::get($token);
        $arr = json_decode($vars,true);
        $uid = $arr['uid'];
        $userInfo = Db::table('yx_user')->where('id',$uid)->find();
        if(empty($userInfo['mobile'])){
            $mobile_bind = 2;
        }else{
            $mobile_bind = 1;
        }
        User::where('id',$uid)->update(
        [
            'nick_name' => $data['nick_name'],
            'avatar_url' => $data['avatar_url']
        ]);

        return $mobile_bind;
    }

    public static function getUserInfo($uid)
    {
        $data = User::get($uid)->toArray();
        if(empty($data['is_teacher'])){
            $data['is_teacher'] = 'false';
        }
       return $data;
    }
}
