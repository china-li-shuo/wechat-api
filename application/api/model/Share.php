<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 14:01
 */

namespace app\api\model;


use think\Db;
use think\Model;

class Share extends Model
{
    public static function addShare($uid)
    {

        $data = Share::where('user_id',$uid)->find();

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

        $data->toArray();
        if($data['create_time'] >= $beginToday && $data['create_time'] <= $endToday){
            Db::table('yx_share')->where('user_id',$uid)->update(['create_time'=>time()]);
            return true;
        }

        return false;
    }
}