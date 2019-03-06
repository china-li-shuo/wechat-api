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
use app\api\model\Group;
use app\api\model\GroupWord;
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
            $notLearnedData = GroupWord::findFirst();
            $notLearnedData = Group::correspondingStage($notLearnedData);
            $notWordData = EnglishWord::notWordData($notLearnedData);
            $notLearnedData = EnglishWord::formatConversion($notWordData,1);
            $notLearnedData['count'] = count($notLearnedData);
            return json($notLearnedData);
        }

        //用户最后一次学习第几组共有多少单词
        $allData = Group::getAllData($LearnedData);   //25

        //用户还未学习的组信息
        $notLearnedData = Group::getGroupData($LearnedData);  //23

        //查询此组对应的阶段
        $notLearnedData = Group::correspondingStage($notLearnedData);

        //用户已学习这组下的第几个数量
        $currentNumber =  LearnedHistoryModel::userLearnedCurrentNumber($LearnedData);  //2

        //用户还没有学习单词的详情
        $notWordData = EnglishWord::notWordData($notLearnedData);
        $notWordData = EnglishWord::formatConversion($notWordData,$currentNumber+1);

        $notWordData['count'] = count($allData);
        return json($notWordData);
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