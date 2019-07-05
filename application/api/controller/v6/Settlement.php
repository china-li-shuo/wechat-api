<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v6;


use app\api\model\Collection;
use app\api\model\Group;
use app\api\model\GroupWord;
use app\api\model\Post;
use app\api\model\Stage;
use app\api\model\User;
use app\api\service\Learned as LearnedService;
use app\api\service\Settlement as SettlementService;
use app\api\service\Token;
use app\api\validate\Post as PostValidate;
use app\api\validate\Settlement as SettlementValidate;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;

class Settlement
{

    /**
     * 请求结算页说明完成本组学习，则用户打卡
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function getSettlementInfo()
    {
        //根据token获取用户刚才所学阶段名称，组名称
        //用户头像，昵称，学习天数，正确率，超过班级百分比
        $uid = Token::getCurrentUid();
        $validate = new SettlementValidate();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $data['user_id'] = $uid;
        $userInfo  = User::getByUid($uid)->toArray();
        $stageData = Stage::get($data['stage']);
        $stageName = $stageData['stage_name'];
        $groupData = Group::get($data['group']);
        $groupName = $groupData['group_name'];
        $userInfo['stage_name'] = &$stageName;
        $userInfo['group_name'] = &$groupName;

        //获取用户此班级下此阶段此组的正确率
        $settlement = new SettlementService();
        $trueRate = $settlement->personalCorrectnessRate($data);
        //查看是否获取勋章
        $medalData = $settlement->getMedal($data);
        //查看班级的排行榜
        $classTrueRate = $settlement->percentageOfClass($data);
        //发帖子的状态
        $clockStatus = $settlement->PostStatus($data);
        $userInfo['clock_status'] = $clockStatus == 0 ? 0 :1;
        if($userInfo['type'] == 5){
            return json($userInfo);
        }
        $userInfo['class_true_rate'] = &$classTrueRate;
        $userInfo['true_rate'] = !empty($trueRate)?$trueRate:'0%';
        $userInfo['medal_data'] = &$medalData;
        return json($userInfo);

    }

    /**
     *  发帖
     * @throws MissException
     * @throws SuccessMessage
     * @throws \app\lib\exception\ParameterException
     */
    public function sendPost()
    {
        $uid = Token::getCurrentUid();
        $validate = new PostValidate();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //去除左边空格，计算发帖内容长度
        $len = mb_strlen(ltrim($data['content']));
        if($len<=0 || $len>=50){
            return json(['clock_status'=>0,'errorCode'=>0,'msg'=>'输入内容为空或者已超出长度限制']);
        }
        //进行发帖，并记录发帖天数
        $res = Post::addPost($uid,$data);

       if(!$res){
           throw new MissException([
               'msg'=>'此班级此阶段此分组下你已经发过帖子了',
               'errorCode'=>50000
           ]);
       }

      throw new SuccessMessage();
    }

    /**
     * 重新来过接口
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     * @throws \app\lib\exception\TokenException
     * @throws \think\Exception
     */
    public function getAgainInfo()
    {
        //根据token获取用户最后一次学习的哪一阶段，哪一组信息，重新查询一遍详情进行返回
        $uid      = Token::getCurrentUid();
        $validate = new SettlementValidate();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //进行查看着以分组下所有单词的详情
        $groupWord = GroupWord::where('group',$data['group'])
            ->select();
        if ($groupWord->isEmpty()) {
            throw new MissException([
                'msg'       => '该分组下没有任何单词',
                'errorCode' => 50000
            ]);
        }

        try {
            $groupWord = $groupWord->toArray();
            //然后根据每个组的详情进行查询每个单词的详情
            $learned = new LearnedService();
            //查询此组对应的阶段和当前组的类型
            $notLearnedData = $learned->correspondingStage($groupWord);
            $notWordData = $learned->detail($notLearnedData);
            $notWordData = Collection::isCollection($uid, $notWordData);
            $notWordData = $learned->handleData($notWordData, 1);
            $notWordData['count'] = count($groupWord);
            return json($notWordData);
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }

    /**
     *  * 下一组单词
     * @param $LearnedData 当前阶段组信息
     * @return \think\response\Json
     * @throws MissException
     * @throws SuccessMessage
     * @throws \app\lib\exception\ParameterException
     */
    public function nextGroupInfo()
    {
        $uid   = Token::getCurrentUid();
        $validate = new SettlementValidate();
        $validate->goCheck();
        $LearnedData= $validate->getDataByRule(input('post.'));
        $LearnedData['now_stage'] = $LearnedData['stage'];
        $LearnedData['now_group'] = $LearnedData['group'];
        $learned = new LearnedService();
        //获取下一组的组id,并且是符合此班级权限的下一组ID
        $nextGroupID = $learned->nextGroupID($LearnedData);
        if (empty($nextGroupID)) {
            $arr = Stage::field('parent_id')->get($LearnedData['stage']);
            if($arr){
                $LearnedData['p_stage_id'] = $arr->parent_id;
            }
            $nextStageID = $learned->nextStageID($LearnedData);
            //如果当前阶段没有下一组了，去找下一阶段,第一组单词
            if (empty($nextStageID)) {
                throw new SuccessMessage([
                    'msg'       => '你太厉害了，所有阶段都已经通关了',
                    'errorCode' => 50000
                ]);
            }
            $nextGroupID = $learned->firstGroupID($nextStageID,$LearnedData['class_id']);
            if (empty($nextGroupID)) {
                throw new SuccessMessage([
                    'msg'       => '你已经学完所有单词了，因为下一阶段，没有任何分组！',
                    'errorCode' => 50000
                ]);
            }
            //进行查看着以分组下所有单词的详情
            $groupWord = GroupWord::where('group',$nextGroupID)
                ->select();
            if ($groupWord->isEmpty()) {
                throw new MissException([
                    'msg'       => '该分组下没有任何单词',
                    'errorCode' => 50000
                ]);
            }
            $groupWord = $groupWord->toArray();
            //然后根据每个组的详情进行查询每个单词的详情
            $learned = new LearnedService();
            //查询此组对应的阶段和当前组的类型
            $notLearnedData = $learned->correspondingStage($groupWord);
            $notWordData = $learned->detail($notLearnedData);
            $notWordData = Collection::isCollection($uid, $notWordData);
            $notWordData = $learned->handleData($notWordData, 1);
            $notWordData['count'] = count($groupWord);
            return json($notWordData);
        }

        //进行查看着以分组下所有单词的详情
        $groupWord = GroupWord::where('group',$nextGroupID)
            ->select();
        if ($groupWord->isEmpty()) {
            throw new MissException([
                'msg'       => '该分组下没有任何单词',
                'errorCode' => 50000
            ]);
        }
        $groupWord = $groupWord->toArray();
        //然后根据每个组的详情进行查询每个单词的详情
        $learned = new LearnedService();
        //查询此组对应的阶段和当前组的类型
        $notLearnedData = $learned->correspondingStage($groupWord);
        $notWordData = $learned->detail($notLearnedData);
        $notWordData = Collection::isCollection($uid, $notWordData);
        $notWordData = $learned->handleData($notWordData, 1);
        $notWordData['count'] = count($groupWord);
        return json($notWordData);
    }

}