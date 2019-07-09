<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/24
 * Time: 9:25
 */


namespace app\api\service;


use app\api\model\Group;
use app\api\model\Post;
use app\api\model\Stage;
use app\api\model\LearnedChild;
use app\api\model\LearnedHistory;
use app\api\model\UserClass;

class Settlement
{
    /**
     * 获取用户此班级下此阶段此组的个人正确率
     */
    public function personalCorrectnessRate($data)
    {
        //这组单词的总个数
        $groupNum = Group::field('word_num')
            ->get($data['group']);
        //计算着组单词答对的个数
        $trueNum = LearnedChild::where([
            'user_id'=>$data['user_id'],
            'stage'=>$data['stage'],
            'group'=>$data['group'],
        ]) ->field('mastered_number')
            ->find();
        if(!empty($groupNum) && !empty($trueNum)){
            return round($trueNum['mastered_number'] / $groupNum['word_num'] * 100, 2) . "%";
        }
        return NULL;
    }

    /**
     * 发帖子的状态
     */
    public static function PostStatus($data)
    {
        $status = Post::where([
            'class_id'=>$data['class_id'],
            'user_id'=>$data['user_id'],
            'stage'=>$data['stage'],
            'group'=>$data['group'],
        ])->count();
        return $status;
    }

    /**
     * 判断用户是否可以获得勋章
     */
    public function getMedal($data)
    {
        //判断是此用户是否学完此阶段，获得此勋章
        //找本阶段的学习数量
        $medalData = cache('medal' . $data['user_id']);
        if (!empty($medalData)) {
            return Null;
        }
        //用户这一阶段已经学过的单词数量
        $already_number = LearnedHistory::where([
            'user_id'=>$data['user_id'],
            'stage'=>$data['stage']
        ]) ->count();
        //查看这个阶段制定多少单词获取勋章
        $stageData = Stage::field('stage_name,stage_desc,word_num')
            ->get($data['stage']);

        if ($already_number >= $stageData['word_num']) {
            $arr = [
                'stage_name' => $stageData['stage_name'],
                'stage_desc' => $stageData['stage_desc'],
            ];
            cache('medal' . $data['user_id'], $data['stage']);
            return $arr;
        }

        return NULL;
    }

    /**
     * 超过全班百分比
     * 根据每个班级下，每个用户，每个组下答题正确率来计算百分比
     */
    public function percentageOfClass($data)
    {
        //查看班级所有成员
        $classData = UserClass::getAllMembersOfClass($data['class_id']);
        if(!empty($classData)){
            $classData = $classData->toArray();
            if(count($classData) > 1000){
                return '75.8%';
            }
        }
        //判断此阶段下此组，所有用户答对的单词
        $classTrueRate = LearnedHistory::getClassTrueRate($classData, $data);
        return $classTrueRate;
    }
}