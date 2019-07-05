<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/13
 * Time: 11:09
 */


namespace app\api\model;


class LearnedHistory extends BaseModel
{

    /**
     * 查看班级下某个用户今天所学多少个单词
     * @param $classData
     */
    public static function getUserTodayLearnedNumber($classData)
    {
        foreach ($classData as &$val) {
            $count =self::where('user_id', $val['user_id'])
                ->where(whereTime())
                ->count();
            $val['today_learned_number'] = $count;
        }
        // 取得列的列表
        foreach ($classData as $key => $row) {
            $edition[$key] = $row['today_learned_number'];
        }
        array_multisort($edition, SORT_DESC, $classData);
        return $classData;
    }

    public static function getGroupByStageID($uid, $stage_id)
    {
        $data = self::where(
            ['user_id'=>$uid,
             'stage'=>$stage_id])
            ->field('group')
            ->group('group')
            ->select();
        return $data;
    }

    /**
     * 添加历史学习记录，并且用户表记录对应修改
     */
    public static function addUserHistory($uid, $data, $answerResult)
    {
        $result = self::where(
            [
                'user_id'=>$uid,
                'group'=>$data['group'],
                'word_id'=>$data['word_id']
            ])->field('id,group,user_id,stage,word_id,is_true')
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
            $res = self::create($arr);
            //学习记录表数据能够进行插入才修改用户记录信息
            if ($res) {
                $userinfo = User::field('already_number')
                    ->get($uid);
                $arr      = [
                    'already_number' => $userinfo['already_number'] + 1,
                    'now_stage'      => $data['stage'],
                    'now_group'      => $data['group'],
                ];
                return User::where('id', $uid)
                    ->update($arr);
            }
        }
        $arr = [
            'now_stage' => $data['stage'],
            'now_group' => $data['group'],
        ];

        User::where('id', $uid)
            ->update($arr);

        self::where([
                'user_id'=>$uid,'group'=>$data['group'],'word_id'=> $data['word_id'],
            ])->update([
                'is_true' => $answerResult,
                'create_time'=>time()]);

        return true;
    }

    /**
     * 获取自己在班级成员在这阶段这一组的正确率
     */
    public static function getClassTrueRate($classData, $data)
    {
        $childData= LearnedChild::where([
            'class_id'=>$data['class_id'],
            'group'=>$data['group'],
            'stage'=>$data['stage'],
        ]) ->field('user_id,mastered_number')
           ->select();
        if (!empty($childData)){
            $childData = $childData->toArray();
        }
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
}