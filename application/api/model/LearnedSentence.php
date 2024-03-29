<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/21
 * Time: 10:09
 */


namespace app\api\model;


class LearnedSentence extends BaseModel
{
    public function sentenceInfo()
    {
        return $this->hasOne('Sentences','id','sentence_id');
    }

    public static function addSentence($uid, $data)
    {
        $sentence = self::where([
            'user_id'     => $uid,
            'group'       => $data['group'],
            'sentence_id' => $data['sentence_id']
        ])->find();
        //如果学习记录为空进行新增
        if(empty($sentence)){
            $data['user_id'] = $uid;
            $res = self::create($data);
            $userInfo = User::get($uid);
            $sentence_number = $userInfo->sentence_number + 1;
            User::where('id',$uid)->update([
                'sentence_number'=>$sentence_number,
                'now_stage'=>$data['stage'],
                'now_group'=>$data['group'],
            ]);
            return $res->id;
        }
        //否则进行修改
        $res = self::where([
            'user_id'     => $uid,
            'group'       => $data['group'],
            'sentence_id' => $data['sentence_id']
        ])->update($data);

        User::where('id',$uid)->update([
            'now_stage'=>$data['stage'],
            'now_group'=>$data['group'],
        ]);
        return $res;
    }

    public static function getSummaryByUid($uid, $page, $size)
    {
        $pagingData = self::with('sentenceInfo')
             ->where('user_id',$uid)
            ->order('create_time desc')
            ->visible(['sentence_id', 'stage', 'group', 'translation', 'sentence_info'])
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

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