<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 16:45
 */

namespace app\api\controller\v1;
use app\api\model\EnglishWord;
use app\api\model\ErrorBook;
use app\api\model\LearnedHistory;
use app\api\service\Token;
use app\api\validate\StageID;
use app\lib\exception\MissException;
use think\Db;

class Activity
{
    public function alreadyStudied()
    {
        //根据token获取用户学习所有阶段,每个阶段下所有组
        $uid = Token::getCurrentTokenVar('uid');
        $historyData = LearnedHistory::LearnedStage($uid);
        $historyData = LearnedHistory::LearnedGroup($uid,$historyData);

        if (empty($historyData)){
            throw new MissException([
                'msg' => '用户已学习信息查询失败',
                'errorCode' => 50000
            ]);
        }
        return json($historyData);
    }

    public function errorBook()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $data = ErrorBook::errorInfo($uid);
        if(empty($data)){
            throw new MissException([
                'msg' => '用户错题本信息查询失败',
                'errorCode' => 50000
            ]);
        }

        return json($data);
    }

    public function alreadyDetail()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $validate = new StageID();
        $validate->goCheck();
        $stage = $validate->getDataByRule(input('post.'));
        $stageID = $stage['stage'];

        //根据用户id和阶段id查出此用户所有的
        $data = $this->getStageGroup($uid,$stageID);

        return json($data);
    }

    public function errorDetail()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $validate = new StageID();
        $validate->goCheck();
        $stage = $validate->getDataByRule(input('post.'));
        $stageID = $stage['stage'];

        //根据用户id和阶段id查出此用户所有的
        $data = $this->errorStageGroup($uid,$stageID);

        return json($data);
    }

    private function getStageGroup($uid,$stageID)
    {
        $prefix = config('secure.prefix');
        //获取阶段名称
        $stage = Db::table($prefix.'stage')->where('id',$stageID)->field('stage_name')->find();
        $data = Db::table('yx_learned_history')->where('user_id',$uid)->where('stage',$stageID)->group('group')->field('id,stage,group')->select();
        foreach ($data as $key=>$val){
            $data[$key]['stage_name'] = &$stage['stage_name'];
        }
        //获取每组的名称
        foreach ($data as $k=>$v){
            $group = Db::table($prefix.'group')->where('id',$v['group'])->field('group_name')->find();
            $lastLearnedTime = Db::table('yx_learned_history')->where('user_id',$uid)->where('stage',$stageID)->where('group',$v['group'])->order('create_time DESC')->field('group,create_time')->find();
            $data[$k]['group_name'] = &$group['group_name'];
            $data[$k]['last_learned_time'] = date('Y-m-d',$lastLearnedTime['create_time']);
        }

        //获取每组下所有的单词,进行查询每个单词的详情
        foreach ($data as $key=>$val){
            $result = Db::table('yx_learned_history')->where('user_id',$uid)->where('stage',$stageID)->where('group',$val['group'])->field('stage,group,word_id')->select();
            $new_arr = EnglishWord::selectWordDetail($result);
            $data[$key]['word'] = $new_arr;

        }

        if (!$data){
            throw new MissException([
                'msg' => '用户已学习信息查询失败',
                'errorCode' => 50000
            ]);
        }
        return $data;
    }


    private function errorStageGroup($uid,$stageID)
    {
        $prefix = config('secure.prefix');
        //获取阶段名称
        $stage = Db::table($prefix.'stage')->where('id',$stageID)->field('stage_name')->find();
        $data = Db::table('yx_error_book')->where('user_id',$uid)->where('stage',$stageID)->group('group')->field('id,stage,group')->select();
        foreach ($data as $key=>$val){
            $data[$key]['stage_name'] = &$stage['stage_name'];
        }
        //获取每组的名称
        foreach ($data as $k=>$v){
            $group = Db::table($prefix.'group')->where('id',$v['group'])->field('group_name')->find();
            $lastLearnedTime = Db::table('yx_error_book')->where('user_id',$uid)->where('stage',$stageID)->where('group',$v['group'])->order('create_time DESC')->field('group,create_time')->find();
            $data[$k]['group_name'] = &$group['group_name'];
            $data[$k]['last_learned_time'] = date('Y-m-d',$lastLearnedTime['create_time']);
        }

        //获取每组下所有的单词,进行查询每个单词的详情
        foreach ($data as $key=>$val){
            $result = Db::table('yx_error_book')->where('user_id',$uid)->where('stage',$stageID)->where('group',$val['group'])->field('stage,group,word_id')->select();
            $new_arr = EnglishWord::selectWordDetail($result);
            $data[$key]['word'] = $new_arr;

        }

        if (!$data){
            throw new MissException([
                'msg' => '用户已学习信息查询失败',
                'errorCode' => 50000
            ]);
        }
        return $data;
    }


}