<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/7
 * Time: 15:22
 */

namespace app\api\controller\v1;

use app\api\model\EnglishWord;
use app\api\model\GroupWord;
use app\api\model\UserClass;
use app\api\model\Group;
use app\api\model\LearnedHistory;
use app\api\model\Stage;
use app\api\model\User;
use app\api\model\Share;
use app\api\service\Token;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;

class Settlement
{

    /**
     * 请求结算页说明完成本组学习，则用户打卡
     * @return \think\response\Json
     * @throws MissException
     */
    public function getSettlementInfo()
    {
        //根据token获取用户刚才所学阶段名称，组名称
        //用户头像，昵称，学习天数，正确率，超过班级百分比
        $uid = Token::getCurrentTokenVar('uid');
        $res = Share::userPunchCard($uid);
        if(!$res){
            throw new MissException([
                'msg' => '你今天已经打过卡了',
                'errorCode' => 50000
            ]);
        }

        $lastLearnedData = LearnedHistory::UserLearned($uid);

        if(empty($lastLearnedData)){
            throw new MissException([
                'msg' => '请先进行学习，在计算(⊙o⊙)哦',
                'errorCode' => 50000
            ]);
        }
        //获取用户最后一次答题组下的正确率
        $trueRate = LearnedHistory::getTrueRate($lastLearnedData);
        //超过全班百分比
        //获取此用户所属班级，并且判断此班级下所有用户，并且根据用户在这一组的正确率进行求百分比
        $classTrueRate = $this->percentageOfClass($uid,$lastLearnedData);
        $stageData = Stage::findStage($lastLearnedData['stage']);
        $stageName = $stageData['stage_name'];
        $groupName = Group::findGroupName($lastLearnedData['group']);
        $userInfo = User::getUserInfo($uid);
        $punchDays = Share::getPunchDays($uid);
        $data = [
            'stage_name'=>&$stageName,
            'group_name'=>&$groupName,
            'user_name'=>&$userInfo['user_name'],
            'nick_name'=>&$userInfo['nick_name'],
            'avatar_url'=>&$userInfo['avatar_url'],
            'punch_days'=>&$punchDays,
            'true_rate'=>&$trueRate,
            'class_true_rate'=>&$classTrueRate
        ];

        if(!$data){
            throw new MissException([
                'msg' => '用户结算信息查询失败',
                'errorCode' => 50000
            ]);
        }

        return json($data);
    }

    /**
     * 超过全班百分比
     * 根据每个班级下，每个用户，每个组下答题正确率来计算百分比
     * @param $uid
     */
    private function percentageOfClass($uid,$lastLearnedData)
    {

        $classData = UserClass::getAllUserByUid($uid);
        if(empty($classData)){
            throw new MissException([
                'msg' => '你不是班级学员(⊙o⊙)哦',
                'errorCode' => 50000
            ]);
        }
        //判断此阶段下此组，所有用户答对的单词
        $classTrueRate = LearnedHistory::classTrueRate($classData,$lastLearnedData);
        return $classTrueRate;
    }

    public function getAgainInfo()
    {
        //根据token获取用户最后一次学习的哪一阶段，哪一组信息，重新查询一遍详情进行返回
        $uid = Token::getCurrentTokenVar('uid');
        $historyLearnedData = LearnedHistory::UserLearned($uid);
        $lastGroupID = Group::userLastSortID($historyLearnedData);
        //根据最后一次阶段id和组id查询group表确定属于某阶段的某一组信息

        //然后最后一次学习组的id进行查询这组下共有多少个单词
        $groupWord = GroupWord::selectGroupWord($lastGroupID);
        //然后根据每个组的详情进行查询每个单词的详情
        $wordDetail = EnglishWord::getNextWordDetail($groupWord);
        if(!$wordDetail){
            throw new MissException([
                'msg' => '重新来过信息查询失败',
                'errorCode' => 50000
            ]);
        }

        return json($wordDetail);
    }


    public function nextGroupInfo()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $userInfo = User::getUserInfo($uid);
        $lastSortID = Group::userLastGroupID($userInfo);
        $nextSortID = $lastSortID+1;
        //先判断下一组还有没有单词
        $res = Group::findLastGroupID(['stage'=>$userInfo['now_stage'],'sort'=>$nextSortID]);

        if(empty($res)){
            throw new SuccessMessage([
                'msg' => '此阶段已经没有下一组单词了呀'
            ]);
            //return json(['msg'=>'此阶段已经没有下一组单词了呀','errorCode'=>6000]);
        }

        $groupWord = GroupWord::selectGroupWord($res);
        $wordDetail = EnglishWord::getNextWordDetail($groupWord);

        if($wordDetail['count'] == 0){
            throw new SuccessMessage([
                'msg' => '此分组下没有单词啦呀'
            ]);
        }

        return json($wordDetail);
    }
}