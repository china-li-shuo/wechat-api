<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/6
 * Time: 11:57
 */


namespace app\article\service;

use app\article\model\User;
use app\article\model\Record as RecordModel;

class Record
{
    public function addRecord($data)
    {
        $record = RecordModel::where([
            'article_id'=>$data['article_id'],
            'user_id'=>$data['user_id']])
            ->find();
        if(empty($record)){
            //进行新增用户的学习记录
            $record = RecordModel::create($data);
            //并且修改该用户的打卡天数
            $user = User::get($data['user_id']);
            if(!empty($record)){
                $user->punch_days += 1;
                $res = $user->save();
            }else{
                $res = null;
            }
        }else{
            $res = $record->force()->save($data);
        }
        return $res;
    }
}