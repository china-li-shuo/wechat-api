<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/5
 * Time: 17:59
 */


namespace app\article\controller\v1;


use app\article\service\Token;
use app\article\validate\AddRecord;
use app\article\model\Record as RecordModel;
use app\lib\exception\MissException;
use app\lib\exception\SuccessMessage;

class Record
{
    /**
     * 添加用户学习记录
     * @throws MissException
     * @throws SuccessMessage
     * @throws \app\lib\exception\ParameterException
     */
    public function addUserRecord()
    {
        $uid= Token::getCurrentUid();
        $validate = new AddRecord();
        $validate->goCheck();
        $data = $validate->getDataByRule(input('post.'));
        $data['user_id'] = $uid;
        $record = RecordModel::where([
            'article_id'=>$data['article_id'],
            'user_id'=>$data['user_id']])
            ->find();
        if(empty($record)){
            $res = RecordModel::create($data);
        }else{
            $res = $record->force()->save($data);
        }
        if($res){
            throw new SuccessMessage();
        }
        throw new MissException([
            'msg'=>'用户记录添加失败',
            'errorCode'=>4000
        ]);
    }
}