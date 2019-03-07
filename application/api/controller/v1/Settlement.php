<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/7
 * Time: 15:22
 */

namespace app\api\controller\v1;

use app\api\model\UserClass;
use app\api\model\Group;
use app\api\model\LearnedHistory;
use app\api\model\Stage;
use app\api\model\User;
use app\api\model\Share;
use app\api\service\Token;
use app\lib\exception\MissException;

class Settlement
{
    public function getSettlementInfo()
    {
        //根据token获取用户刚才所学阶段名称，组名称
        //用户头像，昵称，学习天数，正确率，超过班级百分比
        $uid = Token::getCurrentTokenVar('uid');
        $lastLearnedData = LearnedHistory::UserLearned($uid);
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
        //判断此阶段下此组，所有用户答对的单词
        $classTrueRate = LearnedHistory::classTrueRate($classData,$lastLearnedData);
        return $classTrueRate;
    }
}