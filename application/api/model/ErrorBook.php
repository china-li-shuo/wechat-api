<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/20
 * Time: 9:53
 */


namespace app\api\model;


class ErrorBook extends BaseModel
{
    /**
     * 添加用户错题本
     */
    public static function addErrorBook($uid, $data)
    {
        $errorData = self::where(
            [
                'user_id'=>$uid,
                'word_id'=>$data['word_id'],
                'stage'=>$data['stage'],
                'group'=>$data['group']
            ])->find();

        if (empty($errorData)) {
            $arr = [
                'user_id'     => $uid,
                'group'       => $data['group'],
                'stage'       => $data['stage'],
                'word_id'     => $data['word_id'],
                'user_opt'    => implode(',',$data['useropt']),
                'create_time' => time()
            ];
            $res = self::create($arr);
            return $res->id;
        }
        $arr = [
            'user_opt'=>implode(',',$data['useropt']),
            'create_time' => time()
        ];
        $res = self::where(
            [
                'user_id'=>$uid,
                'word_id'=>$data['word_id'],
                'stage'=>$data['stage'],
                'group'=>$data['group']
            ])->update($arr);
        return $res;
    }

    /**
     * 删除用户错题本
     */
    public static function deleteErrorBook($uid, $data)
    {
        //查看是否有用户错题记录
        $errorData = self::where(
            [
                'user_id'=>$uid,
                'word_id'=>$data['word_id'],
                'stage'=>$data['stage'],
                'group'=>$data['group']
            ])->find();
        //有则进行删除
        if ($errorData) {
            return $errorData->delete();
        }

        return NULL;
    }
}