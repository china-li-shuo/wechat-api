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
use app\api\model\LearnedSentence;
use app\api\model\Stage;
use app\api\model\UserClass;
use app\lib\enum\ScreenEnum;
use app\api\model\UserIntention;
use app\lib\exception\MissException;

class Circle
{
    public function info($user, $class,$stage)
    {
        //取出所有符合班级权限的子阶段
        $data = cpStages(['class_id'=>$class->id,'p_stage_id'=>$stage]);
        if(empty($data)){
            throw new MissException([
                'msg'=>'没有查到此模块下的数据',
                'errorCode'=>'50000'
            ]);
        }
        //取出所有符合班级权限的子阶段ID  数组
        $stageArr = array_column($data,'id');

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
        //进行判断是否是今日已学的模块单词
        if($stage == ScreenEnum::Sentence){
            //获取今日学习长难句的数量
            $data['today_learned_number'] = LearnedSentence::where('user_id',$user->id)
                ->where(whereTime())
                ->where('stage','in',$stageArr)
                ->count();
            $data['already_number'] = $user->sentence_number;
        }else{
            //获取今日学习单词的数量,此组，真题词汇
            $data['today_learned_number'] = LearnedHistory::where('user_id',$user->id)
                ->where(whereTime())
                ->where('stage','in',$stageArr)
                ->count();
            //某一模块下已经学习的单词数量
            $data['already_number'] = LearnedHistory::where('user_id',$user->id)
                ->where('stage','in',$stageArr)
                ->count();
        }

        //当前所属的阶段名称
        $stage = Stage::field('stage_name')
            ->get($user['now_stage']);
        $data['stage_name'] = $stage ? $stage->stage_name : NULL;
        return $data;
    }

    public function getClassRanKing($class_id, $is_today, $stage)
    {
        //每次进来根据用户查询此班级下是否有缓存
        if ($is_today == 1) {
            $classRankingData = cache('class_id_ranking_' . $class_id  .$stage.  '_today');
        } else {
            $classRankingData = cache('class_id_ranking_' . $class_id  .$stage.  '_history');
        }
        if ($classRankingData) {
            return $classRankingData;
        }

        if($is_today == 1){
            //缓存整个班级的信息，用户头像，昵称，用户名，掌握多少单词，坚持多少天，总共学习了多少个单词
            $userClass = UserClass::allUserClassByClassID($class_id);
            //进行判断是否根据已学长难句还是单词排序
            if($stage == ScreenEnum::Sentence){
                $classRankingData  = LearnedSentence::getUserTodayLearnedNumber($userClass->toArray());
            }else{
                $classRankingData  = LearnedHistory::getUserTodayLearnedNumber($userClass->toArray());
            }

            foreach ($classRankingData as &$val){
                $val['nick_name'] = urlDecodeNickName($val['nick_name']);
                if(empty($val['avatar_url'])){
                    $val['avatar_url'] = config('setting.avatar_path');
                }
            }
            cache('class_id_ranking_' . $class_id .$stage. '_today',$classRankingData,7200);
            return $classRankingData;
        }else{
            //进行判断是否根据已学长难句还是单词排序
            if($stage == ScreenEnum::Sentence){
                //历史榜单,根据已掌握单词排序
                $classRankingData = UserClass:: alreadySenDescByID($class_id,20);
                $classRankingData = $classRankingData->hidden(['user'])
                    ->toArray();
                foreach ($classRankingData as &$val){
                    $val['already_number'] =$val['sentence_number'];
                    $val['nick_name'] = urlDecodeNickName($val['nick_name']);
                    $val['today_learned_number'] = '';
                }
            }else{
                //历史榜单,根据已掌握单词排序
                $classRankingData = UserClass:: alreadyNumDescByID($class_id,20);
                $classRankingData = $classRankingData->hidden(['user'])
                    ->toArray();
                foreach ($classRankingData as &$val){
                    $val['nick_name'] = urlDecodeNickName($val['nick_name']);
                    $val['today_learned_number'] = '';
                }
            }
            cache('class_id_ranking_' . $class_id  .$stage.  '_history',$classRankingData, 7200);
            return $classRankingData;
        }
    }

}