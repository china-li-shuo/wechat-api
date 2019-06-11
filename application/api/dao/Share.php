<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 14:01
 */

namespace app\api\dao;


use think\Facade\Cache;
use think\Db;
use think\Model;

class Share extends Model
{
    public static function addShare($uid)
    {

        $data = Share::where('user_id', $uid)->find()->toArray();

        if (empty($data)) {
            $arr = [
                'user_id'     => $uid,
                'number'      => 1,
                'create_time' => time()
            ];
            Db::table('yx_share')->insert($arr);
            return true;
        }

        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;

        $nowTime     = time();
        $punchRecord = Cache::get($uid . 'punchRecord');
        if ($punchRecord) {
            return true;
        }
        if ($nowTime >= $beginToday && $nowTime <= $endToday) {
            //写入缓存，缓存时间,今天结束最后时间-当前时间，如果有缓存，已打卡
            Db::table('yx_share')
                ->where('user_id', $uid)
                ->update(['number' => $data['number'] + 1, 'create_time' => $nowTime]);

            Cache::set($uid . 'punchRecord', '已打卡', $endToday - $nowTime);
            return true;
        }

        return false;
    }


    public static function getPunchDays($uid)
    {
        $punchDays = Share::where('user_id', $uid)->select()->count();

        $userInfo = Db::table('yx_user')->where('id', $uid)->field('punch_days')->find();

        if ($userInfo['punch_days'] < $punchDays) {

            Db::table('yx_user')
                ->where('id', $uid)
                ->update(['punch_days' => $punchDays]);
        }

        return $punchDays;

    }

    /**
     * 用户进行打卡
     * @param $uid
     */
    public static function userPunchCard($uid)
    {

        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $nowTime    = time();
        $where[]    = ['create_time', 'between time', [$beginToday, $endToday]];

        $data = Db::table('yx_share')
            ->field('user_id,create_time')
            ->where('user_id', $uid)
            ->where($where)
            ->find();

        if (empty($data)) {

            $arr = [
                'user_id'     => $uid,
                'create_time' => $nowTime
            ];
            return Db::table('yx_share')->insert($arr);

        }

        return true;
//        return Db::table('yx_share')
//            ->where('user_id', $uid)
//            ->where($where)
//            ->update(['create_time' => $nowTime]);

    }
}