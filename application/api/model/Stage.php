<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/2
 * Time: 14:10
 */

namespace app\api\model;


use app\lib\exception\MissException;
use think\Db;
use think\Model;

class Stage extends Model
{
    public static function getStages()
    {
        return Db::table(YX_QUESTION . 'stage')
            ->field('id,stage_name')
            ->where('parent_id', 0)
            ->order('sort')
            ->select();
    }

    public static function getAllStage()
    {

        return Db::table(YX_QUESTION . 'stage')
            ->order('sort')
            ->hidden(['create_time'])
            ->select();
    }


    protected static function getGroup($stageData)
    {
        $new_arr = [];
        foreach ($stageData as $val) {
            $sql = "SELECT s.id,e.group,e.stage FROM " . YX_QUESTION . "stage AS s INNER JOIN " . YX_QUESTION . "english_word AS e ON s.id=e.stage WHERE e.stage = $val[id] GROUP BY e.group ";

            $res = Db::query($sql);

            if (!empty($res)) {
                array_push($new_arr, $res);
            }
        }

        foreach ($new_arr as $k => $v) {
            foreach ($stageData as $kk => $z) {
                if ($z['id'] == $v[0]['stage']) {
                    $stageData[$kk]['group'] = count($v);
                }
            }
        }

        return $stageData;

    }

    /**
     * 获取某一阶段详情
     * @param $id
     * @return array|false|null|\PDOStatement|string|Model
     */
    public static function findStage($id)
    {
        return Db::table(YX_QUESTION . 'stage')->hidden(['create_time'])->where('id', $id)->find();
    }


    public static function getStageName($historyData)
    {
        foreach ($historyData as $key => $val) {

            $data = Db::table(YX_QUESTION . 'stage')
                ->where('id', $val['stage'])
                ->field('id,stage_name')
                ->select();

            foreach ($data as $k => $v) {
                if ($val['stage'] == $v['id']) {
                    $historyData[$key]['stage_name'] = $v['stage_name'];
                }
            }
        }
        return $historyData;
    }

    /**
     * 根据用户最后一次学习阶段返回阶段名称
     * @param $LearnedData
     * @return mixed
     */
    public static function getStageNameByLearnedNumber($LearnedData)
    {
        $data = Db::table(YX_QUESTION . 'stage')
            ->where('id', $LearnedData['stage'])
            ->field('stage_name,word_num')
            ->find();
        return $data['stage_name'];
    }

    /**
     * 找出下一阶段下一组id
     * @param $stageID
     */
    public static function nextStageGroupInfo($userInfo)
    {
        //进行阶段排序
        $stageData = Db::table(YX_QUESTION . 'stage')
            ->order('sort')
            ->select();
        //找出当前阶段
        $nowStage = Db::table(YX_QUESTION . 'stage')
            ->where('id', $userInfo['now_stage'])
            ->find();
        foreach ($stageData as $key => $val) {
            if ($nowStage == $stageData[$key]) {
                $k = $key + 1;
            }
        }

        //如果下一阶段信息非空
        if (!empty($stageData[$k])) {
            return $stageData[$k]['id'];

        }
        return false;


        //先根据阶段进行排序小组
        $data = Db::table(YX_QUESTION . 'stage')
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

    /**
     * 根据不是父级分类进行排序，获取第一阶段id
     */
    public static function FirstStageID()
    {
        $commonID  = Stage::CommonStageID();
        $stageData = Db::table(YX_QUESTION . 'stage')
            ->where('parent_id', '<>', 0)
            ->where('parent_id', '<>', $commonID)
            ->order('sort')
            ->field('id')
            ->select();
        if (!empty($stageData)) {
            return $stageData[0]['id'];
        }

        return false;
    }

    public static function commonStageID()
    {
        $stageData = Db::table(YX_QUESTION . 'stage')
            ->where('parent_id', 0)
            ->order('sort')
            ->field('id')
            ->select();
        if (!empty($stageData)) {
            return $stageData[0]['id'];
        }
        return false;
    }

    public static function FirstCommonStageID()
    {
        $stageData = Db::table(YX_QUESTION . 'stage')
            ->where('parent_id', '<>', 0)
            ->order('sort')
            ->field('id')
            ->select();
        if (!empty($stageData)) {
            return $stageData[0]['id'];
        }
        return false;
    }

    /**
     * 公共阶段下所有的子阶段
     * @param $commonID
     * @return bool|string
     */
    public static function selectStageData($commonID)
    {
        $stage = Db::table(YX_QUESTION . 'stage')
            ->where('parent_id', $commonID)
            ->field('id')
            ->select();

        $ids = '';
        foreach ($stage as $key=>$val){
            $ids .= ','.$val['id'];
        }

        $ids = substr($ids,1);
        return $ids;
    }

    /**
     * 公共阶段下所有的子阶段
     * @param $commonID
     * @return bool|string
     */
    public static function selectCommonStageData($commonID)
    {
        $commonData = Db::table(YX_QUESTION . 'stage')
            ->where('parent_id', $commonID)
            ->field('id')
            ->select();

        return $commonData;
    }

    /**
     * 查询对应的班级阶段导航胶囊按钮
     * @param $class_id
     */
    public static function getStageNavigationButton($class_id)
    {
        return Db::table('yx_class_permission')
            ->alias('cp')
            ->join(YX_QUESTION.'stage s','cp.stage=s.id')
            ->where('cp.class_id',$class_id)
            ->where('cp.groups','<>','')
            ->order('s.sort')
            ->field('s.id as stage_id,s.stage_name')
            ->select();
    }

    /**
     * 班级对应的阶段权限
     * @param $class_id
     */
    public static function getClassStageInformation($class_id)
    {
        //此班级拥有的权限数据
        $data = Db::table('yx_class_permission')
            ->alias('cp')
            ->join(YX_QUESTION.'stage s','cp.stage=s.id')
            ->where('cp.class_id',$class_id)
            ->where('cp.groups','<>','')
            ->order('s.sort')
            ->field('s.id,s.stage_desc,s.parent_id,s.stage_name,s.group_num,s.word_num')
            ->select();
        //所有父阶段数据
        $parentData = Db::table(YX_QUESTION.'stage')
            ->where('parent_id',0)
            ->field('id,stage_name,group_num,word_num,parent_id')
            ->select();
        if(empty($data) || empty($data)){
            throw new MissException([
                'msg'=>'此班级还没有分配任何权限',
                'errorCode'=>50000
            ]);
        }
        foreach ($parentData as $key=>$val){
            $parentData[$key]['son'] = [];
            foreach ($data as $k=>$v){
                if($val['id'] == $v['parent_id']){
                  array_push($parentData[$key]['son'],$v);
                  continue;
                }
            }
            if(empty($parentData[$key]['son'])){
                unset($parentData[$key]);
                continue;
            }
        }

        return $parentData;
    }
}