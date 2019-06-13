<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v6;


use app\api\model\User;
use app\api\model\Cls;
use app\api\model\Post;
use app\api\model\Unit;
use app\api\model\UserClass;
use app\api\service\Token;
use app\api\validate\IDMustBePositiveInt;
use app\api\validate\PagingParameter;
use app\lib\exception\MissException;
use app\lib\exception\TokenException;

class Home
{
    /**
     * 获取所有分校的信息
     * @throws MissException 错误异常
     */
    public function getBranchSchool()
    {
        $uid = Token::getCurrentUid();
        $userInfo = User::field('mobile_bind')->get($uid);
        //查询分校的信息
        $unit = Unit::getUnitData();
        if(!$unit){
            throw new MissException([
                'msg' => '分校信息查询失败',
                'errorCode'=>50000
            ]);
        }
        return json([
            'mobile_bind'=>$userInfo->mobile_bind,
            'data'=>$unit
        ]);
    }

    /**
     * 查询分校下各个班级下打卡人数
     * @return \think\response\Json
     * @throws MissException
     * @throws \app\lib\exception\ParameterException
     */
    public function getUnitClass($id)
    {
        (new IDMustBePositiveInt())->goCheck();
        //查询分校下的班级信息
        $unitClass = Cls::getUnitClass($id);
        if(!$unitClass){
            throw new MissException([
                'msg'=>'分校下班级信息查询失败',
                'errorCode'=>50000
            ]);
        }
        //查询今日发帖信息和发帖人对应的班级
        $post = Post::getPostByToday();
        $res = $this->postCountByClass($unitClass->toArray(), $post->toArray());
        return json($res);
    }


    /**
     * 获取全部今日打卡信息（分页）
     * @param int $page
     * @param int $size
     * @return json
     * @throws \app\lib\exception\ParameterException
     */
    public function getPunchCardToday($page = 1, $size = 20){
        (new PagingParameter())->goCheck();
        //查询今日发帖信息
        $pagingPosts = Post::getSummaryByPage($page,$size);
        if ($pagingPosts->isEmpty())
        {
            return json([
                'current_page' => $pagingPosts->currentPage(),
                'data' => []
            ]);
        }
        //获取今日打卡的班级另外三个头像
        $data = $this->getClassImg($pagingPosts->toArray());
        return json([
            'current_page' => $pagingPosts->currentPage(),
            'data' => $data
        ]);
    }

    /**
     * 排行榜
     * @return int
     */
    public function getRankingList($token = ''){
        $res = Token::verifyToken($token);
        if(!$res){
            throw new TokenException();
        }

        //进行查询排行榜信息，先从缓存中读取，两小时更新一次
        $rankingData = cache('home_ranking');
        if($rankingData){
            return json($rankingData);
        }
        //进行查询排行榜
        $pagingData = User::getSummaryByPage();
        $pagingData = $pagingData->toArray();
        foreach ($pagingData['data'] as $key=>&$val){
            $val['nick_name'] = urlDecodeNickName($val['nick_name']);
        }
        cache('home_ranking',$pagingData['data'],7200);
        return json($pagingData['data']);
    }

    /**
     * 获取班级下今日发帖人数
     * @param $data 班级信息
     * @param $arr  今日发帖班级信息
     */
    private function postCountByClass($data, $arr)
    {
        $i= 1;
        foreach ($data as $key=>&$val){
            $val['class_id'] = $val['id'];
            foreach ($arr as $k=>&$v){
                if($val['class_id']==$v['class_id']){
                    $val['post_count'] = $i++;
                }
                unset($val['id']);
                continue;
            }
            $i = 1;
            if(empty($val['post_count'])){
                $val['post_count'] = 0;
            }
        }
        return $data;
    }


    /**
     * 获取班级头像
     * @param $data
     * @return mixed
     */
    private function getClassImg($data)
    {
        $data = $data['data'];
        foreach ($data as $key=>&$val){
            $val['nick_name'] = urlDecodeNickName($val['nick_name']);
            $val['content'] = json_decode($val['content']);
            $classImgData = UserClass::getClassImgByID($val['class_id']);
            $classImgData = $classImgData->toArray();
            foreach ($classImgData as $k=>$v){
                if (empty($v['avatar_url'])){
                    unset($classImgData[$k]);
                    continue;
                }
            }
            $data[$key]['images'] = array_values($classImgData);
        }
        return $data;
    }
}