<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/2
 * Time: 10:35
 */

namespace app\api\controller\v1;

use app\api\model\Group;
use app\api\model\LearnedHistory;
use app\api\model\Stage AS StageModel;
use app\api\service\Token;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\MissException;
use think\Db;

class Stage
{
    public function getStages()
    {
        Token::getCurrentTokenVar('uid');
        $stages = StageModel::getStages();
        if(empty($stages)){
            return json([
                'msg' => '还没有任何阶段',
                'errorCode' => 50000,
                'request_url' => errorUrl()
            ]);
        }
        return json(['code'=>200,'msg'=>'查询成功','data'=>$stages]);
    }


    public function getAllStage()
    {
        $uid = Token::getCurrentTokenVar('uid');

        $stages = StageModel::getAllStage();
        //判断用户某一阶段已学了多少个单词
        $stages = LearnedHistory::getWordNumberByStage($uid,$stages);
        $stages =createTreeBySon($stages);
        if(empty($stages)){
            throw new MissException([
                'msg' => '查询失败',
                'errorCode' => 50000
            ]);
        }
        //根据前端需求，进行切割字符串
        foreach ($stages as $key=>$val){
            if(array_key_exists('son',$val)){
               foreach ($val['son'] as $k=>$v){
                  //$end = mb_strpos($v['stage_name'],'词');
                  $stage_name = mb_substr($v['stage_name'],0,2);
                  $stages[$key]['son'][$k]['stageName'] = $stage_name;
               }
            }
        }
        if(empty($stages)){
            throw new MissException([
                'msg' => '切割勋章字符串出错',
                'errorCode' => 50000
            ]);
        }
        return json(['code'=>200,'msg'=>'查询成功','data'=>$stages]);
    }

    public function getDetail($id)
    {
        //获取阶段下共多少组，多少单词
        //获取用户已练习多少组，已练习多少单词
        //展示此阶段下共有哪几组单词(组名称)，每个组下有多少单词，用户每组学了多少单词
        //用户是否获取此勋章称号
        $uid = Token::getCurrentTokenVar('uid');
        $validate = new IDMustBePositiveInt();
        $validate->goCheck();
        $stageData = StageModel::findStage($id);
        $historyGroupData = LearnedHistory::getUserGroupData($uid);
        $historyGroupCount = count($historyGroupData);
        $historyWordCount = LearnedHistory::UserCountGroup($uid);
        $eachGroupData = Group::getEachStageGroupData($id);
        $historyGroupWordCount = LearnedHistory::getAlreadyLearnedGroupWordCount($uid,$historyGroupData);

        //查看此阶段下，每组学习下多少个单词
        if (!empty($eachGroupData) && !empty($historyGroupWordCount)){
            foreach ($eachGroupData as $key=>$val){
                foreach ($historyGroupWordCount as $k=>$v){
                        if($val['id'] == $v['group']){
                            $eachGroupData[$key]['already_group_num'] = $v['already_group_num'];
                        }
                }

                if(!array_key_exists('already_group_num',$eachGroupData[$key])){
                    $eachGroupData[$key]['already_group_num'] = 0;
                }
            }
        }

        //如果没有学习记录 则每组学习0个单词
        if(empty($historyGroupWordCount)){
            foreach ($eachGroupData as $key=>$val){
                $eachGroupData[$key]['already_group_num'] = 0;
            }
        }

        $data = [
            'stage_name'=>$stageData['stage_name'],
            'stage_group_num'=>$stageData['group_num'],
            'stage_word_num'=>$stageData['word_num'],
            'history_group_count'=>$historyGroupCount,
            'history_word_count'=>$historyWordCount,
            'each_group_data'=>$eachGroupData
        ];

        if(!$data){
            return json([
                'msg' => '阶段详情信息查询失败',
                'errorCode' => 50000,
                'request_url' => errorUrl()
            ]);
        }

        return json($data);
    }

    public static function nextStageGroupInfo($userInfo)
    {
        //先根据阶段进行排序小组
        $data = Db::table(YX_QUESTION.'stage')->order('sort')->select();
        //找出当前小组
        $res = Db::table(YX_QUESTION.'stage')->where('id',$userInfo['now_stage'])->find();
        //确定下一组单词的信息
        foreach ($data as $key=>$val){
            if($res == $data[$key]){
                $k = $key+1;
            }
        }

        //如果下一组单词信息非空，返回组id
        if(!empty($data[$k])){
            return $data[$k]['id'];
        }

        return false;
    }

}