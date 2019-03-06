<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 11:16
 */

namespace app\api\model;


use think\Db;
use think\Model;

class ErrorBook extends Model
{
    public static function addErrorBook($uid,$data)
    {
        $errorData = ErrorBook::where('user_id',$uid)->where('word_id',$data['word_id'])->find();

        if(empty($errorData)){

            $arr = [
                'user_id'=>$uid,
                'group'=>$data['group'],
                'stage'=>$data['stage'],
                'word_id'=>$data['word_id'],
                'user_opt'=>$data['useropt'],
                'create_time'=>time()
            ];

            return Db::table('yx_error_book')->insert($arr);
        }
        $arr = ['user_opt'=>$data['useropt'],'create_time'=>time()];
        return Db::table('yx_error_book')->where('user_id',$uid)->where('word_id',$data['word_id'])->update($arr);
    }
}