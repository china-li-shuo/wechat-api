<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/8
 * Time: 16:17
 */


namespace app\article\controller\v1;

use app\article\model\Collect;
use app\article\service\Token;
use app\article\model\Record;
use app\article\validate\PagingParameter;
use app\article\service\Record as RecordService;

class Personal
{
    /**
     * 用户学习记录
     * @param int $page 当前页码默认1
     * @param int $size 每页显示条数默认5
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

    /**
     * 获取我收藏的文章
     * @param int $page 当前页码默认1
     * @param int $size 没有显示条数默认20
     * @return \think\response\Json
     * @throws \app\lib\exception\ParameterException
     */
    public function getCollectedArticles($page = 1, $size = 20)
    {
        //d82460007fb7563923d3e340b779ef94
        (new PagingParameter()) -> goCheck();
        $uid = Token::getCurrentUid();
        $pagingCollect = Collect::getSummaryByUser($uid, $page, $size);
        if ($pagingCollect->isEmpty())
        {
            return json([
                'current_page' => $pagingCollect->currentPage(),
                'data' => []
            ]);
        }
        $collect = $pagingCollect->hidden([
            'id','user_id','status','create_time','update_time'
            ])->toArray();

        return json([
            'current_page' => $pagingCollect->currentPage(),
            'data' => $collect['data']
        ]);
    }
}