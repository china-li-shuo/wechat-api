<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/4/10
 * Time: 18:44
 */

namespace app\api\controller\v3;


use app\api\model\Post;
use app\api\model\Unit;
use app\api\model\UnitClass;
use app\api\model\User;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\MissException;
use app\api\service\Token;
use app\lib\exception\TokenException;
use think\Db;

class Home
{
    /**
     * 获取所有分校的信息
     * @return \think\response\Json
     */
    public function getBranchSchool(){
        $uid = Token::getCurrentTokenVar('uid');
        $userInfo = User::field('mobile_bind')->get($uid);
        //查询分校的信息
        $data = Unit::selectUnitData();
        return json([
            'mobile_bind'=>$userInfo->mobile_bind,
            'data'=>$data
        ]);
    }

    /**
     * 查询分校下各个班级下打卡人数
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function getUnitClass(){
        $validate = new IDMustBePositiveInt();
        $validate->goCheck();
        $arr = $validate->getDataByRule(input('post.'));
        $data = UnitClass::selectUnidClass($arr['id']);
        $arr = Post::selectPost();

        $i= 1;
        foreach ($data as $key=>$val){
            foreach ($arr as $k=>$v){
                if($val['class_id']==$v['class_id']){
                     $data[$key]['post_count'] = $i++;
                     break;
                }
            }
            if(empty($data[$key]['post_count'])){
                $data[$key]['post_count'] = 0;
            }
        }
        if(empty($data)){
            throw new MissException([
                'msg'=>'此分校暂无任何班级信息',
                'errorCode'=>50000
            ]);
        }

        return json($data);
    }

    /**
     * 今日打卡
     */
    public function getPunchCardToday(){
        //查询今日最新发帖的20个人返回
        $data = Post::limitPost(20);
        //每个用共打卡天数，和这个班级下找出另外三个班级成员头像作为展示
        if(empty($data)){
            throw new MissException([
                'msg'=>'今日暂时还未有人打卡，快来抢沙发呀！',
                'errorCode'=>0
            ]);
        }
        try{
            foreach ($data as $key=>$val){
                $data[$key]['nick_name'] = urlDecodeNickName($val['nick_name']);
                $data[$key]['content'] = json_decode($val['content']);
                $classImgData = Db::table('yx_user_class')
                    ->alias('uc')
                    ->join('yx_user u','u.id=uc.user_id')
                    ->field('u.avatar_url')
                    ->limit(5)
                    ->select();
                foreach ($classImgData as $k=>$v){
                    if (empty($v['avatar_url'])){
                        unset($classImgData[$k]);
                        continue;
                    }
                }
                $data[$key]['images'] = array_values($classImgData);
            }
            return json($data);
        }catch (\Exception $e){
            throw new MissException([
                'msg'=>$e->getMessage(),
                'errorCode'=>50000
            ]);
        }
    }

    /**
     * 排行榜
     * @return int
     */
    public function getRankingList($token = ''){
        if (!$token) {
            throw new MissException([
                'msg'       => 'token不允许为空',
                'errorCode' => 60000
            ]);
        }
        $res = Token::verifyToken($token);
        if(!$res){
            throw new TokenException();
        }

        //进行查询排行榜信息，先从缓存中读取，两小时更新一次
        $rankingData = cache('home_ranking');

        if(!empty($rankingData)){
            return json($rankingData);
        }

        //进行查询排行榜
        $rankingData = User::getRankingData();
        cache('home_ranking',$rankingData,7200);
        return json($rankingData);
    }

}