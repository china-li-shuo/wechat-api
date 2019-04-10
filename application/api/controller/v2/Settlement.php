<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/28
 * Time: 13:54
 */

namespace app\api\controller\v2;

use app\api\model\Collection;
use app\api\model\EnglishWord;
use app\api\model\Group;
use app\api\model\GroupWord;
use app\api\model\LearnedHistory;
use app\api\model\Share;
use app\api\model\Stage;
use app\api\model\User;
use app\api\model\UserClass;
use app\api\service\Token;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;
use think\Db;

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
        Share::userPunchCard($uid);
        $stage = empty(input('post.stage')) ? '' : input('post.stage');
        $group = empty(input('post.group')) ? '' : input('post.group');
        if(empty($stage) || empty($group)){
            throw new MissException([
                'msg'=>'参数不全',
                'errorCode'=>10001
            ]);
        }
        $lastLearnedData = [
            'user_id'=>$uid,
            'group'=>$group,
            'stage'=>$stage
        ];

        $userInfo = cache('settlement_'.$uid.$stage.$group);

        if(empty($userInfo)){
            $userInfo  = User::field('is_teacher,now_stage,user_name,nick_name,avatar_url')->get($uid)->toArray();
            $stageData       = Stage::findStage($stage);
            $stageName       = $stageData['stage_name'];
            $groupName       = Group::findGroupName($group);
            $userInfo['stage_name'] = &$stageName;
            $userInfo['group_name'] = &$groupName;
            $userInfo['nick_name'] = urlDecodeNickName($userInfo['nick_name']);
            cache('settlement_'.$uid.$stage.$group,$userInfo,3600*24*30);
        }
        try {
            //获取用户最后一次答题组下的正确率
            $trueRate  = LearnedHistory::getTrueRate($lastLearnedData);
            $medalData = $this->getMedal($uid, $stage);
            if ($userInfo['is_teacher'] == 0) {
                $classTrueRate = $this->percentageOfInter($lastLearnedData);
            } else {
                $classTrueRate = $this->percentageOfClass($uid, $lastLearnedData);
            }

            $punchDays = Share::getPunchDays($uid);
            $userInfo['punch_days'] = &$punchDays;
            $userInfo['true_rate'] = &$trueRate;
            $userInfo['class_true_rate'] = &$classTrueRate;
            $userInfo['medal_data'] = &$medalData;
            return json($userInfo);
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }
    /**
     * 超过全班百分比
     * 根据每个班级下，每个用户，每个组下答题正确率来计算百分比
     * @param $uid
     */
    private function percentageOfClass($uid, $lastLearnedData)
    {
        $classData = UserClass::getAllUserByUid($uid);
        //判断此阶段下此组，所有用户答对的单词
        $classTrueRate = LearnedHistory::classTrueRate($classData, $lastLearnedData);
        return $classTrueRate;
    }

    /**
     * 超过所有用户这组的百分比
     * 根据每个班级下，每个用户，每个组下答题正确率来计算百分比
     * @param $uid
     */
    private function percentageOfInter($lastLearnedData)
    {
        $classData = Db::name('learned_history')
            ->field('user_id')
            ->where('group', $lastLearnedData['group'])
            ->group('user_id')
            ->select();
        //判断此阶段下此组，所有用户答对的单词
        $classTrueRate = LearnedHistory::classTrueRate($classData, $lastLearnedData);
        return $classTrueRate;
    }

    /**
     * 判断用户是否可以好的勋章
     * @return \think\response\Json
     */
    private function getMedal($uid, $now_stage)
    {
        //判断是此用户是否学完此阶段，获得此勋章
        //找本阶段的学习数量
        $medalData = cache('medal' . $uid);

        if (!empty($medalData)) {
            return Null;
        }

        $already_number = Db::name('learned_history')
            ->where('user_id', $uid)
            ->where('stage', $now_stage)
            ->count();

        $stageData = Db::table(YX_QUESTION . 'stage')
            ->where('id', $now_stage)
            ->field('stage_name,stage_desc,word_num')
            ->find();

        if ($already_number >= $stageData['word_num']) {
            $arr = [
                'stage_name' => $stageData['stage_name'],
                'stage_desc' => $stageData['stage_desc'],
            ];
            cache('medal' . $uid, $now_stage);
            return $arr;
        }

        return NULL;
    }

    public function nextGroupInfo()
    {
        $uid      = Token::getCurrentTokenVar('uid');
        $userInfo = User::getUserInfo($uid);
        $commonID = Stage::FirstCommonStageID();
        if ($userInfo['now_stage'] == $commonID) {
            $data = isTeacher($uid);
            return json($data);
        }
        $LastGroupID = Group::userLastGroupID($userInfo);

        if (empty($LastGroupID)) {
            //去找下一阶段,第一组单词
            $nextStageID = Stage::nextStageGroupInfo($userInfo);
            if (empty($nextStageID)) {
                throw new SuccessMessage([
                    'msg'       => '你太厉害了，所有阶段都已经通关了',
                    'errorCode' => 50000
                ]);
            }
            //如果不为空，去找下一阶段的第一组id
            $nextStageFirstGroupID = Group::nextStageFirstGroupID($nextStageID);
            if (empty($nextStageFirstGroupID)) {
                throw new SuccessMessage([
                    'msg'       => '亲，暂你已经学完所有单词了，因为下一阶段，没有任何分组哦！',
                    'errorCode' => 50000
                ]);
            }
            $wordDetail = $this->getWordDetail($nextStageFirstGroupID, $nextStageID);
            return json($wordDetail);           //这个是return json  数据
        }
        $wordDetail = $this->getWordDetail($LastGroupID, $userInfo['now_stage']);
        return json($wordDetail);               //这个是return json  数据
    }

    public function getAgainInfo($id)
    {
        //根据token获取用户最后一次学习的哪一阶段，哪一组信息，重新查询一遍详情进行返回,并且清空此用户学的本阶段本组单词
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new IDMustBePositiveInt();
        $validate->goCheck();
        $groupWord = GroupWord::selectGroupWord($id);
        if (empty($groupWord)) {
            throw new MissException([
                'msg'       => '该分组下没有任何单词',
                'errorCode' => 50000
            ]);
        }
        try {
            //然后根据每个组的详情进行查询每个单词的详情
            $wordDetail = EnglishWord::getNextWordDetail($groupWord);
            //判断是否收藏过该单词
            $wordDetail = Collection::isCollection($uid, $wordDetail);
            return json($wordDetail);
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }

    private function getWordDetail($LastGroupID,$nowStageID)
    {
        $groupWord = GroupWord::selectGroupWord($LastGroupID);

        if(empty($groupWord)){
            throw new MissException([
                'msg' => '亲，此小组下没有任何单词(⊙o⊙)哦',
                'errorCode' => 50000
            ]);
        }

        foreach ($groupWord as $key=>$val){
            $groupWord[$key]['stage'] = $nowStageID;
        }

        $wordDetail = EnglishWord::getNextWordDetail($groupWord);

        if($wordDetail['count'] == 0){
            throw new MissException([
                'msg' => '亲，此小组下没有任何单词(⊙o⊙)哦',
                'errorCode' => 50000
            ]);
        }

        return $wordDetail;
    }
}