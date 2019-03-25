<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 15:51
 */

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\model\EnglishWord;
use app\api\model\ErrorBook;
use app\api\model\Group;
use app\api\model\GroupWord;
use app\api\model\Collection as CollectionModel;
use app\api\model\LearnedHistory as LearnedHistoryModel;
use app\api\model\User;
use app\api\service\Token;
use app\api\validate\Collection;
use app\api\validate\LearnedHistory;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;
use app\api\model\Stage;
use think\Db;

class Learned extends BaseController
{
    protected $beforeActionList = [
        'checkPrimaryScope' => ['only' => 'getList,collection']
    ];

    public function getList()
    {
        //先根据token获取用户的uid
        //根据uid去学习记录表中查询用户最后一次学到了第几组的第几个单词
        $uid         = Token::getCurrentTokenVar('uid');
        $LearnedData = LearnedHistoryModel::UserLearned($uid);

        //如果用户没有学习记录，直接查询第一阶段下，第一组单词
        if(empty($LearnedData)){
            $stage          = Stage::FirstStageID();
            $group          = Group::firstGroupID($stage);
            $notLearnedData = GroupWord::findFirst($group);
            if(empty($notLearnedData)){
                throw new MissException([
                    'msg'=>'本组单词为空，请联系管理员进行添加',
                    'errorCode'=>50000
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

        if (empty($notLearnedData)){
            $wordDetail = $this->nextGroupInfo($uid);
            return json($wordDetail);
        }

        //查询此组对应的阶段
        $notLearnedData = Group::correspondingStage($notLearnedData);
        //用户已学习这组下的第几个数量
        $currentNumber =  LearnedHistoryModel::userLearnedCurrentNumber($LearnedData);  //2

        //用户还没有学习单词的详情
        $notWordData = EnglishWord::notWordData($notLearnedData);

        $notWordData = CollectionModel::isCollection($uid,$notWordData);

        if(empty($notWordData)){
            throw new MissException([
                'msg' => '没有查到此分组下单词详情',
                'errorCode' => 50000
            ]);
        }

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

        //如果答题正确，判断错题本有没有此条记录，如果有则删除
        if($answerResult == 1){
            ErrorBook::deleteErrorBook($uid,$data);
        }

        if($answerResult == 0){
            ErrorBook::addErrorBook($uid,$data);
        }

        //$this->addUserLearnedData($uid,$data);
        $res = LearnedHistoryModel::addUserHistory($uid,$data,$answerResult);

        if(!$res){
            throw new MissException([
                'msg' => '用户答题记录失败',
                'errorCode' => 50000
            ]);
        }
        //判断是此用户是否学完此阶段，获得此勋章
        //找本阶段的学习数量
        $already_number = Db::table('yx_learned_history')
            ->where('user_id',$uid)
            ->where('stage',$data['stage'])
            ->count();

        $stageData = Db::table(YX_QUESTION.'stage')
            ->where('id',$data['stage'])
            ->field('stage_name,stage_desc,word_num')
            ->find();

        if($already_number>=$stageData['word_num']){
            $arr = [
                'stage_name'=>$stageData['stage_name'],
                'stage_desc'=>$stageData['stage_desc'],
                ];
            return json(['msg'=>'ok','code'=>200,'data'=>$arr]);
        }

        return json(['msg'=>'ok','code'=>200,'data'=>'']);

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
        if($data['is_collection'] == 2){
            $res = CollectionModel::deleteCollection($uid,$data);
            if(!$res){
                throw new MissException([
                    'msg' => '你已经取消收藏该单词了呀',
                    'errorCode' => 50000
                ]);
            }
            return json(['msg'=>'取消收藏成功','code'=>200]);
        }

        $res = CollectionModel::addCollection($uid,$data);

        if(!$res){
            throw new MissException([
                'msg' => '你已经收藏过该单词了呀',
                'errorCode' => 50000
            ]);
        }
        return json(['msg'=>'收藏成功','code'=>200]);
    }



    /**
     * 把用户总共学习的单词数量，最后一次学习的阶段,最后一次学习的组写入数据库
     * @param $data
     */
    private function addUserLearnedData($uid,$data)
    {
        $res = Db::table('yx_learned_history')
            ->where('user_id',$uid)
            ->where('word_id',$data['word_id'])
            ->where('group',$data['group'])
            ->where('stage',$data['stage'])
            ->find();

        if(empty($res)){

            $userinfo = Db::table('yx_user')
                ->where('id',$uid)
                ->field('already_number')
                ->find();

            $arr = [
                'already_number' => $userinfo['already_number'] + 1,
                'now_stage'      => $data['stage'],
                'now_group'      => $data['group'],
            ];

            return Db::table('yx_user')->where('id',$uid)->update($arr);
        }

        return true;
    }

    private function nextGroupInfo($uid)
    {
        $userInfo    = User::getUserInfo($uid);
        $LastGroupID = Group::userLastGroupID($userInfo);

        if(empty($LastGroupID)){
            $stageDesc = Db::table(YX_QUESTION.'stage')
                ->where('id',$userInfo['now_stage'])
                ->field('stage_desc')
                ->find();

            //去找下一阶段,第一组单词
            $nextStageID = Stage::nextStageGroupInfo($userInfo);
            if(empty($nextStageID)){
                throw new SuccessMessage([
                    'msg'=>'你太厉害了，所有阶段都已经通关了',
                    'errorCode'=>50000
                ]);
            }
            //如果不为空，去找下一阶段的第一组id
            $nextStageFirstGroupID = Group::nextStageFirstGroupID($nextStageID);
            if(empty($nextStageFirstGroupID)){
                throw new SuccessMessage([
                    'msg'=>'亲，暂你已经学完所有单词了，因为下一阶段，没有任何分组哦！',
                    'errorCode'=>50000
                ]);
            }
            $wordDetail = $this->getWordDetail($nextStageFirstGroupID,$nextStageID);
            return $wordDetail;      //这个是return array  数据
        }
        $wordDetail = $this->getWordDetail($LastGroupID,$userInfo['now_stage']);
        return $wordDetail;          //这个是return array  数据
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