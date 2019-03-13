<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/6
 * Time: 15:36
 */

namespace app\api\controller\v1;

use app\api\model\EnglishWord;
use app\api\model\LearnedHistory;
use app\api\model\User;
use app\api\model\Stage;
use app\api\model\UserClass;
use app\api\model\Share;
use app\api\service\Token;
use app\lib\exception\MissException;
use app\lib\exception\UserClassException;
class Index
{
    public function getUserInfo()
    {
        //根据token获取用户昵称，头像，所属班级,阶段名称，打卡天数，今日已学，已掌握，所剩新词，日历
        $uid = Token::getCurrentTokenVar('uid');
        $classInfo = UserClass::getClassInfo($uid);

        if(empty($classInfo)){
            throw new UserClassException();
        }

        $calendar = LearnedHistory::calendarDays($uid);
        $UserInfo = User::getUserInfo($uid);
        $className = UserClass::getClassName($classInfo);
        $punchDays = Share::getPunchDays($uid);
        $todayLearnedNumber = LearnedHistory::getTodayLearnedNumber($uid);
        $LearnedData = LearnedHistory::UserLearned($uid);
        $allLearnedNumber = LearnedHistory::getAllLearnedNumber($uid);
        $stageName = Stage::getStageNameByLearnedNumber($LearnedData);
        $wordCount = EnglishWord::count();
        $surplusWord = $wordCount-$allLearnedNumber;
        $data = [
            'nick_name'=>&$UserInfo['nick_name'],
            'user_name'=>&$UserInfo['user_name'],
            'avatar_url'=>&$UserInfo['avatar_url'],
            'stage_name'=>&$stageName,
            'is_teacher'=>&$UserInfo['is_teacher'],
            'class_name'=>&$className,
            'punch_days'=>&$punchDays,
            'today_learned_number'=>&$todayLearnedNumber,
            'all_learned_number'=>&$allLearnedNumber,
            'surplus_word'=>&$surplusWord,
            'calendar'=>&$calendar
        ];
        if(!$data){
            return json([
                'msg' => '首页信息查询失败',
                'errorCode' => 50000,
                'request_url' => errorUrl()
            ]);
        }
        return json($data);
    }
}