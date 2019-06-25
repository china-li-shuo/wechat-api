<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/6/25
 * Time: 10:05
 */


namespace app\api\service;

use app\api\model\Collection;
use app\api\model\CollectionSentence;
use app\api\model\ErrorBook;
use app\api\model\Group;
use app\api\model\LearnedHistory;
use app\api\model\Stage;

class Activity
{

    /**
     * 用户已学习阶段和分组信息
     */
    public function learnedInfo($uid)
    {
        //已学习所有阶段
        $stage = LearnedHistory::where('user_id',$uid)
            ->group('stage')
            ->field('stage')
            ->select();

        if(empty($stage)){
            return null;
        }
        $data = $stage->toArray();
        //阶段名称
        foreach ($data as &$val) {
            $stage = Stage::field('stage_name')
                ->get($val['stage']);
            if(!empty($stage)){
                $val['stage_name'] = $stage->stage_name;
            }
        }

        //获取阶段下所有组
        foreach ($data as $key => &$val) {
            $group = LearnedHistory::where([
                'user_id'=>$uid,
                'stage'=>$val['stage']
            ])  ->group('group')
                ->field('group,stage')
                ->select();
            if(!empty($group)){
                $val['group'] = $group->toArray();
                foreach ($val['group'] as $k=>&$v){
                    $groupName = Group::field('group_name')
                        ->get($v['group']);
                    if(!empty($groupName)){
                        $v['group_name'] = $groupName['group_name'];
                    }else{
                        unset($val['group'][$k]);
                        continue;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 用户错题本阶段和分组信息
     */
    public function errorInfo($uid)
    {
        //错题所有阶段
        $error = ErrorBook::where('user_id',$uid)
            ->group('stage')
            ->field('stage')
            ->select();

        if(empty($error)){
            return null;
        }
        $data = $error->toArray();
        //阶段名称
        foreach ($data as &$val) {
            $stage = Stage::field('stage_name')
                ->get($val['stage']);
            if(!empty($stage)){
                $val['stage_name'] = $stage->stage_name;
            }
        }
        //获取阶段下所有组
        foreach ($data as $key => &$val) {
            $group = ErrorBook::where([
                'user_id'=>$uid,
                'stage'=>$val['stage']
            ])  ->group('group')
                ->field('group,stage')
                ->select();
            if(!empty($group)){
                $val['group'] = $group->toArray();
                foreach ($val['group'] as $k=>&$v){
                    $groupName = Group::field('group_name')
                        ->get($v['group']);
                    if(!empty($groupName)){
                        $v['group_name'] = $groupName['group_name'];
                    }else{
                        unset($val['group'][$k]);
                        continue;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 用户已收藏阶段和分组信息
     */
    public function collectionInfo($uid)
    {
        //错题所有阶段
        $collection = Collection::where('user_id',$uid)
            ->group('stage')
            ->field('stage')
            ->select();

        if(empty($collection)){
            return null;
        }
        $data = $collection->toArray();
        //阶段名称
        foreach ($data as &$val) {
            $stage = Stage::field('stage_name')
                ->get($val['stage']);
            if(!empty($stage)){
                $val['stage_name'] = $stage->stage_name;
            }
        }
        //获取阶段下所有组
        foreach ($data as $key => &$val) {
            $group = Collection::where([
                'user_id'=>$uid,
                'stage'=>$val['stage']
            ])  ->group('group')
                ->field('group,stage')
                ->select();
            if(!empty($group)){
                $val['group'] = $group->toArray();
                foreach ($val['group'] as $k=>&$v){
                    $groupName = Group::field('group_name')
                        ->get($v['group']);
                    if(!empty($groupName)){
                        $v['group_name'] = $groupName['group_name'];
                    }else{
                        unset($val['group'][$k]);
                        continue;
                    }
                }
            }
        }
        return $data;
    }

}