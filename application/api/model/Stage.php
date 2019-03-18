<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/2
 * Time: 14:10
 */

namespace app\api\model;


use think\Db;
use think\Model;

class Stage extends Model
{
    public static function getStages()
    {
        return Db::table(YX_QUESTION.'stage')->field('id,stage_name')->where('parent_id',0)->select();
    }

    public static function getAllStage()
    {

        return Db::table(YX_QUESTION.'stage')->order('sort')->hidden(['create_time'])->select();
    }


    protected static function getGroup($stageData)
    {
        $new_arr = [];
        foreach ($stageData as $val){
            $sql = "SELECT s.id,e.group,e.stage FROM ".YX_QUESTION."stage AS s INNER JOIN ".YX_QUESTION."english_word AS e ON s.id=e.stage WHERE e.stage = $val[id] GROUP BY e.group ";

            $res = Db::query($sql);

            if(!empty($res)){
                array_push($new_arr,$res);
            }
        }

        foreach ($new_arr as $k=>$v){
            foreach ($stageData as $kk => $z){
                if($z['id'] == $v[0]['stage']){
                    $stageData[$kk]['group'] = count($v);
                }
            }
        }

        return $stageData;

    }

    /**
     * 获取某一阶段详情
     * @param $id
     * @return array|false|null|\PDOStatement|string|Model
     */
    public static function findStage($id)
    {
        return Db::table(YX_QUESTION.'stage')->hidden(['create_time'])->where('id',$id)->find();
    }


    public static function getStageName($historyData)
    {
        foreach ($historyData as $key=>$val){
            $data = Db::table(YX_QUESTION.'stage')->where('id',$val['stage'])->field('id,stage_name')->select();
            foreach ($data as $k=>$v){
                if($val['stage'] == $v['id']){
                    $historyData[$key]['stage_name'] = $v['stage_name'];
                }
            }
        }
        return $historyData;
    }

    /**
     * 根据用户最后一次学习阶段返回阶段名称
     * @param $LearnedData
     * @return mixed
     */
    public static function getStageNameByLearnedNumber($LearnedData)
    {
        $data = Db::table(YX_QUESTION.'stage')->where('id',$LearnedData['stage'])->field('stage_name,word_num')->find();
        return $data['stage_name'];
    }

    /**
     * 找出下一阶段下一组id
     * @param $stageID
     */
    public static function nextStageGroupInfo($userInfo)
    {
        //进行阶段排序
        $stageData = Db::table(YX_QUESTION.'stage')->order('sort')->select();
        //找出当前阶段
        $nowStage = Db::table(YX_QUESTION.'stage')->where('id',$userInfo['now_stage'])->find();
        foreach ($stageData as $key=>$val){
            if($nowStage == $stageData[$key]){
                $k = $key+1;
            }
        }

        //如果下一阶段信息非空
        if(!empty($stageData[$k])){
            return $stageData[$k]['id'];

        }
        return false;


        //先根据阶段进行排序小组
        $data = Db::table(YX_QUESTION.'stage')->where('stage_id',$userInfo['now_stage'])->order('sort')->select();
        //找出当前小组
        $res = Db::table(YX_QUESTION.'group')->where('stage_id',$userInfo['now_stage'])->where('id',$userInfo['now_group'])->find();
        //确定下一组单词的信息
        foreach ($data as $key=>$val){
            if($res == $data[$key]){
                $k = $key+1;
            }
        }

        //如果下一组单词信息非空，返回组id
        if(!empty($data[$k])){
            return $data[$k]['id'];
        }

        return false;
    }

    /**
     * 根据不是父级分类进行排序，获取第一阶段id
     */
    public static function FirstStageID()
    {
        $stageData = Db::table(YX_QUESTION.'stage')->where('parent_id','<>',0)->order('sort')->field('id')->select();
        if (!empty($stageData)){
            return $stageData[0]['id'];
        }

        return false;
    }


//    /**
//     * 根据已学单词数量获取目前阶段名称
//     * @param $allLearnedNumber
//     */
//    public static function getStageNameByLearnedNumber($allLearnedNumber)
//    {
//        $data = Db::table(YX_QUESTION.'stage')->where('parent_id','<>',0)->field('stage_name,word_num')->select();
//        foreach (array_reverse($data) as $key=>$val){
//            if($allLearnedNumber > $val['word_num']){
//                return $val['stage_name'];
//            }
//        }
//        return $data[0]['stage_name'];
//    }
}