<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/21
 * Time: 10:09
 */


namespace app\api\model;


class LearnedSentence extends BaseModel
{

    public static function addSentence($uid, $data)
    {
        $sentence = self::where([
            'user_id'     => $uid,
            'group'       => $data['group'],
            'sentence_id' => $data['sentence_id']
        ])->find();
        //如果学习记录为空进行新增
        if(empty($sentence)){
            $data['user_id'] = $uid;
            $res = self::create($data);
            return $res->id;
        }
        //否则进行修改
        $res = self::where([
            'user_id'     => $uid,
            'group'       => $data['group'],
            'sentence_id' => $data['sentence_id']
        ])->update($data);

        return $res;
    }
}