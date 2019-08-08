<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/8
 * Time: 16:17
 */


namespace app\article\controller\v1;

use app\article\service\Token;
use app\article\model\Record;
use app\article\validate\PagingParameter;
use app\article\service\Record as RecordService;

class Personal
{
    /**
     * 用户学习记录
     * d82460007fb7563923d3e340b779ef94
     * @param int $page
     * @param int $size
     * @return \think\response\Json
     * @throws \app\lib\exception\ParameterException
     */
    public function getLearningRecords($page = 1, $size = 5)
    {
        (new PagingParameter()) -> goCheck();
        $uid = Token::getCurrentUid();
        $pagingRecords = Record::getSummaryByUser($uid, $page, $size);
        if ($pagingRecords->isEmpty())
        {
            return json([
                'current_page' => $pagingRecords->currentPage(),
                'data' => []
            ]);
        }
        $records = $pagingRecords->hidden(['game_time'])
            ->toArray();
        $recordService = new RecordService();
        $recordData = $recordService->getFinalRecord($records['data']);
        return json([
            'current_page' => $pagingRecords->currentPage(),
            'data' => $recordData
        ]);
    }
}