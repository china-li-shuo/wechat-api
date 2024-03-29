<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/11
 * Time: 11:45
 */
namespace app\api\model;

use think\facade\Cache;

class User extends BaseModel
{
    protected $autoWriteTimestamp = true;
    protected $hidden = ['openid', 'create_time', 'update_time'];

    public function groupType()
    {
        return $this->hasOne('Group','id','now_group')->bind(['type']);
    }

    /**
     * 用户是否存在
     * 存在返回uid，不存在返回0
     */
    public static function getByOpenID($openid)
    {
        $user = self::where('openid', '=', $openid)
            ->find();
        return $user;
    }

    public static function getByUid($uid)
    {
        $user = User::get($uid);
        if(empty($user->now_group)){
            self::where('id',$uid)->update(['now_group'=>23]);
        }
        $user = self::with('groupType')
            ->get($uid)
            ->hidden(['mobile_bind','mobile']);
        $user->nick_name = urlDecodeNickName($user->nick_name);
        return $user;
    }

    public static function updateUserInfo($data, $token)
    {
        $vars     = Cache::get($token);
        $arr      = json_decode($vars, true);
        $userInfo = self::where('id','=',$arr['uid'])
            ->field('mobile_bind')
            ->find();
        //进行更新用户的昵称和头像信息
        self::where('id', $arr['uid'])->update(
            [
                'nick_name'  => $data['nick_name'],
                'avatar_url' => $data['avatar_url']
            ]);
        //查看是否是小试牛刀班级
        $userClass = UserClass::where(['user_id'=>$arr['uid'],'class_id'=>9])
            ->find();
        //如果不是，则默认进行新增
        if(empty($userClass)){
            UserClass::create(['user_id'=>$arr['uid'], 'status'=>1, 'class_id'=>9]);
        }else{
            UserClass::where(['user_id'=>$arr['uid'], 'class_id'=>9])->update(['status'=>1]);
        }
        return $userInfo->mobile_bind;

    }

    public static function bindMobileByUid($uid, $mobile)
    {
        //进行查询是否有批量导入的学员手机号，但是未绑定小程序的
        $user = self::where(['mobile'=>$mobile,'mobile_bind'=>2])
            ->field('id,user_name,mobile,is_teacher')
            ->find();
        //互联网用户直接关联手机号
        if (empty($user)) {
            $res = self::where('id','=', $uid)
                ->update(['mobile' => $mobile, 'mobile_bind' => 1]);
            return $res;
        }
        //学员进行与微信绑定并赋予学员的权限
        $data = [
            'user_name'   => $user->user_name,
            'mobile'      => $user->mobile,
            'mobile_bind' => 1,
            'is_teacher'  => $user->is_teacher,
        ];
        UserClass::where('user_id','=',$user->id)
            ->update(['user_id' => $uid, 'status'=>1, 'create_time'=>time()]);
        $user->delete();
        self::where('id','=', $uid)
            ->update($data);
        return $uid;
    }

    public static function getSummaryByPage($page=1, $size=20){
        $pagingData = self::order('punch_days desc')
            ->field('user_name,nick_name,avatar_url,already_number,sentence_number,punch_days')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

}
