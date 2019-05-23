<?php
/**
 * Created by 空城旧梦狂啸当歌.
 * 微信公号：Sweet的笑
 * User: 李硕
 * Date: 2019/5/20
 * Time: 16:15
 * 代码是生存，不是生活
 */

namespace app\api\controller\v4;
use app\api\model\ErrorBook;
use app\api\service\Token;
use app\api\validate\PagingParameter;

class Book
{
    /**
     * 根据用户id分页获取用户错题本信息列表（简要信息）
     * @param int $page
     * @param int $size
     * @return array
     * @throws \app\lib\exception\ParameterException
     */
    public function getErrorBookByUser($page = 1, $size = 15)
    {
        (new PagingParameter())->goCheck();
        $uid = Token::getCurrentUid();
        //进行查询错题本信息普通词汇
        $pagingErrorBooks = ErrorBook::getSummaryByUser($uid, $page, $size,1);

        if ($pagingErrorBooks->isEmpty())
        {
            //如果普通类型为空，则进行查询特殊类型
            $pagingErrorBooks = ErrorBook::getSummaryByUser($uid, 1, $size,4);
            $data = $pagingErrorBooks->hidden(['user_opt','create_time'])
                ->toArray();
            $data = $this->conversionFormat($data['data'],1,'english_s');
            return json([
                'current_page' => $pagingErrorBooks->currentPage(),
                'data' => $data
            ]);
        }
        $data = $pagingErrorBooks->hidden(['user_opt','create_time'])
            ->toArray();

        $data = $this->conversionFormat($data['data'],1,'english');

        return json([
            'current_page' => $pagingErrorBooks->currentPage(),
            'data' => $data
        ]);

    }

    /**
     * 把json 数据进行格式转换
     * @param $notWordData
     * @param $currentNumber
     * @return 转换后的数据
     */
    private function conversionFormat($notWordData, $currentNumber, $str = 'english')
    {

        $us_audio = config('setting.audio_prefix');
        foreach ($notWordData as $key => $val) {
            if($str == 'english'){
                $notWordData[$key][$str]['chinese_word']  = explode('@', $val[$str]['chinese_word']);
            }
            $notWordData[$key][$str]['options']       = json_decode( $val[$str]['options'], true);
            $notWordData[$key][$str]['sentence']      = json_decode( $val[$str]['sentence'], true);
            $notWordData[$key][$str]['currentNumber'] = $currentNumber + $key;
            $notWordData[$key][$str]['us_audio']      = $us_audio .  $val[$str]['us_audio'];
        }
        return $notWordData;
    }
}