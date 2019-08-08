<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/6
 * Time: 11:57
 */


namespace app\article\service;

use app\article\model\Sentences;
use app\article\model\User;
use app\article\model\Record as RecordModel;

class Record
{
    public function addRecord($data)
    {
        $record = RecordModel::where([
            'article_id'=>$data['article_id'],
            'user_id'=>$data['user_id']])
            ->find();
        if(empty($record)){
            //进行新增用户的学习记录
            $record = RecordModel::create($data);
            //并且修改该用户的打卡天数
            $user = User::get($data['user_id']);
            if(!empty($record)){
                $user->punch_days += 1;
                $res = $user->save();
            }else{
                $res = null;
            }
        }else{
            $res = $record->force()->save($data);
        }
        return $res;
    }

    /**
     * 获取最终的用户学习记录
     */
    public function getFinalRecord($data)
    {
        foreach ($data as &$val){
            $val['words'] = json_decode($val['words'], true);
            if(!empty($val['words'])){
                $val['words'] = $this->addAudioPrefix($val['words']);
            }
            $val['phrase'] = json_decode($val['phrase'], true);
            if(!empty($val['phrase'])){
                $val['phrase'] = $this->addAudioPrefix($val['phrase']);
            }
            $sentences = Sentences::get($val['sentence_id']);
            if(!empty($sentences)){
                $val['cnj'] = $sentences->toArray();
                $val['cnj']['word_parsing'] = json_decode($val['cnj']['word_parsing'], true);
                $val['cnj']['judgment_question'] = json_decode($val['judgment_question'],true);
                if(!empty( $val['cnj']['word_parsing'])){
                    $val['cnj']['word_parsing'] = $this->addAudioPrefix( $val['cnj']['word_parsing']);
                }
                $val['cnj']['sentence_splitting'] = json_decode($val['cnj']['sentence_splitting'], true);
            }
            unset($val['judgment_question']);
            unset($val['sentence_id']);
            continue;
        }

        return $data;
    }

    /**
     * 添加数据音频前缀
     */
    private function addAudioPrefix($data)
    {
        foreach ($data as &$val){
            $val['us_audio'] = config('setting.audio_prefix') . $val['us_audio'];
        }
       return $data;
    }
}