<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/5
 * Time: 17:59
 */


namespace app\article\controller\v1;

use app\article\model\User;
use app\article\service\Token;
use app\article\validate\AddRecord;
use app\article\service\Record as RecordService;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;
use Redis;

class Record
{
    private $redis;
    private $leaderBoard;//用来存放排行榜的key

    /**
     * Record constructor.  构造方法
     * @param array $redis  redis对象
     */
    public function __construct($redis = [])
    {
        if($redis){
            $this->redis = $redis;
        }else{
            $IP = $_SERVER["SERVER_ADDR"];
            $this->redis =new Redis();
            $this->redis->connect($IP,6379);
            if($IP != '127.0.0.1'){
                //58.87.73.141 线上服务器redis密码验证
                $this->redis->auth('success_redis+_)(1982');
            }
        }
    }

    /**
     * 获取指定文章的排行榜单
     * 实现思路：
     * 1、根据文章id 找出对应的榜单
     * 2、接收用户最新的连连看单词用时秒数，查到该用户的昵称信息
     * 3、先在数据库更新用户此篇文章连连看的用时秒数，以及长难句选项的答案
     * 4、然后更新redis中榜单的用时秒数，node:用户的昵称，count:用户游戏用时
     * 5、查找榜单前number:多少名的情况，查找用户在此榜单的排名情况+1
     * @param string $id    文章id
     * @throws MissException
     * @throws SuccessMessage
     * @throws \app\lib\exception\ParameterException
     */
    public function getArticleLeaderBoard()
    {
        $uid = Token::getCurrentUid();
        $validate = new AddRecord();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $data['user_id'] = $uid;
        //查找对应文章下的排行榜
        $this->leaderBoard = 'leaderBoard' . $data['article_id'];
        $record = new RecordService();
        $res = $record->addRecord($data);
        if(empty($res)){
            throw new MissException([
                'msg'=>'用户记录添加失败',
                'errorCode'=>4000
            ]);
        }
        //进行更新排行榜
        $leaderBoard = $this->updateTheList($data);
        if(empty($leaderBoard)){
            throw new MissException([
                'msg'=>'排行榜信息获取失败',
                'errorCode'=>4001
            ]);
        }
        return json($leaderBoard);
    }

    /**
     *进行更新排行榜
     */
    private function updateTheList($data)
    {
        //把这个用户游戏时间添加到这篇文章的排行榜中
        $this->addLeaderboard($data['user_id'],$data['game_time']);
        //进行查询用时最少的前100名榜单
        $leader = $this->getLeadboard(100,false,true);
        $userInfo = [];
        foreach ($leader as $key=>$val){
            $user = User::field('nick_name,avatar_url')
                ->get($key);
            if($user){
                $user->game_time = $val;
                array_push($userInfo,$user->toArray());
            }
        }
        //查询自己所在的排名,根据用时的从小到大排序
        $rankNum = $this->getNodeRank($data['user_id'],false);
        $user = User::get($data['user_id']);
        //返回信息
        $arr['leaderBoard'] = $userInfo;
        $arr['mine'] = ['nick_name'=>$user->nick_name,'avatar_url'=>$user->avatar_url,'game_time'=>$data['game_time'],'ranking'=>$rankNum+1];
        return $arr;
    }

    /**
     * 获取当前的排行榜的key名
     * @return string
     */
    public function getLeaderboard()
    {
        return $this->leaderBoard;
    }

    /**
     * 将对应的值填入到排行榜中
     * @param  $node 这里是用户的id
     * @param number $count 对应的游戏用时秒数,默认值为1
     * @return Long 1 if the element is added. 0 otherwise.
     */
    public function addLeaderboard($node, $count = 1)
    {
        //增加一个或多个元素，如果该元素已经存在，更新它的count值
        //但是 覆盖之后返回0
        return $this->redis->zAdd($this->leaderBoard, $count, $node);
    }
    /**
     * 给出对应的排行榜
     * @param int $number 需要给出排行榜数目
     * @param bool $asc 排序顺序 true为按照高分为第0
     * @param bool $withscores 是否需要用时秒数
     * @param callback $callback 用于处理排行榜的回调函数
     * @return [] 对应排行榜
     */
    public function getLeadboard($number, $asc = true, $withscores = false,$callback = null)
    {
        if ($asc) {
            $nowLeadboard =  $this->redis->zRevRange($this->leaderBoard, 0, $number -1, $withscores);//按照高分数顺序排行;
        } else {
            $nowLeadboard =  $this->redis->zRange($this->leaderBoard, 0, $number -1, $withscores);//按照低分数顺序排行;
        }


        if ($callback) {
            //使用回调处理
            return $callback($nowLeadboard);
        } else {
            return $nowLeadboard;
        }
    }
    /**
     * 获取给定节点的排名
     * @param string $node 对应的节点的key名
     * @param string $asc 是否按照分数大小正序排名, true的情况下分数越大,排名越高
     * @return 用户排名,根据$asc排序,true的话,第一高分为0,false的话第一低分为0
     */
    public function getNodeRank($node, $asc = true)
    {
        if ($asc) {
            //zRevRank 分数最高的排行为0,所以需要加1位
            return $this->redis->zRevRank($this->leaderBoard, $node);
        } else {
            return $this->redis->zRank($this->leaderBoard, $node);
        }
    }
}