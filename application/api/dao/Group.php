<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/5
 * Time: 17:36
 */

namespace app\api\dao;


use think\Db;
use think\Model;

class Group extends Model
{
    //设置当前模型对应的完整数据表名称
    protected $table = 'yx_group';

    //设置当前模型的数据库链接
    protected $connection = 'db_config_2';

    /**
     * 获取用户所有分组的名称
     * @param $historyData
     * @return mixed
     */
    public static function getGroupName($historyData)
    {
        foreach ($historyData as $key => $val) {

            $data = Db::table(YX_QUESTION . 'group')
                ->where('id', $val['group'])
                ->field('id,group_name')
                ->select();

            foreach ($data as $k => $v) {
                if ($val['group'] == $v['id']) {
                    $historyData[$key]['group_name'] = $v['group_name'];
                }
            }
        }

        return $historyData;
    }

    /**
     * 根据阶段id获取分组名称
     * @param $id
     * @return mixed
     */
    public static function findGroupName($id)
    {
        $groupData = Db::table(YX_QUESTION . 'group')
            ->where('id', $id)
            ->find();
        return $groupData['group_name'];
    }

    public static function getGroupData($lastWord)
    {
        //返回用户这组下还未学习的单词 = 这组下所有的单词-用户这组下所有学过的单词
        $allData = Db::table(YX_QUESTION . 'group_word')
            ->where('group', $lastWord['group'])
            ->select();
        $learnedData = Db::table('yx_learned_history')
            ->where('user_id', $lastWord['user_id'])
            ->where('group', $lastWord['group'])
            ->field('group,word_id')
            ->select();

        foreach ($allData as $key => $val) {
            foreach ($learnedData as $k => $v) {
                if ($val['wid'] == $v['word_id']) {
                    unset($allData[$key]);
                }
            }
        }

        return array_values($allData);
    }

    public static function getAllData($lastWord)
    {
        return Db::table(YX_QUESTION . 'group_word')
            ->where('group', $lastWord['group'])
            ->select();

    }

    /**
     * 给对应的组加上对应的阶段和组对应的类型，用于判断是什么类型
     * @param $notLearnedData
     * @return mixed
     */
    public static function correspondingStage($notLearnedData)
    {
        $data = Db::table(YX_QUESTION . 'group')
            ->where('id', $notLearnedData[0]['group'])
            ->field('stage_id,type')
            ->find();
        foreach ($notLearnedData as $key => $val) {
            $notLearnedData[$key]['stage'] = $data['stage_id'];
            $notLearnedData[$key]['type'] = $data['type'];

        }

        return $notLearnedData;
    }

    /**
     * 此阶段下共有哪几组单词(组名称)，每个组下有多少单词
     * @param $id
     */
    public static function getEachStageGroupData($id)
    {
        return Db::table(YX_QUESTION . 'group')
            ->where('stage_id', $id)
            ->order('sort')
            ->field('id,group_name,word_num')
            ->select();
    }

    /**
     * 返回用户最后一次学习组的id
     * @param $historyLearnedData
     * @return array|false|null|\PDOStatement|string|Model
     */
    public static function findLastGroupID($historyLearnedData)
    {

        $data = Db::table(YX_QUESTION . 'group')
            ->where('stage_id', $historyLearnedData['stage'])
            ->where('sort', $historyLearnedData['sort'])
            ->field('id,sort,stage_id')
            ->find();
        return $data['id'];
    }

    public static function findStageID($groupID)
    {
        $data = Db::table(YX_QUESTION . 'group')
            ->where('id', $groupID)
            ->field('stage_id')
            ->find();
        return $data['stage_id'];
    }

    /**
     * 获取词组的排序id
     * @param $userInfo
     * @return mixed
     */
    public static function userLastGroupID($userInfo)
    {
        //先根据阶段进行排序小组
        $data = Db::table(YX_QUESTION . 'group')
            ->where('stage_id', $userInfo['now_stage'])
            ->order('sort')
            ->select();
        //找出当前小组
        $res = Db::table(YX_QUESTION . 'group')
            ->where('stage_id', $userInfo['now_stage'])
            ->where('id', $userInfo['now_group'])
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

    //去找下一阶段的第一组id
    public static function nextStageFirstGroupID($nextStageID)
    {
        //先根据阶段进行排序小组
        $data = Db::table(YX_QUESTION . 'group')
            ->where('stage_id', $nextStageID)
            ->order('sort')
            ->select();
        //如果下一组单词信息非空，返回组id
        if (!empty($data)) {
            return $data[0]['id'];
        }

        return false;
    }


    /**
     * 返回词组的排序id
     * @param $historyLearnedData
     */
    public static function userLastSortID($historyLearnedData)
    {

        $data = Db::table(YX_QUESTION . 'group')
            ->where('stage_id', $historyLearnedData['stage'])
            ->where('id', $historyLearnedData['group'])
            ->field('id,sort')
            ->find();
        return $data['id'];
    }


    public static function firstGroupID($stageID)
    {
        $data = Db::table(YX_QUESTION . 'group')
            ->where('stage_id', $stageID)
            ->order('sort')
            ->field('id')
            ->select();

        if (empty($data)) {
            return false;
        }

        return $data[0]['id'];
    }

    /**
     * 判断这个班级下这个阶段下所有组的权限
     * @param $data
     */
    public static function getEachGroupInformation($data)
    {
        $groupData = Db::table('yx_class_permission')
            ->where('class_id',$data['class_id'])
            ->where('stage',$data['stage'])
            ->field('groups')
            ->find();
        if(!empty($groupData)){
            $groups = explode(',',$groupData['groups']);
            $data = [];
            foreach ($groups as $key=>$val){
                $arr = Db::table(YX_QUESTION.'group')
                    ->where('id',$val)
                    ->field('id,group_name,word_num,sort')
                    ->find();
                array_push($data,$arr);
            }
            //根据字段last_name对数组$data进行降序排列
            $sort= array_column($data,'sort');
            array_multisort($sort,SORT_ASC,$data);
            return $data;
        }
    }

    /****************************************V4start****************************************************/

    /**
     * 获取符合班级权限的下一组ID
     * @param $LearnedData 当前组信息
     * @return mixed
     */
    public static function nextGroupIDByClassPermissions($LearnedData)
    {
        //找出此阶段下所有的组，进行排序
        $data = Db::table(YX_QUESTION . 'group')
            ->where('stage_id', $LearnedData['stage'])
            ->field('id,group_name,sort')
            ->order('sort')
            ->select();
        //找出此班级下有权限的组
        //判断是否在对应的班级权限里
        $permissionData =  Db::table('yx_class_permission')
            ->where('class_id',$LearnedData['class_id'])
            ->where('stage',$LearnedData['stage'])
            ->field('groups')
            ->find();

        $groups = explode(',',$permissionData['groups']);
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
//        //sort
//        $sort = array_column($arr,'sort');
//        array_multisort($sort,SORT_ASC,$arr);
        if(empty($LearnedData['group'])){
            return $arr[0]['id'];
        }
        //确定下一组单词的ID
        foreach ($arr as $key => $val) {
            if ($LearnedData['group'] == $val['id']) {
                $k = $key + 1;
            }
        }

        //如果下一组单词信息非空，返回组id
        if (!empty($arr[$k])) {
            return $arr[$k]['id'];
        }
        return false;
    }
}