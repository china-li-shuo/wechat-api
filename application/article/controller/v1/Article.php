<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/1
 * Time: 10:49
 */


namespace app\article\controller\v1;


use app\article\model\Collect;
use app\article\model\EnglishArticle;
use app\article\service\Token;
use app\article\validate\IDMustBePositiveInt;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;

class Article
{
    /**
     *  今日推送文章
     * @throws MissException
     */
    public function getDailyPush()
    {
        $push_date = date('Y-m-d');
        //获取推送日期的文章
        $articleInfo = EnglishArticle::getPushDateArticle($push_date);
        if(empty($articleInfo)){
            throw new MissException([
                'error_code'=>3000,
                'msg'=>'今日没有推送文章',
                'code'=>404
            ]);
        }
        $articleInfo = $articleInfo->hidden(['create_time','update_time'])
            ->toArray();
        $articleInfo['audio_url'] = config('setting.audio_prefix') . $articleInfo['audio_url']  ;
        $articleInfo['content'] = json_decode($articleInfo['content'], true);
        $articleInfo['words'] = json_decode($articleInfo['words'], true);
        foreach ($articleInfo['words'] as &$val){
            $val['chinese_word'] = explode('@',$val['chinese_word']);
        }
        return json($articleInfo);
    }

    /**
     * 文章的收藏
     * @param string $id   文章id
     * @throws SuccessMessage   返回信息
     * @throws \app\lib\exception\ParameterException
     */
    public function addCollect($id = '')
    {
        (new IDMustBePositiveInt())->goCheck();
        $uid = Token::getCurrentUid();
        $collect = Collect::where(['user_id'=>$uid,'article_id'=>$id])
            ->field('status')->find();
        if(empty($collect)){
            $res = Collect::create(['user_id'=>$uid,'article_id'=>$id,'status'=>1]);
            if($res){
                throw new SuccessMessage();
            }
        }
        if($collect->status == 1){
            throw new SuccessMessage(['msg'=>'你已经收藏过了','error_code'=>2000]);
        }
         $collect->status = 1;
         $res = $collect->save();
         if($res){
             throw new SuccessMessage();
         }
    }

    /**
     *  取消收藏
     * @param string $id  文章id
     * @throws SuccessMessage
     * @throws \app\lib\exception\ParameterException
     */
    public function cancelCollect($id = '')
    {
        (new IDMustBePositiveInt())->goCheck();
        $uid = Token::getCurrentUid();
        $collect = Collect::where(['user_id'=>$uid,'article_id'=>$id])
            ->field('status')->find();
        if(empty($collect)){
            throw new SuccessMessage(['msg'=>'你还没进行收藏','error_code'=>2001]);
        }
        if($collect->status == 1){
            $collect->status = 0;
            $res = $collect->save();
            if($res){
                throw new SuccessMessage();
            }
        }
        throw new SuccessMessage();
    }
}