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
                'user_id'=>$uid, 'group'=>$data['group']
            ])->update([
                'is_true' => $answerResult,
                'create_time'=>time()]);

        return true;
    }
}