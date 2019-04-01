<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/16
 * Time: 10:21
 */

namespace app\api\controller\v2;

use app\api\controller\BaseController;
use app\api\model\Group;
use app\api\model\Stage;
use app\api\model\UserClass;
use app\api\service\Token;
use app\lib\exception\MissException;
use think\Db;

class Teacher extends BaseController
{
    protected $beforeActionList = [
        'checkSuperScope' => ['only' => 'getClassStatus']
    ];

    public function getClassStatus()
    {
        //根据token获取老师所在班级的名称，班级人数，今日学习人数？
        //选组  所有分组
        //排序根据学习学习单词总量排序？
        $uid   = Token::getCurrentTokenVar('uid');
        $stage = empty(input('post.stage')) ? '' : input('post.stage');
        $group = empty(input('post.group')) ? '' : input('post.group');
        $sort  = empty(input('post.sort')) ? '1' : input('post.sort');


        if (!empty($stage) && !empty($group)) {
            $allStudentInfo = $this->getAdminInfo($uid, $stage, $group, $sort);

            $groupName    = Db::table(YX_QUESTION . 'group')->field('group_name')->where('id', $group)->find();
            $allStudentInfo['group_name'] =& $groupName['group_name'];
            if (empty($allStudentInfo)) {
                throw new MissException([
                    'msg'       => '此班级下,此阶段下,此分组下,学员信息查询失败',
                    'errorCode' => 50000
                ]);
            }

            return json($allStudentInfo);
        }

        //如果没有阶段和分组则进行默认查询第一阶段第一组，学员排名信息
        $stageID = Stage::FirstStageID();
        if (empty($stageID)) {
            throw new MissException([
                'msg'       => '没有get到任何阶段信息',
                'errorCode' => 5000
            ]);
        }
        //根据第一阶段ID,找出此阶段下第一分组,单词信息
        $firstGroupID = Group::firstGroupID($stageID);
        $groupName    = Db::table(YX_QUESTION . 'group')->field('group_name')->where('id', $firstGroupID)->find();
        if (empty($firstGroupID)) {
            throw new MissException([
                'msg'       => '没有get到该阶段下，任何分组信息',
                'errorCode' => 5000
            ]);
        }

        $allStudentInfo               = $this->getAdminInfo($uid, $stageID, $firstGroupID, $sort);
        $allStudentInfo['group_name'] =& $groupName['group_name'];
        if (empty($allStudentInfo)) {
            throw new MissException([
                'msg'       => '此班级下,此阶段下,此分组下,学员信息查询失败',
                'errorCode' => 50000
            ]);
        }

        return json($allStudentInfo);
    }


    /**
     * 获取老师页面筛选接口
     * @return int
     */
    public function getScreenInfo()
    {
        $stageData = Db::table(YX_QUESTION . 'stage')
            ->where('parent_id', '<>', 0)
            ->order('sort')
            ->field('id,stage_name')
            ->select();

        foreach ($stageData as $key => $val) {
            $groupData = Db::table(YX_QUESTION . 'group')
                ->where('stage_id', $val['id'])
                ->field('id,group_name')
                ->order('sort')
                ->select();

            if (empty($groupData)) {
                unset($stageData[$key]);
                continue;
            }
            $stageData[$key]['group'] = $groupData;
        }

        if (empty($stageData)) {
            throw new MissException([
                'msg'       => '筛选信息接口查询失败',
                'errorCode' => 50000
            ]);
        }
        return json($stageData);
    }


    /**
     * 此班级下,此阶段下,此分组下,所有学员信息
     * @param $stageID
     * @param $firstGroupID
     * @param $classData
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function allStudentInfo($stageID, $firstGroupID, $classData)
    {
        foreach ($classData as $key => $val) {
            $i    = 0;
            $data = Db::table('yx_learned_history')
                ->where('user_id', $val['user_id'])
                ->where('stage', $stageID)
                ->where('group', $firstGroupID)
                ->select();
            //如果此学员没有学习记录,则已学习和已掌握为0
            if (empty($data)) {
                $classData[$key]['already_studied']  = 0;    //已学习
                $classData[$key]['already_mastered'] = 0;   //已掌握
                continue;
            }
            //否则，统计该学员已学习多少个单词
            $classData[$key]['already_studied'] = count($data);
            //和正确率

            foreach ($data as $k => $v) {
                //答对为已掌握
                if ($v['is_true'] == 1) {
                    $i++;
                }
            }

            $classData[$key]['already_mastered'] = $i;
        }

        return $classData;
    }

    /**
     * 二维数组进行排序
     * @param $sort
     * @param $allStudentInfo
     * @return mixed
     */
    private function multisort($sort, $allStudentInfo)
    {
        // 取得列的列表
        foreach ($allStudentInfo as $key => $row) {
            $edition[$key] = $row['already_mastered'];  //根据掌握单词进行排序
        }

        if ($sort == 2) {
            array_multisort($edition, SORT_ASC, $allStudentInfo);
        } else {
            array_multisort($edition, SORT_DESC, $allStudentInfo);
        }
        return $allStudentInfo;
    }


    /**
     * 根据用户id,阶段和分组id，排序进行获取老师页面的信息
     * @param $uid
     * @param $stageID
     * @param $firstGroupID
     * @param $sort
     * @return \think\response\Json
     * @throws MissException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getAdminInfo($uid, $stageID, $firstGroupID, $sort)
    {
        $classInfo = UserClass::getClassInfo($uid);             //此老师所在班级信息
        $className = UserClass::getClassName($classInfo);       //这个班的名称
        $classData = UserClass::getAllUserByUid($uid);          //这个班级下所有的学生
        //根据阶段ID,和此阶段分组ID,查询该阶段下所有成员，学习单词情况，和掌握情况
        $allStudentInfo = $this->allStudentInfo($stageID, $firstGroupID, $classData);
        if (empty($allStudentInfo)) {
            throw new MissException([
                'msg'       => '此班级下,此阶段下,此分组下,学员信息查询失败',
                'errorCode' => 50000
            ]);
        }
        //如果有班级信息，进行展示用户的用户名头像，班级的名称等信息
        $i = 0;
        foreach ($allStudentInfo as $key => $val) {
            $userInfo = Db::table('yx_user')
                ->where('id', $val['user_id'])
                ->field('user_name,nick_name,avatar_url')
                ->find();
            if (empty($userInfo)) {
                unset($allStudentInfo[$key]);
                continue;
            }
            if ($val['already_studied'] != 0) {
                $i++;
            }
            $allStudentInfo[$key]['user_name']  = &$userInfo['user_name'];
            $allStudentInfo[$key]['nick_name']  = &$userInfo['nick_name'];
            $allStudentInfo[$key]['avatar_url'] = &$userInfo['avatar_url'];
        }

        if (empty($allStudentInfo)) {
            throw new MissException([
                'msg'       => '此班级下,此阶段下,此分组下,学员信息查询失败',
                'errorCode' => 50000
            ]);
        }
        $allStudentInfo        = $this->multisort($sort, $allStudentInfo);
        $data['data']          =& $allStudentInfo;
        $data['total_people']  = count($allStudentInfo);
        $data['class_name']    = &$className;
        $data['total_studies'] = $i;


        if (empty($data)) {
            throw new MissException([
                'msg'       => '此班级下,此阶段下,此分组下,学员信息查询失败',
                'errorCode' => 50000
            ]);
        }
        return $data;
    }
}