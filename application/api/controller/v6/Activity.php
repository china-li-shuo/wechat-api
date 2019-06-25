<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */
namespace app\api\controller\v6;


use app\api\model\CollectionSentence;
use app\api\model\LearnedSentence;
use app\api\validate\PagingParameter;
use think\Db;
use app\api\service\Token;
use app\api\model\ErrorBook;
use app\api\service\Learned;
use app\api\model\Collection;
use app\api\model\LearnedHistory;
use app\lib\exception\MissException;
use app\api\service\Activity as ActivityService;
use app\api\validate\ErrorBook as ErrorBookValidate;

class Activity
{
    protected $activity;
    protected $learned;
    public function __construct()
    {
        $this->activity = new ActivityService();
        $this->learned = new Learned();
    }

    /**
     * 已学习首页(筛选)
     * @throws MissException
     */
    public function alreadyStudied()
    {
        //根据token获取用户学习所有阶段,每个阶段下所有组
        $uid = Token::getCurrentUid();
        $historyData = $this->activity->learnedInfo($uid);
        if (empty($historyData)) {
            throw new MissException([
                'msg'       => '没有查询到你的学习记录',
                'errorCode' => 50000
            ]);
        }
        return json($historyData);
    }

    /**
     * 错题本首页（筛选）
     * @throws MissException
     */
    public function errorBook()
    {
        $uid = Token::getCurrentUid();
        $data = $this->activity->errorInfo($uid);
        if (empty($data)) {
            throw new MissException([
                'msg'       => '没有查询到你的错题记录',
                'errorCode' => 50000
            ]);
        }

        return json($data);
    }

    /**
     * 已收藏首页（筛选）
     * @throws MissException
     */
    public function collection()
    {
        $uid = Token::getCurrentUid();
        $data = $this->activity->collectionInfo($uid);
        if (empty($data)) {
            throw new MissException([
                'msg'       => '没有查询到你的收藏记录',
                'errorCode' => 50000
            ]);
        }

        return json($data);
    }


    /**
     * 已学习详情,根据条件进行筛选指定阶段和组的单词详情
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function alreadyDetail()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $arr = input('post.');
        if (empty($arr['stage']) || empty($arr['group'])) {
            $arr = LearnedHistory::where('user_id',$uid)
                ->order('create_time desc')
                ->field('stage,group')
                ->find();
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->getStageGroup($uid, $arr);
        if (empty($data)) {
            throw new MissException([
                'msg'       => '你还未学习过任何单词',
                'errorCode' => 50000
            ]);
        }
        return json($data);
    }

    /**
     * 错题本详情
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function errorDetail()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $arr = input('post.');
        if (empty($arr['stage']) || empty($arr['group'])) {
          $arr = Db::table('yx_error_book')
              ->where('user_id',$uid)
              ->order('create_time desc')
              ->field('stage,group')
              ->find();
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->errorStageGroup($uid, $arr);
        return json($data);
    }

    /**
     * 错题本 移除
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function errorRemove()
    {
        $uid      = Token::getCurrentTokenVar('uid');
        $validate = new ErrorBookValidate();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $res  = ErrorBook::deleteErrorBook($uid, $data);

        if (!$res) {
            throw new MissException([
                'msg'       => '错题已经移除,请刷新重试',
                'errorCode' => 50000
            ]);
        }

        return json(['msg' => '移除成功', 'code' => 200]);
    }

    /**
     * 收藏详情
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function collectionDetail()
    {
        $uid = Token::getCurrentTokenVar('uid');
        $arr = input('post.');
        if (empty($arr['stage']) || empty($arr['group'])) {
            $arr = Db::table('yx_collection')
                ->where('user_id',$uid)
                ->order('create_time desc')
                ->field('stage,group')
                ->find();
        }
        if (empty($arr)){
            throw new MissException([
                'msg'=>'你还没有收藏过任何单词',
                'errorCode' => 50000
            ]);
        }
        //根据用户id和阶段id查出此用户所有的
        $data = $this->collectStageGroup($uid, $arr);
        return json($data);
    }


    /**
     * 筛选此用户已学习信息
     * @param $uid
     * @param $arr
     * @return mixed
     * @throws MissException
     */
    private function getStageGroup($uid, $arr)
    {
        try {
            $data  = Db::table('yx_learned_history')
                ->where('user_id', $uid)
                ->where('stage', $arr['stage'])
                ->where('group', $arr['group'])
                ->field('id,stage,group,word_id as wid,create_time')
                ->order('create_time desc')
                ->select();
            $groupData = Db::table(YX_QUESTION . 'group')
                ->alias('g')
                ->join('yx_question.yx_stage s','g.stage_id = s.id')
                ->field('g.group_name,s.stage_name')
                ->where('g.id', $arr['group'])
                ->find();
            $new_arr['data'] = [
                'stage_name'  => $groupData['stage_name'],
                'group_name'  => $groupData['group_name'],
                'create_time' => date('Y-m-d', $data[0]['create_time'])
            ];

            //查询此组对应的阶段和当前组的类型
            $notLearnedData =  $this->learned->correspondingStage($data);
            $notWordData =  $this->learned->detail($notLearnedData);
            $notWordData = Collection::isCollection($uid, $notWordData);
            $new_arr['word'] =  $this->learned->handleData($notWordData, 1);
            return $new_arr;
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }

    }


    /**
     * 筛选此用户收藏信息
     * @param $uid
     * @param $arr
     * @return mixed
     * @throws MissException
     */
    private function collectStageGroup($uid, $arr)
    {
        try {
            $data = Db::table('yx_collection')
                ->where('user_id', $uid)
                ->where('stage', $arr['stage'])
                ->where('group', $arr['group'])
                ->field('id,stage,group,word_id as wid,create_time')
                ->order('create_time desc')
                ->select();
            $groupData = Db::table(YX_QUESTION . 'group')
                ->alias('g')
                ->join('yx_question.yx_stage s','g.stage_id = s.id')
                ->field('g.group_name,s.stage_name')
                ->where('g.id', $arr['group'])
                ->find();
            $new_arr['data'] = [
                'stage_name'  => $groupData['stage_name'],
                'group_name'  => $groupData['group_name'],
                'create_time' => date('Y-m-d', $data[0]['create_time'])
            ];
            //查询此组对应的阶段和当前组的类型
            $notLearnedData =  $this->learned->correspondingStage($data);
            $notWordData =  $this->learned->detail($notLearnedData);
            $notWordData = Collection::isCollection($uid, $notWordData);
            $new_arr['word'] =  $this->learned->handleData($notWordData, 1);
            return $new_arr;
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }


    /**
     * 错题本详情
     * @param $uid
     * @param $arr
     * @return mixed
     * @throws MissException
     */
    private function errorStageGroup($uid, $arr)
    {
        try {
            $data = Db::table('yx_error_book')
                ->where('user_id', $uid)
                ->where('stage', $arr['stage'])
                ->where('group', $arr['group'])
                ->field('id,stage,group,word_id as wid,create_time')
                ->order('create_time desc')
                ->select();
            $groupData = Db::table(YX_QUESTION . 'group')
                ->alias('g')
                ->join('yx_question.yx_stage s','g.stage_id = s.id')
                ->field('g.group_name,s.stage_name')
                ->where('g.id', $arr['group'])
                ->find();
            $new_arr['data'] = [
                'stage_name'  => $groupData['stage_name'],
                'group_name'  => $groupData['group_name'],
                'create_time' => date('Y-m-d', $data[0]['create_time'])
            ];
            //查询此组对应的阶段和当前组的类型
            $notLearnedData =  $this->learned->correspondingStage($data);
            $notWordData =  $this->learned->detail($notLearnedData);
            $notWordData = Collection::isCollection($uid, $notWordData);
            $new_arr['word'] =  $this->learned->handleData($notWordData, 1);
            return $new_arr;
        } catch (\Exception $e) {
            throw new MissException([
                'msg'       => $e->getMessage(),
                'errorCode' => 50000
            ]);
        }
    }

    /**
     * 已学长难句
     */
    public function alreadySentence($page = 1, $size = 20)
    {
        $uid = Token::getCurrentUid();
        (new PagingParameter())->goCheck();
        //查询已学习长难句
        $pagingSentences = LearnedSentence::getSummaryByUid($uid, $page, $size);
        if ($pagingSentences->isEmpty())
        {
            return json([
                'current_page' => $pagingSentences->currentPage(),
                'data' => []
            ]);
        }
        $pagingSentence = $pagingSentences->toArray();
        //进行处理长难句数据格式
        $data = $this->Handle($pagingSentence);
        return json([
            'current_page' => $pagingSentences->currentPage(),
            'data' => $data
        ]);
    }

    /**
     * 长难句已收藏
     */
    public function collectionSentence($page = 1, $size = 20)
    {
        $uid = Token::getCurrentUid();
        (new PagingParameter())->goCheck();
        //查询已学习长难句
        $pagingSentences = CollectionSentence::getSummaryByUid($uid, $page, $size);
        if ($pagingSentences->isEmpty())
        {
            return json([
                'current_page' => $pagingSentences->currentPage(),
                'data' => []
            ]);
        }
        $pagingSentence = $pagingSentences->toArray();
        //进行处理长难句数据格式
        $data = $this->Handle($pagingSentence);
        return json([
            'current_page' => $pagingSentences->currentPage(),
            'data' => $data
        ]);
    }

    /**
     * 处理长难句的数据
     */
    private function Handle($pagingSentence)
    {
        //单词表已的音频路径
        $us_audio = config('setting.audio_prefix');

        foreach ($pagingSentence['data'] as $key=>&$val){
            $val['sentence_info']['word_parsing']  = json_decode($val['sentence_info']['word_parsing'], true);
            $val['sentence_info']['sentence_splitting'] = json_decode($val['sentence_info']['sentence_splitting'], true);
            foreach ($val['sentence_info']['word_parsing'] as $k=>&$v){
                $v['us_audio'] = $us_audio . $v['us_audio'];
            }
        }
        return $pagingSentence['data'];
    }
}