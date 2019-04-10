<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/6
 * Time: 15:36
 */

namespace app\api\controller\v2;

use app\api\model\EnglishWord;
use app\api\model\LearnedHistory;
use app\api\model\Share;
use app\api\model\Stage;
use app\api\model\User;
use app\api\model\UserClass;
use app\api\service\Token;
use app\lib\enum\ScopeEnum;
use app\lib\exception\MissException;
use think\facade\Request;

class Index
{
    public function getUserInfo()
    {
        //根据token获取用户昵称，头像，所属班级,阶段名称，打卡天数，今日已学，已掌握，所剩新词，日历
        $uid       = Token::getCurrentTokenVar('uid');
        $classInfo = UserClass::getClassInfo($uid);
        $calendar  = LearnedHistory::calendarDays($uid);
        $UserInfo  = User::getUserInfo($uid);
        //根据互联网用户和班级学员老师赋值不同的权限
        $res = $this->ScopeEnum($UserInfo);
        if (!$res) {
            throw new MissException([
                'msg'       => '此用户赋值权限出错',
                'errorCode' => 50000
            ]);
        }
        $recordStage = cache('record_stage' . $uid);
        try {
            $className          = UserClass::getClassName($classInfo);
            $punchDays          = Share::getPunchDays($uid);
            $todayLearnedNumber = LearnedHistory::getTodayLearnedNumber($uid);

            $LearnedData        = LearnedHistory::UserLearned($uid);
            $allLearnedNumber   = LearnedHistory::getAllLearnedNumber($uid);
            $stageName          = Stage::getStageNameByLearnedNumber($LearnedData);
            $wordCount          = EnglishWord::count();
            $surplusWord        = $wordCount - $allLearnedNumber;
            $data = [
                'nick_name'            => urlDecodeNickName($UserInfo['nick_name']),
                'user_name'            => &$UserInfo['user_name'],
                'avatar_url'           => &$UserInfo['avatar_url'],
                'stage_name'           => &$stageName,
                'is_teacher'           => &$UserInfo['is_teacher'],
                'class_name'           => !empty($className) ? $className : '',
                'punch_days'           => &$punchDays,
                'today_learned_number' => &$todayLearnedNumber,
                'all_learned_number'   => &$allLearnedNumber,
                'surplus_word'         => &$surplusWord,
                'calendar'             => &$calendar,
                'record_stage'         => !empty($recordStage) ? $recordStage : ''
            ];
            return json($data);
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }

    }

    /**
     * 赋值对应的权限
     * @param $UserInfo
     */
    private function ScopeEnum($UserInfo)
    {

        $request     = Request::instance();
        $token       = $request->header('token');
        $data        = cache($token);
        $data        = json_decode($data, true);
        $create_time = time() - $data['create_time'];
        $expire_in   = config('setting.token_expire_in');
        $expire_in   = $expire_in - $create_time;
        switch ($UserInfo['is_teacher']) {
            case 0:
                $data['scope'] = ScopeEnum::User;
                cache($token, json_encode($data), $expire_in);
                return true;
            case 1:
                $data['scope'] = ScopeEnum::Student;
                cache($token, json_encode($data), $expire_in);
                return true;
            case 2:
                $data['scope'] = ScopeEnum::Teacher;
                cache($token, json_encode($data), $expire_in);
                return true;
            default:
                return false;
        }
    }
}