<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 15:59
 */

namespace app\api\model;


use think\Db;
use think\Model;

class LearnedHistory extends Model
{
    public static function UserLearned($uid)
    {
         //return LearnedHistory::where('user_id',$uid)->field('id,group,user_id,stage_id,word_id,is_true')->order('create_time desc')->limit(1)->find()->toArray();

         return Db::table('yx_learned_history')->where('user_id',$uid)->field('id,group,user_id,stage,word_id,is_true')->order('create_time desc')->limit(1)->find();

    }

    public static function LearnedAll($uid)
    {

        return Db::table('yx_learned_history')->where('user_id',$uid)->select();

    }

    public static function UserCountGroup($uid)
    {
        $currentNumber =  Db::table('yx_learned_history')->where('user_id',$uid)->field('id,group,user_id,stage,word_id,is_true')->select();
        return count($currentNumber);
    }

    public static function addUserHistory($uid,$data,$answerResult)
    {
        $result = Db::table('yx_learned_history')->where('user_id',$uid)->where('word_id',$data['word_id'])->field('id,group,user_id,stage,word_id,is_true')->find();

        if(empty($result)){
            $arr = [
                'user_id'=>$uid,
                'group'=>$data['group'],
                'stage'=>$data['stage'],
                'word_id'=>$data['word_id'],
                'is_true'=>$answerResult,
                'create_time'=>time()
            ];
            return Db::table('yx_learned_history')->insert($arr);
        }

        return Db::table('yx_learned_history')->where('user_id',$uid)->where('word_id',$data['word_id'])->update(['is_true'=>$answerResult,'create_time'=>time()]);
    }
}