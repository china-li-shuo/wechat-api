<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/9/27
 * Time: 13:50
 */


namespace app\api_dream\controller\v1;

use app\api_dream\model\Classic as ClassicModel;
use app\api_dream\model\ClassicFav;
use app\lib\service\Token;
use app\lib\validate\IndexMustBePositiveInt;

//期刊
class Classic
{
    /**
     * 获取最新一期
     */
    public function getLatest()
    {
        $classic = ClassicModel::getLatest();
        if($classic->isEmpty()){
            return json([]);
        }
        $classic['fav_nums'] = ClassicFav::where('classic_id',$classic->id)
            ->count();
       return json($classic);
    }

    /**
     * 获取当前一期的下一期
     */
    public function next($index = '')
    {
        (new IndexMustBePositiveInt())->goCheck();
        $classic = ClassicModel::where('index','=',$index+1)
            ->find();
        if(empty($classic)){
            return json([]);
        }
        $classic['fav_nums'] = ClassicFav::where('classic_id',$classic->id)
            ->count();
        return json($classic);
    }

    /**
     * 获取当前一期的上一期
     */
    public function previous($index = '')
    {
        (new IndexMustBePositiveInt())->goCheck();
        $classic = ClassicModel::where('index','=',$index-1)
            ->find();
        if(empty($classic)){
            return json([]);
        }
        $classic['fav_nums'] = ClassicFav::where('classic_id',$classic->id)
            ->count();
        return json($classic);
    }

    /**
     * 获取某一期的详细信息
     */
    public function detail( $type ='',$id = '')
    {
        $classic = ClassicModel::where('type','=',$type)
            ->where('id','=',$id)
            ->find();
        if(empty($classic)){
            return json([]);
        }
        $classic['fav_nums'] = ClassicFav::where('classic_id',$classic->id)
            ->count();
        return json($classic);
    }

    /**
     * 获取点赞信息
     */
    public function favor($type ='',$id = '')
    {

    }

    /**
     * 获取我喜欢的期刊
     */
    public function myFavor()
    {

    }
}