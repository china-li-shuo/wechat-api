<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/19
 * Time: 11:36
 */


namespace app\api\model;


class Collection extends BaseModel
{

    /**
     * 判断当前用户是否收藏过该单词
     */
    public static function isCollection($uid, $notWordData)
    {
        if($notWordData[0]['type'] == 1){
            $notWordData = CollectionSentence::isCollection($uid, $notWordData);
            return $notWordData;
        }
        foreach ($notWordData as $key => $val) {
            $collection = self::where(
                [
                    'user_id'=>$uid,
                    'stage'=>$val['stage'],
                    'group'=>$val['group'],
                    'word_id'=>$val['wid'],
                ])->find();
            if (!empty($collection)) {
                $notWordData[$key]['is_collection'] = 1;
            }
        }
        return $notWordData;
    }

    /**
     * 添加
     */
    public static function addCollection($uid, $data)
    {
        $collection = self::where(
            [
                'user_id'=>$uid,
                'stage'=>$data['stage'],
                'group'=>$data['group'],
                'word_id'=>$data['word_id'],
            ])->find();

        if (empty($collection)) {
            $data['user_id']     = $uid;
            $data['create_time'] = time();
            unset($data['is_collection']);

            $res = self::create($data);
            //返回自增id
            return $res->id;
        }
        return false;
    }

    /**
     * 删除
     */
    public static function deleteCollection($uid, $data)
    {
        $collection = self::where(
            [
                'user_id'=>$uid,
                'stage'=>$data['stage'],
                'group'=>$data['group'],
                'word_id'=>$data['word_id'],
            ])->find();

        if ($collection) {
            return $collection->delete();
        }
        return false;
    }
}