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
}