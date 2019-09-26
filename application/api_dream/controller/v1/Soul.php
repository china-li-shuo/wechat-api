<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/9/26
 * Time: 15:55
 */


namespace app\api_dream\controller\v1;

use app\api_dream\model\Soul as SoulModel;
use  app\lib\validate\PagingParameter;

class Soul
{

    /**
     * 查询毒鸡汤
     */
    public function getSouls($page = 1, $size = 20)
    {
        (new PagingParameter())->goCheck();
        $pagingSouls = SoulModel::getSummaryByPage($page, $size);
        if ($pagingSouls->isEmpty()) {
            return json([
                'current_page' => $pagingSouls->currentPage(),
                'data'         => []
            ]);
        }
        $articles = $pagingSouls->toArray();
        return json([
            'current_page' => $pagingSouls->currentPage(),
            'data'         => $articles['data']
        ]);
    }
}