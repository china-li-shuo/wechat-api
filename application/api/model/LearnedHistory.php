<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 15:59
 */

namespace app\api\model;


use think\Db;
use think\Model;

class LearnedHistory extends Model
{
    /**
     * 查询用户最后一次学习的阶段和组单词行为记录
     * @param $uid
     * @return array|false|null|\PDOStatement|string|Model
     */
    public static function UserLearned($uid)
    {
         //return LearnedHistory::where('user_id',$uid)->field('id,group,user_id,stage_id,word_id,is_true')->order('create_time desc')->limit(1)->find()->toArray();

         return Db::table('yx_learned_history')->where('user_id',$uid)->field('id,group,user_id,stage,word_id,is_true')->order('create_time desc')->limit(1)->find();

    }

    public static function LearnedAll($uid)
    {

        return Db::table('yx_learned_history')->where('user_id',$uid)->select();

    }

    /**
     * 用户所学所有阶段信息,阶段名称
     * @param $uid
     */
    public static function LearnedStage($uid)
    {
        $data = Db::table('yx_learned_history')->where('user_id',$uid)->group('stage')->field('id,stage')->select();
        $prefix = config('secure.prefix');

        foreach ($data as $key=>$val){
            $stage = Db::table($prefix.'stage')->where('id',$val['stage'])->field('stage_name')->find();
            $data[$key]['stage_name'] = &$stage['stage_name'];
        }

        return $data;
    }

    /**
     * 获取阶段下所有组，组名称
     * @param $historyData
     */
    public static function LearnedGroup($uid,$historyData)
    {
        $prefix = config('secure.prefix');

        foreach ($historyData as $key=>$val){
            $data = Db::table('yx_learned_history')->where('user_id',$uid)->where('stage',$val['stage'])->group('group')->field('id,stage,group')->select();

            foreach ($data as $k=>$v){

                $group = Db::table($prefix.'group')->where('id',$v['group'])->field('id,group_name')->find();
                $data[$k]['son'] = $group;
                $data[$k]['stage_name'] = $val['stage_name'];
            }
            $historyData[$key]['data'] = $data;
        }

        return $historyData;
    }

    /**
     * 获取用户历史共学了多少单词
     * @param $uid
     * @return int
     */
    public static function UserCountGroup($uid)
    {

        return  Db::table('yx_learned_history')->where('user_id',$uid)->field('id,group,user_id,stage,word_id,is_true')->count();

    }

    /**
     * 用户历史共学了多少组,分别每个组的信息
     * @param $uid
     */
    public static function getUserGroupData($uid)
    {
        return  Db::table('yx_learned_history')->where('user_id',$uid)->group('group')->field('group')->select();
    }

    /**
     * 返回用户最后一次学习了第几组的第几个单词
     * @param $LearnedData
     * @return int
     */
    public static function userLearnedCurrentNumber($LearnedData)
    {
        $currentNumber =  Db::table('yx_learned_history')->where('user_id',$LearnedData['user_id'])->where('group',$LearnedData['group'])->field('id')->select();

        return count($currentNumber);
    }

    /**
     * 用户已学过的组下，在这个组学了多少个单词
     * @param $historyGroupData
     */
    public static function getAlreadyLearnedGroupWordCount($uid,$historyGroupData)
    {
        foreach ($historyGroupData as $key=>$val){
            $alreadyGroupNum =  Db::table('yx_learned_history')->where('user_id',$uid)->where('group',$val['group'])->count();
            $historyGroupData[$key]['already_group_num'] = $alreadyGroupNum;
        }
        return $historyGroupData;
    }


    public static function addUserHistory($uid,$data,$answerResult)
    {
        $result = Db::table('yx_learned_history')->where('user_id',$uid)->where('word_id',$data['word_id'])->field('id,group,user_id,stage,word_id,is_true')->find();

        if(empty($result)){
            $arr = [
                'user_id'=>$uid,
                'group'=>$data['group'],
                'stage'=>$data['stage'],
                'word_id'=>$data['word_id'],
                'is_true'=>$answerResult,
                'create_time'=>time()
            ];
            return Db::table('yx_learned_history')->insert($arr);
        }

        return Db::table('yx_learned_history')->where('user_id',$uid)->where('word_id',$data['word_id'])->update(['is_true'=>$answerResult,'create_time'=>time()]);
    }

    /**
     * 用户今日已学多少个单词
     * @param $uid
     * @return float|int|string
     */
    public static function getTodayLearnedNumber($uid)
    {
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $where[] = ['create_time', 'between time', [$beginToday, $endToday]];
        return Db::table('yx_learned_history')->where('user_id',$uid)->where($where)->count();
    }

    public static function getAllLearnedNumber($uid)
    {
        return Db::table('yx_learned_history')->where('user_id',$uid)->count();
    }

    /**
     * 用户学习 年-月-日 信息
     * @param $uid
     * @return array
     */
    public static function calendarDays($uid)
    {
        $data = Db::table('yx_learned_history')->where('user_id',$uid)->select();
        $new_arr = [];
        foreach ($data as $key=>$val){
            $calendar = date("Y-m-d",$val['create_time']);
            array_push($new_arr,$calendar);
        }
        //array_flip(array_flip($new_arr));

        return array_values(array_unique($new_arr));
    }

    /**
     * 每个用户坚持学习天数
     * @param $userTodayLearnedNumber
     */
    public static function LearnedDays($userTodayLearnedNumber)
    {
        foreach ($userTodayLearnedNumber as $key=>$val){
            $LearnedNumber= self::calendarDays($val['user_id']);
            $userTodayLearnedNumber[$key]['learned_days'] = count($LearnedNumber);
        }

       return $userTodayLearnedNumber;
    }


    /**
     * 用户每个阶段已学单词数量
     * @param $uid
     * @param $stages
     * @return mixed
     */
    public static function getWordNumberByStage($uid,$stages)
    {

        foreach ($stages as $key=>$val){
            $count = Db::table('yx_learned_history')->where('stage',$val['id'])->where('user_id',$uid)->count();
            $stages[$key]['alreadyNum']=$count;
        }
       return $stages;
    }

    /**
     * 获取用户最后一次答题组下的正确率
     * @param $lastLearnedData
     * 返回百分比
     */
    public static function getTrueRate($lastLearnedData)
    {
        $data = Db::table('yx_learned_history')->where('user_id',$lastLearnedData['user_id'])->where('group',$lastLearnedData['group'])->where('stage',$lastLearnedData['stage'])->select();
        $i = 0;
        foreach ($data as $key=>$val){
            if($val['is_true'] == 1){
                $i++;
            }
        }
        $count = count($data);
        $ct5=round($i/$count*100,2)."%";
        return $ct5;
    }

    /**
     * 获取用户超过所在班级的百分比
     * @param $classData
     * @param $lastLearnedData
     */
    public static function classTrueRate($classData,$lastLearnedData)
    {
        $allUserData = [];
        foreach ($classData as $key=>$val){
            $res = Db::table('yx_learned_history')->where('user_id',$val['user_id'])->where('group',$lastLearnedData['group'])->where('stage',$lastLearnedData['stage'])->select();
            array_push($allUserData,$res);
        }

        $new_arr = [];

        foreach ($allUserData as $key=>$val){

            if(empty($val)){
               unset($allUserData[$key]);
            }

            $i = 0;
            foreach ($val as $k=>$v){
                if($v['is_true'] == 1){
                    $i++;
                    $arr['user_id']=$v['user_id'];
                    $arr['true_num']=$i+1;
                }
                $arr['user_id']=$v['user_id'];
                $arr['true_num']=$i+1;
            }
            array_push($new_arr,$arr);
        }

        // 取得列的列表
        foreach ($new_arr as $key => $row)
        {
            $edition[$key] = $row['true_num'];
        }

        array_multisort($edition, SORT_ASC, $new_arr);


        //$arr = array_column($new_arr,NULL,'true_num');  当true_num 值一样有bug
        //sort($arr);


        foreach ($new_arr as $x=>$y){
            if($y['user_id']==$lastLearnedData['user_id']){
                $nowNum =  $x+1;
            }
        }

        $count = count($new_arr);
        $classTrueRate = round($nowNum/$count*100,2)."%";

        return $classTrueRate;

    }

    /**
     * 查看班级下某个用户今天所学多少个单词
     * @param $classData
     */
    public static function getUserTodayLearnedNumber($classData)
    {

        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $where[] = ['create_time', 'between time', [$beginToday, $endToday]];


        foreach ($classData as $key=>$val){

             $count = Db::table('yx_learned_history')->where('user_id',$val['user_id'])->where($where)->count();
             $classData[$key]['today_learned_number'] = $count;
        }

        // 取得列的列表
        foreach ($classData as $key => $row)
        {
            $edition[$key] = $row['today_learned_number'];
        }

        array_multisort($edition, SORT_DESC, $classData);

        return $classData;
    }

    /**
     * 查看班级下每个用户一共学了多少单词
     * @param $classData
     */
    public static function getUseLearnedNumber($classData)
    {
        foreach ($classData as $key=>$val){

            $count = Db::table('yx_learned_history')->where('user_id',$val['user_id'])->count();
            $classData[$key]['all_learned_number'] = $count;
        }

        // 取得列的列表
        foreach ($classData as $key => $row)
        {
            $edition[$key] = $row['all_learned_number'];
        }

        array_multisort($edition, SORT_DESC, $classData);

        return $classData;
    }


}