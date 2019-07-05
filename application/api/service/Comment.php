<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/28
 * Time: 14:02
 */


namespace app\api\service;


use app\api\model\Zan;
use app\api\model\Comment as CommentModel;
use app\lib\enum\ZanStatusEnum;
use app\lib\exception\MissException;

class Comment
{
    /**
     * @throws MissException
     */
    public function getCommentInfo($data,$uid)
    {
        if(!$data){
            throw new MissException();
        }
        foreach ($data as &$val){
            $val['nick_name'] = urlDecodeNickName($val['nick_name']);
            $val['content'] = json_decode($val['content']);
            $val['zan_people'] = Zan::where(['post_id'=>$val['id'], 'status'=>1])
                ->field('nick_name')
                ->select();
            $val['zan_count'] = count($val['zan_people']);
            $status = Zan::where(['post_id'=>$val['id'], 'user_id'=>$uid])
                ->field('status')
                ->find();
            $val['zan_status'] = empty($status) ? ZanStatusEnum::CANCEL :  $status['status'];
            $val['comment_count'] = CommentModel::where('post_id',$val['id'])->count();
            if(!empty($val['comment'])){
                foreach ($val['comment'] as &$v) {
                    $v['content'] = json_decode($v['content']);
                }
            }
        }
        return $data;
    }
}