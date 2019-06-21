<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/20
 * Time: 9:59
 */


namespace app\api\model;


class LearnedChild extends BaseModel
{
    /**
     * 进行添加子表对应阶段对应组已掌握记录
     */
    public static function addLearnedChild($uid, $data)
    {
        //进行查询学习主表信息
        $historyData = LearnedHistory::where(
            [
                'user_id'=>$uid,
                'word_id'=>$data['word_id'],
                'stage'=>$data['stage'],
                'group'=>$data['group']
            ])->find();

        //进行查询学习记录子表信息
        $childData = self::where(
            [
                'user_id'=>$uid,
                'stage'=>$data['stage'],
                'group'=>$data['group']
            ])->find();


        //进行删除子表用不到的字段
        unset($data['useropt']);
        unset($data['word_id']);
        //如果学习记录子表为空，则新增
        if(empty($childData)){
            $data['mastered_number'] = 1;
            $data['user_id'] = $uid;
            $data['create_time'] = time();
            $res = self::create($data);
            //返回插入的自增id
            return $res->id;
        }

        //第一次学习答对进行递增已掌握数量
        if(empty($historyData)){
            $data['mastered_number'] = $childData['mastered_number']+1;
            $data['create_time'] = time();
            $res = self::where(
                [
                    'user_id'=>$uid,
                    'class_id'=>$data['class_id'],
                    'stage'=>$data['stage'],
                    'group'=>$data['group']
                ])->update($data);
            return $res;
        }

        //如果是重新来过以前是选错的则掌握数进行加一
        if($historyData['is_true'] == 0){
            $data['mastered_number'] = $childData['mastered_number']+1;
            $data['create_time'] = time();
            $res = self::where(
                [
                    'user_id'=>$uid,
                    'class_id'=>$data['class_id'],
                    'stage'=>$data['stage'],
                    'group'=>$data['group']
                ])->update($data);
            return $res;
        }
    }


    /**
     * 进行判断并对应减少用户答题正确数量
     * @param $uid
     * @param $data
     */
    public static function deleteLearnedChild($uid, $data)
    {
        //如果非空则进行判断用户记录主表记录以前的是否为1
        $historyData = LearnedHistory::where(
            [
                'user_id'=>$uid,
                'word_id'=>$data['word_id'],
                'stage'=>$data['stage'],
                'group'=>$data['group']
            ])->find();

        //进行查询学习记录子表信息
        $childData = self::where(
            [
                'user_id'=>$uid,
                'stage'=>$data['stage'],
                'group'=>$data['group']
            ])->find();

        //进行删除子表用不到的字段
        unset($data['useropt']);
        unset($data['word_id']);
        //如果为空进行添加
        if(empty($childData)){
            $data['mastered_number'] = 0;
            $data['user_id'] = $uid;
            $data['create_time'] = time();
            $res = self::create($data);
            //返回插入的自增id
            return $res->id;
        }

        //进行查询用户记录表这个单词是否有过，没有则不删，有则已掌握记录减一
        //如果以前也是选错的则掌握数不减
        if(empty($historyData) || $historyData['is_true'] == 0){
            return true;
        }

        //子表记录进行掌握数减一
        $data['mastered_number'] = $childData['mastered_number']-1;
        //用户掌握数量不能低于0
        if($data['mastered_number'] < 0){
            $data['mastered_number'] = 0;
        }
        $data['create_time'] = time();

        $res = self::where(
            [
                'user_id'=>$uid,
                'class_id'=>$data['class_id'],
                'stage'=>$data['stage'],
                'group'=>$data['group']
            ])->update($data);
        return $res;
    }
}