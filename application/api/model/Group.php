<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 17:36
 */

namespace app\api\model;


use think\Db;
use think\Model;

class Group extends Model
{
    const PREFIX = 'yx_question.yx_';

    public static function getGroupName($historyData)
    {
        foreach ($historyData as $key=>$val){
            $data = Db::table(self::PREFIX.'group')->where('id',$val['group'])->field('id,group_name')->select();
            foreach ($data as $k=>$v){
                if($val['group'] == $v['id']){
                    $historyData[$key]['group_name'] = $v['group_name'];
                }
            }
        }

        return $historyData;
    }



    public static function getGroupData($lastWord)
    {
        //返回用户这组下还未学习的单词 = 这组下所有的单词-用户这组下所有学过的单词
        $allData =  Db::table(self::PREFIX.'group_word')->where('group',$lastWord['group'])->select();

        $learnedData = Db::table('yx_learned_history')->where('user_id',$lastWord['user_id'])->where('group',$lastWord['group'])->field('group,word_id')->select();

        foreach ($allData as $key=>$val){
            foreach ($learnedData as $k=>$v){
                if($val['wid'] == $v['word_id']){
                    unset($allData[$key]);
                }
            }
        }

        return array_values($allData);
    }

    public static function getAllData($lastWord)
    {

        return Db::table(self::PREFIX.'group_word')->where('group',$lastWord['group'])->select();

    }
    public static function correspondingStage($notLearnedData)
    {
        foreach ($notLearnedData as $key=>$val){
            $data = Db::table(self::PREFIX.'group')->where('id',$val['group'])->find();
            $notLearnedData[$key]['stage'] = $data['stage_id'];

        }

        return $notLearnedData;
    }
}