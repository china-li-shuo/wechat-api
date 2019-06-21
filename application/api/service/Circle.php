<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/13
 * Time: 13:51
 */


namespace app\api\service;


use app\api\model\LearnedHistory;
use app\api\model\Stage;
use app\api\model\UserClass;
use app\api\model\UserIntention;

class Circle
{
    public function info($user, $class)
    {
        //用户是不是此班级学员
        $userClassData = UserClass::getUserClassByUCid($user->id, $class->id);
        $data = array_merge($user->toArray(),$class->toArray());
        if($userClassData){
            $data['status'] = $userClassData->status;
        } else{
            //用户加入班级申请意向
            $userIntention = UserIntention::where(['user_id'=>$user->id,'class_id'=>$class->id])
                ->field('status')
                ->find();

            $data['status']  = !empty($userIntention) ? $userIntention->status : 0;
        }
        //获取今日学习单词的数量
        $data['today_learned_number'] = LearnedHistory::where('user_id',$user->id)
            ->where(whereTime())
            ->count();
        //今日已学长难句

        //当前所属的阶段名称
        $stage = Stage::field('stage_name')
            ->get($user['now_stage']);
        $data['stage_name'] = $stage ? $stage->stage_name : NULL;
        return $data;
    }

    public function getClassRanKing($class_id, $is_today)
    {
        //每次进来根据用户查询此班级下是否有缓存
        if ($is_today == 1) {
            $classRankingData = cache('class_id_ranking_' . $class_id . '_today');
        } else {
            $classRankingData = cache('class_id_ranking_' . $class_id . '_history');
        }
        if ($classRankingData) {
            return $classRankingData;
        }

        if($is_today == 1){
            //缓存整个班级的信息，用户头像，昵称，用户名，掌握多少单词，坚持多少天，总共学习了多少个单词
            $userClass = UserClass::allUserClassByClassID($class_id);
            $classRankingData  = LearnedHistory::getUserTodayLearnedNumber($userClass->toArray());
            foreach ($classRankingData as &$val){
                $val['nick_name'] = urlDecodeNickName($val['nick_name']);
                if(empty($val['avatar_url'])){
                    $val['avatar_url'] = config('setting.avatar_path');
                }
            }
            cache('class_id_ranking_' . $class_id . '_today',$classRankingData,7200);
            return $classRankingData;
        }else{
            //历史榜单,根据已掌握单词排序
            $classRankingData = UserClass:: alreadyNumDescByID($class_id,20);
            $classRankingData = $classRankingData->hidden(['user'])
                ->toArray();
            foreach ($classRankingData as &$val){
                $val['nick_name'] = urlDecodeNickName($val['nick_name']);
                $val['today_learned_number'] = '';
            }
            cache('class_id_ranking_' . $class_id . '_history',$classRankingData,7200);
            return $classRankingData;
        }
    }

}