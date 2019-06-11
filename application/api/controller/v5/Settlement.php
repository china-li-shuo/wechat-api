<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v5;

use app\api\dao\Collection;
use app\api\dao\EnglishWord;
use app\api\dao\Group;
use app\api\dao\GroupWord;
use app\api\dao\LearnedHistory;
use app\api\dao\Post;
use app\api\dao\Stage;
use app\api\dao\User;
use app\api\dao\UserClass;
use app\api\service\Token;
use app\api\validate\Post as PostValidate;
use app\api\validate\Settlement as SettlementValidate;
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
        $validate = new SettlementValidate();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $data['user_id'] = $uid;
        $userInfo  = User::field('is_teacher,now_stage,user_name,nick_name,avatar_url,punch_days')->get($uid)->toArray();
        $stageData = Stage::findStage($data['stage']);
        $stageName = $stageData['stage_name'];
        $groupName = Group::findGroupName($data['group']);
        $userInfo['stage_name'] = &$stageName;
        $userInfo['group_name'] = &$groupName;
        $userInfo['nick_name']  = urlDecodeNickName($userInfo['nick_name']);

        try {
            //获取用户此班级下此阶段此组的正确率
            $trueRate  = LearnedHistory::personalCorrectnessRate($data);
            //发帖子的状态
            $clockStatus = Post::findPost($uid,$data);
            if(empty($clockStatus) || $clockStatus['clock_status'] == 0){
                $clockStatus['clock_status'] = 0;
            }
            $medalData = $this->getMedal($uid, $data['stage']);
            $classTrueRate = $this->percentageOfClass($data);
            $userInfo['true_rate'] = !empty($trueRate)?$trueRate:'0%';
            $userInfo['clock_status'] = &$clockStatus['clock_status'];
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
     * 发帖
     */
    public function sendPost()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $validate = new PostValidate();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //去除左边空格，计算发帖内容长度
        $len = mb_strlen(ltrim($data['content']));
        if($len<=0 || $len>=50){
            return json(['clock_status'=>0,'errorCode'=>0,'msg'=>'输入内容为空或者已超出长度限制']);
        }
        //进行发帖，并记录发帖天数
        $res = Post::addPost($uid,$data);

       if(!$res){
           throw new MissException([
               'msg'=>'此班级此阶段此分组下你已经发过帖子了',
               'errorCode'=>50000
           ]);
       }

      throw new SuccessMessage();
    }

    /**
     * 超过全班百分比
     * 根据每个班级下，每个用户，每个组下答题正确率来计算百分比
     * @param $uid
     */
    private function percentageOfClass($data)
    {
        $classData = UserClass::getAllMembersOfClass($data['class_id']);
        //判断此阶段下此组，所有用户答对的单词
        $classTrueRate = LearnedHistory::getClassTrueRate($classData, $data);
        return $classTrueRate;
    }

    /**
     * 判断用户是否可以获得勋章
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

    /**
     * 重新来过接口
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     */
    public function getAgainInfo()
    {
        //根据token获取用户最后一次学习的哪一阶段，哪一组信息，重新查询一遍详情进行返回
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new SettlementValidate();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //进行查看着以分组下所有单词的详情
        $groupWord = GroupWord::selectGroupWord($data['group']);
        if (empty($groupWord)) {
            throw new MissException([
                'msg'       => '该分组下没有任何单词',
                'errorCode' => 50000
            ]);
        }
        try {
            //然后根据每个组的详情进行查询每个单词的详情
            $notLearnedData = Group::correspondingStage($groupWord);
            //根据类型查找不同的单词表
            $notWordData = EnglishWord::selectNotWordData($notLearnedData);
            //判断该用户单词是否收藏过
            $notWordData = Collection::isCollection($uid, $notWordData);
            //根据不同的类型把单词格式进行转换
            $notLearnedData = EnglishWord::conversionByTypeFormat($notWordData, 1);
            $notLearnedData['count'] = count($notLearnedData);
            return json($notLearnedData);
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }

    /**
     * 下一组单词
     * @param $LearnedData 当前阶段组信息
     * @return mixed
     * @throws MissException
     * @throws SuccessMessage
     */
    public function nextGroupInfo()
    {
        $uid   = Token::getCurrentTokenVar('uid');
        $validate = new SettlementValidate();
        $validate->goCheck();
        $LearnedData= $validate->getDataByRule(input('post.'));
        //获取下一组的组id,并且是符合此班级权限的下一组ID
        $nextGroupID = Group::nextGroupIDByClassPermissions($LearnedData);
        if (empty($nextGroupID)) {
            //如果当前阶段没有下一组了，去找下一阶段,第一组单词
            $nextStageID = Stage::nextStageIDByClassPermissions($LearnedData);

            if (empty($nextStageID)) {
                throw new SuccessMessage([
                    'msg'       => '你太厉害了，所有阶段都已经通关了',
                    'errorCode' => 50000
                ]);
            }
            if($nextStageID == 8){
                throw new SuccessMessage([
                    'msg'       => '牛人阶段暂未开放，请耐心等待',
                    'errorCode' => 50000
                ]);
            }
            //如果不为空，去找下一阶段的符合权限第一组id
            $LearnedData['stage'] = $nextStageID;
            $LearnedData['group'] = '';
            $nextStageFirstGroupID = Group::nextGroupIDByClassPermissions($LearnedData);

            if (empty($nextStageFirstGroupID)) {
                throw new SuccessMessage([
                    'msg'       => '你已经学完所有单词了，因为下一阶段，没有任何分组！',
                    'errorCode' => 50000
                ]);
            }
            $wordDetail = $this->getWordDetail($nextStageFirstGroupID,$uid);
            return json($wordDetail);
        }
        $wordDetail = $this->getWordDetail($nextGroupID,$uid);
        return json($wordDetail);
    }

    /**
     * 获取单词详情
     * @param $LastGroupID
     * @param $uid
     * @return mixed
     * @throws MissException
     */
    private function getWordDetail($LastGroupID, $uid)
    {

        $groupWord = GroupWord::selectGroupWord($LastGroupID);
        if (empty($groupWord)) {
            throw new MissException([
                'msg'       => '此小组下没有任何单词',
                'errorCode' => 50000
            ]);
        }

        //进行确定组的阶段和组的类型
        $groupWord = Group::correspondingStage($groupWord);
        //根据类型查找不同的单词表
        $notWordData = EnglishWord::selectNotWordData($groupWord);
        //判断该用户单词是否收藏过
        $notWordData = Collection::isCollection($uid, $notWordData);
        //根据不同的类型把单词格式进行转换
        $notLearnedData = EnglishWord::conversionByTypeFormat($notWordData, 1);
        $notLearnedData['count'] = count($notLearnedData);
        if ($notLearnedData['count'] == 0) {
            throw new MissException([
                'msg'       => '此小组下没有任何单词',
                'errorCode' => 50000
            ]);
        }

        return $notLearnedData;
    }
}