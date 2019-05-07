<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 16:45
 */

namespace app\api\controller\v4;

use app\api\model\Collection;
use app\api\model\EnglishWord;
use app\api\model\ErrorBook;
use app\api\model\Group;
use app\api\model\LearnedHistory;
use app\api\service\Token;
use app\api\validate\ErrorBook as ErrorBookValidate;
use app\lib\exception\MissException;
use think\Db;

class Activity
{
    /**
     * 已学习首页(筛选)
     * @return \think\response\Json
     * @throws MissException
     */
    public function alreadyStudied()
    {
        //根据token获取用户学习所有阶段,每个阶段下所有组
        $uid         = Token::getCurrentTokenVar('uid');
        $historyData = LearnedHistory::learnedInfo($uid);

        if (empty($historyData)) {
            throw new MissException([
                'msg'       => '你还有任何学习记录呢,请开始你的表演',
                'errorCode' => 50000
            ]);
        }
        return json($historyData);
    }

    /**
     * 错题本首页（筛选）
     * @return \think\response\Json
     * @throws MissException
     */
    public function errorBook()
    {
        $uid  = Token::getCurrentTokenVar('uid');
        $data = ErrorBook::errorInfo($uid);
        if (empty($data)) {
            throw new MissException([
                'msg'       => '学霸，还没有答错任何题呢',
                'errorCode' => 50000
            ]);
        }

        return json($data);
    }

    /**
     * 已收藏 首页
     */
    public function collection()
    {
        $uid  = Token::getCurrentTokenVar('uid');
        $data = Collection::collectionInfo($uid);
        if (empty($data)) {
            throw new MissException([
                'msg'       => '空空如也，请先收藏单词(⊙o⊙)哦',
                'errorCode' => 50000
            ]);
        }

        return json($data);
    }
    /**
     * 已学习详情,根据条件进行筛选指定阶段和组的单词详情
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function alreadyDetail()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $arr = input('post.');
        if (empty($arr['stage']) || empty($arr['group'])) {
            $arr = Db::table('yx_learned_history')
                ->where('user_id',$uid)
                ->order('create_time desc')
                ->field('stage,group')
                ->find();
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->getStageGroup($uid, $arr);
        if (empty($data)) {
            throw new MissException([
                'msg'       => '你还未学习过任何单词',
                'errorCode' => 50000
            ]);
        }
        return json($data);
    }

    /**
     * 错题本详情
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function errorDetail()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $arr = input('post.');
        if (empty($arr['stage']) || empty($arr['group'])) {
          $arr = Db::table('yx_error_book')
              ->where('user_id',$uid)
              ->order('create_time desc')
              ->field('stage,group')
              ->find();
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->errorStageGroup($uid, $arr);
        return json($data);
    }

    /**
     * 错题本 移除
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function errorRemove()
    {
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new ErrorBookValidate();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $res  = ErrorBook::deleteErrorBook($uid, $data);

        if (!$res) {
            throw new MissException([
                'msg'       => '错题已经移除,请刷新重试',
                'errorCode' => 50000
            ]);
        }

        return json(['msg' => '移除成功', 'code' => 200]);
    }

    /**
     * 收藏详情
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function collectionDetail()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $arr = input('post.');
        if (empty($arr['stage']) || empty($arr['group'])) {
            $arr = Db::table('yx_collection')
                ->where('user_id',$uid)
                ->order('create_time desc')
                ->field('stage,group')
                ->find();
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->collectStageGroup($uid, $arr);
        return json($data);
    }


    /**
     * 筛选此用户已学习信息
     * @param $uid
     * @param $arr
     * @return mixed
     * @throws MissException
     */
    private function getStageGroup($uid, $arr)
    {
        try {
            $data  = Db::table('yx_learned_history')
                ->where('user_id', $uid)
                ->where('stage', $arr['stage'])
                ->where('group', $arr['group'])
                ->field('id,stage,group,word_id as wid,create_time')
                ->order('create_time desc')
                ->select();
            $groupData = Db::table(YX_QUESTION . 'group')
                ->alias('g')
                ->join('yx_question.yx_stage s','g.stage_id = s.id')
                ->field('g.group_name,s.stage_name')
                ->where('g.id', $arr['group'])
                ->find();
            $new_arr['data'] = [
                'stage_name'  => $groupData['stage_name'],
                'group_name'  => $groupData['group_name'],
                'create_time' => date('Y-m-d', $data[0]['create_time'])
            ];
            //进行确定组的阶段和组的类型
            $data = Group::correspondingStage($data);
            $notWordData = EnglishWord::selectNotWordData($data);
            //根据不同的类型把单词格式进行转换
            $new_arr['word'] = EnglishWord::conversionByTypeFormat($notWordData, 1);
            return $new_arr;
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }

    }


    /**
     * 筛选此用户收藏信息
     * @param $uid
     * @param $arr
     * @return mixed
     * @throws MissException
     */
    private function collectStageGroup($uid, $arr)
    {
        try {
            $data = Db::table('yx_collection')
                ->where('user_id', $uid)
                ->where('stage', $arr['stage'])
                ->where('group', $arr['group'])
                ->field('id,stage,group,word_id as wid,create_time')
                ->order('create_time desc')
                ->select();
            $groupData = Db::table(YX_QUESTION . 'group')
                ->alias('g')
                ->join('yx_question.yx_stage s','g.stage_id = s.id')
                ->field('g.group_name,s.stage_name')
                ->where('g.id', $arr['group'])
                ->find();
            $new_arr['data'] = [
                'stage_name'  => $groupData['stage_name'],
                'group_name'  => $groupData['group_name'],
                'create_time' => date('Y-m-d', $data[0]['create_time'])
            ];
            //进行确定组的阶段和组的类型
            $data = Group::correspondingStage($data);
            $notWordData = EnglishWord::selectNotWordData($data);
            //根据不同的类型把单词格式进行转换
            $new_arr['word'] = EnglishWord::conversionByTypeFormat($notWordData, 1);
            return $new_arr;
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }


    /**
     * 错题本详情
     * @param $uid
     * @param $arr
     * @return mixed
     * @throws MissException
     */
    private function errorStageGroup($uid, $arr)
    {
        try {
            $data = Db::table('yx_error_book')
                ->where('user_id', $uid)
                ->where('stage', $arr['stage'])
                ->where('group', $arr['group'])
                ->field('id,stage,group,word_id as wid,create_time')
                ->order('create_time desc')
                ->select();
            $groupData = Db::table(YX_QUESTION . 'group')
                ->alias('g')
                ->join('yx_question.yx_stage s','g.stage_id = s.id')
                ->field('g.group_name,s.stage_name')
                ->where('g.id', $arr['group'])
                ->find();
            $new_arr['data'] = [
                'stage_name'  => $groupData['stage_name'],
                'group_name'  => $groupData['group_name'],
                'create_time' => date('Y-m-d', $data[0]['create_time'])
            ];
            //进行确定组的阶段和组的类型
            $data = Group::correspondingStage($data);
            $notWordData = EnglishWord::selectNotWordData($data);
            //根据不同的类型把单词格式进行转换
            $new_arr['word'] = EnglishWord::conversionByTypeFormat($notWordData, 1);
            return $new_arr;
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }


}