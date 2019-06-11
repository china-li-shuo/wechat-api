<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v5;

use app\api\dao\Group;
use app\api\dao\LearnedHistory;
use app\api\dao\Stage as StageModel;
use app\api\dao\User;
use app\api\service\Token;
use app\api\validate\ClassID;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\MissException;
use think\Db;

class Stage
{
    /**
     * 根据班级查询对应的阶段导航胶囊按钮
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     */
    public function getStages()
    {
        Token::getCurrentTokenVar('uid');
        $validate = new ClassID();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //查询对应的班级阶段导航胶囊按钮
        $stages = StageModel::getStageNavigationButton($data['class_id']);
        if (empty($stages)) {
            throw new MissException([
                'msg'       => '还没有任何阶段',
                'errorCode' => 50000
            ]);
        }
        foreach ($stages as $key=>$val){
            $arr = Db::table(YX_QUESTION.'stage')
                ->where('id',$val['parent_id'])
                ->field('id,stage_name')
                ->find();
            $stages[$key]['id'] = $arr['id'];
            $stages[$key]['stage_name'] = $arr['stage_name'];
        }
        return json(['code' => 200, 'msg' => '查询成功', 'data' => $stages]);
    }


    /**
     * 根据班级查询对应的阶段
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     */
    public function getAllStage()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $validate = new ClassID();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //根据班级获取此班级下所有的阶段
        $stages = StageModel::getClassStageInformation($data['class_id']);
        //判断用户某一阶段已学了多少个单词
        $stages = LearnedHistory::getAlreadyNumberByStage($uid, $stages);

        $stages = array_values($stages);
        if (empty($stages)) {
            throw new MissException([
                'msg'       => '查询失败',
                'errorCode' => 50000
            ]);
        }
        //根据前端需求，进行切割字符串
        foreach ($stages as $key => $val) {
            if (array_key_exists('son', $val)) {
                foreach ($val['son'] as $k => $v) {
                    $stage_name = mb_substr($v['stage_name'], 0, 2);
                    $stages[$key]['son'][$k]['stageName'] = $stage_name;
                }
            }
        }
        if (empty($stages)) {
            throw new MissException([
                'msg'       => '切割勋章字符串出错',
                'errorCode' => 50000
            ]);
        }
        return json(['code' => 200, 'msg' => '查询成功', 'data' => $stages]);
    }


    public function getDetail()
    {
        //获取阶段下共多少组，多少单词
        //获取用户已练习多少组，已练习多少单词
        //展示此阶段下共有哪几组单词(组名称)，每个组下有多少单词，用户每组学了多少单词
        //用户是否获取此勋章称号
        $uid      = Token::getCurrentTokenVar('uid');
        $data = input('post.');
        isset($data['stage']) ? intval($data['stage']) : 0;
        isset($data['class_id']) ? intval($data['class_id']) : 0;
        if(empty($data['stage'])|| empty($data['class_id'])){
            throw new MissException([
                'msg'=>'参数错误',
                'errorCode'=>50000
            ]);
        }
        $stageData             = StageModel::findStage($data['stage']);

        $historyGroupData      = LearnedHistory::getUserStageGroupData($uid, $data['stage']);
        $historyGroupCount     = count($historyGroupData);
        $historyWordCount      = LearnedHistory::UserCountStageGroup($uid, $data['stage']);

        $eachGroupData         = Group::getEachGroupInformation($data);
        $historyGroupWordCount = LearnedHistory::getAlreadyLearnedGroupWordCount($uid, $historyGroupData);

        //查看此阶段下，每组学习下多少个单词
        if (!empty($eachGroupData) && !empty($historyGroupWordCount)) {
            foreach ($eachGroupData as $key => $val) {
                foreach ($historyGroupWordCount as $k => $v) {
                    if ($val['id'] == $v['group']) {
                        $eachGroupData[$key]['already_group_num'] = $v['already_group_num'];
                    }
                }

                if (!array_key_exists('already_group_num', $eachGroupData[$key])) {
                    $eachGroupData[$key]['already_group_num'] = 0;
                }
            }
        }

        //如果没有学习记录 则每组学习0个单词
        if (empty($historyGroupWordCount)) {
            foreach ($eachGroupData as $key => $val) {
                $eachGroupData[$key]['already_group_num'] = 0;
            }
        }

        $data = [
            'stage_name'          => $stageData['stage_name'],
            'stage_group_num'     => $stageData['group_num'],
            'stage_word_num'      => $stageData['word_num'],
            'history_group_count' => $historyGroupCount,
            'history_word_count'  => $historyWordCount,
            'each_group_data'     => $eachGroupData
        ];

        if (!$data) {
            throw new MissException([
                'msg'       => '阶段详情信息查询失败',
                'errorCode' => 50000
            ]);
        }

        return json($data);
    }

    public static function nextStageGroupInfo($userInfo)
    {
        //先根据阶段进行排序小组
        $data = Db::table(YX_QUESTION . 'stage')
            ->order('sort')
            ->select();
        //找出当前小组
        $res = Db::table(YX_QUESTION . 'stage')
            ->where('id', $userInfo['now_stage'])
            ->find();
        //确定下一组单词的信息
        foreach ($data as $key => $val) {
            if ($res == $data[$key]) {
                $k = $key + 1;
            }
        }

        //如果下一组单词信息非空，返回组id
        if (!empty($data[$k])) {
            return $data[$k]['id'];
        }

        return false;
    }

    /**
     * 提示用户当前所学的阶段
     * @throws \app\lib\exception\ParameterException
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     */
    public function alertMsg($id)
    {
        $validate = new IDMustBePositiveInt();
        $validate->goCheck();
        $uid        = Token::getCurrentTokenVar('uid');
        $commonID   = StageModel::commonStageID();
        $commonData = StageModel::selectCommonStageData($commonID);
        $userInfo   = User::field('now_stage')->get($uid)->toArray();
        foreach ($commonData as $key => $val) {
            if ($val['id'] != $userInfo['now_stage']) {
                if ($id != $userInfo['now_stage']) {
                    $stage = Db::table(YX_QUESTION . 'stage')
                        ->field('stage_name')
                        ->where('id', $userInfo['now_stage'])
                        ->find();
                    return json(['msg' => '你正在学习的阶段是' . $stage['stage_name'], 'errorCode' => 0]);
                }
            }
        }
        return NULL;
    }

    /**
     * 记录用户的当前阶段
     */
    public function recordStage($id)
    {
        $validate = new IDMustBePositiveInt();
        $validate->goCheck();
        $uid = Token::getCurrentTokenVar('uid');
        try {
            //如果是公共部分，判断此用户公共部分是否学完
            $commonID = StageModel::commonStageID();
            if ($id == $commonID) {
                $number      = Db::name('learned_history')->where(['user_id' => $uid, 'stage' => $id])->count();
                $stageData   = Db::table(YX_QUESTION . 'stage')
                    ->field('word_num')
                    ->where('parent_id', $id)
                    ->select();
                $stageNumber = 0;
                foreach ($stageData as $key => $val) {
                    $stageNumber += $val['word_num'];
                }
                if ($stageNumber == $number) {
                    cache('record_stage' . $uid, 1);
                    return json(['msg' => 'ok', 'code' => 200]);
                } else {
                    cache('record_stage' . $uid, 2);
                    return json(['msg' => 'ok', 'code' => 200]);
                }
            } else {
                cache('record_stage' . $uid, 1);
                return json(['msg' => 'ok', 'code' => 200]);
            }
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }

    }
}