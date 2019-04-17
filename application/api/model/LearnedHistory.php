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
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->field('id,group,user_id,stage,word_id,is_true')
            ->order('create_time desc')
            ->limit(1)
            ->find();
    }

    public static function UserLearnedCommon($uid)
    {
        //公共词汇id
        $commonID = Stage::CommonStageID();
        $stageIDS = Stage::selectStageData($commonID);
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->where('stage', 'in', $stageIDS)
            ->field('id,group,user_id,stage,word_id,is_true')
            ->order('create_time desc')
            ->limit(1)
            ->find();
    }

    /**
     * 开始学习不是公共区域的
     * @param $uid
     * @return array|null|\PDOStatement|string|Model
     */
    public static function UserLearnedList($uid)
    {
        //公共词汇id
        $commonID = Stage::CommonStageID();
        $stageIDS = Stage::selectStageData($commonID);
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->where('stage', 'not in', $stageIDS)
            ->field('id,group,user_id,stage,word_id,is_true')
            ->order('create_time desc')
            ->limit(1)
            ->find();
    }

    public static function LearnedAll($uid)
    {

        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->select();

    }

    /**
     * 用户所学所有阶段信息,阶段名称
     * @param $uid
     */
    public static function LearnedStage($uid)
    {
        $data = Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->group('stage')
            ->field('id,stage')
            ->select();

        foreach ($data as $key => $val) {
            $stage                    = Db::table(YX_QUESTION . 'stage')
                ->where('id', $val['stage'])
                ->field('stage_name')
                ->find();
            $data[$key]['stage_name'] = &$stage['stage_name'];
        }

        return $data;
    }

    /**
     * 获取阶段下所有组，组名称
     * @param $historyData
     */
    public static function LearnedGroup($uid, $historyData)
    {

        foreach ($historyData as $key => $val) {

            $data = Db::table('yx_learned_history')
                ->where('user_id', $uid)
                ->where('stage', $val['stage'])
                ->group('group')
                ->field('id,stage,group')
                ->select();

            foreach ($data as $k => $v) {

                $group = Db::table(YX_QUESTION . 'group')
                    ->where('id', $v['group'])
                    ->field('id,group_name')
                    ->find();

                $data[$k]['son']        = $group;
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
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->field('id,group,user_id,stage,word_id,is_true')
            ->count();
    }

    public static function UserCountStageGroup($uid, $id)
    {
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->where('stage', $id)
            ->field('id,group,user_id,stage,word_id,is_true')
            ->count();
    }

    /**
     * 用户历史共学了多少组,分别每个组的信息
     * @param $uid
     */
    public static function getUserGroupData($uid)
    {
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->group('group')
            ->field('group')
            ->select();
    }

    public static function getUserStageGroupData($uid, $id)
    {
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->where('stage', $id)
            ->group('group')
            ->field('group')
            ->select();
    }

    /**
     * 返回用户最后一次学习了第几组的第几个单词
     * @param $LearnedData
     * @return int
     */
    public static function userLearnedCurrentNumber($LearnedData)
    {
        $currentNumber = Db::table('yx_learned_history')
            ->where('user_id', $LearnedData['user_id'])
            ->where('group', $LearnedData['group'])
            ->field('id')
            ->select();

        return count($currentNumber);
    }

    /**
     * 用户已学过的组下，在这个组学了多少个单词
     * @param $historyGroupData
     */
    public static function getAlreadyLearnedGroupWordCount($uid, $historyGroupData)
    {
        foreach ($historyGroupData as $key => $val) {

            $alreadyGroupNum = Db::table('yx_learned_history')
                ->where('user_id', $uid)
                ->where('group', $val['group'])
                ->count();

            $historyGroupData[$key]['already_group_num'] = $alreadyGroupNum;
        }
        return $historyGroupData;
    }


    /**
     * 添加历史学习记录，并且用户表记录对应修改
     * @param $uid
     * @param $data
     * @param $answerResult
     * @return int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public static function addUserHistory($uid, $data, $answerResult)
    {
        $result = Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->where('group', $data['group'])
            ->where('word_id', $data['word_id'])
            ->field('id,group,user_id,stage,word_id,is_true')
            ->find();
        if (empty($result)) {
            $arr = [
                'user_id'     => $uid,
                'group'       => $data['group'],
                'stage'       => $data['stage'],
                'word_id'     => $data['word_id'],
                'is_true'     => $answerResult,
                'create_time' => time()
            ];
            $res = Db::table('yx_learned_history')->insert($arr);
            //学习记录表数据能够进行插入才修改用户记录信息
            if ($res) {
                //进行同步redis有序集合
                //self::isTodayLearned($uid,$data);

                $userinfo = Db::table('yx_user')
                    ->where('id', $uid)
                    ->field('already_number')
                    ->find();
                $arr      = [
                    'already_number' => $userinfo['already_number'] + 1,
                    'now_stage'      => $data['stage'],
                    'now_group'      => $data['group'],
                ];
                return Db::table('yx_user')
                    ->where('id', $uid)
                    ->update($arr);
            }
        }
        $arr = [
            'now_stage' => $data['stage'],
            'now_group' => $data['group'],
        ];

        Db::name('user')->where('id', $uid)->update($arr);
        Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->where('word_id', $data['word_id'])
            ->update(['is_true' => $answerResult]);

        return true;
    }

    /**
     * 用户今日已学多少个单词
     * @param $uid
     * @return float|int|string
     */
    public static function getTodayLearnedNumber($uid)
    {
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $where[]    = ['create_time', 'between time', [$beginToday, $endToday]];
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->where($where)
            ->count();
    }

    /**
     * 判断此阶段此分组此单词是否是今天
     * @param $uid
     * @param $data
     */
    public static function isTodayLearned($uid, $data)
    {
        //查询用户所属的班级名称，
        $classInfo = UserClass::getClassInfo($uid);
        $date      = date("Y-m-d", time());
        $className = UserClass::getClassName($classInfo);
        if (empty($className)) {
            $className = '互联网';
        }
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $where[]    = ['create_time', 'between time', [$beginToday, $endToday]];
        //查询用户此阶段次单词是否是今日所学
        $todayData = Db::name('learned_history')
            ->where('user_id', $uid)
            ->where($where)
            ->where('group', $data['group'])
            ->where('stage', $data['stage'])
            ->where('word_id', $data['word_id'])
            ->find();
        if (empty($todayData)) {
            //不是今日所学单词
            return true;
        }
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        // Redis 没设置密码则不需要这行代码
        // $redis->auth('opG5dGo9feYarUifaLb8AdjKcAAXArgZ');
        //zadd 海淀一班级 5  李四
        //$res = $redis->zScore($className.$date,$uid);
        $redis->zIncrBy($className . $date, 1, $uid);
        return true;
    }

    public static function getAllLearnedNumber($uid)
    {
        return Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->count();
    }

    /**
     * 用户学习 年-月-日 信息
     * @param $uid
     * @return array
     */
    public static function calendarDays($uid)
    {
        $data    = Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->select();
        $new_arr = [];

        foreach ($data as $key => $val) {
            $calendar = date("Y-m-d", $val['create_time']);
            array_push($new_arr, $calendar);
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
        foreach ($userTodayLearnedNumber as $key => $val) {
            $LearnedNumber                                = self::calendarDays($val['user_id']);
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
    public static function getWordNumberByStage($uid, $stages)
    {

        foreach ($stages as $key => $val) {

            $count = Db::table('yx_learned_history')
                ->where('stage', $val['id'])
                ->where('user_id', $uid)
                ->count();

            $stages[$key]['alreadyNum'] = $count;
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
        $data = Db::table('yx_learned_history')
            ->where('user_id', $lastLearnedData['user_id'])
            ->where('group', $lastLearnedData['group'])
            ->where('stage', $lastLearnedData['stage'])
            ->select();
        $i    = 0;
        foreach ($data as $key => $val) {
            if ($val['is_true'] == 1) {
                $i++;
            }
        }
        $count = count($data);
        $ct5   = round($i / $count * 100, 2) . "%";
        return $ct5;
    }

    /**
     * 获取用户此班级下此阶段此组的个人正确率
     * @param $data
     */
    public static function personalCorrectnessRate($data)
    {
        $groupNum = Db::table(YX_QUESTION.'group')
            ->where('id',$data['group'])
            ->field('word_num')
            ->find();

        $trueNum = Db::name('learned_child')
            ->where('user_id',$data['user_id'])
            ->where('stage',$data['stage'])
            ->where('group',$data['group'])
            ->field('mastered_number')
            ->find();
        if(!empty($groupNum) && !empty($trueNum)){
            return round($trueNum['mastered_number'] / $groupNum['word_num'] * 100, 2) . "%";
        }
    }
    /**
     * 获取用户超过所在班级的百分比
     * @param $classData
     * @param $lastLearnedData
     */
    public static function classTrueRate($classData, $lastLearnedData)
    {
        $allUserData = [];
        foreach ($classData as $key => $val) {

            $res = Db::table('yx_learned_history')
                ->where('user_id', $val['user_id'])
                ->where('group', $lastLearnedData['group'])
                ->where('stage', $lastLearnedData['stage'])
                ->select();

            array_push($allUserData, $res);
        }

        $new_arr = [];

        foreach ($allUserData as $key => $val) {

            if (empty($val)) {
                unset($allUserData[$key]);
                continue;
            }

            $i = 0;
            foreach ($val as $k => $v) {
                if ($v['is_true'] == 1) {
                    $i++;
                    $arr['user_id']  = $v['user_id'];
                    $arr['true_num'] = $i + 1;
                }
                $arr['user_id']  = $v['user_id'];
                $arr['true_num'] = $i + 1;
            }
            array_push($new_arr, $arr);
        }

        // 取得列的列表
        foreach ($new_arr as $key => $row) {
            $edition[$key] = $row['true_num'];
        }

        array_multisort($edition, SORT_ASC, $new_arr);

        foreach ($new_arr as $x => $y) {
            if ($y['user_id'] == $lastLearnedData['user_id']) {
                $nowNum = $x + 1;
            }
        }

        $count         = count($new_arr);
        $classTrueRate = round($nowNum / $count * 100, 2) . "%";

        return $classTrueRate;

    }

    /**
     * 获取自己在班级成员在这阶段这一组的正确率
     * @param $classData
     * @param $data
     */
    public static function getClassTrueRate($classData, $data)
    {
        $childData= Db::name('learned_child')
            ->where('class_id', $data['class_id'])
            ->where('group', $data['group'])
            ->where('stage', $data['stage'])
            ->field('user_id,mastered_number')
            ->select();
        foreach ($classData as $key=>$val){
            foreach ($childData as $k=>$v){
                if($val['user_id'] == $v['user_id']){
                    $classData[$key]['mastered_number'] = $v['mastered_number'];
                    continue;
                }
            }
        }

        foreach ($classData as $key=>$val){
            if (empty($val['mastered_number'])){
                $classData[$key]['mastered_number'] =0;
            }
        }

        // 取得列的列表
        foreach ($classData as $key => $row) {
            $edition[$key] = $row['mastered_number'];
        }

        array_multisort($edition, SORT_ASC, $classData);

        foreach ($classData as $x => $y) {
            if ($y['user_id'] == $data['user_id']) {
                $nowNum = $x + 1;
            }
        }
        $count = count($classData);
        return round($nowNum / $count * 100, 2) . "%";
    }

    /**
     * 查看班级下某个用户今天所学多少个单词
     * @param $classData
     */
    public static function getUserTodayLearnedNumber($classData)
    {

        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday   = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $where[]    = ['create_time', 'between time', [$beginToday, $endToday]];


        foreach ($classData as $key => $val) {

            $count = Db::table('yx_learned_history')
                ->where('user_id', $val['user_id'])
                ->where($where)
                ->count();

            $classData[$key]['today_learned_number'] = $count;
        }

        // 取得列的列表
        foreach ($classData as $key => $row) {
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
        foreach ($classData as $key => $val) {

            $count  = Db::table('yx_learned_history')
                ->where('user_id', $val['user_id'])
                ->count();
            $classData[$key]['all_learned_number'] = $count;
        }

        // 取得列的列表
        foreach ($classData as $key => $row) {
            $edition[$key] = $row['all_learned_number'];
        }

        array_multisort($edition, SORT_DESC, $classData);

        return $classData;
    }

    /**
     * 用户已学习阶段和分组信息
     * @param $uid
     */
    public static function learnedInfo($uid)
    {
        $data = Db::table('yx_learned_history')
            ->where('user_id', $uid)
            ->group('stage')
            ->field('stage')
            ->select();

        foreach ($data as $key => $val) {

            $stage = Db::table(YX_QUESTION . 'stage')
                ->where('id', $val['stage'])
                ->field('stage_name')
                ->find();

            $data[$key]['stage_name'] = &$stage['stage_name'];
        }
        //print_r($data);
        //获取阶段下所有组
        foreach ($data as $k => $v) {
            $group = Db::table('yx_learned_history')
                ->where('user_id', $uid)
                ->where('stage', $v['stage'])
                ->group('group')
                ->field('group,stage')
                ->select();

            $data[$k]['group'] = $group;

            foreach ($group as $i => $j) {
                $group_name = Db::table(YX_QUESTION . 'group')
                    ->where('id', $j['group'])
                    ->field('group_name')
                    ->find();

                $data[$k]['group'][$i]['group_name'] = $group_name['group_name'];
            }
        }

        return $data;
    }

}