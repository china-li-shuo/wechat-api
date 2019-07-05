<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/21
 * Time: 10:57
 */


namespace app\api\model;


class CollectionSentence extends BaseModel
{

    public function sentenceInfo()
    {
        return $this->hasOne('Sentences','id','sentence_id');
    }

    public function learned()
    {
        return $this->hasOne('LearnedSentence','sentence_id','sentence_id')
            ->bind(['translation']);
    }
    public static function isCollection($uid, $notWordData)
    {
        foreach ($notWordData as $key => $val) {
            if(array_key_exists('sentence_id',$val)){
                $val['wid'] = $val['sentence_id'];
            }
            $collection = self::where(
                [
                    'user_id'=>$uid,
                    'stage'=>$val['stage'],
                    'group'=>$val['group'],
                    'sentence_id'=>$val['wid'],
                ])->find();
            if (!empty($collection)) {
                $notWordData[$key]['is_collection'] = 1;
            }else{
                $notWordData[$key]['is_collection'] = 2;
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
                'sentence_id'=>$data['sentence_id'],
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
                'sentence_id'=>$data['sentence_id'],
            ])->find();

        if ($collection) {
            return $collection->delete();
        }
        return false;
    }

    public static function getSummaryByUid($uid, $page, $size)
    {
        $pagingData = self::with('sentenceInfo,learned')
            ->where('user_id',$uid)
            ->order('create_time desc')
            ->visible(['sentence_id', 'stage', 'group', 'translation', 'sentence_info'])
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }
}