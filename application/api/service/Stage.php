<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/17
 * Time: 14:13
 */


namespace app\api\service;

use app\api\model\ClassPermission;
use app\api\model\Group;
use app\api\model\LearnedHistory;
use app\api\model\Stage as StageModel;
use app\lib\exception\MissException;

class Stage
{
    /**
     * 根据班级权限，切换阶段父id
     * 获取班级权限下的阶段信息
     * @param $class_id
     * @param $stage_id
     * @return array
     * @throws MissException
     */
    public function getCpStage($class_id, $stage_id)
    {
        //获取父分类下所有子分类
        $stages = StageModel::getCpStage($class_id, $stage_id);
        if(empty($stages)){
            throw new MissException([
                'msg'       => '还没有任何阶段',
                'errorCode' => 50000
            ]);
        }
        $stages = $stages->toArray();
        foreach ($stages as $key=>&$val){
            if(empty($val['cp'])){
               unset($stages[$key]);
            }
            unset($val['cp']);
        }
        //所有父阶段数据
        $parentData = StageModel::where('parent_id',0)
            ->field('id,stage_name,group_num,word_num,parent_id')
            ->order('sort')
            ->select();
        $parentData = $parentData->toArray();

        foreach ($parentData as $key=>&$val){
            $val['son'] = [];
            foreach ($stages as &$v){
                if($val['id'] == $v['parent_id']){
                    array_push($val['son'],$v);
                }
            }
            if(empty($val['son'])){
                unset($parentData[$key]);
                continue;
            }
        }
       return array_values($parentData);
    }

    /**
     * 用户各个阶段所需单词数量
     * @param $uid
     * @param $stages
     */
    public function getAlreadyNumberByStage($uid, $stages)
    {
        foreach ($stages[0]['son'] as $key => &$val) {
                $count = LearnedHistory::where(
                    ['stage'=>$val['id'],
                    'user_id'=>$uid
                ])->count();
                $val['alreadyNum'] = $count;
        }
        return $stages;
    }

    /**
     * 获取阶段下共多少组，多少单词
     * 获取用户已练习多少组，已练习多少单词
     * 展示此阶段下共有哪几组单词(组名称)，每个组下有多少单词，用户每组学了多少单词
     * 用户是否获取此勋章称号
     * @param $uid
     * @param $stage_id
     * @param $class_id
     * @return mixed
     */
    public function detail($uid, $stage_id, $class_id)
    {
        //阶段信息
        $stageData = StageModel::get($stage_id)
            ->hidden(['sort','parent_id','create_time']);
        //用户此阶段下学过的组id
        $historyGroupData = LearnedHistory::getGroupByStageID($uid, $stage_id);
        //用户此阶段共学习了多少组
        $historyGroupCount = count($historyGroupData);
        //用户此阶段共学习了多少单词
        $historyWordCount = LearnedHistory::where(
            ['user_id'=>$uid,
             'stage'=>$stage_id])
            ->count();
        //班级下此阶段组的权限
        $eachGroupData = $this->groupPermission($stage_id,$class_id);
        //每组已经学过的单词
        $eachGroupData = $this->eachGroupData($uid, $historyGroupData, $eachGroupData, $historyWordCount);

        $stageData['history_group_count'] = $historyGroupCount;
        $stageData['history_word_count'] = $historyWordCount;
        $stageData['each_group_data'] = $eachGroupData;
        return $stageData;
    }

    /**
     * @throws MissException
     */
    private function groupPermission($stage_id,$class_id)
    {
        $groupData = ClassPermission::getGroupsPermission($stage_id,$class_id);
        $groups = explode(',',$groupData->groups);
        $eachGroupData = [];
        foreach ($groups as $key=>$val){
            $arr = Group::field('id,group_name,word_num,sort')
                ->get($val);
            if(!empty($arr)){
                array_push($eachGroupData,$arr->toArray());
            }

        }
        //根据字段last_name对数组$data进行降序排列
        $sort= array_column($eachGroupData,'sort');
        array_multisort($sort,SORT_ASC,$eachGroupData);
        if(empty($eachGroupData)){
            throw new MissException([
                'msg'       => '此阶段下没有任何分组',
                'errorCode' => 50000
            ]);
        }
        return $eachGroupData;
    }

    private function eachGroupData($uid, $historyGroupData, $eachGroupData, $historyWordCount)
    {
        foreach ($historyGroupData as $key => $val) {
            $alreadyGroupNum = LearnedHistory::where(
                ['user_id'=>$uid,
                 'group'=>$val['group']])->count();
            $historyGroupData[$key]['already_group_num'] = $alreadyGroupNum;
        }
        //查看此阶段下，每组学习下多少个单词
        if ($eachGroupData && $historyGroupData) {
            foreach ($eachGroupData as $key=>&$val) {
                foreach ($historyGroupData as $k=>$v) {
                    if ($val['id'] == $v['group']) {
                        $val['already_group_num'] = $v['already_group_num'];
                    }
                }
                if (!array_key_exists('already_group_num', $val)) {
                    $val['already_group_num'] = 0;
                }
            }
        }
        //如果没有学习记录 则每组学习0个单词
        if ($historyWordCount==0) {
            foreach ($eachGroupData as $key => $val) {
                $eachGroupData[$key]['already_group_num'] = 0;
            }
        }
        return $eachGroupData;
    }
}