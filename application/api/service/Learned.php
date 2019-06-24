<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/18
 * Time: 10:09
 */


namespace app\api\service;


use app\api\model\ClassPermission;
use app\api\model\Collection;
use app\api\model\English;
use app\api\model\English2;
use app\api\model\Group;
use app\api\model\GroupWord;
use app\api\model\LearnedHistory;
use app\api\model\Sentences;
use app\api\model\Stage as StageModel;
use app\api\model\Synonym;
use app\lib\exception\MissException;

class Learned
{
    /**
     * 用户第一次点击开始学习返回数据
     * 直接查询符合选择切换的父阶段下，合班级权限的第一个子阶段ID，第一组单词
     * @throws \app\lib\exception\MissException
     */
    public function first($userInfo)
    {
        //获取与班级相关联
        $stages = StageModel::getCpStage($userInfo['class_id'], $userInfo['p_stage_id']);
        //进行处理，获取第一个阶段ID
        $stageID = $this->firstStageID($stages->toArray());
        //进行处理，获取第一个阶段组ID
        $groupID = $this->firstGroupID($stageID, $userInfo['class_id']);
        //进行查看着以分组下所有单词的详情
        $notLearnedData = GroupWord::where('group',$groupID)
            ->select()
            ->toArray();

        if (empty($notLearnedData)) {
            throw new MissException([
                'msg'       => '本组单词为空，请联系管理员进行添加',
                'errorCode' => 50000
            ]);
        }
        //查询此组对应的阶段和当前组的类型
        $notLearnedData = $this->correspondingStage($notLearnedData);
        $notWordData = $this->detail($notLearnedData);
        $notWordData = Collection::isCollection($userInfo['id'], $notWordData);
        $notWordData = $this->handleData($notWordData, 1);
        $notWordData['count'] = count($notLearnedData);
        return $notWordData;
    }

    /**
     * 继续上一次学习记录进行学习
     */
    public function continueStudy($userInfo)
    {
        //当前组匹配的所有单词
        $groupWord = GroupWord::where('group',$userInfo['now_group'])
            ->select()
            ->toArray();
        $sumNumber = count($groupWord);  //50  这一组单词的总数量
        //用户这组已经学习的单词信息
        $learnedData = LearnedHistory::where(['user_id'=>$userInfo['id'],'group'=>$userInfo['now_group']])
            ->field('group,word_id')
            ->select()
            ->toArray();
        $currentNumber = count($learnedData); //17  这一组单词的已经学过的数量

        //用户这组剩余的没有学习的单词信息
        foreach ($groupWord as $key=>$val) {
            foreach ($learnedData as $v) {
                if ($val['wid'] == $v['word_id']) {
                    unset($groupWord[$key]);
                }
            }
        }
        //这组未学单词信息
        $notLearnedData = array_values($groupWord);

        //如果当前组已经学完，则进行下一组单词
        if (empty($notLearnedData)) {
            //进行查找下一组单词，传递当前组参数信息
            $notWordData = $this->nextGroupInfo($userInfo);
            //缓存下一组单词数据
            return $notWordData;
        }
        //查询此组对应的阶段和当前组的类型
        $notLearnedData = $this->correspondingStage($notLearnedData);
        $notWordData = $this->detail($notLearnedData);
        $notWordData = Collection::isCollection($userInfo['id'], $notWordData);
        $notWordData = $this->handleData($notWordData, $currentNumber+1);
        $notWordData['count'] = $sumNumber;
        return $notWordData;
    }

    /**
     * 下一组单词的信息
     * @throws MissException
     */
    private function nextGroupInfo($userInfo)
    {
        //获取下一组的组id,并且是符合此班级权限的下一组ID
        $nextGroupID = $this->nextGroupID($userInfo);
        if (empty($nextGroupID)) {
            //如果当前阶段没有下一组了，去找下一阶段,第一组单词
            $nextStageID = $this->nextStageID($userInfo);
            if (empty($nextStageID)) {
                throw new MissException([
                    'msg'       => '此模块下已经没有任何阶段了',
                    'errorCode' => 50000
                ]);
            }
            //如果不为空，去找下一阶段的符合权限第一组id

            $nextGroupID = $this->firstGroupID($nextStageID,$userInfo['class_id']);

            if (empty($nextGroupID)) {
                throw new MissException([
                    'msg'       => '此阶段下没有任何小组',
                    'errorCode' => 50000
                ]);
            }
            $userInfo['now_group'] = $nextGroupID;
            $wordDetail = $this->getWordDetail($userInfo);
            return $wordDetail;      //这个是return array  数据
        }
        $userInfo['now_group'] = $nextGroupID;
        $wordDetail = $this->getWordDetail($userInfo);
        return $wordDetail;          //这个是return array  数据
    }

    /**
     * 判断用户答题结果
     */
    public function answerResult($data)
    {
        //先进性查询分组的类型1、普通类型；2、同义词；3、一次多义；4、熟词僻义
        $groupData = Group::field('type')
            ->get($data['group']);
        if($groupData->type == 1){
            $answer = English::field('answer')
                ->get($data['word_id'])
                ->toArray();
            return $this->checkAnswer($data['useropt'], $answer);
        }

        $answer = English2::field('answer')
            ->get($data['word_id'])
            ->toArray();
        $answer = explode(',', $answer['answer']);
        return $this->checkAnswer($data['useropt'], $answer);
    }

    /**
     * 校验答案正确性
     * @param $arr1     选项数组
     * @param $arr2     答案数组
     */
    private  function checkAnswer($arr1, $arr2)
    {
        $sum1 = 0;
        $sum2 = 0;
        foreach ($arr1 as $key => $val) {
            $sum1 = $sum1 + $val;
        }
        foreach ($arr2 as $key => $val) {
            $sum2 = $sum2 + $val;
        }
        if ($sum1 == $sum2) {
            return 1;
        }
        return 0;
    }

    /**
     * 根据类型查找对应的单词库，1、普通类型；2、同义词；3、一次多义；4、熟词僻义；5、长难句
     * 未学习单词的详情
     * @param $type
     * @param $notLearnedData
     * @return mixed
     */
    public function detail($notLearnedData)
    {
        switch ($notLearnedData[0]['type']) {
            case 1:
                foreach ($notLearnedData as $key => &$val) {
                    $data = English::field('id,english_word,chinese_word,options,answer,sentence,us_audio,us_phonetic')
                        ->get($val['wid']);
                    $val['is_collection'] = 2;
                    if(!empty($data)){
                        $val['son'] = $data->toArray();
                    }else{
                        $val['son'] = [];
                    }
                }
                return $notLearnedData;
            case 5: //长难句
                foreach ($notLearnedData as $key => &$val) {
                    $data = Sentences::field('id,long_sentence,word_parsing,sentence_splitting,parsing,translation,analysis')
                        ->get($val['wid']);
                    $val['is_collection'] = 2;
                    if(!empty($data)){
                        $val['son'] = $data->toArray();
                    }else{
                        $val['son'] = [];
                    }
                }
                return $notLearnedData;
            default:
                foreach ($notLearnedData as $key => &$val) {
                    $data = English2::field('id,english_word,select_title,options,answer,sentence,us_audio,us_phonetic')
                        ->get($val['wid']);
                    $val['is_collection'] = 2;
                    if(!empty($data)){
                        $val['son'] = $data->toArray();
                    }else{
                        $val['son'] = [];
                    }
                }
                return $notLearnedData;
        }
    }

    /**
     * 进行数据的处理
     */
    public function handleData($notWordData, $currentNumber)
    {
        //单词表已的音频路径
        $us_audio = config('setting.audio_prefix');
        //根据类型进行不同的格式转换，1、普通类型；2、同义词；3、一次多义；4、熟词僻义
        switch ($notWordData[0]['type']) {
            case 1://普通类型
                foreach ($notWordData as $key => &$val) {
                    $val['son']['chinese_word']  = explode('@', $val['son']['chinese_word']);
                    $val['son']['answer']        = explode(',', $val['son']['answer']);
                    $val['son']['options']       = json_decode($val['son']['options'], true);
                    $val['son']['sentence']      = json_decode($val['son']['sentence'], true);
                    $val['son']['currentNumber'] = $currentNumber + $key;
                    $val['son']['us_audio']      = $us_audio . $val['son']['us_audio'];
                }
                return $notWordData;
            case 2://同义词，则需查找关联表
                foreach ($notWordData as $key => &$val) {
                    unset($val['son']['sentence']);
                    unset($notWordData[$key]['son']['us_audio']);
                    unset($val['son']['us_phonetic']);
                    $val['son']['answer']  = explode('@', $val['son']['answer']);
                    $val['son']['options'] = json_decode($val['son']['options'], true);
                    $val['son']['currentNumber'] = $currentNumber + $key;
                    $val['son']['detail']  = Synonym::getInfo( $val['son']['id'])->toArray();
                    foreach ($notWordData[$key]['son']['detail'] as $k => &$v) {
                        $v['son']['detail'][$k]['chinese_word'] = explode('@', $v['chinese_word']);
                        $v['son']['detail'][$k]['sentence']     = json_decode($v['sentence'], true);
                        $v['son']['detail'][$k]['us_audio']     = $us_audio . $v['us_audio'];
                    }
                    continue;
                }
                return $notWordData;
            case 5:
                foreach ($notWordData as $key => &$val) {
                    $val['son']['word_parsing']  = json_decode($val['son']['word_parsing'], true);
                    $val['son']['sentence_splitting'] = json_decode($val['son']['sentence_splitting'], true);
                    $val['son']['currentNumber'] = $currentNumber + $key;
                    foreach ($val['son']['word_parsing'] as $k=>&$v){
                        $v['us_audio']      = $us_audio . $v['us_audio'];
                    }

                }
                return $notWordData;
            default://type3一词多义，type4熟词僻义
                foreach ($notWordData as $key => &$val) {
                    $val['son']['answer']        = explode(',',$val['son']['answer']);
                    $val['son']['options']       = json_decode($val['son']['options'], true);
                    $val['son']['sentence']      = json_decode($val['son']['sentence'], true);
                    $val['son']['currentNumber'] = $currentNumber + $key;
                    $val['son']['us_audio']      = $us_audio . $val['son']['us_audio'];
                }
                return $notWordData;
        }
    }

    /**
     * 进行处理返回具有班级权限的子阶段id
     * @throws MissException
     */
    private function firstStageID($stages)
    {
        if(empty($stages)){
            throw new MissException([
                'msg'=>'此班级下没有查到任何阶段有权限',
                'errorCode'=>50000
            ]);
        }
        foreach ($stages as $key=>$val){
            if(empty($val['cp']))
            {
                unset($stages[$key]);
            }
        }
        $stages = array_values($stages);
        return $stages[0]['id'];
    }

    /**
     * 获取符合权限的第一组ID
     */
    public function firstGroupID($stageID, $class_id)
    {
        //找出此阶段下所有的组，进行排序
        $group = Group::where(  'stage_id','=',$stageID)
            ->field('id,group_name,sort')
            ->order('sort')
            ->select();
        $data = $group->toArray();
        //找出此班级下有权限的组
        //判断是否在对应的班级权限里
        $permission = ClassPermission::where(
            ['class_id'=>$class_id, 'stage'=>$stageID
            ])->field('groups')
            ->find();
        $groups = explode(',',$permission->groups);

        //找出符合此班级权限下的所有分组
        if(!empty($data) && !empty($groups)){
            $arr = [];
            foreach ($data as $key=>$val){
                foreach ($groups as $k=>$v){
                    if ($val['id'] == $v){
                        array_push($arr,$data[$key]);
                    }
                }
            }
        }
        return $arr[0]['id'];
    }


    /**
     * 获取符合班级权限的下一组阶段ID
     */
    public static function nextStageID($userInfo)
    {
        //找出此所有阶段，进行排序
        $data = StageModel::where('parent_id','<>',0)
            ->field('id,stage_name,sort')
            ->order('sort')
            ->select()
            ->toArray();

        //找出此班级下有权限的阶段
        //判断是否在对应的班级权限里

        $permission = StageModel::getCpStage($userInfo['class_id'], $userInfo['p_stage_id']);
        $stages = $permission->toArray();
        foreach ($stages as $key=>&$val){
            if(empty($val['cp']))
            {
                unset($stages[$key]);
            }
            unset($val['cp']);
        }
        //最终符合权限的数据
        $permissionData = array_values($stages);

        //找出符合此班级权限下的所有分组
        if(!empty($data) && !empty($permissionData)){
            $arr = [];
            foreach ($data as $key=>&$val){
                foreach ($permissionData as $k=>$v){
                    if ($val['id'] == $v['id']){
                        array_push($arr,$val);
                    }
                }
            }
        }

        //确定下一组单词的ID
        foreach ($arr as $key => $val) {
            if ($userInfo['now_stage'] == $val['id']) {
                $k = $key + 1;
            }
        }

        //如果下一组单词信息非空，返回组id
        if (!empty($arr[$k])) {
            return $arr[$k]['id'];
        }

        return false;
    }

    /**
     * 获取符合班级权限的下一组ID
     * @param $LearnedData 当前组信息
     * @return mixed
     */
    public static function nextGroupID($userInfo)
    {
        //找出此阶段下所有的组，进行排序
        $group = Group::where(  'stage_id','=',$userInfo['now_stage'])
            ->field('id,group_name,sort')
            ->order('sort')
            ->select();
        $data = $group->toArray();
        //找出此班级下有权限的组
        //判断是否在对应的班级权限里
        $permission = ClassPermission::where(
            ['class_id'=>$userInfo['class_id'], 'stage'=>$userInfo['now_stage']
            ])->field('groups')
            ->find();
        if(empty($permission)){
            return NULL;
        }
        $groups = explode(',',$permission->groups);
        //找出符合此班级权限下的所有分组
        if(!empty($data) && !empty($groups)){
            $arr = [];
            foreach ($data as $key=>$val){
                foreach ($groups as $k=>$v){
                    if ($val['id'] == $v){
                        array_push($arr,$data[$key]);
                    }
                }
            }
        }

        //确定下一组单词的ID
        foreach ($arr as $key => $val) {
            if ($userInfo['now_group'] == $val['id']) {
                $k = $key + 1;
            }
        }

        //如果下一组单词信息非空，返回组id
        if (!empty($arr[$k])) {
            return $arr[$k]['id'];
        }
        return false;
    }
    /**
     * 给对应的组加上对应的阶段和组对应的类型，用于判断是什么类型
     */
    public function correspondingStage($notLearnedData)
    {
        $data = Group::field('stage_id,type')
            ->get($notLearnedData[0]['group']);

        foreach ($notLearnedData as $key => $val) {
            $notLearnedData[$key]['stage'] = $data['stage_id'];
            $notLearnedData[$key]['type'] = $data['type'];
        }
        return $notLearnedData;
    }

    /**
     * @throws MissException
     */
    private function getWordDetail($userInfo)
    {
        //进行查看着以分组下所有单词的详情
        $groupWord = GroupWord::where('group',$userInfo['now_group'])
            ->select()
            ->toArray();
        $sumNumber = count($groupWord);  //50  这一组单词的总数量
        if (empty($groupWord)) {
            throw new MissException([
                'msg'       => '本组单词为空，请联系管理员进行添加',
                'errorCode' => 50000
            ]);
        }
        //查询此组对应的阶段和当前组的类型
        $notLearnedData = $this->correspondingStage($groupWord);
        $notWordData = $this->detail($notLearnedData);
        //判断该用户单词是否收藏过
        $notWordData = Collection::isCollection($userInfo['id'], $notWordData);
        $notWordData = $this->handleData($notWordData, 1);
        $notWordData['count'] = $sumNumber;
        return $notWordData;
    }
}