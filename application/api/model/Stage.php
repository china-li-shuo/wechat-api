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
    const PREFIX = 'yx_question.yx_';
    public static function getStages()
    {
        return Db::table(self::PREFIX.'stage')->field('id,stage_name')->where('parent_id',0)->select();
    }

    public static function getAllStage()
    {

        return Db::table(self::PREFIX.'stage')->hidden(['create_time'])->select();
    }


    protected static function getGroup($stageData)
    {
        $new_arr = [];
        foreach ($stageData as $val){
            $sql = "SELECT s.id,e.group,e.stage FROM ".self::PREFIX."stage AS s INNER JOIN ".self::PREFIX."english_word AS e ON s.id=e.stage WHERE e.stage = $val[id] GROUP BY e.group ";

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
        return Db::table(self::PREFIX.'stage')->hidden(['create_time'])->where('id',$id)->find();
    }


    public static function getStageName($historyData)
    {
        foreach ($historyData as $key=>$val){
            $data = Db::table(self::PREFIX.'stage')->where('id',$val['stage'])->field('id,stage_name')->select();
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
        $data = Db::table(self::PREFIX.'stage')->where('id',$LearnedData['stage'])->field('stage_name,word_num')->find();
        return $data['stage_name'];
    }



//    /**
//     * 根据已学单词数量获取目前阶段名称
//     * @param $allLearnedNumber
//     */
//    public static function getStageNameByLearnedNumber($allLearnedNumber)
//    {
//        $data = Db::table(self::PREFIX.'stage')->where('parent_id','<>',0)->field('stage_name,word_num')->select();
//        foreach (array_reverse($data) as $key=>$val){
//            if($allLearnedNumber > $val['word_num']){
//                return $val['stage_name'];
//            }
//        }
//        return $data[0]['stage_name'];
//    }
}