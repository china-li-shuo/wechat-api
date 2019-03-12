<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 16:23
 */

namespace app\api\model;


use app\lib\exception\MissException;
use think\Db;
use think\Model;

class EnglishWord extends Model
{
    const PREFIX = 'yx_question.yx_';
    protected $connection = [
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => '202.85.213.24',
        // 数据库名
        'database'        => 'yx_question',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => 'success2017+_)(',
        // 端口
        'hostport'        => '5203',
        // 连接dsn
        'dsn'             => '',
        // 数据库连接参数
        'params'          => [],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => 'yx_',
        // 数据库调试模式
        'debug'           => false,
    ];

    public static function findFirst()
    {
        return EnglishWord::where('group',1)->select()->toArray();
    }

    /**
     * 判断用户最后一次学了第几阶段的第几组，
     * @param $LearnedData
     * @return array
     */
    public static function findLastWord($LearnedData)
    {
        $data = EnglishWord::where('group',$LearnedData['group'])->where('stage',$LearnedData['stage'])->where('id','>=',$LearnedData['word_id'])->select()->toArray();
        $countWord = EnglishWord::where('group',$LearnedData['group'])->select();
        $count = count($countWord);
        return ['data'=>$data,'count'=>$count];
    }

    public static function notWordData($notLearnedData)
    {
        $prefix = config('secure.prefix');
        foreach ($notLearnedData as $key=>$val){
            $data = Db::table($prefix.'english_word')->where('id',$val['wid'])->find();

            $notLearnedData[$key]['son']=$data;
        }

        return $notLearnedData;
    }

    public static function formatConversion($notWordData,$currentNumber)
    {
        foreach ($notWordData as $key=>$val){
            foreach ($val as $k=>$v){
                $notWordData[$key]['son']['chinese_word'] = explode('@',$v['chinese_word']);
                $notWordData[$key]['son']['options'] = json_decode($v['options'],true);
                $notWordData[$key]['son']['sentence'] = json_decode($v['sentence'],true);
                $notWordData[$key]['son']['currentNumber'] = $currentNumber+$key;
            }

        }

       return $notWordData;
    }
    /**
     * 判断用户答题结果
     */
    public static function answerResult($data)
    {

        $answer = EnglishWord::where('id',$data['word_id'])->field('answer')->find()->toArray();

        if($data['useropt'] == $answer['answer']){
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

        foreach ($historyData as $key=>$val){
            $data = EnglishWord::where('id',$val['word_id'])->find()->toArray();

            if($val['word_id'] == $data['id']){
                $historyData[$key]['son'] = $data;
            }

        }

        foreach ($historyData as $key=>$val){

            $historyData[$key]['create_time'] = date('Y-m-d', $val['create_time']);
            $historyData[$key]['son']['chinese_word'] = explode('@',$val['son']['chinese_word']);
            $historyData[$key]['son']['sentence'] = json_decode($val['son']['sentence'],true);
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

        foreach ($groupWord as $key=>$val){
            $data = Db::table(self::PREFIX.'english_word')->where('id',$val['wid'])->find();
            if($val['wid'] == $data['id']){
                $groupWord[$key]['son'] = $data;
            }

        }

        foreach ($groupWord as $key=>$val){
              if(array_key_exists('son',$val)){
                  $groupWord[$key]['son']['chinese_word'] = explode('@',$val['son']['chinese_word']);
                  $groupWord[$key]['son']['options'] = json_decode($val['son']['options'],true);
                  $groupWord[$key]['son']['answer'] = $val['son']['answer'];
                  $groupWord[$key]['son']['sentence'] = json_decode($val['son']['sentence'],true);
                  $groupWord[$key]['son']['currentNumber'] = $key+1;
                  //unset($groupWord[$key]['son']['options']);
                  //unset($groupWord[$key]['son']['answer']);
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
        foreach ($result as $key=>$val){
            $data = Db::table(self::PREFIX.'english_word')->where('id',$val['word_id'])->find();
            if($val['word_id'] == $data['id']){
            $result[$key]['son'] = $data;
           }
        }

        foreach ($result as $key=>$val){
            if(array_key_exists('son',$val)){
                $result[$key]['son']['chinese_word'] = explode('@',$val['son']['chinese_word']);
                $result[$key]['son']['sentence'] = json_decode($val['son']['sentence'],true);
                unset($result[$key]['son']['options']);
                unset($result[$key]['son']['answer']);
            }

        }
        return $result;
    }
}