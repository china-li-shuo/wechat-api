<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/4
 * Time: 15:51
 */

namespace app\api\controller\v4;

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
use app\api\validate\ClassID;
use app\api\validate\Collection;
use app\api\validate\LearnedHistory;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;

class Learned extends BaseController
{
    /**
     * 开始学习
     * @return \think\response\Json
     * @throws MissException
     * @throws SuccessMessage
     * @throws \app\lib\exception\ParameterException
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     */
    public function getList()
    {
        //先根据token获取用户的uid
        //根据uid去学习记录表中查询用户最后一次学到了第几组的第几个单词
        $uid = Token::getCurrentTokenVar('uid');
        $validate = new ClassID();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        cache('record_stage' . $uid, 1);
        //查询用户最后一次学习的单词记录
        $LearnedData = LearnedHistoryModel::UserLastLearnedData($uid,$data['class_id']);
        //如果用户没有学习记录，直接查询第一阶段下，第一组单词
        if (empty($LearnedData)) {
            //查询符合班级权限的第一个阶段ID
            $stageID = Stage::firstStageIDByClassPermissions($data['class_id']);
            if(empty($stageID)){
                throw new MissException([
                    'msg'=>'此班级下没有查到任何阶段有权限',
                    'errorCode'=>50000
                ]);
            }
            //查询符合班级权限的组ID
            $arr['group'] = '';
            $arr['class_id'] = $data['class_id'];
            $arr['stage'] =$stageID;
            $groupID = Group::nextGroupIDByClassPermissions($arr);
            //进行查看着以分组下所有单词的详情
            $notLearnedData = GroupWord::selectGroupWord($groupID);
            if (empty($notLearnedData)) {
                throw new MissException([
                    'msg'       => '本组单词为空，请联系管理员进行添加',
                    'errorCode' => 50000
                ]);
            }
            //进行确定组的阶段和组的类型
            $notLearnedData = Group::correspondingStage($notLearnedData);
            //根据类型查找不同的单词表
            $notWordData = EnglishWord::selectNotWordData($notLearnedData);
            //判断该用户单词是否收藏过
            $notWordData = CollectionModel::isCollection($uid, $notWordData);
            //根据不同的类型把单词格式进行转换
            $notLearnedData = EnglishWord::conversionByTypeFormat($notWordData, 1);
            $notLearnedData['count'] = count($notLearnedData);
            return json($notLearnedData);
        }

        //用户最后一次学习第几组共有多少单词
        $allData = Group::getAllData($LearnedData);   //25
        //用户还未学习的组信息
        $notLearnedData = Group::getGroupData($LearnedData);  //23
        //如果当前组已经学完，则进行下一组单词
        if (empty($notLearnedData)) {
            //进行查找下一组单词，传递当前组参数信息
            $wordDetail = $this->nextGroupInfo($LearnedData,$uid);
            return json($wordDetail);
        }
        //查询此组对应的阶段和当前组的类型
        $notLearnedData = Group::correspondingStage($notLearnedData);

        //用户已学习这组下的第几个数量
        $currentNumber = LearnedHistoryModel::userLearnedCurrentNumber($LearnedData);  //2

        //用户还没有学习单词的详情
        $notWordData = EnglishWord::selectNotWordData($notLearnedData);

        $notWordData = CollectionModel::isCollection($uid, $notWordData);

        if (empty($notWordData)) {
            throw new MissException([
                'msg'       => '没有查到此分组下单词详情',
                'errorCode' => 50000
            ]);
        }

        $notWordData = EnglishWord::conversionByTypeFormat($notWordData, $currentNumber + 1);
        $notWordData['count'] = count($allData);
        return json($notWordData);
    }

    /**
     * 点击下一个，记录用户学习记录
     * @throws MissException   错误信息
     * @throws SuccessMessage   成功信息
     * @throws \app\lib\exception\ParameterException
     * @throws \app\lib\exception\TokenException    token过期异常
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function clickNext()
    {
        //传递参数（token,class_id,group,stage,word_id,useropt）
        //根据用户选项判断用户答案是否正确
        //如果用户答错，则把错误信息写入数据库
        //然后把用户答题活动记录写入数据库，如果已存在这条记录进行修改，否则添加
        $uid = Token::getCurrentTokenVar('uid');
        $validate = new LearnedHistory();
        $validate->goCheck();
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

        //进行记录用户学习记录
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
        //is_collection  1  为收藏  2为未收藏
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


    /**
     * 下一组单词
     * @param $LearnedData 当前阶段组信息
     * @return mixed
     * @throws MissException
     * @throws SuccessMessage
     */
    private function nextGroupInfo($LearnedData,$uid)
    {
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
                    'msg'       => '亲，暂你已经学完所有单词了，因为下一阶段，没有任何分组哦！',
                    'errorCode' => 50000
                ]);
            }
            $wordDetail = $this->getWordDetail($nextStageFirstGroupID,$uid);
            return $wordDetail;      //这个是return array  数据
        }
        $wordDetail = $this->getWordDetail($nextGroupID,$uid);
        return $wordDetail;          //这个是return array  数据
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
                'msg'       => '亲，此小组下没有任何单词(⊙o⊙)哦',
                'errorCode' => 50000
            ]);
        }

        //进行确定组的阶段和组的类型
        $groupWord = Group::correspondingStage($groupWord);
        //根据类型查找不同的单词表
        $notWordData = EnglishWord::selectNotWordData($groupWord);
        //判断该用户单词是否收藏过
        $notWordData = CollectionModel::isCollection($uid, $notWordData);
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
