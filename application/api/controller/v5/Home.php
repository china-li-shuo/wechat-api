<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公号：空城旧梦狂啸狂啸当歌
 * Date: 2019/6/3
 * Time: 11:57
 */

namespace app\api\controller\v5;


use app\api\dao\Post;
use app\api\dao\Unit;
use app\api\dao\User;
use app\api\service\Token;
use app\api\validate\IDMustBePositiveInt;
use app\api\validate\PagingParameter;
use app\lib\exception\MissException;
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
    public function getUnitClass($id){
        (new IDMustBePositiveInt())->goCheck();
        $data = Db::name('unit_class')
            ->alias('uc')
            ->join('unit u','uc.unid=u.unid')
            ->join('class c','uc.class_id=c.id')
            ->field('u.unid,u.unitname,uc.class_id,c.class_name')
            ->order('c.sort')
            ->where('uc.unid',$id)
            ->select();
        if(empty($data)){
            throw new MissException([
                'msg'=>'分校信息查询失败',
                'errorCode'=>50000
            ]);
        }
        $arr = Post::selectPost();

        $i= 1;
        foreach ($data as $key=>$val){
            foreach ($arr as $k=>$v){
                if($val['class_id']==$v['class_id']){
                    $data[$key]['post_count'] = $i++;
                }
                continue;
            }
            $i = 1;
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
        $arr = $pagingPosts->toArray();
        $data = $arr['data'];
        try{
            foreach ($data as $key=>$val){
                $data[$key]['nick_name'] = urlDecodeNickName($val['nick_name']);
                $data[$key]['content'] = json_decode($val['content']);
                $classImgData = Db::table('yx_user_class')
                    ->alias('uc')
                    ->join('yx_user u','u.id=uc.user_id')
                    ->field('u.avatar_url')
                    ->limit(5)
                    ->where('uc.class_id',$val['class_id'])
                    ->where('uc.status',1)
                    ->select();
                foreach ($classImgData as $k=>$v){
                    if (empty($v['avatar_url'])){
                        unset($classImgData[$k]);
                        continue;
                    }
                }
                $data[$key]['images'] = array_values($classImgData);
            }
            return json([
                'current_page' => $pagingPosts->currentPage(),
                'data' => $data
            ]);
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
        foreach ($rankingData as $key=>$val){
            $rankingData[$key]['nick_name'] = urlDecodeNickName($val['nick_name']);
        }
        cache('home_ranking',$rankingData,7200);
        return json($rankingData);
    }

}