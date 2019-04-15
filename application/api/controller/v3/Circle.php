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
use app\api\model\UserIntention;
use app\api\service\Token;
use app\api\validate\ClassID;
use app\lib\enum\ScopeEnum;
use app\lib\exception\MissException;
use think\Db;
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
        $userClassData = UserClass::findUserClass($uid,$data['class_id']);
        if(!empty($userClassData)){
            $status = 1;
        }else{
            $intentionData = Db::name('user_intention')
                ->where('user_id',$uid)
                ->where('class_id',$data['class_id'])
                ->field('status')
                ->find();
            $status = !empty($intentionData) ?$intentionData['status'] : 0;
        }
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
                'status'               => $status,
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
     *圈子内班级的今日榜单
     */
    public function getRankingList()
    {
        //判断此用户是否是这个班级的
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new ClassID();
        $validate->goCheck();
        $is_today = empty(input('post.is_today')) ? 1 : input('post.is_today');
        $data = $validate->getDataByRule(input('post.'));
        $userClassData = UserClass::findUserClass($uid,$data['class_id']);
        //判断用户是否是此班级成员
        if (empty($userClassData)) {
           throw new MissException([
               'msg'=>'你暂时不是班级成员，请申请加入！',
               'errorCode'=>50000
           ]);
        }
        $todayList = $this->getClassRanKing($data['class_id'],$is_today);
        if(empty($todayList)){
            throw new MissException([
                'msg'       => '暂时没有人进行学习，快来抢沙发呀！',
                'errorCode' => 50000
            ]);
        }

        return json($todayList);
    }

    /**
     * 添加用户对此班级的意向
     */
    public function addUserIntention(){
        //判断此用户是否是这个班级的
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new ClassID();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //如果点击的是小试牛刀班级，则直接更改为审核成功状态
        $classData = UserClass::getAscClassInfo();
        if($classData[0]['id'] == $data['class_id']){
            $data['status'] = 1;
        }else{
            $data['status'] = 2;
        }
        $data['user_id'] = $uid;
        $data['create_time'] = time();
        $res = UserIntention::addUserIntention($data);
        if(empty($res)){
            throw new MissException([
                'msg'=>'操作失败',
                'errorCode'=>50000
            ]);
        }
        return json(['msg'=>'ok','errorCode'=>0,'status'=>$res['status']]);
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

    /**
     * 获取班级排行榜信息
     * @param $uid
     * @throws MissException
     */
    private function getClassRanking($class_id, $is_today = 1)
    {

        try {
            //每次进来根据用户查询此班级下是否有缓存
            if ($is_today == 1) {
                $classRankingData = cache('class_id_ranking_' . $class_id . '_today');
            } else {
                $classRankingData = cache('class_id_ranking_' . $class_id . '_history');
            }
            if (!empty($classRankingData)) {
                return $classRankingData;
            } else {
               if($is_today == 1){
                   //缓存整个班级的信息，用户头像，昵称，用户名，掌握多少单词，坚持多少天，总共学习了多少个单词
                   $classData         = UserClass::allTodayClassUserData($class_id);
                   $classRankingData  = LearnedHistory::getUserTodayLearnedNumber($classData);
                   foreach ($classRankingData as $key=>$val){
                       $classRankingData[$key]['nick_name'] = urlDecodeNickName($val['nick_name']);
                   }
                   cache('class_id_ranking_' . $class_id . '_today',$classRankingData,7200);
                   return $classRankingData;
               }else{
                   //历史榜单
                   $classRankingData         = UserClass:: allHistoryClassUserData($class_id,20);
                   foreach ($classRankingData as $key=>$val){
                       $classRankingData[$key]['nick_name'] = urlDecodeNickName($val['nick_name']);
                       $classRankingData[$key]['today_learned_number'] = '';
                   }
                   cache('class_id_ranking_' . $class_id . '_history',$classRankingData,7200);
                   return $classRankingData;
               }
            }
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }


}