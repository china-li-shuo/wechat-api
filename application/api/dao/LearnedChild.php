<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/12
 * Time: 14:05
 */

namespace app\api\dao;


use think\Db;

class LearnedChild
{
    /**
     * 进行添加子表对应阶段对应组已掌握记录
     * @param $uid
     * @param $data
     * @return int|string
     */
    public static function addLearnedChild($uid, $data)
    {
        //进行查询学习主表信息
        $historyData = Db::name('learned_history')
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->where('word_id',$data['word_id'])
            ->where('user_id',$uid)
            ->find();

        //进行查询学习记录子表信息
        $childData = Db::name('learned_child')
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->where('user_id',$uid)
            ->find();

        //进行删除子表用不到的字段
        unset($data['useropt']);
        unset($data['word_id']);
        //如果学习记录子表为空，则新增
        if(empty($childData)){
            $data['mastered_number'] = 1;
            $data['user_id'] = $uid;
            $data['create_time'] = time();
            return Db::name('learned_child')->insert($data);

        }

        //第一次学习答对进行递增已掌握数量
        if(empty($historyData)){
            $data['mastered_number'] = $childData['mastered_number']+1;
            $data['create_time'] = time();
            return Db::name('learned_child')
                ->where('user_id',$uid)
                ->where('class_id',$data['class_id'])
                ->where('stage',$data['stage'])
                ->where('group',$data['group'])
                ->update($data);
        }

        //如果是重新来过以前是选错的则掌握数进行加一
        if($historyData['is_true'] == 0){
            $data['mastered_number'] = $childData['mastered_number']+1;
            $data['create_time'] = time();
            return Db::name('learned_child')
                ->where('user_id',$uid)
                ->where('class_id',$data['class_id'])
                ->where('stage',$data['stage'])
                ->where('group',$data['group'])
                ->update($data);
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
        $historyData = Db::name('learned_history')
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->where('word_id',$data['word_id'])
            ->where('user_id',$uid)
            ->find();

        //进行查询学习记录子表信息
        $childData = Db::name('learned_child')
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->where('user_id',$uid)
            ->find();

        //进行删除子表用不到的字段
        unset($data['useropt']);
        unset($data['word_id']);
        //如果为空进行添加
        if(empty($childData)){
            $data['mastered_number'] = 0;
            $data['user_id'] = $uid;
            $data['create_time'] = time();
            return Db::name('learned_child')->insert($data);
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
        return Db::name('learned_child')
            ->where('user_id',$uid)
            ->where('class_id',$data['class_id'])
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->update($data);

    }
}
