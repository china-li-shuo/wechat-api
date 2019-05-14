<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 15:51
 */

namespace app\api\controller\v3;

use app\api\controller\BaseController;
use app\api\model\Collection as CollectionModel;
use app\api\model\EnglishWord;
use app\api\model\ErrorBook;
use app\api\model\Group;
use app\api\model\GroupWord;
use app\api\model\LearnedChild;
use app\api\model\LearnedHistory as LearnedHistoryModel;
use app\api\model\Stage;
use app\api\service\Token;
use app\api\validate\Collection;
use app\api\validate\LearnedHistoryV3;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;
use think\Db;

class Learned extends BaseController
{
    public function getList()
    {
        //先根据token获取用户的uid
        //根据uid去学习记录表中查询用户最后一次学到了第几组的第几个单词
        $uid = Token::getCurrentTokenVar('uid');
        cache('record_stage' . $uid, 1);
        $LearnedData = LearnedHistoryModel::UserLearnedList($uid);
        if($LearnedData['stage'] == 8){
            throw new MissException([
                'msg'       => '牛人词汇暂时还没开放，请耐心等待',
                'errorCode' => 50000
            ]);
        }
        //如果用户没有学习记录，直接查询第一阶段下，第一组单词
        if (empty($LearnedData)) {
            //先看是否有缓存数据
            $notLearnedData = cache('userNotLearnedData');
            if(!empty($notLearnedData)){
                return json($notLearnedData);
            }
            $stage          = Stage::FirstStageID();
            $group          = Group::firstGroupID($stage);
            $notLearnedData = GroupWord::findFirst($group);
            if (empty($notLearnedData)) {
                throw new MissException([
                    'msg'       => '本组单词为空，请联系管理员进行添加',
                    'errorCode' => 50000
                ]);
            }

            $notLearnedData          = Group::correspondingStage($notLearnedData);
            $notWordData             = EnglishWord::notWordData($notLearnedData);
            $notWordData             = CollectionModel::isCollection($uid, $notWordData);
            $notLearnedData          = EnglishWord::formatConversion($notWordData, 1);
            $notLearnedData['count'] = count($notLearnedData);
            cache('userNotLearnedData',$notLearnedData,3600*24*7);
            return json($notLearnedData);
        }

        //用户最后一次学习第几组共有多少单词
        $allData = Group::getAllData($LearnedData);   //25
        //用户还未学习的组信息
        $notLearnedData = Group::getGroupData($LearnedData);  //23
        if (empty($notLearnedData)) {
            $wordDetail = $this->nextGroupInfo($allData[0]['group']);
            return json($wordDetail);
        }

        //查询此组对应的阶段
        $notLearnedData = Group::correspondingStage($notLearnedData);
        //用户已学习这组下的第几个数量
        $currentNumber = LearnedHistoryModel::userLearnedCurrentNumber($LearnedData);  //2

        //用户还没有学习单词的详情
        $notWordData = EnglishWord::notWordData($notLearnedData);

        $notWordData = CollectionModel::isCollection($uid, $notWordData);

        if (empty($notWordData)) {
            throw new MissException([
                'msg'       => '没有查到此分组下单词详情',
                'errorCode' => 50000
            ]);
        }

        $notWordData = EnglishWord::formatConversion($notWordData, $currentNumber + 1);

        $notWordData['count'] = count($allData);
        return json($notWordData);
    }

    /**
     * 公共词汇
     */
    public function commonList()
    {
        //先根据token获取用户的uid
        //根据uid去学习记录表中查询用户最后一次学到了第几组的第几个单词
        $uid         = Token::getCurrentTokenVar('uid');
        $LearnedData = LearnedHistoryModel::UserLearnedCommon($uid);
        //如果用户没有学习记录，直接查询第一阶段下，第一组单词
        if (empty($LearnedData)) {
            //公共阶段下的子阶段id
            $commonID       = Stage::FirstCommonStageID();
            $group          = Group::firstGroupID($commonID);
            $notLearnedData = GroupWord::findFirst($group);
            if (empty($notLearnedData)) {
                throw new MissException([
                    'msg'       => '本组单词为空，请联系管理员进行添加',
                    'errorCode' => 50000
                ]);
            }

            $notLearnedData          = Group::correspondingStage($notLearnedData);
            $notWordData             = EnglishWord::notWordData($notLearnedData);
            $notWordData             = CollectionModel::isCollection($uid, $notWordData);
            $notLearnedData          = EnglishWord::formatConversion($notWordData, 1);
            $notLearnedData['count'] = count($notLearnedData);
            return json($notLearnedData);
        }

        //用户最后一次学习第几组共有多少单词
        $allData = Group::getAllData($LearnedData);   //25
        //用户还未学习的组信息
        $notLearnedData = Group::getGroupData($LearnedData);  //23
        if (empty($notLearnedData)) {
            $wordDetail = $this->commonNextGroupInfo($uid,$LearnedData);
            if (empty($wordDetail)) {
                throw new MissException([
                    'msg'       => '本阶段单词已经学完了',
                    'errorCode' => 0
                ]);
            }
            return json($wordDetail);
        }
        //查询此组对应的阶段
        $notLearnedData = Group::correspondingStage($notLearnedData);
        //用户已学习这组下的第几个数量
        $currentNumber = LearnedHistoryModel::userLearnedCurrentNumber($LearnedData);  //2

        //用户还没有学习单词的详情
        $notWordData = EnglishWord::notWordData($notLearnedData);

        $notWordData = CollectionModel::isCollection($uid, $notWordData);

        if (empty($notWordData)) {
            throw new MissException([
                'msg'       => '没有查到此分组下单词详情',
                'errorCode' => 50000
            ]);
        }

        $notWordData = EnglishWord::formatConversion($notWordData, $currentNumber + 1);

        $notWordData['count'] = count($allData);
        return json($notWordData);
    }


    public function clickNext()
    {
        //传递参数（token,class_id,group,stage,word_id,useropt）
        //根据用户选项判断用户答案是否正确
        //如果用户答错，则把错误信息写入数据库
        //然后把用户答题活动记录写入数据库，如果已存在这条记录进行修改，否则添加
        $validate = new LearnedHistoryV3();
        $validate->goCheck();

        $uid = Token::getCurrentTokenVar('uid');

        $data = $validate->getDataByRule(input('post.'));

        $answerResult = EnglishWord::answerResult($data);

        //如果答题正确，判断错题本有没有此条记录，如果有则删除
        if ($answerResult == 1) {
            ErrorBook::deleteErrorBook($uid, $data);
            //进行查询用户记录表这个单词是否已经有过，没有则子表加一，否则子表已掌握记录不变
            LearnedChild::addLearnedChild($uid, $data);
        }

        if ($answerResult == 0) {
            ErrorBook::addErrorBook($uid, $data);
            //进行查询用户记录表这个单词是否有过，没有则不删，有则已掌握记录减一
            LearnedChild::deleteLearnedChild($uid, $data);
        }
        $res = LearnedHistoryModel::addUserHistory($uid, $data, $answerResult);
        if (!$res) {
            throw new MissException([
                'msg'       => '用户答题记录失败',
                'errorCode' => 50000
            ]);
        }
        throw new SuccessMessage();

    }

    /**
     * 收藏
     */
    public function collection()
    {
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new Collection();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //is_collection  1  为收藏  0 为取消收藏
        if ($data['is_collection'] == 2) {
            $res = CollectionModel::deleteCollection($uid, $data);
            if (!$res) {
                throw new MissException([
                    'msg'       => '你已经取消收藏该单词了呀',
                    'errorCode' => 50000
                ]);
            }
            return json(['msg' => '取消收藏成功', 'code' => 200]);
        }

        $res = CollectionModel::addCollection($uid, $data);

        if (!$res) {
            throw new MissException([
                'msg'       => '你已经收藏过该单词了呀',
                'errorCode' => 50000
            ]);
        }
        return json(['msg' => '收藏成功', 'code' => 200]);
    }


    private function nextGroupInfo($group)
    {
        $data =  Db::name('learned_history')->field('stage')->where('group',$group)->find();
        $userInfo['now_stage']=$data['stage'];
        $userInfo['now_group']=$group;
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
            if($nextStageID == 8){
                throw new SuccessMessage([
                    'msg'       => '牛人阶段暂未开放，请耐心等待',
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
            return $wordDetail;      //这个是return array  数据
        }
        $wordDetail = $this->getWordDetail($LastGroupID, $userInfo['now_stage']);
        return $wordDetail;          //这个是return array  数据
    }

    private function getWordDetail($LastGroupID, $nowStageID)
    {
        $groupWord = GroupWord::selectGroupWord($LastGroupID);

        if (empty($groupWord)) {
            throw new MissException([
                'msg'       => '亲，此小组下没有任何单词(⊙o⊙)哦',
                'errorCode' => 50000
            ]);
        }

        foreach ($groupWord as $key => $val) {
            $groupWord[$key]['stage'] = $nowStageID;
        }

        $wordDetail = EnglishWord::getNextWordDetail($groupWord);

        if ($wordDetail['count'] == 0) {
            throw new MissException([
                'msg'       => '亲，此小组下没有任何单词(⊙o⊙)哦',
                'errorCode' => 50000
            ]);
        }

        return $wordDetail;
    }


    private function commonNextGroupInfo($uid,$LearnedData)
    {
        $userInfo['now_stage'] = &$LearnedData['stage'];
        $userInfo['now_group'] = &$LearnedData['group'];
        $LastGroupID = Group::userLastGroupID($userInfo);
        if (empty($LastGroupID)) {
            //如果公共词汇没有了下一组了，判断用户是不是学员或者是不是会员，如果不是此阶段会员也不是学员则提示购买
            $data =  isTeacher($uid);
            echo json_encode($data);die;
        }
        $wordDetail = $this->getWordDetail($LastGroupID, $LearnedData['stage']);
        return $wordDetail;          //这个是return array  数据
    }
}
