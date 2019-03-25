<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/8
 * Time: 15:04
 */

namespace app\api\controller\v1;

use app\api\model\LearnedHistory;
use app\api\model\UserClass;
use app\api\service\Token;
use app\lib\exception\MissException;
use think\Db;

class Top
{
    public function getTodayList()
    {
        //根据id获取用户所属班级，并且获得此班级级所有学员，查看每个用户当天学习了多少单词，多少天进行排名
        $uid       = Token::getCurrentTokenVar('uid');
        $classData = UserClass::getAllUserByUid($uid);
        if (empty($classData)) {
            throw new MissException([
                'msg'       => '你暂时不是班级学员,请先加入学习再来哦！',
                'errorCode' => 50000
            ]);
        }
        //获取班级的名称
        $className = Db::table('yx_class')->where('id', $classData[0]['class_id'])->field('class_name')->find();
        if (!$className) {
            throw new MissException([
                'msg'       => '查询学员班级信息失败',
                'errorCode' => 50000
            ]);
        }
        $userTodayLearnedNumber = LearnedHistory::getUserTodayLearnedNumber($classData);
        $userTodayLearnedNumber = LearnedHistory::LearnedDays($userTodayLearnedNumber);
        $userList               = $this->getUserList($userTodayLearnedNumber);
        if (!$userList) {
            throw new MissException([
                'msg'       => '今日榜单信息查询失败',
                'errorCode' => 50000
            ]);
        }

        $new_arr['data'] = $userList;

        foreach ($userList as $key => $val) {
            if ($val['user_id'] == $uid) {
                $top                           = $key + 1;
                $new_arr['mine']               = $val;
                $new_arr['mine']['today_top']  =& $top;
                $new_arr['mine']['class_name'] =& $className['class_name'];
            }

        }

        $new_arr['data'] = array_values($new_arr['data']);

        if (!$new_arr) {
            throw new MissException([
                'msg'       => '请求超时,程序员小哥正在努力修复',
                'errorCode' => 5000
            ]);
        }


        return json($new_arr);
    }

    /**
     * 获取今日排行榜用户信息
     * @param $userTodayLearnedNumber
     */
    private function getUserList($userTodayLearnedNumber)
    {
        foreach ($userTodayLearnedNumber as $key => $val) {
            $user = Db::table('yx_user')->where('id', $val['user_id'])->find();
            if (empty($user)) {
                unset($userTodayLearnedNumber[$key]);
                continue;
            }
            $userTodayLearnedNumber[$key]['user_name']          = &$user['user_name'];
            $userTodayLearnedNumber[$key]['nick_name']          = &$user['nick_name'];
            $userTodayLearnedNumber[$key]['avatar_url']         = &$user['avatar_url'];
            $userTodayLearnedNumber[$key]['all_learned_number'] = &$user['already_number'];
        }

        return $userTodayLearnedNumber;
    }


    public function getHistoryList()
    {
        $uid       = Token::getCurrentTokenVar('uid');
        $classData = UserClass::getAllUserByUid($uid);
        if (empty($classData)) {
            throw new MissException([
                'msg'       => '你暂时不是班级学员,请先加入学习再来哦！',
                'errorCode' => 50000
            ]);
        }
        //获取班级的名称
        $className = Db::table('yx_class')
            ->where('id', $classData[0]['class_id'])
            ->field('class_name')
            ->find();
        if (!$className) {
            throw new MissException([
                'msg'       => '查询学员班级信息失败',
                'errorCode' => 50000
            ]);
        }
        $allLearnedNumber = LearnedHistory::getUseLearnedNumber($classData);
        $classData        = LearnedHistory::LearnedDays($allLearnedNumber);
        $userList         = $this->getHistoryUserList($classData);
        if (!$userList) {
            throw new MissException([
                'msg'       => '今日榜单信息查询失败',
                'errorCode' => 50000
            ]);
        }

        $new_arr['data'] = $userList;

        foreach ($userList as $key => $val) {
            if ($val['user_id'] == $uid) {
                $top                            = $key + 1;
                $new_arr['mine']                = $val;
                $new_arr['mine']['history_top'] =& $top;
                $new_arr['mine']['class_name']  =& $className['class_name'];
            }

        }
        $new_arr['data'] = array_values($new_arr['data']);

        if (!$new_arr) {
            throw new MissException([
                'msg'       => '请求超时,程序员小哥正在努力修复',
                'errorCode' => 5000
            ]);
        }
        return json($new_arr);
    }

    /**
     * 获取历史排行榜用户信息
     * @param $userTodayLearnedNumber
     */
    private function getHistoryUserList($userTodayLearnedNumber)
    {

        foreach ($userTodayLearnedNumber as $key => $val) {
            $user = Db::table('yx_user')
                ->where('id', $val['user_id'])
                ->find();
            if (empty($user)) {
                unset($userTodayLearnedNumber[$key]);
                continue;
            }
            $userTodayLearnedNumber[$key]['user_name']  = &$user['user_name'];
            $userTodayLearnedNumber[$key]['nick_name']  = &$user['nick_name'];
            $userTodayLearnedNumber[$key]['avatar_url'] = &$user['avatar_url'];
        }

        return $userTodayLearnedNumber;
    }

    /**
     * 这个查询分页数据
     * @param $new_arr
     * @param $limit
     * @param $pageSize
     */
    private function limitPage()
    {
        $new_arr = [];
        //当前页
        $page = empty(input('post.page')) ? 1 : input('post.page');
        //每页显示条数
        $pageSize = 10;
        //偏移量
        $limit = ($page - 1) * $pageSize;

        $arr             = [];
        $new_arr['data'] = array_slice(array_values($new_arr['data']), $limit, $pageSize, true);
        foreach ($new_arr['data'] as $key => $val) {
            $arr[$key + 1] = $val;
        }
        $new_arr['data'] =& $arr;
    }
}