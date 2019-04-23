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
use app\api\model\LearnedHistory;
use app\api\service\Token;
use app\api\validate\StageID;
use app\lib\exception\MissException;
use app\api\validate\ErrorBook AS ErrorBookValidate;
use think\Db;
use think\Exception;
use think\facade\Request;

class Activity
{
    /**
     * 已学习首页
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
     * 错题本首页
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
     * 已学习详情
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function alreadyDetail()
    {
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new StageID();
        $validate->goCheck();
        $stage   = $validate->getDataByRule(input('post.'));
        $stageID = $stage['stage'];
        $data    = cache($uid . 'alreadyDetail' . $stageID);

        if (!empty($data)) {
            return json($data);
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->getStageGroup($uid, $stageID);
        if (empty($data)) {
            throw new MissException([
                'msg'       => '你还未学习过任何单词',
                'errorCode' => 50000
            ]);
        }
        $data['time'] = time();
        cache($uid . 'alreadyDetail' . $stageID, $data, 7200);
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
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new StageID();
        $validate->goCheck();
        $stage   = $validate->getDataByRule(input('post.'));
        $stageID = $stage['stage'];
        if (!empty($data)) {
            return json($data);
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->errorStageGroup($uid, $stageID);
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
     * 错题本详情
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function collectionDetail()
    {
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new StageID();
        $validate->goCheck();
        $stage   = $validate->getDataByRule(input('post.'));
        $stageID = $stage['stage'];
        $data    = cache($uid . 'collectionDetail' . $stageID);

        if (!empty($data)) {
            return json($data);
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->collectStageGroup($uid, $stageID);
        cache($uid . 'collectionDetail' . $stageID, $data, 7200);
        return json($data);
    }


    private function getStageGroup($uid, $stageID)
    {
        try {
            //获取阶段名称
            $stage = Db::table(YX_QUESTION . 'stage')
                ->where('id', $stageID)
                ->field('stage_name')
                ->find();

            $data = Db::table('yx_learned_history')
                ->where('user_id', $uid)
                ->where('stage', $stageID)
                ->group('group')
                ->field('id,stage,group')
                ->select();

            foreach ($data as $key => $val) {
                $data[$key]['stage_name'] = &$stage['stage_name'];
            }

            //获取每组的名称
            foreach ($data as $k => $v) {
                $group = Db::table(YX_QUESTION . 'group')
                    ->where('id', $v['group'])
                    ->field('group_name')
                    ->find();

                $lastLearnedTime = Db::table('yx_learned_history')
                    ->where('user_id', $uid)->where('stage', $stageID)
                    ->where('group', $v['group'])->order('create_time DESC')
                    ->field('group,create_time')
                    ->find();

                $data[$k]['group_name']        = &$group['group_name'];
                $data[$k]['last_learned_time'] = date('Y-m-d', $lastLearnedTime['create_time']);
            }

            //获取每组下所有的单词,进行查询每个单词的详情
            foreach ($data as $key => $val) {
                $result             = Db::table('yx_learned_history')
                    ->where('user_id', $uid)
                    ->where('stage', $stageID)
                    ->where('group', $val['group'])
                    ->field('stage,group,word_id')
                    ->select();
                $new_arr            = EnglishWord::selectWordDetail($result);
                $data[$key]['word'] = $new_arr;
            }
            return $data;
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }

    }


    private function collectStageGroup($uid, $stageID)
    {
        //获取阶段名称
        $stage = Db::table(YX_QUESTION . 'stage')
            ->where('id', $stageID)
            ->field('stage_name')
            ->find();

        $data = Db::table('yx_collection')
            ->where('user_id', $uid)
            ->where('stage', $stageID)
            ->group('group')
            ->field('id,stage,group')
            ->select();

        foreach ($data as $key => $val) {
            $data[$key]['stage_name'] = &$stage['stage_name'];
        }
        //获取每组的名称
        foreach ($data as $k => $v) {

            $group = Db::table(YX_QUESTION . 'group')
                ->where('id', $v['group'])
                ->field('group_name')
                ->find();

            $lastLearnedTime = Db::table('yx_collection')
                ->where('user_id', $uid)
                ->where('stage', $stageID)
                ->where('group', $v['group'])
                ->order('create_time DESC')
                ->field('group,create_time')->find();

            $data[$k]['group_name']        = &$group['group_name'];
            $data[$k]['last_learned_time'] = date('Y-m-d', $lastLearnedTime['create_time']);
        }

        //获取每组下所有的单词,进行查询每个单词的详情
        foreach ($data as $key => $val) {
            $result = Db::table('yx_collection')
                ->where('user_id', $uid)
                ->where('stage', $stageID)
                ->where('group', $val['group'])
                ->field('stage,group,word_id')
                ->select();

            $new_arr            = EnglishWord::selectWordDetail($result);
            $data[$key]['word'] = $new_arr;
        }

        if (!$data) {
            throw new MissException([
                'msg'       => '空空如也，请先开始进行你的表演',
                'errorCode' => 50000
            ]);
        }
        return $data;
    }


    private function errorStageGroup($uid, $stageID)
    {
        //获取阶段名称
        $stage = Db::table(YX_QUESTION . 'stage')
            ->where('id', $stageID)->field('stage_name')
            ->find();

        $data = Db::table('yx_error_book')
            ->where('user_id', $uid)
            ->where('stage', $stageID)
            ->group('group')
            ->field('id,stage,group')
            ->select();

        foreach ($data as $key => $val) {
            $data[$key]['stage_name'] = &$stage['stage_name'];
        }
        //获取每组的名称
        foreach ($data as $k => $v) {
            $group = Db::table(YX_QUESTION . 'group')
                ->where('id', $v['group'])
                ->field('group_name')
                ->find();

            $lastLearnedTime = Db::table('yx_error_book')
                ->where('user_id', $uid)
                ->where('stage', $stageID)
                ->where('group', $v['group'])
                ->order('create_time DESC')
                ->field('group,create_time')
                ->find();

            $data[$k]['group_name']        = &$group['group_name'];
            $data[$k]['last_learned_time'] = date('Y-m-d', $lastLearnedTime['create_time']);
        }

        //获取每组下所有的单词,进行查询每个单词的详情
        foreach ($data as $key => $val) {
            $result             = Db::table('yx_error_book')
                ->where('user_id', $uid)
                ->where('stage', $stageID)
                ->where('group', $val['group'])
                ->field('stage,group,word_id')
                ->select();
            $new_arr            = EnglishWord::selectWordDetail($result);
            $data[$key]['word'] = $new_arr;

        }

        if (!$data) {
            throw new MissException([
                'msg'       => '空空如也，请先开始进行你的表演',
                'errorCode' => 50000
            ]);
        }
        return $data;
    }


}