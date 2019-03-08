<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 11:16
 */

namespace app\api\model;


use think\Db;

class ErrorBook
{
    public static function addErrorBook($uid,$data)
    {
        $errorData = Db::table('yx_error_book')->where('user_id',$uid)->where('word_id',$data['word_id'])->where('group',$data['group'])->where('stage',$data['stage'])->find();

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

    public static function deleteErrorBook($uid,$data)
    {

        $errorData = Db::table('yx_error_book')->where('user_id',$uid)->where('word_id',$data['word_id'])->where('group',$data['group'])->where('stage',$data['stage'])->find();

        if(!empty($errorData)){

           return Db::table('yx_error_book')->delete($errorData['id']);

        }

        return true;
    }
}