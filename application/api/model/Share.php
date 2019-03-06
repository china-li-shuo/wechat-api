<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 14:01
 */

namespace app\api\model;


use think\Facade\Cache;
use think\Db;
use think\Model;

class Share extends Model
{
    public static function addShare($uid)
    {

        $data = Share::where('user_id',$uid)->find()->toArray();

        if(empty($data)){
            $arr = [
                'user_id'=>$uid,
                'number'=>1,
                'create_time'=>time()
            ];
            Db::table('yx_share')->insert($arr);
            return true;
        }

        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

        $nowTime = time();
        $punchRecord = Cache::get($uid.'punchRecord');
        if($punchRecord){
            return true;
        }
        if($nowTime >= $beginToday && $nowTime <= $endToday){
            //写入缓存，缓存时间,今天结束最后时间-当前时间，如果有缓存，已打卡
            Db::table('yx_share')->where('user_id',$uid)->update(['number'=>$data['number']+1,'create_time'=>$nowTime]);
            Cache::set($uid.'punchRecord','已打卡',$endToday-$nowTime);
            return true;
        }

        return false;
    }


    public static function getPunchDays($uid)
    {
        $data = Share::where('user_id',$uid)->find()->toArray();
        return $data['number'];
    }
}