<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 15:51
 */

namespace app\api\controller\v1;

use app\api\model\EnglishWord;
use app\api\model\ErrorBook;
use app\api\model\LearnedHistory as LearnedHistoryModel;
use app\api\service\Token;
use app\api\validate\LearnedHistory;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;

class Learned
{
    public function getList()
    {
        //先根据token获取用户的uid
        //根据uid去学习记录表中查询用户最后一次学到了第几组的第几个单词
        $uid = Token::getCurrentTokenVar('uid');

        $LearnedData = LearnedHistoryModel::UserLearned($uid);

        //如果用户没有学习记录，直接查询第一组单词
        if(empty($LearnedData)){

            $data = EnglishWord::findFirst();

            foreach ($data as $key=>$val){
                $data[$key]['chinese_word'] = explode('@',$val['chinese_word']);
                $data[$key]['options'] = json_decode($val['options'],true);
                $data[$key]['sentence'] = json_decode($val['sentence'],true);
                $data[$key]['current_number'] = $key+1;
            }
            $data['count'] = count($data);
            return json($data);
        }

        $lastWord = EnglishWord::findLastWord($LearnedData);
        $currentNumber =  LearnedHistoryModel::UserCountGroup($uid);
        $data = $lastWord['data'];

        foreach ($data as $key=>$val){
            $data[$key]['chinese_word'] = explode('@',$val['chinese_word']);
            $data[$key]['options'] = json_decode($val['options'],true);
            $data[$key]['sentence'] = json_decode($val['sentence'],true);
            $data[$key]['current_number'] = $currentNumber+$key;
        }
        $data['count'] = $lastWord['count'];
        return json($data);
    }

    public function clickNext()
    {
        //传递参数（token,group,stage,word_id,useropt）
        //根据用户选项判断用户答案是否正确
        //如果用户答错，则把错误信息写入数据库
        //然后把用户答题活动记录写入数据库，如果已存在这条记录进行修改，否则添加
        $validate = new LearnedHistory();
        $validate->goCheck();

        $uid = Token::getCurrentTokenVar('uid');

        $data = $validate->getDataByRule(input('post.'));

        $answerResult = EnglishWord::answerResult($data);

        if($answerResult == 0){
            ErrorBook::addErrorBook($uid,$data);
        }

        $res = LearnedHistoryModel::addUserHistory($uid,$data,$answerResult);

        if(!$res){
            throw new MissException([
                'msg' => '用户答题记录失败',
                'errorCode' => 50000
            ]);
        }

        throw new SuccessMessage();
    }
}