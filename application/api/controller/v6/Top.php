<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v6;

use app\api\dao\LearnedHistory;
use app\api\dao\User;
use app\api\dao\UserClass;
use app\api\service\Token;
use app\lib\exception\MissException;
use think\Db;
use think\Exception;

class Top
{
    /**
     * 今日排行榜
     * @return \think\response\Json
     * @throws Exception
     * @throws MissException
     * @throws \app\lib\exception\TokenException
     */
    public function getTodayList()
    {
        //根据id获取用户所属班级，并且获得此班级级所有学员，查看每个用户当天学习了多少单词，多少天进行排名
        $uid      = Token::getCurrentTokenVar('uid');
        $userInfo = User::field('is_teacher')->get($uid);
        if ($userInfo->is_teacher == 0) {
            $todayList = $this->getInternetRanking($uid);
        } else {
            $todayList = $this->getClassRanKing($uid);
        }
        try {
            return json($todayList);
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }

    /**
     * 利用redis的有序集合实现排行榜
     * @return \think\response\Json
     * @throws Exception
     * @throws MissException
     * @throws \app\lib\exception\TokenException
     */
    public function getTodayRanking()
    {
        //根据id获取用户所属班级，并且获得此班级级所有学员，查看每个用户当天学习了多少单词，多少天进行排名
        $uid       = Token::getCurrentTokenVar('uid');
        $userInfo  = UserClass::getClassInfo($uid);
        $className = UserClass::getClassName($userInfo);
        if (empty($className)) {
            $className = '互联网';
        }
        try {
            $date  = date("Y-m-d", time());
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            // Redis 没设置密码则不需要这行代码
            // $redis->auth('opG5dGo9feYarUifaLb8AdjKcAAXArgZ');
            //榜单
            $rankData = $redis->zRevRange($className . $date, 0, -1, true);
            //zrevrank 查看此用户的今日排名
            $userRank = $redis->zRevRank($className . $date, $uid);
            //进行删除redis不是今日排行的榜单
            //$redis->del($className . $date);
            $userRank = $userRank + 1;
            foreach ($rankData as $key => $val) {
                $userData                         = Db::name('user')
                    ->field('id,user_name,nick_name,avatar_url')
                    ->where('id', $key)
                    ->find();
                $userData['today_learned_number'] = $val;
                $LearnedNumber                    = LearnedHistory::calendarDays($key);
                $userData['learned_days']         = count($LearnedNumber);
                $rankData[$key]                   = $userData;
            }
            $rankData                       = array_values($rankData);
            $userInfo                       = Db::name('user')
                ->field('id,user_name,nick_name,avatar_url')
                ->where('id', $uid)
                ->find();
            $rankData['mine']               = $userInfo;
            $rankData['mine']['min_top']    = $userRank;
            $rankData['mine']['class_name'] = $className;
            return json($rankData);
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }

    }

    /**
     * 获取历史排行榜信息
     * @return \think\response\Json
     * @throws Exception
     * @throws MissException
     * @throws \app\lib\exception\TokenException
     */
    public function getHistoryList()
    {
        $uid = Token::getCurrentTokenVar('uid');

        $userInfo = User::field('is_teacher')->get($uid);
        if ($userInfo->is_teacher == 0) {
            $historyList = $this->getInternetRanking($uid, $is_today = 0);
        } else {
            $historyList = $this->getClassRanKing($uid, $is_today = 0);
        }
        return json($historyList);
    }

    /**
     * 获取排行榜用户信息
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
            $userTodayLearnedNumber[$key]['user_name']          = $user['user_name'];
            $userTodayLearnedNumber[$key]['nick_name']          = urlDecodeNickName($user['nick_name']);
            $userTodayLearnedNumber[$key]['avatar_url']         = &$user['avatar_url'];
            $userTodayLearnedNumber[$key]['all_learned_number'] = &$user['already_number'];
        }
        return array_values($userTodayLearnedNumber);
    }

    /**
     * 获取班级排行榜信息
     * @param $uid
     * @throws MissException
     */
    private function getClassRanking($uid, $is_today = 1)
    {

        try {
            //每次进来根据用户查询此班级下是否有缓存
            $class_id = Db::name('user_class')->field('class_id')->where('user_id', $uid)->find();
            if ($is_today == 0) {
                $userLearnedNumber = cache('class_id_ranking_' . $class_id['class_id'] . '_history');
            } else {
                $userLearnedNumber = cache('class_id_ranking_' . $class_id['class_id'] . '_today');
            }
            if (!empty($userLearnedNumber)) {
                $userInfo        = UserClass::getClassInfo($uid);
                $className       = UserClass::getClassName($userInfo);
                $new_arr['data'] = $userLearnedNumber;
                $dailyQuotations = dailyQuotations(rand(0, 15));
                foreach ($userLearnedNumber as $key => $val) {
                    if ($val['user_id'] == $uid) {
                        $top                                 = $key + 1;
                        $new_arr['mine']                     = $val;
                        $new_arr['mine']['mine_top']         =& $top;
                        $new_arr['mine']['class_name']       =& $className;
                        $new_arr['mine']['daily_quotations'] =& $dailyQuotations;
                    }
                }
                return $new_arr;
            } else {
                //缓存整个班级的信息，用户头像，昵称，用户名，掌握多少单词，坚持多少天，总共学习了多少个单词
                $classData         = UserClass::getAllUserByUid($uid);
                $userData          = $this->getUserList($classData);
                $userList          = LearnedHistory::getUserTodayLearnedNumber($userData);
                $className         = Db::table('yx_class')
                    ->where('id', $classData[0]['class_id'])
                    ->field('class_name')
                    ->find();
                $userLearnedNumber = LearnedHistory::LearnedDays($userList);
                if ($is_today == 0) {
                    // 取得列的列表
                    foreach ($userLearnedNumber as $key => $row) {
                        $edition[$key] = $row['all_learned_number'];
                    }
                    array_multisort($edition, SORT_DESC, $userLearnedNumber);
                    //进行缓存已经排序好的历史榜单数据
                    cache('class_id_ranking_' . $class_id['class_id'] . '_history', $userLearnedNumber, 7200);
                } else {
                    // 取得列的列表
                    foreach ($userLearnedNumber as $key => $row) {
                        $edition[$key] = $row['today_learned_number'];
                    }
                    array_multisort($edition, SORT_DESC, $userLearnedNumber);
                    //进行缓存已经排序好的历史榜单数据
                    cache('class_id_ranking_' . $class_id['class_id'] . '_today', $userLearnedNumber, 7200);
                }

                $new_arr['data'] = $userLearnedNumber;
                $dailyQuotations = dailyQuotations(rand(0, 15));
                foreach ($userLearnedNumber as $key => $val) {
                    if ($val['user_id'] == $uid) {
                        $top                                 = $key + 1;
                        $new_arr['mine']                     = $val;
                        $new_arr['mine']['mine_top']         =& $top;
                        $new_arr['mine']['class_name']       =& $className['class_name'];
                        $new_arr['mine']['daily_quotations'] =& $dailyQuotations;
                    }
                }
                return $new_arr;
            }
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }

    /**
     * 获取互联网用户排名
     * @param $uid
     * @return mixed
     * @throws MissException
     */
    private function getInternetRanking($uid, $is_today = 1)
    {
        try {

            if ($is_today == 0) {
                $userList = cache('InterRanking_history');
            } else {
                $userList = cache('InterRanking_today');
            }

            if (!empty($userList)) {
                $new_arr['data'] = $userList;
                $dailyQuotations = dailyQuotations(rand(0, 15));
                foreach ($userList as $key => $val) {
                    if ($val['user_id'] == $uid) {
                        $top                                 = $key + 1;
                        $new_arr['mine']                     = $val;
                        $new_arr['mine']['mine_top']         =& $top;
                        $new_arr['mine']['class_name']       = NULL;
                        $new_arr['mine']['daily_quotations'] =& $dailyQuotations;
                    }
                }
                return $new_arr;
            }

            $userData = User::where('is_teacher', 0)
                ->where('mobile', '<>', '')
                ->field('id as user_id')
                ->select();
            if ($is_today == 0) {
                $allLearnedNumber  = LearnedHistory::getUseLearnedNumber($userData->toArray());
                $userLearnedNumber = LearnedHistory::LearnedDays($allLearnedNumber);
                $userList          = $this->getUserList($userLearnedNumber);
                cache('InterRanking_history', $userList, 7200);
            } else {
                $userTodayLearnedNumber = LearnedHistory::getUserTodayLearnedNumber($userData->toArray());
                $userLearnedNumber      = LearnedHistory::LearnedDays($userTodayLearnedNumber);
                $userList               = $this->getUserList($userLearnedNumber);
                cache('InterRanking_today', $userList, 7200);

            }
            $new_arr['data'] = $userList;
            $dailyQuotations = dailyQuotations(rand(0, 15));
            foreach ($userList as $key => $val) {
                if ($val['user_id'] == $uid) {
                    $top                                 = $key + 1;
                    $new_arr['mine']                     = $val;
                    $new_arr['mine']['mine_top']         =& $top;
                    $new_arr['mine']['class_name']       = NULL;
                    $new_arr['mine']['daily_quotations'] =& $dailyQuotations;
                }
            }
            return $new_arr;
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
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