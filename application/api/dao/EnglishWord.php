<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 16:23
 */

namespace app\api\dao;


use think\Db;
use think\Model;

class EnglishWord extends Model
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'yx_english_word';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_2';

    public static function findFirst()
    {
        return EnglishWord::where('group', 1)->select()->toArray();
    }

    /**
     * 判断用户最后一次学了第几阶段的第几组，
     * @param $LearnedData
     * @return array
     */
    public static function findLastWord($LearnedData)
    {
        $data = EnglishWord::where('group', $LearnedData['group'])
            ->where('stage', $LearnedData['stage'])
            ->where('id', '>=', $LearnedData['word_id'])
            ->select()
            ->toArray();
        $countWord = EnglishWord::where('group', $LearnedData['group'])->select();
        $count     = count($countWord);
        return ['data' => $data, 'count' => $count];
    }

    public static function notWordData($notLearnedData)
    {
        foreach ($notLearnedData as $key => $val) {
            $data = Db::table(YX_QUESTION . 'english_word')
                ->where('id', $val['wid'])
                ->find();
            $notLearnedData[$key]['is_collection'] = 2;
            $notLearnedData[$key]['son']           = $data;
        }

        return $notLearnedData;
    }

    public static function formatConversion($notWordData, $currentNumber)
    {
        $us_audio = config('setting.audio_prefix');
        foreach ($notWordData as $key => $val) {
            foreach ($val as $k => $v) {
                $notWordData[$key]['son']['chinese_word']  = explode('@', $v['chinese_word']);
                $notWordData[$key]['son']['options']       = json_decode($v['options'], true);
                $notWordData[$key]['son']['sentence']      = json_decode($v['sentence'], true);
                $notWordData[$key]['son']['currentNumber'] = $currentNumber + $key;
                $notWordData[$key]['son']['us_audio']      = $us_audio . $v['us_audio'];
            }

        }

        return $notWordData;
    }

    /**
     * 判断用户答题结果
     */
    public static function answerResult($data)
    {
        //先进性查询分组的类型1、普通类型；2、同义词；3、一次多义；4、熟词僻义
        $groupData = Db::table(YX_QUESTION . 'group')
            ->where('id', $data['group'])
            ->field('type')
            ->find();
        switch ($groupData['type']) {
            case 1:
                $answer = EnglishWord::where('id', $data['word_id'])->field('answer')->find()->toArray();
                return self::checkAnswer($data['useropt'], $answer);
            default:
                $answer = Db::table(YX_QUESTION . 'english_word_s')
                    ->where('id', $data['word_id'])
                    ->field('answer')
                    ->find();
                $answer = explode(',', $answer['answer']);
                return self::checkAnswer($data['useropt'], $answer);
        }

    }

    /**
     * 校验答案正确性
     * @param $arr1     选项数组
     * @param $arr2     答案数组
     */
    private static function checkAnswer($arr1, $arr2)
    {
        $sum1 = 0;
        $sum2 = 0;
        foreach ($arr1 as $key => $val) {
            $sum1 = $sum1 + $val;
        }
        foreach ($arr2 as $key => $val) {
            $sum2 = $sum2 + $val;
        }
        if ($sum1 == $sum2) {
            return 1;
        }
        return 0;
    }

    /**
     * 获取最后单词详情
     * @param $historyData
     * @return mixed
     */
    public static function getWordDetail($historyData)
    {

        foreach ($historyData as $key => $val) {
            $data = EnglishWord::where('id', $val['word_id'])->find()->toArray();

            if ($val['word_id'] == $data['id']) {
                $historyData[$key]['son'] = $data;
            }

        }

        foreach ($historyData as $key => $val) {

            $historyData[$key]['create_time'] = date('Y-m-d', $val['create_time']);
            $historyData[$key]['son']['chinese_word'] = explode('@', $val['son']['chinese_word']);
            $historyData[$key]['son']['sentence']  = json_decode($val['son']['sentence'], true);
            unset($historyData[$key]['son']['options']);
            unset($historyData[$key]['son']['answer']);
        }

        return $historyData;
    }

    /**
     * 获取下一组单词详情
     * @param $historyData
     * @return mixed
     */
    public static function getNextWordDetail($groupWord)
    {
        foreach ($groupWord as $key => $val) {
            $data = Db::table(YX_QUESTION . 'english_word')
                ->where('id', $val['wid'])
                ->find();
            $stage = Db::table(YX_QUESTION . 'group')
                ->where('id', $val['group'])
                ->field('stage_id')
                ->find();
            $groupWord[$key]['stage'] = $stage['stage_id'];
            if ($val['wid'] == $data['id']) {
                $groupWord[$key]['is_collection'] = 2;
                $groupWord[$key]['son']           = $data;
            }
        }
        $us_audio = config('setting.audio_prefix');
        foreach ($groupWord as $key => $val) {
            //查询是否收藏过该单词
            if (array_key_exists('son', $val)) {
                $groupWord[$key]['son']['chinese_word']  = explode('@', $val['son']['chinese_word']);
                $groupWord[$key]['son']['options']       = json_decode($val['son']['options'], true);
                $groupWord[$key]['son']['answer']        = $val['son']['answer'];
                $groupWord[$key]['son']['sentence']      = json_decode($val['son']['sentence'], true);
                $groupWord[$key]['son']['currentNumber'] = $key + 1;
                $groupWord[$key]['son']['us_audio']      = $us_audio . $val['son']['us_audio'];
            }

        }
        $groupWord['count'] = count($groupWord);
        return $groupWord;
    }

    /**
     * 查询每个单词的详情
     * @param $data
     */
    public static function selectWordDetail($result)
    {
        foreach ($result as $key => $val) {
            $data = Db::table(YX_QUESTION . 'english_word')
                ->where('id', $val['word_id'])
                ->find();
            if ($val['word_id'] == $data['id']) {
                $result[$key]['son'] = $data;
            }
        }
        $us_audio = config('setting.audio_prefix');
        foreach ($result as $key => $val) {
            if (array_key_exists('son', $val)) {
                $result[$key]['son']['chinese_word'] = explode('@', $val['son']['chinese_word']);
                $result[$key]['son']['sentence']     = json_decode($val['son']['sentence'], true);
                $result[$key]['son']['us_audio']     = $us_audio . $val['son']['us_audio'];
                unset($result[$key]['son']['options']);
                unset($result[$key]['son']['answer']);
            }

        }
        return $result;
    }


    /*********************************************V4start*****************************************************/
    /**
     * 进行查找为还未学习的单词详情
     * @param $notLearnedData
     * @return mixed
     */
    public static function selectNotWordData($notLearnedData)
    {
        //根据类型查找对应的单词库，1、普通类型；2、同义词；3、一次多义；4、熟词僻义
        switch ($notLearnedData[0]['type']) {
            case 1: //普通词汇
                foreach ($notLearnedData as $key => &$val) {
                    $data = Db::table(YX_QUESTION . 'english_word')
                        ->where('id', $val['wid'])
                        ->field('id,english_word,chinese_word,options,answer,sentence,us_audio,us_phonetic')
                        ->find();
                    $val['is_collection'] = 2;
                    $val['son'] = $data;
                }

                return $notLearnedData;
            case 5: //长难句
                foreach ($notLearnedData as $key => &$val) {
                    $data = Db::table(YX_QUESTION . 'sentences')
                        ->where('id', $val['wid'])
                        ->field('id,long_sentence,word_parsing,sentence_splitting,parsing,translation,analysis')
                        ->find();
                    $val['is_collection'] = 2;
                    $val['son'] = $data;
                }

                return $notLearnedData;
            default: //同义词，熟词僻义，一词多义
                foreach ($notLearnedData as $key => &$val) {
                    $data = Db::table(YX_QUESTION . 'english_word_s')
                        ->where('id', $val['wid'])
                        ->field('id,english_word,select_title,type,options,answer,sentence,us_audio,us_phonetic')
                        ->find();
                    $val['is_collection'] = 2;
                    $val['son'] = $data;
                }

                return $notLearnedData;
        }
    }

    /**
     * 根据类型格式转换
     * @param $notWordData
     * @param $currentNumber
     * @return mixed
     */
    public static function conversionByTypeFormat($notWordData, $currentNumber)
    {
        //单词表已的音频路径
        $us_audio = config('setting.audio_prefix');
        //根据类型进行不同的格式转换，1、普通类型；2、同义词；3、一次多义；4、熟词僻义
        switch ($notWordData[0]['type']) {
            case 1://普通类型
                foreach ($notWordData as $key => &$val) {
                    $val['son']['chinese_word']  = explode('@', $val['son']['chinese_word']);
                    $val['son']['answer']        = explode(',', $val['son']['answer']);
                    $val['son']['options']       = json_decode($val['son']['options'], true);
                    $val['son']['sentence']      = json_decode($val['son']['sentence'], true);
                    $val['son']['currentNumber'] = $currentNumber + $key;
                    $val['son']['us_audio']      = $us_audio . $val['son']['us_audio'];
                }
                return $notWordData;
            case 2://同义词，则需查找关联表
                foreach ($notWordData as $key => &$val) {
                    unset($val['son']['sentence']);
                    unset($notWordData[$key]['son']['us_audio']);
                    unset($val['son']['us_phonetic']);
                    $val['son']['answer']  = explode('@', $val['son']['answer']);
                    $val['son']['options'] = json_decode($val['son']['options'], true);
                    $val['son']['currentNumber'] = $currentNumber + $key;
                    $val['son']['detail']  = Db::table(YX_QUESTION . 'synonym')
                        ->alias('s')
                        ->join(YX_QUESTION . 'english_word e', 'e.id=s.wid')
                        ->field('e.id,s.wid,e.english_word,e.chinese_word,e.sentence,e.us_audio,e.us_phonetic')
                        ->where('s.sid', $val['son']['id'])
                        ->select();
                    foreach ($notWordData[$key]['son']['detail'] as $k => &$v) {
                        $v['son']['detail'][$k]['chinese_word'] = explode('@', $v['chinese_word']);
                        $v['son']['detail'][$k]['sentence']     = json_decode($v['sentence'], true);
                        $v['son']['detail'][$k]['us_audio']     = $us_audio . $v['us_audio'];
                    }
                    continue;
                }
                return $notWordData;
            case 5:
                foreach ($notWordData as $key => &$val) {
                    $val['son']['word_parsing']  = json_decode($val['son']['word_parsing'], true);
                    $val['son']['sentence_splitting'] = json_decode($val['son']['sentence_splitting'], true);
                    $val['son']['currentNumber'] = $currentNumber + $key;
                    foreach ($val['word_parsing'] as $k=>&$v){
                        $v['son']['us_audio']      = $us_audio . $v['son']['us_audio'];
                    }

                }
                return $notWordData;
            default://type3一词多义，type4熟词僻义
                foreach ($notWordData as $key => &$val) {
                    $val['son']['answer']        = explode(',',$val['son']['answer']);
                    $val['son']['options']       = json_decode($val['son']['options'], true);
                    $val['son']['sentence']      = json_decode($val['son']['sentence'], true);
                    $val['son']['currentNumber'] = $currentNumber + $key;
                    $val['son']['us_audio']      = $us_audio . $val['son']['us_audio'];
                }
                return $notWordData;
        }

    }
}