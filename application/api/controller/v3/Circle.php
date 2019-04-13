<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/13
 * Time: 10:11
 */

namespace app\api\controller\v3;

use app\api\model\EnglishWord;
use app\api\model\LearnedHistory;
use app\api\model\Post;
use app\api\model\Stage;
use app\api\model\User;
use app\api\model\UserClass;
use app\api\service\Token;
use app\api\validate\ClassID;
use app\lib\enum\ScopeEnum;
use app\lib\exception\MissException;
use think\facade\Request;

class Circle
{
    /**
     * 圈子首页信息
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     */
    public function getCircleInfo(){
        $validate = new ClassID();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //根据token获取用户昵称，头像，所属班级,阶段名称，打卡天数，今日已学，已掌握，所剩新词，日历
        $uid       = Token::getCurrentTokenVar('uid');
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
            $classDetail        = UserClass::getClassDetail($data['class_id']);
            $todayLearnedNumber = LearnedHistory::getTodayLearnedNumber($uid);
            $LearnedData        = LearnedHistory::UserLearned($uid);
            $stageName          = Stage::getStageNameByLearnedNumber($LearnedData);
            $wordCount          = EnglishWord::count();
            $surplusWord        = $wordCount - $UserInfo['already_number'];
            $data = [
                'nick_name'            => urlDecodeNickName($UserInfo['nick_name']),
                'user_name'            => &$UserInfo['user_name'],
                'avatar_url'           => &$UserInfo['avatar_url'],
                'stage_name'           => &$stageName,
                'is_teacher'           => &$UserInfo['is_teacher'],
                'class_name'           => !empty($classDetail['class_name']) ? $classDetail['class_name'] : '',
                'class_src'            => !empty($classDetail['class_src']) ? config('setting.img_prefix').$classDetail['class_src'] : '',
                'class_desc'           => !empty($classDetail['class_desc']) ? $classDetail['class_desc'] : '',
                'punch_days'           => &$UserInfo['punch_days'],
                'today_learned_number' => &$todayLearnedNumber,
                'already_number'       => &$UserInfo['already_number'],
                'surplus_word'         => &$surplusWord,
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
     * 圈子内班级的今日打卡
     * @throws \app\lib\exception\ParameterException
     */
    public function getPunchCardToday(){
        $validate = new ClassID();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $data = Post::classLimitPost( $data['class_id'],20);
        if(empty($data)){
            throw new MissException([
                'msg'=>'今日暂时还未有人打卡，快来抢沙发呀！',
                'errorCode'=>0
            ]);
        }
        foreach ($data as $key=>$val){
            $data[$key]['nick_name'] = urlDecodeNickName($val['nick_name']);
            $data[$key]['content'] = json_decode($val['content']);
        }

        return json($data);
    }

    /**
     *圈子内班级的今日打卡
     */
    public function getTodayList()
    {
        return 777;
    }
    /**
     * 赋值对应的权限
     * @param $UserInfo
     */
    private function ScopeEnum($UserInfo){

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