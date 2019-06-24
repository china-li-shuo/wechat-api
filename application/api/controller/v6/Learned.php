<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */
namespace app\api\controller\v6;


use app\api\model\English;
use app\api\model\User;
use app\api\service\Token;
use app\api\validate\ClassID;
use app\api\model\ErrorBook;
use app\api\model\LearnedChild;
use app\api\validate\Collection;
use app\api\validate\CollectionSentence;
use app\api\validate\LearnedHistory;
use app\api\validate\LearnedSentence;
use app\api\validate\ModuleCode;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;
use app\api\service\Learned as LearnedService;
use app\api\model\Collection as CollectionModel;
use app\api\model\LearnedHistory as LearnedHistoryModel;
use app\api\model\LearnedSentence as LearnedSentenceModel;
use app\api\model\CollectionSentence as CollectionSentenceModel;

class Learned
{
    /**
     * 开始学习
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function getList()
    {
        $uid = Token::getCurrentUid();
        (new ClassID()) -> goCheck();
        (new ModuleCode()) -> goCheck();
        //查询用户当前学习的阶段、组、组类型
        $userInfo = User::getByUid($uid)
            ->toArray();
        $userInfo['class_id'] = input('post.class_id/d');
        $userInfo['p_stage_id'] = input('post.module_code/d');//父阶段id;
        $stages = cpStages($userInfo); //符合班级权限的所有阶段并且是已切换的模块
        $user = $this->getLearnedInfo($userInfo,$stages);
        //进行查询用户学习记录表最后
        $learned = new LearnedService();
        if (empty($user['now_stage'])) {
            //如果用户没有学习记录
            //直接查询符合选择切换的父阶段下
            //合班级权限的第一个子阶段ID，第一组单词
            $notWordData = $learned->first($userInfo);
            return json($notWordData);
        }

        //继续上一次学习记录进行学习
        $notWordData = $learned->continueStudy($user);
        return json($notWordData);
    }

    private function getLearnedInfo($userInfo,$stages)
    {
        $word = [];
        foreach ($stages as $key=>$val){
            $arr = LearnedHistoryModel::where([
                'user_id'=>$userInfo['id'],
                'stage'=>$val['id']
            ])
                ->order('create_time desc')
                ->find();
            if(!empty($arr)){
                array_push($word,$arr->toArray());
            }
        }
        if(!empty($word)){
            // 取得列的列表
            foreach ($word as $key => $row) {
                $edition[$key] = $row['create_time'];
            }

            array_multisort($edition, SORT_DESC, $word);
            $userInfo['now_stage'] = empty($word[0]['stage']) ? '' : $word[0]['stage'];
            $userInfo['now_group'] = empty($word[0]['group']) ? '' : $word[0]['group'];
            return $userInfo;
        }

        $sentence = [];
        foreach ($stages as $key=>$val){
            $arr = LearnedSentenceModel::where([
                'user_id'=>$userInfo['id'],
                'stage'=>$val['id']
            ])
                ->order('create_time desc')
                ->find();
            if(!empty($arr)){
                array_push($sentence,$arr->toArray());
            }
        }
        if(!empty($sentence)){
            foreach ($sentence as $key => $row) {
                $edition[$key] = $row['create_time'];
            }

            array_multisort($edition, SORT_DESC, $sentence);
            $userInfo['now_stage'] = empty($sentence[0]['stage']) ? '' : $sentence[0]['stage'];
            $userInfo['now_group'] = empty($sentence[0]['group']) ? '' : $sentence[0]['group'];
            return $userInfo;
        }
    }

    /**
     * 点击下一个，记录用户学习记录
     * 传递参数（token,type,class_id,group,stage,word_id,useropt）
     * 根据用户选项判断用户答案是否正确
     * 如果用户答错，则把错误信息写入数据库
     * 然后把用户答题活动记录写入数据库，如果已存在这条记录进行修改，否则添加
     * @throws MissException
     * @throws SuccessMessage
     * @throws \app\lib\exception\ParameterException
     */
    public function clickNext()
    {
        $uid = Token::getCurrentUid();
        $validate = new LearnedHistory();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //根据分组进行查询答案是否正确
        $learned = new LearnedService();
        $answerResult = $learned->answerResult($data);
        //如果答题正确，判断错题本有没有此条记录，如果有则删除
        if ($answerResult == 1) {
            ErrorBook::deleteErrorBook($uid, $data);
            //进行查询用户记录表这个单词是否已经有过，没有则子表加一，否则子表已掌握记录不变
            LearnedChild::addLearnedChild($uid, $data);
        }

        if ($answerResult == 0) {
            ErrorBook::addErrorBook($uid, $data);
            //进行查询用户记录表这个单词是否有过，没有则不删，有则已掌握记录减一
            LearnedChild::deleteLearnedChild($uid, $data);
        }

        //进行记录用户学习记录
        $res = LearnedHistoryModel::addUserHistory($uid, $data, $answerResult);

        if (!$res) {
            throw new MissException([
                'msg'       => '用户答题记录失败',
                'errorCode' => 50000
            ]);
        }
        throw new SuccessMessage();

    }

    /**
     * 长难句点击下一个
     * @throws MissException
     * @throws SuccessMessage
     * @throws \app\lib\exception\ParameterException
     */
    public function clickSentence()
    {
        $uid = Token::getCurrentUid();
        $validate = new LearnedSentence();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $res = LearnedSentenceModel::addSentence($uid, $data);
        if(empty($res)){
            throw new MissException([
                'msg'=>'操作失败',
                'errorCode'=>50000
            ]);
        }
        throw new SuccessMessage();
    }

    /**
     * 收藏
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function collection()
    {
        $uid      = Token::getCurrentUid();
        $validate = new Collection();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //is_collection  1  为收藏  2为未收藏
        if ($data['is_collection'] == 2) {
            $res = CollectionModel::deleteCollection($uid, $data);
            if (!$res) {
                throw new MissException([
                    'msg'       => '你已经取消收藏该单词了呀',
                    'errorCode' => 50000
                ]);
            }
            return json(['msg' => '取消收藏成功', 'code' => 200]);
        }

        $res = CollectionModel::addCollection($uid, $data);

        if (!$res) {
            throw new MissException([
                'msg'       => '你已经收藏过该单词了呀',
                'errorCode' => 50000
            ]);
        }
        return json(['msg' => '收藏成功', 'code' => 200]);
    }

    /**
     * 收藏
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function sentenceCollection()
    {
        $uid      = Token::getCurrentUid();
        $validate = new CollectionSentence();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        //is_collection  1  为收藏  2为未收藏
        if ($data['is_collection'] == 2) {
            $res = CollectionSentenceModel::deleteCollection($uid, $data);
            if (!$res) {
                throw new MissException([
                    'msg'       => '你已经取消收藏该句子',
                    'errorCode' => 50000
                ]);
            }
            return json(['msg' => '取消收藏成功', 'code' => 200]);
        }

        $res = CollectionSentenceModel::addCollection($uid, $data);

        if (!$res) {
            throw new MissException([
                'msg'       => '你已经取消收藏该句子',
                'errorCode' => 50000
            ]);
        }
        return json(['msg' => '收藏成功', 'code' => 200]);
    }

    /**
     * 单词搜索
     * @throws MissException
     */
    public function wordSearch()
    {
        Token::getCurrentUid();
        $english_word = input('post.english_word');
        if(empty($english_word)){
            throw new MissException([
                'msg'=>'english_word不能为空',
                'errorCode'=>50000
            ]);
        }
        $data = English::where('english_word',$english_word)->find();
        if(empty($data)){
            throw new MissException([
                'msg'=>'没有搜索到此单词的信息',
                'errorCode'=>50001
            ]);
        }
        $us_audio = config('setting.audio_prefix');
        //根据类型进行不同的格式转换
        $data['chinese_word']  = explode('@', $data['chinese_word']);
        $data['answer']        = explode(',', $data['answer']);
        $data['options']       = json_decode($data['options'], true);
        $data['sentence']      = json_decode($data['sentence'], true);
        $data['us_audio']      = $us_audio .$data['us_audio'];
        return json($data);
    }
}
