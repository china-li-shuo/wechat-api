<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/12
 * Time: 14:05
 */

namespace app\api\model;


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
        $historyData = Db::name('learned_history')
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->where('word_id',$data['word_id'])
            ->where('user_id',$uid)
            ->find();

        unset($data['useropt']);
        unset($data['word_id']);

        $childData = Db::name('learned_child')
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->where('user_id',$uid)
            ->find();

        if(empty($historyData)){
            if(empty($childData)){
                $data['mastered_number'] = 1;
                $data['user_id'] = $uid;
                return Db::name('learned_child')->insert($data);
            }
        }

        if($historyData['is_true'] == 0){
            $data['mastered_number'] = $childData['mastered_number']+1;
            $data['user_id'] = $uid;
            return Db::name('learned_child')
                ->where('user_id',$uid)
                ->where('class_id',$data['class_id'])
                ->where('stage',$data['stage'])
                ->where('group',$data['group'])
                ->update($data);
        }

    }


    /**
     * 进行删除对应阶段对应组已掌握数据
     * @param $uid
     * @param $data
     */
    public static function deleteLearnedChild($uid, $data)
    {
        $historyData = Db::name('learned_history')
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->where('word_id',$data['word_id'])
            ->where('user_id',$uid)
            ->find();
        //进行查询用户记录表这个单词是否有过，没有则不删，有则已掌握记录减一
        if(empty($historyData)){
            return true;
        }

        unset($data['useropt']);
        unset($data['word_id']);

        if($historyData['is_true'] == 0){
            return true;
        }
        $childData = Db::name('learned_child')
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->where('user_id',$uid)
            ->find();
        //如果子表记录非空则进行减一
        if(!empty($childData)){
            $data['mastered_number'] = $childData['mastered_number']-1;
            if($data['mastered_number']<=0){
                $data['mastered_number'] = 0;
            }
            $data['user_id'] = $uid;
            return Db::name('learned_child')
                ->where('user_id',$uid)
                ->where('class_id',$data['class_id'])
                ->where('stage',$data['stage'])
                ->where('group',$data['group'])
                ->update($data);
        }
        return true;
    }
}