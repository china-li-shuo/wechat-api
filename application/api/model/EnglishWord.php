<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 16:23
 */

namespace app\api\model;


use think\Model;

class EnglishWord extends Model
{
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
}